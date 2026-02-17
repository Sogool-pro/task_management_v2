<?php
session_start();

if (!isset($_SESSION['role']) || !isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}

include "../DB_connection.php";
include "model/user.php";
require_once "../inc/tenant.php";
require_once "../inc/csrf.php";

function seat_limit_redirect_error($message)
{
    header("Location: ../workspace-billing.php?error=" . urlencode((string)$message));
    exit();
}

function seat_limit_redirect_success($message)
{
    header("Location: ../workspace-billing.php?success=" . urlencode((string)$message));
    exit();
}

if (!isset($_POST['seat_limit'])) {
    seat_limit_redirect_error("Seat limit is required.");
}

if (!csrf_verify('workspace_seat_limit_form', $_POST['csrf_token'] ?? null, true)) {
    seat_limit_redirect_error("Invalid or expired request. Please refresh and try again.");
}

$isSuperAdmin = is_super_admin((int)$_SESSION['id'], $pdo);
if ($isSuperAdmin) {
    seat_limit_redirect_error("Super Admin cannot update workspace seats from this page.");
}

$orgId = tenant_get_current_org_id();
if (!$orgId) {
    seat_limit_redirect_error("Workspace context is missing.");
}

$organizationRole = strtolower(trim((string)($_SESSION['organization_role'] ?? '')));
if ($organizationRole !== '' && !in_array($organizationRole, ['owner', 'admin'], true)) {
    seat_limit_redirect_error("You do not have permission to update seat limits.");
}

if (!tenant_table_exists($pdo, 'subscriptions')) {
    seat_limit_redirect_error("Subscriptions table is missing. Run tenancy migrations first.");
}

$seatLimitRaw = trim((string)$_POST['seat_limit']);
if ($seatLimitRaw === '' || !ctype_digit($seatLimitRaw)) {
    seat_limit_redirect_error("Seat limit must be a whole number.");
}

$newSeatLimit = (int)$seatLimitRaw;
if ($newSeatLimit < 1) {
    seat_limit_redirect_error("Seat limit must be at least 1.");
}
if ($newSeatLimit > 5000) {
    seat_limit_redirect_error("Seat limit is too high. Maximum allowed is 5000.");
}

$orgId = (int)$orgId;
$currentMembers = tenant_count_workspace_members($pdo, $orgId);
if ($newSeatLimit < $currentMembers) {
    seat_limit_redirect_error("Cannot set seat limit below active members ({$currentMembers}).");
}

try {
    $subscription = tenant_ensure_subscription($pdo, $orgId);
    if (!$subscription) {
        seat_limit_redirect_error("Unable to initialize workspace subscription.");
    }

    $stmt = $pdo->prepare(
        "UPDATE subscriptions
         SET seat_limit = ?
         WHERE organization_id = ?"
    );
    $stmt->execute([$newSeatLimit, $orgId]);

    seat_limit_redirect_success("Seat limit updated to {$newSeatLimit} successfully.");
} catch (Throwable $e) {
    seat_limit_redirect_error("Failed to update seat limit right now.");
}
