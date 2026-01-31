<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id'])) {

    include "DB_connection.php";
    include "app/Model/Task.php";
    include "app/Model/User.php";

    if ($_SESSION['role'] == "admin") {
        $todaydue_task = count_tasks_due_today($pdo);
        $overdue_task = count_tasks_overdue($pdo);
        $nodeadline_task = count_tasks_NoDeadline($pdo);
        $num_task = count_tasks($pdo);
        $num_users = count_users($pdo);
        $pending = count_pending_tasks($pdo);
        $in_progress = count_in_progress_tasks($pdo);
        $completed = count_completed_tasks($pdo);
        // Count total screenshots
        $sql_screenshots = "SELECT COUNT(*) as total FROM screenshots";
        $stmt_screenshots = $pdo->query($sql_screenshots);
        $result_screenshots = $stmt_screenshots->fetch(PDO::FETCH_ASSOC);
        $num_screenshots = $result_screenshots['total'] ?? 0;
    } else {
        $num_my_task = count_my_tasks($pdo, $_SESSION['id']);
        $overdue_task = count_my_tasks_overdue($pdo, $_SESSION['id']);
        $nodeadline_task = count_my_tasks_NoDeadline($pdo, $_SESSION['id']);
        $pending = count_my_pending_tasks($pdo, $_SESSION['id']);
        $in_progress = count_my_in_progress_tasks($pdo, $_SESSION['id']);
        $completed = count_my_completed_tasks($pdo, $_SESSION['id']);
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-link {
            text-decoration: none;
            color: inherit;
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 20px;
        }

        .dashboard-link .dashboard-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dashboard-link:hover .dashboard-item {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        
        .dashboard-link:hover {
            cursor: pointer;
        }
        
        /* Ensure the entire dashboard item area is clickable */
        .dashboard-link,
        .dashboard-link * {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <input type="checkbox" id="checkbox">
    <?php include "inc/header.php" ?>
    <div class="body">
        <?php include "inc/nav.php" ?>
        <section class="section-1">
            <?php if ($_SESSION['role'] == "admin") { ?>
                <div class="dashboard">
                    <a href="user.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-users"></i>
                            <span><?=$num_users?> Employee</span>
                        </div>
                    </a>

                    <a href="tasks.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-tasks"></i>
                            <span><?=$num_task?> All Tasks</span>
                        </div>
                    </a>

                    <a href="tasks.php?due_date=Overdue" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-window-close-o"></i>
                            <span><?=$overdue_task?> Overdue</span>
                        </div>
                    </a>

                    <a href="tasks.php?due_date=No Deadline" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-clock-o"></i>
                            <span><?=$nodeadline_task?> No Deadline</span>
                        </div>
                    </a>

                    <a href="tasks.php?due_date=Due Today" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span><?=$todaydue_task?> Due Today</span>
                        </div>
                    </a>

                    <a href="notifications.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-bell"></i>
                            <span><?=$overdue_task?> Notifications</span>
                        </div>
                    </a>

                    <a href="tasks.php?status=Pending" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-square-o"></i>
                            <span><?=$pending?> Pending</span>
                        </div>
                    </a>

                    <a href="tasks.php?status=in_progress" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-spinner"></i>
                            <span><?=$in_progress?> In progress</span>
                        </div>
                    </a>

                    <a href="tasks.php?status=Completed" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-check-square-o"></i>
                            <span><?=$completed?> Completed</span>
                        </div>
                    </a>

                    <a href="screenshots.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-camera"></i>
                            <span><?=$num_screenshots?> Screenshots</span>
                        </div>
                    </a>
                </div>
            <?php } else { ?>
                <div class="dashboard">
                    <div style="margin-bottom: 20px;">
                        <button id="btnTimeIn">Time In</button>
                        <button id="btnTimeOut" disabled>Time Out</button>
                        <span id="attendanceStatus"></span>
                    </div>
                    <a href="my_task.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-tasks"></i>
                            <span><?=$num_my_task?> My Tasks</span>
                        </div>
                    </a>

                    <a href="my_tasks_overdue.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-window-close-o"></i>
                            <span><?=$overdue_task?> Overdue</span>
                        </div>
                    </a>

                    <a href="my_tasks_nodeadline.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-clock-o"></i>
                            <span><?=$nodeadline_task?> No Deadline</span>
                        </div>
                    </a>

                    <a href="my_tasks_pending.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-square-o"></i>
                            <span><?=$pending?> Pending</span>
                        </div>
                    </a>

                    <a href="my_tasks_in_progress.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-spinner"></i>
                            <span><?=$in_progress?> In progress</span>
                        </div>
                    </a>

                    <a href="my_tasks_completed.php" class="dashboard-link">
                        <div class="dashboard-item">
                            <i class="fa fa-check-square-o"></i>
                            <span><?=$completed?> Completed</span>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </section>
    </div>

<script type="text/javascript">
    var active = document.querySelector("#navList li:nth-child(1)");
    if (active) {
        active.classList.add("active");
    }

    // Store user ID from PHP session
    var currentUserId = <?= isset($_SESSION['id']) ? $_SESSION['id'] : 'null' ?>;

    // Attendance + Screenshot logic (employees)
    const btnIn = document.getElementById('btnTimeIn');
    const btnOut = document.getElementById('btnTimeOut');
    const statusSpan = document.getElementById('attendanceStatus');
    let attendanceId = null;
    let screenshotTimerId = null;
    let mediaStream = null;
    let isTimingOut = false; // Flag to prevent multiple simultaneous time out calls

    function ajax(url, data, cb, method) {
        var xhr = new XMLHttpRequest();
        var useMethod = method || 'POST';
        xhr.open(useMethod, url, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        cb(JSON.parse(xhr.responseText));
                    } catch (e) {
                        cb({status: 'error', message: 'Invalid JSON response', raw: xhr.responseText});
                    }
                } else {
                    cb({status: 'error', message: 'Network error', status: xhr.status, raw: xhr.responseText});
                }
            }
        };
        if (useMethod === 'POST') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(data);
        } else {
            xhr.send();
        }
    }

    // Check if extension is available
    var extensionAvailable = false;
    window.addEventListener('screenshotExtensionReady', function() {
        extensionAvailable = true;
        console.log('Screenshot extension detected');
    });

    // Check for extension after page load
    setTimeout(function() {
        if (window.screenshotExtensionAvailable) {
            extensionAvailable = true;
        }
    }, 1000);

    if (btnIn) {
        btnIn.addEventListener('click', async function () {
            // Check if already timed in (restored from page load)
            var isAlreadyTimedIn = attendanceId !== null;
            
            // Check if extension is available
            if (extensionAvailable || window.screenshotExtensionAvailable) {
                // If not already timed in, do time in first
                if (!isAlreadyTimedIn) {
                    ajax('time_in.php', '', function (res) {
                        if (res.status === 'success') {
                            attendanceId = res.attendance_id || null;
                            statusSpan.textContent = 'Timed in. Extension will handle screenshots automatically.';
                            btnIn.disabled = true;
                            btnOut.disabled = false;
                            
                            // Tell extension to start capturing
                            window.postMessage({
                                type: 'REQUEST_SCREENSHOT',
                                attendanceId: attendanceId,
                                userId: currentUserId,
                                apiUrl: window.location.origin + window.location.pathname.replace('index.php', 'save_screenshot.php')
                            }, window.location.origin);
                        } else {
                            statusSpan.textContent = res.message || 'Error during time in';
                        }
                    });
                } else {
                    // Already timed in, just start extension
                    statusSpan.textContent = 'Starting screen capture...';
                    window.postMessage({
                        type: 'REQUEST_SCREENSHOT',
                        attendanceId: attendanceId,
                        userId: currentUserId,
                        apiUrl: window.location.origin + window.location.pathname.replace('index.php', 'save_screenshot.php')
                    }, window.location.origin);
                    btnIn.disabled = true;
                }
            } else {
                // Fallback to browser screen share - request permission when Time In is pressed
                statusSpan.textContent = 'Requesting screen access...';
                var stream = await requestScreenShare();
                
                if (!stream) {
                    statusSpan.textContent = 'Screen access denied. Please allow to continue.';
                    return;
                }
                
                // IMPORTANT: Stream is now stored in mediaStream variable globally
                // It will be reused for ALL subsequent screenshots without asking again
                console.log('Screen share granted. Stream stored. Will reuse for all screenshots.');
                
                // If not already timed in, do time in first
                if (!isAlreadyTimedIn) {
                    ajax('time_in.php', '', function (res) {
                        if (res.status === 'success') {
                            attendanceId = res.attendance_id || null;
                            statusSpan.textContent = 'Timed in. Screenshots will be taken automatically.';
                            btnIn.disabled = true;
                            btnOut.disabled = false;
                            // Start screenshot loop - mediaStream is already stored globally, will be reused
                            startScreenshotLoop();
                        } else {
                            statusSpan.textContent = res.message || 'Error during time in';
                            // Stop stream if time in failed
                            if (mediaStream) {
                                mediaStream.getTracks().forEach(function (t) { t.stop(); });
                                mediaStream = null;
                            }
                        }
                    });
                } else {
                    // Already timed in, just start screenshot loop
                    statusSpan.textContent = 'Timed in. Screenshots will be taken automatically.';
                    btnIn.disabled = true;
                    startScreenshotLoop();
                }
            }
        });
    }

    // Helper function to handle time out (used by both manual and automatic time out)
    function performTimeOut() {
        if (!attendanceId || isTimingOut) return; // Already timed out, not timed in, or already timing out
        
        isTimingOut = true; // Set flag to prevent multiple simultaneous calls
        
        ajax('time_out.php', '', function (res) {
            isTimingOut = false; // Reset flag
            
            if (res.status === 'success') {
                statusSpan.textContent = 'Timed out.';
                btnIn.disabled = false;
                btnOut.disabled = true;
                attendanceId = null; // Clear attendance ID
                
                // Stop extension if it's running
                if (extensionAvailable || window.screenshotExtensionAvailable) {
                    window.postMessage({
                        type: 'STOP_SCREENSHOT'
                    }, window.location.origin);
                }
                
                stopScreenshotLoop();
            } else {
                statusSpan.textContent = res.message || 'Error during time out';
            }
        });
    }

    if (btnOut) {
        btnOut.addEventListener('click', function () {
            performTimeOut();
        });
    }

    // Check for active attendance on page load - only restore UI state, don't request screen share
    if (btnIn && btnOut) {
        ajax('check_attendance.php', '', function (res) {
            if (res.status === 'success' && res.has_active_attendance) {
                attendanceId = res.attendance_id || null;
                statusSpan.textContent = 'Timed in (restored). Please press Time In again to start screen sharing.';
                btnIn.disabled = false; // Allow user to press Time In again to start screen sharing
                btnOut.disabled = false;
                // Don't request screen share automatically - user must press Time In button
            }
        }, 'GET');
    }

    const MIN_INTERVAL_SEC = 25; // minimum seconds between screenshots (random around 30 seconds)
    const MAX_INTERVAL_SEC = 35; // maximum seconds between screenshots (random around 30 seconds)

    async function requestScreenShare() {
        // If stream already exists and is active, return it immediately
        if (mediaStream) {
            var videoTrack = mediaStream.getVideoTracks()[0];
            if (videoTrack && videoTrack.readyState === 'live') {
                return mediaStream; // Stream is still active, reuse it
            } else {
                // Stream ended, clear it
                console.log('Stream ended, clearing...');
                mediaStream = null;
            }
        }

        // Only request new stream if we don't have one
        try {
            console.log('Requesting new screen share...');
            mediaStream = await navigator.mediaDevices.getDisplayMedia({
                video: { cursor: "always" },
                audio: false
            });
            
            // Listen for when user stops sharing manually
            var videoTrack = mediaStream.getVideoTracks()[0];
            videoTrack.addEventListener('ended', function() {
                console.log('User stopped screen sharing');
                mediaStream = null;
                stopScreenshotLoop();
                if (statusSpan) {
                     statusSpan.textContent = 'Screen sharing stopped. You are still timed in.';
                }
                // Do NOT automatically time out. Allow user to re-enable or time out manually.
            });
            
            console.log('Screen share granted, stream active');
            return mediaStream;
        } catch (e) {
            console.error('Screen share denied', e);
            if (statusSpan) {
                statusSpan.textContent = 'Screen share denied. Please allow to continue.';
            }
            return null;
        }
    }

    function getRandomDelayMs() {
        var minMs = MIN_INTERVAL_SEC * 1000; // convert seconds to milliseconds
        var maxMs = MAX_INTERVAL_SEC * 1000; // convert seconds to milliseconds
        return minMs + Math.random() * (maxMs - minMs);
    }

    async function takeScreenshotOnce() {
        // Don't take screenshots if not timed in (no attendanceId)
        if (!attendanceId) {
            stopScreenshotLoop();
            return;
        }

        // Use existing stream - don't request new one unless it's actually ended
        if (!mediaStream) {
            console.log('No active stream, cannot take screenshot');
            stopScreenshotLoop();
            if (statusSpan) {
                 statusSpan.textContent = 'Screen sharing stopped. You are still timed in.';
            }
            return;
        }

        // Check if stream is still active
        var videoTrack = mediaStream.getVideoTracks()[0];
        if (!videoTrack || videoTrack.readyState !== 'live') {
            console.log('Stream is not live, stopping...');
            mediaStream = null;
            stopScreenshotLoop();
            if (statusSpan) {
                 statusSpan.textContent = 'Screen sharing stopped. You are still timed in.';
            }
            return;
        }

        // Stream is active, use it for screenshot (reusing the same stream - no new permission needed)
        console.log('Taking screenshot using existing stream (no permission prompt)');
        var stream = mediaStream;
        var videoTrack = stream.getVideoTracks()[0];
        if (!window.ImageCapture) {
            console.error('ImageCapture API not supported in this browser.');
            return;
        }
        var imageCapture = new ImageCapture(videoTrack);

        try {
            var bitmap = await imageCapture.grabFrame();
            var canvas = document.createElement('canvas');
            canvas.width = bitmap.width;
            canvas.height = bitmap.height;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(bitmap, 0, 0);
            var dataUrl = canvas.toDataURL('image/png');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_screenshot.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(
                'attendance_id=' + encodeURIComponent(attendanceId || '') +
                '&image=' + encodeURIComponent(dataUrl)
            );
        } catch (e) {
            console.error('Screenshot failed', e);
        }
    }

    function scheduleNextScreenshot() {
        var delay = getRandomDelayMs();
        screenshotTimerId = setTimeout(async function () {
            await takeScreenshotOnce();
            scheduleNextScreenshot();
        }, delay);
    }

    function startScreenshotLoop() {
        if (screenshotTimerId) return;
        scheduleNextScreenshot();
    }

    function stopScreenshotLoop() {
        if (screenshotTimerId) {
            clearTimeout(screenshotTimerId);
            screenshotTimerId = null;
        }
        if (mediaStream) {
            mediaStream.getTracks().forEach(function (t) { t.stop(); });
            mediaStream = null;
        }
    }
</script>
</body>
</html>

<?php 
} else { 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
?>