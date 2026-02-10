/**
 * ESP32-S3 Control Center
 * Full featured interface with WiFi, Audio, Recording
 * Config file: /config.json on SD card
 * 
 * FIXED VERSION - Resolved:
 * - Duplicate function definitions
 * - Memory leaks in static allocations
 * - Buffer timing and LVGL task handler synchronization
 * - Proper cleanup before screen switches
 * - Null pointer safety checks
 */

#include "Custom_Menu.h"
#include "lvgl.h"
#include "SD_Card.h"
#include "BAT_Driver.h"
#include "Audio_PCM5101.h"
#include "MIC_MSM.h"
#include <WiFi.h>
#include <FS.h>
#include <SD.h>
#include <ArduinoJson.h>

// Configuration structure
struct Config {
    String wifi_ssid = "";
    String wifi_password = "";
    int brightness = 50;
    int volume = 50;
    uint32_t theme_color = 0x1565C0;
    String pin_image = "/badge.bmp";
    bool auto_connect_wifi = true;
} config;

// Global state
static lv_obj_t *status_bar = NULL;
static lv_obj_t *label_battery = NULL;
static lv_obj_t *label_wifi = NULL;
static lv_obj_t *keyboard = NULL;
static lv_obj_t *wifi_password_ta = NULL;
static lv_obj_t *status_ring = NULL;
static String selected_ssid = "";
static String pin_image_path = "/badge.bmp";
static bool recording_active = false; // Fixed: no dynamic allocation

// Forward declarations
void create_main_menu();
void wifi_setup_screen();
void audio_control_screen();
void recording_screen();
void system_info_screen();
void pin_mode_screen();
void select_pin_image_screen();
void activate_pin_mode();
void settings_screen();
void btn_back_clicked(lv_event_t *e);
void update_status_bar();
void show_keyboard(lv_obj_t *textarea);
void scan_wifi_networks(lv_obj_t *list);
void connect_to_wifi(String ssid, String password);
void set_status_ring(uint32_t color, bool pulse = false);
void remove_status_ring();
void load_config();
void save_config();
void create_default_config();
void safe_screen_clean();

// Safe screen cleaning with proper LVGL buffer handling
void safe_screen_clean() {
    // Remove keyboard first if active
    if (keyboard) {
        lv_obj_del(keyboard);
        keyboard = NULL;
    }
    
    // Remove status ring if active
    remove_status_ring();
    
    // Reset global UI element pointers
    status_bar = NULL;
    label_battery = NULL;
    label_wifi = NULL;
    wifi_password_ta = NULL;
    
    // Clean screen and force LVGL to process
    lv_obj_clean(lv_scr_act());
    lv_task_handler();
    delay(10); // Brief delay for buffer sync
}

void Custom_Menu_Init() {
    load_config();
    
    // Auto-connect WiFi if configured
    if (config.auto_connect_wifi && !config.wifi_ssid.isEmpty()) {
        connect_to_wifi(config.wifi_ssid, config.wifi_password);
    }
    
    // Apply settings
    Set_Backlight(config.brightness);
    // Audio_Volume(config.volume); // Commented - may cause issues with audio lib
    pin_image_path = config.pin_image;
    
    create_main_menu();
}

void load_config() {
    if (!SD.exists("/config.json")) {
        create_default_config();
        return;
    }
    
    File file = SD.open("/config.json", FILE_READ);
    if (!file) {
        create_default_config();
        return;
    }
    
    StaticJsonDocument<512> doc;
    DeserializationError error = deserializeJson(doc, file);
    file.close();
    
    if (error) {
        create_default_config();
        return;
    }
    
    // Load settings
    config.wifi_ssid = doc["wifi_ssid"] | "";
    config.wifi_password = doc["wifi_password"] | "";
    config.brightness = doc["brightness"] | 50;
    config.volume = doc["volume"] | 50;
    config.theme_color = doc["theme_color"] | 0x1565C0;
    config.pin_image = doc["pin_image"] | "/badge.bmp";
    config.auto_connect_wifi = doc["auto_connect_wifi"] | true;
}

void save_config() {
    StaticJsonDocument<512> doc;
    
    doc["wifi_ssid"] = config.wifi_ssid;
    doc["wifi_password"] = config.wifi_password;
    doc["brightness"] = config.brightness;
    doc["volume"] = config.volume;
    doc["theme_color"] = config.theme_color;
    doc["pin_image"] = config.pin_image;
    doc["auto_connect_wifi"] = config.auto_connect_wifi;
    
    File file = SD.open("/config.json", FILE_WRITE);
    if (file) {
        serializeJsonPretty(doc, file);
        file.close();
    }
}

void create_default_config() {
    config.wifi_ssid = "";
    config.wifi_password = "";
    config.brightness = 50;
    config.volume = 50;
    config.theme_color = 0x1565C0;
    config.pin_image = "/badge.bmp";
    config.auto_connect_wifi = true;
    
    save_config();
}

