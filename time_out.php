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
$today   = date('Y-m-d');
$now     = date('H:i:s');

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
   NO ATTENDANCE → SUCCESS
-------------------------- */
if (!$att) {
    echo json_encode([
        'status' => 'success',
        'message' => 'No active session'
    ]);
    exit;
}

/* -------------------------
   CLOSE OPEN SESSION
-------------------------- */
if ($att['morning_in'] && !$att['morning_out']) {
    $sql = "UPDATE attendance SET morning_out = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$now, $att['id']]);

} elseif ($att['afternoon_in'] && !$att['afternoon_out']) {
    $sql = "UPDATE attendance SET afternoon_out = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$now, $att['id']]);

} elseif ($att['overtime_in'] && !$att['overtime_out']) {
    $sql = "UPDATE attendance SET overtime_out = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$now, $att['id']]);

} else {
    // Nothing open → return success (DO NOT ERROR)
    echo json_encode([
        'status' => 'success',
        'message' => 'No open session to close'
    ]);
    exit;
}

/* -------------------------
   RECALCULATE TOTAL HOURS
-------------------------- */
$sql = "SELECT morning_in, morning_out,
               afternoon_in, afternoon_out,
               overtime_in, overtime_out
        FROM attendance WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$att['id']]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

$total = 0;

if ($r['morning_in'] && $r['morning_out']) {
    $total += (strtotime($r['morning_out']) - strtotime($r['morning_in'])) / 3600;
}
if ($r['afternoon_in'] && $r['afternoon_out']) {
    $total += (strtotime($r['afternoon_out']) - strtotime($r['afternoon_in'])) / 3600;
}
if ($r['overtime_in'] && $r['overtime_out']) {
    $total += (strtotime($r['overtime_out']) - strtotime($r['overtime_in'])) / 3600;
}

$sql = "UPDATE attendance SET total_hours = ? WHERE id = ?";
$pdo->prepare($sql)->execute([round($total, 2), $att['id']]);

echo json_encode([
    'status' => 'success',
    'message' => 'Time out recorded'
]);
