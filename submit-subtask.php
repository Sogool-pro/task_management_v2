<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id'])) {
    include "DB_connection.php";
    include "app/Model/Subtask.php";
    include "app/Model/Task.php";
    include "app/Model/User.php";
    
    if (!isset($_GET['id'])) {
        header("Location: my_subtasks.php");
        exit();
    }
    $id = $_GET['id'];
    $subtask = get_subtask_by_id($pdo, $id);
    
    if (!$subtask) {
        header("Location: my_subtasks.php");
        exit();
    }
    
    // Check ownership
    if ($subtask['member_id'] != $_SESSION['id']) {
        header("Location: my_subtasks.php");
        exit();
    }
    
    $task = get_task_by_id($pdo, $subtask['task_id']);
 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Submit Subtask</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">Submit Subtask <a href="my_subtasks.php">Back</a></h4>
			<div class="form-1" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                <div class="input-holder">
					<lable>Main Task</lable>
					<p><b><?=$task['title']?></b></p>
				</div>
                <div class="input-holder">
					<lable>Subtask Description</lable>
					<p><?=$subtask['description']?></p>
				</div>
                <div class="input-holder">
					<lable>Due Date</lable>
					<p><?=$subtask['due_date']?></p>
				</div>
                <div class="input-holder">
					<lable>Status</lable>
					<p><?=$subtask['status']?></p>
				</div>
                <?php if ($subtask['feedback']) { ?>
                <div class="input-holder">
					<lable style="color: red;">Leader Feedback</lable>
					<p style="background: #ffe6e6; padding: 10px; border-radius: 5px;"><?=$subtask['feedback']?></p>
				</div>
                <?php } ?>
                
                <?php if ($subtask['submission_file']) { ?>
                <div class="input-holder">
					<lable>Current Submission</lable>
					<p><a href="<?=$subtask['submission_file']?>" target="_blank">View File</a></p>
				</div>
                <?php } ?>
                
                <?php if ($subtask['status'] == 'pending' || $subtask['status'] == 'revise') { ?>
                <div class="separator-line" style="margin: 20px 0; border-bottom: 1px solid #ddd;"></div>
                <form method="POST" enctype="multipart/form-data" action="app/update-subtask-submission.php">
                    <input type="hidden" name="id" value="<?=$id?>">
                    <div class="input-holder">
                        <lable>Upload File</lable>
                        <input type="file" name="submission_file" class="input-1" required>
                    </div>
                    <button class="edit-btn">Submit</button>
                </form>
                <?php } ?>
            </div>
		</section>
	</div>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>
