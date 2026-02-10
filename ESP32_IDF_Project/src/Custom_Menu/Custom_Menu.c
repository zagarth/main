/**
 * Ultra Simple Menu - ONE button, battery ring
 * Start simple, add features one at a time
 */

#include "Custom_Menu.h"
#include "lvgl.h"
#include "SD_MMC.h"
#include "home_icon.h"
#include "BAT_Driver.h"
#include "QMI8658.h"
#include "Display_SPD2010.h"
#include "PCM5101.h"
#include "Wireless.h"
#include "MIC_Speech.h"
#include "maps_config.h"
#include "esp_log.h"
#include "driver/i2s_std.h"
#include "nvs_flash.h"
#include "nvs.h"
#include "esp_http_client.h"
#include "cJSON.h"
#include "esp_crt_bundle.h"
#include "esp_system.h"
#include "esp_heap_caps.h"
#include "esp_wifi.h"
#include "esp_event.h"
#include "esp_netif.h"
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include <sys/stat.h>
#include <dirent.h>
#include <time.h>
#include <errno.h>
#include <string.h>

static const char *TAG = "SimpleMenu";

// Battery ring (the animated rim that worked)
static lv_obj_t *battery_ring = NULL;
static lv_timer_t *battery_timer = NULL;
static float last_logged_voltage = 0.0f;

// Backlight timeout settings
static lv_timer_t *backlight_timer = NULL;
static bool timeout_enabled = false;  // false = always on, true = 30s timeout

// Voice memo recording state
static bool is_recording = false;
static FILE *record_file = NULL;
static i2s_chan_handle_t rx_handle = NULL;
static lv_timer_t *record_timer = NULL;
static int record_seconds = 0;
static lv_obj_t *record_status_label = NULL;
static lv_obj_t *record_time_label = NULL;
static char last_recorded_file[64] = {0};  // Store last filename for verification

// Pin Mode settings
static bool pin_mode_enabled = true;  // Default ON - show hardcoded image on boot
static lv_obj_t *pin_mode_toggle = NULL;
static uint32_t last_tap_time = 0;
static bool skip_pin_mode_once = false;
static char selected_image_file[64] = "home_icon.bin";  // Default image file
static lv_obj_t *preview_img = NULL;  // Preview image object for live updates
static lv_obj_t *preview_container = NULL;  // Preview container

// Pin Mode image loading
static uint8_t *sd_image_buffer = NULL;  // Buffer for SD card image
static lv_img_dsc_t sd_image_dsc;       // Descriptor for SD card image
static bool using_sd_image = false;     // Track which image source is active

// Animation state (dual buffer for streaming frames from SD)
static bool is_animation = false;       // Track if current image is an animation
static uint8_t *frame_buffer_a = NULL;  // First frame buffer (ping)
static uint8_t *frame_buffer_b = NULL;  // Second frame buffer (pong)
static lv_img_dsc_t frame_dsc_a;        // Descriptor for buffer A
 static lv_img_dsc_t frame_dsc_b;        // Descriptor for buffer B
static bool display_buffer_a = true;    // Which buffer is currently displayed
static int current_frame = 0;           // Current frame index
static int total_frames = 0;            // Total number of frames in animation
static lv_timer_t *frame_timer = NULL;  // Timer for frame advancement
static lv_obj_t *anim_img = NULL;       // Animation image object
static char anim_prefix[64] = {0};      // Animation file prefix (e.g., "laughingman")

// WiFi configuration state
static lv_obj_t *wifi_ssid_ta = NULL;   // SSID text area
static lv_obj_t *wifi_pass_ta = NULL;   // Password text area
static lv_obj_t *wifi_keyboard = NULL;  // On-screen keyboard
static lv_obj_t *wifi_status_label = NULL;  // Status label
static bool wifi_connected = false;     // WiFi connection state
static char wifi_connected_ssid[33] = {0};  // Currently connected SSID
static char wifi_connected_password[65] = {0};  // Currently connected password

// Google Maps state
static lv_obj_t *maps_origin_ta = NULL;     // Origin text area
static lv_obj_t *maps_dest_ta = NULL;       // Destination text area
static lv_obj_t *maps_keyboard = NULL;      // On-screen keyboard
static lv_obj_t *maps_status_label = NULL;  // Status label
static char maps_api_key[64] = {0};         // Decoded API key
static bool maps_is_recording = false;      // Voice input recording state
static lv_obj_t *maps_active_ta = NULL;     // Which text area is being voice-filled
static lv_obj_t *maps_record_btn = NULL;    // Current recording button
static FILE *maps_record_file = NULL;       // Audio recording file for Maps voice input
static lv_timer_t *maps_record_timer = NULL;// Timer for Maps voice recording
static uint32_t maps_record_tick_count = 0; // Tick counter for auto-stop (30 ticks = 3 seconds)

// Saved locations
#define MAPS_HOME_ADDRESS "37 Mark Street, London, Ontario"

// External variables
extern uint32_t SDCard_Size;
extern uint8_t LCD_Backlight;

// Forward declarations
static void btn_directions_cb(lv_event_t *e);
static void maps_ta_event_cb(lv_event_t *e);
static void maps_keyboard_event_cb(lv_event_t *e);
static void btn_get_directions_cb(lv_event_t *e);
static void btn_maps_voice_origin_cb(lv_event_t *e);
static void btn_maps_voice_dest_cb(lv_event_t *e);
static void btn_maps_home_cb(lv_event_t *e);
static void maps_voice_tick_cb(lv_timer_t *t);
static void maps_stop_recording(void);
static void maps_send_to_stt_api(const char *audio_file);
static void maps_send_to_stt_api_task(void *param);
static esp_err_t init_i2s_mic(void);
static void write_wav_header(FILE *fp, uint32_t data_size);
static void btn_display_cb(lv_event_t *e);
static void btn_sys_cb(lv_event_t *e);
static void btn_wifi_cb(lv_event_t *e);
static void btn_wifi_connect_cb(lv_event_t *e);
static void btn_wifi_save_cb(lv_event_t *e);
static void btn_wifi_list_cb(lv_event_t *e);
static void wifi_network_select_cb(lv_event_t *e);
static void wifi_ta_event_cb(lv_event_t *e);
static void wifi_keyboard_event_cb(lv_event_t *e);
static void wifi_show_pass_cb(lv_event_t *e);
static void wifi_show_status(const char *ssid);
static void btn_wifi_disconnect_cb(lv_event_t *e);
static void btn_bluetooth_cb(lv_event_t *e);
static void btn_pair_cb(lv_event_t *e);
static void btn_voice_cb(lv_event_t *e);
static void btn_pinmode_cb(lv_event_t *e);
static void btn_back_cb(lv_event_t *e);
static void slider_brightness_cb(lv_event_t *e);
static void home_image_tap_cb(lv_event_t *e);
static void btn_always_on_cb(lv_event_t *e);
static void btn_30s_timeout_cb(lv_event_t *e);
static void backlight_timeout_cb(lv_timer_t *t);
static void btn_record_cb(lv_event_t *e);
static void btn_stop_record_cb(lv_event_t *e);
static void record_tick_cb(lv_timer_t *t);
static void update_battery_ring(void);
static void bat_timer_cb(lv_timer_t *t);
static void refresh_voice_list(lv_obj_t *list);

// Battery ring update (this worked before)
static void update_battery_ring_ex(bool hide_when_good) {
    if (!battery_ring) {
        battery_ring = lv_obj_create(lv_scr_act());
        lv_obj_set_size(battery_ring, 412, 412);
        lv_obj_center(battery_ring);
        lv_obj_set_style_bg_opa(battery_ring, LV_OPA_TRANSP, 0);
        lv_obj_set_style_border_width(battery_ring, 6, 0);
        lv_obj_set_style_radius(battery_ring, LV_RADIUS_CIRCLE, 0);
        lv_obj_clear_flag(battery_ring, LV_OBJ_FLAG_CLICKABLE);
        lv_obj_clear_flag(battery_ring, LV_OBJ_FLAG_SCROLLABLE);
        lv_obj_move_to_index(battery_ring, 95);
    }
    
    // Get battery voltage - fix the 0V issue
    float volts = BAT_Get_Volts();
    
    // Validate voltage reading
    if (volts < 0.0f || volts != volts) {  // Check for negative or NaN
        volts = 3.7f;  // Default safe value
    }
    
    // BAT_Get_Volts might return millivolts, check if value is huge
    if (volts > 100.0f) {
        volts = volts / 1000.0f;  // Convert mV to V
    }
    
    // Only log if voltage changed significantly (0.05V)
    if (last_logged_voltage == 0.0f || fabsf(volts - last_logged_voltage) >= 0.05f) {
        ESP_LOGI(TAG, "Battery: %.2fV", volts);
        last_logged_voltage = volts;
    }
    
    // Hide ring when battery is good (> 3.7V) - only in Pin Mode
    if (hide_when_good && volts > 3.7f) {
        if (battery_ring) {
            lv_obj_add_flag(battery_ring, LV_OBJ_FLAG_HIDDEN);
        }
        return;
    }
    
    // Show ring
    if (battery_ring) {
        lv_obj_clear_flag(battery_ring, LV_OBJ_FLAG_HIDDEN);
    }
    
    // Color based on voltage
    uint32_t color;
    if (volts > 4.0f) {
        color = 0x00FF00;  // Green - full
    } else if (volts > 3.7f) {
        color = 0xFFFF00;  // Yellow - getting low
    } else if (volts > 3.4f) {
        color = 0xFF8800;  // Orange - low
    } else {
        color = 0xFF0000;  // Red - critical
    }
    
    lv_obj_set_style_border_color(battery_ring, lv_color_hex(color), 0);
}

// Wrapper function - always show ring
static void update_battery_ring(void) {
    update_battery_ring_ex(false);
}

// Battery timer callback
static void bat_timer_cb(lv_timer_t *t) {
    update_battery_ring();
}

// Pin Mode battery timer callback - hides ring when battery good
static void pin_mode_bat_timer_cb(lv_timer_t *t) {
    update_battery_ring_ex(true);
}

// Image selector dropdown callback
static void image_selector_cb(lv_event_t *e) {
    if (lv_event_get_code(e) != LV_EVENT_VALUE_CHANGED) return;
    lv_obj_t *dd = lv_event_get_target(e);
    char buf[64];
    lv_dropdown_get_selected_str(dd, buf, sizeof(buf));
    
    // Check if "Default (Embedded)" is selected
    if (strcmp(buf, "Default (Embedded)") == 0) {
        strcpy(selected_image_file, "");  // Empty string = use default
        ESP_LOGI(TAG, "Selected default embedded image");
        
        // Save empty string to NVS to indicate default
        nvs_handle_t nvs_handle;
        if (nvs_open("storage", NVS_READWRITE, &nvs_handle) == ESP_OK) {
            nvs_set_str(nvs_handle, "pin_image", "");
            nvs_commit(nvs_handle);
            nvs_close(nvs_handle);
            ESP_LOGI(TAG, "✓ Saved default image selection to NVS");
        }
        
        // Update preview with embedded icon
        if (preview_img) {
            lv_img_set_src(preview_img, &home_icon);
            ESP_LOGI(TAG, "✓ Preview updated with default embedded icon");
        }
        return;
    }
    
    // Remove play icon if present (animation indicator)
    char clean_buf[56];  // Sized to fit in selected_image_file with _000.bin suffix
    const char *start = buf;
    if (strncmp(buf, LV_SYMBOL_PLAY " ", strlen(LV_SYMBOL_PLAY) + 1) == 0) {
        start = buf + strlen(LV_SYMBOL_PLAY) + 1;  // Skip icon and space
    }
    strncpy(clean_buf, start, sizeof(clean_buf) - 1);
    clean_buf[sizeof(clean_buf) - 1] = '\0';
    
    // Determine if this is an animation (check for _000.bin file existence)
    char test_path[128];
    snprintf(test_path, sizeof(test_path), "/sdcard/%s_000.bin", clean_buf);
    FILE *test = fopen(test_path, "rb");
    bool is_anim = (test != NULL);
    if (test) fclose(test);
    
    // Store filename for loading
    if (is_anim) {
        // Store as animation first frame
        snprintf(selected_image_file, sizeof(selected_image_file), "%s_000.bin", clean_buf);
        ESP_LOGI(TAG, "Selected animation: %s (will load all frames)", clean_buf);
    } else {
        // Store as static image with .bin extension
        snprintf(selected_image_file, sizeof(selected_image_file), "%s.bin", clean_buf);
        ESP_LOGI(TAG, "Selected static image: %s", selected_image_file);
    }
    
    // Save to NVS
    nvs_handle_t nvs_handle;
    if (nvs_open("storage", NVS_READWRITE, &nvs_handle) == ESP_OK) {
        nvs_set_str(nvs_handle, "pin_image", selected_image_file);
        nvs_commit(nvs_handle);
        nvs_close(nvs_handle);
        ESP_LOGI(TAG, "✓ Saved image selection to NVS");
    }
    
    // Update preview with selected image
    if (preview_img && preview_container) {
        // For animations, show first frame
        char preview_path[128];
        if (is_anim) {
            snprintf(preview_path, sizeof(preview_path), "/sdcard/%s_000.bin", clean_buf);
        } else {
            snprintf(preview_path, sizeof(preview_path), "/sdcard/%s.bin", clean_buf);
        }
        
        FILE *f = fopen(preview_path, "rb");
        
        if (f != NULL) {
            fseek(f, 0, SEEK_END);
            long file_size = ftell(f);
            fseek(f, 0, SEEK_SET);
            
            if (file_size == 131072) {
                // Allocate temp buffer for preview
                uint8_t *temp_buf = (uint8_t*)heap_caps_malloc(file_size, MALLOC_CAP_SPIRAM);
                if (temp_buf && fread(temp_buf, 1, file_size, f) == file_size) {
                    // Create temporary image descriptor
                    static lv_img_dsc_t temp_dsc;
                    temp_dsc.header.always_zero = 0;
                    temp_dsc.header.w = 256;
                    temp_dsc.header.h = 256;
                    temp_dsc.header.cf = LV_IMG_CF_TRUE_COLOR;
                    temp_dsc.data_size = file_size;
                    temp_dsc.data = temp_buf;
                    
                    // Update preview image
                    lv_img_set_src(preview_img, &temp_dsc);
                    ESP_LOGI(TAG, "✓ Preview updated with %s%s", clean_buf, is_anim ? " (animation frame 0)" : "");
                    
                    // Note: temp_buf will leak here, but it's only for preview
                    // In production, we'd track and free old buffers
                } else {
                    ESP_LOGW(TAG, "Failed to load preview");
                    if (temp_buf) heap_caps_free(temp_buf);
                }
            }
            fclose(f);
        } else {
            ESP_LOGW(TAG, "Preview file not found: %s", preview_path);
        }
    }
}

/**
 * Frame timer callback - advance animation by swapping buffers
 */
static void frame_timer_cb(lv_timer_t *t) {
    if (!is_animation || !anim_img) return;
    
    // Advance to next frame
    current_frame = (current_frame + 1) % total_frames;
    
    // Determine which buffer to load next
    uint8_t *load_buffer = display_buffer_a ? frame_buffer_b : frame_buffer_a;
    
    // Load next frame into background buffer
    int next_frame = (current_frame + 1) % total_frames;
    char frame_path[128];
    snprintf(frame_path, sizeof(frame_path), "/sdcard/%s_%03d.bin", anim_prefix, next_frame);
    
    FILE *f = fopen(frame_path, "rb");
    if (f) {
        fread(load_buffer, 1, 131072, f);
        fclose(f);
    }
    
    // Swap display buffer - show current frame
    const lv_img_dsc_t *display_dsc = display_buffer_a ? &frame_dsc_a : &frame_dsc_b;
    lv_img_set_src(anim_img, display_dsc);
    
    // Toggle buffer
    display_buffer_a = !display_buffer_a;
}

