/**
 * Display Driver for SPD2010 QSPI Display
 * Waveshare ESP32-S3-Touch-LCD-1.46B
 */

#ifndef DISPLAY_DRIVER_H
#define DISPLAY_DRIVER_H

#include <Arduino.h>
#include <lvgl.h>

void display_init();
void display_flush(lv_disp_drv_t *disp, const lv_area_t *area, lv_color_t *color_p);

#endif
