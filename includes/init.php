<?php

declare(strict_types=1);

// Start output buffering to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}

// Set timezone to Manila, Philippines
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Core includes
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/language.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/search.php';

// Jobs integration (if enabled)
if (defined('EXTERNAL_JOBS_ENABLED') && EXTERNAL_JOBS_ENABLED) {
    require_once __DIR__ . '/jobs_integration.php';
}

// Optional includes
if (file_exists(__DIR__ . '/archive_helper.php')) {
    require_once __DIR__ . '/archive_helper.php';
    // Auto-archive old posts on every page load (lightweight operation)
    if (function_exists('auto_archive_old_posts')) {
        auto_archive_old_posts();
    }
}

// Load PHPMailer if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/mailer.php')) {
        require_once __DIR__ . '/mailer.php';
    }
}

// Publish scheduled posts
publish_scheduled_posts();

/**
 * Publish posts that are scheduled for now or past
 */
function publish_scheduled_posts(): void
{
    try {
        $db = get_db();
        $stmt = $db->prepare(
            "UPDATE posts 
             SET status = 'published', published_at = scheduled_at 
             WHERE status = 'scheduled' AND scheduled_at <= NOW()"
        );
        $stmt->execute();
    } catch (Throwable $e) {
        // Silently fail - don't break the page
        error_log('Failed to publish scheduled posts: ' . $e->getMessage());
    }
}