/**
 * Try to load image from SD card, fallback to embedded image
 * Detects animations (frame sequences) and sets up dual-buffer streaming
 * Returns pointer to image descriptor to use
 */
static const lv_img_dsc_t* load_pin_mode_image(void) {
    // Check if default (embedded) image is selected
    if (selected_image_file[0] == '\0' || strcmp(selected_image_file, "Default (Embedded)") == 0) {
        ESP_LOGI(TAG, "Using default embedded image");
        using_sd_image = false;
        is_animation = false;
        return &home_icon;
    }
    
    // Check if this is an animation (look for _000.bin pattern)
    // Extract prefix from selected file (e.g., "laughingman_000.bin" -> "laughingman")
    char prefix[64] = {0};
    const char *underscore = strrchr(selected_image_file, '_');
    if (underscore && strlen(underscore) == 8 && strcmp(underscore + 4, ".bin") == 0) {
        // Likely animation format: prefix_NNN.bin
        size_t prefix_len = underscore - selected_image_file;
        strncpy(prefix, selected_image_file, prefix_len);
        prefix[prefix_len] = '\0';
        
        // Count frames by checking for _000.bin, _001.bin, etc.
        int frame_count = 0;
        char test_path[128];
        for (int i = 0; i < 999; i++) {
            snprintf(test_path, sizeof(test_path), "/sdcard/%s_%03d.bin", prefix, i);
            FILE *test = fopen(test_path, "rb");
            if (test) {
                fclose(test);
                frame_count++;
            } else {
                break;
            }
        }
        
        if (frame_count >= 2) {
            ESP_LOGI(TAG, "✓ Detected animation: %s with %d frames", prefix, frame_count);
            
            // Allocate dual buffers for animation streaming
            frame_buffer_a = (uint8_t*)heap_caps_malloc(131072, MALLOC_CAP_SPIRAM);
            frame_buffer_b = (uint8_t*)heap_caps_malloc(131072, MALLOC_CAP_SPIRAM);
            
            if (!frame_buffer_a || !frame_buffer_b) {
                ESP_LOGE(TAG, "Failed to allocate dual buffers for animation");
                if (frame_buffer_a) heap_caps_free(frame_buffer_a);
                if (frame_buffer_b) heap_caps_free(frame_buffer_b);
                frame_buffer_a = frame_buffer_b = NULL;
                using_sd_image = false;
                is_animation = false;
                return &home_icon;
            }
            
            // Load first two frames
            snprintf(test_path, sizeof(test_path), "/sdcard/%s_000.bin", prefix);
            FILE *f0 = fopen(test_path, "rb");
            snprintf(test_path, sizeof(test_path), "/sdcard/%s_001.bin", prefix);
            FILE *f1 = fopen(test_path, "rb");
            
            if (!f0 || !f1) {
                ESP_LOGE(TAG, "Failed to open first animation frames");
                if (f0) fclose(f0);
                if (f1) fclose(f1);
                heap_caps_free(frame_buffer_a);
                heap_caps_free(frame_buffer_b);
                frame_buffer_a = frame_buffer_b = NULL;
                using_sd_image = false;
                is_animation = false;
                return &home_icon;
            }
            
            fread(frame_buffer_a, 1, 131072, f0);
            fread(frame_buffer_b, 1, 131072, f1);
            fclose(f0);
            fclose(f1);
            
            // Setup descriptors for both buffers
            frame_dsc_a.header.always_zero = 0;
            frame_dsc_a.header.w = 256;
            frame_dsc_a.header.h = 256;
            frame_dsc_a.header.cf = LV_IMG_CF_TRUE_COLOR;
            frame_dsc_a.data_size = 131072;
            frame_dsc_a.data = frame_buffer_a;
            
            frame_dsc_b.header.always_zero = 0;
            frame_dsc_b.header.w = 256;
            frame_dsc_b.header.h = 256;
            frame_dsc_b.header.cf = LV_IMG_CF_TRUE_COLOR;
            frame_dsc_b.data_size = 131072;
            frame_dsc_b.data = frame_buffer_b;
            
            // Setup animation state
            strncpy(anim_prefix, prefix, sizeof(anim_prefix) - 1);
            total_frames = frame_count;
            current_frame = 0;
            display_buffer_a = true;
            is_animation = true;
            using_sd_image = true;
            
            ESP_LOGI(TAG, "✓ Animation ready: %d frames, dual-buffer streaming", total_frames);
            return &frame_dsc_a;  // Start with first frame
        }
    }
    
    // Not an animation - load as single static image
    char sd_path[128];
    snprintf(sd_path, sizeof(sd_path), "/sdcard/%s", selected_image_file);
    FILE *f = NULL;
    
    ESP_LOGI(TAG, "Attempting to load static image from SD card: %s", sd_path);
    
    // Try to open SD card image
    f = fopen(sd_path, "rb");
    if (f == NULL) {
        ESP_LOGW(TAG, "SD card image not found or SD card not mounted, using embedded image");
        using_sd_image = false;
        is_animation = false;
        return &home_icon;  // Fallback to embedded
    }
    
    // Check file size (should be 131,072 bytes for 256x256 RGB565)
    fseek(f, 0, SEEK_END);
    long file_size = ftell(f);
    fseek(f, 0, SEEK_SET);
    
    if (file_size != 131072) {
        ESP_LOGW(TAG, "Invalid SD image size: %ld bytes (expected 131,072), using embedded image", file_size);
        fclose(f);
        using_sd_image = false;
        is_animation = false;
        return &home_icon;  // Fallback to embedded
    }
    
    // Allocate buffer in PSRAM for image data
    sd_image_buffer = (uint8_t*)heap_caps_malloc(file_size, MALLOC_CAP_SPIRAM);
    if (sd_image_buffer == NULL) {
        ESP_LOGE(TAG, "Failed to allocate PSRAM for SD image (%ld bytes), using embedded image", file_size);
        fclose(f);
        using_sd_image = false;
        is_animation = false;
        return &home_icon;  // Fallback to embedded
    }
    
    // Read image data
    size_t bytes_read = fread(sd_image_buffer, 1, file_size, f);
    fclose(f);
    
    if (bytes_read != file_size) {
        ESP_LOGE(TAG, "Failed to read SD image (read %d of %ld bytes), using embedded image", bytes_read, file_size);
        heap_caps_free(sd_image_buffer);
        sd_image_buffer = NULL;
        using_sd_image = false;
        is_animation = false;
        return &home_icon;  // Fallback to embedded
    }
    
    // Setup LVGL image descriptor for SD card data
    sd_image_dsc.header.always_zero = 0;
    sd_image_dsc.header.w = 256;
    sd_image_dsc.header.h = 256;
    sd_image_dsc.header.cf = LV_IMG_CF_TRUE_COLOR;  // RGB565
    sd_image_dsc.data_size = file_size;
    sd_image_dsc.data = sd_image_buffer;
    
    ESP_LOGI(TAG, "✓ Successfully loaded static image from SD card (%ld bytes)", file_size);
    using_sd_image = true;
    is_animation = false;
    return &sd_image_dsc;
}

// Initialize
// LVGL filesystem driver callbacks for SD card
static void * fs_open_cb(lv_fs_drv_t * drv, const char * path, lv_fs_mode_t mode) {
    const char * flags = "";
    if(mode == LV_FS_MODE_WR) flags = "wb";
    else if(mode == LV_FS_MODE_RD) flags = "rb";
    else if(mode == (LV_FS_MODE_WR | LV_FS_MODE_RD)) flags = "rb+";
    
    char full_path[128];
    snprintf(full_path, sizeof(full_path), "/sdcard/%s", path);
    ESP_LOGI(TAG, "FS OPEN: %s -> %s", path, full_path);
    FILE * f = fopen(full_path, flags);
    if (!f) {
        ESP_LOGE(TAG, "FS OPEN FAILED: %s (errno: %d)", full_path, errno);
    } else {
        ESP_LOGI(TAG, "FS OPEN SUCCESS: %p", f);
    }
    return (void *)f;
}

static lv_fs_res_t fs_close_cb(lv_fs_drv_t * drv, void * file_p) {
    fclose((FILE *)file_p);
    return LV_FS_RES_OK;
}

static lv_fs_res_t fs_read_cb(lv_fs_drv_t * drv, void * file_p, void * buf, uint32_t btr, uint32_t * br) {
    ESP_LOGI(TAG, "FS READ: requested %lu bytes", (unsigned long)btr);
    *br = fread(buf, 1, btr, (FILE *)file_p);
    ESP_LOGI(TAG, "FS READ: got %lu bytes", (unsigned long)*br);
    return LV_FS_RES_OK;
}

static lv_fs_res_t fs_seek_cb(lv_fs_drv_t * drv, void * file_p, uint32_t pos, lv_fs_whence_t whence) {
    int w = SEEK_SET;
    if(whence == LV_FS_SEEK_CUR) w = SEEK_CUR;
    else if(whence == LV_FS_SEEK_END) w = SEEK_END;
    fseek((FILE *)file_p, pos, w);
    return LV_FS_RES_OK;
}

static lv_fs_res_t fs_tell_cb(lv_fs_drv_t * drv, void * file_p, uint32_t * pos_p) {
    *pos_p = ftell((FILE *)file_p);
    return LV_FS_RES_OK;
}

// Double-tap handler to return to menu from Pin Mode image
static void home_image_tap_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    
    ESP_LOGI(TAG, "!!! EVENT RECEIVED: code=%d !!!", code);
    
    if (code == LV_EVENT_CLICKED) {
        uint32_t now = lv_tick_get();
        uint32_t elapsed = now - last_tap_time;
        
        ESP_LOGI(TAG, "CLICKED event - elapsed since last tap: %lu ms", elapsed);
        
        // Double-tap detected (within 1500ms)
        if (last_tap_time > 0 && elapsed < 1500) {
            ESP_LOGI(TAG, "!!! DOUBLE-TAP DETECTED - RETURNING TO MENU !!!");
            last_tap_time = 0;  // Reset
            skip_pin_mode_once = true;  // Skip Pin Mode and show menu
            
            // Play chirp sound
            Play_Music("/sdcard", "chirp.mp3");
            
            // Cleanup animation resources if active
            if (is_animation) {
                if (frame_timer) {
                    lv_timer_del(frame_timer);
                    frame_timer = NULL;
                }
                if (frame_buffer_a) {
                    heap_caps_free(frame_buffer_a);
                    frame_buffer_a = NULL;
                }
                if (frame_buffer_b) {
                    heap_caps_free(frame_buffer_b);
                    frame_buffer_b = NULL;
                }
                anim_img = NULL;
                is_animation = false;
                ESP_LOGI(TAG, "Freed animation dual buffers and stopped timer");
            }
            
            // Cleanup SD image buffer if it was used (static images)
            if (using_sd_image && sd_image_buffer != NULL) {
                ESP_LOGI(TAG, "Freeing SD card image buffer");
                heap_caps_free(sd_image_buffer);
                sd_image_buffer = NULL;
                using_sd_image = false;
            }
            
            Custom_Menu_Init();  // Reload menu
        } else {
            last_tap_time = now;
            ESP_LOGI(TAG, "Single tap registered - tap_time=%lu - waiting for second tap", now);
        }
    } else {
        ESP_LOGI(TAG, "Other event code: %d (not CLICKED)", code);
    }
}

// =============================================================================
// WIFI EVENT HANDLER
// =============================================================================

static void wifi_event_handler(void* arg, esp_event_base_t event_base, int32_t event_id, void* event_data) {
    if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_START) {
        ESP_LOGI(TAG, "WiFi STA started");
    } else if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_DISCONNECTED) {
        wifi_event_sta_disconnected_t* event = (wifi_event_sta_disconnected_t*) event_data;
        wifi_connected = false;
        ESP_LOGW(TAG, "WiFi disconnected, reason: %d", event->reason);
    } else if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_CONNECTED) {
        ESP_LOGI(TAG, "WiFi connected to AP");
    } else if (event_base == IP_EVENT && event_id == IP_EVENT_STA_GOT_IP) {
        ip_event_got_ip_t* event = (ip_event_got_ip_t*) event_data;
        ESP_LOGI(TAG, "WiFi got IP: " IPSTR, IP2STR(&event->ip_info.ip));
        wifi_connected = true;
    } else {
        ESP_LOGI(TAG, "WiFi event: base=%s, id=%ld", event_base, event_id);
    }
}

// =============================================================================
// DECIBEL MONITORING SYSTEM
// =============================================================================

