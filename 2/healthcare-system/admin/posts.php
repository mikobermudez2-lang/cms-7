<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();
$flashError = null;
$editPost = null;
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $flashError = 'Invalid session.';
    } else {
        $postId   = isset($_POST['post_id']) ? (int) $_POST['post_id'] : null;
        $title    = trim($_POST['title'] ?? '');
        $slug     = trim($_POST['slug'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $status   = in_array($_POST['status'] ?? 'draft', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

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
            $slugCheckStmt->execute([$slug, $postId ?? 0]);
            if ($slugCheckStmt->fetch()) {
                $flashError = 'Another post already uses this slug.';
            } else {
                $publishedAt = null;
                if ($status === 'published') {
                    $publishedAt = date('Y-m-d H:i:s');
                }

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
                             status = :status,
                             published_at = :published_at
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'title'        => $title,
                        'slug'         => $slug,
                        'content'      => $content,
                        'status'       => $status,
                        'published_at' => $publishedAt,
                        'id'           => $postId,
                    ]);
                    set_flash('Post updated.');
                } else {
                    $stmt = $db->prepare(
                        'INSERT INTO posts (title, slug, content, status, published_at)
                         VALUES (:title, :slug, :content, :status, :published_at)'
                    );
                    $stmt->execute([
                        'title'        => $title,
                        'slug'         => $slug,
                        'content'      => $content,
                        'status'       => $status,
                        'published_at' => $publishedAt,
                    ]);
                    set_flash('Post created.');
                }

                redirect('/admin/posts.php');
            }
        }
    }
}

if (isset($_GET['archive'])) {
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/posts.php');
    }
    $archiveId = (int) $_GET['archive'];
    $stmt = $db->prepare('UPDATE posts SET archived_at = NOW() WHERE id = ? AND archived_at IS NULL');
    $stmt->execute([$archiveId]);
    set_flash('Post archived.');
    redirect('/admin/posts.php');
}

if (isset($_GET['unarchive'])) {
    if (!verify_csrf($_GET['csrf'] ?? '')) {
        set_flash('Invalid session.', 'danger');
        redirect('/admin/posts.php');
    }
    $unarchiveId = (int) $_GET['unarchive'];
    $stmt = $db->prepare('UPDATE posts SET archived_at = NULL WHERE id = ?');
    $stmt->execute([$unarchiveId]);
    set_flash('Post unarchived.');
    redirect('/admin/posts.php');
}

if (isset($_GET['delete'])) {
    if (!is_admin()) {
        set_flash('Only administrators can delete posts.', 'danger');
        redirect('/admin/posts.php');
    }
    $deleteId = (int) $_GET['delete'];
    $stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$deleteId]);
    set_flash('Post deleted.');
    redirect('/admin/posts.php');
}

if ($editingId) {
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$editingId]);
    $editPost = $stmt->fetch() ?: null;
}

$postsStmt = $db->query('SELECT id, title, slug, status, created_at, updated_at, published_at, archived_at FROM posts ORDER BY updated_at DESC');
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
                                            <span class="badge text-bg-<?= $post['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                                <?= e(ucfirst($post['status'])); ?>
                                            </span>
                                            <?php if (!empty($post['archived_at'])): ?>
                                                <br><small class="badge bg-secondary mt-1">Archived</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= e(date('M d, Y g:i A', strtotime($post['updated_at']))); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= url('/admin/posts.php?edit=' . (int) $post['id']); ?>" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (empty($post['archived_at'])): ?>
                                                    <a class="btn btn-sm btn-outline-secondary" href="<?= url('/admin/posts.php?archive=' . (int) $post['id'] . '&csrf=' . urlencode(csrf_token())); ?>" onclick="return confirm('Archive this post? It will be moved to archived section.');" title="Archive">
                                                        <i class="bi bi-archive"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a class="btn btn-sm btn-outline-info" href="<?= url('/admin/posts.php?unarchive=' . (int) $post['id'] . '&csrf=' . urlencode(csrf_token())); ?>" onclick="return confirm('Unarchive this post? It will be moved back to recent posts.');" title="Unarchive">
                                                        <i class="bi bi-archive-fill"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (is_admin()): ?>
                                                    <a class="btn btn-sm btn-outline-danger" href="<?= url('/admin/posts.php?delete=' . (int) $post['id']); ?>" onclick="return confirm('Delete this post?');" title="Delete">
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
                    <input type="hidden" name="post_id" value="<?= (int) $editPost['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= e($editPost['title'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?= e($editPost['slug'] ?? ''); ?>" placeholder="auto-generated if left empty">
                    <small class="text-muted">Used in the blog URL.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="draft" <?= ($editPost['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?= ($editPost['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea id="postEditor" name="content" rows="12" class="form-control" required><?= $editPost['content'] ?? ''; ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><?= $editPost ? 'Update Post' : 'Publish Post'; ?></button>
                    <?php if ($editPost): ?>
                        <a class="btn btn-outline-secondary" href="<?= url('/admin/posts.php'); ?>">Cancel</a>
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


