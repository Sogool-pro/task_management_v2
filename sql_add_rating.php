<?php
include "DB_connection.php";

echo "Checking if 'rating' column exists in 'tasks' table...\n";

$stmt = $pdo->prepare("
    SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name='tasks' AND column_name='rating'
");
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo "Column 'rating' not found. Adding it...\n";
    try {
        $sql = "ALTER TABLE tasks ADD COLUMN rating INTEGER DEFAULT 0";
        $pdo->exec($sql);
        echo "Successfully added 'rating' column.\n";
    } catch (PDOException $e) {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column 'rating' already exists.\n";
}
?>
