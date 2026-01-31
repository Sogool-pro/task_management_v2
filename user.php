<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    include "app/Model/User.php";
    include "app/Model/Task.php";

    $users = get_all_users($pdo);
  
 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Manage Users</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="css/style.css">

</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">Manage Users <a href="add-user.php">Add User</a></h4>
			<?php if ($users != 0) { ?>
			<table class="main-table">
				<tr>
					<th>#</th>
					<th>Full Name</th>
					<th>Username</th>
					<th>Role</th>
					<th>Task Progress</th>
					<th>Action</th>
				</tr>
				<?php $i=0; foreach ($users as $user) { 
					$progress = get_employee_task_progress($pdo, $user['id']);
				?>
				<tr>
					<td><?=++$i?></td>
					<td><?=$user['full_name']?></td>
					<td><?=$user['username']?></td>
					<td><?=$user['role']?></td>
					<td>
						<?php if ($progress['total'] > 0) { ?>
							<div style="display: flex; align-items: center; gap: 10px;">
								<span style="font-weight: 600; color: #333;"><?=$progress['completed']?>/<?=$progress['total']?></span>
								<div style="flex: 1; background: #e2e8f0; border-radius: 10px; height: 20px; max-width: 150px; overflow: hidden; position: relative;">
									<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); height: 100%; width: <?=$progress['percentage']?>%; transition: width 0.3s ease; border-radius: 10px;"></div>
								</div>
								<span style="font-size: 12px; color: #64748b;"><?=$progress['percentage']?>%</span>
							</div>
						<?php } else { ?>
							<span style="color: #64748b; font-style: italic;">No tasks assigned</span>
						<?php } ?>
					</td>
					<td>
						<a href="edit-user.php?id=<?=$user['id']?>" class="edit-btn">Edit</a>
						<a href="delete-user.php?id=<?=$user['id']?>" class="delete-btn" onclick="return confirmDelete('<?=$user['full_name']?>')">Delete</a>
					</td>
				</tr>
			   <?php	} ?>
			</table>
		<?php }else { ?>
			<h3>Empty</h3>
		<?php  }?>
			
		</section>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<?php include 'inc/modals.php'; ?>

<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(2)");
	active.classList.add("active");

	function confirmDelete(userName) {
		return confirm("Are you sure you want to delete user '" + userName + "'?\n\nThis action cannot be undone. If this user has tasks assigned, the deletion will fail.");
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