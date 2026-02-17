<?php
session_start();

if (!isset($_SESSION['role']) || !isset($_SESSION['id']) || $_SESSION['role'] !== "admin") {
    header("Location: ../login.php?error=First login");
    exit();
}

// Direct user creation is deprecated in SaaS mode.
// Admins should invite users so they can set their own password securely.
header("Location: ../invite-user.php?warn=" . urlencode("Direct user creation is disabled. Use workspace invites."));
exit();
