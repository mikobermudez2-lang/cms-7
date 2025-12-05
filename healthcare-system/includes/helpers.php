<?php

declare(strict_types=1);

/**
 * Escapes HTML entities for safe output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Returns the base URI (relative to the web server document root) for the app.
 */
function app_base_uri(): string
{
    static $base;

    if ($base !== null) {
        return $base;
    }

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $appRoot = realpath(__DIR__ . '/..');

    if (!$docRoot || !$appRoot) {
        return $base = '';
    }

    $docRoot = str_replace('\\', '/', realpath($docRoot) ?: $docRoot);
    $appRoot = str_replace('\\', '/', $appRoot);

    if (str_starts_with($appRoot, $docRoot)) {
        $relative = trim(substr($appRoot, strlen($docRoot)), '/');
        $base = $relative ? '/' . $relative : '';
    } else {
        $base = '';
    }

    return $base;
}

/**
 * Build a full URL (relative to this app) from a path that starts with a slash.
 */
function url(string $path): string
{
    if (preg_match('#^(?:[a-z][a-z0-9+\-.]*:)?//#i', $path)) {
        return $path;
    }

    if (!str_starts_with($path, '/')) {
        return $path;
    }

    $base = app_base_uri();
    return $base ? rtrim($base, '/') . $path : $path;
}

/**
 * Build an asset URL relative to the project root.
 */
function asset(string $path): string
{
    $path = '/' . ltrim($path, '/');
    return url($path);
}

/**
 * Simple redirect helper aware of the app base path.
 */
function redirect(string $path): void
{
    if (preg_match('#^(?:[a-z][a-z0-9+\-.]*:)?//#i', $path)) {
        $target = $path;
    } elseif (str_starts_with($path, '/')) {
        $target = url($path);
    } else {
        $target = $path;
    }

    header("Location: {$target}");
    exit;
}

/**
 * Creates a CSRF token stored in the session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token.
 */
function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

/**
 * Simple flash message helper.
 */
function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = compact('message', 'type');
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

/**
 * Fetch list of doctors for dropdowns.
 */
function fetch_doctors(): array
{
    $db = get_db();
    $stmt = $db->query('SELECT id, name, specialty FROM doctors ORDER BY name');
    return $stmt->fetchAll();
}

/**
 * Fetch list of patients for dropdowns.
 */
function fetch_patients(): array
{
    $db = get_db();
    $stmt = $db->query('SELECT id, name FROM patients ORDER BY name');
    return $stmt->fetchAll();
}


