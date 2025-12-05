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
    $filterStatus = $_GET['status'] ?? '';
    $filterDate = $_GET['date'] ?? '';
    
    $checkColumns = $db->query("SHOW COLUMNS FROM appointments LIKE 'patient_name'");
    $hasNewColumns = $checkColumns->rowCount() > 0;
    
    if ($hasNewColumns) {
        $query = 'SELECT a.*, 
                         COALESCE(p.name, a.patient_name) AS patient_name,
                         COALESCE(p.email, a.patient_email) AS patient_email,
                         COALESCE(p.phone, a.patient_phone) AS patient_phone,
                         COALESCE(p.age, a.patient_age) AS patient_age,
                         d.name AS doctor_name
                  FROM appointments a
                  LEFT JOIN patients p ON p.id = a.patient_id
                  JOIN doctors d ON d.id = a.doctor_id';
    } else {
        $query = 'SELECT a.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone, p.age AS patient_age, d.name AS doctor_name
                  FROM appointments a
                  JOIN patients p ON p.id = a.patient_id
                  JOIN doctors d ON d.id = a.doctor_id';
    }
    
    $conditions = [];
    $params = [];
    
    if ($filterStatus) {
        $conditions[] = 'a.status = ?';
        $params[] = $filterStatus;
    }
    if ($filterDate) {
        $conditions[] = 'a.date = ?';
        $params[] = $filterDate;
    }
    if ($conditions) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $query .= ' ORDER BY a.date DESC, a.time DESC';
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $appointments,
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load appointments.']);
}

