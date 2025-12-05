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

    $isValid = password_verify($password, $user['password']) || $password === $user['password'];

    if ($isValid) {
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];

        if ($user['role'] === 'doctor') {
            $_SESSION['doctor_id'] = get_doctor_id_for_user($user['username']);
        }

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

function require_doctor(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'doctor') {
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

/**
 * Check if current user is doctor
 */
function is_doctor(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'doctor';
}

/**
 * Check if current user has permission to perform an action
 * Returns true for admin, false for staff on restricted actions
 */
function can_manage_doctors(): bool
{
    return is_admin();
}

function can_manage_records(): bool
{
    return is_admin() || is_doctor();
}

function can_delete_appointments(): bool
{
    return is_admin();
}

function can_delete_announcements(): bool
{
    return is_admin();
}

function can_delete_patients(): bool
{
    return is_admin();
}

/**
 * Maps a doctor username (email) to a doctor_id by matching username to doctor's email.
 */
function get_doctor_id_for_user(string $username): ?int
{
    // First check the manual mapping (for backward compatibility)
    if (isset(DOCTOR_ACCOUNT_MAP[$username])) {
        return (int) DOCTOR_ACCOUNT_MAP[$username];
    }

    $db = get_db();
    
    // Username is now the doctor's email address
    // Match username (email) to doctor's email
    $stmt = $db->prepare('SELECT id FROM doctors WHERE LOWER(email) = ? LIMIT 1');
    $stmt->execute([strtolower($username)]);
    $doctorId = $stmt->fetchColumn();
    
    if ($doctorId) {
        return (int) $doctorId;
    }
    
    // Fallback: try to match by checking if username appears in doctor email (case-insensitive)
    $stmt = $db->prepare('SELECT id FROM doctors WHERE LOWER(email) LIKE ? LIMIT 1');
    $stmt->execute(["%{$username}%"]);
    $doctorId = $stmt->fetchColumn();
    
    if ($doctorId) {
        return (int) $doctorId;
    }
    
    // Last resort: return first doctor (for backward compatibility)
    $fallback = $db->query('SELECT id FROM doctors ORDER BY id ASC LIMIT 1')->fetchColumn();
    return $fallback ? (int) $fallback : null;
}


