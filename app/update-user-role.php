<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "../DB_connection.php";
    require_once "../inc/tenant.php";
    require_once "../inc/csrf.php";
    include "model/user.php";

    $is_super_admin = is_super_admin($_SESSION['id'], $pdo);
    $orgId = tenant_get_current_org_id();
    $is_owner = false;
    if ($orgId && tenant_table_exists($pdo, 'organization_members')) {
        $ownerStmt = $pdo->prepare(
            "SELECT role FROM organization_members WHERE organization_id = ? AND user_id = ? LIMIT 1"
        );
        $ownerStmt->execute([$orgId, $_SESSION['id']]);
        $is_owner = $ownerStmt->fetchColumn() === 'owner';
    }

    if (!$is_super_admin && !$is_owner) {
        header("Location: ../user.php?error=Access Denied");
        exit();
    }

    if (isset($_POST['user_id']) && isset($_POST['role'])) {
        if (!csrf_verify('update_user_role_form', $_POST['csrf_token'] ?? null, true)) {
            header("Location: ../user.php?error=" . urlencode("Invalid or expired request. Please refresh and try again."));
            exit();
        }

        $user_id = $_POST['user_id'];
        $role = $_POST['role'];

        // Prevent super admin from changing their own role (optional but safe)
        $target_user = get_user_by_id($pdo, $user_id);
        if ($orgId && tenant_table_exists($pdo, 'organization_members')) {
            $ownerCheck = $pdo->prepare(
                "SELECT role FROM organization_members WHERE organization_id = ? AND user_id = ? LIMIT 1"
            );
            $ownerCheck->execute([$orgId, $user_id]);
            if ($ownerCheck->fetchColumn() === 'owner') {
                header("Location: ../user.php?error=Cannot change workspace owner role");
                exit();
            }
        } else if ($target_user['username'] == 'admin') {
            header("Location: ../user.php?error=Cannot change Super Admin role");
            exit();
        }

        if ($role == 'admin' || $role == 'employee') {
            $sql = "UPDATE users SET role = ? WHERE id = ?";
            $params = [$role, $user_id];
            $scope = tenant_get_scope($pdo, 'users');
            $sql .= $scope['sql'];
            $params = array_merge($params, $scope['params']);
            $stmt = $pdo->prepare($sql);
            $res = $stmt->execute($params);

            if ($res && $orgId && tenant_table_exists($pdo, 'organization_members')) {
                $memberRole = $role === 'admin' ? 'admin' : 'member';
                $stmt = $pdo->prepare(
                    "UPDATE organization_members SET role = ? WHERE organization_id = ? AND user_id = ?"
                );
                $stmt->execute([$memberRole, $orgId, $user_id]);
            }

            if ($res) {
                header("Location: ../user.php?success=Role updated successfully");
            } else {
                header("Location: ../user.php?error=Failed to update role");
            }
        } else {
            header("Location: ../user.php?error=Invalid role");
        }
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}

