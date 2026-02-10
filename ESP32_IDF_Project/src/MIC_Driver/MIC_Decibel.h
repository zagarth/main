#ifndef MIC_DECIBEL_H
#define MIC_DECIBEL_H

#include <stdint.h>
#include <stdbool.h>

// Initialize decibel monitoring system
void MIC_Decibel_init(void);

// Start/stop decibel monitoring
void MIC_Decibel_start(void);
void MIC_Decibel_stop(void);

// Get current decibel level
float MIC_Decibel_get_level(void);

// Enable/disable warning system
void MIC_Decibel_set_warning_enabled(bool enabled);
bool MIC_Decibel_is_warning_enabled(void);

// Check if warning is currently active
bool MIC_Decibel_is_warning_active(void);

// Deinitialize
void MIC_Decibel_deinit(void);

#endif // MIC_DECIBEL_H
