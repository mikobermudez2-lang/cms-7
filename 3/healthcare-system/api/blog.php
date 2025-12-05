<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$selectedDate = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
$postSlug = isset($_GET['post']) ? trim((string) $_GET['post']) : '';

try {
    $db = get_db();
    
    // Build query based on date filter
    if ($selectedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published"
               AND DATE(COALESCE(published_at, created_at)) = ?
             ORDER BY COALESCE(published_at, created_at) DESC'
        );
        $postsStmt->execute([$selectedDate]);
    } else {
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published"
               AND archived_at IS NULL
             ORDER BY COALESCE(published_at, created_at) DESC'
        );
        $postsStmt->execute();
    }
    $posts = $postsStmt->fetchAll();

    // Get all dates that have posts
    $datesStmt = $db->prepare(
        'SELECT DISTINCT DATE(COALESCE(published_at, created_at)) as post_date
         FROM posts
         WHERE status = "published"
         ORDER BY post_date DESC'
    );
    $datesStmt->execute();
    $postDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get selected post details
    $selectedPost = null;
    if ($postSlug !== '') {
        $selectedStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published" AND slug = ?'
        );
        $selectedStmt->execute([$postSlug]);
        $selectedPost = $selectedStmt->fetch() ?: null;
    }
    
    // Default to first post if no slug specified
    if (!$selectedPost && !empty($posts)) {
        $selectedPost = $posts[0];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'posts' => array_map(static function ($post) {
                return [
                    'id'           => $post['id'],
                    'title'        => $post['title'],
                    'slug'         => $post['slug'],
                    'excerpt'      => excerpt($post['content']),
                    'content'      => $post['content'],
                    'published_at' => $post['published_at'],
                    'created_at'   => $post['created_at'],
                    'archived_at'  => $post['archived_at'],
                ];
            }, $posts),
            'selectedPost' => $selectedPost ? [
                'id'           => $selectedPost['id'],
                'title'        => $selectedPost['title'],
                'slug'         => $selectedPost['slug'],
                'content'      => $selectedPost['content'],
                'published_at' => $selectedPost['published_at'],
                'created_at'   => $selectedPost['created_at'],
                'archived_at'  => $selectedPost['archived_at'],
            ] : null,
            'postDates' => $postDates,
        ],
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load posts.']);
}

