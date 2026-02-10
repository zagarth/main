#include "MIC_Decibel.h"
#include "esp_log.h"
#include "driver/i2s_std.h"
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include <math.h>
#include <string.h>

static const char *TAG = "MIC_Decibel";

// I2S configuration (matching MIC_Speech.c setup)
#define I2S_SAMPLE_RATE     16000
#define I2S_CHANNEL_NUM     1
#define I2S_BITS_PER_SAMPLE 32
#define SAMPLE_BUFFER_SIZE  512  // Number of samples to read for RMS calculation

// Decibel calculation constants
#define REFERENCE_LEVEL     32768.0f  // Reference for 32-bit samples
#define MIN_DB              20.0f     // Minimum dB to display
#define MAX_DB              120.0f    // Maximum dB to clip
#define WARNING_THRESHOLD   95.0f     // Warning threshold in dB

static i2s_chan_handle_t rx_handle = NULL;
static TaskHandle_t decibel_task_handle = NULL;
static float current_db_level = 0.0f;
static float smoothed_db_level = 0.0f;  // Smoothed value for stable display
static bool warning_enabled = false;
static bool warning_active = false;
static bool is_running = false;

#define SMOOTHING_FACTOR 0.3f  // Exponential moving average (0.0-1.0, lower = more smoothing)

// Calculate RMS (Root Mean Square) from PCM samples - simplified for volume meter
static float calculate_rms(int32_t *samples, size_t count) {
    float sum = 0.0f;
    
    for (size_t i = 0; i < count; i++) {
        // Use raw sample values for maximum sensitivity
        float sample_f = (float)abs(samples[i]);
        sum += sample_f;
    }
    
    // Return average amplitude (not squared) for simpler visualization
    return sum / (float)count;
}

// Convert RMS to simple 0-100 volume level (no dB conversion)
static float rms_to_volume(float rms) {
    // Narrow range to capture middle variations better
    const float MIN_LEVEL = 700000000.0f;    // Quiet threshold (700M)
    const float MAX_LEVEL = 1300000000.0f;   // Loud threshold (1.3B)
    
    if (rms < MIN_LEVEL) return 0.0f;
    
    float normalized = (rms - MIN_LEVEL) / (MAX_LEVEL - MIN_LEVEL);
    normalized = normalized * 100.0f;  // Scale to 0-100
    
    // Clamp to 0-100
    if (normalized < 0.0f) normalized = 0.0f;
    if (normalized > 100.0f) normalized = 100.0f;
    
    return normalized;
}

// Decibel monitoring task
static void decibel_task(void *arg) {
    size_t bytes_to_read = SAMPLE_BUFFER_SIZE * sizeof(int32_t);
    int32_t *sample_buffer = (int32_t *)malloc(bytes_to_read);
    
    if (!sample_buffer) {
        ESP_LOGE(TAG, "Failed to allocate sample buffer - stopping decibel monitoring");
        is_running = false;
        vTaskDelete(NULL);
        return;
    }
    
    ESP_LOGI(TAG, "Decibel monitoring task started");
    
    while (is_running) {
        size_t bytes_read = 0;
        
        // Read samples from I2S with timeout
        esp_err_t ret = i2s_channel_read(rx_handle, sample_buffer, bytes_to_read, 
                                          &bytes_read, pdMS_TO_TICKS(1000));
        
        if (ret != ESP_OK) {
            ESP_LOGW(TAG, "I2S read error: %s - continuing...", esp_err_to_name(ret));
            vTaskDelay(pdMS_TO_TICKS(100));
            continue;
        }
        
        if (bytes_read > 0) {
            size_t samples_read = bytes_read / sizeof(int32_t);
            
            // Calculate RMS amplitude
            float rms = calculate_rms(sample_buffer, samples_read);
            
            // Log raw RMS value every 10 cycles to see what we're getting
            static int log_counter = 0;
            if (log_counter++ % 10 == 0) {
                ESP_LOGI(TAG, "Raw RMS: %.2f", rms);
            }
            
            // Convert to simple volume level (0-100)
            float raw_volume = rms_to_volume(rms);
            
            // Apply exponential moving average for smooth, stable readings
            if (smoothed_db_level == 0.0f) {
                smoothed_db_level = raw_volume;  // Initialize on first reading
            } else {
                smoothed_db_level = (SMOOTHING_FACTOR * raw_volume) + ((1.0f - SMOOTHING_FACTOR) * smoothed_db_level);
            }
            
            current_db_level = smoothed_db_level;
            
            // Check for warning condition (volume > 90)
            if (warning_enabled && current_db_level >= 90.0f) {
                if (!warning_active) {
                    warning_active = true;
                    ESP_LOGW(TAG, "WARNING: Volume level %.1f exceeds threshold!", current_db_level);
                }
            } else {
                warning_active = false;
            }
        }
        
        vTaskDelay(pdMS_TO_TICKS(100));  // Update every 100ms
    }
    
    free(sample_buffer);
    ESP_LOGI(TAG, "Decibel monitoring task stopped");
    vTaskDelete(NULL);
}

