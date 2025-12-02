<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;
$editJob = null;
$editingId = isset($_GET['edit']) ? trim($_GET['edit']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session.';
    } else {
        $jobId          = isset($_POST['job_id']) && $_POST['job_id'] !== '' ? trim($_POST['job_id']) : null;
        
        // Check if this is an external job (only allow image editing)
        $isExternalJob = false;
        if ($jobId) {
            $checkStmt = $db->prepare('SELECT external_id FROM jobs WHERE id = ? LIMIT 1');
            $checkStmt->execute([$jobId]);
            $jobCheck = $checkStmt->fetch();
            $isExternalJob = $jobCheck && !empty($jobCheck['external_id']);
        }
        
        if ($isExternalJob) {
            // For external jobs, only allow image updates
            $imageUrl = trim($_POST['image_url'] ?? '');
            $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
            
            // Handle image removal
            if ($removeImage) {
                $imageUrl = null;
            }
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($file['type'], $allowedTypes, true) && $file['size'] <= $maxSize) {
                    $uploadDir = __DIR__ . '/../uploads/jobs/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('job_', true) . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $imageUrl = url('/uploads/jobs/' . $filename);
                    } else {
                        $flashError = 'Failed to upload image.';
                    }
                } else {
                    $flashError = 'Invalid image file. Only JPEG, PNG, GIF, and WebP up to 5MB are allowed.';
                }
            }
            
            // Update only the image for external jobs
            if (empty($flashError)) {
                $stmt = $db->prepare('UPDATE jobs SET image_url = :image_url WHERE id = :id');
                $stmt->execute([
                    'image_url' => $imageUrl ?: null,
                    'id' => $jobId,
                ]);
                set_flash('Job image updated.');
                redirect('/admin/jobs.php');
            }
        } else {
            // Regular job editing (non-external)
            $title          = trim($_POST['title'] ?? '');
            $department     = trim($_POST['department'] ?? '');
            $location       = trim($_POST['location'] ?? '');
            $employmentType = trim($_POST['employment_type'] ?? '');
            $summary        = trim($_POST['summary'] ?? '');
            $description    = trim($_POST['description'] ?? '');
            $imageUrl       = trim($_POST['image_url'] ?? '');
            $removeImage     = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
            $status         = in_array($_POST['status'] ?? 'open', ['open', 'closed'], true) ? $_POST['status'] : 'open';
            
            // Handle image removal
            if ($removeImage) {
                $imageUrl = null;
            }
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($file['type'], $allowedTypes, true) && $file['size'] <= $maxSize) {
                    $uploadDir = __DIR__ . '/../uploads/jobs/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('job_', true) . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $imageUrl = url('/uploads/jobs/' . $filename);
                    } else {
                        $flashError = 'Failed to upload image.';
                    }
                } else {
                    $flashError = 'Invalid image file. Only JPEG, PNG, GIF, and WebP up to 5MB are allowed.';
                }
            }

            if ($title === '') {
                $flashError = 'Job title is required.';
            } else {
            $postedAt = null;
            if ($status === 'open') {
                $postedAt = date('Y-m-d H:i:s');
            }

            if ($jobId) {
                // Keep existing posted_at if already set and status is still open
                $existingStmt = $db->prepare('SELECT posted_at, status FROM jobs WHERE id = ? LIMIT 1');
                $existingStmt->execute([$jobId]);
                $existing = $existingStmt->fetch();
                if ($existing && $existing['posted_at'] && $status === 'open') {
                    $postedAt = $existing['posted_at'];
                }

                $stmt = $db->prepare(
                    'UPDATE jobs
                     SET title = :title,
                         department = :department,
                         location = :location,
                         employment_type = :employment_type,
                         summary = :summary,
                         description = :description,
                         image_url = :image_url,
                         status = :status,
                         posted_at = :posted_at
                     WHERE id = :id'
                );
                $stmt->execute([
                    'title'          => $title,
                    'department'     => $department,
                    'location'       => $location,
                    'employment_type'=> $employmentType,
                    'summary'        => $summary,
                    'description'    => $description,
                    'image_url'      => $imageUrl ?: null,
                    'status'         => $status,
                    'posted_at'      => $postedAt,
                    'id'             => $jobId,
                ]);
                set_flash('Job updated.');
            } else {
                $newId = generate_id();
                $stmt = $db->prepare(
                    'INSERT INTO jobs (id, title, department, location, employment_type, summary, description, image_url, status, posted_at)
                     VALUES (:id, :title, :department, :location, :employment_type, :summary, :description, :image_url, :status, :posted_at)'
                );
                $stmt->execute([
                    'id'             => $newId,
                    'title'          => $title,
                    'department'     => $department,
                    'location'       => $location,
                    'employment_type'=> $employmentType,
                    'summary'        => $summary,
                    'description'    => $description,
                    'image_url'      => $imageUrl ?: null,
                    'status'         => $status,
                    'posted_at'      => $postedAt,
                ]);
                set_flash('Job created.');
            }

            redirect('/admin/jobs.php');
        }
        }
    }
}

