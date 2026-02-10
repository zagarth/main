"""
Waveshare ESP32-S3-Touch-LCD-1.46B Interactive Menu
With Full Touch Support
"""

import machine
import time
from machine import Pin, SPI, I2C

# === Hardware Configuration ===
# Display pins
LCD_BL = 38
LCD_DC = 2
LCD_RST = 3
LCD_CS = 5
LCD_SCLK = 6
LCD_MOSI = 7
WIDTH = 172
HEIGHT = 320

# Touch pins (CST816S)
TOUCH_SDA = 4
TOUCH_SCL = 5
TOUCH_INT = 0
TOUCH_RST = 1

# === CST816S Touch Controller ===
class CST816S:
    def __init__(self, sda=TOUCH_SDA, scl=TOUCH_SCL, rst=TOUCH_RST):
        self.i2c = I2C(0, sda=Pin(sda), scl=Pin(scl), freq=400000)
        self.rst = Pin(rst, Pin.OUT)
        self.addr = 0x15
        
        # Reset touch controller
        self.rst.value(0)
        time.sleep_ms(10)
        self.rst.value(1)
        time.sleep_ms(50)
        
        print("Touch controller initialized")
    
    def get_touch(self):
        """Read touch coordinates"""
        try:
            data = self.i2c.readfrom(self.addr, 6)
            
            # Check if touched
            if data[0] == 0:
                return None
            
            # Extract coordinates
            x = ((data[2] & 0x0F) << 8) | data[3]
            y = ((data[4] & 0x0F) << 8) | data[5]
            
            return (x, y)
        except:
            return None

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
        self._write_cmd(0x11)
        time.sleep_ms(120)
        self._write_cmd(0x36); self._write_data(0x00)
        self._write_cmd(0x3A); self._write_data(0x05)
        self._write_cmd(0xB2)
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
        self._write_cmd(0xE0)
        for val in [0xD0, 0x04, 0x0D, 0x11, 0x13, 0x2B, 0x3F, 0x54, 0x4C, 0x18, 0x0D, 0x0B, 0x1F, 0x23]:
            self._write_data(val)
        self._write_cmd(0xE1)
        for val in [0xD0, 0x04, 0x0C, 0x11, 0x13, 0x2C, 0x3F, 0x44, 0x51, 0x2F, 0x1F, 0x1F, 0x20, 0x23]:
            self._write_data(val)
        self._write_cmd(0x21)
        self._write_cmd(0x29)
    
    def fill_screen(self, color):
        self._write_cmd(0x2A)
        self._write_data(0x00); self._write_data(0x00)
        self._write_data(0x00); self._write_data(WIDTH - 1)
        self._write_cmd(0x2B)
        self._write_data(0x00); self._write_data(0x00)
        self._write_data((HEIGHT >> 8) & 0xFF); self._write_data(HEIGHT & 0xFF)
        self._write_cmd(0x2C)
        line = bytearray([color >> 8, color & 0xFF] * WIDTH)
        self.cs.value(0)
        self.dc.value(1)
        for _ in range(HEIGHT):
            self.spi.write(line)
        self.cs.value(1)
    
    def fill_rect(self, x, y, w, h, color):
        if x + w > WIDTH or y + h > HEIGHT or x < 0 or y < 0:
            return
        self._write_cmd(0x2A)
        self._write_data(x >> 8); self._write_data(x & 0xFF)
        self._write_data((x + w - 1) >> 8); self._write_data((x + w - 1) & 0xFF)
        self._write_cmd(0x2B)
        self._write_data(y >> 8); self._write_data(y & 0xFF)
        self._write_data((y + h - 1) >> 8); self._write_data((y + h - 1) & 0xFF)
        self._write_cmd(0x2C)
        line = bytearray([color >> 8, color & 0xFF] * w)
        self.cs.value(0)
        self.dc.value(1)
        for _ in range(h):
            self.spi.write(line)
        self.cs.value(1)
    
    def draw_text(self, text, x, y, color, bg_color=None, scale=2):
        if bg_color is not None:
            self.fill_rect(x, y, len(text) * 8 * scale, 8 * scale, bg_color)
        for char_idx, char in enumerate(text):
            self._draw_char(char, x + char_idx * 8 * scale, y, color, scale)
    
    def _draw_char(self, char, x, y, color, scale):
        font = self._get_char_pattern(char)
        for row in range(8):
            for col in range(8):
                if font[row] & (1 << (7 - col)):
                    if scale == 1:
                        self.fill_rect(x + col, y + row, 1, 1, color)
                    else:
                        self.fill_rect(x + col * scale, y + row * scale, scale, scale, color)
    
    def _get_char_pattern(self, char):
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
            'F': [0x7E, 0x40, 0x40, 0x7C, 0x40, 0x40, 0x40, 0x00],
            'G': [0x3C, 0x42, 0x40, 0x4E, 0x42, 0x42, 0x3C, 0x00],
            'H': [0x42, 0x42, 0x42, 0x7E, 0x42, 0x42, 0x42, 0x00],
            'I': [0x3C, 0x18, 0x18, 0x18, 0x18, 0x18, 0x3C, 0x00],
            'L': [0x40, 0x40, 0x40, 0x40, 0x40, 0x40, 0x7E, 0x00],
            'M': [0x42, 0x66, 0x5A, 0x42, 0x42, 0x42, 0x42, 0x00],
            'N': [0x42, 0x62, 0x52, 0x4A, 0x46, 0x42, 0x42, 0x00],
            'O': [0x3C, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C, 0x00],
            'P': [0x7C, 0x42, 0x42, 0x7C, 0x40, 0x40, 0x40, 0x00],
            'R': [0x7C, 0x42, 0x42, 0x7C, 0x48, 0x44, 0x42, 0x00],
            'S': [0x3C, 0x42, 0x40, 0x3C, 0x02, 0x42, 0x3C, 0x00],
            'T': [0x7E, 0x18, 0x18, 0x18, 0x18, 0x18, 0x18, 0x00],
            'U': [0x42, 0x42, 0x42, 0x42, 0x42, 0x42, 0x3C, 0x00],
            'W': [0x42, 0x42, 0x42, 0x42, 0x5A, 0x66, 0x42, 0x00],
            'X': [0x42, 0x42, 0x24, 0x18, 0x24, 0x42, 0x42, 0x00],
            'a': [0x00, 0x00, 0x3C, 0x02, 0x3E, 0x42, 0x3E, 0x00],
            'b': [0x40, 0x40, 0x5C, 0x62, 0x42, 0x42, 0x7C, 0x00],
            'c': [0x00, 0x00, 0x3C, 0x42, 0x40, 0x42, 0x3C, 0x00],
            'd': [0x02, 0x02, 0x3A, 0x46, 0x42, 0x42, 0x3E, 0x00],
            'e': [0x00, 0x00, 0x3C, 0x42, 0x7E, 0x40, 0x3C, 0x00],
            'h': [0x40, 0x40, 0x5C, 0x62, 0x42, 0x42, 0x42, 0x00],
            'i': [0x18, 0x00, 0x38, 0x18, 0x18, 0x18, 0x3C, 0x00],
            'l': [0x38, 0x18, 0x18, 0x18, 0x18, 0x18, 0x3C, 0x00],
            'n': [0x00, 0x00, 0x5C, 0x62, 0x42, 0x42, 0x42, 0x00],
            'o': [0x00, 0x00, 0x3C, 0x42, 0x42, 0x42, 0x3C, 0x00],
            'r': [0x00, 0x00, 0x5C, 0x62, 0x40, 0x40, 0x40, 0x00],
            's': [0x00, 0x00, 0x3E, 0x40, 0x3C, 0x02, 0x7C, 0x00],
            't': [0x20, 0x20, 0x7C, 0x20, 0x20, 0x22, 0x1C, 0x00],
            'u': [0x00, 0x00, 0x42, 0x42, 0x42, 0x46, 0x3A, 0x00],
            'x': [0x00, 0x00, 0x42, 0x24, 0x18, 0x24, 0x42, 0x00],
            'y': [0x00, 0x00, 0x42, 0x42, 0x42, 0x3E, 0x02, 0x3C],
            ':': [0x00, 0x18, 0x18, 0x00, 0x18, 0x18, 0x00, 0x00],
            '!': [0x18, 0x18, 0x18, 0x18, 0x00, 0x18, 0x18, 0x00],
            '.': [0x00, 0x00, 0x00, 0x00, 0x00, 0x18, 0x18, 0x00],
            '-': [0x00, 0x00, 0x00, 0x7E, 0x00, 0x00, 0x00, 0x00],
        }
        return patterns.get(char, patterns[' '])

