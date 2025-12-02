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

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $suggestions = get_search_suggestions($query, 5);
    echo json_encode([
        'success' => true,
        'data' => $suggestions,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => true, 'data' => []]);
}