if (isset($_GET['delete'])) {
    if (!is_admin()) {
        set_flash('Only administrators can delete jobs.', 'danger');
        redirect('/admin/jobs.php');
    }
    $deleteId = trim($_GET['delete']);
    // Don't allow deletion of external jobs
    $checkStmt = $db->prepare('SELECT external_id FROM jobs WHERE id = ? LIMIT 1');
    $checkStmt->execute([$deleteId]);
    $job = $checkStmt->fetch();
    if ($job && !empty($job['external_id'])) {
        set_flash('External jobs cannot be deleted. They are managed by the integration system.', 'warning');
        redirect('/admin/jobs.php');
    }
    $stmt = $db->prepare('DELETE FROM jobs WHERE id = ?');
    $stmt->execute([$deleteId]);
    set_flash('Job deleted.');
    redirect('/admin/jobs.php');
}

// Handle manual sync
if (isset($_GET['sync']) && is_admin()) {
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/jobs.php');
    }
    if (function_exists('sync_external_jobs')) {
        $result = sync_external_jobs();
        if ($result['success']) {
            set_flash($result['message'], 'success');
        } else {
            set_flash($result['message'], 'danger');
        }
    } else {
        set_flash('External jobs integration is not available.', 'warning');
    }
    redirect('/admin/jobs.php');
}

if ($editingId) {
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$editingId]);
    $editJob = $stmt->fetch() ?: null;
    
    // Check if this is an external job
    $isExternalJob = $editJob && !empty($editJob['external_id']);
}

try {
    $jobsStmt = $db->query(
        'SELECT id, external_id, title, department, location, employment_type, status, posted_at
         FROM jobs
         ORDER BY posted_at DESC, id DESC'
    );
    $jobs = $jobsStmt->fetchAll();
} catch (Throwable $th) {
    $flashError = 'Jobs table is not available yet. Please ask your integration partner to create it.';
    $jobs = [];
}

// Initialize isExternalJob for form display
if (!isset($isExternalJob)) {
    $isExternalJob = false;
}

