/**
 * LVGL Configuration for ESP32-S3 Waveshare 1.46B
 */

#define LV_COLOR_DEPTH 16
#define LV_COLOR_16_SWAP 0

/* Memory settings */
#define LV_MEM_CUSTOM 0
#define LV_MEM_SIZE (128U * 1024U)

/* Display settings */
#define LV_HOR_RES_MAX 412
#define LV_VER_RES_MAX 412
#define LV_DPI_DEF 160

/* Performance */
#define LV_DISP_DEF_REFR_PERIOD 30
#define LV_INDEV_DEF_READ_PERIOD 30

/* Features */
#define LV_USE_PERF_MONITOR 1
#define LV_USE_MEM_MONITOR 0
#define LV_USE_LOG 0

/* Font support */
#define LV_FONT_MONTSERRAT_14 1
#define LV_FONT_MONTSERRAT_16 1
#define LV_FONT_MONTSERRAT_20 1
#define LV_FONT_MONTSERRAT_24 1
#define LV_FONT_MONTSERRAT_32 1

/* Widgets */
#define LV_USE_BTN 1
#define LV_USE_LABEL 1
#define LV_USE_LIST 1
#define LV_USE_SLIDER 1
#define LV_USE_ARC 1

/* Themes */
#define LV_USE_THEME_DEFAULT 1
#define LV_THEME_DEFAULT_DARK 0
