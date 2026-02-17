<?php
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    if (isset($_POST['id'])) {
        include "../DB_connection.php";
        require_once "../inc/csrf.php";
        include "model/Group.php";

        if (!csrf_verify('delete_group_form', $_POST['csrf_token'] ?? null, true)) {
            $em = "Invalid or expired request. Please refresh and try again.";
            header("Location: ../groups.php?error=" . urlencode($em));
            exit();
        }

        $id = (int)$_POST['id'];

        if ($id > 0) {
            if (delete_group($pdo, $id)) {
                $em = "Group deleted successfully";
                header("Location: ../groups.php?success=$em");
                exit();
            } else {
                $em = "Error occurred";
                header("Location: ../groups.php?error=$em");
                exit();
            }
        } else {
            $em = "Error occurred";
            header("Location: ../groups.php?error=$em");
            exit();
        }
    } else {
        header("Location: ../groups.php");
        exit();
    }
} else {
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}
