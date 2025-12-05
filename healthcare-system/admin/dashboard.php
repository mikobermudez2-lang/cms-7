<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();

$totals = [
    'patients'   => (int) $db->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'doctors'    => (int) $db->query('SELECT COUNT(*) FROM doctors')->fetchColumn(),
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

$availableBeds = max(0, DEFAULT_AVAILABLE_BEDS - $totals['patients']);

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/partials/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Total Patients</p>
                <h2 id="statPatients"><?= e((string) $totals['patients']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Total Doctors</p>
                <h2 id="statDoctors"><?= e((string) $totals['doctors']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Appointments Today</p>
                <h2 id="statAppointments"><?= e((string) $totals['appointments_today']); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-shadow">
            <div class="card-body">
                <p class="text-muted mb-1">Announcements</p>
                <h2 id="statAnnouncements"><?= e((string) $totals['announcements']); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Today's Appointments</h5>
                <a href="<?= url('/admin/appointments.php'); ?>" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="card-body" id="appointmentsTodayContainer">
                <?php if (empty($appointmentsToday)): ?>
                    <p class="text-muted">No appointments scheduled for today.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentsTodayBody">
                                <?php foreach ($appointmentsToday as $appt): ?>
                                    <?php $timeLabel = $appt['time'] ? date('h:i A', strtotime($appt['time'])) : 'To be scheduled'; ?>
                                    <tr>
                                        <td><?= e($timeLabel); ?></td>
                                        <td><?= e($appt['patient_name']); ?></td>
                                        <td><?= e($appt['doctor_name']); ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= $appt['status'] === 'Waiting' ? 'warning' : ($appt['status'] === 'Confirmed' ? 'success' : 'secondary'); ?>">
                                                <?= e($appt['status']); ?>
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
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Announcements</h5>
                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/announcements.php'); ?>">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($latestAnnouncements)): ?>
                    <p class="text-muted">No announcements yet.</p>
                <?php else: ?>
                    <?php foreach ($latestAnnouncements as $announcement): ?>
                        <div class="announcement-card mb-3">
                            <small class="text-muted d-block mb-1"><?= e(date('M d, Y', strtotime($announcement['created_at']))); ?></small>
                            <?php 
                                // Strip HTML tags and decode HTML entities for preview
                                $preview = strip_tags($announcement['message']);
                                $preview = html_entity_decode($preview, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                // Limit preview length
                                $preview = mb_strlen($preview) > 150 ? mb_substr($preview, 0, 150) . '...' : $preview;
                            ?>
                            <p class="mb-0"><?= e($preview); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

