<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Our Doctors';
$currentPage = 'doctors';

// Load doctors from database
$db = get_db();
$doctorsStmt = $db->query('SELECT id, name, specialty, email, phone FROM doctors ORDER BY name');
$doctors = $doctorsStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">Meet Our Doctors</h2>
                <p class="text-muted mb-0">Compassionate specialists ready to serve you.</p>
            </div>
            <a class="btn btn-primary" href="<?= url('/public/appointment.php'); ?>">Book Now</a>
        </div>
        <div class="row g-4" id="doctorsContainer">
            <?php if (empty($doctors)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p class="mb-0">No doctors registered yet. Please check back later.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="col-md-6 col-lg-4" data-doctor-id="<?= (int) $doctor['id']; ?>">
                        <div class="card card-shadow h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-person-badge text-primary fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-1"><?= e($doctor['name']); ?></h5>
                                        <p class="text-primary fw-semibold mb-1">
                                            <i class="bi bi-briefcase me-1"></i><?= e($doctor['specialty']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="border-top pt-3">
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-envelope me-1"></i><?= e($doctor['email']); ?>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-telephone me-1"></i><?= e($doctor['phone']); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="<?= url('/public/appointment.php?doctor_id=' . (int) $doctor['id']); ?>" class="btn btn-outline-primary btn-sm w-100">
                                    Book Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

