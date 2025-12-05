<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Automatically archive published posts that are older than 7 days.
 * This function should be called on page loads or via cron job.
 * 
 * @return int Number of posts archived
 */
function auto_archive_old_posts(): int
{
    $db = get_db();
    
    try {
        // Archive posts that are:
        // - Published (status = 'published')
        // - Not already archived (archived_at IS NULL)
        // - Published more than 7 days ago
        $stmt = $db->prepare(
            'UPDATE posts
             SET archived_at = NOW()
             WHERE status = "published"
               AND archived_at IS NULL
               AND published_at IS NOT NULL
               AND published_at < DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (Throwable $th) {
        error_log('Error auto-archiving posts: ' . $th->getMessage());
        return 0;
    }
}

/**
 * Check if a post is archived
 * 
 * @param array $post Post data array
 * @return bool
 */
function is_post_archived(array $post): bool
{
    return !empty($post['archived_at']);
}

/**
 * Check if a post is recent (not archived and published within last 7 days)
 * 
 * @param array $post Post data array
 * @return bool
 */
function is_post_recent(array $post): bool
{
    if (empty($post['published_at']) || !empty($post['archived_at'])) {
        return false;
    }
    
    $publishedDate = new DateTime($post['published_at']);
    $sevenDaysAgo = new DateTime('-7 days');
    
    return $publishedDate >= $sevenDaysAgo;
}

