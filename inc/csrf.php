<?php

if (!function_exists('csrf_bootstrap_session')) {
    function csrf_bootstrap_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}

if (!function_exists('csrf_generate_token_value')) {
    function csrf_generate_token_value()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            return hash('sha256', uniqid('csrf_', true) . microtime(true));
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token($formKey = 'default', $ttlSeconds = 7200)
    {
        csrf_bootstrap_session();

        if (!isset($_SESSION['_csrf']) || !is_array($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = [];
        }

        $formKey = (string)$formKey;
        $ttlSeconds = max(300, (int)$ttlSeconds);
        $now = time();
        $stored = $_SESSION['_csrf'][$formKey] ?? null;

        if (
            !is_array($stored)
            || !isset($stored['value'], $stored['expires_at'])
            || !is_string($stored['value'])
            || (int)$stored['expires_at'] < $now
        ) {
            $_SESSION['_csrf'][$formKey] = [
                'value' => csrf_generate_token_value(),
                'expires_at' => $now + $ttlSeconds,
            ];
        }

        return (string)$_SESSION['_csrf'][$formKey]['value'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field($formKey = 'default', $fieldName = 'csrf_token')
    {
        $token = csrf_token($formKey);
        $safeFieldName = htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8');
        $safeToken = htmlspecialchars((string)$token, ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="' . $safeFieldName . '" value="' . $safeToken . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify($formKey = 'default', $submittedToken = null, $consume = true)
    {
        csrf_bootstrap_session();

        $formKey = (string)$formKey;
        $submittedToken = (string)($submittedToken ?? '');
        if ($submittedToken === '') {
            return false;
        }

        $stored = $_SESSION['_csrf'][$formKey] ?? null;
        if (!is_array($stored) || !isset($stored['value'], $stored['expires_at'])) {
            return false;
        }

        if ((int)$stored['expires_at'] < time()) {
            unset($_SESSION['_csrf'][$formKey]);
            return false;
        }

        $valid = hash_equals((string)$stored['value'], $submittedToken);
        if ($valid && $consume) {
            unset($_SESSION['_csrf'][$formKey]);
        }

        return $valid;
    }
}

