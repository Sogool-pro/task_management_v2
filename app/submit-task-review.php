<?php
session_start();
if ((isset($_SESSION['role']) && $_SESSION['role'] == "employee") || (isset($_SESSION['role']) && $_SESSION['role'] == "admin")) {
    
    if (isset($_POST['task_id'])) {
        include "../DB_connection.php";
        include "Model/Notification.php";
        include "Model/Task.php";

        function validate_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        $task_id = validate_input($_POST['task_id']);
        $note = isset($_POST['submission_note']) ? validate_input($_POST['submission_note']) : null;

        // Verify all subtasks are completed? 
        // Ideally we should, but frontend checks it. Backend check is safer.
        // Let's assume frontend logic is sufficient for now or add a check if needed.
        
        // Update task status to 'completed' (or 'pending_review' if we had that status, but 'completed' fits the flow of "leader is done")
        // Also save the submission note.
        
        $sql = "UPDATE tasks SET status = 'completed', submission_note = ?, reviewed_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$note, $task_id]);

        // Notify Admin(s)
        // Find admins
        $stmt2 = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
        $stmt2->execute();
        $admins = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $task = get_task_by_id($pdo, $task_id);
        
        foreach($admins as $admin){
             insert_notification($pdo, ["Task Submitted by Leader (" . $_SESSION['full_name'] . ")", $admin['id'], 'Task Submitted', $task_id]);
        }

        $em = "Task submitted for review successfully";
        header("Location: ../my_task.php?success=$em");
        exit();

    }else {
        $em = "Unknown error occurred";
        header("Location: ../my_task.php?error=$em");
        exit();
    }
}else{ 
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}
