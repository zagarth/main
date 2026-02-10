#!/usr/bin/env python3
"""
Create a simple 64x64 test icon as C array (smaller to fit in flash easily)
"""

from PIL import Image, ImageDraw

# Create simple 64x64 icon
size = 64
img = Image.new('RGB', (size, size), 'black')
draw = ImageDraw.Draw(img)

# Draw a simple smiley face
draw.ellipse([10, 10, 54, 54], fill='yellow', outline='orange')  # Face
draw.ellipse([20, 20, 28, 28], fill='black')  # Left eye
draw.ellipse([36, 20, 44, 28], fill='black')  # Right eye
draw.arc([20, 25, 44, 45], 0, 180, fill='black', width=3)  # Smile

# Convert to C array (RGB565)
with open('src/Custom_Menu/test_icon.c', 'w') as f:
    f.write('#include "lvgl.h"\n\n')
    f.write(f'// Simple {size}x{size} test icon\n')
    f.write('const LV_ATTRIBUTE_MEM_ALIGN uint16_t test_icon_map[] = {\n')
    
    for y in range(size):
        f.write('  ')
        for x in range(size):
            r, g, b = img.getpixel((x, y))
            # Convert to RGB565
            rgb565 = ((r & 0xF8) << 8) | ((g & 0xFC) << 3) | (b >> 3)
            f.write(f'0x{rgb565:04x}, ')
        f.write(f'\n')
    
    f.write('};\n\n')
    f.write('const lv_img_dsc_t test_icon = {\n')
    f.write('  .header.always_zero = 0,\n')
    f.write(f'  .header.w = {size},\n')
    f.write(f'  .header.h = {size},\n')
    f.write(f'  .data_size = {size * size * 2},\n')
    f.write('  .header.cf = LV_IMG_CF_TRUE_COLOR,\n')
    f.write('  .data = (const uint8_t *)test_icon_map,\n')
    f.write('};\n')

# Also create header file
with open('src/Custom_Menu/test_icon.h', 'w') as f:
    f.write('#ifndef TEST_ICON_H\n')
    f.write('#define TEST_ICON_H\n\n')
    f.write('#include "lvgl.h"\n\n')
    f.write('extern const lv_img_dsc_t test_icon;\n\n')
    f.write('#endif\n')

print(f"Created test_icon.c and test_icon.h ({size}x{size} = {size*size*2} bytes)")
