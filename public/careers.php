<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Careers';
$currentPage = 'careers';
$db = get_db();

// Auto-sync external jobs if integration is enabled
if (defined('EXTERNAL_JOBS_ENABLED') && EXTERNAL_JOBS_ENABLED && function_exists('sync_external_jobs')) {
    // Sync external jobs (runs silently in background)
    // Only sync once per session to avoid flooding API with requests
    if (!isset($_SESSION['last_job_sync']) || (time() - $_SESSION['last_job_sync']) > 300) {
        try {
            $syncResult = sync_external_jobs();
            $_SESSION['last_job_sync'] = time();
            
            // Log errors for debugging (especially useful for localhost testing)
            if (!$syncResult['success']) {
                $message = $syncResult['message'] ?? '';
                error_log('Auto-sync failed: ' . $message);
            }
        } catch (Throwable $e) {
            error_log('Auto-sync error: ' . $e->getMessage());
        }
    }
}

try {
    $jobsStmt = $db->prepare(
        'SELECT 
            id,
            external_id,
            title,
            department,
            location,
            employment_type,
            summary,
            description,
            image_url,
            posted_at,
            closes_at,
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
                            <?php if (!empty($job['image_url'])): ?>
                                <img src="<?= e($job['image_url']); ?>" class="card-img-top" alt="<?= e($job['title'] ?? 'Job'); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="bi bi-briefcase-fill text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
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
                                <?php elseif (!empty($job['description'])): ?>
                                    <p class="text-muted mb-3"><?= e(excerpt(strip_tags((string) $job['description']), 140)); ?></p>
                                <?php endif; ?>

                                <div class="border-top pt-3 d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#jobModal<?= e($job['id']); ?>">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job Details Modal -->
                    <div class="modal fade" id="jobModal<?= e($job['id']); ?>" tabindex="-1" aria-labelledby="jobModalLabel<?= e($job['id']); ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="jobModalLabel<?= e($job['id']); ?>"><?= e($job['title'] ?? 'Job Details'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if (!empty($job['image_url'])): ?>
                                        <img src="<?= e($job['image_url']); ?>" class="img-fluid rounded mb-4" alt="<?= e($job['title'] ?? 'Job'); ?>">
                                    <?php endif; ?>
                                    
                                    <div class="row mb-3">
                                        <?php if (!empty($job['department'])): ?>
                                            <div class="col-md-6 mb-2">
                                                <strong><i class="bi bi-building me-2"></i>Department:</strong>
                                                <p class="mb-0"><?= e($job['department']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($job['employment_type'])): ?>
                                            <div class="col-md-6 mb-2">
                                                <strong><i class="bi bi-briefcase me-2"></i>Employment Type:</strong>
                                                <p class="mb-0"><?= e($job['employment_type']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($job['location'])): ?>
                                            <div class="col-md-6 mb-2">
                                                <strong><i class="bi bi-geo-alt me-2"></i>Location:</strong>
                                                <p class="mb-0"><?= e($job['location']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($job['posted_at'])): ?>
                                            <div class="col-md-6 mb-2">
                                                <strong><i class="bi bi-calendar me-2"></i>Posted:</strong>
                                                <p class="mb-0"><?= e(date('F d, Y', strtotime((string) $job['posted_at']))); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($job['closes_at'])): ?>
                                            <div class="col-md-6 mb-2">
                                                <strong><i class="bi bi-calendar-x me-2"></i>Closing Date:</strong>
                                                <p class="mb-0"><?= e(date('F d, Y', strtotime((string) $job['closes_at']))); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($job['summary'])): ?>
                                        <div class="mb-3">
                                            <h6>Summary</h6>
                                            <p class="text-muted"><?= e($job['summary']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($job['description'])): ?>
                                        <div class="mb-3">
                                            <h6>Full Description</h6>
                                            <div class="announcement-content">
                                                <?= $job['description']; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <a href="<?= url('/public/contact.php'); ?>" class="btn btn-primary">
                                        <i class="bi bi-envelope-open me-2"></i>Apply Now
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


