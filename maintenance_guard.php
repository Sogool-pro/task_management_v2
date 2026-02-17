<?php
/*
 * Shared access guard for maintenance/debug scripts.
 *
 * Rules:
 * - Always allow CLI execution.
 * - Allow if ALLOW_MAINTENANCE_SCRIPTS=true/1 is set.
 * - Allow localhost requests when APP_ENV is not production.
 */

require_once __DIR__ . '/inc/tenant.php';

if (!function_exists('is_maintenance_script_allowed')) {
    function is_maintenance_script_allowed()
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        $explicit = getenv('ALLOW_MAINTENANCE_SCRIPTS');
        if ($explicit !== false) {
            $normalized = strtolower(trim((string)$explicit));
            if ($normalized === '1' || $normalized === 'true' || $normalized === 'yes') {
                return true;
            }
        }

        $appEnv = strtolower((string)(getenv('APP_ENV') ?: 'development'));
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $isLocalhost = in_array($remoteAddr, ['127.0.0.1', '::1'], true);

        return $isLocalhost && $appEnv !== 'production';
    }
}

if (!function_exists('enforce_maintenance_script_access')) {
    function enforce_maintenance_script_access()
    {
        if (is_maintenance_script_allowed()) {
            return;
        }

        if (PHP_SAPI !== 'cli') {
            http_response_code(403);
        }

        exit('Forbidden: maintenance script access is disabled.');
    }
}

if (!function_exists('maintenance_truthy')) {
    function maintenance_truthy($value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }
        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('maintenance_get_cli_option')) {
    function maintenance_get_cli_option(string $name): ?string
    {
        if (PHP_SAPI !== 'cli' || !isset($GLOBALS['argv']) || !is_array($GLOBALS['argv'])) {
            return null;
        }

        $prefix = '--' . $name . '=';
        foreach ($GLOBALS['argv'] as $arg) {
            if (strpos($arg, $prefix) === 0) {
                return substr($arg, strlen($prefix));
            }
        }

        return null;
    }
}

if (!function_exists('maintenance_is_global_override_requested')) {
    function maintenance_is_global_override_requested(): bool
    {
        $fromQuery = $_GET['global'] ?? null;
        $fromCli = maintenance_get_cli_option('global');
        return maintenance_truthy($fromQuery) || maintenance_truthy($fromCli);
    }
}

if (!function_exists('maintenance_is_global_override_allowed')) {
    function maintenance_is_global_override_allowed(): bool
    {
        return maintenance_truthy(getenv('ALLOW_GLOBAL_MAINTENANCE'));
    }
}

if (!function_exists('maintenance_get_requested_org_id')) {
    function maintenance_get_requested_org_id(): ?int
    {
        $raw = $_GET['org_id'] ?? maintenance_get_cli_option('org-id');
        if ($raw === null || $raw === '') {
            $raw = getenv('MAINTENANCE_ORG_ID');
        }
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }
        $orgId = (int)$raw;
        return $orgId > 0 ? $orgId : null;
    }
}

if (!function_exists('maintenance_is_tenant_enabled')) {
    function maintenance_is_tenant_enabled(PDO $pdo): bool
    {
        return tenant_column_exists($pdo, 'users', 'organization_id');
    }
}

if (!function_exists('maintenance_org_exists')) {
    function maintenance_org_exists(PDO $pdo, int $orgId): bool
    {
        if ($orgId <= 0) {
            return false;
        }
        if (!tenant_table_exists($pdo, 'organizations')) {
            return true;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM organizations WHERE id = ? LIMIT 1");
        $stmt->execute([$orgId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('maintenance_require_org_context')) {
    function maintenance_require_org_context(PDO $pdo): array
    {
        $tenantEnabled = maintenance_is_tenant_enabled($pdo);
        $requestedOrgId = maintenance_get_requested_org_id();
        $globalRequested = maintenance_is_global_override_requested();
        $globalAllowed = maintenance_is_global_override_allowed();

        if (!$tenantEnabled) {
            return ['global' => true, 'org_id' => null];
        }

        if ($requestedOrgId !== null) {
            if (!maintenance_org_exists($pdo, $requestedOrgId)) {
                exit("Invalid org_id. Workspace not found.\n");
            }
            return ['global' => false, 'org_id' => $requestedOrgId];
        }

        if ($globalRequested && $globalAllowed) {
            return ['global' => true, 'org_id' => null];
        }

        $message = "Tenant-safe mode: provide org_id (query param or --org-id=ID). "
            . "For full global mode, set ALLOW_GLOBAL_MAINTENANCE=1 and pass global=1.";
        exit($message . "\n");
    }
}

if (!function_exists('maintenance_bootstrap_tenant_context')) {
    function maintenance_bootstrap_tenant_context(?int $orgId): void
    {
        if ($orgId === null || $orgId <= 0) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['organization_id'] = (int)$orgId;
    }
}
