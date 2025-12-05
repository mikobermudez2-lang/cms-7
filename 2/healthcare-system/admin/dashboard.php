<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$db = get_db();

$totals = [
    'total_posts'      => (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published_posts'  => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'draft_posts'      => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn(),
];

$recentPostsStmt = $db->query(
    'SELECT id, title, status, updated_at, published_at 
     FROM posts 
     ORDER BY updated_at DESC 
     LIMIT 6'
);
$recentPosts = $recentPostsStmt->fetchAll();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/partials/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card card-shadow h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Posts</p>
                <h2 id="statTotalPosts"><?= e((string) $totals['total_posts']); ?></h2>
                <p class="text-muted small mb-0">All stories in the CMS</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-shadow h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Published</p>
                <h2 id="statPublishedPosts"><?= e((string) $totals['published_posts']); ?></h2>
                <p class="text-muted small mb-0">Live on the site</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-shadow h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Drafts</p>
                <h2 id="statDraftPosts"><?= e((string) $totals['draft_posts']); ?></h2>
                <p class="text-muted small mb-0">Waiting for review</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-shadow h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Posts</h5>
                <a href="<?= url('/admin/posts.php'); ?>" class="btn btn-sm btn-outline-primary">Manage Posts</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentPosts)): ?>
                    <p class="text-muted mb-0">No posts yet. Create your first story to populate this feed.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentPosts as $post): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= e($post['title']); ?></h6>
                                    <small class="text-muted">
                                        <?= e(ucfirst($post['status'])); ?> • Updated <?= e(date('M d, Y g:i A', strtotime($post['updated_at']))); ?>
                                    </small>
                                </div>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= url('/admin/posts.php?edit=' . (int) $post['id']); ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-shadow h-100">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a class="btn btn-primary" href="<?= url('/admin/posts.php'); ?>">Create New Post</a>
                    <a class="btn btn-outline-primary" href="<?= url('/public/blog.php'); ?>" target="_blank">Preview Blog</a>
                </div>
                <hr>
                <p class="text-muted small mb-2">Publishing tips</p>
                <ul class="text-muted small mb-0">
                    <li>Keep slugs short and descriptive.</li>
                    <li>Use the draft state while reviewing content.</li>
                    <li>Published posts appear instantly on the site.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
