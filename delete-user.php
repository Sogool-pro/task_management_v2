<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    include "app/model/User.php";
    
    if (!isset($_GET['id'])) {
    	 header("Location: user.php");
    	 exit();
    }
    $id = $_GET['id'];
    $user = get_user_by_id($pdo, $id);

    if ($user == 0) {
    	 $em = "User not found";
    	 header("Location: user.php?error=$em");
    	 exit();
    }

    // Check if user has active (non-completed) tasks assigned
    if (user_has_tasks($pdo, $id)) {
    	$em = "Cannot delete user. This user has active tasks assigned. Please reassign or complete the tasks first.";
    	header("Location: user.php?error=$em");
    	exit();
    }

    // Unassign completed tasks (set assigned_to to NULL) before deletion
    // This prevents foreign key constraint errors
    include "app/model/Task.php";
    unassign_completed_tasks($pdo, $id);

    $data = array($id, "employee");
    $result = delete_user($pdo, $data);
    
    if ($result) {
    	$sm = "User deleted successfully";
    	header("Location: user.php?success=$sm");
    } else {
    	$em = "Failed to delete user. Please try again.";
    	header("Location: user.php?error=$em");
    }
    exit();

 }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>