void set_status_ring(uint32_t color, bool pulse) {
    // Remove old ring if exists
    if (status_ring) {
        lv_obj_del(status_ring);
        status_ring = NULL;
    }
    
    lv_task_handler(); // Process deletion
    
    // Create circular border around screen edge
    status_ring = lv_obj_create(lv_scr_act());
    lv_obj_set_size(status_ring, 412, 412);
    lv_obj_center(status_ring);
    lv_obj_set_style_bg_opa(status_ring, LV_OPA_TRANSP, 0);
    lv_obj_set_style_border_width(status_ring, 8, 0);
    lv_obj_set_style_border_color(status_ring, lv_color_hex(color), 0);
    lv_obj_set_style_radius(status_ring, LV_RADIUS_CIRCLE, 0);
    lv_obj_clear_flag(status_ring, LV_OBJ_FLAG_CLICKABLE);
    lv_obj_move_background(status_ring);
    
    if (pulse) {
        // Add pulsing animation
        lv_anim_t anim;
        lv_anim_init(&anim);
        lv_anim_set_var(&anim, status_ring);
        lv_anim_set_values(&anim, 8, 15);
        lv_anim_set_time(&anim, 800);
        lv_anim_set_playback_time(&anim, 800);
        lv_anim_set_repeat_count(&anim, LV_ANIM_REPEAT_INFINITE);
        lv_anim_set_exec_cb(&anim, [](void *obj, int32_t v) {
            lv_obj_set_style_border_width((lv_obj_t*)obj, v, 0);
        });
        lv_anim_start(&anim);
    }
    
    lv_task_handler(); // Process creation
}

void remove_status_ring() {
    if (status_ring) {
        lv_obj_del(status_ring);
        status_ring = NULL;
        lv_task_handler(); // Process deletion
    }
}

void update_status_bar() {
    if (!status_bar) return;
    
    // Battery
    if (label_battery) {
        float voltage = BAT_Get_Volts();
        int percent = (int)((voltage - 3.3) / (4.2 - 3.3) * 100);
        if (percent > 100) percent = 100;
        if (percent < 0) percent = 0;
        lv_label_set_text_fmt(label_battery, LV_SYMBOL_BATTERY_FULL " %d%%", percent);
    }
    
    // WiFi status
    if (label_wifi) {
        if (WiFi.status() == WL_CONNECTED) {
            lv_label_set_text_fmt(label_wifi, LV_SYMBOL_WIFI " %s", WiFi.SSID().c_str());
        } else {
            lv_label_set_text(label_wifi, LV_SYMBOL_WIFI " Disconnected");
        }
    }
}

