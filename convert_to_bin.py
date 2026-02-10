"""
Simple PNG to RGB565 binary converter for ESP32 Pin Mode
Usage: python convert_to_bin.py input.png output.bin
"""

import sys
import struct
from PIL import Image

def convert_png_to_rgb565(input_path, output_path):
    print(f"Loading image: {input_path}")
    img = Image.open(input_path)
    print(f"  Original size: {img.size}, Mode: {img.mode}")
    
    # Resize to 256x256
    if img.size != (256, 256):
        img = img.resize((256, 256), Image.Resampling.LANCZOS)
        print(f"  Resized to 256x256")
    
    # Handle transparency - composite onto black background
    if img.mode in ('RGBA', 'LA', 'P'):
        print(f"  Processing transparency...")
        background = Image.new('RGB', img.size, (0, 0, 0))
        if img.mode == 'P':
            img = img.convert('RGBA')
        background.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
        img = background
    elif img.mode != 'RGB':
        img = img.convert('RGB')
    
    # Convert to RGB565
    print(f"  Converting to RGB565...")
    pixels = img.load()
    rgb565_bytes = bytearray()
    
    for y in range(256):
        for x in range(256):
            r, g, b = pixels[x, y]
            
            # Convert to 5-6-5 bit format
            r5 = (r >> 3) & 0x1F
            g6 = (g >> 2) & 0x3F
            b5 = (b >> 3) & 0x1F
            
            # Pack into 16-bit value (RGB565)
            rgb565 = (r5 << 11) | (g6 << 5) | b5
            
            # Write as little-endian (ESP32 byte order)
            rgb565_bytes.extend(struct.pack('<H', rgb565))
    
    # Write binary file
    print(f"  Writing binary file: {output_path}")
    with open(output_path, 'wb') as f:
        f.write(bytes(rgb565_bytes))
    
    file_size = len(rgb565_bytes)
    print(f"✓ Success! Written {file_size} bytes ({file_size:,} bytes)")
    
    if file_size != 131072:
        print(f"  WARNING: Expected 131,072 bytes, got {file_size}")
    
    return file_size

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python convert_to_bin.py input.png output.bin")
        print("Example: python convert_to_bin.py my_image.png F:/home_icon.bin")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    try:
        convert_png_to_rgb565(input_file, output_file)
        print("\nFile ready to use on ESP32!")
    except Exception as e:
        print(f"ERROR: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
