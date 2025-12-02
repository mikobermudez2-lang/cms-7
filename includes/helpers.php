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
    // Prevent output before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    if (preg_match('#^(?:[a-z][a-z0-9+\-.]*:)?//#i', $path)) {
        $target = $path;
    } elseif (str_starts_with($path, '/')) {
        $target = url($path);
    } else {
        $target = $path;
    }

    // Ensure no output has been sent
    if (!headers_sent()) {
        header("Location: {$target}", true, 302);
        exit;
    } else {
        // Fallback if headers already sent
        echo '<script>window.location.href = "' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">';
        exit;
    }
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
 * Generate a URL-friendly slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text ?: bin2hex(random_bytes(4));
}

/**
 * Create a plain-text excerpt from HTML content.
 */
function excerpt(string $html, int $length = 160): string
{
    $plain = trim(strip_tags($html));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $length)) . '...';
}

/**
 * Generate a unique ID string for database records.
 * Uses timestamp + random bytes for uniqueness.
 */
function generate_id(): string
{
    return bin2hex(random_bytes(16));
}