void create_main_menu() {
    safe_screen_clean();
    
    // Status bar
    status_bar = lv_obj_create(lv_scr_act());
    lv_obj_set_size(status_bar, 412, 35);
    lv_obj_align(status_bar, LV_ALIGN_TOP_MID, 0, 0);
    lv_obj_set_style_bg_color(status_bar, lv_color_hex(0x0D47A1), 0);
    lv_obj_set_style_border_width(status_bar, 0, 0);
    lv_obj_set_style_radius(status_bar, 0, 0);
    
    label_battery = lv_label_create(status_bar);
    lv_obj_set_style_text_color(label_battery, lv_color_white(), 0);
    lv_obj_set_style_text_font(label_battery, &lv_font_montserrat_12, 0);
    lv_obj_align(label_battery, LV_ALIGN_LEFT_MID, 5, 0);
    
    label_wifi = lv_label_create(status_bar);
    lv_obj_set_style_text_color(label_wifi, lv_color_white(), 0);
    lv_obj_set_style_text_font(label_wifi, &lv_font_montserrat_12, 0);
    lv_obj_align(label_wifi, LV_ALIGN_RIGHT_MID, -5, 0);
    
    update_status_bar();
    
    // Title
    lv_obj_t *title_bar = lv_obj_create(lv_scr_act());
    lv_obj_set_size(title_bar, 412, 50);
    lv_obj_align(title_bar, LV_ALIGN_TOP_MID, 0, 35);
    lv_obj_set_style_bg_color(title_bar, lv_color_hex(0x1565C0), 0);
    lv_obj_set_style_border_width(title_bar, 0, 0);
    lv_obj_set_style_radius(title_bar, 0, 0);
    
    lv_obj_t *title_label = lv_label_create(title_bar);
    lv_label_set_text(title_label, "CONTROL CENTER");
    lv_obj_set_style_text_font(title_label, &lv_font_montserrat_22, 0);
    lv_obj_set_style_text_color(title_label, lv_color_white(), 0);
    lv_obj_center(title_label);
    
    // Menu buttons
    int y = 105;
    int h = 55;
    int s = 10;
    
    // WiFi Setup
    lv_obj_t *btn_wifi = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_wifi, 380, h);
    lv_obj_align(btn_wifi, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_wifi, lv_color_hex(0x1976D2), 0);
    lv_obj_add_event_cb(btn_wifi, [](lv_event_t *e) { wifi_setup_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l1 = lv_label_create(btn_wifi);
    lv_label_set_text(l1, LV_SYMBOL_WIFI "  WiFi Setup");
    lv_obj_set_style_text_font(l1, &lv_font_montserrat_20, 0);
    lv_obj_center(l1);
    y += h + s;
    
    // Audio Control
    lv_obj_t *btn_audio = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_audio, 380, h);
    lv_obj_align(btn_audio, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_audio, lv_color_hex(0x388E3C), 0);
    lv_obj_add_event_cb(btn_audio, [](lv_event_t *e) { audio_control_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l2 = lv_label_create(btn_audio);
    lv_label_set_text(l2, LV_SYMBOL_AUDIO "  Audio Control");
    lv_obj_set_style_text_font(l2, &lv_font_montserrat_20, 0);
    lv_obj_center(l2);
    y += h + s;
    
    // Recording
    lv_obj_t *btn_rec = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_rec, 380, h);
    lv_obj_align(btn_rec, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_rec, lv_color_hex(0xD32F2F), 0);
    lv_obj_add_event_cb(btn_rec, [](lv_event_t *e) { recording_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l3 = lv_label_create(btn_rec);
    lv_label_set_text(l3, LV_SYMBOL_STOP "  Recording");
    lv_obj_set_style_text_font(l3, &lv_font_montserrat_20, 0);
    lv_obj_center(l3);
    y += h + s;
    
    // System Info
    lv_obj_t *btn_sys = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_sys, 380, h);
    lv_obj_align(btn_sys, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_sys, lv_color_hex(0x7B1FA2), 0);
    lv_obj_add_event_cb(btn_sys, [](lv_event_t *e) { system_info_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l4 = lv_label_create(btn_sys);
    lv_label_set_text(l4, LV_SYMBOL_SETTINGS "  System Info");
    lv_obj_set_style_text_font(l4, &lv_font_montserrat_20, 0);
    lv_obj_center(l4);
    y += h + s;
    
    // Pin Mode
    lv_obj_t *btn_pin = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_pin, 380, h);
    lv_obj_align(btn_pin, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_pin, lv_color_hex(0xF57C00), 0);
    lv_obj_add_event_cb(btn_pin, [](lv_event_t *e) { pin_mode_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l5 = lv_label_create(btn_pin);
    lv_label_set_text(l5, LV_SYMBOL_IMAGE "  Pin Mode");
    lv_obj_set_style_text_font(l5, &lv_font_montserrat_20, 0);
    lv_obj_center(l5);
    y += h + s;
    
    // Settings
    lv_obj_t *btn_settings = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_settings, 380, h);
    lv_obj_align(btn_settings, LV_ALIGN_TOP_MID, 0, y);
    lv_obj_set_style_bg_color(btn_settings, lv_color_hex(0x455A64), 0);
    lv_obj_add_event_cb(btn_settings, [](lv_event_t *e) { settings_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l6 = lv_label_create(btn_settings);
    lv_label_set_text(l6, LV_SYMBOL_SETTINGS "  Settings");
    lv_obj_set_style_text_font(l6, &lv_font_montserrat_20, 0);
    lv_obj_center(l6);
    
    lv_task_handler(); // Process all UI creation
}

void btn_back_clicked(lv_event_t *e) {
    create_main_menu();
}

void pin_mode_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0xE65100), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_IMAGE " Pin Mode");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_24, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // Info
    lv_obj_t *info = lv_label_create(lv_scr_act());
    lv_label_set_text(info, "Display badge image\nuntil screen is tapped");
    lv_obj_set_style_text_color(info, lv_color_white(), 0);
    lv_obj_set_style_text_align(info, LV_TEXT_ALIGN_CENTER, 0);
    lv_obj_align(info, LV_ALIGN_TOP_MID, 0, 70);
    
    // Current image path
    lv_obj_t *path_label = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(path_label, "Image: %s", pin_image_path.c_str());
    lv_obj_set_style_text_color(path_label, lv_color_hex(0xFFCC80), 0);
    lv_obj_set_style_text_font(path_label, &lv_font_montserrat_14, 0);
    lv_obj_align(path_label, LV_ALIGN_TOP_MID, 0, 130);
    
    // Activate button
    lv_obj_t *btn_activate = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_activate, 250, 70);
    lv_obj_align(btn_activate, LV_ALIGN_CENTER, 0, -10);
    lv_obj_set_style_bg_color(btn_activate, lv_color_hex(0xFF6F00), 0);
    lv_obj_add_event_cb(btn_activate, [](lv_event_t *e) { activate_pin_mode(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_activate = lv_label_create(btn_activate);
    lv_label_set_text(l_activate, LV_SYMBOL_IMAGE " Activate Pin");
    lv_obj_set_style_text_font(l_activate, &lv_font_montserrat_22, 0);
    lv_obj_center(l_activate);
    
    // Select image button
    lv_obj_t *btn_select = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_select, 180, 50);
    lv_obj_align(btn_select, LV_ALIGN_BOTTOM_LEFT, 10, -10);
    lv_obj_set_style_bg_color(btn_select, lv_color_hex(0xEF6C00), 0);
    lv_obj_add_event_cb(btn_select, [](lv_event_t *e) { select_pin_image_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_select = lv_label_create(btn_select);
    lv_label_set_text(l_select, "Select Image");
    lv_obj_set_style_text_font(l_select, &lv_font_montserrat_16, 0);
    lv_obj_center(l_select);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 180, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_RIGHT, -10, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_16, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
}

void select_pin_image_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x4E342E), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, "Select Pin Image");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_22, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 15);
    
    // Image list
    lv_obj_t *list = lv_list_create(lv_scr_act());
    lv_obj_set_size(list, 380, 290);
    lv_obj_align(list, LV_ALIGN_TOP_MID, 0, 55);
    lv_obj_set_style_bg_color(list, lv_color_hex(0x5D4037), 0);
    
    // Scan SD card for images
    File root = SD.open("/");
    if (root) {
        File file = root.openNextFile();
        while (file) {
            String name = String(file.name());
            if (!file.isDirectory() && (name.endsWith(".bmp") || name.endsWith(".BMP") || 
                name.endsWith(".jpg") || name.endsWith(".JPG") || 
                name.endsWith(".png") || name.endsWith(".PNG"))) {
                
                lv_obj_t *btn = lv_list_add_btn(list, LV_SYMBOL_IMAGE, name.c_str());
                lv_obj_set_style_text_color(btn, lv_color_white(), 0);
                
                // Set as selected image on click
                lv_obj_add_event_cb(btn, [](lv_event_t *e) {
                    lv_obj_t *btn = lv_event_get_target(e);
                    const char *text = lv_list_get_btn_text(lv_obj_get_parent(btn), btn);
                    if (text) {
                        pin_image_path = "/" + String(text);
                        pin_mode_screen(); // Go back to show selection
                    }
                }, LV_EVENT_CLICKED, NULL);
            }
            file = root.openNextFile();
        }
        file.close();
        root.close();
    }
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 160, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, [](lv_event_t *e) { pin_mode_screen(); }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
}

void activate_pin_mode() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Orange pulsing ring - loading image
    set_status_ring(0xFF6F00, true);
    lv_task_handler();
    delay(100);
    
    // Try to load and display the image
    lv_obj_t *img = lv_img_create(lv_scr_act());
    
    // Check if file exists
    if (SD.exists(pin_image_path.c_str())) {
        // Note: LVGL needs image decoder setup for SD card images
        // For now, try loading - will show placeholder if decoder missing
        lv_img_set_src(img, pin_image_path.c_str());
        lv_obj_center(img);
        
        // Green ring - image loaded
        set_status_ring(0x00FF00, false);
        lv_task_handler();
        delay(500);
        remove_status_ring();
    } else {
        // Red ring - file not found
        set_status_ring(0xFF0000, false);
        lv_task_handler();
        
        // Show error message
        lv_obj_t *error = lv_label_create(lv_scr_act());
        lv_label_set_text(error, "Image not found!\nTap to exit");
        lv_obj_set_style_text_color(error, lv_color_white(), 0);
        lv_obj_set_style_text_font(error, &lv_font_montserrat_22, 0);
        lv_obj_set_style_text_align(error, LV_TEXT_ALIGN_CENTER, 0);
        lv_obj_center(error);
    }
    
    // Make screen tappable to exit
    lv_obj_add_flag(lv_scr_act(), LV_OBJ_FLAG_CLICKABLE);
    lv_obj_add_event_cb(lv_scr_act(), [](lv_event_t *e) {
        remove_status_ring();
        create_main_menu();
    }, LV_EVENT_CLICKED, NULL);
    
    // Add tap hint at bottom
    lv_obj_t *hint = lv_label_create(lv_scr_act());
    lv_label_set_text(hint, "Tap anywhere to exit");
    lv_obj_set_style_text_color(hint, lv_color_hex(0x666666), 0);
    lv_obj_set_style_text_font(hint, &lv_font_montserrat_12, 0);
    lv_obj_align(hint, LV_ALIGN_BOTTOM_MID, 0, -5);
    
    lv_task_handler();
}

void show_keyboard(lv_obj_t *textarea) {
    if (keyboard || !textarea) return;
    
    keyboard = lv_keyboard_create(lv_scr_act());
    lv_obj_set_size(keyboard, 412, 180);
    lv_obj_align(keyboard, LV_ALIGN_BOTTOM_MID, 0, 0);
    lv_keyboard_set_textarea(keyboard, textarea);
    
    lv_task_handler();
}

void scan_wifi_networks(lv_obj_t *list) {
    if (!list) return;
    
    lv_obj_clean(list);
    lv_task_handler();
    
    lv_obj_t *scanning = lv_label_create(list);
    lv_label_set_text(scanning, "Scanning...");
    lv_obj_set_style_text_color(scanning, lv_color_white(), 0);
    lv_task_handler();
    
    int n = WiFi.scanNetworks();
    lv_obj_del(scanning);
    lv_task_handler();
    
    if (n == 0) {
        lv_obj_t *none = lv_label_create(list);
        lv_label_set_text(none, "No networks found");
        lv_obj_set_style_text_color(none, lv_color_white(), 0);
    } else {
        for (int i = 0; i < n && i < 15; i++) {
            String ssid = WiFi.SSID(i);
            int rssi = WiFi.RSSI(i);
            bool encrypted = (WiFi.encryptionType(i) != WIFI_AUTH_OPEN);
            
            String btn_text = ssid + "\n" + String(rssi) + "dBm " + (encrypted ? LV_SYMBOL_LOCK : "");
            
            lv_obj_t *btn = lv_list_add_btn(list, LV_SYMBOL_WIFI, btn_text.c_str());
            lv_obj_set_style_text_color(btn, lv_color_white(), 0);
            
            // Store SSID in user data
            lv_obj_add_event_cb(btn, [](lv_event_t *e) {
                lv_obj_t *btn = lv_event_get_target(e);
                const char *text = lv_list_get_btn_text(lv_obj_get_parent(btn), btn);
                if (text) {
                    String full_text = String(text);
                    int newline_pos = full_text.indexOf('\n');
                    if (newline_pos > 0) {
                        selected_ssid = full_text.substring(0, newline_pos);
                    } else {
                        selected_ssid = full_text;
                    }
                    
                    // Show password input
                    if (wifi_password_ta) {
                        lv_obj_clear_flag(wifi_password_ta, LV_OBJ_FLAG_HIDDEN);
                        show_keyboard(wifi_password_ta);
                    }
                }
            }, LV_EVENT_CLICKED, NULL);
        }
    }
    
    lv_task_handler();
}

void connect_to_wifi(String ssid, String password) {
    // RED pulsing ring - connecting
    set_status_ring(0xFF0000, true);
    lv_task_handler();
    
    WiFi.begin(ssid.c_str(), password.c_str());
    
    // Wait up to 10 seconds
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(100);
        lv_task_handler(); // Keep UI responsive
        delay(400);
        attempts++;
        
        // Switch to BLUE after initial attempts
        if (attempts == 5) {
            set_status_ring(0x0000FF, true);
            lv_task_handler();
        }
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        // GREEN solid ring - connected!
        set_status_ring(0x00FF00, false);
        lv_task_handler();
        delay(1500);
        remove_status_ring();
    } else {
        // PURPLE - failed
        set_status_ring(0xFF00FF, false);
        lv_task_handler();
        delay(1500);
        remove_status_ring();
    }
    
    update_status_bar();
    lv_task_handler();
}

