<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;
$editPost = null;
$editingId = isset($_GET['edit']) ? trim($_GET['edit']) : null;

// Get all categories for dropdown
$categories = get_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session.';
    } else {
        try {
        $postId     = isset($_POST['post_id']) && $_POST['post_id'] !== '' ? trim($_POST['post_id']) : null;
        $title      = trim($_POST['title'] ?? '');
        $slug       = trim($_POST['slug'] ?? '');
        $content    = sanitize_html(trim($_POST['content'] ?? ''));
        $categoryId = !empty($_POST['category_id']) ? trim($_POST['category_id']) : null;
        $status     = in_array($_POST['status'] ?? 'draft', ['draft', 'scheduled', 'published'], true) ? $_POST['status'] : 'draft';
        $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        
        // Validate category_id if provided
        if ($categoryId) {
            try {
                // Check if categories table exists
                $tableExists = $db->query("SHOW TABLES LIKE 'categories'")->fetch();
                if ($tableExists) {
                    $catCheck = $db->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
                    $catCheck->execute([$categoryId]);
                    if (!$catCheck->fetch()) {
                        $categoryId = null; // Invalid category, set to null
                    }
                } else {
                    $categoryId = null; // Categories table doesn't exist
                }
            } catch (Throwable $e) {
                $categoryId = null; // Error checking category, set to null
                error_log('Category validation error: ' . $e->getMessage());
            }
        }

        if ($title === '' || $content === '') {
            $flashError = 'Title and content are required.';
        } else {
            if ($slug === '') {
                $slug = slugify($title);
            } else {
                $slug = slugify($slug);
            }

            // Ensure unique slug
            $slugCheckStmt = $db->prepare('SELECT id FROM posts WHERE slug = ? AND id <> ? LIMIT 1');
            $slugCheckStmt->execute([$slug, $postId ?? '']);
            if ($slugCheckStmt->fetch()) {
                $flashError = 'Another post already uses this slug.';
            } else {
                $publishedAt = null;
                if ($status === 'published') {
                    $publishedAt = date('Y-m-d H:i:s');
                } elseif ($status === 'scheduled' && $scheduledAt) {
                    $scheduledAt = date('Y-m-d H:i:s', strtotime($scheduledAt));
                }

                $user = current_user();

                if ($postId) {
                    // Keep previous published_at if already set and staying published
                    $existingStmt = $db->prepare('SELECT published_at FROM posts WHERE id = ? LIMIT 1');
                    $existingStmt->execute([$postId]);
                    $existing = $existingStmt->fetch();
                    if ($existing && $existing['published_at'] && $status === 'published') {
                        $publishedAt = $existing['published_at'];
                    }

                    $stmt = $db->prepare(
                        'UPDATE posts 
                         SET title = :title,
                             slug = :slug,
                             content = :content,
                             category_id = :category_id,
                             status = :status,
                             scheduled_at = :scheduled_at,
                             published_at = :published_at
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'title'          => $title,
                        'slug'           => $slug,
                        'content'        => $content,
                        'category_id'    => $categoryId ?: null,
                        'status'         => $status,
                        'scheduled_at'   => $status === 'scheduled' ? $scheduledAt : null,
                        'published_at'   => $publishedAt,
                        'id'             => $postId,
                    ]);
                    
                    log_activity('update_post', 'post', $postId, 'Updated: ' . $title);
                    set_flash('Post updated.');
                } else {
                    $newId = generate_id();
                    
                    // Use basic INSERT - only required fields
                    $stmt = $db->prepare(
                        'INSERT INTO posts (id, author_id, title, slug, content, category_id, status, scheduled_at, published_at)
                         VALUES (:id, :author_id, :title, :slug, :content, :category_id, :status, :scheduled_at, :published_at)'
                    );
                    
                    $stmt->execute([
                        'id'             => $newId,
                        'author_id'      => $user['id'] ?? null,
                        'title'          => $title,
                        'slug'           => $slug,
                        'content'        => $content,
                        'category_id'    => $categoryId ?: null,
                        'status'         => $status,
                        'scheduled_at'   => $status === 'scheduled' ? $scheduledAt : null,
                        'published_at'   => $publishedAt,
                    ]);
                    
                    log_activity('create_post', 'post', $newId, 'Created: ' . $title);
                    set_flash('Post created.');
                }

                redirect('/admin/posts.php');
            }
        }
        } catch (PDOException $e) {
            error_log('Database error in posts.php: ' . $e->getMessage());
            error_log('SQL State: ' . $e->getCode());
            $flashError = 'Database error: ' . ($e->getCode() == '23000' ? 'Invalid category or constraint violation.' : 'Please check your database connection.');
        } catch (Throwable $e) {
            error_log('Error in posts.php: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $flashError = 'An error occurred while saving the post. Please try again.';
        }
    }
}

