<?php
include "maintenance_guard.php";
include "DB_connection.php";
include "app/model/Group.php";

enforce_maintenance_script_access();
$context = maintenance_require_org_context($pdo);
maintenance_bootstrap_tenant_context($context['org_id']);

$deleted = delete_orphan_task_chat_groups($pdo);
$scopeLabel = $context['org_id'] !== null ? ("org_id=" . (int)$context['org_id']) : "GLOBAL";
echo "Orphan task chat cleanup done ({$scopeLabel}). Deleted rows: " . (int)$deleted . "\n";
