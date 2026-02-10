#!/usr/bin/env python3
"""Convert icon.png to C array for embedding in firmware"""

from PIL import Image

img = Image.open('src/icon.png')
# Convert RGBA to RGB, compositing transparent areas onto black background
if img.mode == 'RGBA':
    background = Image.new('RGB', img.size, (0, 0, 0))  # Black background
    background.paste(img, mask=img.split()[3])  # Use alpha channel as mask
    img = background
else:
    img = img.convert('RGB')
width, height = img.size

print(f"Converting {width}x{height} image to C array...")

with open('src/Custom_Menu/home_icon.c', 'w') as f:
    f.write('#include "lvgl.h"\n\n')
    f.write(f'// Converted from icon_128.png - {width}x{height}\n')
    f.write('const LV_ATTRIBUTE_MEM_ALIGN uint16_t home_icon_map[] = {\n')
    
    for y in range(height):
        f.write('  ')
        for x in range(width):
            r, g, b = img.getpixel((x, y))
            # RGB565 with byte swap for little-endian ESP32
            rgb565 = ((r & 0xF8) << 8) | ((g & 0xFC) << 3) | (b >> 3)
            # Swap bytes: convert big-endian to little-endian
            rgb565_swapped = ((rgb565 & 0xFF) << 8) | ((rgb565 >> 8) & 0xFF)
            f.write(f'0x{rgb565_swapped:04x},')
        f.write('\n')
    
    f.write('};\n\n')
    f.write('const lv_img_dsc_t home_icon = {\n')
    f.write('  .header.always_zero = 0,\n')
    f.write(f'  .header.w = {width},\n')
    f.write(f'  .header.h = {height},\n')
    f.write(f'  .data_size = {width * height * 2},\n')
    f.write('  .header.cf = LV_IMG_CF_TRUE_COLOR,\n')
    f.write('  .data = (const uint8_t *)home_icon_map,\n')
    f.write('};\n')

with open('src/Custom_Menu/home_icon.h', 'w') as f:
    f.write('#ifndef HOME_ICON_H\n')
    f.write('#define HOME_ICON_H\n\n')
    f.write('#include "lvgl.h"\n\n')
    f.write('extern const lv_img_dsc_t home_icon;\n\n')
    f.write('#endif\n')

print(f"Created home_icon.c and home_icon.h")
print(f"Size: {width * height * 2} bytes ({width * height * 2 / 1024:.1f} KB)")
