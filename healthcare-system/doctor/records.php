<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

$db = get_db();
$doctorId = $_SESSION['doctor_id'] ?? get_doctor_id_for_user(current_user()['username']);
$_SESSION['doctor_id'] = $doctorId;

$patientStmt = $db->prepare(
    'SELECT DISTINCT p.id, p.name
     FROM patients p
     JOIN appointments a ON a.patient_id = p.id
     WHERE a.doctor_id = ?
     ORDER BY p.name'
);
$patientStmt->execute([$doctorId]);
$availablePatients = $patientStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$flash = null;
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flash = 'Invalid session token.';
        $flashType = 'danger';
    } else {
        $recordId = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $diagnosis = trim($_POST['diagnosis'] ?? '');

        if ($patientId && isset($availablePatients[$patientId]) && $diagnosis) {
            if ($recordId) {
                $stmt = $db->prepare('UPDATE records SET diagnosis = ?, updated_at = NOW() WHERE id = ? AND patient_id = ?');
                $stmt->execute([$diagnosis, $recordId, $patientId]);
                $flash = 'Record updated successfully.';
            } else {
                $stmt = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                $stmt->execute([$patientId, $diagnosis]);
                $flash = 'Record added successfully.';
            }
        } else {
            $flash = 'Please select a patient you manage and add diagnosis notes.';
            $flashType = 'danger';
        }
    }
}

$recordsStmt = $db->prepare(
    'SELECT r.*, p.name AS patient_name
     FROM records r
     JOIN patients p ON p.id = r.patient_id
     WHERE EXISTS (
         SELECT 1 FROM appointments a WHERE a.patient_id = r.patient_id AND a.doctor_id = ?
     )
     ORDER BY r.updated_at DESC'
);
$recordsStmt->execute([$doctorId]);
$records = $recordsStmt->fetchAll();

$pageTitle = 'Records';
$activePage = 'records';
include __DIR__ . '/partials/header.php';
?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flashType); ?>" data-flash><?= e($flash); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Diagnosis</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?= e($record['patient_name']); ?></td>
                                    <td><?= e($record['diagnosis']); ?></td>
                                    <td><?= e(date('M d, Y h:i A', strtotime($record['updated_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No records found yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="form-section">
            <h5 class="mb-3">Update Record</h5>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select patient</option>
                        <?php foreach ($availablePatients as $id => $name): ?>
                            <option value="<?= (int) $id; ?>"><?= e($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis / Notes</label>
                    <textarea name="diagnosis" rows="5" class="form-control" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Save Record</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

