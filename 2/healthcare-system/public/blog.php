<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Blog';
$currentPage = 'blog';
$db = get_db();
$selectedPost = null;
$slug = isset($_GET['post']) ? trim((string) $_GET['post']) : '';
$filter = isset($_GET['filter']) ? trim((string) $_GET['filter']) : 'recent'; // 'recent' or 'archived'

try {
    // Build query based on filter
    if ($filter === 'archived') {
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at
             FROM posts
             WHERE status = "published"
               AND archived_at IS NOT NULL
             ORDER BY archived_at DESC, COALESCE(published_at, updated_at) DESC'
        );
    } else {
        // Recent posts (not archived, published within last 7 days or not yet archived)
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at
             FROM posts
             WHERE status = "published"
               AND archived_at IS NULL
             ORDER BY COALESCE(published_at, updated_at) DESC'
        );
    }
    $postsStmt->execute();
    $posts = $postsStmt->fetchAll();

    if ($slug !== '') {
        // Find selected post (can be from recent or archived)
        $selectedStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at
             FROM posts
             WHERE status = "published" AND slug = ?'
        );
        $selectedStmt->execute([$slug]);
        $selectedPost = $selectedStmt->fetch() ?: null;
        
        // Update filter if selected post is archived
        if ($selectedPost && !empty($selectedPost['archived_at'])) {
            $filter = 'archived';
        }
    }

    if (!$selectedPost && !empty($posts)) {
        $selectedPost = $posts[0];
    }
} catch (Throwable $th) {
    error_log('Error loading blog: ' . $th->getMessage());
    $posts = [];
}

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($selectedPost): ?>
                    <article class="card card-shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <small class="text-muted">
                                    <?php if (!empty($selectedPost['archived_at'])): ?>
                                        <span class="badge bg-secondary me-2">Archived</span>
                                    <?php endif; ?>
                                    Published: <?= e(date('F d, Y h:i A', strtotime($selectedPost['published_at'] ?? $selectedPost['updated_at']))); ?>
                                    <?php if (!empty($selectedPost['archived_at'])): ?>
                                        <br>Archived: <?= e(date('F d, Y h:i A', strtotime($selectedPost['archived_at']))); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <h1 class="fw-bold mb-3"><?= e($selectedPost['title']); ?></h1>
                            <div class="announcement-content">
                                <?= $selectedPost['content']; ?>
                            </div>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">
                            <?php if ($filter === 'archived'): ?>
                                No archived posts available.
                            <?php else: ?>
                                No recent posts available.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <div class="card card-shadow mb-3">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Filter Posts</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group w-100" role="group">
                            <a href="<?= url('/public/blog.php?filter=recent' . ($selectedPost ? '&post=' . rawurlencode($selectedPost['slug']) : '')); ?>" 
                               class="btn <?= $filter === 'recent' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-clock me-1"></i>Recent
                            </a>
                            <a href="<?= url('/public/blog.php?filter=archived' . ($selectedPost ? '&post=' . rawurlencode($selectedPost['slug']) : '')); ?>" 
                               class="btn <?= $filter === 'archived' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-archive me-1"></i>Archived
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card card-shadow">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <?= $filter === 'archived' ? 'Archived Posts' : 'Recent Posts'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($posts)): ?>
                            <p class="text-muted mb-0">
                                <?php if ($filter === 'archived'): ?>
                                    No archived posts yet. Posts are automatically archived after 7 days.
                                <?php else: ?>
                                    No recent posts available.
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($posts as $post): ?>
                                    <a class="list-group-item list-group-item-action <?= $selectedPost && $selectedPost['id'] === $post['id'] ? 'active' : ''; ?>" 
                                       href="<?= url('/public/blog.php?filter=' . $filter . '&post=' . rawurlencode($post['slug'])); ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <?php if (!empty($post['archived_at'])): ?>
                                                    <span class="badge bg-secondary bg-opacity-50 mb-1">Archived</span>
                                                <?php endif; ?>
                                                <strong><?= e($post['title']); ?></strong>
                                                <p class="text-muted small mb-0"><?= e(excerpt($post['content'], 90)); ?></p>
                                                <small class="text-muted">
                                                    <?= e(date('M d, Y', strtotime($post['published_at'] ?? $post['updated_at']))); ?>
                                                </small>
                                            </div>
                                            <i class="bi bi-chevron-right"></i>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>


