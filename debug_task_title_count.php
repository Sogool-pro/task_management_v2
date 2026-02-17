<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();
$context = maintenance_require_org_context($pdo);
$orgId = $context['org_id'] !== null ? (int)$context['org_id'] : null;

$title = "Fireguard";
$sql = "SELECT t.id, t.title, t.status, t.created_at
        FROM tasks t
        WHERE LOWER(t.title) = LOWER(?)";
$params = [$title];
$scope = tenant_get_scope($pdo, 'tasks', 't', 'AND', 'organization_id', $orgId);
$sql .= $scope['sql'] . " ORDER BY t.id DESC";
$params = array_merge($params, $scope['params']);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Tasks with title '{$title}': " . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo "id={$r['id']} | status={$r['status']} | created_at={$r['created_at']}" . PHP_EOL;
}
