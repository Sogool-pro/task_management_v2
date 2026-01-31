<?php
session_start();

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include "DB_connection.php";

$task_id = $_POST['task_id'];
$comment = $_POST['comment'] ?? null;

if (isset($_POST['approve'])) {
    $sql = "UPDATE tasks
            SET submission_status = 'approved',
                status = 'completed',
                admin_comment = ?
            WHERE id = ?";
} else {
    $sql = "UPDATE tasks
            SET submission_status = 'rejected',
                status = 'pending',
                admin_comment = ?
            WHERE id = ?";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$comment, $task_id]);

header("Location: tasks.php?reviewed=1");
exit();
