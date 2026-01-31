<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id'])) {
    include "DB_connection.php";
    include "app/Model/Subtask.php";
    include "app/Model/Task.php";
    include "app/Model/User.php";

    $subtasks = get_subtasks_by_member($pdo, $_SESSION['id']);
 ?>
<!DOCTYPE html>
<html>
<head>
	<title>My Subtasks</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">My Subtasks</h4>
			<?php if (isset($_GET['success'])) {?>
      	  	<div class="success" role="alert">
			  <?php echo stripcslashes($_GET['success']); ?>
			</div>
		<?php } ?>
			<?php if ($subtasks != 0 && !empty($subtasks)) { ?>
			<table class="main-table">
				<thead>
                    <tr>
                        <th>Main Task</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
				<?php $i=0; foreach ($subtasks as $sub) { ?>
				<tr>
					<td><?=$sub['task_title']?></td>
					<td><?=$sub['description']?></td>
	                <td><?=$sub['due_date']?></td>
					<td><?=$sub['status']?></td>
					<td>
                        <a href="submit-subtask.php?id=<?=$sub['id']?>" class="edit-btn">View / Submit</a>
					</td>
				</tr>
			   <?php } ?>
               </tbody>
			</table>
		<?php }else { ?>
			<h3>No assigned subtasks</h3>
		<?php  }?>
			
		</section>
	</div>

<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(2)"); // Highlight Tasks tab roughly
	active.classList.add("active");
</script>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>
