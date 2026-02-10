"""
ESP32 Pin Mode Image Uploader
Converts PNG images to RGB565 binary format and copies to SD card

Usage:
1. Select PNG image (will be resized to 256x256)
2. Preview original and converted RGB565 version
3. Select SD card drive letter
4. Click "Convert & Copy" to create .bin file and copy to SD card

Output: home_icon.bin (131,072 bytes, RGB565 little-endian format)
"""

import tkinter as tk
from tkinter import ttk, filedialog, messagebox
from PIL import Image, ImageTk
import os
import struct
import string
import platform

class PinModeImageUploader:
    def __init__(self, root):
        self.root = root
        self.root.title("ESP32 Pin Mode Image Uploader")
        self.root.geometry("900x700")
        
        self.selected_image_path = None
        self.converted_image = None
        self.rgb565_data = None
        
        self.setup_ui()
        self.detect_drives()
        self.log("Ready. Select a PNG image to begin.")
    
    def setup_ui(self):
        """Create the GUI layout"""
        
        # Top frame - File selection
        top_frame = ttk.Frame(self.root, padding="10")
        top_frame.grid(row=0, column=0, columnspan=2, sticky=(tk.W, tk.E))
        
        ttk.Label(top_frame, text="PNG Image:").grid(row=0, column=0, sticky=tk.W)
        self.file_path_var = tk.StringVar(value="No file selected")
        ttk.Entry(top_frame, textvariable=self.file_path_var, width=60, state='readonly').grid(row=0, column=1, padx=5)
        ttk.Button(top_frame, text="Browse...", command=self.browse_file).grid(row=0, column=2)
        
        # Middle frame - Image previews
        preview_frame = ttk.Frame(self.root, padding="10")
        preview_frame.grid(row=1, column=0, columnspan=2, sticky=(tk.W, tk.E, tk.N, tk.S))
        
        # Original image preview
        original_frame = ttk.LabelFrame(preview_frame, text="Original Image", padding="10")
        original_frame.grid(row=0, column=0, padx=5, sticky=(tk.N, tk.S))
        
        self.original_canvas = tk.Canvas(original_frame, width=256, height=256, bg='black')
        self.original_canvas.pack()
        
        # RGB565 preview
        rgb565_frame = ttk.LabelFrame(preview_frame, text="RGB565 Preview", padding="10")
        rgb565_frame.grid(row=0, column=1, padx=5, sticky=(tk.N, tk.S))
        
        self.rgb565_canvas = tk.Canvas(rgb565_frame, width=256, height=256, bg='black')
        self.rgb565_canvas.pack()
        
        # Info labels
        info_frame = ttk.Frame(preview_frame)
        info_frame.grid(row=1, column=0, columnspan=2, pady=10)
        
        self.info_label = ttk.Label(info_frame, text="File size: 131,072 bytes (256×256 RGB565)", 
                                     foreground='blue')
        self.info_label.pack()
        
        # Bottom control frame
        control_frame = ttk.Frame(self.root, padding="10")
        control_frame.grid(row=2, column=0, columnspan=2, sticky=(tk.W, tk.E))
        
        ttk.Label(control_frame, text="SD Card Drive:").grid(row=0, column=0, sticky=tk.W, padx=5)
        
        self.drive_var = tk.StringVar()
        self.drive_combo = ttk.Combobox(control_frame, textvariable=self.drive_var, width=15, state='readonly')
        self.drive_combo.grid(row=0, column=1, padx=5)
        
        ttk.Button(control_frame, text="Refresh Drives", command=self.detect_drives).grid(row=0, column=2, padx=5)
        
        self.convert_button = ttk.Button(control_frame, text="Convert & Copy to SD Card", 
                                         command=self.convert_and_copy, state='disabled')
        self.convert_button.grid(row=0, column=3, padx=20)
        
        self.status_label = ttk.Label(control_frame, text="", foreground='green')
        self.status_label.grid(row=0, column=4, padx=5)
        
        # Debug log frame
        log_frame = ttk.LabelFrame(self.root, text="Debug Log", padding="10")
        log_frame.grid(row=3, column=0, columnspan=2, sticky=(tk.W, tk.E, tk.N, tk.S), padx=10, pady=10)
        
        self.log_text = tk.Text(log_frame, height=10, width=100, wrap=tk.WORD, 
                                bg='#1e1e1e', fg='#d4d4d4', font=('Consolas', 9))
        self.log_text.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
        
        scrollbar = ttk.Scrollbar(log_frame, orient=tk.VERTICAL, command=self.log_text.yview)
        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.log_text.config(yscrollcommand=scrollbar.set)
        
        # Configure grid weights for resizing
        self.root.grid_rowconfigure(1, weight=1)
        self.root.grid_rowconfigure(3, weight=1)
        self.root.grid_columnconfigure(0, weight=1)
    
    def log(self, message):
        """Add message to debug log"""
        self.log_text.insert(tk.END, f"{message}\n")
        self.log_text.see(tk.END)
        self.root.update_idletasks()
    
    def detect_drives(self):
        """Detect available drives (Windows)"""
        self.log("Detecting removable drives...")
        
        drives = []
        if platform.system() == 'Windows':
            import ctypes
            
            # Get all drive letters
            bitmask = ctypes.windll.kernel32.GetLogicalDrives()
            for letter in string.ascii_uppercase:
                if bitmask & 1:
                    drive = f"{letter}:\\"
                    # Check if it's removable
                    drive_type = ctypes.windll.kernel32.GetDriveTypeW(drive)
                    # DRIVE_REMOVABLE = 2
                    if drive_type == 2:
                        drives.append(f"{letter}:")
                        self.log(f"  Found removable drive: {letter}:")
                bitmask >>= 1
        else:
            # For Linux/Mac, look in /media or /Volumes
            self.log("  Non-Windows OS detected. Manual drive selection may be needed.")
            drives = ["/media", "/Volumes"]  # Placeholder
        
        if drives:
            self.drive_combo['values'] = drives
            self.drive_combo.current(0)
            self.log(f"Detected {len(drives)} removable drive(s)")
        else:
            self.drive_combo['values'] = []
            self.log("⚠️ No removable drives detected. Insert SD card and click 'Refresh Drives'")
    
    def browse_file(self):
        """Open file browser to select PNG image"""
        file_path = filedialog.askopenfilename(
            title="Select PNG Image",
            filetypes=[("PNG Images", "*.png"), ("All Files", "*.*")]
        )
        
        if file_path:
            self.selected_image_path = file_path
            self.file_path_var.set(file_path)
            self.log(f"\n📁 Selected: {os.path.basename(file_path)}")
            self.load_and_convert_image()
    
    def load_and_convert_image(self):
        """Load PNG and convert to RGB565"""
        try:
            self.log("Loading image...")
            img = Image.open(self.selected_image_path)
            self.log(f"  Original size: {img.size}, Mode: {img.mode}")
            
            # Resize to 256x256
            if img.size != (256, 256):
                img = img.resize((256, 256), Image.Resampling.LANCZOS)
                self.log(f"  Resized to 256×256")
            
            # Handle transparency - composite onto black background
            if img.mode in ('RGBA', 'LA', 'P'):
                self.log("  Processing transparency (compositing onto black)...")
                background = Image.new('RGB', img.size, (0, 0, 0))
                if img.mode == 'P':
                    img = img.convert('RGBA')
                background.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
                img = background
            elif img.mode != 'RGB':
                img = img.convert('RGB')
            
            # Display original
            self.display_image(img, self.original_canvas)
            self.log("✓ Original image displayed")
            
            # Convert to RGB565
            self.log("Converting to RGB565...")
            self.rgb565_data = self.convert_to_rgb565(img)
            self.log(f"✓ Converted {len(self.rgb565_data)} bytes")
            
            # Create preview of RGB565 version
            rgb565_preview = self.rgb565_to_image(self.rgb565_data, 256, 256)
            self.display_image(rgb565_preview, self.rgb565_canvas)
            self.log("✓ RGB565 preview displayed")
            
            # Enable convert button
            self.convert_button.config(state='normal')
            self.status_label.config(text="✓ Ready to copy", foreground='green')
            
        except Exception as e:
            self.log(f"❌ Error: {str(e)}")
            messagebox.showerror("Error", f"Failed to load image:\n{str(e)}")
    
    def convert_to_rgb565(self, img):
        """Convert RGB image to RGB565 byte array (big-endian for LV_COLOR_16_SWAP)"""
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
                
                # Write as big-endian (for LV_COLOR_16_SWAP=y)
                rgb565_bytes.extend(struct.pack('>H', rgb565))
        
        return bytes(rgb565_bytes)
    
    def rgb565_to_image(self, data, width, height):
        """Convert RGB565 byte array back to RGB image for preview"""
        img = Image.new('RGB', (width, height))
        pixels = img.load()
        
        idx = 0
        for y in range(height):
            for x in range(width):
                # Read big-endian 16-bit value (for LV_COLOR_16_SWAP=y)
                rgb565 = struct.unpack('>H', data[idx:idx+2])[0]
                idx += 2
                
                # Extract RGB components
                r5 = (rgb565 >> 11) & 0x1F
                g6 = (rgb565 >> 5) & 0x3F
                b5 = rgb565 & 0x1F
                
                # Convert back to 8-bit
                r = (r5 << 3) | (r5 >> 2)
                g = (g6 << 2) | (g6 >> 4)
                b = (b5 << 3) | (b5 >> 2)
                
                pixels[x, y] = (r, g, b)
        
        return img
    
    def display_image(self, img, canvas):
        """Display PIL image on canvas"""
        photo = ImageTk.PhotoImage(img)
        canvas.delete("all")
        canvas.create_image(128, 128, image=photo)
        canvas.image = photo  # Keep reference
    
    def convert_and_copy(self):
        """Write RGB565 data to .bin file and copy to SD card"""
        if not self.rgb565_data:
            messagebox.showerror("Error", "No image converted yet!")
            return
        
        drive = self.drive_var.get()
        if not drive:
            messagebox.showerror("Error", "Please select an SD card drive!")
            return
        
        try:
            self.log("\n🔄 Starting copy operation...")
            self.status_label.config(text="Working...", foreground='orange')
            self.root.update_idletasks()
            
            # Generate output filename from input PNG name
            input_basename = os.path.splitext(os.path.basename(self.selected_image_path))[0]
            output_filename = f"{input_basename}.bin"
            output_file = os.path.join(drive, output_filename)
            self.log(f"  Output filename: {output_filename}")
            self.log(f"  Target: {output_file}")
            
            # Verify drive is writable
            if not os.path.exists(drive):
                raise Exception(f"Drive {drive} not accessible!")
            
            # Write binary file
            self.log("  Writing binary data...")
            with open(output_file, 'wb') as f:
                f.write(self.rgb565_data)
            
            # Verify file
            file_size = os.path.getsize(output_file)
            self.log(f"✓ File written: {file_size} bytes")
            
            if file_size != 131072:
                raise Exception(f"File size mismatch! Expected 131,072 bytes, got {file_size}")
            
            self.log("✓ File size verified (131,072 bytes)")
            self.log(f"✓✓ SUCCESS! Image copied to {output_file}")
            self.log("\n📌 Next steps:")
            self.log("   1. Safely eject SD card from computer")
            self.log("   2. Insert SD card into ESP32-S3")
            self.log("   3. Reboot ESP32 (or enable Pin Mode)")
            self.log("   4. ESP32 will load home_icon.bin from SD card")
            
            self.status_label.config(text="✓ Copy complete!", foreground='green')
            messagebox.showinfo("Success", 
                f"Image successfully copied to {output_file}\n\n"
                "File size: 131,072 bytes\n\n"
                "Next steps:\n"
                "1. Safely eject SD card\n"
                "2. Insert into ESP32-S3\n"
                "3. Reboot to load new image")
            
        except Exception as e:
            self.log(f"❌ Error during copy: {str(e)}")
            self.status_label.config(text="❌ Failed", foreground='red')
            messagebox.showerror("Error", f"Failed to copy file:\n{str(e)}")


def main():
    root = tk.Tk()
    app = PinModeImageUploader(root)
    root.mainloop()


if __name__ == "__main__":
    main()
