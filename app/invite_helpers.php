<?php

if (!function_exists('invite_make_open_link_email')) {
    function invite_make_open_link_email($token)
    {
        $token = strtolower(trim((string)$token));
        if ($token === '') {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (Throwable $e) {
                $token = hash('sha256', uniqid('open_link_', true) . microtime(true));
            }
        }

        // Synthetic address used to represent one-time shareable links.
        return '__open_link__+' . $token . '@join.taskflow.local';
    }
}

if (!function_exists('invite_is_open_link_email')) {
    function invite_is_open_link_email($email)
    {
        $email = strtolower(trim((string)$email));
        return str_starts_with($email, '__open_link__+') && str_ends_with($email, '@join.taskflow.local');
    }
}

if (!function_exists('invite_format_display_email')) {
    function invite_format_display_email($email)
    {
        if (invite_is_open_link_email($email)) {
            return 'One-time share link';
        }
        return (string)$email;
    }
}

if (!function_exists('invite_guess_name_from_email')) {
    function invite_guess_name_from_email($email)
    {
        $email = strtolower(trim((string)$email));
        $local = explode('@', $email)[0] ?? $email;
        $local = str_replace(['.', '_', '-'], ' ', $local);
        $local = preg_replace('/\s+/', ' ', trim($local));
        return $local === '' ? 'New Employee' : ucwords($local);
    }
}
