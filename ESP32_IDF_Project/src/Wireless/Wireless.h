#pragma once

#include <stdio.h>
#include <string.h>
#include <stdint.h>
#include <stdbool.h>
#include "esp_log.h"
#include "freertos/FreeRTOS.h"
#include "esp_wifi.h"
#include "nvs_flash.h"
#include "esp_system.h"
#include "esp_bt.h"
#include "esp_gap_ble_api.h"
#include "esp_bt_main.h"

// Device structure for BLE scanning
typedef struct {
    uint8_t address[6];
    char name[100];
    int8_t rssi;
    bool is_valid;
} discovered_device_t;

extern uint16_t BLE_NUM;
extern uint16_t WIFI_NUM;
extern bool Scan_finish;

void Wireless_Init(void);
void WIFI_Init(void *arg);
uint16_t WIFI_Scan(void);
void BLE_Init(void *arg);
uint16_t BLE_Scan(void);
void BLE_Get_Devices(discovered_device_t **devices, size_t *count);
void BLE_Clear_Devices(void);
bool BLE_Connect_First_Device(void);
const char* BLE_Get_Connected_Device_Name(void);
bool BLE_Is_Connected(void);
void BLE_Set_UI_Callback(void (*callback)(const char* status, const char* device_name));