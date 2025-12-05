<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Contact';
$currentPage = 'contact';

// Contact information (can be moved to config or database later)
$contactInfo = [
    'phone' => '(555) 123-7788',
    'email' => 'hello@healthcarecenter.com',
    'address' => '100 Wellness Avenue, Pasig City',
    'emergency' => '(555) 999-0000',
];

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <h2 class="fw-bold mb-4">Visit or call us anytime</h2>
                <div class="contact-info">
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-2">
                            <i class="bi bi-telephone-fill text-primary me-2"></i>Phone
                        </h5>
                        <p class="text-muted mb-0"><?= e($contactInfo['phone']); ?></p>
                    </div>
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-2">
                            <i class="bi bi-envelope-fill text-primary me-2"></i>Email
                        </h5>
                        <p class="text-muted mb-0">
                            <a href="mailto:<?= e($contactInfo['email']); ?>"><?= e($contactInfo['email']); ?></a>
                        </p>
                    </div>
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-2">
                            <i class="bi bi-geo-alt-fill text-primary me-2"></i>Address
                        </h5>
                        <p class="text-muted mb-0"><?= e($contactInfo['address']); ?></p>
                    </div>
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-2">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Emergency Hotline
                        </h5>
                        <p class="text-muted mb-0">
                            <a href="tel:<?= preg_replace('/[^0-9]/', '', $contactInfo['emergency']); ?>" class="text-danger fw-bold">
                                <?= e($contactInfo['emergency']); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card card-shadow">
                    <div class="card-body p-0">
                        <h5 class="card-title p-3 mb-0 border-bottom">Find Us on Map</h5>
                        <div class="map-container" style="position: relative; width: 100%; height: 500px; overflow: hidden; border-radius: 0 0 16px 16px;">
                            <iframe 
                                src="https://www.google.com/maps?q=<?= urlencode($contactInfo['address']); ?>&output=embed&hl=en"
                                width="100%" 
                                height="500" 
                                style="border:0; display: block;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Healthcare Center Location - <?= e($contactInfo['address']); ?>">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

