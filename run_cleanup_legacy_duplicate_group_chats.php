<?php
include "maintenance_guard.php";
include "DB_connection.php";
include "app/model/Group.php";

enforce_maintenance_script_access();
$context = maintenance_require_org_context($pdo);
$orgId = $context['org_id'] !== null ? (int)$context['org_id'] : null;
maintenance_bootstrap_tenant_context($orgId);

$sql = "SELECT g.name
        FROM groups g
        WHERE g.type = 'group'";
$params = [];
$scope = tenant_get_scope($pdo, 'groups', 'g', 'AND', 'organization_id', $orgId);
$sql .= $scope['sql'] . "
        GROUP BY LOWER(name), name
        HAVING COUNT(*) > 1";
$params = array_merge($params, $scope['params']);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$names = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

$total = 0;
foreach ($names as $name) {
    $total += (int)delete_legacy_duplicate_group_chats_by_title($pdo, $name);
}

$scopeLabel = $orgId !== null ? ("org_id=" . $orgId) : "GLOBAL";
echo "Legacy duplicate group-chat cleanup done ({$scopeLabel}). Deleted rows: " . $total . PHP_EOL;
