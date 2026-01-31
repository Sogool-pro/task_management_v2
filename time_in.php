<?php
session_start();
date_default_timezone_set('Asia/Manila');

require 'DB_connection.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'employee') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$today = date('Y-m-d');
$now   = date('H:i:s');

/* -------------------------
   DETERMINE SESSION
-------------------------- */
$hour = (int) date('H');

if ($hour >= 5 && $hour < 12) {
    $session = 'morning';
} elseif ($hour >= 12 && $hour < 18) {
    $session = 'afternoon';
} else {
    $session = 'overtime';
}

/* -------------------------
   GET TODAY ATTENDANCE
-------------------------- */
$sql = "SELECT * FROM attendance 
        WHERE user_id = ? AND att_date = ?
        ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $today]);
$att = $stmt->fetch(PDO::FETCH_ASSOC);

/* -------------------------
   CREATE ATTENDANCE ROW
-------------------------- */
if (!$att) {
    $sql = "INSERT INTO attendance (user_id, att_date, {$session}_in)
            VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $today, $now]);

    echo json_encode([
        'status' => 'success',
        'session' => $session,
        'time_in' => $now,
        'message' => 'Time in recorded'
    ]);
    exit;
}

/* -------------------------
   SESSION ALREADY COMPLETED
   ✅ RETURN SUCCESS (NO UPDATE)
-------------------------- */
if ($att["{$session}_in"] && $att["{$session}_out"]) {
    echo json_encode([
        'status' => 'success',
        'session' => $session,
        'time_in' => $att["{$session}_in"],
        'message' => 'Session already completed (no changes)'
    ]);
    exit;
}

/* -------------------------
   SESSION ALREADY OPEN
   ✅ RETURN SUCCESS (NO UPDATE)
-------------------------- */
if ($att["{$session}_in"] && !$att["{$session}_out"]) {
    echo json_encode([
        'status' => 'success',
        'session' => $session,
        'time_in' => $att["{$session}_in"],
        'message' => 'Session already active'
    ]);
    exit;
}

/* -------------------------
   FIRST TIME-IN FOR SESSION
-------------------------- */
$sql = "UPDATE attendance
        SET {$session}_in = ?
        WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$now, $att['id']]);

echo json_encode([
    'status' => 'success',
    'session' => $session,
    'time_in' => $now,
    'message' => 'Time in recorded'
]);
