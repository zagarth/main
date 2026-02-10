/**
 * Custom Touch Menu for ESP32-S3 Waveshare 1.46B
 * 
 * Hardware:
 * - 412x412 QSPI Display (SPD2010)
 * - CST816S Touch Controller
 * - 8MB PSRAM
 */

#include <Arduino.h>
#include <lvgl.h>
#include <Wire.h>
#include "display_driver.h"
#include "touch_driver.h"

// Display and touch objects
static lv_disp_draw_buf_t draw_buf;
static lv_color_t buf1[412 * 50];
static lv_color_t buf2[412 * 50];
static lv_disp_drv_t disp_drv;
static lv_indev_drv_t indev_drv;

// Menu objects
lv_obj_t *screen_main;
lv_obj_t *menu_list;
lv_obj_t *title_label;

// Forward declarations
void create_main_menu();
void demo_colors_screen();
void demo_patterns_screen();
void demo_info_screen();

void setup() {
    Serial.begin(115200);
    Serial.println("ESP32-S3 Custom Menu Starting...");
    
    // Initialize LVGL
    lv_init();
    
    // Initialize display
    display_init();
    
    // Setup LVGL display driver
    lv_disp_draw_buf_init(&draw_buf, buf1, buf2, 412 * 50);
    
    lv_disp_drv_init(&disp_drv);
    disp_drv.hor_res = 412;
    disp_drv.ver_res = 412;
    disp_drv.flush_cb = display_flush;
    disp_drv.draw_buf = &draw_buf;
    lv_disp_drv_register(&disp_drv);
    
    // Initialize touch
    touch_init();
    
    // Setup LVGL input device driver
    lv_indev_drv_init(&indev_drv);
    indev_drv.type = LV_INDEV_TYPE_POINTER;
    indev_drv.read_cb = touch_read;
    lv_indev_drv_register(&indev_drv);
    
    // Create main menu
    create_main_menu();
    
    Serial.println("Menu Ready!");
}

void loop() {
    lv_timer_handler();
    delay(5);
}

// Menu button callbacks
void btn_colors_clicked(lv_event_t *e) {
    Serial.println("Colors demo selected");
    demo_colors_screen();
}

void btn_patterns_clicked(lv_event_t *e) {
    Serial.println("Patterns demo selected");
    demo_patterns_screen();
}

void btn_info_clicked(lv_event_t *e) {
    Serial.println("Info screen selected");
    demo_info_screen();
}

void btn_back_clicked(lv_event_t *e) {
    Serial.println("Back to menu");
    create_main_menu();
}

void create_main_menu() {
    // Clear screen
    lv_obj_clean(lv_scr_act());
    
    // Create title bar
    lv_obj_t *title_bar = lv_obj_create(lv_scr_act());
    lv_obj_set_size(title_bar, 412, 60);
    lv_obj_set_pos(title_bar, 0, 0);
    lv_obj_set_style_bg_color(title_bar, lv_color_hex(0x1565C0), 0);
    lv_obj_set_style_border_width(title_bar, 0, 0);
    lv_obj_set_style_radius(title_bar, 0, 0);
    
    title_label = lv_label_create(title_bar);
    lv_label_set_text(title_label, "MAIN MENU");
    lv_obj_set_style_text_font(title_label, &lv_font_montserrat_24, 0);
    lv_obj_set_style_text_color(title_label, lv_color_white(), 0);
    lv_obj_center(title_label);
    
    // Create menu buttons
    int y_pos = 80;
    int btn_height = 70;
    int spacing = 15;
    
    // Colors button
    lv_obj_t *btn_colors = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_colors, 360, btn_height);
    lv_obj_set_pos(btn_colors, 26, y_pos);
    lv_obj_set_style_bg_color(btn_colors, lv_color_hex(0x42A5F5), 0);
    lv_obj_add_event_cb(btn_colors, btn_colors_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label_colors = lv_label_create(btn_colors);
    lv_label_set_text(label_colors, "Color Demo");
    lv_obj_set_style_text_font(label_colors, &lv_font_montserrat_20, 0);
    lv_obj_center(label_colors);
    
    y_pos += btn_height + spacing;
    
    // Patterns button
    lv_obj_t *btn_patterns = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_patterns, 360, btn_height);
    lv_obj_set_pos(btn_patterns, 26, y_pos);
    lv_obj_set_style_bg_color(btn_patterns, lv_color_hex(0x66BB6A), 0);
    lv_obj_add_event_cb(btn_patterns, btn_patterns_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label_patterns = lv_label_create(btn_patterns);
    lv_label_set_text(label_patterns, "Pattern Demo");
    lv_obj_set_style_text_font(label_patterns, &lv_font_montserrat_20, 0);
    lv_obj_center(label_patterns);
    
    y_pos += btn_height + spacing;
    
    // Info button
    lv_obj_t *btn_info = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_info, 360, btn_height);
    lv_obj_set_pos(btn_info, 26, y_pos);
    lv_obj_set_style_bg_color(btn_info, lv_color_hex(0xFFA726), 0);
    lv_obj_add_event_cb(btn_info, btn_info_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label_info = lv_label_create(btn_info);
    lv_label_set_text(label_info, "System Info");
    lv_obj_set_style_text_font(label_info, &lv_font_montserrat_20, 0);
    lv_obj_center(label_info);
    
    // Footer
    lv_obj_t *footer = lv_label_create(lv_scr_act());
    lv_label_set_text(footer, "Touch to select");
    lv_obj_set_style_text_color(footer, lv_color_hex(0x888888), 0);
    lv_obj_set_pos(footer, 130, 380);
}

