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

function update_subtask_submission($pdo, $id, $file_path, $note = null){
    $sql = "UPDATE subtasks SET submission_file = ?, submission_note = ?, status = 'submitted', updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$file_path, $note, $id]);
}

function update_subtask_status($pdo, $id, $status, $feedback = null, $score = null){
    $sql = "UPDATE subtasks SET status = ?, feedback = ?, score = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $feedback, $score, $id]);
}

function delete_subtask($pdo, $id){
    $stmt = $pdo->prepare("DELETE FROM subtasks WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * Get collaborative scores breakdown by project/task for a user
 * Returns overall stats and per-project breakdown
 */
function get_collaborative_scores_by_user($pdo, $user_id) {
    // Get overall collaborative score stats
    $sql_overall = "SELECT COUNT(*) as count, AVG(s.score) as avg 
                    FROM subtasks s 
                    WHERE s.member_id = ? AND s.score IS NOT NULL";
    $stmt = $pdo->prepare($sql_overall);
    $stmt->execute([$user_id]);
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get per-project breakdown
    $sql_breakdown = "SELECT t.id as task_id, t.title as task_title, 
                             COUNT(s.id) as subtask_count, AVG(s.score) as avg_score
                      FROM subtasks s
                      JOIN tasks t ON s.task_id = t.id
                      WHERE s.member_id = ? AND s.score IS NOT NULL
                      GROUP BY t.id, t.title
                      ORDER BY t.title ASC";
    $stmt = $pdo->prepare($sql_breakdown);
    $stmt->execute([$user_id]);
    $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'count' => $overall['count'] ?? 0,
        'avg' => $overall['avg'] ? number_format($overall['avg'], 1) : "0.0",
        'projects' => $breakdown
    ];
}
