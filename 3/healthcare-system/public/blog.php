<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Blog';
$currentPage = 'blog';
$db = get_db();
$selectedPost = null;
$slug = isset($_GET['post']) ? trim((string) $_GET['post']) : '';
$selectedDate = isset($_GET['date']) ? trim((string) $_GET['date']) : '';

try {
    // Build query based on date filter
    if ($selectedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        // Filter by specific date
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published"
               AND DATE(COALESCE(published_at, created_at)) = ?
             ORDER BY COALESCE(published_at, created_at) DESC'
        );
        $postsStmt->execute([$selectedDate]);
    } else {
        // Show all recent posts (not archived)
        $postsStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published"
               AND archived_at IS NULL
             ORDER BY COALESCE(published_at, created_at) DESC'
        );
        $postsStmt->execute();
    }
    $posts = $postsStmt->fetchAll();

    // Get all dates that have posts (for calendar highlighting)
    $datesStmt = $db->prepare(
        'SELECT DISTINCT DATE(COALESCE(published_at, created_at)) as post_date
         FROM posts
         WHERE status = "published"
         ORDER BY post_date DESC'
    );
    $datesStmt->execute();
    $postDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($slug !== '') {
        // Find selected post
        $selectedStmt = $db->prepare(
            'SELECT id, title, slug, content, published_at, updated_at, archived_at, created_at
             FROM posts
             WHERE status = "published" AND slug = ?'
        );
        $selectedStmt->execute([$slug]);
        $selectedPost = $selectedStmt->fetch() ?: null;
    }

    if (!$selectedPost && !empty($posts)) {
        $selectedPost = $posts[0];
    }
} catch (Throwable $th) {
    error_log('Error loading blog: ' . $th->getMessage());
    $posts = [];
    $postDates = [];
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
                                    Published: <?= e(date('F d, Y h:i A', strtotime($selectedPost['published_at'] ?? $selectedPost['created_at']))); ?>
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
                            <?php if ($selectedDate !== ''): ?>
                                No posts found for <?= e(date('F d, Y', strtotime($selectedDate))); ?>.
                            <?php else: ?>
                                No posts available.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4">
                <!-- Calendar Filter -->
                <div class="card card-shadow mb-3">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>Filter by Date</h5>
                        <?php if ($selectedDate !== ''): ?>
                            <a href="<?= url('/public/blog.php'); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form id="dateFilterForm" method="get" action="<?= url('/public/blog.php'); ?>">
                            <div class="mb-3">
                                <input type="date" 
                                       class="form-control form-control-lg" 
                                       id="dateFilter" 
                                       name="date" 
                                       value="<?= e($selectedDate); ?>"
                                       max="<?= date('Y-m-d'); ?>"
                                       onchange="this.form.submit()">
                            </div>
                        </form>
                        <?php if (!empty($postDates)): ?>
                            <div class="mt-3">
                                <small class="text-muted d-block mb-2">Recent dates with posts:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach (array_slice($postDates, 0, 10) as $date): ?>
                                        <a href="<?= url('/public/blog.php?date=' . urlencode($date)); ?>" 
                                           class="btn btn-sm <?= $selectedDate === $date ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <?= e(date('M j', strtotime($date))); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($selectedDate !== ''): ?>
                            <div class="alert alert-info mt-3 mb-0 py-2">
                                <small>
                                    <i class="bi bi-funnel me-1"></i>
                                    Showing posts from <strong><?= e(date('F d, Y', strtotime($selectedDate))); ?></strong>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Posts List -->
                <div class="card card-shadow">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-newspaper me-2 text-primary"></i>
                            <?= $selectedDate !== '' ? 'Posts on ' . e(date('M d, Y', strtotime($selectedDate))) : 'Recent Posts'; ?>
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (empty($posts)): ?>
                            <p class="text-muted mb-0">
                                <?php if ($selectedDate !== ''): ?>
                                    No posts found for this date.
                                <?php else: ?>
                                    No posts available.
                                <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($posts as $post): ?>
                                    <?php 
                                    $postDate = date('Y-m-d', strtotime($post['published_at'] ?? $post['created_at']));
                                    $linkParams = $selectedDate !== '' ? "date={$selectedDate}&" : '';
                                    $isActive = $selectedPost && $selectedPost['id'] === $post['id'];
                                    ?>
                                    <a class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1 <?= $isActive ? 'bg-primary text-white' : ''; ?>" 
                                       href="<?= url('/public/blog.php?' . $linkParams . 'post=' . rawurlencode($post['slug'])); ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1 me-2">
                                                <strong class="d-block mb-1"><?= e($post['title']); ?></strong>
                                                <p class="small mb-1 lh-sm <?= $isActive ? 'text-white-50' : 'text-muted'; ?>"><?= e(excerpt($post['content'], 60)); ?></p>
                                                <small class="<?= $isActive ? 'text-white-50' : 'text-muted'; ?>">
                                                    <i class="bi bi-clock me-1"></i><?= e(date('M d, Y', strtotime($post['published_at'] ?? $post['created_at']))); ?>
                                                </small>
                                            </div>
                                            <i class="bi bi-chevron-right <?= $isActive ? 'text-white-50' : 'text-muted'; ?>"></i>
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