void demo_colors_screen() {
    lv_obj_clean(lv_scr_act());
    
    // Create colored sections
    int section_height = 82;
    const lv_color_t colors[] = {
        lv_color_hex(0xF44336), // Red
        lv_color_hex(0x4CAF50), // Green  
        lv_color_hex(0x2196F3), // Blue
        lv_color_hex(0xFFEB3B), // Yellow
        lv_color_hex(0x9C27B0)  // Purple
    };
    
    for(int i = 0; i < 5; i++) {
        lv_obj_t *section = lv_obj_create(lv_scr_act());
        lv_obj_set_size(section, 412, section_height);
        lv_obj_set_pos(section, 0, i * section_height);
        lv_obj_set_style_bg_color(section, colors[i], 0);
        lv_obj_set_style_border_width(section, 0, 0);
        lv_obj_set_style_radius(section, 0, 0);
    }
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 100, 50);
    lv_obj_set_pos(btn_back, 156, 350);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, "BACK");
    lv_obj_center(label);
}

void demo_patterns_screen() {
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_black(), 0);
    
    // Create pattern with circles
    for(int i = 0; i < 8; i++) {
        for(int j = 0; j < 8; j++) {
            lv_obj_t *circle = lv_obj_create(lv_scr_act());
            lv_obj_set_size(circle, 40, 40);
            lv_obj_set_pos(circle, j * 50 + 6, i * 50 + 6);
            lv_obj_set_style_radius(circle, LV_RADIUS_CIRCLE, 0);
            lv_obj_set_style_bg_color(circle, lv_color_hex(0x00BCD4), 0);
            lv_obj_set_style_border_width(circle, 0, 0);
        }
    }
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 100, 50);
    lv_obj_set_pos(btn_back, 156, 350);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, "BACK");
    lv_obj_center(label);
}

void demo_info_screen() {
    lv_obj_clean(lv_scr_act());
    lv_obj_set_style_bg_color(lv_scr_act(), lv_color_hex(0x212121), 0);
    
    // Info labels
    lv_obj_t *label_title = lv_label_create(lv_scr_act());
    lv_label_set_text(label_title, "ESP32-S3");
    lv_obj_set_style_text_font(label_title, &lv_font_montserrat_32, 0);
    lv_obj_set_style_text_color(label_title, lv_color_white(), 0);
    lv_obj_set_pos(label_title, 120, 80);
    
    lv_obj_t *label_board = lv_label_create(lv_scr_act());
    lv_label_set_text(label_board, "Waveshare 1.46B");
    lv_obj_set_style_text_font(label_board, &lv_font_montserrat_20, 0);
    lv_obj_set_style_text_color(label_board, lv_color_hex(0xBBBBBB), 0);
    lv_obj_set_pos(label_board, 100, 130);
    
    lv_obj_t *label_display = lv_label_create(lv_scr_act());
    lv_label_set_text(label_display, "412x412 QSPI Display");
    lv_obj_set_style_text_color(label_display, lv_color_hex(0xBBBBBB), 0);
    lv_obj_set_pos(label_display, 85, 170);
    
    lv_obj_t *label_ram = lv_label_create(lv_scr_act());
    lv_label_set_text(label_ram, "8MB PSRAM");
    lv_obj_set_style_text_color(label_ram, lv_color_hex(0xBBBBBB), 0);
    lv_obj_set_pos(label_ram, 140, 210);
    
    lv_obj_t *label_touch = lv_label_create(lv_scr_act());
    lv_label_set_text(label_touch, "CST816S Touch");
    lv_obj_set_style_text_color(label_touch, lv_color_hex(0xBBBBBB), 0);
    lv_obj_set_pos(label_touch, 120, 250);
    
    // Back button
    lv_obj_t *btn_back = lv_btn_create(lv_scr_act());
    lv_obj_set_size(btn_back, 100, 50);
    lv_obj_set_pos(btn_back, 156, 350);
    lv_obj_set_style_bg_color(btn_back, lv_color_hex(0x1976D2), 0);
    lv_obj_add_event_cb(btn_back, btn_back_clicked, LV_EVENT_CLICKED, NULL);
    
    lv_obj_t *label = lv_label_create(btn_back);
    lv_label_set_text(label, "BACK");
    lv_obj_center(label);
}
