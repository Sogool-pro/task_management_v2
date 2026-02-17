<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();
$context = maintenance_require_org_context($pdo);
$orgId = $context['org_id'] !== null ? (int)$context['org_id'] : null;

$taskExistsScope = "";
$taskCountScope = "";
if (tenant_column_exists($pdo, 'groups', 'organization_id') && tenant_column_exists($pdo, 'tasks', 'organization_id')) {
    $taskExistsScope = " AND t.organization_id = g.organization_id";
    $taskCountScope = " AND t2.organization_id = g.organization_id";
}

$sql = "SELECT g.id, g.name, g.type, g.task_id, g.created_at,
               EXISTS(SELECT 1 FROM tasks t WHERE t.id = g.task_id{$taskExistsScope}) AS task_exists_by_id,
               (SELECT COUNT(*) FROM tasks t2 WHERE t2.title = g.name{$taskCountScope}) AS task_count_by_title
        FROM groups g
        WHERE g.type = 'task_chat'";

$params = [];
$scope = tenant_get_scope($pdo, 'groups', 'g', 'AND', 'organization_id', $orgId);
$sql .= $scope['sql'] . "
        ORDER BY g.name ASC, g.created_at DESC, g.id DESC";
$params = array_merge($params, $scope['params']);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo implode(" | ", [
        "id=".$r['id'],
        "name=".$r['name'],
        "task_id=".($r['task_id'] === null ? "NULL" : $r['task_id']),
        "created_at=".$r['created_at'],
        "exists_by_id=".$r['task_exists_by_id'],
        "task_count_by_title=".$r['task_count_by_title']
    ]) . PHP_EOL;
}
