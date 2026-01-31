<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include "DB_connection.php";
include "app/Model/Task.php";

$task = get_task_by_id($pdo, $_GET['id']);
?>

<h3><?=$task['title']?></h3>

<a href="uploads/<?=$task['submission_file']?>" target="_blank">
    View Submitted File
</a>

<form method="POST" action="admin-review-task.php">
    <input type="hidden" name="task_id" value="<?=$task['id']?>">

    <textarea name="comment" placeholder="Admin feedback (optional)"
              class="input-1"></textarea>

    <button name="approve" class="btn btn-success">Approve</button>
    <button name="reject" class="btn btn-danger">Reject</button>
</form>
