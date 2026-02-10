#ifndef MAPS_CONFIG_H
#define MAPS_CONFIG_H

#include <stdint.h>
#include <string.h>

// XOR obfuscated Google Maps API key
// Protects against casual code inspection
// Key rotation: ESP32_SECURE_2026
static const uint8_t OBFUSCATED_API_KEY[] = {
    0x04, 0x1A, 0x2A, 0x52, 0x61, 0x26, 0x12, 0x7D,
    0x1E, 0x16, 0x1B, 0x4C, 0x55, 0x62, 0x53, 0x43,
    0x0C, 0x62, 0x50, 0x12, 0x7D, 0x1B, 0x43, 0x3D,
    0x23, 0x48, 0x51, 0x62, 0x52, 0x1C, 0x74, 0x43,
    0x1A, 0x48, 0x51, 0x62, 0x52, 0x1C, 0x62, 0x49
};

static const char XOR_KEY[] = "ESP32_SECURE_2026";

// Decode API key at runtime
static inline void decode_api_key(char *output, size_t max_len) {
    size_t key_len = strlen(XOR_KEY);
    size_t api_len = sizeof(OBFUSCATED_API_KEY);
    
    if (max_len < api_len + 1) return;
    
    for (size_t i = 0; i < api_len; i++) {
        output[i] = OBFUSCATED_API_KEY[i] ^ XOR_KEY[i % key_len];
    }
    output[api_len] = '\0';
}

#endif // MAPS_CONFIG_H
