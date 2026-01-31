// Background service worker for the extension
let currentAttendanceId = null;
let currentUserId = null;
let apiUrl = null;

// Listen for messages from content script
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.type === 'CAPTURE_SCREENSHOT') {
        startScreenshotCapture(request.attendanceId, request.userId, request.apiUrl);
        sendResponse({status: 'started'});
    } else if (request.type === 'STOP_SCREENSHOT') {
        stopScreenshotCapture();
        sendResponse({status: 'stopped'});
    } else if (request.type === 'SCREENSHOT_CAPTURED') {
        // Screenshot was captured by content script, just acknowledge
        sendResponse({status: 'received'});
    }
    return true; // Keep channel open for async response
});

async function startScreenshotCapture(attendanceId, userId, url) {
    // Stop any existing capture
    stopScreenshotCapture();
    
    currentAttendanceId = attendanceId;
    currentUserId = userId;
    apiUrl = url || 'http://localhost/Task_Management/save_screenshot.php';
    
    // Request screen capture (this will show permission prompt once)
    try {
        // Request screen capture permission (shows dialog once)
        chrome.desktopCapture.chooseDesktopMedia(
            ['screen', 'window'],
            async (streamId) => {
                if (!streamId) {
                    console.error('User cancelled screen capture');
                    return;
                }

                // Send streamId to content script to handle media stream
                // (Service workers can't access getUserMedia directly)
                chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
                    if (tabs[0]) {
                        chrome.tabs.sendMessage(tabs[0].id, {
                            type: 'START_CAPTURE',
                            streamId: streamId,
                            attendanceId: attendanceId,
                            userId: userId,
                            apiUrl: url
                        });
                    }
                });
            }
        );
    } catch (err) {
        console.error('Failed to start screen capture:', err);
    }
}

function stopScreenshotCapture() {
    currentAttendanceId = null;
    currentUserId = null;
    
    // Tell content script to stop
    chrome.tabs.query({active: true, currentWindow: true}, (tabs) => {
        if (tabs[0]) {
            chrome.tabs.sendMessage(tabs[0].id, {
                type: 'STOP_CAPTURE'
            });
        }
    });
}

