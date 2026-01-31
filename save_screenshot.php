<?php
session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require 'DB_connection.php';

$user_id = $_SESSION['id'];
$attendance_id = isset($_POST['attendance_id']) ? (int) $_POST['attendance_id'] : null;

// DEBUG LOGGING
$logFile = __DIR__ . '/screenshot_debug.log';
$logEntry = date('Y-m-d H:i:s') . " - Request from User: " . ($user_id ?? 'Unknown') . " - Attendance: " . ($attendance_id ?? 'None') . "\n";

if (!isset($_SESSION['id'])) {
    $logEntry .= "Error: Unauthorized (No Session)\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$imageData = $_POST['image'] ?? '';
if (empty($imageData)) {
    $logEntry .= "Error: No image data\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'No image data provided']);
    exit;
}

// Expect "data:image/png;base64,...."
if (strpos($imageData, 'base64,') !== false) {
    $imageData = substr($imageData, strpos($imageData, 'base64,') + 7);
}

$binary = base64_decode($imageData);
if ($binary === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to decode image']);
    exit;
}

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'screenshots';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Check if there's already a screenshot for this attendance_id
$oldScreenshot = null;
$oldScreenshots = [];
if ($attendance_id) {
    // Get all existing screenshots for this attendance_id
    $sql_check = "SELECT id, image_path FROM screenshots WHERE attendance_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$attendance_id]);
    $oldScreenshots = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the latest one to reuse filename
    if (!empty($oldScreenshots)) {
        $oldScreenshot = $oldScreenshots[0]; // Use first one for filename
    }
}

// Use existing filename if updating, otherwise create new filename
if ($oldScreenshot && $oldScreenshot['image_path']) {
    // Update existing screenshot - use the same filename
    $relativePath = $oldScreenshot['image_path'];
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $relativePath;
    
    // Delete all old screenshot files for this attendance_id
    foreach ($oldScreenshots as $old) {
        if (!empty($old['image_path'])) {
            $oldFilePath = __DIR__ . DIRECTORY_SEPARATOR . $old['image_path'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
    }
} else {
    // Create new screenshot filename
    $filenameOnly = $user_id . '_' . ($attendance_id ? $attendance_id : time()) . '.png';
    $relativePath = 'screenshots/' . $filenameOnly;
    $fullPath = $dir . DIRECTORY_SEPARATOR . $filenameOnly;
}

// Save the new screenshot
// Save the new screenshot
if (file_put_contents($fullPath, $binary) === false) {
    $logEntry .= "Error: Failed to write file to $fullPath\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image']);
    exit;
}

$logEntry .= "Success: Saved to $fullPath\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Delete all old screenshot records for this attendance_id, then insert new one
if ($attendance_id && !empty($oldScreenshots)) {
    // Delete all old screenshot records for this attendance_id
    $sql_delete = "DELETE FROM screenshots WHERE attendance_id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$attendance_id]);
}

// Insert new screenshot record (always insert, even if updating - keeps it simple)
$sql = "INSERT INTO screenshots (user_id, attendance_id, image_path, taken_at)
        VALUES (?, ?, ?, NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $attendance_id ?: null, $relativePath]);

echo json_encode(['status' => 'success']);


