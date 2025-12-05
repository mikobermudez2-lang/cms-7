<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Careers';
$currentPage = 'careers';
$db = get_db();

try {
    $jobsStmt = $db->prepare(
        'SELECT 
            id,
            title,
            department,
            location,
            employment_type,
            summary,
            description,
            posted_at,
            status
         FROM jobs
         WHERE status = "open"
         ORDER BY posted_at DESC'
    );
    $jobsStmt->execute();
    $openJobs = $jobsStmt->fetchAll();
} catch (Throwable $th) {
    error_log('Error loading jobs: ' . $th->getMessage());
    $openJobs = [];
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-3">Careers at Healthcare Center</h1>
                <p class="lead text-muted mb-0">
                    Join a team that puts patients and people first. Browse our current openings across clinical,
                    administrative, and support departments.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="<?= url('/public/contact.php'); ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-envelope-open me-2"></i>Send Your Application
                </a>
            </div>
        </div>

        <?php if (empty($openJobs)): ?>
            <div class="alert alert-info">
                <p class="mb-0">
                    There are no published job openings right now. You may still send us your resume so we can
                    contact you when a suitable role becomes available.
                </p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($openJobs as $job): ?>
                    <div class="col-md-6">
                        <div class="card card-shadow h-100 border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold mb-1"><?= e($job['title'] ?? 'Open Position'); ?></h5>
                                        <p class="text-muted small mb-1">
                                            <?php if (!empty($job['department'])): ?>
                                                <i class="bi bi-building me-1"></i><?= e($job['department']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($job['employment_type'])): ?>
                                                · <span class="badge text-bg-primary ms-1">
                                                    <?= e($job['employment_type']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($job['location'])): ?>
                                            <p class="text-muted small mb-1">
                                                <i class="bi bi-geo-alt me-1"></i><?= e($job['location']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($job['posted_at'])): ?>
                                        <small class="text-muted ms-3">
                                            Posted<br><?= e(date('M d, Y', strtotime((string) $job['posted_at']))); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($job['summary'])): ?>
                                    <p class="text-muted mb-3"><?= e(excerpt((string) $job['summary'], 140)); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($job['description'])): ?>
                                    <div class="announcement-content small mb-3">
                                        <?= $job['description']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                    <small class="text-muted mb-0">
                                        To apply, include the position title in your message via our contact page.
                                    </small>
                                    <a href="<?= url('/public/contact.php'); ?>" class="btn btn-outline-primary btn-sm ms-3">
                                        Apply
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


