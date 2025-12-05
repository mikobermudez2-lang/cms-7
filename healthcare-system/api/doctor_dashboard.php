<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = get_db();
    $doctorId = $_SESSION['doctor_id'] ?? null;
    
    if (!$doctorId) {
        $doctorId = get_doctor_id_for_user(current_user()['username']);
        $_SESSION['doctor_id'] = $doctorId;
    }
    
    $today = date('Y-m-d');
    
    $counts = [
        'today' => 0,
        'upcoming' => 0,
        'patients' => 0,
    ];
    
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND date = ? AND time IS NOT NULL');
    $stmt->execute([$doctorId, $today]);
    $counts['today'] = (int) $stmt->fetchColumn();
    
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND date >= ? AND status IN ("Waiting","Confirmed") AND time IS NOT NULL');
    $stmt->execute([$doctorId, $today]);
    $counts['upcoming'] = (int) $stmt->fetchColumn();
    
    $stmt = $db->prepare('SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?');
    $stmt->execute([$doctorId]);
    $counts['patients'] = (int) $stmt->fetchColumn();
    
    $upcomingStmt = $db->prepare(
        'SELECT a.*, p.name AS patient_name
         FROM appointments a
         JOIN patients p ON p.id = a.patient_id
         WHERE a.doctor_id = ?
         ORDER BY a.date ASC, a.time ASC
         LIMIT 10'
    );
    $upcomingStmt->execute([$doctorId]);
    $appointments = $upcomingStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'counts' => $counts,
            'appointments' => $appointments,
        ],
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load dashboard stats.']);
}

