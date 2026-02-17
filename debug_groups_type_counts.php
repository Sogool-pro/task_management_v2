<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();
$context = maintenance_require_org_context($pdo);
$orgId = $context['org_id'] !== null ? (int)$context['org_id'] : null;

$sql = "SELECT COALESCE(g.type, 'NULL') AS grp_type, COUNT(*) AS cnt
        FROM groups g
        WHERE 1=1";
$params = [];
$scope = tenant_get_scope($pdo, 'groups', 'g', 'AND', 'organization_id', $orgId);
$sql .= $scope['sql'] . "
        GROUP BY COALESCE(g.type, 'NULL')
        ORDER BY cnt DESC";
$params = array_merge($params, $scope['params']);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['grp_type'] . " => " . $r['cnt'] . PHP_EOL;
}

echo PHP_EOL . "Sample Fireguard rows:" . PHP_EOL;
$sql2 = "SELECT g.id, g.name, COALESCE(g.type, 'NULL') AS grp_type, g.task_id, g.created_at
         FROM groups g
         WHERE LOWER(g.name) = LOWER(?)";
$params2 = ['Fireguard'];
$scope = tenant_get_scope($pdo, 'groups', 'g', 'AND', 'organization_id', $orgId);
$sql2 .= $scope['sql'] . "
         ORDER BY g.id DESC";
$params2 = array_merge($params2, $scope['params']);
$s2 = $pdo->prepare($sql2);
$s2->execute($params2);
$rows2 = $s2->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows2 as $r) {
    echo "id={$r['id']} | name={$r['name']} | type={$r['grp_type']} | task_id=" . ($r['task_id'] === null ? 'NULL' : $r['task_id']) . " | created_at={$r['created_at']}" . PHP_EOL;
}
