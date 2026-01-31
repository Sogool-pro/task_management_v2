<?php  

function get_all_my_notifications($pdo, $id){
	$sql = "SELECT * FROM notifications WHERE recipient=?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$id]);

	if($stmt->rowCount() > 0){
		$notifications = $stmt->fetchAll();
	}else $notifications = 0;

	return $notifications;
}


function count_notification($pdo, $id){
	$sql = "SELECT COUNT(*) FROM notifications WHERE recipient=? AND is_read='f'";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$id]);

	return $stmt->fetchColumn();
}

function insert_notification($pdo, $data){
	// Automatically set the current date when inserting a notification
	// $data should be: [$message, $recipient, $type] or [$message, $recipient, $type, $task_id]
	
	// Check if task_id column exists in the table (PostgreSQL version)
	$has_task_id_column = false;
	try {
        $check_sql = "SELECT 1 FROM information_schema.columns WHERE table_name = 'notifications' AND column_name = 'task_id'";
		$check_stmt = $pdo->query($check_sql);
		$has_task_id_column = (bool)$check_stmt->fetchColumn();
	} catch (Exception $e) {
		$has_task_id_column = false;
	}
	
	// Check if task_id is provided
	$task_id = (count($data) >= 4 && isset($data[3])) ? $data[3] : null;
	
	if ($has_task_id_column && $task_id !== null) {
		// Insert with task_id
		$sql = "INSERT INTO notifications (message, recipient, type, date, task_id) VALUES(?,?,?,CURRENT_DATE,?)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$data[0], $data[1], $data[2], $task_id]);
	} else if ($has_task_id_column) {
		// Insert with NULL task_id
		$sql = "INSERT INTO notifications (message, recipient, type, date, task_id) VALUES(?,?,?,CURRENT_DATE,NULL)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$data[0], $data[1], $data[2]]);
	} else {
		// Old format without task_id column
		$sql = "INSERT INTO notifications (message, recipient, type, date) VALUES(?,?,?,CURRENT_DATE)";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$data[0], $data[1], $data[2]]);
	}
}

function notification_make_read($pdo, $recipient_id, $notification_id){
	$sql = "UPDATE notifications SET is_read='t' WHERE id=? AND recipient=?";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$notification_id, $recipient_id]);
}