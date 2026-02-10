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
#include "esp_log.h"
#include "driver/i2s_std.h"
#include "nvs_flash.h"
#include "nvs.h"
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
static bool pin_mode_enabled = false;
static lv_obj_t *pin_mode_toggle = NULL;
static uint32_t last_tap_time = 0;
static bool skip_pin_mode_once = false;

// External variables
extern uint32_t SDCard_Size;
extern uint8_t LCD_Backlight;

// Forward declarations
static void btn_brightness_cb(lv_event_t *e);
static void btn_sys_cb(lv_event_t *e);
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
            Custom_Menu_Init();  // Reload menu
        } else {
            last_tap_time = now;
            ESP_LOGI(TAG, "Single tap registered - tap_time=%lu - waiting for second tap", now);
        }
    } else {
        ESP_LOGI(TAG, "Other event code: %d (not CLICKED)", code);
    }
}

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
    
    // Load Pin Mode settings from NVS
    nvs_handle_t nvs_handle;
    esp_err_t err = nvs_open("storage", NVS_READONLY, &nvs_handle);
    if (err == ESP_OK) {
        uint8_t mode = 0;
        if (nvs_get_u8(nvs_handle, "pin_mode", &mode) == ESP_OK) {
            pin_mode_enabled = (mode == 1);
            ESP_LOGI(TAG, "Loaded Pin Mode: %s", pin_mode_enabled ? "ON" : "OFF");
        }
        nvs_close(nvs_handle);
    }
    
    battery_ring = NULL;
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    update_battery_ring_ex(false);  // Always show ring initially
    
    // Check Pin Mode - show image on boot or show menu (unless bypassed by double-tap)
    if (pin_mode_enabled && !skip_pin_mode_once) {
        ESP_LOGI(TAG, "=== PIN MODE ENABLED - SETTING UP HOME IMAGE ===");
        ESP_LOGI(TAG, "home_icon descriptor: w=%d, h=%d, cf=%d", home_icon.header.w, home_icon.header.h, home_icon.header.cf);
        
        // Full screen image display - zoom to fill 412x412 circular display
        lv_obj_t *home_img = lv_img_create(lv_scr_act());
        ESP_LOGI(TAG, "Image object created: %p", home_img);
        
        lv_img_set_src(home_img, &home_icon);
        ESP_LOGI(TAG, "Image source set to home_icon");
        
        lv_img_set_zoom(home_img, 412);  // 412 = 161% zoom (256x256 -> ~412x412 to fill screen)
        ESP_LOGI(TAG, "Image zoom set to 412 (161%%)");
        
        lv_obj_center(home_img);
        ESP_LOGI(TAG, "Image centered on screen");
        
        // Add double-tap detection to the image itself
        lv_obj_add_flag(home_img, LV_OBJ_FLAG_CLICKABLE);
        ESP_LOGI(TAG, "Image set as CLICKABLE");
        
        lv_obj_move_to_index(home_img, 96);
        ESP_LOGI(TAG, "Image z-index set to 96");
        
        lv_obj_add_event_cb(home_img, home_image_tap_cb, LV_EVENT_CLICKED, NULL);
        ESP_LOGI(TAG, "Event callback attached to image for LV_EVENT_CLICKED");
        
        last_tap_time = 0;  // Reset tap timer
        ESP_LOGI(TAG, "Tap timer reset to 0");
        
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
    
    // 2x2 Grid of buttons with icons (centered)
    int btn_size = 140;
    int spacing = 20;
    int start_y = 56;  // Center vertically: (412 - 300) / 2
    
    // Row 1, Col 1: Brightness
    lv_obj_t *btn_bright = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_bright, btn_size, btn_size);
    lv_obj_align(btn_bright, LV_ALIGN_TOP_MID, -btn_size/2 - spacing/2, start_y);
    lv_obj_set_style_bg_color(btn_bright, lv_color_hex(0xFFA000), 0);
    lv_obj_set_style_radius(btn_bright, 20, 0);
    lv_obj_add_event_cb(btn_bright, btn_brightness_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label_bright = lv_label_create(btn_bright);
    lv_label_set_text(label_bright, LV_SYMBOL_EYE_OPEN "\nBright");
    lv_obj_set_style_text_font(label_bright, &lv_font_montserrat_16, 0);
    lv_obj_center(label_bright);
    
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

// Brightness button callback
static void btn_brightness_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Brightness button pressed");
    
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
    btn_brightness_cb(e);
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
    btn_brightness_cb(e);
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
    lv_obj_align(bat_label, LV_ALIGN_CENTER, 0, -60);
    
    // Temperature info
    float temp = getTemperature();
    lv_obj_t *temp_label = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(temp_label, "Temp: %.1f C", temp);
    lv_obj_set_style_text_font(temp_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(temp_label, lv_color_hex(0xE65100), 0);
    lv_obj_align(temp_label, LV_ALIGN_CENTER, 0, -30);
    
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
    lv_obj_align(sd_label, LV_ALIGN_CENTER, 0, 10);
    
    // Chip info
    lv_obj_t *chip_label = lv_label_create(lv_scr_act());
    lv_label_set_text(chip_label, "ESP32-S3 @ 240MHz");
    lv_obj_set_style_text_font(chip_label, &lv_font_montserrat_14, 0);
    lv_obj_set_style_text_color(chip_label, lv_color_hex(0xFFFF00), 0);
    lv_obj_align(chip_label, LV_ALIGN_CENTER, 0, 50);
    
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

// Back button callback
static void btn_back_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    ESP_LOGI(TAG, "Back pressed");
    
    // Clear status label pointers
    ble_status_label = NULL;
    ble_device_label = NULL;
    
    // Stop recording if active
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
        
        // Save to NVS
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
        lv_obj_t *preview = lv_obj_create(lv_scr_act());
        lv_obj_set_size(preview, 200, 200);
        lv_obj_align(preview, LV_ALIGN_TOP_MID, 0, 60);
        lv_obj_set_style_bg_color(preview, lv_color_hex(0x222222), 0);
        lv_obj_set_style_border_color(preview, lv_color_hex(0x555555), 0);
        lv_obj_set_style_border_width(preview, 2, 0);
        lv_obj_set_style_radius(preview, 100, 0);
        lv_obj_set_style_clip_corner(preview, true, 0);
        
        // Load embedded home icon (256x256)
        lv_obj_t *img = lv_img_create(preview);
        lv_img_set_src(img, &home_icon);
        lv_img_set_zoom(img, 128);  // 128 = 50% zoom (256x256 -> 128x128)
        lv_obj_center(img);
        ESP_LOGI(TAG, "Pin Mode preview: Loaded embedded home icon at 50%% zoom");
        ESP_LOGI(TAG, "Pin Mode state: %s", pin_mode_enabled ? "ENABLED" : "DISABLED");
        
        // Debug info label (shows current state)
        lv_obj_t *debug_label = lv_label_create(lv_scr_act());
        lv_label_set_text_fmt(debug_label, "Embedded Icon: 256x256\nState: %s", pin_mode_enabled ? "ON" : "OFF");
        lv_obj_set_style_text_color(debug_label, lv_color_hex(0x888888), 0);
        lv_obj_set_style_text_font(debug_label, &lv_font_montserrat_12, 0);
        lv_obj_align(debug_label, LV_ALIGN_TOP_MID, 0, 265);
        
        // Toggle switch label
        lv_obj_t *toggle_label = lv_label_create(lv_scr_act());
        lv_label_set_text(toggle_label, "Start with Pin Mode:");
        lv_obj_set_style_text_color(toggle_label, lv_color_white(), 0);
        lv_obj_align(toggle_label, LV_ALIGN_TOP_LEFT, 40, 310);
        
        // Toggle switch with NVS state
        pin_mode_toggle = lv_switch_create(lv_scr_act());
        lv_obj_align(pin_mode_toggle, LV_ALIGN_TOP_RIGHT, -40, 305);
        lv_obj_set_style_bg_color(pin_mode_toggle, lv_color_hex(0x444444), LV_PART_MAIN | LV_STATE_DEFAULT);
        lv_obj_set_style_bg_color(pin_mode_toggle, lv_color_hex(0x00AA00), LV_PART_INDICATOR | LV_STATE_CHECKED);
        if (pin_mode_enabled) {
            lv_obj_add_state(pin_mode_toggle, LV_STATE_CHECKED);
            ESP_LOGI(TAG, "Toggle initialized: CHECKED");
        } else {
            ESP_LOGI(TAG, "Toggle initialized: UNCHECKED");
        }
        lv_obj_add_event_cb(pin_mode_toggle, pin_mode_toggle_cb, LV_EVENT_VALUE_CHANGED, NULL);
        
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
}