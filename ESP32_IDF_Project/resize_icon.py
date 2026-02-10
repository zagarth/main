#!/usr/bin/env python3
"""Resize icon.png to 128x128 for ESP32 memory constraints"""
from PIL import Image

# Open original 256x256 image
img = Image.open('src/icon.png')
print(f"Original size: {img.size}")

# Resize to 128x128
img_small = img.resize((128, 128), Image.Resampling.LANCZOS)

# Save
img_small.save('src/icon_128.png', optimize=True)
print(f"Saved icon_128.png: {img_small.size}")

# Show file sizes
import os
orig_size = os.path.getsize('src/icon.png')
new_size = os.path.getsize('src/icon_128.png')
print(f"Original: {orig_size:,} bytes")
print(f"Resized: {new_size:,} bytes")
print(f"Savings: {orig_size - new_size:,} bytes ({100*(orig_size-new_size)/orig_size:.1f}%)")