void Custom_Menu_Init(void) {
    ESP_LOGI(TAG, "Init - Ultra Simple Version");
    
    // Register LVGL filesystem driver for SD card
    static lv_fs_drv_t fs_drv;
    lv_fs_drv_init(&fs_drv);
    fs_drv.letter = 'A';
    fs_drv.open_cb = fs_open_cb;
    fs_drv.close_cb = fs_close_cb;
    fs_drv.read_cb = fs_read_cb;
    fs_drv.seek_cb = fs_seek_cb;
    fs_drv.tell_cb = fs_tell_cb;
    lv_fs_drv_register(&fs_drv);
    ESP_LOGI(TAG, "LVGL filesystem driver registered (A:)");
    
    // Register WiFi event handlers and ensure WiFi is in STA mode
    esp_event_handler_register(WIFI_EVENT, ESP_EVENT_ANY_ID, &wifi_event_handler, NULL);
    esp_event_handler_register(IP_EVENT, IP_EVENT_STA_GOT_IP, &wifi_event_handler, NULL);
    
    // Ensure WiFi is in STA mode and started (Wireless.h may have initialized it)
    esp_wifi_set_mode(WIFI_MODE_STA);
    esp_wifi_start();
    ESP_LOGI(TAG, "WiFi event handlers registered and WiFi started in STA mode");
    
    // Load Pin Mode settings from NVS
    nvs_handle_t nvs_handle;
    esp_err_t err = nvs_open("storage", NVS_READWRITE, &nvs_handle);
    if (err == ESP_OK) {
        uint8_t pin_mode = 1;
        if (nvs_get_u8(nvs_handle, "pin_mode", &pin_mode) == ESP_OK) {
            pin_mode_enabled = (pin_mode == 1);
            ESP_LOGI(TAG, "Loaded Pin Mode: %s", pin_mode_enabled ? "ON" : "OFF");
        } else {
            pin_mode_enabled = true;  // Default ON
            ESP_LOGI(TAG, "No NVS Pin Mode setting, defaulting to ON");
        }
        
        // Load selected image filename
        size_t len = sizeof(selected_image_file);
        if (nvs_get_str(nvs_handle, "pin_image", selected_image_file, &len) == ESP_OK) {
            ESP_LOGI(TAG, "Loaded Pin Image: %s", selected_image_file);
        } else {
            ESP_LOGI(TAG, "Using default image: %s", selected_image_file);
        }
        nvs_close(nvs_handle);
    } else {
        ESP_LOGI(TAG, "NVS not available, using code defaults: pin_mode=ON");
    }
    
    battery_ring = NULL;
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    update_battery_ring_ex(false);  // Show ring for menu/pin mode
    
    // Check Pin Mode - show image on boot or show menu (unless bypassed by double-tap)
    if (pin_mode_enabled && !skip_pin_mode_once) {
        ESP_LOGI(TAG, "=== PIN MODE ENABLED - SETTING UP HOME IMAGE ===");
        
        // Change background to white for Pin Mode image display
        lv_obj_set_style_bg_color(lv_scr_act(), lv_color_white(), 0);
        
        // Try to load from SD card, fallback to embedded image
        const lv_img_dsc_t *img_src = load_pin_mode_image();
        ESP_LOGI(TAG, "Using %s image", using_sd_image ? "SD card" : "embedded");
        ESP_LOGI(TAG, "Image descriptor: w=%d, h=%d, cf=%d", img_src->header.w, img_src->header.h, img_src->header.cf);
        
        // Full screen image display - zoom to fill 412x412 circular display
        lv_obj_t *home_img = lv_img_create(lv_scr_act());
        ESP_LOGI(TAG, "Image object created: %p", home_img);
        
        lv_img_set_src(home_img, img_src);
        ESP_LOGI(TAG, "Image source set to %s%s", 
                 using_sd_image ? "SD card buffer" : "embedded home_icon",
                 is_animation ? " (ANIMATION)" : "");
        
        lv_img_set_zoom(home_img, 412);  // 412 = 161% zoom (256x256 -> ~412x412 to fill screen)
        ESP_LOGI(TAG, "Image zoom set to 412 (161%%)");
        
        lv_obj_center(home_img);
        ESP_LOGI(TAG, "Image centered on screen");
        
        // Add double-tap detection to the image itself
        lv_obj_add_flag(home_img, LV_OBJ_FLAG_CLICKABLE);
        lv_obj_clear_flag(home_img, LV_OBJ_FLAG_SCROLLABLE);
        ESP_LOGI(TAG, "Image set as CLICKABLE");
        
        lv_obj_move_to_index(home_img, 200);  // Move to very top
        ESP_LOGI(TAG, "Image z-index set to 200 (on top of everything)");
        
        lv_obj_add_event_cb(home_img, home_image_tap_cb, LV_EVENT_CLICKED, NULL);
        ESP_LOGI(TAG, "Event callback attached to image for LV_EVENT_CLICKED");
        
        last_tap_time = 0;  // Reset tap timer
        ESP_LOGI(TAG, "Tap timer reset to 0");
        
        // If this is an animation, start frame timer
        if (is_animation) {
            anim_img = home_img;  // Track for frame updates
            if (frame_timer) {
                lv_timer_del(frame_timer);
            }
            frame_timer = lv_timer_create(frame_timer_cb, 33, NULL);  // 33ms = 30.3 FPS
            ESP_LOGI(TAG, "✓ Animation timer started: %d frames at 30.3 FPS", total_frames);
        } else {
            anim_img = NULL;
        }
        
        ESP_LOGI(TAG, "=== HOME IMAGE SETUP COMPLETE - TOUCH SHOULD WORK ===");
        
        // Battery timer - delete old one first to prevent leaks
        if (battery_timer) {
            lv_timer_del(battery_timer);
        }
        battery_timer = lv_timer_create(pin_mode_bat_timer_cb, 5000, NULL);
        return;
    }
    
    skip_pin_mode_once = false;  // Reset flag
    ESP_LOGI(TAG, "Pin Mode disabled - showing menu");
    
    ESP_LOGI(TAG, "Showing menu");
    
    // 2x2 Grid of buttons with icons (centered)
    int btn_size = 140;
    int spacing = 20;
    int start_y = 56;  // Center vertically: (412 - 300) / 2
    
    // Row 1, Col 1: Directions (Google Maps)
    lv_obj_t *btn_directions = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_directions, btn_size, btn_size);
    lv_obj_align(btn_directions, LV_ALIGN_TOP_MID, -btn_size/2 - spacing/2, start_y);
    lv_obj_set_style_bg_color(btn_directions, lv_color_hex(0x4285F4), 0);
    lv_obj_set_style_radius(btn_directions, 20, 0);
    lv_obj_add_event_cb(btn_directions, btn_directions_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_directions = lv_label_create(btn_directions);
    lv_label_set_text(label_directions, LV_SYMBOL_GPS "\nMaps");
    lv_obj_set_style_text_font(label_directions, &lv_font_montserrat_16, 0);
    lv_obj_center(label_directions);
    
    // Row 1, Col 2: System Info
    lv_obj_t *btn_sys = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_sys, btn_size, btn_size);
    lv_obj_align(btn_sys, LV_ALIGN_TOP_MID, btn_size/2 + spacing/2, start_y);
    lv_obj_set_style_bg_color(btn_sys, lv_color_hex(0x1565C0), 0);
    lv_obj_set_style_radius(btn_sys, 20, 0);
    lv_obj_add_event_cb(btn_sys, btn_sys_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_sys = lv_label_create(btn_sys);
    lv_label_set_text(label_sys, LV_SYMBOL_SETTINGS "\nSystem");
    lv_obj_set_style_text_font(label_sys, &lv_font_montserrat_16, 0);
    lv_obj_center(label_sys);
    
    // Row 2, Col 1: Voice Memo
    lv_obj_t *btn_voice = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_voice, btn_size, btn_size);
    lv_obj_align(btn_voice, LV_ALIGN_TOP_MID, -btn_size/2 - spacing/2, start_y + btn_size + spacing);
    lv_obj_set_style_bg_color(btn_voice, lv_color_hex(0xD32F2F), 0);
    lv_obj_set_style_radius(btn_voice, 20, 0);
    lv_obj_add_event_cb(btn_voice, btn_voice_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_voice = lv_label_create(btn_voice);
    lv_label_set_text(label_voice, LV_SYMBOL_AUDIO "\nVoice");
    lv_obj_set_style_text_font(label_voice, &lv_font_montserrat_16, 0);
    lv_obj_center(label_voice);
    
    // Row 2, Col 2: Pin Mode
    lv_obj_t *btn_pin = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_pin, btn_size, btn_size);
    lv_obj_align(btn_pin, LV_ALIGN_TOP_MID, btn_size/2 + spacing/2, start_y + btn_size + spacing);
    lv_obj_set_style_bg_color(btn_pin, lv_color_hex(0x7B1FA2), 0);
    lv_obj_set_style_radius(btn_pin, 20, 0);
    lv_obj_add_event_cb(btn_pin, btn_pinmode_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_pin = lv_label_create(btn_pin);
    lv_label_set_text(label_pin, LV_SYMBOL_IMAGE "\nPin Mode");
    lv_obj_set_style_text_font(label_pin, &lv_font_montserrat_16, 0);
    lv_obj_center(label_pin);
    
    // Start battery timer - delete old one first to prevent leaks
    if (battery_timer) {
        lv_timer_del(battery_timer);
    }
    battery_timer = lv_timer_create(bat_timer_cb, 10000, NULL);
    
    ESP_LOGI(TAG, "Ready");
}

// Display settings button callback (brightness & timeout)
static void btn_display_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Display settings button pressed");
    
    // Reset battery ring pointer
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_EYE_OPEN " Brightness & Timeout");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 30);
    
    // Current brightness label
    lv_obj_t *bright_label = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(bright_label, "Brightness: %d%%", LCD_Backlight);
    lv_obj_set_style_text_font(bright_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(bright_label, lv_color_white(), 0);
    lv_obj_align(bright_label, LV_ALIGN_TOP_MID, 0, 60);
    
    // Brightness slider
    lv_obj_t *slider = lv_slider_create(lv_scr_act());
    lv_obj_set_size(slider, 280, 15);
    lv_obj_align(slider, LV_ALIGN_CENTER, 0, -60);
    lv_slider_set_range(slider, 10, 100);  // 10-100%
    lv_slider_set_value(slider, LCD_Backlight, LV_ANIM_OFF);
    lv_obj_set_style_bg_color(slider, lv_color_hex(0x555555), LV_PART_MAIN);
    lv_obj_set_style_bg_color(slider, lv_color_hex(0xFFA000), LV_PART_INDICATOR);
    lv_obj_set_style_bg_color(slider, lv_color_hex(0xFFD54F), LV_PART_KNOB);
    lv_obj_add_event_cb(slider, slider_brightness_cb, LV_EVENT_VALUE_CHANGED, bright_label);
    
    // Timeout label
    lv_obj_t *timeout_title = lv_label_create(lv_scr_act());
    lv_label_set_text(timeout_title, "Screen Timeout:");
    lv_obj_set_style_text_font(timeout_title, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(timeout_title, lv_color_white(), 0);
    lv_obj_align(timeout_title, LV_ALIGN_CENTER, 0, -10);
    
    // Always On button
    lv_obj_t *btn_always = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_always, 130, 50);
    lv_obj_align(btn_always, LV_ALIGN_CENTER, -70, 40);
    lv_obj_set_style_bg_color(btn_always, timeout_enabled ? lv_color_hex(0x555555) : lv_color_hex(0x00AA44), 0);
    lv_obj_add_event_cb(btn_always, btn_always_on_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_always = lv_label_create(btn_always);
    lv_label_set_text(label_always, "Always On");
    lv_obj_set_style_text_font(label_always, &lv_font_montserrat_14, 0);
    lv_obj_center(label_always);
    
    // 30 Second Timeout button
    lv_obj_t *btn_30s = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_30s, 130, 50);
    lv_obj_align(btn_30s, LV_ALIGN_CENTER, 70, 40);
    lv_obj_set_style_bg_color(btn_30s, timeout_enabled ? lv_color_hex(0x00AA44) : lv_color_hex(0x555555), 0);
    lv_obj_add_event_cb(btn_30s, btn_30s_timeout_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_30s = lv_label_create(btn_30s);
    lv_label_set_text(label_30s, "30 Sec");
    lv_obj_set_style_text_font(label_30s, &lv_font_montserrat_14, 0);
    lv_obj_center(label_30s);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// Slider brightness callback
static void slider_brightness_cb(lv_event_t *e) {
    lv_obj_t *slider = lv_event_get_target(e);
    lv_obj_t *label = (lv_obj_t *)lv_event_get_user_data(e);
    
    int32_t value = lv_slider_get_value(slider);
    Set_Backlight((uint8_t)value);
    lv_label_set_text_fmt(label, "Brightness: %d%%", (int)value);
    
    ESP_LOGI(TAG, "Brightness set to %d%%", (int)value);
}

// Always On button callback
static void btn_always_on_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Always On selected");
    timeout_enabled = false;
    
    // Stop timeout timer if running
    if (backlight_timer) {
        lv_timer_del(backlight_timer);
        backlight_timer = NULL;
    }
    
    // Refresh screen to update button colors
    btn_display_cb(e);
}

// 30 Second Timeout button callback
static void btn_30s_timeout_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "30 Second Timeout selected");
    timeout_enabled = true;
    
    // Start timeout timer (30 seconds)
    if (backlight_timer) {
        lv_timer_del(backlight_timer);
    }
    backlight_timer = lv_timer_create(backlight_timeout_cb, 30000, NULL);
    lv_timer_set_repeat_count(backlight_timer, 1);  // Run once
    
    // Refresh screen to update button colors
    btn_display_cb(e);
}

// Directions (Google Maps) button callback
static void btn_directions_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Directions button pressed");
    
    // Decode API key on first use
    if (maps_api_key[0] == '\0') {
        decode_api_key(maps_api_key, sizeof(maps_api_key));
        ESP_LOGI(TAG, "API key decoded");
    }
    
    // Reset battery ring pointer before cleaning
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_GPS " Get Directions");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 55);
    
    // Origin label
    lv_obj_t *origin_label = lv_label_create(lv_scr_act());
    lv_label_set_text(origin_label, "From:");
    lv_obj_set_style_text_font(origin_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(origin_label, lv_color_white(), 0);
    lv_obj_align(origin_label, LV_ALIGN_TOP_LEFT, 51, 85);
    
    // Origin text area
    maps_origin_ta = lv_textarea_create(lv_scr_act());
    lv_obj_set_size(maps_origin_ta, 250, 40);
    lv_obj_align(maps_origin_ta, LV_ALIGN_TOP_LEFT, 51, 103);
    lv_textarea_set_placeholder_text(maps_origin_ta, "Starting location");
    lv_textarea_set_one_line(maps_origin_ta, true);
    lv_textarea_set_max_length(maps_origin_ta, 100);
    lv_obj_add_event_cb(maps_origin_ta, maps_ta_event_cb, LV_EVENT_ALL, NULL);
    
    // Microphone button for origin
    lv_obj_t *btn_mic_origin = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_mic_origin, 55, 40);
    lv_obj_align(btn_mic_origin, LV_ALIGN_TOP_LEFT, 306, 103);
    lv_obj_set_style_bg_color(btn_mic_origin, lv_color_hex(0xF44336), 0);
    lv_obj_set_style_radius(btn_mic_origin, 8, 0);
    lv_obj_add_event_cb(btn_mic_origin, btn_maps_voice_origin_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *mic_label_origin = lv_label_create(btn_mic_origin);
    lv_label_set_text(mic_label_origin, LV_SYMBOL_AUDIO);
    lv_obj_set_style_text_font(mic_label_origin, &lv_font_montserrat_16, 0);
    lv_obj_center(mic_label_origin);
    
    // Destination label
    lv_obj_t *dest_label = lv_label_create(lv_scr_act());
    lv_label_set_text(dest_label, "To:");
    lv_obj_set_style_text_font(dest_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(dest_label, lv_color_white(), 0);
    lv_obj_align(dest_label, LV_ALIGN_TOP_LEFT, 51, 153);
    
    // Destination text area
    maps_dest_ta = lv_textarea_create(lv_scr_act());
    lv_obj_set_size(maps_dest_ta, 250, 40);
    lv_obj_align(maps_dest_ta, LV_ALIGN_TOP_LEFT, 51, 171);
    lv_textarea_set_placeholder_text(maps_dest_ta, "Destination");
    lv_textarea_set_one_line(maps_dest_ta, true);
    lv_textarea_set_max_length(maps_dest_ta, 100);
    lv_obj_add_event_cb(maps_dest_ta, maps_ta_event_cb, LV_EVENT_ALL, NULL);
    
    // Microphone button for destination
    lv_obj_t *btn_mic_dest = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_mic_dest, 55, 40);
    lv_obj_align(btn_mic_dest, LV_ALIGN_TOP_LEFT, 306, 171);
    lv_obj_set_style_bg_color(btn_mic_dest, lv_color_hex(0xF44336), 0);
    lv_obj_set_style_radius(btn_mic_dest, 8, 0);
    lv_obj_add_event_cb(btn_mic_dest, btn_maps_voice_dest_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *mic_label_dest = lv_label_create(btn_mic_dest);
    lv_label_set_text(mic_label_dest, LV_SYMBOL_AUDIO);
    lv_obj_set_style_text_font(mic_label_dest, &lv_font_montserrat_16, 0);
    lv_obj_center(mic_label_dest);
    
    // Status label
    maps_status_label = lv_label_create(lv_scr_act());
    lv_label_set_text(maps_status_label, "");
    lv_obj_set_style_text_font(maps_status_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(maps_status_label, lv_color_hex(0xFFAA00), 0);
    lv_obj_align(maps_status_label, LV_ALIGN_TOP_MID, 0, 220);
    
    // Home button (quick-fill)
    lv_obj_t *btn_home = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_home, 140, 40);
    lv_obj_align(btn_home, LV_ALIGN_TOP_MID, 0, 245);
    lv_obj_set_style_bg_color(btn_home, lv_color_hex(0x4CAF50), 0);
    lv_obj_set_style_radius(btn_home, 8, 0);
    lv_obj_add_event_cb(btn_home, btn_maps_home_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *home_label = lv_label_create(btn_home);
    lv_label_set_text(home_label, LV_SYMBOL_HOME "  Home");
    lv_obj_set_style_text_font(home_label, &lv_font_montserrat_14, 0);
    lv_obj_center(home_label);
    
    // Get Directions button
    lv_obj_t *btn_get = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_get, 240, 45);
    lv_obj_align(btn_get, LV_ALIGN_BOTTOM_MID, 0, -75);
    lv_obj_set_style_bg_color(btn_get, lv_color_hex(0x4285F4), 0);
    lv_obj_set_style_radius(btn_get, 10, 0);
    lv_obj_add_event_cb(btn_get, btn_get_directions_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_get = lv_label_create(btn_get);
    lv_label_set_text(label_get, LV_SYMBOL_GPS "  Get Directions");
    lv_obj_set_style_text_font(label_get, &lv_font_montserrat_14, 0);
    lv_obj_center(label_get);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 240, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT "  Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
    
    // Create keyboard LAST for proper z-order (shows on top)
    maps_keyboard = lv_keyboard_create(lv_scr_act());
    lv_obj_set_size(maps_keyboard, 300, 120);
    lv_obj_align(maps_keyboard, LV_ALIGN_BOTTOM_MID, 0, -130);
    lv_obj_add_flag(maps_keyboard, LV_OBJ_FLAG_HIDDEN);  // Start hidden
    lv_obj_move_to_index(maps_keyboard, 1000);  // High z-index to appear on top
    lv_obj_add_event_cb(maps_keyboard, maps_keyboard_event_cb, LV_EVENT_ALL, NULL);
}

// Maps text area event callback - show/hide keyboard
static void maps_ta_event_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    lv_obj_t *ta = lv_event_get_target(e);
    
    if (code == LV_EVENT_FOCUSED) {
        lv_keyboard_set_textarea(maps_keyboard, ta);
        lv_obj_clear_flag(maps_keyboard, LV_OBJ_FLAG_HIDDEN);
    }
    
    if (code == LV_EVENT_DEFOCUSED) {
        lv_keyboard_set_textarea(maps_keyboard, NULL);
    }
}

// Maps keyboard event callback - hide keyboard when OK pressed
static void maps_keyboard_event_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    
    if (code == LV_EVENT_READY || code == LV_EVENT_CANCEL) {
        lv_obj_add_flag(maps_keyboard, LV_OBJ_FLAG_HIDDEN);
        lv_keyboard_set_textarea(maps_keyboard, NULL);
    }
}