# === Interactive Menu ===
class TouchMenu:
    def __init__(self, display, touch):
        self.display = display
        self.touch = touch
        self.items = []
        self.selected = -1
        self.running = True
        
        # Colors
        self.bg_color = 0x0000        # Black
        self.item_color = 0x07FF      # Cyan
        self.selected_color = 0xFFE0  # Yellow
        self.title_color = 0xF800     # Red
        self.pressed_color = 0x001F   # Blue
    
    def add_item(self, text, callback):
        self.items.append({'text': text, 'callback': callback})
    
    def draw(self):
        self.display.fill_screen(self.bg_color)
        
        # Draw title bar
        self.display.fill_rect(0, 0, WIDTH, 50, 0x1082)
        self.display.draw_text("MAIN MENU", 25, 18, self.title_color, scale=2)
        
        # Draw menu items
        y_start = 65
        item_height = 45
        
        for idx, item in enumerate(self.items):
            y = y_start + idx * item_height
            
            # Draw button background
            if idx == self.selected:
                bg = self.pressed_color
                fg = 0xFFFF
            else:
                bg = 0x2104
                fg = self.item_color
            
            self.display.fill_rect(10, y, WIDTH - 20, 35, bg)
            self.display.draw_text(item['text'], 20, y + 10, fg, scale=2)
        
        # Draw footer
        self.display.draw_text("Touch to select", 15, HEIGHT - 25, 0x7BEF, scale=1)
    
    def get_item_at(self, x, y):
        """Get menu item index at touch coordinates"""
        y_start = 65
        item_height = 45
        
        for idx in range(len(self.items)):
            item_y = y_start + idx * item_height
            if 10 <= x <= WIDTH - 10 and item_y <= y <= item_y + 35:
                return idx
        return -1
    
    def handle_touch(self, x, y):
        """Handle touch event"""
        item_idx = self.get_item_at(x, y)
        if item_idx >= 0:
            self.selected = item_idx
            self.draw()
            time.sleep_ms(100)  # Visual feedback
            
            # Execute callback
            if self.items[item_idx]['callback']:
                self.items[item_idx]['callback']()
                time.sleep(1)
                self.selected = -1
                self.draw()
    
    def run(self):
        """Main event loop"""
        self.draw()
        last_touch = None
        touch_debounce = 0
        
        print("\nMenu running - touch the screen!")
        print("Press Ctrl+C to exit")
        
        while self.running:
            try:
                # Read touch
                touch = self.touch.get_touch()
                
                if touch and touch != last_touch:
                    current_time = time.ticks_ms()
                    if current_time - touch_debounce > 200:
                        print(f"Touch at: {touch}")
                        self.handle_touch(touch[0], touch[1])
                        touch_debounce = current_time
                
                last_touch = touch
                time.sleep_ms(50)
                
            except KeyboardInterrupt:
                print("\nExiting menu...")
                self.running = False
                break

