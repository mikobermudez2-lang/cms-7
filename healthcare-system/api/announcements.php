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
    $stmt = $db->query(
        'SELECT id, message, created_at FROM announcements ORDER BY created_at DESC LIMIT 10'
    );
    $announcements = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => array_map(static function ($item) {
            return [
                'id' => (int) $item['id'],
                'message' => $item['message'],
                'created_at' => $item['created_at'],
            ];
        }, $announcements),
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load announcements.']);
}


