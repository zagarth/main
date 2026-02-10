"""
GUI Tool for converting GIF animations to RGB565 binary frames for ESP32
Based on convert_gif_to_bin.py with tkinter interface

Features:
- Select GIF file via file dialog
- Preview animation frames
- Set output directory
- Convert all frames to RGB565 format
- Progress tracking
- Frame info display
"""

import sys
import os
import struct
import tkinter as tk
from tkinter import filedialog, ttk, messagebox
from PIL import Image, ImageTk, ImageDraw
import threading


class GIFConverterGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("GIF to RGB565 Converter for ESP32")
        self.root.geometry("800x700")
        
        self.gif_path = None
        self.gif_image = None
        self.output_dir = None
        self.preview_frames = []
        self.current_preview_frame = 0
        self.animation_running = False
        
        self.setup_ui()
        
    def setup_ui(self):
        """Setup the user interface"""
        # Main container
        main_frame = ttk.Frame(self.root, padding="10")
        main_frame.grid(row=0, column=0, sticky=(tk.W, tk.E, tk.N, tk.S))
        self.root.columnconfigure(0, weight=1)
        self.root.rowconfigure(0, weight=1)
        
        # Title
        title = ttk.Label(main_frame, text="GIF to RGB565 Converter", font=("Arial", 16, "bold"))
        title.grid(row=0, column=0, columnspan=3, pady=10)
        
        # File selection frame
        file_frame = ttk.LabelFrame(main_frame, text="Input GIF", padding="10")
        file_frame.grid(row=1, column=0, columnspan=3, sticky=(tk.W, tk.E), pady=5)
        
        self.file_label = ttk.Label(file_frame, text="No file selected", wraplength=500)
        self.file_label.grid(row=0, column=0, sticky=tk.W, padx=5)
        
        browse_btn = ttk.Button(file_frame, text="Browse GIF...", command=self.browse_gif)
        browse_btn.grid(row=0, column=1, padx=5)
        
        # GIF info frame
        info_frame = ttk.LabelFrame(main_frame, text="GIF Information", padding="10")
        info_frame.grid(row=2, column=0, columnspan=3, sticky=(tk.W, tk.E), pady=5)
        
        self.info_text = tk.Text(info_frame, height=6, width=70, state='disabled', bg="#f0f0f0")
        self.info_text.grid(row=0, column=0, sticky=(tk.W, tk.E))
        
        # Preview frame
        preview_frame = ttk.LabelFrame(main_frame, text="Preview", padding="10")
        preview_frame.grid(row=3, column=0, columnspan=3, sticky=(tk.W, tk.E, tk.N, tk.S), pady=5)
        main_frame.rowconfigure(3, weight=1)
        
        # Canvas for preview
        self.preview_canvas = tk.Canvas(preview_frame, width=256, height=256, bg="black")
        self.preview_canvas.grid(row=0, column=0, columnspan=3, pady=5)
        
        # Preview controls
        self.prev_btn = ttk.Button(preview_frame, text="◀ Previous", command=self.prev_frame, state='disabled')
        self.prev_btn.grid(row=1, column=0, padx=5, pady=5)
        
        self.frame_label = ttk.Label(preview_frame, text="Frame: 0 / 0")
        self.frame_label.grid(row=1, column=1, padx=5, pady=5)
        
        self.next_btn = ttk.Button(preview_frame, text="Next ▶", command=self.next_frame, state='disabled')
        self.next_btn.grid(row=1, column=2, padx=5, pady=5)
        
        self.play_btn = ttk.Button(preview_frame, text="▶ Play Animation", command=self.toggle_animation, state='disabled')
        self.play_btn.grid(row=2, column=0, columnspan=3, pady=5)
        
        # Output directory frame
        output_frame = ttk.LabelFrame(main_frame, text="Output Settings", padding="10")
        output_frame.grid(row=4, column=0, columnspan=3, sticky=(tk.W, tk.E), pady=5)
        
        self.output_label = ttk.Label(output_frame, text="Output directory: Not selected", wraplength=500)
        self.output_label.grid(row=0, column=0, sticky=tk.W, padx=5)
        
        output_btn = ttk.Button(output_frame, text="Choose Output Dir...", command=self.browse_output)
        output_btn.grid(row=0, column=1, padx=5)
        
        # Output filename prefix
        ttk.Label(output_frame, text="Filename prefix:").grid(row=1, column=0, sticky=tk.W, padx=5, pady=5)
        self.prefix_entry = ttk.Entry(output_frame, width=30)
        self.prefix_entry.insert(0, "animation")
        self.prefix_entry.grid(row=1, column=1, sticky=tk.W, padx=5, pady=5)
        
        # Progress frame
        progress_frame = ttk.LabelFrame(main_frame, text="Conversion Progress", padding="10")
        progress_frame.grid(row=5, column=0, columnspan=3, sticky=(tk.W, tk.E), pady=5)
        
        self.progress_bar = ttk.Progressbar(progress_frame, length=600, mode='determinate')
        self.progress_bar.grid(row=0, column=0, sticky=(tk.W, tk.E), padx=5, pady=5)
        
        self.status_label = ttk.Label(progress_frame, text="Ready to convert")
        self.status_label.grid(row=1, column=0, sticky=tk.W, padx=5)
        
        # Convert button
        self.convert_btn = ttk.Button(main_frame, text="Convert to RGB565", command=self.start_conversion, state='disabled')
        self.convert_btn.grid(row=6, column=0, columnspan=3, pady=10)
        
    def browse_gif(self):
        """Open file dialog to select GIF"""
        filename = filedialog.askopenfilename(
            title="Select GIF file",
            filetypes=[("GIF files", "*.gif"), ("All files", "*.*")]
        )
        
        if filename:
            self.gif_path = filename
            self.file_label.config(text=filename)
            self.load_gif_info()
            
            if self.output_dir:
                self.convert_btn.config(state='normal')
    
    def browse_output(self):
        """Open directory dialog for output location"""
        directory = filedialog.askdirectory(title="Select output directory")
        
        if directory:
            self.output_dir = directory
            self.output_label.config(text=f"Output directory: {directory}")
            
            if self.gif_path:
                self.convert_btn.config(state='normal')
    
    def load_gif_info(self):
        """Load and display GIF information"""
        try:
            self.gif_image = Image.open(self.gif_path)
            n_frames = getattr(self.gif_image, 'n_frames', 1)
            
            # Get frame durations
            durations = []
            for frame_idx in range(n_frames):
                self.gif_image.seek(frame_idx)
                duration = self.gif_image.info.get('duration', 100)
                durations.append(duration)
            
            avg_duration = sum(durations) / len(durations) if durations else 100
            fps = 1000 / avg_duration if avg_duration > 0 else 10
            
            info = f"""Filename: {os.path.basename(self.gif_path)}
Size: {self.gif_image.size[0]}x{self.gif_image.size[1]} pixels
Mode: {self.gif_image.mode}
Frames: {n_frames}
Avg Duration: {avg_duration:.1f} ms/frame (~{fps:.1f} FPS)
Output Format: RGB565 (256x256, 128 KB/frame)"""
            
            self.info_text.config(state='normal')
            self.info_text.delete(1.0, tk.END)
            self.info_text.insert(1.0, info)
            self.info_text.config(state='disabled')
            
            # Load preview frames
            self.load_preview_frames(n_frames)
            
        except Exception as e:
            messagebox.showerror("Error", f"Failed to load GIF: {str(e)}")
    
    def load_preview_frames(self, n_frames):
        """Load frames for preview"""
        self.preview_frames = []
        self.gif_image.seek(0)
        
        # Load up to 50 frames for preview (to avoid memory issues)
        max_preview = min(n_frames, 50)
        
        for frame_idx in range(max_preview):
            try:
                self.gif_image.seek(frame_idx)
                frame = self.gif_image.copy()
                
                # Convert to RGB if necessary
                if frame.mode != 'RGB':
                    if frame.mode in ('RGBA', 'LA', 'P'):
                        if frame.mode == 'P':
                            frame = frame.convert('RGBA')
                        background = Image.new('RGBA', frame.size, (0, 0, 0, 255))
                        if frame.mode == 'RGBA':
                            background = Image.alpha_composite(background, frame)
                        frame = background.convert('RGB')
                    else:
                        frame = frame.convert('RGB')
                
                # Resize for preview (256x256) - use BLACK canvas
                if frame.size[0] <= 256 and frame.size[1] <= 256:
                    canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                    x_offset = (256 - frame.size[0]) // 2
                    y_offset = (256 - frame.size[1]) // 2
                    canvas.paste(frame, (x_offset, y_offset))
                    frame = canvas
                else:
                    aspect_ratio = frame.size[0] / frame.size[1]
                    if aspect_ratio > 1:
                        new_width = 256
                        new_height = int(256 / aspect_ratio)
                    else:
                        new_height = 256
                        new_width = int(256 * aspect_ratio)
                    
                    frame = frame.resize((new_width, new_height), Image.Resampling.LANCZOS)
                    canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                    x_offset = (256 - new_width) // 2
                    y_offset = (256 - new_height) // 2
                    canvas.paste(frame, (x_offset, y_offset))
                    frame = canvas
                
                # Apply circular mask with white background
                mask = Image.new('L', (256, 256), 0)
                draw = ImageDraw.Draw(mask)
                draw.ellipse((0, 0, 256, 256), fill=255)
                
                # Create white background and apply mask
                result = Image.new('RGB', (256, 256), (255, 255, 255))
                result.paste(frame, (0, 0), mask)
                frame = result
                
                # Convert to PhotoImage for tkinter
                photo = ImageTk.PhotoImage(frame)
                self.preview_frames.append(photo)
                
            except EOFError:
                break
        
        self.current_preview_frame = 0
        self.show_preview_frame()
        
        # Enable controls
        self.prev_btn.config(state='normal')
        self.next_btn.config(state='normal')
        self.play_btn.config(state='normal')
    
    def show_preview_frame(self):
        """Display current preview frame"""
        if self.preview_frames:
            self.preview_canvas.delete("all")
            self.preview_canvas.create_image(128, 128, image=self.preview_frames[self.current_preview_frame])
            self.frame_label.config(text=f"Frame: {self.current_preview_frame + 1} / {len(self.preview_frames)}")
    
    def prev_frame(self):
        """Show previous frame"""
        if self.preview_frames:
            self.current_preview_frame = (self.current_preview_frame - 1) % len(self.preview_frames)
            self.show_preview_frame()
    
    def next_frame(self):
        """Show next frame"""
        if self.preview_frames:
            self.current_preview_frame = (self.current_preview_frame + 1) % len(self.preview_frames)
            self.show_preview_frame()
    
    def toggle_animation(self):
        """Start/stop animation preview"""
        self.animation_running = not self.animation_running
        
        if self.animation_running:
            self.play_btn.config(text="⏸ Pause Animation")
            self.animate_preview()
        else:
            self.play_btn.config(text="▶ Play Animation")
    
    def animate_preview(self):
        """Animate preview frames"""
        if self.animation_running and self.preview_frames:
            self.next_frame()
            # Use 100ms delay for preview animation
            self.root.after(100, self.animate_preview)
    
    def start_conversion(self):
        """Start conversion in background thread"""
        self.convert_btn.config(state='disabled')
        self.progress_bar['value'] = 0
        self.status_label.config(text="Starting conversion...")
        
        # Run conversion in separate thread
        thread = threading.Thread(target=self.convert_gif)
        thread.daemon = True
        thread.start()
    
    def convert_gif(self):
        """Convert GIF to RGB565 binary frames"""
        try:
            prefix = self.prefix_entry.get()
            output_prefix = os.path.join(self.output_dir, prefix)
            
            with Image.open(self.gif_path) as gif:
                n_frames = getattr(gif, 'n_frames', 1)
                
                # Get frame durations
                durations = []
                for frame_idx in range(n_frames):
                    gif.seek(frame_idx)
                    duration = gif.info.get('duration', 100)
                    durations.append(duration)
                
                gif.seek(0)
                frame_files = []
                
                for frame_idx in range(n_frames):
                    try:
                        gif.seek(frame_idx)
                        frame = gif.copy()
                        
                        # Update progress
                        progress = (frame_idx / n_frames) * 100
                        self.root.after(0, lambda p=progress, i=frame_idx, n=n_frames: 
                                       self.update_progress(p, f"Converting frame {i+1}/{n}..."))
                        
                        # Handle transparency - composite on BLACK first (matches CLI script)
                        if frame.mode in ('RGBA', 'LA', 'P'):
                            if frame.mode == 'P':
                                frame = frame.convert('RGBA')
                            background = Image.new('RGBA', frame.size, (0, 0, 0, 255))
                            if frame.mode == 'RGBA':
                                background = Image.alpha_composite(background, frame)
                            frame = background.convert('RGB')
                        elif frame.mode != 'RGB':
                            frame = frame.convert('RGB')
                        
                        # Handle sizing - use BLACK canvas initially (matches CLI script)
                        if frame.size[0] <= 256 and frame.size[1] <= 256:
                            canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                            x_offset = (256 - frame.size[0]) // 2
                            y_offset = (256 - frame.size[1]) // 2
                            canvas.paste(frame, (x_offset, y_offset))
                            frame = canvas
                        else:
                            aspect_ratio = frame.size[0] / frame.size[1]
                            if aspect_ratio > 1:
                                new_width = 256
                                new_height = int(256 / aspect_ratio)
                            else:
                                new_height = 256
                                new_width = int(256 * aspect_ratio)
                            
                            frame = frame.resize((new_width, new_height), Image.Resampling.LANCZOS)
                            canvas = Image.new('RGB', (256, 256), (0, 0, 0))
                            x_offset = (256 - new_width) // 2
                            y_offset = (256 - new_height) // 2
                            canvas.paste(frame, (x_offset, y_offset))
                            frame = canvas
                        
                        # Apply circular mask with white background (only affects outside the circle)
                        mask = Image.new('L', (256, 256), 0)
                        draw = ImageDraw.Draw(mask)
                        draw.ellipse((0, 0, 256, 256), fill=255)
                        
                        # Create white background and apply mask
                        result = Image.new('RGB', (256, 256), (255, 255, 255))
                        result.paste(frame, (0, 0), mask)
                        frame = result
                        
                        # Convert to RGB565
                        pixels = frame.load()
                        rgb565_bytes = bytearray()
                        
                        for y in range(256):
                            for x in range(256):
                                r, g, b = pixels[x, y]
                                
                                # Convert to 5-6-5 bit format
                                r5 = (r >> 3) & 0x1F
                                g6 = (g >> 2) & 0x3F
                                b5 = (b >> 3) & 0x1F
                                
                                # Pack into 16-bit value (BGR565)
                                rgb565 = (b5 << 11) | (g6 << 5) | r5
                                
                                # Write as little-endian
                                rgb565_bytes.extend(struct.pack('<H', rgb565))
                        
                        output_path = f"{output_prefix}_{frame_idx:03d}.bin"
                        with open(output_path, 'wb') as f:
                            f.write(rgb565_bytes)
                        
                        frame_files.append(output_path)
                        
                    except EOFError:
                        break
                
                # Write info file
                info_path = f"{output_prefix}_info.txt"
                with open(info_path, 'w') as f:
                    f.write(f"# GIF Animation Info\n")
                    f.write(f"# Source: {self.gif_path}\n")
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
                        f.write(f"  {os.path.basename(fname)}\n")
                
                # Conversion complete
                self.root.after(0, lambda: self.conversion_complete(len(frame_files), info_path))
                
        except Exception as e:
            self.root.after(0, lambda: self.conversion_error(str(e)))
    
    def update_progress(self, value, status):
        """Update progress bar and status"""
        self.progress_bar['value'] = value
        self.status_label.config(text=status)
    
    def conversion_complete(self, n_frames, info_path):
        """Called when conversion is complete"""
        self.progress_bar['value'] = 100
        self.status_label.config(text=f"✓ Conversion complete! Created {n_frames} frame files")
        self.convert_btn.config(state='normal')
        
        messagebox.showinfo("Success", 
                          f"Conversion complete!\n\n"
                          f"Created {n_frames} frame files\n"
                          f"Output directory: {self.output_dir}\n"
                          f"Info file: {os.path.basename(info_path)}")
    
    def conversion_error(self, error):
        """Called when conversion fails"""
        self.status_label.config(text=f"✗ Error: {error}")
        self.convert_btn.config(state='normal')
        messagebox.showerror("Conversion Error", f"Failed to convert GIF:\n{error}")


def main():
    root = tk.Tk()
    app = GIFConverterGUI(root)
    root.mainloop()


if __name__ == '__main__':
    main()
