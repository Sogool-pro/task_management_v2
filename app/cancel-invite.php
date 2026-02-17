<?php
session_start();

if (!isset($_SESSION['role']) || !isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php?error=First login");
    exit();
}

include "../DB_connection.php";
include "model/user.php";
require_once "../inc/tenant.php";
require_once "../inc/csrf.php";

if (!isset($_POST['invite_id'])) {
    header("Location: ../invite-user.php?error=Invite ID is required.");
    exit();
}

if (!csrf_verify('revoke_invite_form', $_POST['csrf_token'] ?? null, true)) {
    header("Location: ../invite-user.php?error=" . urlencode("Invalid or expired request. Please refresh and try again."));
    exit();
}

$is_super_admin = is_super_admin($_SESSION['id'], $pdo);
if ($is_super_admin) {
    header("Location: ../invite-user.php?error=Access denied for super admin.");
    exit();
}

$orgId = tenant_get_current_org_id();
if (!$orgId) {
    header("Location: ../invite-user.php?error=Workspace context is missing.");
    exit();
}

if (!tenant_table_exists($pdo, 'workspace_invites')) {
    header("Location: ../invite-user.php?error=workspace_invites table is missing.");
    exit();
}

$inviteId = (int)$_POST['invite_id'];
if ($inviteId <= 0) {
    header("Location: ../invite-user.php?error=Invalid invite ID.");
    exit();
}

$stmt = $pdo->prepare(
    "UPDATE workspace_invites
     SET status = 'revoked'
     WHERE id = ?
       AND organization_id = ?
       AND status = 'pending'"
);
$stmt->execute([$inviteId, $orgId]);

if ($stmt->rowCount() > 0) {
    header("Location: ../invite-user.php?success=Invite revoked.");
    exit();
}

header("Location: ../invite-user.php?error=Invite not found or already finalized.");
exit();
