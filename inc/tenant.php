<?php

if (!function_exists('tenant_column_exists')) {
    function tenant_column_exists($pdo, $table, $column)
    {
        $sql = "SELECT 1
                FROM information_schema.columns
                WHERE table_name = ? AND column_name = ?
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('tenant_table_exists')) {
    function tenant_table_exists($pdo, $table)
    {
        $sql = "SELECT 1
                FROM information_schema.tables
                WHERE table_name = ?
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('tenant_get_current_org_id')) {
    function tenant_get_current_org_id()
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['organization_id'])) {
            $orgId = (int)$_SESSION['organization_id'];
            return $orgId > 0 ? $orgId : null;
        }
        return null;
    }
}

if (!function_exists('tenant_get_scope')) {
    function tenant_get_scope($pdo, $table, $alias = '', $joinWord = 'AND', $column = 'organization_id', $orgId = null)
    {
        if (!tenant_column_exists($pdo, $table, $column)) {
            return ['sql' => '', 'params' => []];
        }

        $resolvedOrgId = $orgId !== null ? (int)$orgId : tenant_get_current_org_id();
        if ($resolvedOrgId <= 0) {
            return ['sql' => '', 'params' => []];
        }

        $qualified = $alias !== '' ? "{$alias}.{$column}" : $column;
        $joinWord = strtoupper(trim($joinWord)) === 'WHERE' ? 'WHERE' : 'AND';

        return [
            'sql' => " {$joinWord} {$qualified} = ?",
            'params' => [$resolvedOrgId]
        ];
    }
}

if (!function_exists('tenant_resolve_user_org')) {
    function tenant_resolve_user_org($pdo, $userId, $fallbackOrgId = null)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return $fallbackOrgId ? (int)$fallbackOrgId : null;
        }

        if (tenant_table_exists($pdo, 'organization_members')) {
            $stmt = $pdo->prepare(
                "SELECT organization_id
                 FROM organization_members
                 WHERE user_id = ?
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $orgId = $stmt->fetchColumn();
            if ($orgId) {
                return (int)$orgId;
            }
        }

        if (tenant_column_exists($pdo, 'users', 'organization_id')) {
            $stmt = $pdo->prepare("SELECT organization_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $orgId = $stmt->fetchColumn();
            if ($orgId) {
                return (int)$orgId;
            }
        }

        return $fallbackOrgId ? (int)$fallbackOrgId : null;
    }
}

if (!function_exists('tenant_resolve_user_membership_role')) {
    function tenant_resolve_user_membership_role($pdo, $userId, $orgId = null, $fallbackRole = 'member')
    {
        $userId = (int)$userId;
        $orgId = $orgId !== null ? (int)$orgId : tenant_get_current_org_id();

        if ($userId <= 0 || $orgId <= 0 || !tenant_table_exists($pdo, 'organization_members')) {
            return $fallbackRole;
        }

        $stmt = $pdo->prepare(
            "SELECT role
             FROM organization_members
             WHERE user_id = ? AND organization_id = ?
             LIMIT 1"
        );
        $stmt->execute([$userId, $orgId]);
        $role = $stmt->fetchColumn();

        return $role ? (string)$role : $fallbackRole;
    }
}
