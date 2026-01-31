<?php

function insert_subtask($pdo, $data){
    $sql = "INSERT INTO subtasks (task_id, member_id, description, due_date) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    return $pdo->lastInsertId();
}

function get_subtasks_by_task($pdo, $task_id){
    $sql = "SELECT s.*, u.full_name as member_name 
            FROM subtasks s
            JOIN users u ON s.member_id = u.id
            WHERE s.task_id = ?
            ORDER BY s.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_subtask_by_id($pdo, $subtask_id){
    $sql = "SELECT * FROM subtasks WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$subtask_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_subtasks_by_member($pdo, $member_id){
    $sql = "SELECT s.*, t.title as task_title 
            FROM subtasks s
            JOIN tasks t ON s.task_id = t.id
            WHERE s.member_id = ?
            ORDER BY s.due_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_subtask_submission($pdo, $id, $file_path){
    $sql = "UPDATE subtasks SET submission_file = ?, status = 'submitted', updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$file_path, $id]);
}

function update_subtask_status($pdo, $id, $status, $feedback = null){
    $sql = "UPDATE subtasks SET status = ?, feedback = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $feedback, $id]);
}

function delete_subtask($pdo, $id){
    $stmt = $pdo->prepare("DELETE FROM subtasks WHERE id = ?");
    $stmt->execute([$id]);
}
