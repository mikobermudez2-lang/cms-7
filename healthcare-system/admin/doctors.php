<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_manage_doctors()) {
        $flashError = 'You do not have permission to manage doctors.';
    } elseif (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $doctorId = isset($_POST['id']) ? (int) $_POST['id'] : null;

        if ($name && $specialty && $email && $phone) {
            if ($doctorId) {
                $stmt = $db->prepare('UPDATE doctors SET name = ?, specialty = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$name, $specialty, $email, $phone, $doctorId]);
                set_flash('Doctor updated successfully.');
            } else {
                // Create user account for the doctor
                // Username: email address
                $username = strtolower(trim($email));
                // Password: default password "chestoinks"
                $password = 'chestoinks';
                
                // Check if username (email) already exists
                $checkUser = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $checkUser->execute([$username]);
                if ($checkUser->fetch()) {
                    $flashError = 'A user account with this email already exists. Please use a different email.';
                } else {
                    // Hash the password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user account
                    $userStmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                    $userStmt->execute([$username, $hashedPassword, 'doctor']);
                    $userId = (int) $db->lastInsertId();
                    
                    // Insert doctor record
                    $stmt = $db->prepare('INSERT INTO doctors (name, specialty, email, phone) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $specialty, $email, $phone]);
                    $newDoctorId = (int) $db->lastInsertId();
                    
                    set_flash("Doctor added successfully. User account created: Username: <strong>{$username}</strong>, Password: <strong>{$password}</strong>");
                    redirect('/admin/doctors.php');
                }
            }
            
            // Only redirect if no error occurred
            if (!$flashError && $doctorId) {
                redirect('/admin/doctors.php');
            }
        } else {
            $flashError = 'All fields are required.';
        }
    }
}

if (isset($_GET['delete'])) {
    if (!can_manage_doctors()) {
        set_flash('You do not have permission to delete doctors.', 'danger');
        redirect('/admin/doctors.php');
    }
    $doctorId = (int) $_GET['delete'];
    
    // Get doctor email to find associated user account
    $doctorStmt = $db->prepare('SELECT email FROM doctors WHERE id = ?');
    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch();
    
    if ($doctor && $doctor['email']) {
        // Username is the doctor's email
        $username = strtolower(trim($doctor['email']));
        
        // Find and delete associated user account
        $userStmt = $db->prepare('SELECT id FROM users WHERE role = ? AND username = ? LIMIT 1');
        $userStmt->execute(['doctor', $username]);
        $user = $userStmt->fetch();
        
        if ($user) {
            $deleteUser = $db->prepare('DELETE FROM users WHERE id = ?');
            $deleteUser->execute([$user['id']]);
        }
    }
    
    $stmt = $db->prepare('DELETE FROM doctors WHERE id = ?');
    $stmt->execute([$doctorId]);
    set_flash('Doctor removed.');
    redirect('/admin/doctors.php');
}

$search = trim($_GET['search'] ?? '');
$query = 'SELECT * FROM doctors';
$params = [];

if ($search) {
    $query .= ' WHERE name LIKE ? OR specialty LIKE ?';
    $params = ["%{$search}%", "%{$search}%"];
}

$query .= ' ORDER BY name';
$stmt = $db->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$editDoctor = null;
if (isset($_GET['edit'])) {
    $editStmt = $db->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
    $editStmt->execute([(int) $_GET['edit']]);
    $editDoctor = $editStmt->fetch();
}

$pageTitle = 'Manage Doctors';
$activePage = 'doctors';
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
                    <input type="text" name="search" value="<?= e($search); ?>" class="form-control" placeholder="Search doctors...">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <a href="<?= url('/admin/doctors.php'); ?>" class="btn btn-outline-secondary">Clear</a>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialty</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Username</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                                <?php
                                // Username is the doctor's email
                                $userStmt = $db->prepare('SELECT username FROM users WHERE role = ? AND username = ? LIMIT 1');
                                $userStmt->execute(['doctor', strtolower(trim($doctor['email']))]);
                                $userAccount = $userStmt->fetch();
                                $displayUsername = $userAccount ? $userAccount['username'] : 'N/A';
                                ?>
                                <tr>
                                    <td><?= e($doctor['name']); ?></td>
                                    <td><?= e($doctor['specialty']); ?></td>
                                    <td><?= e($doctor['email']); ?></td>
                                    <td><?= e($doctor['phone']); ?></td>
                                    <td><code class="text-muted"><?= e($displayUsername); ?></code></td>
                                    <td class="text-end">
                                        <?php if (can_manage_doctors()): ?>
                                            <a href="<?= url('/admin/doctors.php?edit=' . (int) $doctor['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="<?= url('/admin/doctors.php?delete=' . (int) $doctor['id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete doctor?');">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted small">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($doctors)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No doctors found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if (can_manage_doctors()): ?>
    <div class="col-lg-4">
        <div class="form-section">
            <h5 class="mb-3"><?= $editDoctor ? 'Edit Doctor' : 'Add Doctor'; ?></h5>
            <?php if (!$editDoctor): ?>
                <div class="alert alert-info mb-3">
                    <small><strong>ℹ️ Note:</strong> When adding a new doctor, a user account will be automatically created:<br>
                    • <strong>Username:</strong> Email address<br>
                    • <strong>Password:</strong> chestoinks (default password)<br>
                    The credentials will be displayed after adding the doctor.</small>
                </div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editDoctor): ?>
                    <input type="hidden" name="id" value="<?= (int) $editDoctor['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($editDoctor['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Specialty</label>
                    <input type="text" name="specialty" class="form-control" required value="<?= e($editDoctor['specialty'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($editDoctor['email'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required value="<?= e($editDoctor['phone'] ?? ''); ?>">
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editDoctor ? 'Update' : 'Add'; ?> Doctor</button>
                    <?php if ($editDoctor): ?>
                        <a href="<?= url('/admin/doctors.php'); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>

