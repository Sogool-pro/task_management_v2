<?php
session_start();
date_default_timezone_set('Asia/Manila');

if (isset($_SESSION['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'employee') {
    require 'DB_connection.php';
    require_once 'inc/tenant.php';

    $user_id = (int) $_SESSION['id'];
    $now = date('H:i:s');

    // Auto clock-out on logout if there is an active attendance record.
    $sql = "SELECT id, time_in FROM attendance
            WHERE user_id = ?
              AND time_in IS NOT NULL
              AND (time_out IS NULL OR time_out = '00:00:00')";
    $params = [$user_id];
    $scope = tenant_get_scope($pdo, 'attendance');
    $sql .= $scope['sql'] . "
            ORDER BY id DESC
            LIMIT 1";
    $params = array_merge($params, $scope['params']);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($att) {
        $hours = round((strtotime($now) - strtotime($att['time_in'])) / 3600, 2);
        if ($hours < 0) {
            $hours = 0;
        }

        $update = "UPDATE attendance SET time_out = ?, total_hours = ? WHERE id = ?";
        $updateParams = [$now, $hours, $att['id']];
        $scope = tenant_get_scope($pdo, 'attendance');
        $update .= $scope['sql'];
        $updateParams = array_merge($updateParams, $scope['params']);
        $pdo->prepare($update)->execute($updateParams);
    }
}

session_unset();
session_destroy();

header("Location: login.php");
exit();
