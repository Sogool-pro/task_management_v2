<?php
session_start();
if ((isset($_SESSION['role']) && $_SESSION['role'] == "employee") || (isset($_SESSION['role']) && $_SESSION['role'] == "admin")) {
    
    if (isset($_POST['subtask_id']) && isset($_POST['action']) && isset($_POST['feedback'])) {
        include "../DB_connection.php";
        include "Model/Subtask.php";
        include "Model/Notification.php";

        function validate_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        $subtask_id = validate_input($_POST['subtask_id']);
        $action = validate_input($_POST['action']);
        $feedback = validate_input($_POST['feedback']);
        $parent_id = isset($_POST['parent_id']) ? validate_input($_POST['parent_id']) : null;

        $subtask = get_subtask_by_id($pdo, $subtask_id);
        if (!$subtask) {
            header("Location: ../edit-task-employee.php?error=Subtask not found&id=$parent_id");
            exit();
        }

        if ($action == 'accept') {
            $status = 'completed';
            $msg = "Your subtask submission has been ACCEPTED.";
        } else if ($action == 'revise') {
            $status = 'revise';
            $msg = "Your subtask submission requires REVISION. Feedback: $feedback";
        } else {
             header("Location: ../edit-task-employee.php?error=Invalid action&id=$parent_id");
             exit();
        }

        update_subtask_status($pdo, $subtask_id, $status, $feedback);
        
        // Notify the member
        insert_notification($pdo, [$msg, $subtask['member_id'], 'Subtask Review', $subtask['task_id']]);

        $em = "Subtask updated successfully";
        header("Location: ../edit-task-employee.php?success=$em&id=$parent_id");
        exit();

    }else {
        $em = "Unknown error occurred";
        header("Location: ../tasks.php?error=$em");
        exit();
    }
}else{ 
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}
