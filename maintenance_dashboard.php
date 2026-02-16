<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

$tenantEnabled = maintenance_is_tenant_enabled($pdo);
$globalOverrideAllowed = maintenance_is_global_override_allowed();

$tenantScripts = [
    [
        'path' => 'reset_database.php',
        'label' => 'Reset Workspace Data',
        'description' => 'Deletes tenant activity records only.',
        'destructive' => true,
    ],
    [
        'path' => 'run_cleanup_orphan_task_chats.php',
        'label' => 'Cleanup Orphan Task Chats',
        'description' => 'Removes orphan task chat groups for this tenant.',
        'destructive' => true,
    ],
    [
        'path' => 'run_cleanup_legacy_duplicate_group_chats.php',
        'label' => 'Cleanup Duplicate Group Chats',
        'description' => 'Removes duplicate legacy group chat rows for this tenant.',
        'destructive' => true,
    ],
    [
        'path' => 'debug_task_chats.php',
        'label' => 'Debug Task Chats',
        'description' => 'Inspects task_chat groups for this tenant.',
        'destructive' => false,
    ],
    [
        'path' => 'debug_groups_type_counts.php',
        'label' => 'Debug Group Type Counts',
        'description' => 'Shows group type counts for this tenant.',
        'destructive' => false,
    ],
    [
        'path' => 'debug_task_title_count.php',
        'label' => 'Debug Task Title Count',
        'description' => 'Shows matching tasks by title for this tenant.',
        'destructive' => false,
    ],
];

$globalScripts = [
    [
        'path' => 'run_migration_workspace_invites.php',
        'label' => 'Run Invite Migration',
        'description' => 'Creates workspace_invites table and indexes.',
    ],
    [
        'path' => 'debug_schema.php',
        'label' => 'Debug Schema',
        'description' => 'Inspects subtasks table columns.',
    ],
    [
        'path' => 'debug_group_type_constraint.php',
        'label' => 'Debug Group Type Constraint',
        'description' => 'Inspects DB CHECK constraints on groups.',
    ],
];

$orgRows = [];
$queryError = null;

