<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;
$statuses = ['Waiting', 'Confirmed', 'Completed', 'Rejected', 'Cancelled'];

// Check if new columns exist (needed for POST handling)
$checkColumns = $db->query("SHOW COLUMNS FROM appointments LIKE 'patient_name'");
$hasNewColumns = $checkColumns->rowCount() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session.';
    } else {
        $appointmentId = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $patientId = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? (int) $_POST['patient_id'] : 0;
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $dateInput = trim((string) ($_POST['date'] ?? ''));
        $timeInput = trim((string) ($_POST['time'] ?? ''));
        $status = $_POST['status'] ?? 'Waiting';
        
        // Debug: Log the appointment ID to ensure it's being passed
        if ($appointmentId) {
            error_log("Updating appointment ID: {$appointmentId}");
        }

        $hasDate = $dateInput !== '';
        $hasTime = $timeInput !== '';
        $hasSchedule = $hasDate && $hasTime;
        $requiresSchedule = in_array($status, ['Confirmed', 'Completed'], true);

        if ($hasDate xor $hasTime) {
            $flashError = 'Provide both date and time or leave them blank.';
        } elseif ($requiresSchedule && !$hasSchedule) {
            $flashError = 'Set a date and time before confirming or completing an appointment.';
        } elseif (!$doctorId || !in_array($status, $statuses, true)) {
            $flashError = 'Doctor and status are required.';
        } elseif ($hasSchedule) {
            // Validate date range: not in past, not more than 1 year in future
            $selectedDate = new DateTime($dateInput);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $maxDate = new DateTime();
            $maxDate->setTime(0, 0, 0);
            $maxDate->modify('+1 year');
            
            if ($selectedDate < $today) {
                $flashError = 'Scheduled date cannot be in the past.';
            } elseif ($selectedDate > $maxDate) {
                $flashError = 'Scheduled date cannot be more than one year in the future.';
            } elseif ($selectedDate == $today && $hasTime) {
                // If date is today, check if time is in the past
                $selectedDateTime = new DateTime($dateInput . ' ' . $timeInput);
                $now = new DateTime();
                if ($selectedDateTime < $now) {
                    $flashError = 'Scheduled time cannot be in the past for today\'s date.';
                }
            }
        }
        
        // Patient validation (only if no error so far)
        if (!$flashError) {
            // If no patient_id but editing a booking request, we'll create patient record from booking info
            if ($appointmentId && !$patientId && $hasNewColumns) {
                $checkBooking = $db->prepare('SELECT patient_name, patient_email FROM appointments WHERE id = ?');
                $checkBooking->execute([$appointmentId]);
                $bookingInfo = $checkBooking->fetch();
                if (!$bookingInfo || !$bookingInfo['patient_name']) {
                    $flashError = 'Patient is required or booking info must be available.';
                }
            } elseif (!$appointmentId && !$patientId) {
                $flashError = 'Patient is required for new appointments.';
            } elseif ($appointmentId && !$patientId && !$hasNewColumns) {
                $flashError = 'Patient is required. Please update database schema first.';
            }
        }
        
        // Process update/insert if no errors
        if (!$flashError) {
            $dateValue = $hasSchedule ? $dateInput : null;
            $timeValue = $hasSchedule ? $timeInput : null;

            $statusChanged = false; // Initialize variable
            // If editing and patient_id is not set but we have patient info in appointment, create patient record
            if ($appointmentId && !$patientId && $hasNewColumns) {
                $oldStmt = $db->prepare('SELECT patient_name, patient_email, patient_phone, patient_age FROM appointments WHERE id = ?');
                $oldStmt->execute([$appointmentId]);
                $oldAppt = $oldStmt->fetch();
                
                if ($oldAppt && $oldAppt['patient_name'] && $oldAppt['patient_email']) {
                    // Check if patient already exists by email
                    $checkStmt = $db->prepare('SELECT id FROM patients WHERE email = ? LIMIT 1');
                    $checkStmt->execute([$oldAppt['patient_email']]);
                    $existingPatientId = $checkStmt->fetchColumn();
                    
                    if ($existingPatientId) {
                        $patientId = (int) $existingPatientId;
                        // Update patient info
                        $updatePatient = $db->prepare('UPDATE patients SET name = ?, age = ?, phone = ? WHERE id = ?');
                        $updatePatient->execute([$oldAppt['patient_name'], $oldAppt['patient_age'], $oldAppt['patient_phone'], $patientId]);
                    } else {
                        // Create new patient record
                        $createPatient = $db->prepare('INSERT INTO patients (name, age, email, phone) VALUES (?, ?, ?, ?)');
                        $createPatient->execute([$oldAppt['patient_name'], $oldAppt['patient_age'], $oldAppt['patient_email'], $oldAppt['patient_phone']]);
                        $patientId = (int) $db->lastInsertId();
                    }
                    
                    // Note: Record will be created when appointment is confirmed (see below)
                }
            }
        
            if ($appointmentId && $appointmentId > 0) {
                // Verify appointment exists
                $verifyStmt = $db->prepare('SELECT id FROM appointments WHERE id = ?');
                $verifyStmt->execute([$appointmentId]);
                if (!$verifyStmt->fetch()) {
                    $flashError = 'Appointment not found.';
                } else {
                    // Check if status changed (to send email notification)
                    $oldStmt = $db->prepare('SELECT status FROM appointments WHERE id = ?');
                    $oldStmt->execute([$appointmentId]);
                    $oldStatus = $oldStmt->fetchColumn();
                    $statusChanged = ($oldStatus !== $status);

                    // Use NULL for patient_id if it's 0 (for booking requests that haven't been processed yet)
                    $patientIdValue = $patientId > 0 ? $patientId : null;
                    
                    if ($hasNewColumns) {
                        // Update with patient_id (can be NULL for unprocessed bookings)
                        // Clear patient_name, patient_email, etc. if patient_id is assigned
                        if ($patientIdValue) {
                            $stmt = $db->prepare(
                                'UPDATE appointments SET patient_id = ?, doctor_id = ?, date = ?, time = ?, status = ?, patient_name = NULL, patient_email = NULL, patient_phone = NULL, patient_age = NULL WHERE id = ?'
                            );
                        } else {
                            $stmt = $db->prepare(
                                'UPDATE appointments SET patient_id = ?, doctor_id = ?, date = ?, time = ?, status = ? WHERE id = ?'
                            );
                        }
                        $result = $stmt->execute([$patientIdValue, $doctorId, $dateValue, $timeValue, $status, $appointmentId]);
                        
                        if (!$result || $stmt->rowCount() === 0) {
                            $flashError = 'Failed to update appointment. Please try again.';
                        }
                    } else {
                        // Old schema requires patient_id
                        if (!$patientIdValue) {
                            $flashError = 'Patient is required. Please select a patient.';
                        } else {
                            $stmt = $db->prepare(
                                'UPDATE appointments SET patient_id = ?, doctor_id = ?, date = ?, time = ?, status = ? WHERE id = ?'
                            );
                            $result = $stmt->execute([$patientIdValue, $doctorId, $dateValue, $timeValue, $status, $appointmentId]);
                            
                            if (!$result || $stmt->rowCount() === 0) {
                                $flashError = 'Failed to update appointment. Please try again.';
                            }
                        }
                    }
                    
                    if (!$flashError) {
                        set_flash('Appointment updated.');
                        
                        // Create record when appointment is confirmed (flow: Booking → Confirmed → Active Patient → Record)
                        if ($status === 'Confirmed' && $patientId > 0) {
                            $recordCheck = $db->prepare('SELECT id FROM records WHERE patient_id = ? LIMIT 1');
                            $recordCheck->execute([$patientId]);
                            $existingRecord = $recordCheck->fetch();
                            
                            if (!$existingRecord) {
                                // Get patient info for record
                                $patientStmt = $db->prepare('SELECT name, age, email, phone FROM patients WHERE id = ?');
                                $patientStmt->execute([$patientId]);
                                $patientData = $patientStmt->fetch();
                                
                                if ($patientData) {
                                    $patientInfo = "- Name: {$patientData['name']}\n- Age: {$patientData['age']}\n- Email: {$patientData['email']}\n- Phone: {$patientData['phone']}\n\nAppointment confirmed: " . date('Y-m-d H:i:s');
                                    $createRecord = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                                    $createRecord->execute([$patientId, $patientInfo]);
                                }
                            }
                        }
                    }
                }
            } else {
                $statusChanged = true; // New appointment, send email
                $stmt = $db->prepare(
                    'INSERT INTO appointments (patient_id, doctor_id, date, time, status) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$patientId, $doctorId, $dateValue, $timeValue, $status]);
                set_flash('Appointment created.');
                
                // Create record when new appointment is confirmed (flow: Booking → Confirmed → Active Patient → Record)
                if ($status === 'Confirmed' && $patientId > 0) {
                    $recordCheck = $db->prepare('SELECT id FROM records WHERE patient_id = ? LIMIT 1');
                    $recordCheck->execute([$patientId]);
                    $existingRecord = $recordCheck->fetch();
                    
                    if (!$existingRecord) {
                        // Get patient info for record
                        $patientStmt = $db->prepare('SELECT name, age, email, phone FROM patients WHERE id = ?');
                        $patientStmt->execute([$patientId]);
                        $patientData = $patientStmt->fetch();
                        
                        if ($patientData) {
                            $patientInfo = "- Name: {$patientData['name']}\n- Age: {$patientData['age']}\n- Email: {$patientData['email']}\n- Phone: {$patientData['phone']}\n\nAppointment confirmed: " . date('Y-m-d H:i:s');
                            $createRecord = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                            $createRecord->execute([$patientId, $patientInfo]);
                        }
                    }
                }
            }

            // Send email notification for all status changes
            if ($statusChanged && function_exists('send_appointment_confirmation')) {
                // Get patient email - try from patients table first, then from appointments table if patient_id is null
                $patientEmail = null;
                $patientName = null;
                
                if ($patientId > 0) {
                    $patientStmt = $db->prepare('SELECT name, email FROM patients WHERE id = ?');
                    $patientStmt->execute([$patientId]);
                    $patient = $patientStmt->fetch();
                    if ($patient) {
                        $patientEmail = $patient['email'];
                        $patientName = $patient['name'];
                    }
                } elseif ($hasNewColumns && $appointmentId) {
                    // If patient_id is null, get email from appointments table
                    $apptStmt = $db->prepare('SELECT patient_name, patient_email FROM appointments WHERE id = ?');
                    $apptStmt->execute([$appointmentId]);
                    $apptData = $apptStmt->fetch();
                    if ($apptData) {
                        $patientEmail = $apptData['patient_email'];
                        $patientName = $apptData['patient_name'];
                    }
                }

                $doctorStmt = $db->prepare('SELECT name FROM doctors WHERE id = ?');
                $doctorStmt->execute([$doctorId]);
                $doctor = $doctorStmt->fetch();

                if ($patientEmail && $patientName && $doctor) {
                    $emailSent = send_appointment_confirmation(
                        $patientEmail,
                        $patientName,
                        [
                            'date' => $dateValue,
                            'time' => $timeValue,
                            'doctor_name' => $doctor['name'],
                            'status' => $status,
                        ]
                    );
                    
                    if (!$emailSent) {
                        error_log("Failed to send confirmation email to: {$patientEmail}");
                        // Don't show error to user, but log it
                    }
                } else {
                    error_log("Cannot send confirmation email: patient email or doctor not found. Patient ID: {$patientId}, Appointment ID: {$appointmentId}");
                }
            }

            if (!$flashError) {
                redirect('/admin/appointments.php');
            }
        }
    }
}

