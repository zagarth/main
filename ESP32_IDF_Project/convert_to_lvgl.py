#!/usr/bin/env python3
"""
Convert PNG to LVGL binary format (.bin) for direct loading without decoder.
This ensures images will display even if PNG decoder has issues.
"""

from PIL import Image
import struct

def convert_to_lvgl_bin(input_path, output_path):
    """Convert PNG to LVGL binary format (RGB565)"""
    img = Image.open(input_path)
    img = img.convert('RGB')  # Ensure RGB format
    
    width, height = img.size
    
    # Create binary file
    with open(output_path, 'wb') as f:
        # Write LVGL header (color format = RGB565 = 4)
        f.write(struct.pack('<I', 4))  # CF_TRUE_COLOR_565
        f.write(struct.pack('<I', width))
        f.write(struct.pack('<I', height))
        
        # Write pixel data (RGB565 format)
        for y in range(height):
            for x in range(width):
                r, g, b = img.getpixel((x, y))
                # Convert to RGB565
                rgb565 = ((r & 0xF8) << 8) | ((g & 0xFC) << 3) | (b >> 3)
                f.write(struct.pack('<H', rgb565))
    
    print(f"Converted {input_path} -> {output_path}")
    print(f"Size: {width}x{height}, Format: RGB565")

if __name__ == '__main__':
    # Convert both images
    convert_to_lvgl_bin('src/icon_128.png', 'src/icon_128.bin')
    convert_to_lvgl_bin('src/tux_128.png', 'src/tux_128.bin')
