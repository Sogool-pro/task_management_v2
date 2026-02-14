<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

function group_link_column_exists(PDO $pdo, string $driver, string $table, string $column): bool
{
    if ($driver === "mysql") {
        $sql = "SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND column_name = ?
                LIMIT 1";
    } else {
        $sql = "SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'public'
                  AND table_name = ?
                  AND column_name = ?
                LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function group_link_fk_exists(PDO $pdo, string $driver, string $table, string $constraint): bool
{
    if ($driver === "mysql") {
        $sql = "SELECT 1
                FROM information_schema.table_constraints
                WHERE constraint_schema = DATABASE()
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = 'FOREIGN KEY'
                LIMIT 1";
    } else {
        $sql = "SELECT 1
                FROM information_schema.table_constraints
                WHERE table_schema = 'public'
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = 'FOREIGN KEY'
                LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $constraint]);
    return (bool)$stmt->fetchColumn();
}

function group_link_index_exists(PDO $pdo, string $driver, string $table, string $indexName): bool
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
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if (!group_link_column_exists($pdo, $driver, "groups", "task_id")) {
        if ($driver === "mysql") {
            $pdo->exec("ALTER TABLE groups ADD COLUMN task_id INT NULL");
        } else {
            $pdo->exec("ALTER TABLE public.groups ADD COLUMN task_id integer");
        }
    }

    if (!group_link_fk_exists($pdo, $driver, "groups", "groups_task_id_fkey")) {
        if ($driver === "mysql") {
            $pdo->exec(
                "ALTER TABLE groups
                 ADD CONSTRAINT groups_task_id_fkey
                 FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE"
            );
        } else {
            $pdo->exec(
                "ALTER TABLE public.groups
                 ADD CONSTRAINT groups_task_id_fkey
                 FOREIGN KEY (task_id) REFERENCES public.tasks(id) ON DELETE CASCADE"
            );
        }
    }

    if (!group_link_index_exists($pdo, $driver, "groups", "idx_groups_task_chat_task_id")) {
        if ($driver === "mysql") {
            // MySQL/MariaDB has no partial-index predicate support like PostgreSQL.
            $pdo->exec("CREATE INDEX idx_groups_task_chat_task_id ON groups (type, task_id)");
        } else {
            $pdo->exec(
                "CREATE INDEX idx_groups_task_chat_task_id
                 ON public.groups USING btree (task_id)
                 WHERE (type = 'task_chat'::text)"
            );
        }
    }

    echo "Migration applied: groups.task_id link added/verified.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