if (isset($_GET['archive'])) {
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/posts.php');
    }
    $archiveId = trim($_GET['archive']);
    
    // Get post title before archiving for logging
    $postStmt = $db->prepare('SELECT title FROM posts WHERE id = ? LIMIT 1');
    $postStmt->execute([$archiveId]);
    $post = $postStmt->fetch();
    $postTitle = $post['title'] ?? 'Unknown Post';
    
    $stmt = $db->prepare('UPDATE posts SET archived_at = NOW() WHERE id = ? AND archived_at IS NULL');
    $stmt->execute([$archiveId]);
    
    log_activity('archive_post', 'post', $archiveId, 'Archived: ' . $postTitle);
    set_flash('Post archived.');
    redirect('/admin/posts.php');
}

if (isset($_GET['unarchive'])) {
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/posts.php');
    }
    $unarchiveId = trim($_GET['unarchive']);
    
    // Get post title before unarchiving for logging
    $postStmt = $db->prepare('SELECT title FROM posts WHERE id = ? LIMIT 1');
    $postStmt->execute([$unarchiveId]);
    $post = $postStmt->fetch();
    $postTitle = $post['title'] ?? 'Unknown Post';
    
    $stmt = $db->prepare('UPDATE posts SET archived_at = NULL WHERE id = ?');
    $stmt->execute([$unarchiveId]);
    
    log_activity('unarchive_post', 'post', $unarchiveId, 'Unarchived: ' . $postTitle);
    set_flash('Post unarchived.');
    redirect('/admin/posts.php');
}

if (isset($_GET['delete'])) {
    if (!is_admin()) {
        set_flash('Only administrators can delete posts.', 'danger');
        redirect('/admin/posts.php');
    }
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/posts.php');
    }
    $deleteId = trim($_GET['delete']);
    
    // Get post title before deleting for logging
    $postStmt = $db->prepare('SELECT title FROM posts WHERE id = ? LIMIT 1');
    $postStmt->execute([$deleteId]);
    $post = $postStmt->fetch();
    $postTitle = $post['title'] ?? 'Unknown Post';
    
    $stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$deleteId]);
    
    log_activity('delete_post', 'post', $deleteId, 'Deleted: ' . $postTitle);
    set_flash('Post deleted.');
    redirect('/admin/posts.php');
}

if ($editingId) {
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$editingId]);
    $editPost = $stmt->fetch() ?: null;
}

$postsStmt = $db->query('SELECT p.id, p.title, p.slug, p.status, p.created_at, p.updated_at, p.published_at, p.archived_at, p.scheduled_at, c.name as category_name, c.color as category_color FROM posts p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.updated_at DESC');
$posts = $postsStmt->fetchAll();