try {
    if ($tenantEnabled && tenant_table_exists($pdo, 'organizations')) {
        $hasMembers = tenant_table_exists($pdo, 'organization_members');
        $hasSubscriptions = tenant_table_exists($pdo, 'subscriptions');

        $sql = "SELECT o.id, o.name, o.slug, o.status, o.plan_code";
        if ($hasSubscriptions) {
            $sql .= ", s.status AS subscription_status, s.seat_limit";
        } else {
            $sql .= ", NULL AS subscription_status, NULL AS seat_limit";
        }
        if ($hasMembers) {
            $sql .= ", COUNT(DISTINCT om.user_id) AS member_count";
        } else {
            $sql .= ", 0 AS member_count";
        }

        $sql .= " FROM organizations o";
        if ($hasSubscriptions) {
            $sql .= " LEFT JOIN subscriptions s ON s.organization_id = o.id";
        }
        if ($hasMembers) {
            $sql .= " LEFT JOIN organization_members om ON om.organization_id = o.id";
        }

        $sql .= " GROUP BY o.id, o.name, o.slug, o.status, o.plan_code";
        if ($hasSubscriptions) {
            $sql .= ", s.status, s.seat_limit";
        }
        $sql .= " ORDER BY o.id ASC";

        $stmt = $pdo->query($sql);
        $orgRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $queryError = $e->getMessage();
}

function maintenance_build_link(string $path, ?int $orgId = null, bool $global = false): string
{
    if ($global) {
        return $path . '?global=1';
    }
    if ($orgId !== null && $orgId > 0) {
        return $path . '?org_id=' . (int)$orgId;
    }
    return $path;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Dashboard</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 24px;
        }
        h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        p {
            margin: 6px 0;
            color: #374151;
        }
        .note {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #3730a3;
            border-radius: 10px;
            padding: 10px 12px;
            margin-top: 8px;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            margin-right: 6px;
            background: #e5e7eb;
            color: #111827;
        }
        .ok {
            background: #dcfce7;
            color: #166534;
        }
        .warn {
            background: #fee2e2;
            color: #991b1b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 14px;
        }
        th {
            color: #6b7280;
            font-weight: 600;
        }
        .actions a {
            display: inline-block;
            margin: 2px 6px 2px 0;
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
            background: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            font-size: 12px;
        }
        .actions a.destructive {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }
        code {
            background: #f3f4f6;
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 12px;
        }
        @media (max-width: 900px) {
            body { padding: 12px; }
            th, td { font-size: 12px; }
            .actions a { margin-bottom: 4px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Maintenance Dashboard</h1>
        <p>Use this page to run maintenance/debug scripts per workspace without manually typing <code>org_id</code>.</p>
        <?php if ($tenantEnabled) { ?>
            <span class="pill ok">Tenant mode enabled</span>
        <?php } else { ?>
            <span class="pill warn">Tenant mode not detected</span>
        <?php } ?>
        <?php if ($globalOverrideAllowed) { ?>
            <span class="pill warn">Global override enabled</span>
        <?php } else { ?>
            <span class="pill">Global override disabled</span>
        <?php } ?>
        <div class="note">
            Global scripts and global reset are powerful. In tenant mode, prefer per-workspace actions.
        </div>
    </div>

    <div class="card">
        <h2>Workspace Actions</h2>
        <?php if ($queryError !== null) { ?>
            <div class="error">Failed to load organizations: <?= htmlspecialchars($queryError) ?></div>
        <?php } elseif (!$tenantEnabled) { ?>
            <p>Tenant columns are not enabled yet. Run your tenancy migration first.</p>
        <?php } elseif (empty($orgRows)) { ?>
            <p>No organizations found.</p>
        <?php } else { ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Workspace</th>
                    <th>Status</th>
                    <th>Plan</th>
                    <th>Members</th>
                    <th>Subscription</th>
                    <th>Run Script</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orgRows as $org) { ?>
                    <tr>
                        <td><code><?= (int)$org['id'] ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars((string)$org['name']) ?></strong><br>
                            <span style="color:#6b7280;">slug: <?= htmlspecialchars((string)$org['slug']) ?></span>
                        </td>
                        <td><?= htmlspecialchars((string)$org['status']) ?></td>
                        <td><?= htmlspecialchars((string)$org['plan_code']) ?></td>
                        <td><?= (int)$org['member_count'] ?></td>
                        <td>
                            <?= htmlspecialchars((string)($org['subscription_status'] ?? '-')) ?>
                            <?php if (!empty($org['seat_limit'])) { ?>
                                <br><span style="color:#6b7280;">seats: <?= (int)$org['seat_limit'] ?></span>
                            <?php } ?>
                        </td>
                        <td class="actions">
                            <?php foreach ($tenantScripts as $script) {
                                $href = maintenance_build_link($script['path'], (int)$org['id']);
                                $confirm = '';
                                if (!empty($script['destructive'])) {
                                    $confirm = "return confirm('Run {$script['label']} for workspace ID {$org['id']}? This can delete data.');";
                                }
                                ?>
                                <a
                                    href="<?= htmlspecialchars($href) ?>"
                                    title="<?= htmlspecialchars($script['description']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="<?= !empty($script['destructive']) ? 'destructive' : '' ?>"
                                    <?= $confirm ? 'onclick="' . htmlspecialchars($confirm) . '"' : '' ?>
                                ><?= htmlspecialchars($script['label']) ?></a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div>

    <div class="card">
        <h2>Global Scripts</h2>
        <p>These are not tenant-scoped diagnostics.</p>
        <div class="actions">
            <?php foreach ($globalScripts as $script) { ?>
                <a
                    href="<?= htmlspecialchars($script['path']) ?>"
                    title="<?= htmlspecialchars($script['description']) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                ><?= htmlspecialchars($script['label']) ?></a>
            <?php } ?>
        </div>
        <p style="margin-top:10px;">
            Global reset link:
            <?php if ($globalOverrideAllowed) { ?>
                <a
                    class="destructive"
                    href="<?= htmlspecialchars(maintenance_build_link('reset_database.php', null, true)) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    onclick="return confirm('Run GLOBAL reset? This will clear all tenants.');"
                >reset_database.php?global=1</a>
            <?php } else { ?>
                <code>Disabled (set ALLOW_GLOBAL_MAINTENANCE=1 to enable)</code>
            <?php } ?>
        </p>
    </div>

    <div class="card">
        <h2>CLI Examples</h2>
        <p><code>php reset_database.php --org-id=1</code></p>
        <p><code>php run_cleanup_orphan_task_chats.php --org-id=1</code></p>
        <p><code>php debug_task_chats.php --org-id=1</code></p>
        <p><code>php reset_database.php --global=1</code> (requires <code>ALLOW_GLOBAL_MAINTENANCE=1</code>)</p>
    </div>
</div>
</body>
</html>
