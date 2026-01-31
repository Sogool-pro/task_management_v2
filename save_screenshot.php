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
// If attendance_id not provided, attempt to find today's attendance row for this user
if (!$attendance_id) {
    $sql_find = "SELECT id FROM attendance WHERE user_id = ? AND att_date = CURRENT_DATE LIMIT 1";
    $stmt_find = $pdo->prepare($sql_find);
    $stmt_find->execute([$user_id]);
    $found = $stmt_find->fetch(PDO::FETCH_ASSOC);
    if ($found) $attendance_id = $found['id'];
}
$imageData = $_POST['image'] ?? '';

if (empty($imageData)) {
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
if (file_put_contents($fullPath, $binary) === false) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image']);
    exit;
}

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


