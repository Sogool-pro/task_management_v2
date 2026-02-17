<?php
/**
 * Database Reset Script
 *
 * Tenant-safe mode:
 * - Clears workspace activity data for one org_id.
 * - Keeps users and workspace settings.
 *
 * Global mode:
 * - Truncates all app tables.
 * - Recreates admin user (admin / admin123).
 *
 * Tenant-safe usage:
 *   Browser: /reset_database.php?org_id=1
 *   CLI:     php reset_database.php --org-id=1
 *
 * Global usage (explicit):
 *   1) Set ALLOW_GLOBAL_MAINTENANCE=1
 *   2) Browser: /reset_database.php?global=1
 *      or CLI:  php reset_database.php --global=1
 */

include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

function reset_table_exists(PDO $pdo, string $table): bool
{
    return tenant_table_exists($pdo, $table);
}

function reset_print_line(string $message): void
{
    echo $message . (PHP_SAPI === 'cli' ? PHP_EOL : "<br>");
}

try {
    $context = maintenance_require_org_context($pdo);
    $isGlobal = (bool)$context['global'];
    $orgId = $context['org_id'] !== null ? (int)$context['org_id'] : null;
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($isGlobal) {
        if (PHP_SAPI !== 'cli') {
            echo "<h2>Running Global Database Reset</h2><ul>";
        }
        reset_print_line("Global mode enabled.");

        $tables = [
            'group_message_reads',
            'group_messages',
            'chat_attachments',
            'chats',
            'screenshots',
            'attendance',
            'notifications',
            'leader_feedback',
            'subtasks',
            'task_assignees',
            'group_members',
            'groups',
            'tasks',
            'password_resets',
            'organization_members',
            'subscriptions',
            'users',
            'organizations'
        ];

        $existingTables = [];
        foreach ($tables as $table) {
            if (reset_table_exists($pdo, $table)) {
                $existingTables[] = $table;
            }
        }

        if ($driver === 'pgsql') {
            if (!empty($existingTables)) {
                $tableList = implode(', ', $existingTables);
                $pdo->exec("TRUNCATE TABLE {$tableList} RESTART IDENTITY CASCADE");
            }
            foreach ($existingTables as $table) {
                reset_print_line("Cleared table: {$table}");
            }
        } else {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            foreach ($existingTables as $table) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
                reset_print_line("Cleared table: {$table}");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }

        $adminPasswordPlain = 'admin123';
        $adminPasswordHash = password_hash($adminPasswordPlain, PASSWORD_DEFAULT);
        $tenantEnabled = maintenance_is_tenant_enabled($pdo);
        $defaultOrgId = null;

        if ($tenantEnabled && reset_table_exists($pdo, 'organizations')) {
            $stmtOrg = $pdo->prepare(
                "INSERT INTO organizations (name, slug, billing_email, status, plan_code)
                 VALUES ('Default Workspace', 'default-workspace', NULL, 'active', 'legacy')"
            );
            $stmtOrg->execute();
            $defaultOrgId = (int)$pdo->lastInsertId();
        }

        if ($tenantEnabled && $defaultOrgId !== null) {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, username, password, role, organization_id)
                 VALUES (?, ?, ?, 'admin', ?)"
            );
            $stmt->execute(['Administrator', 'admin', $adminPasswordHash, $defaultOrgId]);
            $adminUserId = (int)$pdo->lastInsertId();

            if (reset_table_exists($pdo, 'organization_members')) {
                $mStmt = $pdo->prepare(
                    "INSERT INTO organization_members (organization_id, user_id, role)
                     VALUES (?, ?, 'owner')"
                );
                $mStmt->execute([$defaultOrgId, $adminUserId]);
            }
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, 'admin')"
            );
            $stmt->execute(['Administrator', 'admin', $adminPasswordHash]);
        }

        reset_print_line("Created admin user.");

        if (PHP_SAPI !== 'cli') {
            echo "</ul>";
            echo "<h2 style='color: green;'>Global reset completed successfully.</h2>";
            echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>Admin Login Credentials:</h3>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
            echo "</div>";
            echo "<p><a href='login.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
        }
    } else {
        if ($orgId === null) {
            throw new RuntimeException('org_id is required in tenant-safe mode.');
        }
        if (PHP_SAPI !== 'cli') {
            echo "<h2>Resetting Workspace Data</h2><ul>";
        }
        reset_print_line("Tenant-safe mode for org_id={$orgId}");

        $tables = [
            'group_message_reads',
            'group_messages',
            'chats',
            'screenshots',
            'attendance',
            'notifications',
            'leader_feedback',
            'subtasks',
            'task_assignees',
            'group_members',
            'groups',
            'tasks',
            'password_resets'
        ];

        foreach ($tables as $table) {
            if (!reset_table_exists($pdo, $table)) {
                reset_print_line("Skipped missing table: {$table}");
                continue;
            }
            if (!tenant_column_exists($pdo, $table, 'organization_id')) {
                reset_print_line("Skipped unscoped table (no organization_id): {$table}");
                continue;
            }

            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE organization_id = ?");
            $stmt->execute([$orgId]);
            reset_print_line("Cleared {$table}: {$stmt->rowCount()} row(s)");
        }

        if (PHP_SAPI !== 'cli') {
            echo "</ul>";
            echo "<h2 style='color: green;'>Workspace reset completed successfully.</h2>";
            echo "<p>Only tenant-owned activity data was cleared. Users/workspace settings were kept.</p>";
        }
    }
} catch (PDOException $e) {
    if (PHP_SAPI !== 'cli') {
        echo "<h2 style='color: red;'>Error occurred:</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }

    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'pgsql') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    } catch (Throwable $ignored) {
        // no-op
    }
} catch (Throwable $e) {
    if (PHP_SAPI !== 'cli') {
        echo "<h2 style='color: red;'>Error occurred:</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
}
