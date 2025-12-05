<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = get_db();
    
    $totals = [
        'total_posts'     => (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
        'published_posts' => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
        'draft_posts'     => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
    ];
    
    $recentPostsStmt = $db->query(
        'SELECT id, title, status, updated_at, published_at 
         FROM posts 
         ORDER BY updated_at DESC 
         LIMIT 5'
    );
    $recentPosts = $recentPostsStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => $totals,
            'recent_posts' => $recentPosts,
        ],
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load dashboard stats.']);
}

