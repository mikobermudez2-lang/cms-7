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
        'patients' => (int) $db->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
        'doctors' => (int) $db->query('SELECT COUNT(*) FROM doctors')->fetchColumn(),
        'announcements' => (int) $db->query('SELECT COUNT(*) FROM announcements')->fetchColumn(),
    ];
    
    $appointmentsTodayCountStmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE date = CURDATE() AND time IS NOT NULL');
    $appointmentsTodayCountStmt->execute();
    $totals['appointments_today'] = (int) $appointmentsTodayCountStmt->fetchColumn();
    
    $appointmentsTodayStmt = $db->prepare(
        'SELECT a.id, p.name AS patient_name, d.name AS doctor_name, a.time, a.status
         FROM appointments a
         JOIN patients p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id
         WHERE a.date = CURDATE() AND a.time IS NOT NULL
         ORDER BY a.time ASC'
    );
    $appointmentsTodayStmt->execute();
    $appointmentsToday = $appointmentsTodayStmt->fetchAll();
    
    $announcementsStmt = $db->query('SELECT message, created_at FROM announcements ORDER BY created_at DESC LIMIT 3');
    $latestAnnouncements = $announcementsStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'totals' => $totals,
            'appointments_today' => $appointmentsToday,
            'latest_announcements' => $latestAnnouncements,
        ],
    ]);
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load dashboard stats.']);
}

