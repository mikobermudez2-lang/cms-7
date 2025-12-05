<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

header('Content-Type: application/json');

$patientId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$patientId) {
    http_response_code(400);
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT id, name, age, email, phone FROM patients WHERE id = ? LIMIT 1');
$stmt->execute([$patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(404);
    echo json_encode(['error' => 'Patient not found']);
    exit;
}

echo json_encode($patient);

