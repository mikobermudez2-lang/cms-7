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
        'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
         FROM posts
         WHERE status = "published"
           AND archived_at IS NULL
         ORDER BY GREATEST(COALESCE(published_at, created_at), created_at) DESC, created_at DESC
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
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h1 class="hero-title">Where Patients are Partners</h1>
                <p class="hero-lead">
                    Healthcare Center has defined for itself the value proposition: "Where Patients are Partners." 
                    This phrase finds its fullest meaning when the patient is viewed not as a problem to be solved 
                    or a charge to be cared for, but as a partner in his own health.
                </p>
                <p class="hero-text">
                    With our commitment to clinical excellence and innovation, you are guaranteed a world-class 
                    healthcare experience at Healthcare Center.
                </p>
                <div class="hero-cta">
                    <a href="<?= url('/public/about.php'); ?>" class="btn btn-primary btn-lg px-5">Learn More About Us</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper">
                    <img src="<?= url('/public/chestoink.png'); ?>" class="hero-image" alt="Healthcare Center">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="section-features">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose Healthcare Center?</h2>
            <p class="section-subtitle">We are not like any other health center in the Philippines.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-award" aria-hidden="true"></i>
                    </div>
                    <h3 class="feature-title">Commitment to Clinical Excellence and Innovation</h3>
                    <p class="feature-text">Our world-class hospital and medical center deliver cutting-edge health care services with centers of excellence.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-heart-pulse" aria-hidden="true"></i>
                    </div>
                    <h3 class="feature-title">Patient-centered Care and Personalized Treatment</h3>
                    <p class="feature-text">Every patient receives personalized attention and care tailored to their unique health needs.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-people" aria-hidden="true"></i>
                    </div>
                    <h3 class="feature-title">Compassionate and Highly Qualified Medical Staff</h3>
                    <p class="feature-text">Our multidisciplinary team combines decades of experience with heartfelt service.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-cpu" aria-hidden="true"></i>
                    </div>
                    <h3 class="feature-title">Cutting-edge Medical Technology and State-of-the-Art Facilities</h3>
                    <p class="feature-text">We invest in the latest medical technology to ensure the best possible outcomes.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Stories Section -->
<section class="section-stories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Latest Stories</h2>
            <p class="section-subtitle">Insights, features, and updates from the Healthcare Center editorial team.</p>
        </div>
        <div class="row">
            <div class="col-lg-10 col-xl-8 mx-auto">
                <div id="postsContainer">
                    <?php if (empty($latestPosts)): ?>
                        <div class="alert alert-info text-center">
                            <p class="mb-0">No posts at this time. Check back soon for updates!</p>
                        </div>
                    <?php else: ?>
                        <div class="posts-list" id="postsList">
                            <?php foreach ($latestPosts as $post): ?>
                                <article class="post-card" data-post-id="<?= e($post['id']); ?>">
                                    <div class="post-meta">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <time datetime="<?= e($post['published_at'] ?? $post['updated_at']); ?>">
                                            <?= e(date('F d, Y', strtotime($post['published_at'] ?? $post['updated_at']))); ?>
                                        </time>
                                    </div>
                                    <h3 class="post-title"><?= e($post['title']); ?></h3>
                                    <p class="post-excerpt"><?= e(excerpt($post['content'])); ?></p>
                                    <a href="<?= url('/public/blog.php?post=' . rawurlencode($post['slug'])); ?>" class="post-link">
                                        Read Story <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="posts-footer">
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