if (isset($_GET['delete'])) {
    if (!can_delete_appointments()) {
        set_flash('You do not have permission to delete appointments.', 'danger');
        redirect('/admin/appointments.php');
    }
    $stmt = $db->prepare('DELETE FROM appointments WHERE id = ?');
    $stmt->execute([(int) $_GET['delete']]);
    set_flash('Appointment removed.');
    redirect('/admin/appointments.php');
}

$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';

// $hasNewColumns already defined above

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
    // Fallback for old schema
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

$editAppointment = null;
if (isset($_GET['edit'])) {
    if ($hasNewColumns) {
        $editStmt = $db->prepare('SELECT a.*, 
                                         COALESCE(p.name, a.patient_name) AS display_patient_name,
                                         COALESCE(p.email, a.patient_email) AS display_patient_email,
                                         COALESCE(p.phone, a.patient_phone) AS display_patient_phone,
                                         COALESCE(p.age, a.patient_age) AS display_patient_age
                                  FROM appointments a
                                  LEFT JOIN patients p ON p.id = a.patient_id
                                  WHERE a.id = ? LIMIT 1');
    } else {
        $editStmt = $db->prepare('SELECT a.*, p.name AS display_patient_name, p.email AS display_patient_email, p.phone AS display_patient_phone, p.age AS display_patient_age
                                  FROM appointments a
                                  JOIN patients p ON p.id = a.patient_id
                                  WHERE a.id = ? LIMIT 1');
    }
    $editStmt->execute([(int) $_GET['edit']]);
    $editAppointment = $editStmt->fetch();
}

$patients = fetch_patients();
$doctors = fetch_doctors();

$pageTitle = 'Manage Appointments';
$activePage = 'appointments';
include __DIR__ . '/partials/header.php';
$flash = get_flash();
?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']); ?>" data-flash><?= e($flash['message']); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= e($flashError); ?></div>
<?php endif; ?>

<?php if (!$hasNewColumns): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Database Schema Update Required</strong><br>
        To enable the new booking workflow (bookings appear in admin first, then patient records are created), 
        please run the schema update: 
        <a href="<?= url('/update_appointments_schema.php'); ?>" target="_blank" class="alert-link">Update Schema</a>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow mb-4">
            <div class="card-header bg-white border-0">
                <form class="row g-2 align-items-end" method="get">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= e($status); ?>" <?= $filterStatus === $status ? 'selected' : ''; ?>><?= e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="<?= e($filterDate); ?>" class="form-control">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-primary mt-auto" type="submit">Filter</button>
                        <a href="<?= url('/admin/appointments.php'); ?>" class="btn btn-outline-secondary mt-auto">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card card-shadow">
            <?php if ($hasNewColumns): ?>
                <div class="card-header bg-white border-0 border-bottom">
                    <small class="text-muted">
                        <strong>💡 Tip:</strong> Appointments with a <span class="badge text-bg-info">New</span> badge are booking requests from the public website. 
                        Click <strong>"Process"</strong> to assign a doctor, set date/time, and create a patient record.
                    </small>
                </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <?php
                            $dateLabel = $appt['date'] ? date('M d, Y', strtotime($appt['date'])) : 'To be scheduled';
                            $timeLabel = $appt['time'] ? date('h:i A', strtotime($appt['time'])) : 'To be scheduled';
                            ?>
                            <tr>
                                <td><?= e($dateLabel); ?></td>
                                <td><?= e($timeLabel); ?></td>
                                <td>
                                    <?= e($appt['patient_name'] ?? 'N/A'); ?>
                                    <?php if ($hasNewColumns && !$appt['patient_id']): ?>
                                        <span class="badge text-bg-info ms-2" title="Booking Request - Patient record not created yet">New</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($appt['doctor_name']); ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $appt['status'] === 'Waiting' ? 'warning' : ($appt['status'] === 'Confirmed' ? 'success' : 'secondary'); ?>">
                                        <?= e($appt['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary" href="<?= url('/admin/appointments.php?edit=' . (int) $appt['id']); ?>"><?= ($hasNewColumns && !$appt['patient_id']) ? 'Process' : 'Edit'; ?></a>
                                    <?php if (can_delete_appointments()): ?>
                                        <a class="btn btn-sm btn-outline-danger" href="<?= url('/admin/appointments.php?delete=' . (int) $appt['id']); ?>" onclick="return confirm('Delete appointment?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if ($editAppointment): ?>
    <div class="col-lg-4">
        <div class="form-section">
            <h5 class="mb-3"><?= 'Edit Appointment'; ?></h5>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editAppointment): ?>
                    <input type="hidden" name="id" value="<?= (int) $editAppointment['id']; ?>">
                <?php endif; ?>
                <?php if ($hasNewColumns && $editAppointment && !$editAppointment['patient_id'] && $editAppointment['display_patient_name']): ?>
                    <div class="alert alert-info mb-3">
                        <strong>📋 Booking Request (New Patient):</strong><br>
                        <small>
                            <strong>Name:</strong> <?= e($editAppointment['display_patient_name']); ?><br>
                            <strong>Email:</strong> <?= e($editAppointment['display_patient_email'] ?? ''); ?><br>
                            <strong>Phone:</strong> <?= e($editAppointment['display_patient_phone'] ?? ''); ?><br>
                            <strong>Age:</strong> <?= e($editAppointment['display_patient_age'] !== null ? (string) $editAppointment['display_patient_age'] : ''); ?>
                        </small>
                        <p class="mb-0 mt-2"><small>💡 <strong>What is "Process"?</strong> This button appears for new booking requests from the public website. Click it to assign a doctor, set date/time, and create a patient record.</small></p>
                        <p class="mb-0 mt-2"><small>Patient record will be created automatically when you save with a patient selected or when status is set to Confirmed.</small></p>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" id="patientSelect" class="form-select" <?= !$editAppointment || $editAppointment['patient_id'] ? 'required' : ''; ?>>
                        <option value=""><?= $editAppointment && !$editAppointment['patient_id'] ? 'Create from booking info' : 'Select patient'; ?></option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int) $patient['id']; ?>" <?= isset($editAppointment['patient_id']) && $editAppointment['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                <?= e($patient['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($editAppointment && !$editAppointment['patient_id']): ?>
                        <small class="text-muted">Leave blank to auto-create patient record from booking info when saving.</small>
                    <?php endif; ?>
                    <div id="patientDetails" class="mt-2" style="display: none;">
                        <div class="alert alert-light border">
                            <strong>Patient Details:</strong><br>
                            <small>
                                <strong>Name:</strong> <span id="patientName">-</span><br>
                                <strong>Age:</strong> <span id="patientAge">-</span><br>
                                <strong>Email:</strong> <span id="patientEmail">-</span><br>
                                <strong>Phone:</strong> <span id="patientPhone">-</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= (int) $doctor['id']; ?>" <?= isset($editAppointment['doctor_id']) && $editAppointment['doctor_id'] == $doctor['id'] ? 'selected' : ''; ?>>
                                <?= e($doctor['name']); ?> — <?= e($doctor['specialty']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Scheduled Date</label>
                    <?php
                    $today = date('Y-m-d');
                    $maxDate = date('Y-m-d', strtotime('+1 year'));
                    ?>
                    <input type="date" name="date" id="appointmentDate" class="form-control" 
                           min="<?= $today; ?>" 
                           max="<?= $maxDate; ?>" 
                           value="<?= e($editAppointment['date'] ?? ''); ?>">
                    <small class="text-muted">Leave blank until the schedule is confirmed. Date must be today or within the next year.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Scheduled Time</label>
                    <input type="time" name="time" id="appointmentTime" class="form-control" value="<?= e($editAppointment['time'] ?? ''); ?>">
                    <small class="text-muted">Must be provided if you set a date.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status); ?>" <?= (isset($editAppointment['status']) ? $editAppointment['status'] : 'Waiting') === $status ? 'selected' : ''; ?>>
                                <?= e($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editAppointment ? 'Update' : 'Add'; ?> Appointment</button>
                    <?php if ($editAppointment): ?>
                        <a class="btn btn-outline-secondary" href="<?= url('/admin/appointments.php'); ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
(function() {
    const patientSelect = document.getElementById('patientSelect');
    const patientDetails = document.getElementById('patientDetails');
    
    if (!patientSelect || !patientDetails) return;
    
    // Load patient details if a patient is already selected (when editing)
    const initialPatientId = patientSelect.value;
    if (initialPatientId) {
        loadPatientDetails(initialPatientId);
    }
    
    patientSelect.addEventListener('change', function() {
        const patientId = this.value;
        if (patientId) {
            loadPatientDetails(patientId);
        } else {
            patientDetails.style.display = 'none';
        }
    });
    
    function loadPatientDetails(patientId) {
        patientDetails.style.display = 'block';
        document.getElementById('patientName').textContent = 'Loading...';
        document.getElementById('patientAge').textContent = 'Loading...';
        document.getElementById('patientEmail').textContent = 'Loading...';
        document.getElementById('patientPhone').textContent = 'Loading...';
        
        fetch('<?= url('/api/patient.php'); ?>?id=' + encodeURIComponent(patientId))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch patient details');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('patientName').textContent = data.name || '-';
                document.getElementById('patientAge').textContent = data.age || '-';
                document.getElementById('patientEmail').textContent = data.email || '-';
                document.getElementById('patientPhone').textContent = data.phone || '-';
            })
            .catch(error => {
                console.error('Error loading patient details:', error);
                document.getElementById('patientName').textContent = 'Error loading details';
                document.getElementById('patientAge').textContent = '-';
                document.getElementById('patientEmail').textContent = '-';
                document.getElementById('patientPhone').textContent = '-';
            });
    }
    
    // Date and time validation
    const dateInput = document.getElementById('appointmentDate');
    const timeInput = document.getElementById('appointmentTime');
    
    if (dateInput && timeInput) {
        function validateDateTime() {
            const date = dateInput.value;
            const time = timeInput.value;
            
            // Clear any previous validation messages
            dateInput.setCustomValidity('');
            timeInput.setCustomValidity('');
            
            // If both are empty, that's allowed
            if (!date && !time) {
                return true;
            }
            
            // If one is provided, both must be provided (handled by server-side validation)
            if ((date && !time) || (!date && time)) {
                return true; // Let server-side handle this
            }
            
            // Both are provided, validate them
            const selectedDateTime = new Date(date + 'T' + time);
            const now = new Date();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selectedDate = new Date(date);
            selectedDate.setHours(0, 0, 0, 0);
            
            // Check if date is in the past
            if (selectedDate < today) {
                dateInput.setCustomValidity('Date cannot be in the past');
                dateInput.reportValidity();
                return false;
            }
            
            // Check if date is more than 1 year in the future
            const maxDate = new Date();
            maxDate.setFullYear(maxDate.getFullYear() + 1);
            maxDate.setHours(0, 0, 0, 0);
            if (selectedDate > maxDate) {
                dateInput.setCustomValidity('Date cannot be more than one year in the future');
                dateInput.reportValidity();
                return false;
            }
            
            // If date is today, check if time is in the past
            if (selectedDate.getTime() === today.getTime() && selectedDateTime < now) {
                timeInput.setCustomValidity('Time cannot be in the past for today\'s date');
                timeInput.reportValidity();
                return false;
            }
            
            return true;
        }
        
        dateInput.addEventListener('change', validateDateTime);
        timeInput.addEventListener('change', validateDateTime);
        
        // Validate on form submit
        const form = dateInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateDateTime()) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    }
})();
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>