// Maps voice recording timer callback - records audio in 100ms chunks
static void maps_voice_tick_cb(lv_timer_t *t) {
    if (!maps_is_recording || !maps_record_file || !rx_handle) {
        return;
    }
    
    // Auto-stop after 30 ticks (3 seconds)
    maps_record_tick_count++;
    if (maps_record_tick_count >= 30) {
        ESP_LOGI(TAG, "Auto-stopping Maps voice recording at 3 seconds");
        maps_stop_recording();
        return;
    }
    
    // Read audio data from I2S microphone
    // At 16kHz, need 1600 samples per 100ms
    const size_t buffer_size = 1600;
    int32_t *i2s_buffer = malloc(buffer_size * sizeof(int32_t));
    if (!i2s_buffer) {
        ESP_LOGE(TAG, "Failed to allocate Maps audio buffer");
        return;
    }
    
    size_t bytes_read = 0;
    esp_err_t ret = i2s_channel_read(rx_handle, i2s_buffer, buffer_size * sizeof(int32_t), &bytes_read, 100);
    
    if (ret == ESP_OK && bytes_read > 0) {
        // Convert 32-bit samples to 16-bit
        int16_t *pcm_buffer = malloc(buffer_size * sizeof(int16_t));
        if (pcm_buffer) {
            for (size_t i = 0; i < bytes_read / sizeof(int32_t); i++) {
                // Shift right to get 16-bit sample from 32-bit
                pcm_buffer[i] = (int16_t)(i2s_buffer[i] >> 14);
            }
            
            // Write to file
            size_t samples = bytes_read / sizeof(int32_t);
            fwrite(pcm_buffer, sizeof(int16_t), samples, maps_record_file);
            
            free(pcm_buffer);
        }
    }
    
    free(i2s_buffer);
}

// Stop Maps voice recording and prepare for STT API
static void maps_stop_recording(void) {
    if (!maps_is_recording) {
        return;
    }
    
    ESP_LOGI(TAG, "Stopping Maps voice recording");
    maps_is_recording = false;
    
    // Stop timer
    if (maps_record_timer) {
        lv_timer_del(maps_record_timer);
        maps_record_timer = NULL;
    }
    
    // Update WAV header with final data size
    if (maps_record_file) {
        fseek(maps_record_file, 0, SEEK_END);
        long file_size = ftell(maps_record_file);
        uint32_t data_size = file_size - 44;  // WAV header is 44 bytes
        
        write_wav_header(maps_record_file, data_size);
        
        fclose(maps_record_file);
        maps_record_file = NULL;
        
        ESP_LOGI(TAG, "Saved WAV file, size: %lu bytes", (unsigned long)file_size);
    }
    
    // Reset button color to red
    if (maps_record_btn) {
        lv_obj_set_style_bg_color(maps_record_btn, lv_color_hex(0xF44336), 0);
        maps_record_btn = NULL;
    }
    
    // Update status label
    if (maps_status_label) {
        lv_label_set_text(maps_status_label, "Processing voice...");
    }
    
    // Send to Google Speech-to-Text API in a separate task with large stack
    const char *audio_file = (maps_active_ta == maps_origin_ta) ? 
                             "/sdcard/voice_origin.wav" : 
                             "/sdcard/voice_dest.wav";
    
    // Create task with 16KB stack for HTTPS/TLS operations
    static char task_audio_file[64];
    strncpy(task_audio_file, audio_file, sizeof(task_audio_file) - 1);
    xTaskCreate(maps_send_to_stt_api_task, "stt_task", 16384, (void*)task_audio_file, 5, NULL);
}

// Task wrapper for STT API call (needs large stack for HTTPS/TLS)
static void maps_send_to_stt_api_task(void *param) {
    const char *audio_file = (const char *)param;
    maps_send_to_stt_api(audio_file);
    vTaskDelete(NULL);  // Delete task when done
}

// Send recorded audio to Google Speech-to-Text API
static void maps_send_to_stt_api(const char *audio_file) {
    // Decode API key on first use
    if (maps_api_key[0] == '\0') {
        decode_api_key(maps_api_key, sizeof(maps_api_key));
        ESP_LOGI(TAG, "API key initialized");
    }
    
    // Check WiFi connection
    if (!wifi_connected) {
        ESP_LOGW(TAG, "WiFi not connected, cannot transcribe");
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ Connect to WiFi first");
        }
        maps_active_ta = NULL;
        return;
    }
    
    ESP_LOGI(TAG, "Encoding audio to base64 using SD card buffer (free heap: %lu bytes)", esp_get_free_heap_size());
    
    // Open WAV file
    FILE *fp_wav = fopen(audio_file, "rb");
    if (!fp_wav) {
        ESP_LOGE(TAG, "Failed to open audio file: %s", audio_file);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ File read error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    // Get file size and skip WAV header
    fseek(fp_wav, 0, SEEK_END);
    long total_size = ftell(fp_wav);
    fseek(fp_wav, 44, SEEK_SET);  // Skip 44-byte WAV header
    long audio_data_size = total_size - 44;
    
    ESP_LOGI(TAG, "Audio data size: %ld bytes (header skipped)", audio_data_size);
    
    // Create temporary base64 file on SD card
    const char *temp_b64_file = "/sdcard/temp_audio.b64";
    FILE *fp_b64 = fopen(temp_b64_file, "w");
    if (!fp_b64) {
        ESP_LOGE(TAG, "Failed to create temp base64 file");
        fclose(fp_wav);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ SD write error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    // Base64 encode in chunks to minimize RAM usage
    const size_t chunk_size = 3000;  // Process 3KB at a time (must be multiple of 3 for base64)
    uint8_t *read_buffer = malloc(chunk_size);
    char *b64_buffer = malloc((chunk_size / 3) * 4 + 8);  // Base64 output buffer
    
    if (!read_buffer || !b64_buffer) {
        ESP_LOGE(TAG, "Failed to allocate chunk buffers");
        fclose(fp_wav);
        fclose(fp_b64);
        if (read_buffer) free(read_buffer);
        if (b64_buffer) free(b64_buffer);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ Memory error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    const char base64_chars[] = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
    size_t total_read = 0;
    
    // Read and encode in chunks
    while (total_read < audio_data_size) {
        size_t to_read = (audio_data_size - total_read < chunk_size) ? (audio_data_size - total_read) : chunk_size;
        size_t bytes_read = fread(read_buffer, 1, to_read, fp_wav);
        
        if (bytes_read == 0) break;
        
        // Base64 encode this chunk
        size_t b64_idx = 0;
        for (size_t i = 0; i < bytes_read; i += 3) {
            uint32_t val = (read_buffer[i] << 16);
            if (i + 1 < bytes_read) val |= (read_buffer[i + 1] << 8);
            if (i + 2 < bytes_read) val |= read_buffer[i + 2];
            
            b64_buffer[b64_idx++] = base64_chars[(val >> 18) & 0x3F];
            b64_buffer[b64_idx++] = base64_chars[(val >> 12) & 0x3F];
            b64_buffer[b64_idx++] = (i + 1 < bytes_read) ? base64_chars[(val >> 6) & 0x3F] : '=';
            b64_buffer[b64_idx++] = (i + 2 < bytes_read) ? base64_chars[val & 0x3F] : '=';
        }
        
        // Write base64 chunk to file
        fwrite(b64_buffer, 1, b64_idx, fp_b64);
        total_read += bytes_read;
    }
    
    free(read_buffer);
    free(b64_buffer);
    fclose(fp_wav);
    fclose(fp_b64);
    
    ESP_LOGI(TAG, "Base64 encoding complete, reading back for JSON (free heap: %lu)", esp_get_free_heap_size());
    
    // Now read the base64 file to create JSON
    fp_b64 = fopen(temp_b64_file, "r");
    if (!fp_b64) {
        ESP_LOGE(TAG, "Failed to read temp base64 file");
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ SD read error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    fseek(fp_b64, 0, SEEK_END);
    long b64_size = ftell(fp_b64);
    fseek(fp_b64, 0, SEEK_SET);
    
    ESP_LOGI(TAG, "Base64 size: %ld bytes, Free heap: %lu bytes", b64_size, esp_get_free_heap_size());
    
    // Check if we have enough memory (need ~2x for JSON + base64 string)
    if (esp_get_free_heap_size() < (b64_size * 2 + 10000)) {
        ESP_LOGE(TAG, "Insufficient heap for JSON: need ~%ld, have %lu", b64_size * 2 + 10000, esp_get_free_heap_size());
        fclose(fp_b64);
        remove(temp_b64_file);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ Insufficient memory");
        }
        maps_active_ta = NULL;
        return;
    }
    
    // Use heap_caps_malloc to allocate from any available heap (internal + SPIRAM if present)
    char *base64_audio = heap_caps_malloc(b64_size + 1, MALLOC_CAP_8BIT);
    if (!base64_audio) {
        ESP_LOGE(TAG, "Failed to allocate %ld bytes for base64 string (free heap: %lu, largest block: %zu)", 
                 b64_size + 1, esp_get_free_heap_size(), heap_caps_get_largest_free_block(MALLOC_CAP_8BIT));
        fclose(fp_b64);
        remove(temp_b64_file);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ Memory error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    size_t read_bytes = fread(base64_audio, 1, b64_size, fp_b64);
    base64_audio[b64_size] = '\0';
    fclose(fp_b64);
    
    // Delete temp file
    remove(temp_b64_file);
    
    ESP_LOGI(TAG, "Read %zu bytes of base64, building JSON (free heap: %lu)", read_bytes, esp_get_free_heap_size());
    ESP_LOGI(TAG, "Base64 sample (first 50 chars): %.50s", base64_audio);
    ESP_LOGI(TAG, "Base64 sample (last 50 chars): %s", base64_audio + (b64_size > 50 ? b64_size - 50 : 0));
    
    // Build JSON request
    cJSON *root = cJSON_CreateObject();
    cJSON *config = cJSON_CreateObject();
    cJSON *audio = cJSON_CreateObject();
    
    cJSON_AddStringToObject(config, "encoding", "LINEAR16");
    cJSON_AddNumberToObject(config, "sampleRateHertz", 16000);
    cJSON_AddStringToObject(config, "languageCode", "en-US");
    cJSON_AddItemToObject(root, "config", config);
    
    cJSON_AddStringToObject(audio, "content", base64_audio);
    cJSON_AddItemToObject(root, "audio", audio);
    
    ESP_LOGI(TAG, "JSON objects created, printing (free heap: %lu)", esp_get_free_heap_size());
    
    char *json_request = cJSON_PrintUnformatted(root);
    cJSON_Delete(root);
    free(base64_audio);
    
    if (!json_request) {
        ESP_LOGE(TAG, "Failed to create JSON request (out of memory)");
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ JSON memory error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    ESP_LOGI(TAG, "JSON created, size: %d bytes (free heap: %lu)", strlen(json_request), esp_get_free_heap_size());
    
    // Build API URL
    char api_url[256];
    snprintf(api_url, sizeof(api_url), "https://speech.googleapis.com/v1/speech:recognize?key=%s", maps_api_key);
    ESP_LOGI(TAG, "API URL constructed, length: %d", strlen(api_url));
    
    // HTTP POST request
    esp_http_client_config_t config_http = {
        .url = api_url,
        .method = HTTP_METHOD_POST,
        .timeout_ms = 10000,
        .crt_bundle_attach = esp_crt_bundle_attach,
    };
    
    esp_http_client_handle_t client = esp_http_client_init(&config_http);
    if (!client) {
        ESP_LOGE(TAG, "Failed to initialize HTTP client");
        free(json_request);
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ HTTP init error");
        }
        maps_active_ta = NULL;
        return;
    }
    
    esp_http_client_set_header(client, "Content-Type", "application/json");
    esp_http_client_set_post_field(client, json_request, strlen(json_request));
    
    esp_err_t err = esp_http_client_perform(client);
    
    if (err == ESP_OK) {
        int status_code = esp_http_client_get_status_code(client);
        int content_length = esp_http_client_get_content_length(client);
        
        ESP_LOGI(TAG, "STT API Status: %d, Length: %d", status_code, content_length);
        
        if (status_code == 200 && content_length > 0) {
            char *response = malloc(content_length + 1);
            if (response) {
                int read_len = esp_http_client_read_response(client, response, content_length);
                response[read_len] = '\0';
                
                ESP_LOGI(TAG, "Response: %s", response);
                
                // Parse JSON response
                cJSON *json_response = cJSON_Parse(response);
                if (json_response) {
                    cJSON *results = cJSON_GetObjectItem(json_response, "results");
                    if (results && cJSON_IsArray(results) && cJSON_GetArraySize(results) > 0) {
                        cJSON *first_result = cJSON_GetArrayItem(results, 0);
                        cJSON *alternatives = cJSON_GetObjectItem(first_result, "alternatives");
                        if (alternatives && cJSON_IsArray(alternatives) && cJSON_GetArraySize(alternatives) > 0) {
                            cJSON *first_alt = cJSON_GetArrayItem(alternatives, 0);
                            cJSON *transcript = cJSON_GetObjectItem(first_alt, "transcript");
                            if (transcript && cJSON_IsString(transcript)) {
                                const char *text = cJSON_GetStringValue(transcript);
                                ESP_LOGI(TAG, "Transcribed: %s", text);
                                
                                // Fill text area
                                if (maps_active_ta) {
                                    lv_textarea_set_text(maps_active_ta, text);
                                }
                                if (maps_status_label) {
                                    lv_label_set_text(maps_status_label, "✓ Voice recognized");
                                }
                            }
                        }
                    }
                    cJSON_Delete(json_response);
                } else {
                    ESP_LOGE(TAG, "Failed to parse JSON response");
                    if (maps_status_label) {
                        lv_label_set_text(maps_status_label, "❌ Parse error");
                    }
                }
                free(response);
            }
        } else {
            ESP_LOGE(TAG, "HTTP error: %d", status_code);
            
            // Read error response
            if (content_length > 0) {
                char *error_response = malloc(content_length + 1);
                if (error_response) {
                    int read_len = esp_http_client_read_response(client, error_response, content_length);
                    error_response[read_len] = '\0';
                    ESP_LOGE(TAG, "Error response: %s", error_response);
                    free(error_response);
                }
            }
            
            if (maps_status_label) {
                lv_label_set_text_fmt(maps_status_label, "❌ API error: %d", status_code);
            }
        }
    } else {
        ESP_LOGE(TAG, "HTTP request failed: %s", esp_err_to_name(err));
        if (maps_status_label) {
            lv_label_set_text(maps_status_label, "❌ Network error");
        }
    }
    
    esp_http_client_cleanup(client);
    free(json_request);
    maps_active_ta = NULL;
}

// Voice input for origin field
static void btn_maps_voice_origin_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    lv_obj_t *btn = lv_event_get_target(e);
    
    // Don't start a new recording if one is already in progress
    if (maps_is_recording) {
        ESP_LOGW(TAG, "Recording already in progress");
        return;
    }
    
    // Clear the text area before recording
    lv_textarea_set_text(maps_origin_ta, "");
    
    // Start recording
    ESP_LOGI(TAG, "Starting voice input for origin");
    maps_is_recording = true;
    maps_active_ta = maps_origin_ta;
    maps_record_btn = btn;
    maps_record_tick_count = 0;
    
    // Update UI
    lv_obj_set_style_bg_color(btn, lv_color_hex(0x00FF00), 0);  // Green when recording
    lv_label_set_text(maps_status_label, "🎤 Recording... (3 sec max)");
    
    // Open WAV file
    maps_record_file = fopen("/sdcard/voice_origin.wav", "wb");
    if (!maps_record_file) {
        ESP_LOGE(TAG, "Failed to open /sdcard/voice_origin.wav");
        lv_label_set_text(maps_status_label, "❌ SD card error");
        maps_is_recording = false;
        lv_obj_set_style_bg_color(btn, lv_color_hex(0xF44336), 0);
        return;
    }
    
    // Write WAV header (will update with correct size later)
    write_wav_header(maps_record_file, 0);
    
    // Initialize I2S microphone
    esp_err_t ret = init_i2s_mic();
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to initialize I2S microphone");
        fclose(maps_record_file);
        maps_record_file = NULL;
        maps_is_recording = false;
        lv_obj_set_style_bg_color(btn, lv_color_hex(0xF44336), 0);
        lv_label_set_text(maps_status_label, "❌ Microphone error");
        return;
    }
    
    // Start recording timer (100ms interval)
    maps_record_timer = lv_timer_create(maps_voice_tick_cb, 100, NULL);
    lv_timer_set_repeat_count(maps_record_timer, -1);  // Repeat indefinitely (auto-stop in callback)
    
    ESP_LOGI(TAG, "Maps voice recording started for origin");
}

// Voice input for destination field
static void btn_maps_voice_dest_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    lv_obj_t *btn = lv_event_get_target(e);
    
    // Don't start a new recording if one is already in progress
    if (maps_is_recording) {
        ESP_LOGW(TAG, "Recording already in progress");
        return;
    }
    
    // Clear the text area before recording
    lv_textarea_set_text(maps_dest_ta, "");
    
    // Start recording
    ESP_LOGI(TAG, "Starting voice input for destination");
    maps_is_recording = true;
    maps_active_ta = maps_dest_ta;
    maps_record_btn = btn;
    maps_record_tick_count = 0;
    
    // Update UI
    lv_obj_set_style_bg_color(btn, lv_color_hex(0x00FF00), 0);  // Green when recording
    lv_label_set_text(maps_status_label, "🎤 Recording... (3 sec max)");
    
    // Open WAV file
    maps_record_file = fopen("/sdcard/voice_dest.wav", "wb");
    if (!maps_record_file) {
        ESP_LOGE(TAG, "Failed to open /sdcard/voice_dest.wav");
        lv_label_set_text(maps_status_label, "❌ SD card error");
        maps_is_recording = false;
        lv_obj_set_style_bg_color(btn, lv_color_hex(0xF44336), 0);
        return;
    }
    
    // Write WAV header (will update with correct size later)
    write_wav_header(maps_record_file, 0);
    
    // Initialize I2S microphone
    esp_err_t ret = init_i2s_mic();
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to initialize I2S microphone");
        fclose(maps_record_file);
        maps_record_file = NULL;
        maps_is_recording = false;
        lv_obj_set_style_bg_color(btn, lv_color_hex(0xF44336), 0);
        lv_label_set_text(maps_status_label, "❌ Microphone error");
        return;
    }
    
    // Start recording timer (100ms interval)
    maps_record_timer = lv_timer_create(maps_voice_tick_cb, 100, NULL);
    lv_timer_set_repeat_count(maps_record_timer, -1);  // Repeat indefinitely (auto-stop in callback)
    
    ESP_LOGI(TAG, "Maps voice recording started for destination");
}

