<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name       = trim($_POST['name'] ?? '');
$age        = trim($_POST['age'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$doctorId   = (int) ($_POST['doctor_id'] ?? 0);
$date       = isset($_POST['date']) && $_POST['date'] !== '' ? $_POST['date'] : null;
$time       = isset($_POST['time']) && $_POST['time'] !== '' ? $_POST['time'] : null;

// Validate required fields
if (!$name || !$age || !$email || !$phone || !$doctorId) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Validate name: only letters, spaces, apostrophes, and hyphens
if (!preg_match('/^[A-Za-z\s\'-]+$/', $name)) {
    echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, apostrophes, and hyphens.']);
    exit;
}

// Validate age: only numbers
if (!preg_match('/^[0-9]+$/', $age)) {
    echo json_encode(['success' => false, 'message' => 'Age must contain only numbers.']);
    exit;
}

// Validate phone: only numbers
if (!preg_match('/^[0-9]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number must contain only numbers.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Convert age to integer after validation
$age = (int) $age;

try {
    $db = get_db();
    $db->beginTransaction();

    $doctorCheck = $db->prepare('SELECT COUNT(*) FROM doctors WHERE id = ?');
    $doctorCheck->execute([$doctorId]);
    if (!$doctorCheck->fetchColumn()) {
        throw new RuntimeException('Doctor not found.');
    }

    // Store booking request with patient info directly in appointments table
    // Patient record will be created later by admin when processing
    $appointment = $db->prepare(
        'INSERT INTO appointments (patient_id, patient_name, patient_email, patient_phone, patient_age, doctor_id, date, time, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $appointment->execute([null, $name, $email, $phone, $age, $doctorId, $date, $time, 'Waiting']);

    $db->commit();

    // Send notification email to admin if mailer is available
    if (function_exists('send_appointment_request_notification')) {
        $doctorStmt = $db->prepare('SELECT name FROM doctors WHERE id = ?');
        $doctorStmt->execute([$doctorId]);
        $doctor = $doctorStmt->fetch();

        if ($doctor) {
            // Get admin email from config or use a default
            $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@healthcarecenter.com';
            send_appointment_request_notification(
                $adminEmail,
                [
                    'patient_name' => $name,
                    'patient_email' => $email,
                    'patient_phone' => $phone,
                    'doctor_name' => $doctor['name'],
                ]
            );
        }
    }

    echo json_encode(['success' => true]);
} catch (Throwable $th) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log the error for debugging
    error_log('Appointment booking error: ' . $th->getMessage());
    error_log('Stack trace: ' . $th->getTraceAsString());
    
    $isDoctorError = $th->getMessage() === 'Doctor not found.';
    http_response_code($isDoctorError ? 422 : 500);
    
    // For development: show actual error message
    // For production: use generic message
    $debugMode = defined('DEBUG_MODE') && DEBUG_MODE;
    $message = $isDoctorError 
        ? $th->getMessage() 
        : ($debugMode ? $th->getMessage() : 'Unable to save appointment. Please try again or contact support.');
    
    echo json_encode([
        'success' => false, 
        'message' => $message,
        'error' => $debugMode ? $th->getMessage() : null
    ]);
}


