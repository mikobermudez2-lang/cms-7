<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'About Us';
$currentPage = 'about';

// Load dynamic statistics
$db = get_db();
$stats = [
    'doctors' => (int) $db->query('SELECT COUNT(*) FROM doctors')->fetchColumn(),
    'patients' => (int) $db->query('SELECT COUNT(*) FROM patients')->fetchColumn(),
    'appointments' => (int) $db->query('SELECT COUNT(*) FROM appointments WHERE status = "Confirmed"')->fetchColumn(),
];

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12">
                <div class="card card-shadow border-0 overflow-hidden">
                    <img src="<?= url('/public/poster.jpg'); ?>" 
                         class="img-fluid w-100" 
                         style="object-fit: cover; height: 400px;" 
                         alt="Healthcare Center">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold mb-3">Our Story</h1>
                <p class="text-muted lead mx-auto" style="max-width: 800px;">Founded in 1998, Healthcare Center has grown from a small community clinic into a 250-bed medical campus serving thousands of families each year. Our mission is to provide accessible, high-quality healthcare powered by empathy and innovation.</p>
            </div>
        </div>
        
        <!-- Dynamic Statistics -->
        <div class="row g-4 mt-4">
            <div class="col-md-4">
                <div class="text-center p-4 bg-light rounded">
                    <h2 class="text-primary fw-bold"><?= e((string) $stats['doctors']); ?>+</h2>
                    <p class="text-muted mb-0">Qualified Doctors</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4 bg-light rounded">
                    <h2 class="text-primary fw-bold"><?= e((string) $stats['patients']); ?>+</h2>
                    <p class="text-muted mb-0">Patients Served</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4 bg-light rounded">
                    <h2 class="text-primary fw-bold"><?= e((string) $stats['appointments']); ?>+</h2>
                    <p class="text-muted mb-0">Confirmed Appointments</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-bullseye text-primary fs-4"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-3">Mission</h5>
                        <p class="text-muted mb-0">Deliver people-centered care through collaboration, technology, and continuous improvement.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-eye text-primary fs-4"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-3">Vision</h5>
                        <p class="text-muted mb-0">Be the most trusted healthcare partner in the region with measurable outcomes.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-heart text-primary fs-4"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-3">Values</h5>
                        <p class="text-muted mb-0">Compassion, integrity, teamwork, and innovation guide every decision we make.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

