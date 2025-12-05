<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

$db = get_db();
$doctorId = $_SESSION['doctor_id'] ?? get_doctor_id_for_user(current_user()['username']);
$_SESSION['doctor_id'] = $doctorId;

$allowedStatuses = [
    'accept' => 'Confirmed',
    'complete' => 'Completed',
    'reject' => 'Rejected',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('Invalid session token.', 'danger');
        redirect('/doctor/appointments.php');
    }
    $action = $_POST['action'] ?? '';
    $appointmentId = (int) ($_POST['id'] ?? 0);
if ($appointmentId && isset($allowedStatuses[$action])) {
    $apptStmt = $db->prepare('SELECT date, time FROM appointments WHERE id = ? AND doctor_id = ?');
    $apptStmt->execute([$appointmentId, $doctorId]);
    $appointment = $apptStmt->fetch();

    if (!$appointment) {
        set_flash('Appointment not found.', 'danger');
    } else {
        $nextStatus = $allowedStatuses[$action];
        $needsSchedule = in_array($nextStatus, ['Confirmed', 'Completed'], true);
        $hasSchedule = !empty($appointment['date']) && !empty($appointment['time']);

        if ($needsSchedule && !$hasSchedule) {
            set_flash('Please coordinate with the admin team to assign a date and time before updating this status.', 'danger');
        } else {
            $stmt = $db->prepare('UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?');
            $stmt->execute([$nextStatus, $appointmentId, $doctorId]);
            set_flash('Appointment updated.', 'success');
        }
    }
}
    redirect('/doctor/appointments.php');
}

// Check if appointments table has new columns (patient_name, patient_email, etc.)
$hasNewColumns = false;
try {
    $checkCols = $db->query("SHOW COLUMNS FROM appointments LIKE 'patient_name'");
    $hasNewColumns = $checkCols->rowCount() > 0;
} catch (Throwable $e) {
    // Table might not exist or error, assume old schema
    $hasNewColumns = false;
}

$filterStatus = $_GET['status'] ?? '';
if ($hasNewColumns) {
    // New schema: patient info can be in appointments table if patient_id is NULL
    $query = 'SELECT a.*, 
                     COALESCE(p.name, a.patient_name) AS patient_name,
                     COALESCE(p.email, a.patient_email) AS patient_email,
                     COALESCE(p.phone, a.patient_phone) AS patient_phone
              FROM appointments a
              LEFT JOIN patients p ON p.id = a.patient_id
              WHERE a.doctor_id = ?';
} else {
    // Old schema: patient_id is required
    $query = 'SELECT a.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone
              FROM appointments a
              JOIN patients p ON p.id = a.patient_id
              WHERE a.doctor_id = ?';
}
$params = [$doctorId];
if ($filterStatus) {
    $query .= ' AND a.status = ?';
    $params[] = $filterStatus;
}
$query .= ' ORDER BY a.date DESC, a.time DESC';
$stmt = $db->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Appointments';
$activePage = 'appointments';
include __DIR__ . '/partials/header.php';
$flash = get_flash();
?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']); ?>" data-flash><?= e($flash['message']); ?></div>
<?php endif; ?>

<div class="card card-shadow mb-4">
    <div class="card-body">
        <form class="row g-2" method="get">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="Waiting" <?= $filterStatus === 'Waiting' ? 'selected' : ''; ?>>Waiting</option>
                    <option value="Confirmed" <?= $filterStatus === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Completed" <?= $filterStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Rejected" <?= $filterStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="<?= url('/doctor/appointments.php'); ?>">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-shadow">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                    $dateLabel = $appointment['date'] ? date('M d, Y', strtotime($appointment['date'])) : 'To be scheduled';
                    $timeLabel = $appointment['time'] ? date('h:i A', strtotime($appointment['time'])) : 'To be scheduled';
                    ?>
                    <tr>
                        <td><?= e($dateLabel); ?></td>
                        <td><?= e($timeLabel); ?></td>
                        <td><?= e($appointment['patient_name']); ?></td>
                        <td>
                            <div><?= e($appointment['email']); ?></div>
                            <small class="text-muted"><?= e($appointment['phone']); ?></small>
                        </td>
                        <td>
                            <span class="badge text-bg-<?= $appointment['status'] === 'Confirmed' ? 'success' : ($appointment['status'] === 'Waiting' ? 'warning' : 'secondary'); ?>">
                                <?= e($appointment['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if ($appointment['status'] === 'Waiting'): ?>
                                <?php $canConfirm = !empty($appointment['date']) && !empty($appointment['time']); ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $appointment['id']; ?>">
                                    <button class="btn btn-sm btn-success" name="action" value="accept" <?= $canConfirm ? '' : 'disabled'; ?>>Accept</button>
                                    <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject</button>
                                </form>
                                <?php if (!$canConfirm): ?>
                                    <div class="text-muted small mt-2">Schedule pending admin assignment.</div>
                                <?php endif; ?>
                            <?php elseif ($appointment['status'] === 'Confirmed'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $appointment['id']; ?>">
                                    <button class="btn btn-sm btn-secondary" name="action" value="complete">Mark Completed</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">No actions</span>
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
<?php include __DIR__ . '/partials/footer.php'; ?>

