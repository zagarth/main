"""
GIF to RGB565 binary converter for ESP32 animated images
Extracts all frames from a GIF and converts to RGB565 format

Usage: python convert_gif_to_bin.py input.gif output_prefix
       Creates: output_prefix_000.bin, output_prefix_001.bin, etc.
       Also creates: output_prefix_info.txt with frame timing info
"""

import sys
import struct
from PIL import Image

def convert_gif_to_rgb565_frames(input_path, output_prefix):
    """Convert GIF frames to RGB565 binary files"""
    print(f"Loading GIF: {input_path}")
    
    with Image.open(input_path) as gif:
        # Get GIF info
        n_frames = getattr(gif, 'n_frames', 1)
        print(f"  Frames: {n_frames}")
        print(f"  Original size: {gif.size}, Mode: {gif.mode}")
        
        # Get frame durations (in milliseconds)
        durations = []
        try:
            for frame_idx in range(n_frames):
                gif.seek(frame_idx)
                duration = gif.info.get('duration', 100)  # Default 100ms if not specified
                durations.append(duration)
        except EOFError:
            pass
        
        # Reset to first frame
        gif.seek(0)
        
        # Process each frame
        frame_files = []
        for frame_idx in range(n_frames):
            try:
                gif.seek(frame_idx)
                frame = gif.copy()
                
                print(f"\nProcessing frame {frame_idx + 1}/{n_frames}")
                
                # Handle transparency first
                if frame.mode in ('RGBA', 'LA', 'P'):
                    print(f"  Processing transparency...")
                    # Check if image has transparency info
                    if frame.mode == 'P':
                        frame = frame.convert('RGBA')
                    
                    # Create black background
                    background = Image.new('RGBA', frame.size, (0, 0, 0, 255))
                    # Composite frame over black background
                    if frame.mode == 'RGBA':
                        background = Image.alpha_composite(background, frame)
                    frame = background.convert('RGB')
                elif frame.mode != 'RGB':
                    frame = frame.convert('RGB')
                
                # Handle sizing - preserve aspect ratio and center
                if frame.size[0] <= 256 and frame.size[1] <= 256:
                    # Image fits - center it on 256x256 black canvas
                    print(f"  Centering {frame.size[0]}x{frame.size[1]} image on 256x256 canvas")
                    canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                    x_offset = (256 - frame.size[0]) // 2
                    y_offset = (256 - frame.size[1]) // 2
                    canvas.paste(frame, (x_offset, y_offset))
                    frame = canvas
                else:
                    # Image too large - scale proportionally to fit, then center
                    aspect_ratio = frame.size[0] / frame.size[1]
                    if aspect_ratio > 1:
                        # Wider than tall
                        new_width = 256
                        new_height = int(256 / aspect_ratio)
                    else:
                        # Taller than wide
                        new_height = 256
                        new_width = int(256 * aspect_ratio)
                    
                    print(f"  Scaling {frame.size[0]}x{frame.size[1]} -> {new_width}x{new_height} (preserving aspect ratio)")
                    frame = frame.resize((new_width, new_height), Image.Resampling.LANCZOS)
                    
                    # Center on 256x256 canvas
                    canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                    x_offset = (256 - new_width) // 2
                    y_offset = (256 - new_height) // 2
                    canvas.paste(frame, (x_offset, y_offset))
                    frame = canvas
                    print(f"  Centered on 256x256 canvas")
                
                # Convert to RGB565
                print(f"  Converting to RGB565...")
                pixels = frame.load()
                rgb565_bytes = bytearray()
                
                for y in range(256):
                    for x in range(256):
                        r, g, b = pixels[x, y]
                        
                        # Convert to 5-6-5 bit format
                        r5 = (r >> 3) & 0x1F
                        g6 = (g >> 2) & 0x3F
                        b5 = (b >> 3) & 0x1F
                        
                        # Pack into 16-bit value (BGR565 for display)
                        rgb565 = (b5 << 11) | (g6 << 5) | r5
                        
                        # Write as little-endian (ESP32 byte order)
                        rgb565_bytes.extend(struct.pack('<H', rgb565))
                
                output_path = f"{output_prefix}_{frame_idx:03d}.bin"
                with open(output_path, 'wb') as f:
                    f.write(rgb565_bytes)
                
                file_size = len(rgb565_bytes) / 1024
                print(f"  Wrote: {output_path} ({file_size:.1f} KB)")
                frame_files.append(output_path)
                
            except EOFError:
                break
        
        # Write frame info file
        info_path = f"{output_prefix}_info.txt"
        with open(info_path, 'w') as f:
            f.write(f"# GIF Animation Info\n")
            f.write(f"# Source: {input_path}\n")
            f.write(f"# Frames: {len(frame_files)}\n")
            f.write(f"# Format: RGB565 (256x256)\n\n")
            
            f.write(f"Total frames: {len(frame_files)}\n")
            f.write(f"Frame size: 256x256 pixels\n")
            f.write(f"Color format: RGB565 (16-bit)\n")
            f.write(f"File size per frame: 131,072 bytes (128 KB)\n\n")
            
            f.write("Frame timing (milliseconds):\n")
            for i, duration in enumerate(durations[:len(frame_files)]):
                f.write(f"Frame {i:03d}: {duration} ms\n")
            
            f.write("\nFiles created:\n")
            for fname in frame_files:
                f.write(f"  {fname}\n")
        
        print(f"\n✓ Created {len(frame_files)} frame files")
        print(f"✓ Created info file: {info_path}")
        print(f"\nTo use in ESP32:")
        print(f"  1. Copy all .bin files to SD card")
        print(f"  2. Load frames in sequence")
        print(f"  3. Use timing from info file for animation speed")

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python convert_gif_to_bin.py input.gif output_prefix")
        print("Example: python convert_gif_to_bin.py animation.gif /sdcard/anim")
        print("         Creates: /sdcard/anim_000.bin, /sdcard/anim_001.bin, etc.")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_prefix = sys.argv[2]
    
    try:
        convert_gif_to_rgb565_frames(input_file, output_prefix)
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)