$pageTitle = 'Posts';
$activePage = 'posts';
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
                <h5 class="mb-0">Posts</h5>
                <small class="text-muted"><?= count($posts); ?> total</small>
            </div>
            <div class="card-body p-0">
                <?php if (empty($posts)): ?>
                    <p class="text-muted text-center py-4 mb-0">No posts yet. Create your first post to get started.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($post['title']); ?></strong><br>
                                            <small class="text-muted"><?= e($post['slug']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($post['category_name']): ?>
                                                <span class="badge" style="background-color: <?= e($post['category_color'] ?? '#6c757d'); ?>">
                                                    <?= e($post['category_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColor = match($post['status']) {
                                                'published' => 'success',
                                                'scheduled' => 'warning',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge text-bg-<?= $statusColor; ?>">
                                                <?= e(ucfirst($post['status'])); ?>
                                            </span>
                                            <?php if ($post['status'] === 'scheduled' && $post['scheduled_at']): ?>
                                                <br><small class="text-muted"><?= e(date('M d, g:i A', strtotime($post['scheduled_at']))); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($post['archived_at'])): ?>
                                                <br><small class="badge bg-secondary mt-1">Archived</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= e(date('M d, Y g:i A', strtotime($post['updated_at']))); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/posts.php?edit=' . urlencode($post['id'])); ?>" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (empty($post['archived_at'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/admin/posts.php?archive=' . urlencode($post['id']) . '&csrf=' . urlencode(csrf_token())); ?>" onclick="return confirm('Archive this post? It will be moved to archived section.');" title="Archive">
                                                        <i class="bi bi-archive"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a class="btn btn-sm btn-outline-info" href="<?= url('/admin/posts.php?unarchive=' . urlencode($post['id']) . '&csrf=' . urlencode(csrf_token())); ?>" onclick="return confirm('Unarchive this post? It will be moved back to recent posts.');" title="Unarchive">
                                                        <i class="bi bi-archive-fill"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (is_admin()): ?>
                                                    <a class="btn btn-sm btn-outline-danger" href="<?= url('/admin/posts.php?delete=' . urlencode($post['id']) . '&csrf=' . urlencode(csrf_token())); ?>" onclick="return confirm('Delete this post?');" title="Delete">
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
            <h5 class="mb-3"><?= $editPost ? 'Edit Post' : 'Create Post'; ?></h5>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()); ?>">
                <?php if ($editPost): ?>
                    <input type="hidden" name="post_id" value="<?= e($editPost['id']); ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= e($editPost['title'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?= e($editPost['slug'] ?? ''); ?>" placeholder="auto-generated if left empty">
                    <small class="text-muted">Used in the blog URL.</small>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— No Category —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['id']); ?>" <?= ($editPost['category_id'] ?? '') === $cat['id'] ? 'selected' : ''; ?>>
                                    <?= e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="statusSelect" onchange="toggleSchedule()">
                            <option value="draft" <?= ($editPost['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="scheduled" <?= ($editPost['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="published" <?= ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3" id="scheduleField" style="display: <?= ($editPost['status'] ?? '') === 'scheduled' ? 'block' : 'none'; ?>;">
                    <label class="form-label">Schedule Publish Date</label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" 
                           value="<?= $editPost['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($editPost['scheduled_at'])) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea id="postEditor" name="content" rows="12" class="form-control" required><?= $editPost['content'] ?? ''; ?></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg me-1"></i>
                        <?= $editPost ? 'Update Post' : 'Save Post'; ?>
                    </button>
                    <?php if ($editPost): ?>
                        <a class="btn btn-outline-secondary" href="<?= url('/admin/posts.php'); ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function toggleSchedule() {
        const status = document.getElementById('statusSelect').value;
        const scheduleField = document.getElementById('scheduleField');
        scheduleField.style.display = status === 'scheduled' ? 'block' : 'none';
    }
    </script>
</div>

<!-- include libraries(jQuery, bootstrap) -->
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- include summernote css/js (local) -->
<link href="<?= url('/public/vendor/summernote/dist/summernote-bs5.min.css'); ?>" rel="stylesheet">
<script src="<?= url('/public/vendor/summernote/dist/summernote-bs5.min.js'); ?>"></script>
<script>
    $(document).ready(function() {
        $('#postEditor').summernote({
            placeholder: 'Write your story...',
            tabsize: 2,
            height: 320,
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
                                $('#postEditor').summernote('insertImage', response.url);
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