# === Demo Functions ===
def demo_colors(display):
    colors = [(0xF800, "Red"), (0x07E0, "Green"), (0x001F, "Blue")]
    for color, name in colors:
        display.fill_screen(color)
        display.draw_text(name, 50, 140, 0xFFFF, scale=3)
        time.sleep(0.5)

def demo_pattern(display):
    display.fill_screen(0x0000)
    for i in range(0, HEIGHT, 15):
        display.fill_rect(0, i, WIDTH, 8, 0x07FF if i % 30 == 0 else 0xF81F)

def demo_info(display):
    display.fill_screen(0x0000)
    display.draw_text("ESP32-S3", 35, 80, 0x07FF, scale=2)
    display.draw_text("Waveshare", 25, 110, 0xFFFF, scale=2)
    display.draw_text("1.46 LCD", 30, 140, 0xF81F, scale=2)

def demo_exit():
    global menu
    menu.running = False
    print("Exiting...")

# === Main Program ===
print("Initializing hardware...")
display = ST7789()
touch = CST816S()

print("Creating interactive menu...")
menu = TouchMenu(display, touch)
menu.add_item("Colors", lambda: demo_colors(display))
menu.add_item("Pattern", lambda: demo_pattern(display))
menu.add_item("About", lambda: demo_info(display))
menu.add_item("Exit", demo_exit)

print("Starting menu...")
menu.run()
