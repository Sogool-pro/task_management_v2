<?php
include "DB_connection.php";

echo "Checking if 'submission_note' column exists in 'subtasks' table...\n";

// Check if column exists
$stmt = $pdo->prepare("
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='subtasks' AND column_name='submission_note'
");
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo "Column not found. Adding 'submission_note'...\n";
    try {
        $sql = "ALTER TABLE subtasks ADD COLUMN submission_note TEXT DEFAULT NULL";
        $pdo->exec($sql);
        echo "Successfully added 'submission_note' column.\n";
    } catch (PDOException $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column 'submission_note' already exists.\n";
}
?>
