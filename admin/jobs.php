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
        $title          = trim($_POST['title'] ?? '');
        $department     = trim($_POST['department'] ?? '');
        $location       = trim($_POST['location'] ?? '');
        $employmentType = trim($_POST['employment_type'] ?? '');
        $summary        = trim($_POST['summary'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $status         = in_array($_POST['status'] ?? 'open', ['open', 'closed'], true) ? $_POST['status'] : 'open';

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
                    'status'         => $status,
                    'posted_at'      => $postedAt,
                    'id'             => $jobId,
                ]);
                set_flash('Job updated.');
                } else {
                $newId = generate_id();
                $stmt = $db->prepare(
                    'INSERT INTO jobs (id, title, department, location, employment_type, summary, description, status, posted_at)
                     VALUES (:id, :title, :department, :location, :employment_type, :summary, :description, :status, :posted_at)'
                );
                $stmt->execute([
                    'id'             => $newId,
                    'title'          => $title,
                    'department'     => $department,
                    'location'       => $location,
                    'employment_type'=> $employmentType,
                    'summary'        => $summary,
                    'description'    => $description,
                    'status'         => $status,
                    'posted_at'      => $postedAt,
                ]);
                set_flash('Job created.');
            }

            redirect('/admin/jobs.php');
        }
    }
}

if (isset($_GET['delete'])) {
    if (!is_admin()) {
        set_flash('Only administrators can delete jobs.', 'danger');
        redirect('/admin/jobs.php');
    }
    $deleteId = trim($_GET['delete']);
    $stmt = $db->prepare('DELETE FROM jobs WHERE id = ?');
    $stmt->execute([$deleteId]);
    set_flash('Job deleted.');
    redirect('/admin/jobs.php');
}

if ($editingId) {
    $stmt = $db->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$editingId]);
    $editJob = $stmt->fetch() ?: null;
}

try {
    $jobsStmt = $db->query(
        'SELECT id, title, department, location, employment_type, status, posted_at
         FROM jobs
         ORDER BY posted_at DESC, id DESC'
    );
    $jobs = $jobsStmt->fetchAll();
} catch (Throwable $th) {
    $flashError = 'Jobs table is not available yet. Please ask your integration partner to create it.';
    $jobs = [];
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
                <small class="text-muted"><?= count($jobs); ?> total</small>
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
                                        <td><strong><?= e($job['title']); ?></strong></td>
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
                                                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/jobs.php?edit=' . urlencode($job['id'])); ?>" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (is_admin()): ?>
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
            <form method="post">
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


