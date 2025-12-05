<?php
require_once __DIR__ . '/../includes/init.php';

// Prevent caching to ensure fresh data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'Home';
$currentPage = 'index';

$db = get_db();

// Load latest published blog posts (recent only, not archived)
try {
    $postsStmt = $db->prepare(
        'SELECT id, title, slug, content, published_at, updated_at, archived_at
         FROM posts
         WHERE status = "published"
           AND archived_at IS NULL
         ORDER BY COALESCE(published_at, updated_at) DESC
         LIMIT 6'
    );
    $postsStmt->execute();
    $latestPosts = $postsStmt->fetchAll();
} catch (Throwable $th) {
    error_log('Error loading posts: ' . $th->getMessage());
    $latestPosts = [];
}


include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative" style="background: linear-gradient(135deg, #0b1d3a 0%, #1a3a5f 100%); color: white; padding: 100px 0; min-height: 600px; display: flex; align-items: center;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="display-4 fw-bold mb-3" style="font-size: 2.5rem;">A Premier Hospital in the Philippines</h2>
                <h1 class="display-3 fw-bold mb-4" style="font-size: 3rem;">Your Partner in Health</h1>
                <p class="lead mb-5" style="font-size: 1.25rem; opacity: 0.9;">Providing you with world-class care anytime, anywhere.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a class="btn btn-light btn-lg px-4 py-3" href="<?= url('/public/blog.php'); ?>">
                        <i class="bi bi-journal-text me-2"></i>Read Our Blog
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 py-3" href="<?= url('/public/about.php'); ?>">
                        <i class="bi bi-people me-2"></i>About Our Center
                    </a>
                    <a class="btn btn-outline-light btn-lg px-4 py-3" href="<?= url('/public/contact.php'); ?>">
                        <i class="bi bi-chat-dots me-2"></i>Contact Us
                    </a>
                </div>
                <div class="mt-5 pt-4 border-top border-light border-opacity-25">
                    <div class="row g-4 text-center">
                        <div class="col-md-4">
                            <div class="hero-contact-card">
                                <i class="bi bi-telephone-fill fs-3 mb-2 d-block"></i>
                                <strong>Emergency Hotline</strong>
                                <p class="mb-0">(555) 999-0000</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="hero-contact-card">
                                <i class="bi bi-chat-dots-fill fs-3 mb-2 d-block"></i>
                                <strong>Chat With Us</strong>
                                <p class="mb-0">Available 24/7</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="hero-contact-card">
                                <i class="bi bi-clock-fill fs-3 mb-2 d-block"></i>
                                <strong>Operating Hours</strong>
                                <p class="mb-0">24/7 Emergency Care</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Where Patients are Partners Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4" style="font-size: 2.5rem;">Where Patients are Partners</h2>
                <p class="lead text-muted mb-4">
                    Healthcare Center has defined for itself the value proposition: "Where Patients are Partners." 
                    This phrase finds its fullest meaning when the patient is viewed not as a problem to be solved 
                    or a charge to be cared for, but as a partner in his own health.
                </p>
                <p class="text-muted mb-4">
                    With our commitment to clinical excellence and innovation, you are guaranteed a world-class 
                    healthcare experience at Healthcare Center.
                </p>
                <a href="<?= url('/public/about.php'); ?>" class="btn btn-primary btn-lg">Learn More About Us</a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="<?= url('/public/chestoink.png'); ?>" class="img-fluid rounded-4 shadow-lg" alt="Healthcare Center" style="max-height: 500px; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3" style="font-size: 2.5rem;">Why Choose Healthcare Center?</h2>
            <p class="lead text-muted">We are not like any other health center in the Philippines.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card card-shadow h-100 border-0 text-center p-4">
                    <div class="mb-3">
                        <div class="why-icon-circle">
                            <i class="bi bi-award" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-3">Commitment to Clinical Excellence and Innovation</h5>
                    <p class="text-muted mb-0">Our world-class hospital and medical center deliver cutting-edge health care services with centers of excellence.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-shadow h-100 border-0 text-center p-4">
                    <div class="mb-3">
                        <div class="why-icon-circle">
                            <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-3">Patient-centered Care and Personalized Treatment</h5>
                    <p class="text-muted mb-0">Every patient receives personalized attention and care tailored to their unique health needs.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-shadow h-100 border-0 text-center p-4">
                    <div class="mb-3">
                        <div class="why-icon-circle">
                            <i class="bi bi-people" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-3">Compassionate and Highly Qualified Medical Staff</h5>
                    <p class="text-muted mb-0">Our multidisciplinary team combines decades of experience with heartfelt service.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-shadow h-100 border-0 text-center p-4">
                    <div class="mb-3">
                        <div class="why-icon-circle">
                            <i class="bi bi-cpu" aria-hidden="true"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-3">Cutting-edge Medical Technology and State-of-the-Art Facilities</h5>
                    <p class="text-muted mb-0">We invest in the latest medical technology to ensure the best possible outcomes.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Stories Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3" style="font-size: 2.5rem;">Latest Stories</h2>
            <p class="lead text-muted">Insights, features, and updates from the Healthcare Center editorial team.</p>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div id="postsContainer">
                    <?php if (empty($latestPosts)): ?>
                        <div class="alert alert-info text-center">
                            <p class="mb-0">No posts at this time. Check back soon for updates!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group announcement-feed" id="postsList">
                            <?php foreach ($latestPosts as $post): ?>
                                <div class="list-group-item mb-3 border rounded shadow-sm" data-post-id="<?= (int) $post['id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= e(date('F d, Y h:i A', strtotime($post['published_at'] ?? $post['updated_at']))); ?>
                                        </small>
                                    </div>
                                    <div class="announcement-content">
                                        <h5 class="fw-bold mb-2"><?= e($post['title']); ?></h5>
                                        <p class="mb-3 text-muted"><?= e(excerpt($post['content'])); ?></p>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= url('/public/blog.php?post=' . rawurlencode($post['slug'])); ?>">Read Story</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Showing <span id="postCount"><?= count($latestPosts); ?></span> post(s) - 
                                Last updated: <span id="lastPostUpdate"><?= date('h:i:s A'); ?></span>
                                <span class="spinner-border spinner-border-sm ms-2 d-none" id="loadingSpinner" role="status"></span>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

