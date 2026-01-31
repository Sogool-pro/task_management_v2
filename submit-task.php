<?php
session_start();

include "DB_connection.php";

// Basic safety checks for POST and FILES
if (!isset($_POST['task_id']) || !isset($_FILES['submission'])) {
    $em = "Invalid submission request";
    header("Location: my_task.php?error=$em");
    exit();
}

$task_id = $_POST['task_id'];

// Create unique file name and move upload
$fileName = time() . "_" . basename($_FILES['submission']['name']);
$targetDir = "uploads/";

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$target = $targetDir . $fileName;

if (!move_uploaded_file($_FILES['submission']['tmp_name'], $target)) {
    $em = "Failed to upload file";
    header("Location: edit-task-employee.php?id=$task_id&error=$em");
    exit();
}

// Update task with submission details
$sql = "UPDATE tasks SET
        submission_file = ?,
        submission_status = 'submitted',
        submitted_at = NOW()
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$fileName, $task_id]);

// Redirect back to task view for the employee
header("Location: edit-task-employee.php?id=$task_id&submitted=1");
exit();