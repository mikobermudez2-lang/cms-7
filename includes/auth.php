<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';

/**
 * Attempt to log in a user with rate limiting
 */
function attempt_login(string $username, string $password): array
{
    $ip = get_client_ip();
    
    // Check rate limiting
    if (is_rate_limited($ip)) {
        $remaining = get_lockout_remaining($ip);
        return [
            'success' => false,
            'error' => 'Too many login attempts. Please try again in ' . ceil($remaining / 60) . ' minutes.',
            'locked' => true,
            'remaining' => $remaining,
        ];
    }
    
    $db = get_db();
    $stmt = $db->prepare('SELECT id, username, password, role, display_name, is_active FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        record_login_attempt($ip, $username, false);
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        record_login_attempt($ip, $username, false);
        return ['success' => false, 'error' => 'This account has been deactivated'];
    }

    // Verify password (supports both bcrypt and legacy plain text for migration)
    $isValid = verify_password($password, $user['password']);
    
    // Legacy support: check plain text (for migration only - remove in production)
    if (!$isValid && hash_equals($user['password'], $password)) {
        $isValid = true;
        // Upgrade to bcrypt
        $newHash = hash_password($password);
        $stmt = $db->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
        $stmt->execute([$newHash, $user['id']]);
    }

    if ($isValid) {
        // Clear failed attempts and record success
        clear_login_attempts($ip);
        record_login_attempt($ip, $username, true);
        
        // Update last login
        $stmt = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);
        
        $_SESSION['user'] = [
            'id'           => $user['id'],
            'username'     => $user['username'],
            'role'         => $user['role'],
            'display_name' => $user['display_name'] ?? $user['username'],
        ];
        
        log_activity('login', 'user', $user['id'], 'User logged in');
        
        return ['success' => true, 'user' => $_SESSION['user']];
    }

    record_login_attempt($ip, $username, false);
    return ['success' => false, 'error' => 'Invalid username or password'];
}

/**
 * Log out the current user
 */
function logout(): void
{
    $user = current_user();
    if ($user) {
        log_activity('logout', 'user', $user['id'], 'User logged out');
    }
    
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    session_destroy();
}

/**
 * Get the currently logged-in user
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Check if a user is logged in
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Require a specific role to access page
 */
function require_role(string $role): void
{
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        redirect('/admin/login.php');
    }
}

/**
 * Require admin role
 */
function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        redirect('/admin/login.php');
    }
}

/**
 * Require admin or staff role (for content management)
 */
function require_admin_or_staff(): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], ['admin', 'staff'], true)) {
        redirect('/admin/login.php');
    }
}

/**
 * Require any authenticated user (admin panel access)
 */
