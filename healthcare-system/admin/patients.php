<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session token.';
    } else {
        $patientId = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $age = (int) ($_POST['age'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name && $age && $email && $phone) {
            if ($patientId) {
                // Update patient
                $stmt = $db->prepare('UPDATE patients SET name = ?, age = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$name, $age, $email, $phone, $patientId]);
                
                // Automatically update or create record
                $recordCheck = $db->prepare('SELECT id FROM records WHERE patient_id = ? LIMIT 1');
                $recordCheck->execute([$patientId]);
                $existingRecord = $recordCheck->fetch();
                
                $patientInfo = "- Name: {$name}\n- Age: {$age}\n- Email: {$email}\n- Phone: {$phone}\n\nLast updated: " . date('Y-m-d H:i:s');
                
                if ($existingRecord) {
                    // Update existing record
                    $updateRecord = $db->prepare('UPDATE records SET diagnosis = ?, updated_at = NOW() WHERE patient_id = ?');
                    $updateRecord->execute([$patientInfo, $patientId]);
                } else {
                    // Create new record
                    $createRecord = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                    $createRecord->execute([$patientId, $patientInfo]);
                }
                
                set_flash('Patient updated successfully. Record automatically updated.');
            } else {
                // Create new patient
                $stmt = $db->prepare('INSERT INTO patients (name, age, email, phone) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $age, $email, $phone]);
                $newPatientId = (int) $db->lastInsertId();
                
                // Automatically create record
                $patientInfo = "- Name: {$name}\n- Age: {$age}\n- Email: {$email}\n- Phone: {$phone}\n\nCreated: " . date('Y-m-d H:i:s');
                $createRecord = $db->prepare('INSERT INTO records (patient_id, diagnosis, updated_at) VALUES (?, ?, NOW())');
                $createRecord->execute([$newPatientId, $patientInfo]);
                
                set_flash('Patient added successfully. Record automatically created.');
            }
            redirect('/admin/patients.php');
        } else {
            $flashError = 'All fields are required.';
        }
    }
}

if (isset($_GET['delete'])) {
    if (!can_delete_patients()) {
        set_flash('You do not have permission to delete patients.', 'danger');
        redirect('/admin/patients.php');
    }
    $patientId = (int) $_GET['delete'];
    $stmt = $db->prepare('DELETE FROM patients WHERE id = ?');
    $stmt->execute([$patientId]);
    set_flash('Patient removed.');
    redirect('/admin/patients.php');
}

$search = trim($_GET['search'] ?? '');
// Show active patients: Flow is Booking → Confirmed Appointment → Active Patient → Record
// Active patients are those with confirmed appointments, recent appointments, or records
$query = 'SELECT DISTINCT p.* 
          FROM patients p
          WHERE EXISTS (
              SELECT 1 FROM appointments a 
              WHERE a.patient_id = p.id 
              AND (a.status = \'Confirmed\'
                   OR a.status = \'Waiting\'
                   OR (a.date IS NOT NULL AND a.date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH))
                   OR (a.date IS NOT NULL AND a.date >= CURDATE()))
          )
          OR EXISTS (
              SELECT 1 FROM records r 
              WHERE r.patient_id = p.id
          )';
$params = [];
if ($search) {
    $query .= ' AND (p.name LIKE ? OR p.email LIKE ?)';
    $params = ["%{$search}%", "%{$search}%"];
}
$query .= ' ORDER BY p.name';
$stmt = $db->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

$editPatient = null;
if (isset($_GET['edit'])) {
    $editStmt = $db->prepare('SELECT * FROM patients WHERE id = ? LIMIT 1');
    $editStmt->execute([(int) $_GET['edit']]);
    $editPatient = $editStmt->fetch();
}

$pageTitle = 'Active Patients';
$activePage = 'patients';
include __DIR__ . '/partials/header.php';
$flash = get_flash();
?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']); ?>" data-flash><?= e($flash['message']); ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= e($flashError); ?></div>
<?php endif; ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow">
            <div class="card-header bg-white border-0">
                <form class="d-flex gap-2" method="get">
                    <input type="text" name="search" value="<?= e($search); ?>" class="form-control" placeholder="Search active patients...">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <a href="<?= url('/admin/patients.php'); ?>" class="btn btn-outline-secondary">Clear</a>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?= e($patient['name']); ?></td>
                                    <td><?= e((string) $patient['age']); ?></td>
                                    <td><?= e($patient['email']); ?></td>
                                    <td><?= e($patient['phone']); ?></td>
                                    <td class="text-end">
                                        <a href="<?= url('/admin/patients.php?edit=' . (int) $patient['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php if (can_delete_patients()): ?>
                                            <a href="<?= url('/admin/patients.php?delete=' . (int) $patient['id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete patient?');">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($patients)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No active patients found. Active patients are those with recent appointments (last 6 months), active appointments (Waiting/Confirmed), or patients with records in the system.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-section" id="patientFormSection">
            <?php if ($editPatient): ?>
                <div class="alert alert-info mb-3">
                    <strong>✏️ Editing Patient:</strong> <?= e($editPatient['name']); ?>
                </div>
            <?php endif; ?>
            <h5 class="mb-3"><?= $editPatient ? 'Edit Active Patient' : 'Add Active Patient'; ?></h5>
            <div class="alert alert-info mb-3">
                <small><strong>ℹ️ Note:</strong> New patients are automatically added to the Records system. Active Patients shows patients with recent appointments (last 6 months), active appointments (Waiting/Confirmed), or patients who have records in the system.</small>
            </div>
            <form method="post" id="patientForm">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editPatient): ?>
                    <input type="hidden" name="id" value="<?= (int) $editPatient['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="patientName" class="form-control" required value="<?= e($editPatient['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" id="patientAge" class="form-control" required min="1" max="150" value="<?= $editPatient ? (int) $editPatient['age'] : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="patientEmail" class="form-control" required value="<?= e($editPatient['email'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="patientPhone" class="form-control" required value="<?= e($editPatient['phone'] ?? ''); ?>">
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" type="submit"><?= $editPatient ? 'Update Patient' : 'Add Patient'; ?></button>
                    <?php if ($editPatient): ?>
                        <a href="<?= url('/admin/patients.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function() {
    // Scroll to form when editing
    <?php if ($editPatient): ?>
    const formSection = document.getElementById('patientFormSection');
    if (formSection) {
        setTimeout(() => {
            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Highlight the form temporarily
            formSection.style.transition = 'all 0.3s';
            formSection.style.border = '2px solid #0d6efd';
            formSection.style.borderRadius = '8px';
            setTimeout(() => {
                formSection.style.border = '';
                formSection.style.borderRadius = '';
            }, 2000);
        }, 100);
    }
    <?php endif; ?>
})();
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>

