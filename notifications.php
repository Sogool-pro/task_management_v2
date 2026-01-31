<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id'])) {
    include "DB_connection.php";
    include "app/Model/Notification.php";
    include "app/Model/Task.php";

    $notifications = get_all_my_notifications($pdo, $_SESSION['id']);
    $user_role = $_SESSION['role'];

    // Helper function to get task_id from notification
    function get_notification_task_id($pdo, $notification) {
        // First try to get task_id from notification record (if column exists)
        if (isset($notification['task_id']) && $notification['task_id'] !== null) {
            return $notification['task_id'];
        }
        
        // Otherwise, try to extract task title from message and find task
        $message = $notification['message'];
        // Extract task title from messages like: "'Task Title' has been assigned..."
        if (preg_match("/'([^']+)'/", $message, $matches)) {
            $task_title = $matches[1];
            $task = get_task_by_title($pdo, $task_title);
            if ($task != 0) {
                return $task['id'];
            }
        }
        
        return null;
    }

 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Notifications</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">

</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">All Notifications</h4>
			<?php if (isset($_GET['success'])) {?>
      	  	<div class="success" role="alert">
			  <?php echo stripcslashes($_GET['success']); ?>
			</div>
		<?php } ?>
			<?php if ($notifications != 0) { ?>
			<table class="main-table">
				<tr>
					<th>#</th>
					<th>Message</th>
					<th>Type</th>
					<th>Date</th>
				</tr>
				<?php $i=0; foreach ($notifications as $notification) { 
					$task_id = get_notification_task_id($pdo, $notification);
					$is_clickable = $task_id !== null;
					$click_style = $is_clickable ? 'cursor: pointer; background-color: #f8f9fa;' : '';
					$onclick = $is_clickable ? 'onclick="handleNotificationClick(' . $task_id . ')"' : '';
				?>
				<tr style="<?=$click_style?>" <?=$onclick?> onmouseover="if(<?=$is_clickable ? 'true' : 'false'?>) this.style.backgroundColor='#e9ecef'" onmouseout="if(<?=$is_clickable ? 'true' : 'false'?>) this.style.backgroundColor='#f8f9fa'">
					<td><?=++$i?></td>
					<td><?=$notification['message']?><?=$is_clickable ? ' <i class="fa fa-arrow-right" style="color: #007bff; margin-left: 5px;"></i>' : ''?></td>
					<td><?=$notification['type']?></td>
					<td><?=$notification['date']?></td>
				</tr>
			   <?php	} ?>
			</table>
		<?php }else { ?>
			<h3>You have zero notification</h3>
		<?php  }?>
			
		</section>
	</div>


<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(4)");
	if (active) {
		active.classList.add("active");
	}

	function handleNotificationClick(taskId) {
		if (!taskId) return;
		
		var userRole = '<?=$user_role?>';
		var url = '';
		
		if (userRole === 'admin') {
			// Admin: redirect to edit-task.php to see submitted file
			url = 'edit-task.php?id=' + taskId;
		} else if (userRole === 'employee') {
			// Employee: redirect to edit-task-employee.php to see task details
			url = 'edit-task-employee.php?id=' + taskId;
		}
		
		if (url) {
			window.location.href = url;
		}
	}
</script>


</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>