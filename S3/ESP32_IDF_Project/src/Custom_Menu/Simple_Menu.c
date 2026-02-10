/**
 * Simple Menu - Clean working version
 * Based on Waveshare patterns that actually work
 */

#include "Custom_Menu.h"
#include "lvgl.h"
#include "SD_Card.h"
#include "BAT_Driver.h"
#include "WIFI_Driver.h"
#include "BLE_Driver.h"

static const char *TAG = "SimpleMenu";

// Forward declarations - Waveshare style
static void btn_wifi_cb(lv_event_t *e);
static void btn_ble_cb(lv_event_t *e);
static void btn_sys_cb(lv_event_t *e);
static void btn_back_cb(lv_event_t *e);

// Screens
static void create_main_menu(void);
static void create_wifi_screen(void);
static void create_ble_screen(void);
static void create_sys_screen(void);

// Initialize
void Custom_Menu_Init(void) {
    ESP_LOGI(TAG, "Init");
    create_main_menu();
}

// Main menu with 3 buttons
static void create_main_menu(void) {
    lv_obj_clean(lv_scr_act());
    
    // Title
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, "Control Center");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_24, 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 20);
    
    // WiFi button
    lv_obj_t *btn1 = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn1, 350, 60);
    lv_obj_align(btn1, LV_ALIGN_CENTER, 0, -80);
    lv_obj_set_style_bg_color(btn1, lv_color_hex(0x1976D2), 0);
    lv_obj_add_event_cb(btn1, btn_wifi_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label1 = lv_label_create(btn1);
    lv_label_set_text(label1, LV_SYMBOL_WIFI "  WiFi");
    lv_obj_set_style_text_font(label1, &lv_font_montserrat_20, 0);
    lv_obj_center(label1);
    
    // Bluetooth button
    lv_obj_t *btn2 = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn2, 350, 60);
    lv_obj_align(btn2, LV_ALIGN_CENTER, 0, 0);
    lv_obj_set_style_bg_color(btn2, lv_color_hex(0x388E3C), 0);
    lv_obj_add_event_cb(btn2, btn_ble_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label2 = lv_label_create(btn2);
    lv_label_set_text(label2, LV_SYMBOL_BLUETOOTH "  Bluetooth");
    lv_obj_set_style_text_font(label2, &lv_font_montserrat_20, 0);
    lv_obj_center(label2);
    
    // System Info button
    lv_obj_t *btn3 = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn3, 350, 60);
    lv_obj_align(btn3, LV_ALIGN_CENTER, 0, 80);
    lv_obj_set_style_bg_color(btn3, lv_color_hex(0x7B1FA2), 0);
    lv_obj_add_event_cb(btn3, btn_sys_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label3 = lv_label_create(btn3);
    lv_label_set_text(label3, LV_SYMBOL_SETTINGS "  System Info");
    lv_obj_set_style_text_font(label3, &lv_font_montserrat_20, 0);
    lv_obj_center(label3);
    
    ESP_LOGI(TAG, "Main menu ready");
}

// WiFi screen
static void create_wifi_screen(void) {
    lv_obj_clean(lv_scr_act());
    
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_WIFI " WiFi Setup");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_22, 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 15);
    
    // Info
    lv_obj_t *info = lv_label_create(lv_scr_act());
    lv_label_set_text(info, "WiFi functionality here");
    lv_obj_align(info, LV_ALIGN_CENTER, 0, 0);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(label, &lv_font_montserrat_18, 0);
    lv_obj_center(label);
}

// BLE screen
static void create_ble_screen(void) {
    lv_obj_clean(lv_scr_act());
    
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_BLUETOOTH " Bluetooth");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_22, 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 15);
    
    // Info
    lv_obj_t *info = lv_label_create(lv_scr_act());
    lv_label_set_text(info, "Bluetooth functionality here");
    lv_obj_align(info, LV_ALIGN_CENTER, 0, 0);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(label, &lv_font_montserrat_18, 0);
    lv_obj_center(label);
}

// System Info screen
static void create_sys_screen(void) {
    lv_obj_clean(lv_scr_act());
    
    lv_obj_t *title = lv_label_create(lv_scr_act());
    lv_label_set_text(title, LV_SYMBOL_SETTINGS " System Info");
    lv_obj_set_style_text_font(title, &lv_font_montserrat_22, 0);
    lv_obj_align(title, LV_ALIGN_TOP_MID, 0, 15);
    
    // Battery
    float volts = BAT_Get_Volts() / 1000.0f;
    lv_obj_t *bat = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(bat, "Battery: %.2fV", volts);
    lv_obj_align(bat, LV_ALIGN_CENTER, 0, -20);
    
    // SD Card
    uint64_t sd_mb = SD_Total() / (1024 * 1024);
    lv_obj_t *sd = lv_label_create(lv_scr_act());
    lv_label_set_text_fmt(sd, "SD Card: %lluMB", sd_mb);
    lv_obj_align(sd, LV_ALIGN_CENTER, 0, 20);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 200, 50);
    lv_obj_align(btn_back, LV_ALIGN_BOTTOM_MID, 0, -10);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x424242), 0);
    lv_obj_add_event_cb(btn_back, btn_back_cb, LV_EVENT_ALL, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, LV_SYMBOL_LEFT " Back");
    lv_obj_set_style_text_font(label, &lv_font_montserrat_18, 0);
    lv_obj_center(label);
}

// Callbacks - with event filtering like Waveshare
static void btn_wifi_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "WiFi button pressed");
    create_wifi_screen();
}

static void btn_ble_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "BLE button pressed");
    create_ble_screen();
}

static void btn_sys_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "System button pressed");
    create_sys_screen();
}

static void btn_back_cb(lv_event_t *e) {
    lv_event_code_t code = lv_event_get_code(e);
    if (code != LV_EVENT_CLICKED) return;
    
    ESP_LOGI(TAG, "Back button pressed");
    create_main_menu();
}
