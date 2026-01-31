<?php
session_start();
include "../DB_connection.php";

if ($_SESSION['role'] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$task_id = $_POST['task_id'];

$sql = "UPDATE tasks 
        SET status = 'completed'
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$task_id]);

header("Location: ../tasks.php?success=Task marked as completed");
