<?php
session_start();

if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] === "admin") {
    header("Location: invite-user.php");
    exit();
}

$em = "First login";
header("Location: login.php?error=$em");
exit();
