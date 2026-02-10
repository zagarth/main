"""
Waveshare ESP32-S3-Touch-LCD-1.46B CORRECT Configuration
Uses SPD2010 QSPI Display Controller
"""
from machine import Pin, SPI
import time

# CORRECT pins from official demo
BL = 5      # Backlight
CS = 21     # Chip Select  
CLK = 40    # SPI Clock
D0 = 46     # Data line 0
D1 = 45     # Data line 1
D2 = 42     # Data line 2
D3 = 41     # Data line 3
TE = 18     # Tearing effect

WIDTH = 412
HEIGHT = 412

print("=" * 50)
print("CORRECT PIN CONFIGURATION FOUND!")
print("=" * 50)
print(f"Backlight: GPIO {BL}")
print(f"CS: GPIO {CS}")
print(f"Clock: GPIO {CLK}")
print(f"Data: GPIO {D0}, {D1}, {D2}, {D3} (QSPI)")
print(f"TE: GPIO {TE}")
print(f"Display: {WIDTH}x{HEIGHT} SPD2010")
print("=" * 50)

# Test backlight
print("\n1. Testing backlight...")
bl = Pin(BL, Pin.OUT)
for i in range(3):
    bl.value(0)
    time.sleep_ms(300)
    bl.value(1)
    time.sleep_ms(300)
print("✓ Backlight test complete!")

# Initialize QSPI
print("\n2. Initializing QSPI interface...")
bl.value(1)

# Use QSPI mode
from machine import SoftSPI

# For now, let's use DATA0 as MOSI for basic test
spi = SPI(2, baudrate=40000000, polarity=3, phase=0, 
          sck=Pin(CLK), mosi=Pin(D0))

cs = Pin(CS, Pin.OUT)
cs.value(1)

def qspi_cmd(cmd):
    """Send command in QSPI mode"""
    cs.value(0)
    # Command phase: send 32-bit command
    spi.write(bytearray([
        (cmd >> 24) & 0xFF,
        (cmd >> 16) & 0xFF, 
        (cmd >> 8) & 0xFF,
        cmd & 0xFF
    ]))
    cs.value(1)
    time.sleep_us(10)

def qspi_data(data):
    """Send data in QSPI mode"""
    cs.value(0)
    if isinstance(data, int):
        spi.write(bytearray([data]))
    else:
        spi.write(data)
    cs.value(1)
    time.sleep_us(10)

print("\n3. Initializing SPD2010 display...")

# SPD2010 init sequence (from official demo)
print("Sending init commands...")

# Basic init
qspi_cmd(0x11000000)  # Sleep out
time.sleep_ms(120)

qspi_cmd(0x36000000)  # MADCTL
qspi_data(0x00)

qspi_cmd(0x3A000000)  # Pixel format
qspi_data(0x05)  # 16-bit

qspi_cmd(0x29000000)  # Display ON
time.sleep_ms(50)

print("\n4. Attempting to fill screen...")
# Try to draw
qspi_cmd(0x2A000000)  # Column address
qspi_data(0x00); qspi_data(0x00)
qspi_data(0x01); qspi_data(0x9B)  # 411

qspi_cmd(0x2B000000)  # Row address  
qspi_data(0x00); qspi_data(0x00)
qspi_data(0x01); qspi_data(0x9B)  # 411

qspi_cmd(0x2C000000)  # Memory write

# Draw red
red = bytearray([0xF8, 0x00] * 412)
cs.value(0)
for _ in range(412):
    spi.write(red)
cs.value(1)

print("\n" + "=" * 50)
print("IMPORTANT NOTE:")
print("=" * 50)
print("This board uses QSPI (4-wire) interface.")
print("MicroPython's SPI doesn't fully support QSPI mode.")
print("\nTo properly drive this display, you need:")
print("1. Use Arduino IDE with the official demo")
print("2. Use ESP-IDF with proper QSPI support")
print("3. Or use a MicroPython build with QSPI support")
print("\nThe official demo works perfectly in Arduino/ESP-IDF!")
print("=" * 50)
