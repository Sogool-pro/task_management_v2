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
require_once "mail_config.php";
require_once "invite_helpers.php";

if (!csrf_verify('generate_workspace_join_link_form', $_POST['csrf_token'] ?? null, true)) {
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

$capacity = tenant_check_workspace_capacity($pdo, (int)$orgId);
if (!$capacity['ok']) {
    header("Location: ../invite-user.php?error=" . urlencode((string)$capacity['reason']));
    exit();
}

if (!tenant_table_exists($pdo, 'workspace_invites')) {
    header("Location: ../invite-user.php?error=workspace_invites table is missing. Run migration first.");
    exit();
}

$token = '';
try {
    $token = bin2hex(random_bytes(32));
} catch (Throwable $e) {
    $token = hash('sha256', uniqid('workspace_join_', true) . microtime(true));
}
$syntheticEmail = invite_make_open_link_email($token);
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

try {
    $stmt = $pdo->prepare(
        "INSERT INTO workspace_invites
         (organization_id, invited_by, email, full_name, role, token, status, expires_at)
         VALUES (?, ?, ?, NULL, 'employee', ?, 'pending', ?)"
    );
    $stmt->execute([(int)$orgId, (int)$_SESSION['id'], $syntheticEmail, $token, $expiresAt]);
} catch (Throwable $e) {
    header("Location: ../invite-user.php?error=Failed to generate one-time link.");
    exit();
}

$link = APP_URL . "/join-workspace.php?token={$token}";
header(
    "Location: ../invite-user.php?success=" .
    urlencode("One-time join link generated successfully.") .
    "&one_time_link=" .
    urlencode($link)
);
exit();
