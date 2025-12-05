<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

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

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/partials/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Appointments Today</p>
                <h2 id="countToday"><?= e((string) $counts['today']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Upcoming</p>
                <h2 id="countUpcoming"><?= e((string) $counts['upcoming']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Patients Under Care</p>
                <h2 id="countPatients"><?= e((string) $counts['patients']); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card card-shadow">
    <div class="card-header bg-white border-0">
        <h5 class="mb-0">Appointments</h5>
    </div>
    <div class="card-body" id="appointmentsContainer">
        <?php if (empty($appointments)): ?>
            <p class="text-muted mb-0">No appointments scheduled yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
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
                                    <span class="badge text-bg-<?= $appointment['status'] === 'Confirmed' ? 'success' : ($appointment['status'] === 'Waiting' ? 'warning' : 'secondary'); ?>">
                                        <?= e($appointment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

