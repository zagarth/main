"""
Waveshare ESP32-S3-Touch-LCD-1.46B Display Test V2
Trying alternative pin configurations
"""

import machine
import time
from machine import Pin, SPI

# Alternative pin definitions for ESP32-S3-Touch-LCD-1.46B
# Based on typical Waveshare ESP32-S3 LCD layouts
LCD_BL = 38    # Backlight (trying alternative)
LCD_DC = 2     # Data/Command
LCD_RST = 3    # Reset
LCD_CS = 5     # Chip Select
LCD_SCLK = 6   # SPI Clock
LCD_MOSI = 7   # SPI MOSI

# Display dimensions
WIDTH = 172
HEIGHT = 320

print("Testing pins:")
print(f"BL={LCD_BL}, DC={LCD_DC}, RST={LCD_RST}, CS={LCD_CS}, SCLK={LCD_SCLK}, MOSI={LCD_MOSI}")

# Initialize backlight
print("Testing backlight...")
bl = Pin(LCD_BL, Pin.OUT)
bl.value(0)
time.sleep_ms(500)
bl.value(1)
print("Backlight should be ON - do you see any glow?")
time.sleep(2)

# Initialize reset pin
print("Resetting display...")
rst = Pin(LCD_RST, Pin.OUT)
rst.value(1)
time.sleep_ms(5)
rst.value(0)
time.sleep_ms(10)
rst.value(1)
time.sleep_ms(120)

# Initialize SPI
print("Initializing SPI...")
spi = SPI(1, baudrate=40000000, polarity=0, phase=0, 
          sck=Pin(LCD_SCLK), mosi=Pin(LCD_MOSI))

# Initialize DC and CS pins
dc = Pin(LCD_DC, Pin.OUT)
cs = Pin(LCD_CS, Pin.OUT)
cs.value(1)

def write_cmd(cmd):
    """Write command to display"""
    cs.value(0)
    dc.value(0)
    spi.write(bytearray([cmd]))
    cs.value(1)

def write_data(data):
    """Write data to display"""
    cs.value(0)
    dc.value(1)
    if isinstance(data, int):
        spi.write(bytearray([data]))
    else:
        spi.write(data)
    cs.value(1)

# ST7789V initialization sequence
print("Initializing ST7789V display...")

write_cmd(0x11)  # Sleep out
time.sleep_ms(120)

write_cmd(0x36)  # Memory Data Access Control
write_data(0x00)

write_cmd(0x3A)  # Interface Pixel Format
write_data(0x05)  # 16-bit color

write_cmd(0xB2)  # Porch Setting
write_data(0x0C)
write_data(0x0C)
write_data(0x00)
write_data(0x33)
write_data(0x33)

write_cmd(0xB7)  # Gate Control
write_data(0x35)

write_cmd(0xBB)  # VCOM Setting
write_data(0x19)

write_cmd(0xC0)  # LCM Control
write_data(0x2C)

write_cmd(0xC2)  # VDV and VRH Command Enable
write_data(0x01)

write_cmd(0xC3)  # VRH Set
write_data(0x12)

write_cmd(0xC4)  # VDV Set
write_data(0x20)

write_cmd(0xC6)  # Frame Rate Control
write_data(0x0F)

write_cmd(0xD0)  # Power Control 1
write_data(0xA4)
write_data(0xA1)

write_cmd(0xE0)  # Positive Voltage Gamma Control
write_data(0xD0)
write_data(0x04)
write_data(0x0D)
write_data(0x11)
write_data(0x13)
write_data(0x2B)
write_data(0x3F)
write_data(0x54)
write_data(0x4C)
write_data(0x18)
write_data(0x0D)
write_data(0x0B)
write_data(0x1F)
write_data(0x23)

write_cmd(0xE1)  # Negative Voltage Gamma Control
write_data(0xD0)
write_data(0x04)
write_data(0x0C)
write_data(0x11)
write_data(0x13)
write_data(0x2C)
write_data(0x3F)
write_data(0x44)
write_data(0x51)
write_data(0x2F)
write_data(0x1F)
write_data(0x1F)
write_data(0x20)
write_data(0x23)

write_cmd(0x21)  # Display Inversion On

write_cmd(0x29)  # Display On

print("Display initialized!")

# Fill screen with red
def fill_screen(color):
    """Fill entire screen with a color (RGB565 format)"""
    write_cmd(0x2A)  # Column Address Set
    write_data(0x00)
    write_data(0x00)
    write_data(0x00)
    write_data(WIDTH - 1)
    
    write_cmd(0x2B)  # Row Address Set
    write_data(0x00)
    write_data(0x00)
    write_data((HEIGHT >> 8) & 0xFF)
    write_data(HEIGHT & 0xFF)
    
    write_cmd(0x2C)  # Memory Write
    
    # Create color buffer
    color_high = color >> 8
    color_low = color & 0xFF
    line = bytearray([color_high, color_low] * WIDTH)
    
    cs.value(0)
    dc.value(1)
    for _ in range(HEIGHT):
        spi.write(line)
    cs.value(1)

print("Filling screen with RED...")
fill_screen(0xF800)
time.sleep(2)

print("Filling screen with WHITE...")
fill_screen(0xFFFF)

print("\nTest complete! Is the screen showing anything?")