// Home button callback - quick-fill origin with home address
static void btn_maps_home_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Home button pressed - filling origin");
    lv_textarea_set_text(maps_origin_ta, MAPS_HOME_ADDRESS);
    lv_label_set_text(maps_status_label, "✓ Home address filled");
}

// Get Directions button callback
static void btn_get_directions_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    const char *origin = lv_textarea_get_text(maps_origin_ta);
    const char *dest = lv_textarea_get_text(maps_dest_ta);
    
    if (strlen(origin) == 0 || strlen(dest) == 0) {
        lv_label_set_text(maps_status_label, "Please enter both locations");
        return;
    }
    
    ESP_LOGI(TAG, "Getting directions: %s -> %s", origin, dest);
    lv_label_set_text(maps_status_label, "Connecting to Google Maps...");
    
    // TODO: Implement actual HTTP request to Google Maps API
    // URL format: https://maps.googleapis.com/maps/api/directions/json?origin=ORIGIN&destination=DEST&key=API_KEY
    
    // For now, just show placeholder response
    vTaskDelay(pdMS_TO_TICKS(500));
    lv_label_set_text(maps_status_label, "API call ready - WiFi needed");
}

// Backlight timeout callback
static void backlight_timeout_cb(lv_timer_t *t) {
    if (timeout_enabled) {
        ESP_LOGI(TAG, "Screen timeout - dimming backlight");
        Set_Backlight(10);  // Dim to 10%
    }
}

