<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function attempt_login(string $username, string $password): bool
{
    $db = get_db();
    $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    $isValid = password_verify($password, $user['password']) || hash_equals($user['password'], $password);

    if ($isValid) {
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        return true;
    }

    return false;
}

function logout(): void
{
    session_destroy();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_role(string $role): void
{
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        redirect('login.php');
    }
}

function require_staff(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'staff') {
        redirect('login.php');
    }
}

/**
 * Require admin or staff role (for admin panel access)
 */
function require_admin_or_staff(): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], ['admin', 'staff'], true)) {
        redirect('login.php');
    }
}

/**
 * Check if current user is admin
 */
function is_admin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

/**
 * Check if current user is staff
 */
function is_staff(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'staff';
}

function can_manage_posts(): bool
{
    return is_admin() || is_staff();
}