function require_auth(): void
{
    $user = current_user();
    if (!$user) {
        redirect('/admin/login.php');
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
 * Check if user can manage posts (create/edit)
 */
function can_manage_posts(): bool
{
    return is_admin() || is_staff();
}

/**
 * Check if user can manage all posts (including others')
 */
function can_manage_all_posts(): bool
{
    return is_admin() || is_staff();
}

/**
 * Check if user can manage users
 */
function can_manage_users(): bool
{
    return is_admin();
}

/**
 * Check if user can manage settings
 */
function can_manage_settings(): bool
{
    return is_admin();
}

// ============================================
// USER MANAGEMENT
// ============================================

/**
 * Get all users
 */
function get_users(): array
{
    $db = get_db();
    
    // Check which columns exist
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $hasAvatar = in_array('avatar', $columns, true);
    $hasBio = in_array('bio', $columns, true);
    $hasPasswordChanged = in_array('password_changed_at', $columns, true);
    
    // Build query based on available columns
    $selectFields = ['id', 'username', 'email', 'role', 'display_name', 'is_active', 'last_login_at', 'created_at'];
    if ($hasAvatar) {
        $selectFields[] = 'avatar';
    }
    if ($hasBio) {
        $selectFields[] = 'bio';
    }
    if ($hasPasswordChanged) {
        $selectFields[] = 'password_changed_at';
    }
    
    $sql = 'SELECT ' . implode(', ', $selectFields) . ' FROM users ORDER BY created_at DESC';
    $stmt = $db->query($sql);
    $users = $stmt->fetchAll();
    
    // Ensure all expected fields exist (set to null if missing)
    foreach ($users as &$user) {
        if (!isset($user['avatar'])) {
            $user['avatar'] = null;
        }
        if (!isset($user['bio'])) {
            $user['bio'] = null;
        }
        if (!isset($user['password_changed_at'])) {
            $user['password_changed_at'] = null;
        }
    }
    
    return $users;
}

/**
 * Get user by ID
 */
function get_user(string $id): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user by username
 */
function get_user_by_username(string $username): ?array
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a new user
 */
function create_user(array $data): ?string
{
    // Validate required fields
    if (empty($data['username']) || empty($data['password'])) {
        return null;
    }
    
    // Check if username exists
    if (get_user_by_username($data['username'])) {
        return null;
    }
    
    // Validate password
    $passwordErrors = validate_password($data['password']);
    if (!empty($passwordErrors)) {
        return null;
    }
    
    $db = get_db();
    $id = generate_id();
    
    $stmt = $db->prepare(
        'INSERT INTO users (id, username, email, password, role, display_name, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    
    $result = $stmt->execute([
        $id,
        $data['username'],
        $data['email'] ?? null,
        hash_password($data['password']),
        $data['role'] ?? 'staff',
        $data['display_name'] ?? $data['username'],
        $data['is_active'] ?? 1,
    ]);
    
    if ($result) {
        log_activity('create_user', 'user', $id, 'Created user: ' . $data['username']);
        return $id;
    }
    
    return null;
}

/**
 * Update a user
 */
function update_user(string $id, array $data): bool
{
    $db = get_db();
    
    $fields = [];
    $params = [];
    
    if (isset($data['email'])) {
        $fields[] = 'email = ?';
        $params[] = $data['email'];
    }
    if (isset($data['role'])) {
        $fields[] = 'role = ?';
        $params[] = $data['role'];
    }
    if (isset($data['display_name'])) {
        $fields[] = 'display_name = ?';
        $params[] = $data['display_name'];
    }
    if (isset($data['bio'])) {
        $fields[] = 'bio = ?';
        $params[] = $data['bio'];
    }
    if (isset($data['avatar'])) {
        $fields[] = 'avatar = ?';
        $params[] = $data['avatar'];
    }
    if (isset($data['is_active'])) {
        $fields[] = 'is_active = ?';
        $params[] = $data['is_active'] ? 1 : 0;
    }
    
    if (empty($fields)) {
        return false;
    }
    
    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        log_activity('update_user', 'user', $id, 'Updated user profile');
    }
    
    return $result;
}

/**
 * Change user password
 */
function change_password(string $userId, string $currentPassword, string $newPassword): array
{
    $user = get_user($userId);
    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }
    
    // Verify current password
    if (!verify_password($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
    }
    
    // Validate new password
    $passwordErrors = validate_password($newPassword);
    if (!empty($passwordErrors)) {
        return ['success' => false, 'errors' => $passwordErrors];
    }
    
    // Update password
    $db = get_db();
    $stmt = $db->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
    $result = $stmt->execute([hash_password($newPassword), $userId]);
    
    if ($result) {
        log_activity('change_password', 'user', $userId, 'Password changed');
        return ['success' => true];
    }
    
    return ['success' => false, 'error' => 'Failed to update password'];
}

/**
 * Admin reset password (no current password required)
 */
function reset_password(string $userId, string $newPassword): bool
{
    $passwordErrors = validate_password($newPassword);
    if (!empty($passwordErrors)) {
        return false;
    }
    
    $db = get_db();
    $stmt = $db->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
    $result = $stmt->execute([hash_password($newPassword), $userId]);
    
    if ($result) {
        log_activity('reset_password', 'user', $userId, 'Password reset by admin');
    }
    
    return $result;
}

/**
 * Delete a user
 */
function delete_user(string $id): bool
{
    $user = get_user($id);
    if (!$user) {
        return false;
    }
    
    // Don't allow deleting yourself
    $currentUser = current_user();
    if ($currentUser && $currentUser['id'] === $id) {
        return false;
    }
    
    $db = get_db();
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $result = $stmt->execute([$id]);
    
    if ($result) {
        log_activity('delete_user', 'user', $id, 'Deleted user: ' . $user['username']);
    }
    
    return $result;
}
