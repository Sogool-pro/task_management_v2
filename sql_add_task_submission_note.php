<?php
include "DB_connection.php";

echo "Checking if 'submission_note' column exists in 'tasks' table...\n";

// Check if column exists
$stmt = $pdo->prepare("
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='tasks' AND column_name='submission_note'
");
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo "Column not found. Adding 'submission_note' to 'tasks'...\n";
    try {
        $sql = "ALTER TABLE tasks ADD COLUMN submission_note TEXT DEFAULT NULL";
        $pdo->exec($sql);
        echo "Successfully added 'submission_note' column.\n";
    } catch (PDOException $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column 'submission_note' already exists in 'tasks'.\n";
}
?>
