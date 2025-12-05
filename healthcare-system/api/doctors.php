<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

try {
    $db = get_db();
    $stmt = $db->query('SELECT id, name, specialty, email, phone FROM doctors ORDER BY name');
    $doctors = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $doctors,
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load doctors']);
}


