<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'About Us';
$currentPage = 'about';

$db = get_db();
$publishedStories = (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();

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
    </div>
</section>

<!-- Statistics Section with Background -->
<section class="py-5" style="background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%);">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-newspaper text-white" style="font-size: 2.5rem;"></i>
                    </div>
                    <h2 class="text-white fw-bold mb-2" style="font-size: 3rem;"><?= e((string) max($publishedStories, 1)); ?>+</h2>
                    <p class="text-white-50 mb-0 fs-5">Published Stories</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check text-white" style="font-size: 2.5rem;"></i>
                    </div>
                    <h2 class="text-white fw-bold mb-2" style="font-size: 3rem;">25+</h2>
                    <p class="text-white-50 mb-0 fs-5">Years Serving Communities</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-people text-white" style="font-size: 2.5rem;"></i>
                    </div>
                    <h2 class="text-white fw-bold mb-2" style="font-size: 3rem;">40+</h2>
                    <p class="text-white-50 mb-0 fs-5">Community Programs</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">What Drives Us</h2>
            <p class="text-muted">Our foundation for exceptional healthcare</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%);">
                                <i class="bi bi-bullseye text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: #1E3A8A;">Mission</h4>
                        <p class="text-muted mb-0">Deliver people-centered care through collaboration, technology, and continuous improvement.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%);">
                                <i class="bi bi-eye text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: #1E3A8A;">Vision</h4>
                        <p class="text-muted mb-0">Be the most trusted healthcare partner in the region with measurable outcomes.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-shadow h-100 border-0">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%);">
                                <i class="bi bi-heart-fill text-white" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3" style="color: #1E3A8A;">Values</h4>
                        <p class="text-muted mb-0">Compassion, integrity, teamwork, and innovation guide every decision we make.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

