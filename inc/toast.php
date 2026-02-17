<?php
// Toast Notification System - Flash message consumer
$_toast_success = '';
$_toast_error = '';

// 1. Check session flash messages (login, etc.)
if (isset($_SESSION['toast_success'])) {
    $_toast_success = $_SESSION['toast_success'];
    unset($_SESSION['toast_success']);
}
if (isset($_SESSION['toast_error'])) {
    $_toast_error = $_SESSION['toast_error'];
    unset($_SESSION['toast_error']);
}

// 2. Check URL params (existing pattern used across the app)
if (isset($_GET['success']) && !empty($_GET['success'])) {
    $_toast_success = htmlspecialchars(stripcslashes($_GET['success']));
    unset($_GET['success']);
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $_toast_error = htmlspecialchars(stripcslashes($_GET['error']));
    unset($_GET['error']);
}
?>

<!-- Toast Container -->
<div id="toast-container"></div>

<style>
/* Toast Notification Styles */
#toast-container {
    position: fixed;
    top: 24px;
    right: 22px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}

.toast-notification {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 12px;
    width: min(350px, calc(100vw - 40px));
    min-height: 64px;
    padding: 12px 14px;
    border-radius: 12px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 16px;
    font-weight: 600;
    color: #0b7a39;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
    border: 1px solid transparent;
    transform: translateX(120%);
    opacity: 0;
    animation: toast-slide-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    cursor: pointer;
    transition: opacity 0.3s ease, transform 0.3s ease;
    position: relative;
    overflow: hidden;
}

.toast-notification.toast-dismiss {
    animation: toast-slide-out 0.35s cubic-bezier(0.55, 0, 1, 0.45) forwards;
}

.toast-notification.toast-success {
    background: #e8f6ee;
    border-color: #b8e8cb;
    color: #118b46;
}

.toast-notification.toast-error {
    background: #fdecec;
    border-color: #f7b9b9;
    color: #b91c1c;
}

.toast-notification.toast-warning {
    background: #fff6e6;
    border-color: #f8d7a3;
    color: #b45309;
}

.toast-icon {
    flex-shrink: 0;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: rgba(17, 24, 39, 0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    color: #fff;
}

.toast-notification.toast-success .toast-icon {
    background: #118b46;
}

.toast-notification.toast-error .toast-icon {
    background: #b91c1c;
}

.toast-notification.toast-warning .toast-icon {
    background: #b45309;
}

.toast-message {
    flex: 1;
    line-height: 1.35;
    font-size: 14px;
    word-break: break-word;
}

.toast-close {
    flex-shrink: 0;
    background: none;
    border: none;
    color: rgba(17, 24, 39, 0.45);
    font-size: 18px;
    cursor: pointer;
    padding: 0 2px;
    line-height: 1;
    transition: color 0.2s;
}
.toast-close:hover {
    color: #111827;
}

/* Progress bar */
.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    border-radius: 0 0 12px 12px;
    background: currentColor;
    opacity: 0.3;
    animation: toast-progress-shrink 4s linear forwards;
}

@keyframes toast-slide-in {
    0% {
        transform: translateX(120%);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes toast-slide-out {
    0% {
        transform: translateX(0);
        opacity: 1;
    }
    100% {
        transform: translateX(120%);
        opacity: 0;
    }
}

@keyframes toast-progress-shrink {
    0% { width: 100%; }
    100% { width: 0%; }
}

/* Mobile responsive */
@media (max-width: 520px) {
    #toast-container {
        top: 12px;
        right: 12px;
        left: 12px;
    }
    .toast-notification {
        max-width: 100%;
        width: 100%;
        min-height: 60px;
        padding: 10px 12px;
    }
    .toast-message {
        font-size: 13px;
    }
}
</style>

<script>
/**
 * Show a toast notification.
 * @param {string} message - The message to display
 * @param {string} type - 'success', 'error', or 'warning'
 * @param {number} duration - Auto-dismiss in ms (default 4000)
 */
function showToast(message, type, duration) {
    type = type || 'success';
    duration = duration || 4000;

    var container = document.getElementById('toast-container');
    if (!container) return;

    var iconMap = {
        success: 'fa-check',
        error: 'fa-times',
        warning: 'fa-exclamation'
    };

    var toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.innerHTML =
        '<div class="toast-icon"><i class="fa ' + (iconMap[type] || 'fa-info') + '"></i></div>' +
        '<span class="toast-message">' + message + '</span>' +
        '<button class="toast-close" aria-label="Close">&times;</button>' +
        '<div class="toast-progress" style="animation-duration:' + duration + 'ms;"></div>';

    container.appendChild(toast);

    // Close on click
    toast.querySelector('.toast-close').addEventListener('click', function() {
        dismissToast(toast);
    });
    toast.addEventListener('click', function(e) {
        if (e.target.classList.contains('toast-close')) return;
        dismissToast(toast);
    });

    // Auto dismiss
    var timer = setTimeout(function() {
        dismissToast(toast);
    }, duration);

    function dismissToast(el) {
        clearTimeout(timer);
        if (el.classList.contains('toast-dismiss')) return;
        el.classList.add('toast-dismiss');
        setTimeout(function() {
            if (el.parentNode) el.parentNode.removeChild(el);
        }, 350);
    }
}

// Auto-trigger toasts from PHP data
(function() {
    <?php if (!empty($_toast_success)) { ?>
    showToast(<?php echo json_encode($_toast_success); ?>, 'success');
    <?php
}?>
    <?php if (!empty($_toast_error)) { ?>
    showToast(<?php echo json_encode($_toast_error); ?>, 'error');
    <?php
}?>
})();
</script>