void wifi_setup_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x1A237E), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_WIFI " WiFi Setup");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_24, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 10);
    
    // Password input (hidden initially)
    wifi_password_ta = lv_textarea_create(lv_scr_act());
    lv_obj_set_size(wifi_password_ta, 350, 40);
    lv_obj_align(wifi_password_ta, LV_ALIGN_TOP_MID, 0, 45);
    lv_textarea_set_placeholder_text(wifi_password_ta, "Enter password");
    lv_textarea_set_password_mode(wifi_password_ta, true);
    lv_obj_add_flag(wifi_password_ta, LV_OBJ_FLAG_HIDDEN);
    
    // Connect button
    lv_obj_t *btn_connect = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_connect, 150, 40);
    lv_obj_align(btn_connect, LV_ALIGN_TOP_MID, 0, 90);
    lv_obj_set_style_bg_color(btn_connect, lv_color_hex(0x4CAF50), 0);
    lv_obj_add_event_cb(btn_connect, [](lv_event_t *e) {
        if (!selected_ssid.isEmpty() && wifi_password_ta) {
            const char *pwd = lv_textarea_get_text(wifi_password_ta);
            connect_to_wifi(selected_ssid, String(pwd));
            
            // Hide keyboard and password field
            if (keyboard) {
                lv_obj_del(keyboard);
                keyboard = NULL;
            }
            lv_obj_add_flag(wifi_password_ta, LV_OBJ_FLAG_HIDDEN);
            lv_task_handler();
        }
    }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_connect = lv_label_create(btn_connect);
    lv_label_set_text(l_connect, "Connect");
    lv_obj_center(l_connect);
    
    // Network list
    lv_obj_t *list = lv_list_create(lv_scr_act());
    lv_obj_set_size(list, 380, 150);
    lv_obj_align(list, LV_ALIGN_TOP_MID, 0, 140);
    lv_obj_set_style_bg_color(list, lv_color_hex(0x283593), 0);
    
    // Scan button
    lv_obj_t *btn_scan = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_scan, 180, 45);
    lv_obj_align(btn_scan, LV_ALIGN_BOTTOM_LEFT, 10, -10);
    lv_obj_set_style_bg_color(btn_scan, lv_color_hex(0x1976D2), 0);
    lv_obj_add_event_cb(btn_scan, [](lv_event_t *e) {
        lv_obj_t *list = (lv_obj_t*)lv_event_get_user_data(e);
        scan_wifi_networks(list);
    }, LV_EVENT_CLICKED, list);
    
    lv_obj_t *l_scan = lv_label_create(btn_scan);
    lv_label_set_text(l_scan, LV_SYMBOL_REFRESH " Scan");
    lv_obj_set_style_text_font(l_scan, &lv_font_montserrat_18, 0);
    lv_obj_center(l_scan);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 180, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_RIGHT, -10, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x757575), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
    
    // Auto scan on entry
    scan_wifi_networks(list);
}