// System button callback
static void btn_sys_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "System button pressed");
    
    // Reset battery ring pointer before cleaning
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_SETTINGS " System Info");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 30);
    
    // Battery voltage - with validation
    float volts = BAT_Get_Volts();
    
    ESP_LOGI(TAG, "BAT_Get_Volts raw: %f", volts);
    
    // Validate voltage
    if (volts < 0.0f || volts != volts) {  // Check negative or NaN
        volts = 3700.0f;  // Default in mV
        ESP_LOGW(TAG, "Invalid voltage, using default");
    }
    
    // Convert to display format: mV -> V.VV
    int mv = (int)volts;
    if (mv < 100) {
        mv = (int)(volts * 1000.0f);  // Was in volts, convert to mV
    }
    
    // Display as X.XX V
    int v_whole = mv / 1000;
    int v_frac = (mv % 1000) / 10;  // Get 2 decimal places
    
    ESP_LOGI(TAG, "Display voltage: %d.%02d V (%d mV)", v_whole, v_frac, mv);
    
    lv_obj_t *bat_label = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(bat_label, "Battery: %d.%02d V", v_whole, v_frac);
    lv_obj_set_style_text_font(bat_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(bat_label, lv_color_hex(0x00FF00), 0);
    lv_obj_align(bat_label, LV_ALIGN_CENTER, 0, -120);
    
    // SD Card - validate size
    uint32_t sd_mb = SDCard_Size;
    
    ESP_LOGI(TAG, "SDCard_Size raw: %lu", (unsigned long)sd_mb);
    
    lv_obj_t *sd_label = lv_label_create(lv_scr_act());
    if (sd_mb > 0) {
        lv_label_set_text_fmt(sd_label, "SD: %lu MB", (unsigned long)sd_mb);
    } else {
        lv_label_set_text(sd_label, "SD: Not detected");
    }
    lv_obj_set_style_text_font(sd_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(sd_label, lv_color_hex(0x00FFFF), 0);
    lv_obj_align(sd_label, LV_ALIGN_CENTER, 0, -70);
    
    // Display button (brightness & timeout)
    lv_obj_t *btn_display = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_display, 200, 50);
    lv_obj_align(btn_display, LV_ALIGN_BOTTOM_MID, 0, -200);
    lv_obj_set_style_bg_color(btn_display, lv_color_hex(0xFFA000), 0);
    lv_obj_set_style_radius(btn_display, 10, 0);
    lv_obj_add_event_cb(btn_display, btn_display_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_display = lv_label_create(btn_display);
    lv_label_set_text(label_display, LV_SYMBOL_EYE_OPEN " Display");
    lv_obj_set_style_text_font(label_display, &lv_font_montserrat_14, 0);
    lv_obj_center(label_display);
    
    // WiFi button
    lv_obj_t *btn_wifi = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_wifi, 200, 50);
    lv_obj_align(btn_wifi, LV_ALIGN_BOTTOM_MID, 0, -140);
    lv_obj_set_style_bg_color(btn_wifi, lv_color_hex(0x4CAF50), 0);
    lv_obj_set_style_radius(btn_wifi, 10, 0);
    lv_obj_add_event_cb(btn_wifi, btn_wifi_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_wifi = lv_label_create(btn_wifi);
    lv_label_set_text(label_wifi, LV_SYMBOL_WIFI " WiFi");
    lv_obj_set_style_text_font(label_wifi, &lv_font_montserrat_14, 0);
    lv_obj_center(label_wifi);
    
    // Bluetooth button
    lv_obj_t *btn_bt = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_bt, 200, 50);
    lv_obj_align(btn_bt, LV_ALIGN_BOTTOM_MID, 0, -80);
    lv_obj_set_style_bg_color(btn_bt, lv_color_hex(0x2196F3), 0);
    lv_obj_set_style_radius(btn_bt, 10, 0);
    lv_obj_add_event_cb(btn_bt, btn_bluetooth_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_bt = lv_label_create(btn_bt);
    lv_label_set_text(label_bt, LV_SYMBOL_BLUETOOTH " Bluetooth");
    lv_obj_set_style_text_font(label_bt, &lv_font_montserrat_14, 0);
    lv_obj_center(label_bt);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// Bluetooth button callback
static lv_obj_t *ble_status_label = NULL;
static lv_obj_t *ble_device_label = NULL;

// Voice memo list
static lv_obj_t *voice_memo_list = NULL;

// BLE pairing task - runs in background
static void ble_pairing_task(void *pvParameters) {
    ESP_LOGI(TAG, "Starting BLE pairing task...");
    
    // Clear previous scan results
    BLE_Clear_Devices();
    
    // Update UI: Scanning
    if (ble_status_label && ble_device_label) {
        lv_label_set_text(ble_status_label, "Scanning...");
        lv_obj_set_style_text_color(ble_status_label, lv_color_hex(0xFFFF00), 0);
        lv_label_set_text(ble_device_label, "Device: Searching...");
    }
    
    // Scan for devices (5 seconds)
    BLE_Scan();
    
    // Update UI: Connecting
    if (ble_status_label) {
        lv_label_set_text(ble_status_label, "Connecting...");
    }
    
    // Try to connect to first discovered device
    bool connected = BLE_Connect_First_Device();
    
    // Update UI with result
    if (connected && ble_status_label && ble_device_label) {
        lv_label_set_text(ble_status_label, "Connected");
        lv_obj_set_style_text_color(ble_status_label, lv_color_hex(0x00FF00), 0);
        
        char device_text[120];
        snprintf(device_text, sizeof(device_text), "Device: %s", BLE_Get_Connected_Device_Name());
        lv_label_set_text(ble_device_label, device_text);
        lv_obj_set_style_text_color(ble_device_label, lv_color_hex(0x00FF00), 0);
    } else if (ble_status_label && ble_device_label) {
        lv_label_set_text(ble_status_label, "Failed - No devices");
        lv_obj_set_style_text_color(ble_status_label, lv_color_hex(0xFF0000), 0);
        lv_label_set_text(ble_device_label, "Device: None");
        lv_obj_set_style_text_color(ble_device_label, lv_color_hex(0x888888), 0);
    }
    
    ESP_LOGI(TAG, "BLE pairing task finished");
    vTaskDelete(NULL);
}

// Pair button callback - launches background pairing task
static void btn_pair_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Pair button pressed");
    
    // Launch pairing task in background
    xTaskCreate(
        ble_pairing_task,
        "BLE Pairing",
        4096,
        NULL,
        2,
        NULL
    );
}

static void btn_bluetooth_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Bluetooth button pressed");
    
    // Reset battery ring pointer before cleaning
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_BLUETOOTH " Bluetooth");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 30);
    
    // Status label
    ble_status_label = lv_label_create(lv_scr_act());
    lv_label_set_text(ble_status_label, "Ready to pair");
    lv_obj_set_style_text_font(ble_status_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(ble_status_label, lv_color_hex(0x00FF00), 0);
    lv_obj_align(ble_status_label, LV_ALIGN_CENTER, 0, -60);
    
    // Connected device label
    ble_device_label = lv_label_create(lv_scr_act());
    lv_label_set_text(ble_device_label, "Device: None");
    lv_obj_set_style_text_font(ble_device_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(ble_device_label, lv_color_hex(0x888888), 0);
    lv_obj_align(ble_device_label, LV_ALIGN_CENTER, 0, -20);
    
    // Pair button
    lv_obj_t *btn_pair = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_pair, 200, 60);
    lv_obj_align(btn_pair, LV_ALIGN_CENTER, 0, 40);
    lv_obj_set_style_bg_color(btn_pair, lv_color_hex(0x2196F3), 0);
    lv_obj_add_event_cb(btn_pair, btn_pair_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *pair_label = lv_label_create(btn_pair);
    lv_label_set_text(pair_label, LV_SYMBOL_BLUETOOTH " Pair");
    lv_obj_set_style_text_font(pair_label, &lv_font_montserrat_16, 0);
    lv_obj_center(pair_label);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// WiFi disconnect button callback
static void btn_wifi_disconnect_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Disconnecting from WiFi");
    
    // TODO: Implement actual WiFi disconnect
    // esp_wifi_disconnect() would go here
    
    // Update connection state
    wifi_connected = false;
    wifi_connected_ssid[0] = '\0';
    wifi_connected_password[0] = '\0';
    
    // Return to WiFi configuration screen
    btn_wifi_cb(e);
}

// Show WiFi status screen with speed meter
static void wifi_show_status(const char *ssid) {
    // Reset battery ring pointer before cleaning
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_WIFI " WiFi Connected");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // WiFi name (moved below header)
    lv_obj_t *ssid_label = lv_label_create(lv_scr_act());
    lv_label_set_text(ssid_label, ssid);
    lv_obj_set_style_text_font(ssid_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(ssid_label, lv_color_hex(0xAAAAAA), 0);
    lv_obj_align(ssid_label, LV_ALIGN_TOP_MID, 0, 45);
    
    // Signal strength meter (arc gauge)
    // Converts dBm (-100 to 0) to arc value (0 to 100)
    int signal_dbm = -45;  // Placeholder: -45 dBm is good signal
    int arc_value = signal_dbm + 100;  // -45 becomes 55, -90 becomes 10
    lv_obj_t *arc = lv_arc_create(lv_scr_act());
    lv_obj_set_size(arc, 180, 180);
    lv_obj_align(arc, LV_ALIGN_CENTER, 0, -20);
    lv_arc_set_rotation(arc, 135);
    lv_arc_set_bg_angles(arc, 0, 270);
    lv_arc_set_value(arc, arc_value);
    lv_obj_set_style_arc_width(arc, 12, LV_PART_MAIN);
    lv_obj_set_style_arc_width(arc, 12, LV_PART_INDICATOR);
    lv_obj_set_style_arc_color(arc, lv_color_hex(0x444444), LV_PART_MAIN);
    // Color based on signal strength: Green (>-50), Yellow (-70 to -50), Red (<-70)
    uint32_t signal_color = (signal_dbm > -50) ? 0x00FF00 : (signal_dbm > -70) ? 0xFFAA00 : 0xFF0000;
    lv_obj_set_style_arc_color(arc, lv_color_hex(signal_color), LV_PART_INDICATOR);
    lv_obj_clear_flag(arc, LV_OBJ_FLAG_CLICKABLE);
    
    // Signal strength label (center of arc) - shows both dBm and Mbps
    lv_obj_t *signal_label = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(signal_label, "%d dBm\n75 Mbps", signal_dbm);  // Placeholder values
    lv_obj_set_style_text_font(signal_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(signal_label, lv_color_white(), 0);
    lv_obj_set_style_text_align(signal_label, LV_TEXT_ALIGN_CENTER, 0);
    lv_obj_align(signal_label, LV_ALIGN_CENTER, 0, -25);
    
    // Connection type
    lv_obj_t *type_label = lv_label_create(lv_scr_act());
    lv_label_set_text(type_label, "802.11n (WiFi 4)");  // Placeholder
    lv_obj_set_style_text_font(type_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(type_label, lv_color_hex(0x888888), 0);
    lv_obj_align(type_label, LV_ALIGN_CENTER, 0, 105);
    
    // Save button
    lv_obj_t *btn_save = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_save, 150, 50);
    lv_obj_align(btn_save, LV_ALIGN_BOTTOM_LEFT, 30, -80);
    lv_obj_set_style_bg_color(btn_save, lv_color_hex(0x4CAF50), 0);
    lv_obj_add_event_cb(btn_save, btn_wifi_save_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *save_label = lv_label_create(btn_save);
    lv_label_set_text(save_label, LV_SYMBOL_SAVE "  Save");
    lv_obj_set_style_text_font(save_label, &lv_font_montserrat_14, 0);
    lv_obj_center(save_label);
    
    // Disconnect button
    lv_obj_t *btn_disconnect = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_disconnect, 150, 50);
    lv_obj_align(btn_disconnect, LV_ALIGN_BOTTOM_RIGHT, -30, -80);
    lv_obj_set_style_bg_color(btn_disconnect, lv_color_hex(0xF44336), 0);
    lv_obj_add_event_cb(btn_disconnect, btn_wifi_disconnect_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *disconnect_label = lv_label_create(btn_disconnect);
    lv_label_set_text(disconnect_label, LV_SYMBOL_CLOSE "  Disconnect");
    lv_obj_set_style_text_font(disconnect_label, &lv_font_montserrat_14, 0);
    lv_obj_center(disconnect_label);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// WiFi save button callback - saves current connection to NVS
static void btn_wifi_save_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Saving WiFi credentials for: %s", wifi_connected_ssid);
    
    // Open NVS
    nvs_handle_t nvs_handle;
    esp_err_t err = nvs_open("wifi_storage", NVS_READWRITE, &nvs_handle);
    if (err != ESP_OK) {
        ESP_LOGE(TAG, "Failed to open NVS: %s", esp_err_to_name(err));
        return;
    }
    
    // Get current count of saved networks
    uint8_t count = 0;
    nvs_get_u8(nvs_handle, "wifi_count", &count);
    
    // Check if network already exists
    bool found = false;
    uint8_t slot = 0;
    for (uint8_t i = 0; i < count && i < 10; i++) {
        char key[16];
        snprintf(key, sizeof(key), "ssid_%d", i);
        
        char saved_ssid[33];
        size_t len = sizeof(saved_ssid);
        if (nvs_get_str(nvs_handle, key, saved_ssid, &len) == ESP_OK) {
            if (strcmp(saved_ssid, wifi_connected_ssid) == 0) {
                found = true;
                slot = i;
                break;
            }
        }
    }
    
    // If not found and we have space, add to next slot
    if (!found) {
        if (count < 10) {
            slot = count;
            count++;
            nvs_set_u8(nvs_handle, "wifi_count", count);
        } else {
            ESP_LOGW(TAG, "Maximum saved networks reached");
            nvs_close(nvs_handle);
            return;
        }
    }
    
    // Save SSID and password
    char ssid_key[16], pass_key[16];
    snprintf(ssid_key, sizeof(ssid_key), "ssid_%d", slot);
    snprintf(pass_key, sizeof(pass_key), "pass_%d", slot);
    
    nvs_set_str(nvs_handle, ssid_key, wifi_connected_ssid);
    nvs_set_str(nvs_handle, pass_key, wifi_connected_password);
    nvs_commit(nvs_handle);
    nvs_close(nvs_handle);
    
    ESP_LOGI(TAG, "Saved network at slot %d (total: %d)", slot, count);
    
    // Show confirmation
    lv_obj_t *status = lv_label_create(lv_scr_act());
    lv_label_set_text(status, LV_SYMBOL_OK " Network saved!");
    lv_obj_set_style_text_font(status, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(status, lv_color_hex(0x4CAF50), 0);
    lv_obj_align(status, LV_ALIGN_CENTER, 0, 150);
    
    // Remove confirmation after 2 seconds
    lv_obj_del_delayed(status, 2000);
}

// WiFi connection list callback - shows saved networks
static void btn_wifi_list_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Showing saved WiFi networks");
    
    lv_obj_clean(lv_scr_act());
    
    // Keep battery ring
    battery_ring = NULL;
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, "Saved Networks");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // Open NVS
    nvs_handle_t nvs_handle;
    esp_err_t err = nvs_open("wifi_storage", NVS_READONLY, &nvs_handle);
    if (err != ESP_OK) {
        ESP_LOGW(TAG, "No saved networks found");
        
        lv_obj_t *msg = lv_label_create(lv_scr_act());
        lv_label_set_text(msg, "No saved networks yet.\n\nConnect to a network and\ntap " LV_SYMBOL_SAVE " Save to store it.");
        lv_obj_set_style_text_font(msg, &lv_font_montserrat_14, 0);
        lv_obj_set_style_text_color(msg, lv_color_hex(0x888888), 0);
        lv_obj_set_style_text_align(msg, LV_TEXT_ALIGN_CENTER, 0);
        lv_obj_align(msg, LV_ALIGN_CENTER, 0, 0);
    } else {
        // Get count of saved networks
        uint8_t count = 0;
        nvs_get_u8(nvs_handle, "wifi_count", &count);
        
        if (count == 0) {
            lv_obj_t *msg = lv_label_create(lv_scr_act());
            lv_label_set_text(msg, "No saved networks yet.\n\nConnect to a network and\ntap " LV_SYMBOL_SAVE " Save to store it.");
            lv_obj_set_style_text_font(msg, &lv_font_montserrat_14, 0);
            lv_obj_set_style_text_color(msg, lv_color_hex(0x888888), 0);
            lv_obj_set_style_text_align(msg, LV_TEXT_ALIGN_CENTER, 0);
            lv_obj_align(msg, LV_ALIGN_CENTER, 0, 0);
        } else {
            // Create scrollable list
            lv_obj_t *list = lv_list_create(lv_scr_act());
            lv_obj_set_size(list, 350, 280);
            lv_obj_align(list, LV_ALIGN_TOP_MID, 0, 55);
            lv_obj_set_style_bg_color(list, lv_color_hex(0x1a1a1a), 0);
            
            // Read and display each saved network
            for (uint8_t i = 0; i < count && i < 10; i++) {
                char key[16];
                snprintf(key, sizeof(key), "ssid_%d", i);
                
                char ssid[33];
                size_t len = sizeof(ssid);
                if (nvs_get_str(nvs_handle, key, ssid, &len) == ESP_OK) {
                    lv_obj_t *btn = lv_list_add_btn(list, LV_SYMBOL_WIFI, ssid);
                    lv_obj_set_user_data(btn, (void*)(uintptr_t)i);  // Store slot number
                    lv_obj_add_event_cb(btn, wifi_network_select_cb, LV_EVENT_CLICKED, NULL);
                }
            }
        }
        
        nvs_close(nvs_handle);
    }
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -20);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_wifi_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// Callback when user selects a saved network
static void wifi_network_select_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    lv_obj_t *btn = lv_event_get_target(e);
    uint8_t slot = (uint8_t)(uintptr_t)lv_obj_get_user_data(btn);
    
    ESP_LOGI(TAG, "Selected network slot %d", slot);
    
    // Open NVS
    nvs_handle_t nvs_handle;
    esp_err_t err = nvs_open("wifi_storage", NVS_READONLY, &nvs_handle);
    if (err != ESP_OK) {
        ESP_LOGE(TAG, "Failed to open NVS: %s", esp_err_to_name(err));
        return;
    }
    
    // Read SSID and password
    char ssid_key[16], pass_key[16];
    snprintf(ssid_key, sizeof(ssid_key), "ssid_%d", slot);
    snprintf(pass_key, sizeof(pass_key), "pass_%d", slot);
    
    char ssid[33], password[65];
    size_t ssid_len = sizeof(ssid);
    size_t pass_len = sizeof(password);
    
    if (nvs_get_str(nvs_handle, ssid_key, ssid, &ssid_len) == ESP_OK &&
        nvs_get_str(nvs_handle, pass_key, password, &pass_len) == ESP_OK) {
        
        // Return to WiFi input screen
        btn_wifi_cb(e);
        
        // Populate text areas
        lv_textarea_set_text(wifi_ssid_ta, ssid);
        lv_textarea_set_text(wifi_pass_ta, password);
        
        ESP_LOGI(TAG, "Loaded credentials for: %s", ssid);
    }
    
    nvs_close(nvs_handle);
}

// WiFi connect button callback
static void btn_wifi_connect_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    const char *ssid = lv_textarea_get_text(wifi_ssid_ta);
    const char *pass = lv_textarea_get_text(wifi_pass_ta);
    
    // Validate SSID is not empty
    if (ssid == NULL || strlen(ssid) == 0 || strspn(ssid, " \t\n\r") == strlen(ssid)) {
        ESP_LOGW(TAG, "Cannot connect: SSID is empty");
        lv_label_set_text(wifi_status_label, LV_SYMBOL_WARNING " Enter WiFi name");
        lv_obj_set_style_text_color(wifi_status_label, lv_color_hex(0xFF5722), 0);
        return;
    }
    
    ESP_LOGI(TAG, "Connecting to WiFi: SSID='%s'", ssid);
    lv_label_set_text(wifi_status_label, "Connecting...");
    lv_obj_set_style_text_color(wifi_status_label, lv_color_hex(0xFFFF00), 0);
    lv_task_handler();
    
    // Save credentials for attempt
    strncpy(wifi_connected_ssid, ssid, sizeof(wifi_connected_ssid) - 1);
    wifi_connected_ssid[sizeof(wifi_connected_ssid) - 1] = '\0';
    strncpy(wifi_connected_password, pass, sizeof(wifi_connected_password) - 1);
    wifi_connected_password[sizeof(wifi_connected_password) - 1] = '\0';
    
    // Configure WiFi
    wifi_config_t wifi_config = {0};
    strncpy((char*)wifi_config.sta.ssid, ssid, sizeof(wifi_config.sta.ssid) - 1);
    strncpy((char*)wifi_config.sta.password, pass, sizeof(wifi_config.sta.password) - 1);
    wifi_config.sta.threshold.authmode = WIFI_AUTH_WPA2_PSK;
    wifi_config.sta.pmf_cfg.capable = true;
    wifi_config.sta.pmf_cfg.required = false;
    
    ESP_LOGI(TAG, "Setting WiFi config for SSID='%s', password length=%d", ssid, strlen(pass));
    
    esp_err_t ret = esp_wifi_set_config(WIFI_IF_STA, &wifi_config);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to set WiFi config: %s", esp_err_to_name(ret));
        lv_label_set_text(wifi_status_label, "❌ Config failed");
        wifi_connected = false;
        return;
    }
    
    ret = esp_wifi_connect();
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to start WiFi connect: %s", esp_err_to_name(ret));
        lv_label_set_text(wifi_status_label, "❌ Connect failed");
        wifi_connected = false;
        return;
    }
    
    // Wait for connection (up to 10 seconds)
    int attempts = 0;
    while (attempts < 100 && !wifi_connected) {
        vTaskDelay(pdMS_TO_TICKS(100));
        lv_task_handler();
        attempts++;
    }
    
    if (wifi_connected) {
        ESP_LOGI(TAG, "WiFi connected successfully");
        wifi_show_status(wifi_connected_ssid);
    } else {
        ESP_LOGW(TAG, "WiFi connection timeout");
        lv_label_set_text(wifi_status_label, "❌ Connection timeout");
    }
}

// Keyboard event callback - hide keyboard when OK pressed
static void wifi_keyboard_event_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    
    if (code == LV_EVENT_READY || code == LV_EVENT_CANCEL) {
        // Hide keyboard when OK or Cancel is pressed
        lv_obj_add_flag(wifi_keyboard, LV_OBJ_FLAG_HIDDEN);
        lv_keyboard_set_textarea(wifi_keyboard, NULL);
    }
}

// Show password checkbox callback
static void wifi_show_pass_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_VALUE_CHANGED) return;
    
    lv_obj_t *checkbox = lv_event_get_target(e);
    bool checked = lv_obj_has_state(checkbox, LV_STATE_CHECKED);
    
    // Toggle password mode
    lv_textarea_set_password_mode(wifi_pass_ta, !checked);
}

// WiFi text area click callback - shows keyboard
static void wifi_ta_event_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    lv_obj_t *ta = lv_event_get_target(e);
    
    if (code == LV_EVENT_FOCUSED) {
        // Set keyboard to this text area
        lv_keyboard_set_textarea(wifi_keyboard, ta);
        lv_obj_clear_flag(wifi_keyboard, LV_OBJ_FLAG_HIDDEN);
        
        // If this is the password field, set keyboard to password mode
        if (ta == wifi_pass_ta) {
            lv_keyboard_set_mode(wifi_keyboard, LV_KEYBOARD_MODE_TEXT_UPPER);
        }
    }
    
    if (code == LV_EVENT_DEFOCUSED) {
        lv_keyboard_set_textarea(wifi_keyboard, NULL);
    }
}

