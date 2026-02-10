"""
Waveshare ESP32-S3-Touch-LCD-1.46B Menu System
With Touch Support
"""

import machine
import time
from machine import Pin, SPI, I2C

# === Display Configuration ===
LCD_BL = 38
LCD_DC = 2
LCD_RST = 3
LCD_CS = 5
LCD_SCLK = 6
LCD_MOSI = 7
WIDTH = 172
HEIGHT = 320

# === Touch Configuration (CST816S) ===
TOUCH_SDA = 4
TOUCH_SCL = 5
TOUCH_INT = 0
TOUCH_RST = 1

# === Display Class ===
class ST7789:
    def __init__(self):
        # Initialize backlight
        self.bl = Pin(LCD_BL, Pin.OUT)
        self.bl.value(1)
        
        # Initialize reset
        self.rst = Pin(LCD_RST, Pin.OUT)
        self.rst.value(1)
        time.sleep_ms(5)
        self.rst.value(0)
        time.sleep_ms(10)
        self.rst.value(1)
        time.sleep_ms(120)
        
        # Initialize SPI
        self.spi = SPI(1, baudrate=40000000, polarity=0, phase=0,
                      sck=Pin(LCD_SCLK), mosi=Pin(LCD_MOSI))
        
        # Initialize DC and CS
        self.dc = Pin(LCD_DC, Pin.OUT)
        self.cs = Pin(LCD_CS, Pin.OUT)
        self.cs.value(1)
        
        self._init_display()
    
    def _write_cmd(self, cmd):
        self.cs.value(0)
        self.dc.value(0)
        self.spi.write(bytearray([cmd]))
        self.cs.value(1)
    
    def _write_data(self, data):
        self.cs.value(0)
        self.dc.value(1)
        if isinstance(data, int):
            self.spi.write(bytearray([data]))
        else:
            self.spi.write(data)
        self.cs.value(1)
    
    def _init_display(self):
        self._write_cmd(0x11)  # Sleep out
        time.sleep_ms(120)
        
        self._write_cmd(0x36)  # Memory Data Access Control
        self._write_data(0x00)
        
        self._write_cmd(0x3A)  # Interface Pixel Format
        self._write_data(0x05)  # 16-bit
        
        self._write_cmd(0xB2)  # Porch Setting
        for val in [0x0C, 0x0C, 0x00, 0x33, 0x33]:
            self._write_data(val)
        
        self._write_cmd(0xB7); self._write_data(0x35)
        self._write_cmd(0xBB); self._write_data(0x19)
        self._write_cmd(0xC0); self._write_data(0x2C)
        self._write_cmd(0xC2); self._write_data(0x01)
        self._write_cmd(0xC3); self._write_data(0x12)
        self._write_cmd(0xC4); self._write_data(0x20)
        self._write_cmd(0xC6); self._write_data(0x0F)
        self._write_cmd(0xD0); self._write_data(0xA4); self._write_data(0xA1)
        
        # Gamma
        self._write_cmd(0xE0)
        for val in [0xD0, 0x04, 0x0D, 0x11, 0x13, 0x2B, 0x3F, 0x54, 0x4C, 0x18, 0x0D, 0x0B, 0x1F, 0x23]:
            self._write_data(val)
        
        self._write_cmd(0xE1)
        for val in [0xD0, 0x04, 0x0C, 0x11, 0x13, 0x2C, 0x3F, 0x44, 0x51, 0x2F, 0x1F, 0x1F, 0x20, 0x23]:
            self._write_data(val)
        
        self._write_cmd(0x21)  # Display Inversion On
        self._write_cmd(0x29)  # Display On
    
    def fill_screen(self, color):
        self._write_cmd(0x2A)  # Column
        self._write_data(0x00); self._write_data(0x00)
        self._write_data(0x00); self._write_data(WIDTH - 1)
        
        self._write_cmd(0x2B)  # Row
        self._write_data(0x00); self._write_data(0x00)
        self._write_data((HEIGHT >> 8) & 0xFF); self._write_data(HEIGHT & 0xFF)
        
        self._write_cmd(0x2C)  # Write
        line = bytearray([color >> 8, color & 0xFF] * WIDTH)
        
        self.cs.value(0)
        self.dc.value(1)
        for _ in range(HEIGHT):
            self.spi.write(line)
        self.cs.value(1)
    
    def fill_rect(self, x, y, w, h, color):
        if x + w > WIDTH or y + h > HEIGHT:
            return
        
        self._write_cmd(0x2A)  # Column
        self._write_data(x >> 8); self._write_data(x & 0xFF)
        self._write_data((x + w - 1) >> 8); self._write_data((x + w - 1) & 0xFF)
        
        self._write_cmd(0x2B)  # Row
        self._write_data(y >> 8); self._write_data(y & 0xFF)
        self._write_data((y + h - 1) >> 8); self._write_data((y + h - 1) & 0xFF)
        
        self._write_cmd(0x2C)  # Write
        line = bytearray([color >> 8, color & 0xFF] * w)
        
        self.cs.value(0)
        self.dc.value(1)
        for _ in range(h):
            self.spi.write(line)
        self.cs.value(1)
    
    def draw_text(self, text, x, y, color, bg_color=None, scale=2):
        """Simple 8x8 bitmap font text rendering"""
        if bg_color is not None:
            self.fill_rect(x, y, len(text) * 8 * scale, 8 * scale, bg_color)
        
        for char_idx, char in enumerate(text):
            self._draw_char(char, x + char_idx * 8 * scale, y, color, scale)
    
    def _draw_char(self, char, x, y, color, scale):
        """Draw a single character using simple 8x8 font"""
        # Simple built-in font patterns (only A-Z, 0-9, space for now)
        font = self._get_char_pattern(char)
        
        for row in range(8):
            for col in range(8):
                if font[row] & (1 << (7 - col)):
                    if scale == 1:
                        self.fill_rect(x + col, y + row, 1, 1, color)
                    else:
                        self.fill_rect(x + col * scale, y + row * scale, scale, scale, color)
    
    def _get_char_pattern(self, char):
        """Get 8x8 bitmap pattern for a character"""
        # Very basic 5x7 patterns (centered in 8x8)
        patterns = {
            ' ': [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00],
            '0': [0x3C, 0x66, 0x66, 0x66, 0x66, 0x66, 0x3C, 0x00],
            '1': [0x18, 0x38, 0x18, 0x18, 0x18, 0x18, 0x7E, 0x00],
            '2': [0x3C, 0x66, 0x06, 0x0C, 0x18, 0x30, 0x7E, 0x00],
            '3': [0x3C, 0x66, 0x06, 0x1C, 0x06, 0x66, 0x3C, 0x00],
            '4': [0x0C, 0x1C, 0x2C, 0x4C, 0x7E, 0x0C, 0x0C, 0x00],
            '5': [0x7E, 0x60, 0x7C, 0x06, 0x06, 0x66, 0x3C, 0x00],
            'A': [0x18, 0x24, 0x42, 0x42, 0x7E, 0x42, 0x42, 0x00],
            'B': [0x7C, 0x42, 0x42, 0x7C, 0x42, 0x42, 0x7C, 0x00],
            'C': [0x3C, 0x42, 0x40, 0x40, 0x40, 0x42, 0x3C, 0x00],
            'D': [0x78, 0x44, 0x42, 0x42, 0x42, 0x44, 0x78, 0x00],
            'E': [0x7E, 0x40, 0x40, 0x7C, 0x40, 0x40, 0x7E, 0x00],
            'M': [0x42, 0x66, 0x5A, 0x42, 0x42, 0x42, 0x42, 0x00],
            'N': [0x42, 0x62, 0x52, 0x4A, 0x46, 0x42, 0x42, 0x00],
            'O': [0x3C, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C, 0x00],
            'S': [0x3C, 0x42, 0x40, 0x3C, 0x02, 0x42, 0x3C, 0x00],
            'T': [0x7E, 0x18, 0x18, 0x18, 0x18, 0x18, 0x18, 0x00],
            'U': [0x42, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C, 0x00],
            'W': [0x42, 0x42, 0x42, 0x42, 0x5A, 0x66, 0x42, 0x00],
            'i': [0x18, 0x00, 0x38, 0x18, 0x18, 0x18, 0x3C, 0x00],
        }
        return patterns.get(char.upper(), patterns[' '])