void audio_control_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x1B5E20), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_AUDIO " Audio Control");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_26, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // Volume slider
    lv_obj_t *vol_label = lv_label_create(lv_scr_act());
    lv_label_set_text(vol_label, "Volume");
    lv_obj_set_style_text_color(vol_label, lv_color_white(), 0);
    lv_obj_set_style_text_font(vol_label, &lv_font_montserrat_20, 0);
    lv_obj_align(vol_label, LV_ALIGN_TOP_LEFT, 20, 80);
    
    lv_obj_t *vol_val = lv_label_create(lv_scr_act());
    lv_label_set_text(vol_val, "50%");
    lv_obj_set_style_text_color(vol_val, lv_color_white(), 0);
    lv_obj_align(vol_val, LV_ALIGN_TOP_RIGHT, -20, 80);
    
    lv_obj_t *slider_vol = lv_slider_create(lv_scr_act());
    lv_obj_set_size(slider_vol, 350, 20);
    lv_obj_align(slider_vol, LV_ALIGN_TOP_MID, 0, 120);
    lv_slider_set_range(slider_vol, 0, 100);
    lv_slider_set_value(slider_vol, 50, LV_ANIM_OFF);
    lv_obj_set_style_bg_color(slider_vol, lv_color_hex(0x66BB6A), LV_PART_INDICATOR);
    
    lv_obj_add_event_cb(slider_vol, [](lv_event_t *e) {
        lv_obj_t *slider = lv_event_get_target(e);
        int val = lv_slider_get_value(slider);
        lv_obj_t *label = (lv_obj_t*)lv_event_get_user_data(e);
        if (label) {
            lv_label_set_text_fmt(label, "%d%%", val);
        }
        Audio_Volume(val);
    }, LV_EVENT_VALUE_CHANGED, vol_val);
    
    // Playback controls
    int y_controls = 200;
    int btn_size = 70;
    
    // Previous
    lv_obj_t *btn_prev = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_prev, btn_size, btn_size);
    lv_obj_align(btn_prev, LV_ALIGN_TOP_LEFT, 40, y_controls);
    lv_obj_set_style_bg_color(btn_prev, lv_color_hex(0x388E3C), 0);
    
    lv_obj_t *l_prev = lv_label_create(btn_prev);
    lv_label_set_text(l_prev, LV_SYMBOL_PREV);
    lv_obj_set_style_text_font(l_prev, &lv_font_montserrat_28, 0);
    lv_obj_center(l_prev);
    
    // Play/Pause
    lv_obj_t *btn_play = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_play, btn_size, btn_size);
    lv_obj_align(btn_play, LV_ALIGN_TOP_MID, 0, y_controls);
    lv_obj_set_style_bg_color(btn_play, lv_color_hex(0x2E7D32), 0);
    
    lv_obj_t *l_play = lv_label_create(btn_play);
    lv_label_set_text(l_play, LV_SYMBOL_PLAY);
    lv_obj_set_style_text_font(l_play, &lv_font_montserrat_28, 0);
    lv_obj_center(l_play);
    
    // Next
    lv_obj_t *btn_next = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_next, btn_size, btn_size);
    lv_obj_align(btn_next, LV_ALIGN_TOP_RIGHT, -40, y_controls);
    lv_obj_set_style_bg_color(btn_next, lv_color_hex(0x388E3C), 0);
    
    lv_obj_t *l_next = lv_label_create(btn_next);
    lv_label_set_text(l_next, LV_SYMBOL_NEXT);
    lv_obj_set_style_text_font(l_next, &lv_font_montserrat_28, 0);
    lv_obj_center(l_next);
    
    // Now playing
    lv_obj_t *np_label = lv_label_create(lv_scr_act());
    lv_label_set_text(np_label, "No audio playing");
    lv_obj_set_style_text_color(np_label, lv_color_hex(0xA5D6A7), 0);
    lv_obj_set_style_text_font(np_label, &lv_font_montserrat_16, 0);
    lv_obj_align(np_label, LV_ALIGN_BOTTOM_MID, 0, -70);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 160, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
}

