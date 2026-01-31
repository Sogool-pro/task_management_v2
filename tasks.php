<?php
session_start();

if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] === "admin") {

    require_once "DB_connection.php";
    require_once "app/Model/Task.php";
    require_once "app/Model/User.php";

    $text = "All Task";

    // ---- Due Date Filters ----
    if (isset($_GET['due_date']) && $_GET['due_date'] === "Due Today") {
        $text = "Due Today";
        $tasks = get_all_tasks_due_today($pdo);
        $num_task = count_tasks_due_today($pdo);

    } elseif (isset($_GET['due_date']) && $_GET['due_date'] === "Overdue") {
        $text = "Overdue";
        $tasks = get_all_tasks_overdue($pdo);
        $num_task = count_tasks_overdue($pdo);

    } elseif (isset($_GET['due_date']) && $_GET['due_date'] === "No Deadline") {
        $text = "No Deadline";
        $tasks = get_all_tasks_NoDeadline($pdo);
        $num_task = count_tasks_NoDeadline($pdo);

    // ---- Status Filters ----
    } elseif (isset($_GET['status']) && $_GET['status'] === "Pending") {
        $text = "Pending";
        $tasks = get_all_tasks_pending($pdo);
        $num_task = count_pending_tasks($pdo);

    } elseif (isset($_GET['status']) && $_GET['status'] === "in_progress") {
        $text = "In Progress";
        $tasks = get_all_tasks_in_progress($pdo);
        $num_task = count_in_progress_tasks($pdo);

    } elseif (isset($_GET['status']) && $_GET['status'] === "Completed") {
        $text = "Completed";
        $tasks = get_all_tasks_completed($pdo);
        $num_task = count_completed_tasks($pdo);

    } else {
        $tasks = get_all_tasks($pdo);
        $num_task = count_tasks($pdo);
    }

    $users = get_all_users($pdo);

    // Progress bar data
    $total_tasks = count_tasks($pdo);
    $completed_tasks = count_completed_tasks($pdo);
    $completion_percentage = $total_tasks > 0
        ? round(($completed_tasks / $total_tasks) * 100)
        : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Tasks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<input type="checkbox" id="checkbox">

<?php require_once "inc/header.php"; ?>

<div class="body">
<?php require_once "inc/nav.php"; ?>

<section class="section-1">

<h4 class="title-2"><?= $text ?> (<?= $num_task ?>)</h4>

<a href="create_task.php" class="btn create-task-btn">Create Task</a>

<?php if ($tasks != 0) { ?>
<table class="main-table">
<tr>
    <th>#</th>
    <th>Title</th>
    <th>Description</th>
    <th>Assigned To</th>
    <th>Due Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php $i = 0; foreach ($tasks as $task) { ?>
<tr>
<td><?= ++$i ?></td>
<td><?= $task['title'] ?></td>
<td><?= $task['description'] ?></td>
<td>
<?php
$assignees = get_task_assignees($pdo, $task['id']);
if ($assignees != 0) {
    $names = [];
    foreach ($assignees as $a) {
        $names[] = $a['full_name'] .
            ($a['role'] === 'leader' ? ' (Leader)' : '');
    }
    echo implode(', ', $names);
}
?>
</td>
<td><?= $task['due_date'] ?: 'No Deadline' ?></td>
<td><?= $task['status'] ?></td>
<td>
    <a href="edit-task.php?id=<?= $task['id'] ?>" class="edit-btn">Edit</a>
    <a href="delete-task.php?id=<?= $task['id'] ?>" class="delete-btn">Delete</a>
</td>
</tr>
<?php } ?>
</table>
<?php } else { ?>
<h3>Empty</h3>
<?php } ?>

</section>
</div>

<?php require_once "inc/modals.php"; ?>

</body>
</html>

<?php
} else {
    header("Location: login.php?error=First login");
    exit();
}
