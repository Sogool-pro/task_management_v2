<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    require_once "inc/tenant.php";
    include "app/model/user.php";
    include "app/model/Task.php";
    
    $is_super_admin = is_super_admin($_SESSION['id'], $pdo);

    if (!isset($_GET['id'])) {
    	 header("Location: user.php");
    	 exit();
    }
    $id = $_GET['id'];
    $user = get_user_by_id($pdo, $id);
    $orgId = tenant_get_current_org_id();
    $is_owner = false;
    if ($orgId && tenant_table_exists($pdo, 'organization_members')) {
        $ownerStmt = $pdo->prepare(
            "SELECT role FROM organization_members WHERE organization_id = ? AND user_id = ? LIMIT 1"
        );
        $ownerStmt->execute([$orgId, $_SESSION['id']]);
        $is_owner = $ownerStmt->fetchColumn() === 'owner';
    }

    if ($user == 0) {
    	 $em = "User not found";
    	 header("Location: user.php?error=$em");
    	 exit();
    }

    // Security check: only super admin can delete admins
    if ($user['role'] == 'admin' && !$is_super_admin && !$is_owner) {
        $em = "Access denied. Only Super Admin can delete other Admins.";
        header("Location: user.php?error=$em");
        exit();
    }
    
    if ($orgId && tenant_table_exists($pdo, 'organization_members')) {
        $ownerCheck = $pdo->prepare(
            "SELECT role FROM organization_members WHERE organization_id = ? AND user_id = ? LIMIT 1"
        );
        $ownerCheck->execute([$orgId, $id]);
        if ($ownerCheck->fetchColumn() === 'owner') {
            $em = "Access denied. Workspace owner account cannot be deleted.";
            header("Location: user.php?error=$em");
            exit();
        }
    } else if ($user['username'] == 'admin') {
        // Legacy fallback for older schema
        $em = "Access denied. The Super Admin account cannot be deleted.";
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
    unassign_completed_tasks($pdo, $id);

    if ($orgId && tenant_table_exists($pdo, 'organization_members')) {
        $stmt = $pdo->prepare("DELETE FROM organization_members WHERE organization_id = ? AND user_id = ?");
        $stmt->execute([$orgId, $id]);
    }

    $sql = "DELETE FROM users WHERE id=?";
    $params = [$id];
    $scope = tenant_get_scope($pdo, 'users');
    $sql .= $scope['sql'];
    $params = array_merge($params, $scope['params']);
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
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