void recording_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x880E4F), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_STOP " Recording");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_26, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 30);
    
    // Status
    lv_obj_t *status = lv_label_create(lv_scr_act());
    lv_label_set_text(status, "Ready to record");
    lv_obj_set_style_text_color(status, lv_color_white(), 0);
    lv_obj_set_style_text_font(status, &lv_font_montserrat_18, 0);
    lv_obj_align(status, LV_ALIGN_TOP_MID, 0, 90);
    
    // Record button (large)
    lv_obj_t *btn_rec = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_rec, 120, 120);
    lv_obj_align(btn_rec, LV_ALIGN_CENTER, 0, 0);
    lv_obj_set_style_bg_color(btn_rec, lv_color_hex(0xE53935), 0);
    lv_obj_set_style_radius(btn_rec, LV_RADIUS_CIRCLE, 0);
    
    lv_obj_t *l_rec = lv_label_create(btn_rec);
    lv_label_set_text(l_rec, LV_SYMBOL_STOP);
    lv_obj_set_style_text_font(l_rec, &lv_font_montserrat_48, 0);
    lv_obj_center(l_rec);
    
    // Fixed: use global variable instead of heap allocation
    lv_obj_add_event_cb(btn_rec, [](lv_event_t *e) {
        lv_obj_t *status = (lv_obj_t*)lv_event_get_user_data(e);
        recording_active = !recording_active;
        
        if (recording_active) {
            if (status) {
                lv_label_set_text(status, LV_SYMBOL_STOP " Recording...");
            }
            MIC_Start();
            // RED pulsing ring while recording
            set_status_ring(0xFF0000, true);
        } else {
            if (status) {
                lv_label_set_text(status, "Recording saved to SD");
            }
            MIC_Stop();
            // GREEN flash then remove
            set_status_ring(0x00FF00, false);
            lv_task_handler();
            delay(800);
            remove_status_ring();
        }
        lv_task_handler();
    }, LV_EVENT_CLICKED, status);
    
    // Recording time
    lv_obj_t *time_label = lv_label_create(lv_scr_act());
    lv_label_set_text(time_label, "00:00");
    lv_obj_set_style_text_color(time_label, lv_color_white(), 0);
    lv_obj_set_style_text_font(time_label, &lv_font_montserrat_32, 0);
    lv_obj_align(time_label, LV_ALIGN_BOTTOM_MID, 0, -80);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 160, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
}

