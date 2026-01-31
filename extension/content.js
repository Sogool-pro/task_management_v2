// Content script that communicates with the webpage
(function() {
    'use strict';

    let screenshotInterval = null;
    let mediaStream = null;
    let currentAttendanceId = null;
    let currentUserId = null;
    let apiUrl = null;

    // Listen for messages from the webpage
    window.addEventListener('message', function(event) {
        // Only accept messages from same origin
        if (event.origin !== window.location.origin) return;

        if (event.data.type === 'REQUEST_SCREENSHOT') {
            // Forward request to background script
            chrome.runtime.sendMessage({
                type: 'CAPTURE_SCREENSHOT',
                attendanceId: event.data.attendanceId,
                userId: event.data.userId,
                apiUrl: event.data.apiUrl
            });
        } else if (event.data.type === 'STOP_SCREENSHOT') {
            chrome.runtime.sendMessage({
                type: 'STOP_SCREENSHOT'
            });
        }
    });

    // Listen for messages from background script
    chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
        if (request.type === 'START_CAPTURE') {
            startCapture(request.streamId, request.attendanceId, request.userId, request.apiUrl);
            sendResponse({status: 'started'});
        } else if (request.type === 'STOP_CAPTURE') {
            stopCapture();
            sendResponse({status: 'stopped'});
        }
        return true;
    });

    async function startCapture(streamId, attendanceId, userId, url) {
        stopCapture(); // Stop any existing capture
        
        currentAttendanceId = attendanceId;
        currentUserId = userId;
        apiUrl = url;

        try {
            // Get media stream using the streamId
            mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    mandatory: {
                        chromeMediaSource: 'desktop',
                        chromeMediaSourceId: streamId
                    }
                }
            });

            // Start taking screenshots at intervals (30 seconds for testing)
            const MIN_INTERVAL = 30 * 1000; // 30 seconds
            const MAX_INTERVAL = 30 * 1000; // 30 seconds
            
            function takeScreenshot() {
                if (!currentAttendanceId) {
                    stopCapture();
                    return;
                }
                captureAndSend();
                const delay = MIN_INTERVAL + Math.random() * (MAX_INTERVAL - MIN_INTERVAL);
                screenshotInterval = setTimeout(takeScreenshot, delay);
            }
            
            // Start immediately, then schedule next
            takeScreenshot();
        } catch (err) {
            console.error('Failed to start capture:', err);
        }
    }

    function stopCapture() {
        if (screenshotInterval) {
            clearTimeout(screenshotInterval);
            screenshotInterval = null;
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
        currentAttendanceId = null;
        currentUserId = null;
    }

    async function captureAndSend() {
        if (!mediaStream || !currentAttendanceId) return;

        try {
            const videoTrack = mediaStream.getVideoTracks()[0];
            const imageCapture = new ImageCapture(videoTrack);
            const bitmap = await imageCapture.grabFrame();
            
            // Convert to canvas
            const canvas = document.createElement('canvas');
            canvas.width = bitmap.width;
            canvas.height = bitmap.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(bitmap, 0, 0);
            
            // Convert to base64
            const dataUrl = canvas.toDataURL('image/png');
            
            // Send to PHP backend
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `attendance_id=${encodeURIComponent(currentAttendanceId)}&image=${encodeURIComponent(dataUrl)}`,
                credentials: 'include' // Include cookies for session
            }).catch(err => {
                console.error('Failed to send screenshot:', err);
            });
        } catch (err) {
            console.error('Failed to capture screenshot:', err);
        }
    }

    // Inject script to detect if extension is available
    const script = document.createElement('script');
    script.textContent = `
        (function() {
            window.screenshotExtensionAvailable = true;
            window.dispatchEvent(new Event('screenshotExtensionReady'));
        })();
    `;
    (document.head || document.documentElement).appendChild(script);
    script.remove();
})();

