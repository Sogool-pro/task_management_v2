<?php
include "DB_connection.php";

try {
    // 1. Drop old constraint if exists
    $sql = "SELECT conname FROM pg_constraint WHERE conrelid = 'tasks'::regclass AND contype = 'c' AND conname LIKE '%status%'";
    $stmt = $pdo->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($constraints) {
        foreach ($constraints as $conname) {
            $pdo->exec("ALTER TABLE tasks DROP CONSTRAINT \"$conname\"");
            echo "Dropped constraint: $conname <br>";
        }
    }

    // 2. Add new constraint
    $sql = "ALTER TABLE tasks ADD CONSTRAINT tasks_status_check 
            CHECK (status IN ('pending', 'in_progress', 'completed', 'rejected', 'revise'))";
    $pdo->exec($sql);
    echo "Constraint updated successfully. Allowed: pending, in_progress, completed, rejected, revise.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
