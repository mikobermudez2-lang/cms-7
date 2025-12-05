<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = get_db();
    $stmt = $db->prepare(
        'SELECT id, title, slug, content, status, published_at, updated_at, archived_at
         FROM posts
         WHERE status = "published"
           AND archived_at IS NULL
         ORDER BY COALESCE(published_at, updated_at) DESC
         LIMIT 10'
    );
    $stmt->execute();
    $posts = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => array_map(static function ($post) {
            return [
                'id'           => (int) $post['id'],
                'title'        => $post['title'],
                'slug'         => $post['slug'],
                'excerpt'      => excerpt($post['content']),
                'content'      => $post['content'],
                'published_at' => $post['published_at'],
                'updated_at'   => $post['updated_at'],
            ];
        }, $posts),
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load posts.']);
}


