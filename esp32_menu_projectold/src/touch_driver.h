/**
 * Touch Driver for CST816S Touch Controller
 * Waveshare ESP32-S3-Touch-LCD-1.46B
 */

#ifndef TOUCH_DRIVER_H
#define TOUCH_DRIVER_H

#include <Arduino.h>
#include <lvgl.h>

void touch_init();
void touch_read(lv_indev_drv_t *indev, lv_indev_data_t *data);

#endif
