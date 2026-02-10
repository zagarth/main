# GIF to Binary Converter

## Script: `convert_gif_to_bin.py`

Converts animated GIFs to RGB565 binary frames for ESP32 display.

### Usage
```bash
python convert_gif_to_bin.py input.gif output_prefix
```

### Example
```bash
python convert_gif_to_bin.py spinning_logo.gif frames/logo
```

**Creates:**
- `frames/logo_000.bin` - First frame
- `frames/logo_001.bin` - Second frame
- `frames/logo_002.bin` - Third frame
- ...
- `frames/logo_info.txt` - Frame timing information

### Output Format
- **Size:** 256x256 pixels per frame
- **Color:** RGB565 (16-bit)
- **File size:** 128 KB per frame (131,072 bytes)
- **Info file:** Contains frame durations in milliseconds

### Features
- Automatically extracts all frames
- Resizes to 256x256
- Handles transparency (composites on black)
- Preserves frame timing from GIF
- Numbered output files for easy sequencing

### Notes
- Each frame is 128KB - SD card storage recommended
- Info file contains millisecond delays for each frame
- Use sequential loading on ESP32 for animation
- Compatible with existing pin mode image loader
