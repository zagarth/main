#!/usr/bin/env python3
"""
Convert PNG to C array for embedding in firmware
Uses RGB565 format (2 bytes per pixel)
"""

from PIL import Image

def convert_to_c_array(input_path, output_path, var_name):
    """Convert PNG to C array"""
    img = Image.open(input_path)
    img = img.convert('RGB')
    
    width, height = img.size
    
    with open(output_path, 'w') as f:
        f.write(f'// Auto-generated from {input_path}\n')
        f.write(f'#include "lvgl.h"\n\n')
        f.write(f'const uint16_t {var_name}_data[] = {{\n')
        
        # Write pixel data in RGB565
        for y in range(height):
            f.write('    ')
            for x in range(width):
                r, g, b = img.getpixel((x, y))
                # Convert to RGB565
                rgb565 = ((r & 0xF8) << 8) | ((g & 0xFC) << 3) | (b >> 3)
                f.write(f'0x{rgb565:04x}, ')
            f.write(f'// Row {y}\n')
        
        f.write('};\n\n')
        f.write(f'const lv_img_dsc_t {var_name} = {{\n')
        f.write(f'    .header.always_zero = 0,\n')
        f.write(f'    .header.w = {width},\n')
        f.write(f'    .header.h = {height},\n')
        f.write(f'    .data_size = {width * height * 2},\n')
        f.write(f'    .header.cf = LV_IMG_CF_TRUE_COLOR,\n')
        f.write(f'    .data = (const uint8_t *){var_name}_data,\n')
        f.write('};\n')
    
    print(f"Converted {input_path} -> {output_path}")
    print(f"Size: {width}x{height}")
    array_size = width * height * 2
    print(f"Array size: {array_size} bytes ({array_size/1024:.1f} KB)")

if __name__ == '__main__':
    # Convert icon
    convert_to_c_array('src/icon_128.png', 'src/icon_image.c', 'icon_image')
