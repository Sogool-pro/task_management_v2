<?php
/**
 * Migration: Add Task Assignees Support
 *
 * This will create the task_assignees table to support assigning tasks
 * to 1 leader and multiple members.
 *
 * Usage: Visit http://localhost/task_management_v2/run_migration_task_assignees.php in your browser
 */

include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

function migration_index_exists(PDO $pdo, string $driver, string $table, string $indexName): bool
{
    if ($driver === "mysql") {
        $sql = "SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                LIMIT 1";
    } else {
        $sql = "SELECT 1
                FROM pg_indexes
                WHERE schemaname = 'public'
                  AND tablename = ?
                  AND indexname = ?
                LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $indexName]);
    return (bool)$stmt->fetchColumn();
}

try {
    echo "<h2>Running Migration: Add Task Assignees Support</h2>";
    echo "<ul>";

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === "mysql") {
        $sql = "CREATE TABLE IF NOT EXISTS task_assignees (
                    id INT NOT NULL AUTO_INCREMENT,
                    task_id INT NOT NULL,
                    user_id INT NOT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'member' CHECK (role IN ('leader', 'member')),
                    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT task_assignees_pkey PRIMARY KEY (id),
                    CONSTRAINT unique_task_user UNIQUE (task_id, user_id),
                    CONSTRAINT task_assignees_ibfk_1 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
                    CONSTRAINT task_assignees_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS task_assignees (
                    id SERIAL PRIMARY KEY,
                    task_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'member' CHECK (role IN ('leader', 'member')),
                    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT unique_task_user UNIQUE (task_id, user_id),
                    CONSTRAINT task_assignees_ibfk_1 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
                    CONSTRAINT task_assignees_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                )";
    }

    $pdo->exec($sql);
    echo "<li>OK Created table: task_assignees</li>";

    if (!migration_index_exists($pdo, $driver, "task_assignees", "idx_task_assignees_task_id")) {
        $pdo->exec("CREATE INDEX idx_task_assignees_task_id ON task_assignees (task_id)");
    }

    if (!migration_index_exists($pdo, $driver, "task_assignees", "idx_task_assignees_user_id")) {
        $pdo->exec("CREATE INDEX idx_task_assignees_user_id ON task_assignees (user_id)");
    }

    echo "<li>OK Verified indexes: idx_task_assignees_task_id, idx_task_assignees_user_id</li>";
    echo "</ul>";

    echo "<h2 style='color: green;'>OK Migration completed successfully!</h2>";
    echo "<p>The task_assignees table is ready. You can now create tasks with leaders and members.</p>";
    echo "<p><a href='create_task.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Go to Create Task</a></p>";
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Error occurred:</h2>";
    echo "<pre style='background: #fee; padding: 15px; border-radius: 5px; border: 1px solid #fcc;'>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "</pre>";

    if (
        strpos($e->getMessage(), "already exists") !== false ||
        $e->getCode() === "42S01" ||
        $e->getCode() === "42P07"
    ) {
        echo "<p style='color: orange;'><strong>Note:</strong> The table may already exist. You can ignore this error if the table is already created.</p>";
        echo "<p><a href='create_task.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Go to Create Task</a></p>";
    }
}

