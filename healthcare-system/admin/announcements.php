<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session.';
    } else {
        $message = trim($_POST['message'] ?? '');
        
        // Remove base64 images to prevent database issues
        // Base64 images start with data:image
        $message = preg_replace('/<img[^>]+src=["\']data:image[^"\']+["\'][^>]*>/i', '', $message);
        
        if ($message) {
            // Ensure uploads directory exists
            $uploadDir = __DIR__ . '/../uploads/announcements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $stmt = $db->prepare('INSERT INTO announcements (message) VALUES (?)');
            $stmt->execute([$message]);
            set_flash('Announcement posted.');
            redirect('/admin/announcements.php');
        } else {
            $flashError = 'Message is required.';
        }
    }
}

if (isset($_GET['delete'])) {
    if (!can_delete_announcements()) {
        set_flash('You do not have permission to delete announcements.', 'danger');
        redirect('/admin/announcements.php');
    }
    $stmt = $db->prepare('DELETE FROM announcements WHERE id = ?');
    $stmt->execute([(int) $_GET['delete']]);
    set_flash('Announcement removed.');
    redirect('/admin/announcements.php');
}

$announcementsStmt = $db->query('SELECT * FROM announcements ORDER BY created_at DESC');
$announcements = $announcementsStmt->fetchAll();

$pageTitle = 'Announcements';
$activePage = 'announcements';
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
        <h5 class="mb-3">Published Announcements</h5>
        <div class="announcement-feed">
            <?php foreach ($announcements as $announcement): ?>
                <div class="list-group-item announcement-preview">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <small class="text-muted">
                            <?= e(date('F d, Y', strtotime($announcement['created_at']))); ?>
                        </small>
                        <?php if (can_delete_announcements()): ?>
                            <a class="btn btn-sm btn-outline-danger" href="<?= url('/admin/announcements.php?delete=' . (int) $announcement['id']); ?>" onclick="return confirm('Delete announcement?');">Delete</a>
                        <?php endif; ?>
                    </div>
                    <div class="announcement-content"><?= $announcement['message']; ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($announcements)): ?>
                <div class="list-group-item">
                    <p class="text-muted text-center mb-0">No announcements posted yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="form-section">
            <h5 class="mb-3">Create Announcement</h5>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea id="announcementEditor" name="message" rows="10" class="form-control" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Publish</button>
            </form>
        </div>
    </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#announcementEditor').summernote({
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            placeholder: 'Type your announcement here...',
            callbacks: {
                onImageUpload: function(files) {
                    // Upload image to server
                    var file = files[0];
                    var formData = new FormData();
                    formData.append('image', file);
                    
                    $.ajax({
                        url: '<?= url("/api/upload_image.php"); ?>',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                // Insert image into editor
                                $('#announcementEditor').summernote('insertImage', response.url);
                            } else {
                                alert('Failed to upload image: ' + (response.message || 'Unknown error'));
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

