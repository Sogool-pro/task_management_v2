<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require 'DB_connection.php';

$user_id = $_SESSION['id'];

// Check if there is an open attendance (no time_out yet)
date_default_timezone_set('Asia/Manila');
$hour = (int) date('H');

if ($hour >= 5 && $hour < 12) {
    $session = 'morning';
} elseif ($hour >= 12 && $hour < 18) {
    $session = 'afternoon';
} else {
    $session = 'overtime';
}

// Check if there is an open attendance for the CURRENT session
$sql = "SELECT id FROM attendance 
        WHERE user_id = ? 
        AND att_date = CURRENT_DATE 
        AND {$session}_in IS NOT NULL 
        AND {$session}_out IS NULL 
        LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

if ($attendance) {
    echo json_encode([
        'status' => 'success',
        'has_active_attendance' => true,
        'attendance_id' => $attendance['id']
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'has_active_attendance' => false
    ]);
}

