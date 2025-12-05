<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

$db = get_db();
$doctorId = $_SESSION['doctor_id'] ?? get_doctor_id_for_user(current_user()['username']);
$_SESSION['doctor_id'] = $doctorId;

$stmt = $db->prepare(
    'SELECT DISTINCT p.*
     FROM patients p
     JOIN appointments a ON a.patient_id = p.id
     WHERE a.doctor_id = ?
     ORDER BY p.name'
);
$stmt->execute([$doctorId]);
$patients = $stmt->fetchAll();

$pageTitle = 'Patients';
$activePage = 'patients';
include __DIR__ . '/partials/header.php';
?>
<div class="card card-shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?= e($patient['name']); ?></td>
                            <td><?= e((string) $patient['age']); ?></td>
                            <td><?= e($patient['email']); ?></td>
                            <td><?= e($patient['phone']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No assigned patients yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