void system_info_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x4A148C), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_SETTINGS " System Info");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_24, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    int y = 70;
    int s = 28;
    
    // Chip
    lv_obj_t *l1 = lv_label_create(lv_scr_act());
    lv_label_set_text(l1, "ESP32-S3 @ 240MHz");
    lv_obj_set_style_text_color(l1, lv_color_white(), 0);
    lv_obj_set_style_text_font(l1, &lv_font_montserrat_18, 0);
    lv_obj_align(l1, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // RAM
    lv_obj_t *l2 = lv_label_create(lv_scr_act());
    uint32_t free_heap = ESP.getFreeHeap() / 1024;
    lv_label_set_text_fmt(l2, "Free RAM: %luKB", free_heap);
    lv_obj_set_style_text_color(l2, lv_color_hex(0xCE93D8), 0);
    lv_obj_set_style_text_font(l2, &lv_font_montserrat_16, 0);
    lv_obj_align(l2, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // PSRAM
    lv_obj_t *l3 = lv_label_create(lv_scr_act());
    lv_label_set_text(l3, "PSRAM: 8MB");
    lv_obj_set_style_text_color(l3, lv_color_hex(0xCE93D8), 0);
    lv_obj_set_style_text_font(l3, &lv_font_montserrat_16, 0);
    lv_obj_align(l3, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // Flash
    lv_obj_t *l4 = lv_label_create(lv_scr_act());
    lv_label_set_text(l4, "Flash: 16MB");
    lv_obj_set_style_text_color(l4, lv_color_hex(0xCE93D8), 0);
    lv_obj_set_style_text_font(l4, &lv_font_montserrat_16, 0);
    lv_obj_align(l4, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // Display
    lv_obj_t *l5 = lv_label_create(lv_scr_act());
    lv_label_set_text(l5, "Display: 412x412 QSPI");
    lv_obj_set_style_text_color(l5, lv_color_hex(0xCE93D8), 0);
    lv_obj_set_style_text_font(l5, &lv_font_montserrat_16, 0);
    lv_obj_align(l5, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // SD Card
    lv_obj_t *l6 = lv_label_create(lv_scr_act());
    uint64_t sd_total = SD.totalBytes() / (1024 * 1024 * 1024);
    lv_label_set_text_fmt(l6, "SD Card: %lluGB", sd_total);
    lv_obj_set_style_text_color(l6, lv_color_hex(0xAED581), 0);
    lv_obj_set_style_text_font(l6, &lv_font_montserrat_16, 0);
    lv_obj_align(l6, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // Battery
    lv_obj_t *l7 = lv_label_create(lv_scr_act());
    float voltage = BAT_Get_Volts();
    lv_label_set_text_fmt(l7, "Battery: %.2fV", voltage);
    lv_obj_set_style_text_color(l7, lv_color_hex(0xFFB74D), 0);
    lv_obj_set_style_text_font(l7, &lv_font_montserrat_16, 0);
    lv_obj_align(l7, LV_ALIGN_TOP_MID, 0, y);
    y += s;
    
    // WiFi
    lv_obj_t *l8 = lv_label_create(lv_scr_act());
    if (WiFi.status() == WL_CONNECTED) {
        lv_label_set_text_fmt(l8, "WiFi: %s\nIP: %s", 
            WiFi.SSID().c_str(), 
            WiFi.localIP().toString().c_str());
    } else {
        lv_label_set_text(l8, "WiFi: Disconnected");
    }
    lv_obj_set_style_text_color(l8, lv_color_hex(0x81C784), 0);
    lv_obj_set_style_text_font(l8, &lv_font_montserrat_14, 0);
    lv_obj_align(l8, LV_ALIGN_TOP_MID, 0, y);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 160, 45);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back = lv_label_create(btn_back);
    lv_label_set_text(l_back, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back);
    
    lv_task_handler();
}

void settings_screen() {
    safe_screen_clean();
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x37474F), 0);
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_SETTINGS " Settings");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_24, 0);
    lv_obj_set_style_text_color(title, lv_color_white(), 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 15);
    
    int y = 70;
    
    // Brightness control
    lv_obj_t *bright_label = lv_label_create(lv_scr_act());
    lv_label_set_text(bright_label, "Brightness");
    lv_obj_set_style_text_color(bright_label, lv_color_white(), 0);
    lv_obj_align(bright_label, LV_ALIGN_TOP_LEFT, 20, y);
    
    lv_obj_t *bright_val = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(bright_val, "%d%%", config.brightness);
    lv_obj_set_style_text_color(bright_val, lv_color_hex(0xFFD54F), 0);
    lv_obj_align(bright_val, LV_ALIGN_TOP_RIGHT, -20, y);
    
    lv_obj_t *slider_bright = lv_slider_create(lv_scr_act());
    lv_obj_set_size(slider_bright, 350, 15);
    lv_obj_align(slider_bright, LV_ALIGN_TOP_MID, 0, y + 35);
    lv_slider_set_range(slider_bright, 10, 100);
    lv_slider_set_value(slider_bright, config.brightness, LV_ANIM_OFF);
    lv_obj_add_event_cb(slider_bright, [](lv_event_t *e) {
        int val = lv_slider_get_value((lv_obj_t*)lv_event_get_target(e));
        lv_obj_t *label = (lv_obj_t*)lv_event_get_user_data(e);
        if (label) {
            lv_label_set_text_fmt(label, "%d%%", val);
        }
        config.brightness = val;
        Set_Backlight(val);
    }, LV_EVENT_VALUE_CHANGED, bright_val);
    y += 75;
    
    // Volume control
    lv_obj_t *vol_label = lv_label_create(lv_scr_act());
    lv_label_set_text(vol_label, "Volume");
    lv_obj_set_style_text_color(vol_label, lv_color_white(), 0);
    lv_obj_align(vol_label, LV_ALIGN_TOP_LEFT, 20, y);
    
    lv_obj_t *vol_val = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(vol_val, "%d%%", config.volume);
    lv_obj_set_style_text_color(vol_val, lv_color_hex(0xFFD54F), 0);
    lv_obj_align(vol_val, LV_ALIGN_TOP_RIGHT, -20, y);
    
    lv_obj_t *slider_vol = lv_slider_create(lv_scr_act());
    lv_obj_set_size(slider_vol, 350, 15);
    lv_obj_align(slider_vol, LV_ALIGN_TOP_MID, 0, y + 35);
    lv_slider_set_range(slider_vol, 0, 100);
    lv_slider_set_value(slider_vol, config.volume, LV_ANIM_OFF);
    lv_obj_add_event_cb(slider_vol, [](lv_event_t *e) {
        int val = lv_slider_get_value((lv_obj_t*)lv_event_get_target(e));
        lv_obj_t *label = (lv_obj_t*)lv_event_get_user_data(e);
        if (label) {
            lv_label_set_text_fmt(label, "%d%%", val);
        }
        config.volume = val;
        // Audio_Volume(val);  // Disabled - audio library issue
    }, LV_EVENT_VALUE_CHANGED, vol_val);
    y += 75;
    
    // Auto-connect WiFi toggle
    lv_obj_t *wifi_label = lv_label_create(lv_scr_act());
    lv_label_set_text(wifi_label, "Auto-connect WiFi");
    lv_obj_set_style_text_color(wifi_label, lv_color_white(), 0);
    lv_obj_align(wifi_label, LV_ALIGN_TOP_LEFT, 20, y);
    
    lv_obj_t *wifi_switch = lv_switch_create(lv_scr_act());
    lv_obj_align(wifi_switch, LV_ALIGN_TOP_RIGHT, -20, y - 5);
    if (config.auto_connect_wifi) {
        lv_obj_add_state(wifi_switch, LV_STATE_CHECKED);
    }
    lv_obj_add_event_cb(wifi_switch, [](lv_event_t *e) {
        lv_obj_t *sw = (lv_obj_t*)lv_event_get_target(e);
        config.auto_connect_wifi = lv_obj_has_state(sw, LV_STATE_CHECKED);
    }, LV_EVENT_VALUE_CHANGED, NULL);
    y += 60;
    
    // Info text
    lv_obj_t *info = lv_label_create(lv_scr_act());
    lv_label_set_text(info, "Settings saved to SD card\nEdit /config.json for more options");
    lv_obj_set_style_text_color(info, lv_color_hex(0x90A4AE), 0);
    lv_obj_set_style_text_font(info, &lv_font_montserrat_12, 0);
    lv_obj_set_style_text_align(info, LV_TEXT_ALIGN_CENTER, 0);
    lv_obj_align(info, LV_ALIGN_TOP_MID, 0, y);
    
    // Save button
    lv_obj_t *btn_save = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_save, 180, 50);
    lv_obj_align(btn_save, LV_ALIGN_BOTTOM_LEFT, 10, -10);
    lv_obj_set_style_bg_color(btn_save, lv_color_hex(0x43A047), 0);
    lv_obj_add_event_cb(btn_save, [](lv_event_t *e) {
        save_config();
        set_status_ring(0x00FF00, false);
        lv_task_handler();
        delay(800);
        remove_status_ring();
    }, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_save = lv_label_create(btn_save);
    lv_label_set_text(l_save, LV_SYMBOL_SAVE " Save");
    lv_obj_set_style_text_font(l_save, &lv_font_montserrat_18, 0);
    lv_obj_center(l_save);
    
    // Back button
    lv_obj_t *btn_back2 = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back2, 180, 50);
    lv_obj_align(btn_back2, LV_ALIGN_BOTTOM_RIGHT, -10, -10);
    lv_obj_set_style_bg_color(btn_back2, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back2, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *l_back2 = lv_label_create(btn_back2);
    lv_label_set_text(l_back2, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(l_back2, &lv_font_montserrat_18, 0);
    lv_obj_center(l_back2);
    
    lv_task_handler();
}
