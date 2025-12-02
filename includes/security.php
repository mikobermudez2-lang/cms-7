<?php

declare(strict_types=1);

/**
 * Security functions: Rate limiting, password validation, input sanitization
 */

// ============================================
// RATE LIMITING
// ============================================

/**
 * Check if IP is rate limited for login attempts
 */
function is_rate_limited(string $ip, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    $db = get_db();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM login_attempts 
         WHERE ip_address = ? 
         AND success = 0 
         AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([$ip, $windowMinutes]);
    $count = (int) $stmt->fetchColumn();
    
    return $count >= $maxAttempts;
}

/**
 * Get remaining lockout time in seconds
 */
function get_lockout_remaining(string $ip, int $windowMinutes = 15): int
{
    $db = get_db();
    $stmt = $db->prepare(
        'SELECT attempted_at FROM login_attempts 
         WHERE ip_address = ? AND success = 0 
         ORDER BY attempted_at DESC LIMIT 1'
    );
    $stmt->execute([$ip]);
    $lastAttempt = $stmt->fetchColumn();
    
    if (!$lastAttempt) {
        return 0;
    }
    
    $unlockTime = strtotime($lastAttempt) + ($windowMinutes * 60);
    $remaining = $unlockTime - time();
    
    return max(0, $remaining);
}

/**
 * Record a login attempt
 */
function record_login_attempt(string $ip, ?string $username, bool $success): void
{
    $db = get_db();
    $stmt = $db->prepare(
        'INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)'
    );
    $stmt->execute([$ip, $username, $success ? 1 : 0]);
    
    // Clean old attempts (older than 24 hours)
    $db->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
}

/**
 * Clear successful login clears failed attempts for that IP
 */
function clear_login_attempts(string $ip): void
{
    $db = get_db();
    $stmt = $db->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);
}

/**
 * Get client IP address
 */
function get_client_ip(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

// ============================================
// PASSWORD SECURITY
// ============================================

/**
 * Hash a password using bcrypt
 */
function hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 */
function verify_password(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

/**
 * Check if password meets requirements
 * Returns array of errors, empty if valid
 */
function validate_password(string $password): array
{
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return $errors;
}

/**
 * Check if password needs rehashing (algorithm upgrade)
 */
function needs_password_rehash(string $hash): bool
{
    return \password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ============================================
// INPUT SANITIZATION
// ============================================

/**
 * Sanitize HTML content (for WYSIWYG editors)
 * Allows safe HTML tags, removes dangerous ones
 */
function sanitize_html(string $html): string
{
    // Allowed tags
    $allowedTags = '<p><br><strong><b><em><i><u><s><strike><sub><sup>'
        . '<h1><h2><h3><h4><h5><h6>'
        . '<ul><ol><li>'
        . '<a><img>'
        . '<table><thead><tbody><tfoot><tr><th><td>'
        . '<blockquote><pre><code>'
        . '<hr><div><span>';
    
    // Strip disallowed tags
    $html = strip_tags($html, $allowedTags);
    
    // Remove dangerous attributes using regex
    $dangerousPatterns = [
        // Remove event handlers
        '/\s+on\w+\s*=\s*["\'][^"\']*["\']/i',
        '/\s+on\w+\s*=\s*[^\s>]*/i',
        // Remove javascript: URLs
        '/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i',
        '/src\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i',
        // Remove data: URLs in src (except images)
        '/src\s*=\s*["\']?\s*data:(?!image\/)[^"\'>\s]*/i',
        // Remove style expressions
        '/style\s*=\s*["\'][^"\']*expression\s*\([^"\']*["\']/i',
        // Remove vbscript
        '/href\s*=\s*["\']?\s*vbscript:[^"\'>\s]*/i',
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        $html = preg_replace($pattern, '', $html);
    }
    
    return trim($html);
}

/**
 * Sanitize plain text input
 */
function sanitize_text(string $text): string
{
    return htmlspecialchars(trim($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitize_email(string $email): string
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate and sanitize URL
 */
function sanitize_url(string $url): ?string
{
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

// ============================================
// ACTIVITY LOGGING
// ============================================

/**
 * Log user activity
 */
function log_activity(
    string $action,
    ?string $entityType = null,
    ?string $entityId = null,
    ?string $description = null
): void {
    try {
        $user = current_user();
        $db = get_db();
        
        // Check if activity_logs table exists
        $tableExists = $db->query("SHOW TABLES LIKE 'activity_logs'")->fetch();
        if (!$tableExists) {
            return; // Silently fail if table doesn't exist
        }
        
        // Check which columns exist in activity_logs table
        $columns = $db->query("SHOW COLUMNS FROM activity_logs")->fetchAll(PDO::FETCH_COLUMN);
        $hasUserAgent = in_array('user_agent', $columns, true);
        
        // Build INSERT query based on available columns
        $insertFields = ['user_id', 'action', 'entity_type', 'entity_id', 'description', 'ip_address'];
        $insertValues = ['?', '?', '?', '?', '?', '?'];
        
        if ($hasUserAgent) {
            $insertFields[] = 'user_agent';
            $insertValues[] = '?';
        }
        
        $sql = 'INSERT INTO activity_logs (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $insertValues) . ')';
        $stmt = $db->prepare($sql);
        
        $params = [
            $user['id'] ?? null,
            $action,
            $entityType,
            $entityId,
            $description,
            get_client_ip()
        ];
        
        if ($hasUserAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            if ($userAgent && strlen($userAgent) > 255) {
                $userAgent = substr($userAgent, 0, 255);
            }
            $params[] = $userAgent;
        }
        
        $stmt->execute($params);
    } catch (PDOException $e) {
        // Log database errors for debugging
        error_log('Failed to log activity (PDO): ' . $e->getMessage());
        error_log('SQL State: ' . $e->getCode());
        error_log('SQL Error Info: ' . print_r($e->errorInfo ?? [], true));
    } catch (Throwable $e) {
        // Log other errors for debugging
        error_log('Failed to log activity: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
    }
}

/**
 * Get recent activity logs
 */
function get_activity_logs(int $limit = 50, ?string $userId = null): array
{
    $db = get_db();
    
    $sql = 'SELECT al.*, u.username, u.display_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id';
    
    if ($userId) {
        $sql .= ' WHERE al.user_id = ?';
        $sql .= ' ORDER BY al.created_at DESC LIMIT ?';
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $limit]);
    } else {
        $sql .= ' ORDER BY al.created_at DESC LIMIT ?';
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
    }
    
    return $stmt->fetchAll();
}

