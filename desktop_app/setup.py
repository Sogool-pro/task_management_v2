#!/usr/bin/env python3
"""
Setup script for Employee Screenshot Monitor
Helps configure the application with user credentials
"""

import requests
import json
import os
import getpass

CONFIG_FILE = os.path.join(os.path.dirname(__file__), 'config.json')
API_BASE_URL = 'http://localhost/Task_Management'  # Change this to your server URL

def login():
    """Login and get session"""
    print("Employee Screenshot Monitor - Setup")
    print("=" * 40)
    print()
    
    username = input("Enter your username: ")
    password = getpass.getpass("Enter your password: ")
    
    session = requests.Session()
    
    try:
        # Login
        login_url = f"{API_BASE_URL}/app/login.php"
        
        # Login form uses 'user_name' and 'password'
        login_data = {
            'user_name': username,
            'password': password
        }
        
        response = session.post(login_url, data=login_data, allow_redirects=True)
        
        # Check if login was successful - check if we got redirected to index.php
        if 'index.php' in response.url or response.status_code == 200:
            # Try to get user ID from check_attendance endpoint
            check_url = f"{API_BASE_URL}/check_attendance.php"
            check_response = session.get(check_url)
            
            if check_response.status_code == 200:
                try:
                    data = check_response.json()
                    if data.get('status') == 'success':
                        print("\n✓ Login successful!")
                        
                        # Try to get user ID from session (we'll detect it automatically)
                        # Save configuration
                        config = {
                            'user_id': None,  # Will be detected automatically from session
                            'username': username,
                            'cookies': dict(session.cookies)
                        }
                        
                        with open(CONFIG_FILE, 'w') as f:
                            json.dump(config, f, indent=2)
                        
                        print(f"✓ Configuration saved to {CONFIG_FILE}")
                        print("\nYou can now run screenshot_monitor.py")
                        return True
                except:
                    pass
            
            print("✗ Could not verify login. Please check your credentials.")
            return False
        else:
            print("✗ Login failed. Please check your credentials.")
            return False
            
    except Exception as e:
        print(f"✗ Error during login: {e}")
        return False

if __name__ == '__main__':
    if login():
        print("\nSetup completed successfully!")
    else:
        print("\nSetup failed. Please try again.")

