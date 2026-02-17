<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

require 'DB_connection.php';
require_once 'inc/tenant.php';
require_once 'inc/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'employee') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_verify('attendance_ajax_actions', $_POST['csrf_token'] ?? null, false)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired request']);
    exit;
}

$user_id = $_SESSION['id'];
$now     = date('H:i:s');

// Find the ACTIVE session (time_out IS NULL)
$params = [$user_id];
$scope = tenant_get_scope($pdo, 'attendance');
$sql = "SELECT * FROM attendance WHERE user_id=? AND time_out IS NULL" . $scope['sql'] . " ORDER BY id DESC LIMIT 1";
$params = array_merge($params, $scope['params']);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$att = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$att) {
    echo json_encode(['status'=>'error','message'=>'No active session to time out']);
    exit;
}

if ($att['time_out']) {
    echo json_encode(['status'=>'success','message'=>'Already timed out']);
    exit;
}

$hours = round((strtotime($now) - strtotime($att['time_in'])) / 3600, 2);

$sql = "UPDATE attendance SET time_out=?, total_hours=? WHERE id=?";
$params = [$now, $hours, $att['id']];
$scope = tenant_get_scope($pdo, 'attendance');
$sql .= $scope['sql'];
$params = array_merge($params, $scope['params']);
$pdo->prepare($sql)->execute($params);

echo json_encode(['status'=>'success','message'=>'Time out recorded']);