// Initialize I2S for microphone input
void MIC_Decibel_init(void) {
    ESP_LOGI(TAG, "Initializing decibel monitoring system");
    
    // If already initialized, skip
    if (rx_handle != NULL) {
        ESP_LOGI(TAG, "I2S already initialized, skipping");
        return;
    }
    
    // Configure I2S channel
    i2s_chan_config_t chan_cfg = I2S_CHANNEL_DEFAULT_CONFIG(I2S_NUM_1, I2S_ROLE_MASTER);
    
    esp_err_t ret = i2s_new_channel(&chan_cfg, NULL, &rx_handle);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to create I2S RX channel: %s - Decibel monitoring disabled", esp_err_to_name(ret));
        rx_handle = NULL;
        return;
    }
    
    // Configure I2S standard mode - Using same GPIO as Voice Memo (15, 2, 39)
    i2s_std_config_t std_cfg = {
        .clk_cfg = I2S_STD_CLK_DEFAULT_CONFIG(I2S_SAMPLE_RATE),
        .slot_cfg = I2S_STD_MSB_SLOT_DEFAULT_CONFIG(I2S_DATA_BIT_WIDTH_32BIT, I2S_SLOT_MODE_MONO),
        .gpio_cfg = {
            .mclk = I2S_GPIO_UNUSED,
            .bclk = GPIO_NUM_15,    // BCK pin - Matches Voice Memo
            .ws = GPIO_NUM_2,       // WS pin - Matches Voice Memo
            .dout = I2S_GPIO_UNUSED,
            .din = GPIO_NUM_39,     // DIN pin - Matches Voice Memo
            .invert_flags = {
                .mclk_inv = false,
                .bclk_inv = false,
                .ws_inv = false,
            },
        },
    };
    
    // Use right channel for mono
    std_cfg.slot_cfg.slot_mask = I2S_STD_SLOT_RIGHT;
    
    ret = i2s_channel_init_std_mode(rx_handle, &std_cfg);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to init I2S standard mode: %s", esp_err_to_name(ret));
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
        return;
    }
    
    ret = i2s_channel_enable(rx_handle);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to enable I2S channel: %s", esp_err_to_name(ret));
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
        return;
    }
    
    ESP_LOGI(TAG, "I2S initialized for decibel monitoring");
}

// Start decibel monitoring
void MIC_Decibel_start(void) {
    if (is_running) {
        ESP_LOGW(TAG, "Decibel monitoring already running");
        return;
    }
    
    if (!rx_handle) {
        ESP_LOGE(TAG, "I2S not initialized - cannot start decibel monitoring");
        is_running = false;
        return;
    }
    
    is_running = true;
    
    // Create monitoring task
    BaseType_t task_ret = xTaskCreate(decibel_task, "decibel_task", 4096, NULL, 5, &decibel_task_handle);
    if (task_ret != pdPASS) {
        ESP_LOGE(TAG, "Failed to create decibel task");
        is_running = false;
        return;
    }
    
    ESP_LOGI(TAG, "Decibel monitoring started");
}

// Stop decibel monitoring
void MIC_Decibel_stop(void) {
    if (!is_running) {
        ESP_LOGW(TAG, "Decibel monitoring not running");
        return;
    }
    
    is_running = false;
    
    // Wait for task to finish
    if (decibel_task_handle) {
        vTaskDelay(pdMS_TO_TICKS(200));  // Give task time to clean up
        decibel_task_handle = NULL;
    }
    
    current_db_level = 0.0f;
    warning_active = false;
    
    ESP_LOGI(TAG, "Decibel monitoring stopped");
}

// Get current decibel level
float MIC_Decibel_get_level(void) {
    return current_db_level;
}

// Enable/disable warning system
void MIC_Decibel_set_warning_enabled(bool enabled) {
    warning_enabled = enabled;
    if (!enabled) {
        warning_active = false;
    }
    ESP_LOGI(TAG, "Warning system %s", enabled ? "enabled" : "disabled");
}

bool MIC_Decibel_is_warning_enabled(void) {
    return warning_enabled;
}

// Check if warning is currently active
bool MIC_Decibel_is_warning_active(void) {
    return warning_active;
}

// Deinitialize
void MIC_Decibel_deinit(void) {
    MIC_Decibel_stop();
    
    if (rx_handle) {
        i2s_channel_disable(rx_handle);
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
    }
    
    ESP_LOGI(TAG, "Decibel monitoring deinitialized");
}
