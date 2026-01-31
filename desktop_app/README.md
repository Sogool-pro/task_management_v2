# Employee Screenshot Monitor - Desktop Application

A desktop application that runs silently in the background and automatically takes screenshots when employees are timed in. **NO BROWSER PROMPTS NEEDED!**

## Features

- ✅ **Zero prompts** - Takes screenshots automatically without any permission dialogs
- ✅ **Silent operation** - Runs in the background, no visible windows
- ✅ **Automatic detection** - Detects when you're timed in/out automatically
- ✅ **Random intervals** - Takes screenshots at random intervals (30-60 minutes, configurable)
- ✅ **No browser needed** - Works independently of browser

## Installation

### Step 1: Install Python

1. Download Python 3.8+ from https://www.python.org/downloads/
2. During installation, check "Add Python to PATH"
3. Verify installation: Open Command Prompt and type `python --version`

### Step 2: Install Dependencies

Open Command Prompt in the `desktop_app` folder and run:

```bash
pip install -r requirements.txt
```

### Step 3: Configure

Run the setup script to login and save your credentials:

```bash
python setup.py
```

Enter your username and password when prompted. This will save your session cookies.

### Step 4: Run the Monitor

Start the screenshot monitor:

```bash
python screenshot_monitor.py
```

The monitor will:
- Check every 5 seconds if you're timed in
- Take screenshots automatically when timed in
- Send screenshots to your PHP backend
- Run silently in the background

## Running in Background (Windows)

### Option 1: Run as Background Process

```bash
pythonw screenshot_monitor.py
```

This runs Python without a console window.

### Option 2: Create a Windows Service

You can use tools like NSSM (Non-Sucking Service Manager) to run it as a Windows service:

1. Download NSSM from https://nssm.cc/download
2. Install the service:
   ```bash
   nssm install ScreenshotMonitor "C:\Python\python.exe" "C:\path\to\screenshot_monitor.py"
   ```
3. Start the service:
   ```bash
   nssm start ScreenshotMonitor
   ```

### Option 3: Add to Startup

1. Press `Win + R`, type `shell:startup`, press Enter
2. Create a shortcut to `screenshot_monitor.py`
3. Right-click shortcut → Properties → Change "python" to "pythonw" in target

## Configuration

Edit `screenshot_monitor.py` to change:

- `API_BASE_URL` - Your server URL
- `CHECK_INTERVAL` - How often to check attendance status (seconds)
- `SCREENSHOT_INTERVAL_MIN` - Minimum time between screenshots (seconds)
- `SCREENSHOT_INTERVAL_MAX` - Maximum time between screenshots (seconds)

## Troubleshooting

### "No configuration found"
Run `setup.py` first to configure your credentials.

### "Connection refused"
- Check that `API_BASE_URL` is correct
- Make sure your XAMPP server is running
- Check firewall settings

### Screenshots not being sent
- Check that you're logged in (run `setup.py` again)
- Verify your PHP session is still valid
- Check the console output for error messages

## Security Notes

- The `config.json` file contains your session cookies - keep it secure
- Don't share the config file with others
- The application only sends screenshots when you're actively timed in

## Stopping the Monitor

Press `Ctrl+C` in the console, or if running as a service, stop it via Windows Services.

