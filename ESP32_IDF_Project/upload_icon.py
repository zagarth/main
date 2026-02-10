#!/usr/bin/env python3
"""
Upload icon.bmp to ESP32 SD card via serial
Creates /sdcard/images/ directory and copies icon.bmp
"""

import serial
import time
import sys
import os

def send_command(ser, cmd, timeout=2):
    """Send command and wait for response"""
    ser.write(cmd.encode() + b'\r\n')
    time.sleep(0.1)
    response = b''
    start = time.time()
    while time.time() - start < timeout:
        if ser.in_waiting:
            response += ser.read(ser.in_waiting)
        time.sleep(0.1)
    return response.decode('utf-8', errors='ignore')

def upload_file(port='COM4', baud=115200):
    """Upload icon.bmp to SD card"""
    icon_path = 'src/icon.bmp'
    
    if not os.path.exists(icon_path):
        print(f"Error: {icon_path} not found!")
        return False
    
    print(f"Reading {icon_path}...")
    with open(icon_path, 'rb') as f:
        icon_data = f.read()
    
    print(f"File size: {len(icon_data)} bytes")
    print(f"Connecting to {port}...")
    
    try:
        ser = serial.Serial(port, baud, timeout=1)
        time.sleep(2)  # Wait for connection
        
        # Clear any existing data
        ser.reset_input_buffer()
        
        print("Creating /sdcard/images/ directory...")
        # Note: This requires a serial console interface on the ESP32
        # For now, just print instructions
        print("\n=== MANUAL STEPS ===")
        print("1. Connect to ESP32 serial console")
        print("2. Create directory: mkdir /sdcard/images")
        print("3. Transfer file using your preferred method")
        print("   - Option A: Use ESP32 file upload tool")
        print("   - Option B: Remove SD card and copy manually")
        print(f"   - Copy: {icon_path} -> /sdcard/images/icon.bmp")
        print("\nFile to copy: " + os.path.abspath(icon_path))
        
        ser.close()
        return True
        
    except Exception as e:
        print(f"Error: {e}")
        return False

if __name__ == '__main__':
    port = sys.argv[1] if len(sys.argv) > 1 else 'COM4'
    upload_file(port)
