"""Test with alternative pin mappings for 1.46B variant"""
from machine import Pin, SPI
import time

# Alternative pin mappings to try
configs = [
    {"name": "Config 1 (original)", "BL": 38, "DC": 2, "RST": 3, "CS": 5, "CLK": 6, "MOSI": 7},
    {"name": "Config 2 (alt 1)", "BL": 38, "DC": 8, "RST": 9, "CS": 10, "CLK": 12, "MOSI": 11},
    {"name": "Config 3 (alt 2)", "BL": 38, "DC": 2, "RST": 21, "CS": 10, "CLK": 12, "MOSI": 11},
]

for config in configs:
    print(f"\n{'='*50}")
    print(f"Testing: {config['name']}")
    print(f"Pins: {config}")
    print('='*50)
    
    try:
        # Setup
        bl = Pin(config['BL'], Pin.OUT)
        bl.value(1)
        
        rst = Pin(config['RST'], Pin.OUT)
        rst.value(0)
        time.sleep_ms(50)
        rst.value(1)
        time.sleep_ms(150)
        
        spi = SPI(1, baudrate=10000000, sck=Pin(config['CLK']), mosi=Pin(config['MOSI']))
        dc = Pin(config['DC'], Pin.OUT)
        cs = Pin(config['CS'], Pin.OUT)
        cs.value(1)
        
        def cmd(c):
            cs.value(0)
            dc.value(0)
            spi.write(bytearray([c]))
            cs.value(1)
        
        def data(d):
            cs.value(0)
            dc.value(1)
            spi.write(bytearray([d]) if isinstance(d, int) else d)
            cs.value(1)
        
        # Quick init
        cmd(0x11); time.sleep_ms(120)
        cmd(0x36); data(0x00)
        cmd(0x3A); data(0x05)
        cmd(0x29)
        time.sleep_ms(50)
        
        # Draw red
        cmd(0x2A); data(0); data(0); data(0); data(171)
        cmd(0x2B); data(0); data(0); data(1); data(63)
        cmd(0x2C)
        
        red = bytearray([0xF8, 0x00] * 172)
        cs.value(0)
        dc.value(1)
        for _ in range(320):
            spi.write(red)
        cs.value(1)
        
        print(f"✓ {config['name']} sent successfully!")
        print("Check if screen changed!")
        time.sleep(3)
        
    except Exception as e:
        print(f"✗ {config['name']} failed: {e}")
        continue

print("\n=== TEST COMPLETE ===")
print("Did any configuration show color on screen?")