// WiFi button callback
static void btn_wifi_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "WiFi button pressed");
    
    // If already connected, show status screen directly
    if (wifi_connected) {
        wifi_show_status(wifi_connected_ssid);
        return;
    }
    
    // Reset battery ring pointer before cleaning
    battery_ring = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_WIFI " WiFi Configuration");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // SSID label
    lv_obj_t *ssid_label = lv_label_create(lv_scr_act());
    lv_label_set_text(ssid_label, "Network Name (SSID):");
    lv_obj_set_style_text_font(ssid_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(ssid_label, lv_color_white(), 0);
    lv_obj_align(ssid_label, LV_ALIGN_TOP_LEFT, 20, 60);
    
    // SSID text area
    wifi_ssid_ta = lv_textarea_create(lv_scr_act());
    lv_obj_set_size(wifi_ssid_ta, 280, 40);
    lv_obj_align(wifi_ssid_ta, LV_ALIGN_TOP_MID, 0, 80);
    lv_textarea_set_placeholder_text(wifi_ssid_ta, "Enter WiFi name");
    lv_textarea_set_one_line(wifi_ssid_ta, true);
    lv_textarea_set_max_length(wifi_ssid_ta, 32);
    lv_obj_add_event_cb(wifi_ssid_ta, wifi_ta_event_cb, LV_EVENT_ALL, NULL);
    
    // Password label
    lv_obj_t *pass_label = lv_label_create(lv_scr_act());
    lv_label_set_text(pass_label, "Password:");
    lv_obj_set_style_text_font(pass_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(pass_label, lv_color_white(), 0);
    lv_obj_align(pass_label, LV_ALIGN_TOP_LEFT, 20, 130);
    
    // Password text area
    wifi_pass_ta = lv_textarea_create(lv_scr_act());
    lv_obj_set_size(wifi_pass_ta, 280, 40);
    lv_obj_align(wifi_pass_ta, LV_ALIGN_TOP_MID, 0, 150);
    lv_textarea_set_placeholder_text(wifi_pass_ta, "Enter password");
    lv_textarea_set_one_line(wifi_pass_ta, true);
    lv_textarea_set_password_mode(wifi_pass_ta, true);
    lv_textarea_set_max_length(wifi_pass_ta, 64);
    lv_obj_add_event_cb(wifi_pass_ta, wifi_ta_event_cb, LV_EVENT_ALL, NULL);
    
    // Show password checkbox
    lv_obj_t *show_pass_cb = lv_checkbox_create(lv_scr_act());
    lv_checkbox_set_text(show_pass_cb, "Show password");
    lv_obj_align(show_pass_cb, LV_ALIGN_TOP_LEFT, 20, 195);
    lv_obj_set_style_text_font(show_pass_cb, &lv_font_montserrat_12, 0);
    lv_obj_add_event_cb(show_pass_cb, wifi_show_pass_cb, LV_EVENT_ALL, NULL);
    
    // Connection List button
    lv_obj_t *btn_list = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_list, 150, 45);
    lv_obj_align(btn_list, LV_ALIGN_TOP_LEFT, 30, 240);
    lv_obj_set_style_bg_color(btn_list, lv_color_hex(0x2196F3), 0);
    lv_obj_add_event_cb(btn_list, btn_wifi_list_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *list_label = lv_label_create(btn_list);
    lv_label_set_text(list_label, LV_SYMBOL_LIST "  Saved");
    lv_obj_set_style_text_font(list_label, &lv_font_montserrat_14, 0);
    lv_obj_center(list_label);
    
    // Connect button
    lv_obj_t *btn_connect = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_connect, 150, 45);
    lv_obj_align(btn_connect, LV_ALIGN_TOP_RIGHT, -30, 240);
    lv_obj_set_style_bg_color(btn_connect, lv_color_hex(0x4CAF50), 0);
    lv_obj_add_event_cb(btn_connect, btn_wifi_connect_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *connect_label = lv_label_create(btn_connect);
    lv_label_set_text(connect_label, LV_SYMBOL_WIFI "  Connect");
    lv_obj_set_style_text_font(connect_label, &lv_font_montserrat_14, 0);
    lv_obj_center(connect_label);
    
    // Status label (for feedback)
    wifi_status_label = lv_label_create(lv_scr_act());
    lv_label_set_text(wifi_status_label, "");
    lv_obj_set_style_text_font(wifi_status_label, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_color(wifi_status_label, lv_color_hex(0xFFAA00), 0);
    lv_obj_align(wifi_status_label, LV_ALIGN_TOP_MID, 0, 295);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 150, 45);
    lv_obj_align(btn_back, LV_ALIGN_TOP_MID, 0, 320);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label2 = lv_label_create(btn_back);
    lv_label_set_text(back_label2, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label2, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label2);
    
    // Create keyboard LAST (initially hidden) - must be after buttons to be on top
    wifi_keyboard = lv_keyboard_create(lv_scr_act());
    lv_obj_set_size(wifi_keyboard, 320, 140);
    lv_obj_align(wifi_keyboard, LV_ALIGN_BOTTOM_MID, 0, -60);
    lv_obj_add_flag(wifi_keyboard, LV_OBJ_FLAG_HIDDEN);
    lv_obj_move_to_index(wifi_keyboard, 1000);  // High z-index to appear on top
    lv_obj_add_event_cb(wifi_keyboard, wifi_keyboard_event_cb, LV_EVENT_ALL, NULL);
}

// Back button callback
static void btn_back_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    ESP_LOGI(TAG, "Back pressed");
    
    // Clear status label pointers
    ble_status_label = NULL;
    ble_device_label = NULL;
    
    // Stop voice memo recording if active
    if (is_recording) {
        is_recording = false;
        if (record_file) {
            fclose(record_file);
            record_file = NULL;
        }
        if (record_timer) {
            lv_timer_del(record_timer);
            record_timer = NULL;
        }
        if (rx_handle) {
            i2s_channel_disable(rx_handle);
            i2s_del_channel(rx_handle);
            rx_handle = NULL;
        }
    }
    
    // Stop Maps voice recording if active
    if (maps_is_recording) {
        maps_is_recording = false;
        if (maps_record_file) {
            fclose(maps_record_file);
            maps_record_file = NULL;
        }
        if (maps_record_timer) {
            lv_timer_del(maps_record_timer);
            maps_record_timer = NULL;
        }
        maps_active_ta = NULL;
        maps_record_btn = NULL;
        maps_record_tick_count = 0;
    }
    
    // Clear battery ring pointer before reinitializing
    battery_ring = NULL;
    
    // Clean screen and set background before recreating menu
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Small delay to let screen stabilize
    lv_refr_now(NULL);
    
    Custom_Menu_Init();
}

// WAV file header structure
typedef struct {
    char riff[4];              // "RIFF"
    uint32_t file_size;        // File size - 8
    char wave[4];              // "WAVE"
    char fmt[4];               // "fmt "
    uint32_t fmt_size;         // 16 for PCM
    uint16_t audio_format;     // 1 for PCM
    uint16_t num_channels;     // 1 for mono
    uint32_t sample_rate;      // 16000
    uint32_t byte_rate;        // sample_rate * num_channels * bits_per_sample/8
    uint16_t block_align;      // num_channels * bits_per_sample/8
    uint16_t bits_per_sample;  // 16
    char data[4];              // "data"
    uint32_t data_size;        // Size of audio data
} wav_header_t;

// Write WAV header
static void write_wav_header(FILE *fp, uint32_t data_size) {
    wav_header_t header;
    
    memcpy(header.riff, "RIFF", 4);
    header.file_size = data_size + 36;
    memcpy(header.wave, "WAVE", 4);
    memcpy(header.fmt, "fmt ", 4);
    header.fmt_size = 16;
    header.audio_format = 1;  // PCM
    header.num_channels = 1;  // Mono
    header.sample_rate = 16000;
    header.byte_rate = 16000 * 1 * 2;  // 16kHz * 1ch * 2 bytes
    header.block_align = 2;
    header.bits_per_sample = 16;
    memcpy(header.data, "data", 4);
    header.data_size = data_size;
    
    fseek(fp, 0, SEEK_SET);
    fwrite(&header, sizeof(wav_header_t), 1, fp);
}

// Initialize I2S microphone for recording
static esp_err_t init_i2s_mic(void) {
    if (rx_handle) {
        return ESP_OK;  // Already initialized
    }
    
    i2s_chan_config_t chan_cfg = I2S_CHANNEL_DEFAULT_CONFIG(I2S_NUM_1, I2S_ROLE_MASTER);
    esp_err_t ret = i2s_new_channel(&chan_cfg, NULL, &rx_handle);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to create I2S channel: %s", esp_err_to_name(ret));
        return ret;
    }
    
    i2s_std_config_t std_cfg = {
        .clk_cfg = I2S_STD_CLK_DEFAULT_CONFIG(16000),
        .slot_cfg = I2S_STD_MSB_SLOT_DEFAULT_CONFIG(I2S_DATA_BIT_WIDTH_32BIT, I2S_SLOT_MODE_MONO),
        .gpio_cfg = {
            .mclk = I2S_GPIO_UNUSED,
            .bclk = GPIO_NUM_15,
            .ws = GPIO_NUM_2,
            .dout = I2S_GPIO_UNUSED,
            .din = GPIO_NUM_39,
            .invert_flags = {
                .mclk_inv = false,
                .bclk_inv = false,
                .ws_inv = false,
            },
        },
    };
    std_cfg.slot_cfg.slot_mask = I2S_STD_SLOT_RIGHT;
    
    ret = i2s_channel_init_std_mode(rx_handle, &std_cfg);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to init I2S std mode: %s", esp_err_to_name(ret));
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
        return ret;
    }
    
    ret = i2s_channel_enable(rx_handle);
    if (ret != ESP_OK) {
        ESP_LOGE(TAG, "Failed to enable I2S channel: %s", esp_err_to_name(ret));
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
        return ret;
    }
    
    ESP_LOGI(TAG, "I2S microphone initialized");
    return ESP_OK;
}

// Record timer callback - updates time display and writes audio data
static void record_tick_cb(lv_timer_t *t) {
    if (!is_recording || !record_file || !rx_handle) {
        return;
    }
    
    // Update time display every 10 ticks (1 second)
    static int tick_count = 0;
    tick_count++;
    if (tick_count >= 10) {
        tick_count = 0;
        record_seconds++;
        int mins = record_seconds / 60;
        int secs = record_seconds % 60;
        
        if (record_time_label) {
            lv_label_set_text_fmt(record_time_label, "%02d:%02d", mins, secs);
        }
    }
    
    // Read audio data from I2S microphone
    // At 16kHz, need 1600 samples per 100ms
    const size_t buffer_size = 1600;
    int32_t *i2s_buffer = malloc(buffer_size * sizeof(int32_t));
    if (!i2s_buffer) {
        ESP_LOGE(TAG, "Failed to allocate audio buffer");
        return;
    }
    
    size_t bytes_read = 0;
    esp_err_t ret = i2s_channel_read(rx_handle, i2s_buffer, buffer_size * sizeof(int32_t), &bytes_read, 100);
    
    if (ret == ESP_OK && bytes_read > 0) {
        // Convert 32-bit samples to 16-bit
        int16_t *pcm_buffer = malloc(buffer_size * sizeof(int16_t));
        if (pcm_buffer) {
            for (size_t i = 0; i < bytes_read / sizeof(int32_t); i++) {
                // Shift right to get 16-bit sample from 32-bit
                pcm_buffer[i] = (int16_t)(i2s_buffer[i] >> 14);
            }
            
            // Write to file
            size_t samples = bytes_read / sizeof(int32_t);
            fwrite(pcm_buffer, sizeof(int16_t), samples, record_file);
            
            free(pcm_buffer);
        }
    }
    
    free(i2s_buffer);
}

// Record button callback
static void btn_record_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    if (is_recording) {
        ESP_LOGI(TAG, "Already recording");
        return;
    }
    
    ESP_LOGI(TAG, "Starting recording");
    
    // Check if SD card is mounted
    struct stat sd_stat;
    if (stat("/sdcard", &sd_stat) == -1) {
        ESP_LOGE(TAG, "SD card not mounted at /sdcard");
        if (record_status_label) {
            lv_label_set_text(record_status_label, "Error: SD card not found");
        }
        return;
    }
    
    // Generate filename directly in /sdcard root (skip subdirectory for now)
    char filename[64];
    static int file_counter = 0;
    snprintf(filename, sizeof(filename), "/sdcard/memo_%04d.wav", file_counter++);
    strcpy(last_recorded_file, filename);  // Store for verification
    
    ESP_LOGI(TAG, "Opening file: %s", filename);
    
    // Open file
    record_file = fopen(filename, "wb");
    if (!record_file) {
        ESP_LOGE(TAG, "Failed to open file: %s (errno=%d)", filename, errno);
        if (record_status_label) {
            lv_label_set_text(record_status_label, "Error: Cannot create file");
        }
        return;
    }
    
    ESP_LOGI(TAG, "File opened successfully");
    
    // Write placeholder WAV header (will update on stop)
    write_wav_header(record_file, 0);
    
    // Initialize I2S microphone
    if (init_i2s_mic() != ESP_OK) {
        ESP_LOGE(TAG, "Failed to initialize I2S microphone");
        fclose(record_file);
        record_file = NULL;
        if (record_status_label) {
            lv_label_set_text(record_status_label, "Error: Microphone init failed");
        }
        return;
    }
    
    // Start recording
    is_recording = true;
    record_seconds = 0;
    
    if (record_status_label) {
        lv_label_set_text(record_status_label, "Recording...");
        lv_obj_set_style_text_color(record_status_label, lv_color_hex(0xFF0000), 0);
    }
    
    if (record_time_label) {
        lv_label_set_text(record_time_label, "00:00");
    }
    
    // Start timer for audio capture and time display (100ms intervals)
    record_timer = lv_timer_create(record_tick_cb, 100, NULL);
    
    ESP_LOGI(TAG, "Recording started: %s", filename);
}

// Stop recording button callback
static void btn_stop_record_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    if (!is_recording) {
        ESP_LOGI(TAG, "Not recording");
        return;
    }
    
    ESP_LOGI(TAG, "Stopping recording");
    
    is_recording = false;
    
    // Stop timer
    if (record_timer) {
        lv_timer_del(record_timer);
        record_timer = NULL;
    }
    
    // Update WAV header with actual data size
    if (record_file) {
        long data_size = ftell(record_file) - sizeof(wav_header_t);
        ESP_LOGI(TAG, "Data size: %ld bytes", data_size);
        
        // Flush any pending writes
        fflush(record_file);
        
        // Update header
        write_wav_header(record_file, (uint32_t)data_size);
        
        // Flush header update
        fflush(record_file);
        
        fclose(record_file);
        record_file = NULL;
        ESP_LOGI(TAG, "Recording saved (%ld bytes)", data_size);
        
        // Give filesystem time to sync
        vTaskDelay(pdMS_TO_TICKS(200));
        
        // Verify file exists
        struct stat st;
        if (stat(last_recorded_file, &st) == 0) {
            ESP_LOGI(TAG, "File verified: %s (%ld bytes on disk)", last_recorded_file, st.st_size);
        } else {
            ESP_LOGE(TAG, "File NOT found after save: %s (errno=%d)", last_recorded_file, errno);
        }
    }
    
    // Disable I2S
    if (rx_handle) {
        i2s_channel_disable(rx_handle);
        i2s_del_channel(rx_handle);
        rx_handle = NULL;
    }
    
    if (record_status_label) {
        lv_label_set_text(record_status_label, "Stopped");
        lv_obj_set_style_text_color(record_status_label, lv_color_hex(0x00FF00), 0);
    }
    
    // Refresh the voice list to show new recording
    if(voice_memo_list) {
        ESP_LOGI(TAG, "Refreshing voice memo list after recording");
        refresh_voice_list(voice_memo_list);
    }
}

// Play button callback for voice memo
static void btn_play_file_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    char *filename = (char *)lv_event_get_user_data(e);
    if (!filename) return;
    
    ESP_LOGI(TAG, "Playing: %s", filename);
    Play_Music("/sdcard", filename);
}

// Delete button callback for voice memo
static void btn_delete_file_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    char *filepath = (char *)lv_event_get_user_data(e);
    if (!filepath) return;
    
    ESP_LOGI(TAG, "Deleting: %s", filepath);
    
    if (remove(filepath) == 0) {
        ESP_LOGI(TAG, "File deleted successfully");
        // Refresh the voice memo screen
        lv_event_t refresh_event;
        refresh_event.code = LV_EVENT_CLICKED;
        btn_voice_cb(&refresh_event);
    } else {
        ESP_LOGE(TAG, "Failed to delete file");
    }
}

