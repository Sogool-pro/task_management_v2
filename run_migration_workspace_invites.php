<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

function workspace_invites_index_exists(PDO $pdo, string $driver, string $table, string $indexName): bool
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

    if ($driver === "mysql") {
        $sql = "CREATE TABLE IF NOT EXISTS workspace_invites (
                    id INT NOT NULL AUTO_INCREMENT,
                    organization_id INT NOT NULL,
                    invited_by INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    full_name VARCHAR(120) DEFAULT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'employee',
                    token VARCHAR(64) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    expires_at DATETIME NOT NULL,
                    accepted_at DATETIME DEFAULT NULL,
                    accepted_user_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT workspace_invites_pkey PRIMARY KEY (id),
                    CONSTRAINT workspace_invites_token_key UNIQUE (token),
                    CONSTRAINT workspace_invites_org_fk FOREIGN KEY (organization_id) REFERENCES organizations (id),
                    CONSTRAINT workspace_invites_invited_by_fk FOREIGN KEY (invited_by) REFERENCES users (id),
                    CONSTRAINT workspace_invites_accepted_user_fk FOREIGN KEY (accepted_user_id) REFERENCES users (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS workspace_invites (
                    id SERIAL PRIMARY KEY,
                    organization_id INTEGER NOT NULL REFERENCES organizations(id),
                    invited_by INTEGER NOT NULL REFERENCES users(id),
                    email VARCHAR(255) NOT NULL,
                    full_name VARCHAR(120),
                    role VARCHAR(20) NOT NULL DEFAULT 'employee',
                    token VARCHAR(64) NOT NULL UNIQUE,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    expires_at TIMESTAMP NOT NULL,
                    accepted_at TIMESTAMP NULL,
                    accepted_user_id INTEGER NULL REFERENCES users(id),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
    }

    $pdo->exec($sql);

    if (!workspace_invites_index_exists($pdo, $driver, "workspace_invites", "idx_workspace_invites_org_status")) {
        $pdo->exec("CREATE INDEX idx_workspace_invites_org_status ON workspace_invites (organization_id, status)");
    }
    if (!workspace_invites_index_exists($pdo, $driver, "workspace_invites", "idx_workspace_invites_email_status")) {
        $pdo->exec("CREATE INDEX idx_workspace_invites_email_status ON workspace_invites (email, status)");
    }

    echo "Migration applied: workspace_invites table ready.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