$pageTitle = 'Jobs';
$activePage = 'jobs';
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
    <div class="col-lg-7">
        <div class="card card-shadow h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Job Listings</h5>
                <div class="d-flex align-items-center gap-2">
                    <?php if (defined('EXTERNAL_JOBS_ENABLED') && EXTERNAL_JOBS_ENABLED && is_admin()): ?>
                        <a href="<?= url('/admin/jobs.php?sync=1&csrf=' . urlencode(csrf_token())); ?>" 
                           class="btn btn-sm btn-outline-primary" 
                           title="Sync jobs from external database">
                            <i class="bi bi-arrow-repeat me-1"></i>Sync External
                        </a>
                    <?php endif; ?>
                    <small class="text-muted"><?= count($jobs); ?> total</small>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($jobs)): ?>
                    <p class="text-muted text-center py-4 mb-0">
                        No jobs have been created yet. Use the form on the right to publish your first opening.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($job['title']); ?></strong>
                                            <?php if (!empty($job['external_id'])): ?>
                                                <br><small class="text-muted"><i class="bi bi-link-45deg"></i> External</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($job['department'] ?? '—'); ?></td>
                                        <td><?= e($job['location'] ?? '—'); ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= ($job['status'] ?? 'open') === 'open' ? 'success' : 'secondary'; ?>">
                                                <?= e(ucfirst($job['status'] ?? 'open')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($job['posted_at'])): ?>
                                                <small class="text-muted">
                                                    <?= e(date('M d, Y', strtotime((string) $job['posted_at']))); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">—</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/jobs.php?edit=' . urlencode($job['id'])); ?>" title="<?= !empty($job['external_id']) ? 'Edit Image (External Job)' : 'Edit'; ?>">
                                                    <i class="bi bi-<?= !empty($job['external_id']) ? 'image' : 'pencil'; ?>"></i>
                                                </a>
                                                <?php if (is_admin() && empty($job['external_id'])): ?>
                                                    <a class="btn btn-sm btn-outline-danger" href="<?= url('/admin/jobs.php?delete=' . urlencode($job['id'])); ?>" onclick="return confirm('Delete this job?');" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="form-section">
            <h5 class="mb-3"><?= $editJob ? 'Edit Job' : 'Create Job'; ?></h5>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editJob): ?>
                    <input type="hidden" name="job_id" value="<?= e($editJob['id']); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($editJob['title'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="<?= e($editJob['department'] ?? ''); ?>" placeholder="e.g. Nursing, Radiology">
                </div>

                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= e($editJob['location'] ?? ''); ?>" placeholder="e.g. Main Hospital, Remote">
                </div>

                <div class="mb-3">
                    <label class="form-label">Employment Type</label>
                    <input type="text" name="employment_type" class="form-control" value="<?= e($editJob['employment_type'] ?? ''); ?>" placeholder="e.g. Full-time, Part-time, Contract">
                </div>

                <div class="mb-3">
                    <label class="form-label">Short Summary</label>
                    <textarea name="summary" rows="3" class="form-control" placeholder="Brief overview shown on the careers page"><?= e($editJob['summary'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="open" <?= ($editJob['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="closed" <?= ($editJob['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Description</label>
                    <textarea id="jobDescriptionEditor" name="description" rows="10" class="form-control"><?= $editJob['description'] ?? ''; ?></textarea>
                    <small class="text-muted">You can include responsibilities, qualifications, schedule, and benefits.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Job Image <?php if ($isExternalJob): ?><span class="badge bg-info">You can add image</span><?php endif; ?></label>
                    <?php if (!empty($editJob['image_url'])): ?>
                        <div class="mb-2">
                            <img src="<?= e($editJob['image_url']); ?>" alt="Current image" class="img-thumbnail" style="max-height: 150px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage<?= e($editJob['id'] ?? ''); ?>">
                                <label class="form-check-label" for="removeImage<?= e($editJob['id'] ?? ''); ?>">
                                    Remove current image
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($isExternalJob): ?>
                            <div class="alert alert-warning mb-2">
                                <i class="bi bi-image me-2"></i>No image set. Add an image to make this job posting more attractive.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <small class="text-muted">Upload an image for this job posting (JPEG, PNG, GIF, or WebP, max 5MB)</small>
                    <?php if (!empty($editJob['image_url'])): ?>
                        <input type="hidden" name="image_url" value="<?= e($editJob['image_url']); ?>">
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editJob ? 'Update Job' : 'Publish Job'; ?></button>
                    <?php if ($editJob): ?>
                        <a class="btn btn-outline-secondary" href="<?= url('/admin/jobs.php'); ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- include libraries(jQuery, bootstrap) -->
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- include summernote css/js (local) -->
<link href="<?= url('/public/vendor/summernote/dist/summernote-bs5.min.css'); ?>" rel="stylesheet">
<script src="<?= url('/public/vendor/summernote/dist/summernote-bs5.min.js'); ?>"></script>
<script>
    $(document).ready(function() {
        $('#jobDescriptionEditor').summernote({
            placeholder: 'Write the full job description here...',
            tabsize: 2,
            height: 260,
            styleTags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            callbacks: {
                onImageUpload: function(files) {
                    const file = files[0];
                    const formData = new FormData();
                    formData.append('image', file);
                    $.ajax({
                        url: '<?= url("/api/upload_image.php"); ?>',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#jobDescriptionEditor').summernote('insertImage', response.url);
                            } else {
                                alert(response.message || 'Failed to upload image.');
                            }
                        },
                        error: function() {
                            alert('Failed to upload image. Please try again.');
                        }
                    });
                }
            }
        });
        
    });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>