# === Menu System ===
class Menu:
    def __init__(self, display):
        self.display = display
        self.items = []
        self.selected = 0
        
        # Colors
        self.bg_color = 0x0000      # Black
        self.item_color = 0x07FF    # Cyan
        self.selected_color = 0xFFE0  # Yellow
        self.title_color = 0xF800   # Red
    
    def add_item(self, text, callback):
        self.items.append({'text': text, 'callback': callback})
    
    def draw(self):
        self.display.fill_screen(self.bg_color)
        
        # Draw title
        self.display.draw_text("MAIN MENU", 20, 20, self.title_color, scale=2)
        
        # Draw menu items
        y_start = 70
        item_height = 40
        
        for idx, item in enumerate(self.items):
            y = y_start + idx * item_height
            color = self.selected_color if idx == self.selected else self.item_color
            
            # Draw selection indicator
            if idx == self.selected:
                self.display.fill_rect(5, y - 5, 162, 30, 0x1082)
            
            self.display.draw_text(f"{idx+1} {item['text']}", 15, y, color, scale=2)
    
    def select_next(self):
        self.selected = (self.selected + 1) % len(self.items)
        self.draw()
    
    def select_prev(self):
        self.selected = (self.selected - 1) % len(self.items)
        self.draw()
    
    def activate(self):
        if self.items and self.items[self.selected]['callback']:
            self.items[self.selected]['callback']()

# === Demo Functions ===
def demo_colors(display):
    colors = [(0xF800, "Red"), (0x07E0, "Green"), (0x001F, "Blue"), (0xFFFF, "White")]
    for color, name in colors:
        display.fill_screen(color)
        time.sleep(0.5)

def demo_pattern(display):
    display.fill_screen(0x0000)
    for i in range(0, HEIGHT, 20):
        display.fill_rect(0, i, WIDTH, 10, 0x07FF if i % 40 == 0 else 0xF800)

def demo_text(display):
    display.fill_screen(0x0000)
    display.draw_text("DEMO TEXT", 20, 100, 0x07E0, scale=3)
    display.draw_text("MENU SYSTEM", 15, 140, 0xFFFF, scale=2)

# === Main Program ===
print("Initializing display...")
display = ST7789()
display.fill_screen(0x0000)

print("Creating menu...")
menu = Menu(display)
menu.add_item("Colors", lambda: demo_colors(display))
menu.add_item("Pattern", lambda: demo_pattern(display))
menu.add_item("Text", lambda: demo_text(display))
menu.add_item("About", lambda: display.draw_text("ESP32 S3", 30, 140, 0xFFFF, 0x0000, 2))

menu.draw()

print("\nMenu loaded!")
print("Commands:")
print("  menu.select_next()  - Move selection down")
print("  menu.select_prev()  - Move selection up")
print("  menu.activate()     - Run selected item")
print("  menu.draw()         - Redraw menu")