// Refresh voice memo list
static void refresh_voice_list(lv_obj_t *list) {
    // Free malloc'd strings before clearing list to prevent memory leak
    uint32_t child_cnt = lv_obj_get_child_cnt(list);
    for(uint32_t i = 0; i < child_cnt; i++) {
        lv_obj_t *child = lv_obj_get_child(list, i);
        if(child) {
            uint32_t btn_cnt = lv_obj_get_child_cnt(child);
            for(uint32_t j = 0; j < btn_cnt; j++) {
                lv_obj_t *btn = lv_obj_get_child(child, j);
                if(btn && lv_obj_check_type(btn, &lv_btn_class)) {
                    void *user_data = lv_obj_get_user_data(btn);
                    if(user_data) {
                        free(user_data);
                    }
                }
            }
        }
    }
    
    // Clear existing list items
    lv_obj_clean(list);
    
    // Open directory
    DIR *dir = opendir("/sdcard");
    if (!dir) {
        ESP_LOGE(TAG, "SD card directory not found (errno=%d: %s)", errno, strerror(errno));
        lv_obj_t *item = lv_list_add_text(list, "No recordings found");
        lv_obj_set_style_text_color(item, lv_color_hex(0x888888), 0);
        return;
    }
    
    ESP_LOGI(TAG, "Directory opened, scanning for memo files...");
    
    struct dirent *entry;
    int count = 0;
    int total_files = 0;
    
    while ((entry = readdir(dir)) != NULL) {
        total_files++;
        ESP_LOGI(TAG, "File: %s", entry->d_name);
        
        // Filter .wav files that start with "memo_"
        if (strstr(entry->d_name, "memo_") && strstr(entry->d_name, ".wav")) {
            ESP_LOGI(TAG, "MATCH! Found voice memo: %s", entry->d_name);
            count++;
            
            // Create full path for this file
            static char filepath[300];
            snprintf(filepath, sizeof(filepath), "/sdcard/%s", entry->d_name);
            
            // Create list item with filename
            lv_obj_t *item = lv_list_add_btn(list, LV_SYMBOL_AUDIO, entry->d_name);
            lv_obj_set_style_text_font(item, &lv_font_montserrat_14, 0);
            
            // Add play button
            lv_obj_t *btn_play = lv_btn_create(item);
            lv_obj_set_size(btn_play, 50, 30);
            lv_obj_align(btn_play, LV_ALIGN_RIGHT_MID, -60, 0);
            lv_obj_set_style_bg_color(btn_play, lv_color_hex(0x00AA44), 0);
            
            // Need to store filename persistently for callback
            char *stored_name = malloc(strlen(entry->d_name) + 1);
            strcpy(stored_name, entry->d_name);
            lv_obj_add_event_cb(btn_play, btn_play_file_cb, LV_EVENT_ALL, stored_name);
            
            lv_obj_t *play_label = lv_label_create(btn_play);
            lv_label_set_text(play_label, LV_SYMBOL_PLAY);
            lv_obj_center(play_label);
            
            // Add delete button
            lv_obj_t *btn_del = lv_btn_create(item);
            lv_obj_set_size(btn_del, 50, 30);
            lv_obj_align(btn_del, LV_ALIGN_RIGHT_MID, 0, 0);
            lv_obj_set_style_bg_color(btn_del, lv_color_hex(0xCC0000), 0);
            
            // Store full path for delete
            char *stored_path = malloc(strlen(filepath) + 1);
            strcpy(stored_path, filepath);
            lv_obj_add_event_cb(btn_del, btn_delete_file_cb, LV_EVENT_ALL, stored_path);
            
            lv_obj_t *del_label = lv_label_create(btn_del);
            lv_label_set_text(del_label, LV_SYMBOL_TRASH);
            lv_obj_center(del_label);
        }
    }
    
    closedir(dir);
    
    ESP_LOGI(TAG, "Scanned %d total files, found %d voice memos", total_files, count);
    
    if (count == 0) {
        lv_obj_t *item = lv_list_add_text(list, "No recordings yet");
        lv_obj_set_style_text_color(item, lv_color_hex(0x888888), 0);
    }
}

// Voice Memo button callback
static void btn_voice_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Voice Memo button pressed");
    
    // Reset states
    is_recording = false;
    if (record_timer) {
        lv_timer_del(record_timer);
        record_timer = NULL;
    }
    if (record_file) {
        fclose(record_file);
        record_file = NULL;
    }
    
    // Reset pointers
    battery_ring = NULL;
    voice_memo_list = NULL;
    
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Keep battery ring
    update_battery_ring();
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_AUDIO " Voice Memo");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // Status label
    record_status_label = lv_label_create(lv_scr_act());
    lv_label_set_text(record_status_label, "Ready");
    lv_obj_set_style_text_font(record_status_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(record_status_label, lv_color_hex(0x00FF00), 0);
    lv_obj_align(record_status_label, LV_ALIGN_TOP_MID, 0, 45);
    
    // Time label
    record_time_label = lv_label_create(lv_scr_act());
    lv_label_set_text(record_time_label, "00:00");
    lv_obj_set_style_text_font(record_time_label, &lv_font_montserrat_16, 0);
    lv_obj_set_style_text_color(record_time_label, lv_color_white(), 0);
    lv_obj_align(record_time_label, LV_ALIGN_TOP_MID, 0, 70);
    
    // Record button
    lv_obj_t *btn_rec = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_rec, 120, 50);
    lv_obj_align(btn_rec, LV_ALIGN_TOP_MID, -65, 100);
    lv_obj_set_style_bg_color(btn_rec, lv_color_hex(0xD32F2F), 0);
    lv_obj_add_event_cb(btn_rec, btn_record_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *rec_label = lv_label_create(btn_rec);
    lv_label_set_text(rec_label, LV_SYMBOL_AUDIO " REC");
    lv_obj_set_style_text_font(rec_label, &lv_font_montserrat_14, 0);
    lv_obj_center(rec_label);
    
    // Stop button
    lv_obj_t *btn_stop = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_stop, 120, 50);
    lv_obj_align(btn_stop, LV_ALIGN_TOP_MID, 65, 100);
    lv_obj_set_style_bg_color(btn_stop, lv_color_hex(0x555555), 0);
    lv_obj_add_event_cb(btn_stop, btn_stop_record_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *stop_label = lv_label_create(btn_stop);
    lv_label_set_text(stop_label, LV_SYMBOL_STOP " STOP");
    lv_obj_set_style_text_font(stop_label, &lv_font_montserrat_14, 0);
    lv_obj_center(stop_label);
    
    // Recordings list title
    lv_obj_t *list_title = lv_label_create(lv_scr_act());
    lv_label_set_text(list_title, "Recordings:");
    lv_obj_set_style_text_font(list_title, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(list_title, lv_color_white(), 0);
    lv_obj_align(list_title, LV_ALIGN_TOP_LEFT, 20, 160);
    
    // Scrollable list of recordings
    voice_memo_list = lv_list_create(lv_scr_act());
    lv_obj_set_size(voice_memo_list, 370, 140);
    lv_obj_align(voice_memo_list, LV_ALIGN_TOP_MID, 0, 185);
    lv_obj_set_style_bg_color(voice_memo_list, lv_color_hex(0x222222), 0);
    lv_obj_set_style_border_color(voice_memo_list, lv_color_hex(0x555555), 0);
    lv_obj_set_style_border_width(voice_memo_list, 2, 0);
    
    // Populate list
    refresh_voice_list(voice_memo_list);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *back_label = lv_label_create(btn_back);
    lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
    lv_obj_center(back_label);
}

// Pin Mode button callback (stub for now)
// Toggle switch callback - save to NVS
static void pin_mode_toggle_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code == LV_EVENT_VALUE_CHANGED) {
        lv_obj_t *toggle = lv_event_get_target(e);
        pin_mode_enabled = lv_obj_has_state(toggle, LV_STATE_CHECKED);
        
        ESP_LOGI(TAG, "Pin Mode toggle changed: %s", pin_mode_enabled ? "ON" : "OFF");
        
        // Save to NVS FIRST
        nvs_handle_t nvs_handle;
        esp_err_t err = nvs_open("storage", NVS_READWRITE, &nvs_handle);
        if (err == ESP_OK) {
            err = nvs_set_u8(nvs_handle, "pin_mode", pin_mode_enabled ? 1 : 0);
            if (err == ESP_OK) {
                err = nvs_commit(nvs_handle);
                if (err == ESP_OK) {
                    ESP_LOGI(TAG, "Pin Mode saved to NVS successfully: %s", pin_mode_enabled ? "ON" : "OFF");
                } else {
                    ESP_LOGE(TAG, "Failed to commit NVS: %s", esp_err_to_name(err));
                }
            } else {
                ESP_LOGE(TAG, "Failed to set NVS value: %s", esp_err_to_name(err));
            }
            nvs_close(nvs_handle);
        } else {
            ESP_LOGE(TAG, "Error opening NVS: %s", esp_err_to_name(err));
        }
    }
}

static void btn_pinmode_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code == LV_EVENT_CLICKED) {
        ESP_LOGI(TAG, "Pin Mode screen");
        
        // Clean screen and reset battery ring
        lv_obj_clean(lv_scr_act());
        lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
        battery_ring = NULL;
        update_battery_ring();
        
        // Title
        lv_obj_t *title = lv_label_create(lv_scr_act());
        lv_label_set_text(title, "Pin Mode Settings");
        lv_obj_set_style_text_font(title, &lv_font_montserrat_16, 0);
        lv_obj_set_style_text_color(title, lv_color_white(), 0);
        lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
        
        // Preview area with image
        preview_container = lv_obj_create(lv_scr_act());
        lv_obj_set_size(preview_container, 200, 200);
        lv_obj_align(preview_container, LV_ALIGN_TOP_MID, 0, 60);
        lv_obj_set_style_bg_color(preview_container, lv_color_hex(0x222222), 0);
        lv_obj_set_style_border_color(preview_container, lv_color_hex(0x555555), 0);
        lv_obj_set_style_border_width(preview_container, 2, 0);
        lv_obj_set_style_radius(preview_container, 100, 0);
        lv_obj_set_style_clip_corner(preview_container, true, 0);
        
        // Load embedded home icon (256x256)
        preview_img = lv_img_create(preview_container);
        lv_img_set_src(preview_img, &home_icon);
        lv_img_set_zoom(preview_img, 128);  // 128 = 50% zoom (256x256 -> 128x128)
        lv_obj_center(preview_img);
        ESP_LOGI(TAG, "Pin Mode preview: Loaded embedded home icon at 50%% zoom");
        ESP_LOGI(TAG, "Pin Mode state: %s", pin_mode_enabled ? "ENABLED" : "DISABLED");
        
        // Toggle switch label
        lv_obj_t *toggle_label = lv_label_create(lv_scr_act());
        lv_label_set_text(toggle_label, "Start with Pin Mode:");
        lv_obj_set_style_text_color(toggle_label, lv_color_white(), 0);
        lv_obj_align(toggle_label, LV_ALIGN_TOP_LEFT, 40, 265);
        
        // Toggle switch with NVS state
        pin_mode_toggle = lv_switch_create(lv_scr_act());
        lv_obj_align(pin_mode_toggle, LV_ALIGN_TOP_RIGHT, -40, 260);
        lv_obj_set_style_bg_color(pin_mode_toggle, lv_color_hex(0x444444), LV_PART_MAIN | LV_STATE_DEFAULT);
        lv_obj_set_style_bg_color(pin_mode_toggle, lv_color_hex(0x00AA00), LV_PART_INDICATOR | LV_STATE_CHECKED);
        if (pin_mode_enabled) {
            lv_obj_add_state(pin_mode_toggle, LV_STATE_CHECKED);
            ESP_LOGI(TAG, "Toggle initialized: CHECKED");
        } else {
            ESP_LOGI(TAG, "Toggle initialized: UNCHECKED");
        }
        lv_obj_add_event_cb(pin_mode_toggle, pin_mode_toggle_cb, LV_EVENT_VALUE_CHANGED, NULL);
        
        // Image selector dropdown (above back button)
        lv_obj_t *dd = lv_dropdown_create(lv_scr_act());
        lv_obj_set_width(dd, 280);
        lv_obj_align(dd, LV_ALIGN_BOTTOM_MID, 0, -65);  // Position from bottom, above back button
        lv_obj_set_style_bg_color(dd, lv_color_hex(0x333333), 0);
        lv_obj_set_style_text_color(dd, lv_color_white(), 0);
        
        // Make dropdown list open UPWARD to avoid covering back button at bottom
        lv_dropdown_set_dir(dd, LV_DIR_TOP);
        
        // Limit dropdown list height
        lv_obj_t *dd_list = lv_dropdown_get_list(dd);
        if (dd_list) {
            lv_obj_set_height(dd_list, 150);  // Max height before scrolling
        }
        
        // Start with Default (Embedded) option
        char options[2048] = "Default (Embedded)";
        int selected_idx = 0;  // Default to first option
        int idx = 1;  // Start at index 1 (0 is Default)
        
        // Check if currently using default (empty string)
        bool using_default = (selected_image_file[0] == '\0');
        
        // Track animation prefixes we've already added
        char added_prefixes[50][64];  // Support up to 50 unique animations
        int prefix_count = 0;
        
        // Scan SD card for .bin files
        DIR *dir = opendir("/sdcard");
        if (dir) {
            struct dirent *entry;
            while ((entry = readdir(dir)) != NULL) {
                if (strstr(entry->d_name, ".bin")) {
                    // Check if this is an animation frame (prefix_NNN.bin pattern)
                    const char *underscore = strrchr(entry->d_name, '_');
                    bool is_frame = false;
                    char prefix[64] = {0};
                    
                    if (underscore && strlen(underscore) == 8 && strcmp(underscore + 4, ".bin") == 0) {
                        // Likely animation format: prefix_NNN.bin
                        // Extract prefix
                        size_t prefix_len = underscore - entry->d_name;
                        if (prefix_len < sizeof(prefix)) {
                            strncpy(prefix, entry->d_name, prefix_len);
                            prefix[prefix_len] = '\0';
                            
                            // Check if this is frame 000 (first frame)
                            if (underscore[1] == '0' && underscore[2] == '0' && underscore[3] == '0') {
                                // Check if we already added this prefix
                                bool already_added = false;
                                for (int i = 0; i < prefix_count; i++) {
                                    if (strcmp(added_prefixes[i], prefix) == 0) {
                                        already_added = true;
                                        break;
                                    }
                                }
                                
                                if (!already_added && prefix_count < 50) {
                                    // Add as animation entry (prefix only, no extension, with icon)
                                    strcat(options, "\n");
                                    strcat(options, LV_SYMBOL_PLAY " ");
                                    strcat(options, prefix);
                                    
                                    // Track this prefix
                                    strcpy(added_prefixes[prefix_count], prefix);
                                    prefix_count++;
                                    
                                    // Check if this is the selected animation
                                    if (!using_default) {
                                        // Check if selected_image_file matches this prefix pattern
                                        if (strncmp(selected_image_file, prefix, strlen(prefix)) == 0 &&
                                            selected_image_file[strlen(prefix)] == '_') {
                                            selected_idx = idx;
                                        }
                                    }
                                    
                                    is_frame = true;
                                    idx++;
                                }
                            }
                        }
                    }
                    
                    // If not an animation frame, add as regular static image
                    if (!is_frame) {
                        // Skip if this matches any animation prefix
                        bool is_other_frame = false;
                        for (int i = 0; i < prefix_count; i++) {
                            if (strncmp(entry->d_name, added_prefixes[i], strlen(added_prefixes[i])) == 0 &&
                                entry->d_name[strlen(added_prefixes[i])] == '_') {
                                is_other_frame = true;
                                break;
                            }
                        }
                        
                        if (!is_other_frame) {
                            // Add as static image (remove .bin extension)
                            char display_name[64];
                            strncpy(display_name, entry->d_name, sizeof(display_name) - 1);
                            char *dot = strrchr(display_name, '.');
                            if (dot) *dot = '\0';  // Remove extension
                            
                            strcat(options, "\n");
                            strcat(options, display_name);
                            
                            if (!using_default && strcmp(entry->d_name, selected_image_file) == 0) {
                                selected_idx = idx;
                            }
                            idx++;
                        }
                    }
                }
            }
            closedir(dir);
        }
        
        lv_dropdown_set_options(dd, options);
        lv_dropdown_set_selected(dd, selected_idx);
        lv_obj_add_event_cb(dd, image_selector_cb, LV_EVENT_VALUE_CHANGED, NULL);
        
        // Back button
        lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
        lv_obj_set_size(btn_back, 200, 50);
        lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);  // Standard position
        lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
        lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
        
        lv_obj_t *back_label = lv_label_create(btn_back);
        lv_label_set_text(back_label, LV_SYMBOL_LEFT " Back");
        lv_obj_set_style_text_font(back_label, &lv_font_montserrat_14, 0);
        lv_obj_center(back_label);
    }
}