#!/usr/bin/env python3
"""
Employee Screenshot Monitor - Desktop Application
Runs silently in the background, takes screenshots automatically when timed in.
No browser prompts needed!
"""

import requests
import time
import json
import base64
import io
from datetime import datetime
from PIL import ImageGrab
import sys
import os
import threading
from pathlib import Path

# Configuration
CONFIG_FILE = os.path.join(os.path.dirname(__file__), 'config.json')
API_BASE_URL = 'http://localhost/Task_Management'  # Change this to your server URL
CHECK_INTERVAL = 5  # Check every 5 seconds if employee is timed in
SCREENSHOT_INTERVAL_MIN = 30  # Take screenshot every 30 seconds (for testing)
SCREENSHOT_INTERVAL_MAX = 30  # Maximum interval (for testing)

class ScreenshotMonitor:
    def __init__(self):
        self.user_id = None
        self.session = requests.Session()
        self.is_timed_in = False
        self.attendance_id = None
        self.last_screenshot_time = 0
        self.running = True
        
    def load_config(self):
        """Load user configuration"""
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r') as f:
                    config = json.load(f)
                    self.user_id = config.get('user_id')
                    # Load session cookies if available
                    if 'cookies' in config:
                        self.session.cookies.update(config['cookies'])
                    return True
            except Exception as e:
                print(f"Error loading config: {e}")
        return False
    
    def save_config(self):
        """Save user configuration"""
        config = {
            'user_id': self.user_id,
            'cookies': dict(self.session.cookies)
        }
        try:
            with open(CONFIG_FILE, 'w') as f:
                json.dump(config, f)
        except Exception as e:
            print(f"Error saving config: {e}")
    
    def check_attendance_status(self):
        """Check if employee is currently timed in"""
        try:
            url = f"{API_BASE_URL}/check_attendance.php"
            response = self.session.get(url, timeout=5)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'success' and data.get('has_active_attendance'):
                    self.is_timed_in = True
                    self.attendance_id = data.get('attendance_id')
                    return True
            
            self.is_timed_in = False
            self.attendance_id = None
            return False
        except Exception as e:
            print(f"Error checking attendance: {e}")
            return False
    
    def take_screenshot(self):
        """Take a screenshot and send to server"""
        try:
            # Capture screenshot
            screenshot = ImageGrab.grab()
            
            # Convert to base64
            buffer = io.BytesIO()
            screenshot.save(buffer, format='PNG')
            buffer.seek(0)
            image_data = base64.b64encode(buffer.read()).decode('utf-8')
            data_url = f"data:image/png;base64,{image_data}"
            
            # Send to server
            url = f"{API_BASE_URL}/save_screenshot.php"
            payload = {
                'attendance_id': self.attendance_id,
                'image': data_url
            }
            
            response = self.session.post(url, data=payload, timeout=10)
            
            if response.status_code == 200:
                result = response.json()
                if result.get('status') == 'success':
                    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Screenshot sent successfully")
                    return True
            
            print(f"Failed to send screenshot: {response.text}")
            return False
            
        except Exception as e:
            print(f"Error taking screenshot: {e}")
            return False
    
    def should_take_screenshot(self):
        """Check if it's time to take a screenshot"""
        current_time = time.time()
        elapsed = current_time - self.last_screenshot_time
        
        # Calculate random interval
        import random
        interval = random.uniform(SCREENSHOT_INTERVAL_MIN, SCREENSHOT_INTERVAL_MAX)
        
        if elapsed >= interval:
            self.last_screenshot_time = current_time
            return True
        return False
    
    def run(self):
        """Main monitoring loop"""
        print("Employee Screenshot Monitor Started")
        print(f"API URL: {API_BASE_URL}")
        print(f"Screenshot interval: {SCREENSHOT_INTERVAL_MIN}-{SCREENSHOT_INTERVAL_MAX} seconds")
        print("Press Ctrl+C to stop\n")
        
        # Load config
        if not self.load_config():
            print("No configuration found. Please run setup.py first.")
            return
        
        if not self.user_id:
            print("User ID not configured. Please run setup.py first.")
            return
        
        print(f"Monitoring for user ID: {self.user_id}")
        print("Waiting for time in...\n")
        
        while self.running:
            try:
                # Check if timed in
                was_timed_in = self.is_timed_in
                self.check_attendance_status()
                
                if self.is_timed_in:
                    if not was_timed_in:
                        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Timed in detected!")
                        self.last_screenshot_time = time.time()
                    
                    # Take screenshot if interval has passed
                    if self.should_take_screenshot():
                        self.take_screenshot()
                else:
                    if was_timed_in:
                        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Timed out detected.")
                
                # Wait before next check
                time.sleep(CHECK_INTERVAL)
                
            except KeyboardInterrupt:
                print("\nStopping monitor...")
                self.running = False
                break
            except Exception as e:
                print(f"Error in main loop: {e}")
                time.sleep(CHECK_INTERVAL)
        
        print("Monitor stopped.")

if __name__ == '__main__':
    monitor = ScreenshotMonitor()
    monitor.run()

