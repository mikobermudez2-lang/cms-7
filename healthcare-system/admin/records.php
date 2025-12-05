<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin(); // Only admin can access records (doctors have their own records page)

$db = get_db();
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session token.';
    } else {
        $recordId = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $diagnosis = trim($_POST['diagnosis'] ?? '');

        if ($patientId && $diagnosis) {
            if ($recordId) {
                $stmt = $db->prepare('UPDATE records SET patient_id = ?, diagnosis = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$patientId, $diagnosis, $recordId]);
                set_flash('Record updated.');
            } else {
                $stmt = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                $stmt->execute([$patientId, $diagnosis]);
                set_flash('Record created.');
            }
            redirect('/admin/records.php');
        } else {
            $flashError = 'Patient and diagnosis are required.';
        }
    }
}

if (isset($_GET['delete'])) {
    $stmt = $db->prepare('DELETE FROM records WHERE id = ?');
    $stmt->execute([(int) $_GET['delete']]);
    set_flash('Record removed.');
    redirect('/admin/records.php');
}

$recordsStmt = $db->query(
    'SELECT r.*, p.name AS patient_name
     FROM records r
     JOIN patients p ON p.id = r.patient_id
     ORDER BY r.updated_at DESC'
);
$records = $recordsStmt->fetchAll();

$editRecord = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM records WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editRecord = $stmt->fetch();
}

$patients = fetch_patients();

$pageTitle = 'Medical Records';
$activePage = 'records';
include __DIR__ . '/partials/header.php';
$flash = get_flash();
?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']); ?>" data-flash><?= e($flash['message']); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= e($flashError); ?></div>
<?php endif; ?>
<div class="alert alert-info mb-4">
    <strong>📋 Medical Records System</strong><br>
    <small>This section contains records for all patients (present, past, and active). Records are automatically created/updated when patients are added or modified in the Active Patients section.</small>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Diagnosis</th>
                                <th>Last Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <?php
                                // Clean diagnosis text - remove "Patient Information:" prefix if present
                                $diagnosis = $record['diagnosis'];
                                if (strpos($diagnosis, 'Patient Information:') === 0) {
                                    $diagnosis = substr($diagnosis, strlen('Patient Information:'));
                                    $diagnosis = trim($diagnosis);
                                }
                                // Display truncated version in table, full version on hover
                                $diagnosisPreview = mb_strlen($diagnosis) > 150 ? mb_substr($diagnosis, 0, 150) . '...' : $diagnosis;
                                // Replace newlines with spaces for preview
                                $diagnosisPreviewSingleLine = str_replace(["\r\n", "\r", "\n"], ' ', $diagnosisPreview);
                                ?>
                                <tr>
                                    <td><strong><?= e($record['patient_name']); ?></strong></td>
                                    <td>
                                        <div style="max-width: 400px; word-wrap: break-word;" title="<?= e(str_replace(["\r\n", "\r", "\n"], ' ', $diagnosis)); ?>">
                                            <?= e($diagnosisPreviewSingleLine); ?>
                                        </div>
                                    </td>
                                    <td><small class="text-muted"><?= e(date('M d, Y h:i A', strtotime($record['updated_at']))); ?></small></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url('/admin/records.php?edit=' . (int) $record['id']); ?>" class="btn btn-primary">Edit</a>
                                            <a href="<?= url('/admin/records.php?delete=' . (int) $record['id']); ?>" class="btn btn-outline-danger" onclick="return confirm('Delete record?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No records available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-section">
            <h5 class="mb-3"><?= $editRecord ? 'Edit Record' : 'Add Record'; ?></h5>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editRecord): ?>
                    <input type="hidden" name="id" value="<?= (int) $editRecord['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= (int) $patient['id']; ?>" <?= isset($editRecord['patient_id']) && $editRecord['patient_id'] == $patient['id'] ? 'selected' : ''; ?>>
                                <?= e($patient['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis / Notes</label>
                    <?php
                    // Clean diagnosis text when editing - remove "Patient Information:" prefix if present
                    $editDiagnosis = $editRecord['diagnosis'] ?? '';
                    if ($editRecord && strpos($editDiagnosis, 'Patient Information:') === 0) {
                        $editDiagnosis = substr($editDiagnosis, strlen('Patient Information:'));
                        $editDiagnosis = trim($editDiagnosis);
                    }
                    ?>
                    <textarea name="diagnosis" rows="8" class="form-control" required placeholder="Enter diagnosis, notes, or medical information..."><?= e($editDiagnosis); ?></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editRecord ? 'Update' : 'Add'; ?> Record</button>
                    <?php if ($editRecord): ?>
                        <a href="<?= url('/admin/records.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

