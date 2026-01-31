# Employee Screenshot Monitor - Browser Extension

## Installation Instructions

### For Chrome/Edge:

1. Open Chrome/Edge browser
2. Go to `chrome://extensions/` (or `edge://extensions/`)
3. Enable "Developer mode" (toggle in top right)
4. Click "Load unpacked"
5. Select the `extension` folder
6. The extension will be installed

### For Firefox:

1. Open Firefox
2. Go to `about:debugging`
3. Click "This Firefox"
4. Click "Load Temporary Add-on"
5. Select the `manifest.json` file in the extension folder

## How It Works

1. **One-time setup**: Employee installs the extension once
2. **Permission**: Extension requests screen capture permission once
3. **Automatic**: When employee clicks "Time In" on your website, extension automatically starts capturing screenshots
4. **Silent**: After initial permission, screenshots happen automatically without prompts

## Integration with Your Website

You need to update your `index.php` to detect and use the extension. The extension listens for messages from your webpage.

## Notes

- Extension needs to be installed by each employee
- Screen capture permission is requested once
- Works silently after initial setup
- Screenshots are sent to your PHP backend automatically

