<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? 'posts'); // posts or jobs
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 10)));

// Optional filters
$options = [];
if (!empty($_GET['category'])) {
    $options['category_id'] = trim($_GET['category']);
}
if (!empty($_GET['date_from'])) {
    $options['date_from'] = trim($_GET['date_from']);
}
if (!empty($_GET['date_to'])) {
    $options['date_to'] = trim($_GET['date_to']);
}
if (!empty($_GET['department'])) {
    $options['department'] = trim($_GET['department']);
}
if (!empty($_GET['employment_type'])) {
    $options['employment_type'] = trim($_GET['employment_type']);
}

try {
    if ($type === 'jobs') {
        $results = search_jobs($query, $options, $page, $perPage);
    } else {
        // Try full-text search first, fall back to simple search
        try {
            $results = search_posts($query, $options, $page, $perPage);
        } catch (Throwable $e) {
            $results = search_posts_simple($query, $page, $perPage);
        }
    }
    
    // Format results for API
    $items = array_map(function ($item) use ($type) {
        if ($type === 'posts') {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'slug' => $item['slug'],
                'excerpt' => excerpt($item['content'] ?? '', 160),
                'category' => $item['category_name'] ?? null,
                'category_slug' => $item['category_slug'] ?? null,
                'author' => $item['author_name'] ?? null,
                'published_at' => $item['published_at'],
                'view_count' => $item['view_count'] ?? 0,
            ];
        } else {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'department' => $item['department'],
                'location' => $item['location'],
                'employment_type' => $item['employment_type'],
                'salary_range' => $item['salary_range'],
                'summary' => $item['summary'],
                'posted_at' => $item['posted_at'],
            ];
        }
    }, $results['items']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $items,
            'total' => $results['total'],
            'page' => $results['page'],
            'per_page' => $results['perPage'],
            'total_pages' => $results['totalPages'],
            'query' => $query,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed',
    ]);
}

