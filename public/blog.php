<?php
require_once __DIR__ . '/../includes/init.php';

$currentPage = 'blog';
$db = get_db();
$selectedPost = null;
$slug = isset($_GET['post']) ? trim((string) $_GET['post']) : '';
$selectedDate = isset($_GET['date']) ? trim((string) $_GET['date']) : '';
$categorySlug = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

// Get category if specified
$category = null;
if ($categorySlug) {
    $category = get_category_by_slug($categorySlug);
}

try {
    // Build query based on filters
    $whereConditions = ['status = "published"', 'archived_at IS NULL'];
    $params = [];
    
    if ($selectedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $whereConditions[] = 'DATE(COALESCE(published_at, created_at)) = ?';
        $params[] = $selectedDate;
    }
    
    if ($category) {
        $whereConditions[] = 'category_id = ?';
        $params[] = $category['id'];
    }
    
    $whereSql = implode(' AND ', $whereConditions);
    
    // Count total posts
    $countStmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalPosts = (int) $countStmt->fetchColumn();
    $totalPages = (int) ceil($totalPosts / $perPage);
    
    // Get posts with pagination
    $offset = ($page - 1) * $perPage;
    $postsStmt = $db->prepare(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                u.display_name as author_name
         FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN users u ON p.author_id = u.id
         WHERE {$whereSql}
         ORDER BY COALESCE(p.published_at, p.created_at) DESC
         LIMIT ? OFFSET ?"
    );
    $params[] = $perPage;
    $params[] = $offset;
    $postsStmt->execute($params);
    $posts = $postsStmt->fetchAll();

    // Get all dates that have posts (for calendar highlighting)
    $datesStmt = $db->prepare(
        'SELECT DISTINCT DATE(COALESCE(published_at, created_at)) as post_date
         FROM posts WHERE status = "published" ORDER BY post_date DESC LIMIT 30'
    );
    $datesStmt->execute();
    $postDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($slug !== '') {
        // Find selected post and increment view count
        $selectedStmt = $db->prepare(
            'SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                    u.display_name as author_name
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users u ON p.author_id = u.id
             WHERE p.status = "published" AND p.slug = ?'
        );
        $selectedStmt->execute([$slug]);
        $selectedPost = $selectedStmt->fetch() ?: null;
        
        // Increment view count
        if ($selectedPost) {
            $db->prepare('UPDATE posts SET view_count = view_count + 1 WHERE id = ?')->execute([$selectedPost['id']]);
        }
    }

    if (!$selectedPost && !empty($posts)) {
        $selectedPost = $posts[0];
    }
    
    // Get categories for sidebar
    $categories = get_categories();
    
} catch (Throwable $th) {
    error_log('Error loading blog: ' . $th->getMessage());
    $posts = [];
    $postDates = [];
    $categories = [];
    $totalPages = 0;
}

// SEO
$pageTitle = $selectedPost ? get_localized($selectedPost, 'title') : __('blog'); // 'blog' translation is now 'News'
$metaDescription = $selectedPost ? excerpt($selectedPost['content'], 160) : __('latest_stories');
$ogImage = url('/public/poster.jpg');

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Main Content -->
            <div class="col-lg-8">
                <?php if ($selectedPost): ?>
                    <article class="card card-shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php if (!empty($selectedPost['category_name'])): ?>
                                    <a href="?category=<?= e($selectedPost['category_slug']); ?>" class="badge text-decoration-none" style="background-color: <?= e($selectedPost['category_color'] ?? '#2563EB'); ?>">
                                        <?= e(get_localized($selectedPost, 'category_name')); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($selectedPost['archived_at'])): ?>
                                    <span class="badge bg-secondary">Archived</span>
                                <?php endif; ?>
                            </div>
                            
                            <h1 class="fw-bold mb-3"><?= e(get_localized($selectedPost, 'title')); ?></h1>
                            
                            <div class="d-flex flex-wrap gap-3 text-muted mb-4">
                                <small>
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= __('published_on'); ?> <?= e(date('F d, Y h:i A', strtotime($selectedPost['published_at'] ?? $selectedPost['created_at']))); ?>
                                </small>
                                <?php if (!empty($selectedPost['author_name'])): ?>
                                    <small>
                                        <i class="bi bi-person me-1"></i>
                                        <?= __('by_author'); ?> <?= e($selectedPost['author_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="announcement-content">
                                <?= get_localized($selectedPost, 'content'); ?>
                            </div>
                            
                            <!-- Share Buttons -->
                            <div class="mt-4 pt-3 border-top">
                                <strong class="me-2"><i class="bi bi-share"></i> <?= __('share'); ?>:</strong>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-facebook"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?= urlencode($selectedPost['title']); ?>" target="_blank" class="btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">
                            <?php if ($selectedDate !== ''): ?>
                                <?= __('no_results'); ?> <?= e(date('F d, Y', strtotime($selectedDate))); ?>.
                            <?php else: ?>
                                <?= __('no_posts'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Categories -->
                <div class="card card-shadow mb-3" id="categoriesCard">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-folder me-2 text-primary"></i><?= __('categories'); ?></h5>
                    </div>
                    <div class="card-body pt-0">
                        <div class="list-group list-group-flush" id="categoriesList">
                            <a href="<?= url('/public/blog.php'); ?>" class="list-group-item list-group-item-action <?= !$categorySlug ? 'active' : ''; ?>">
                                <?= __('all'); ?>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="?category=<?= e($cat['slug']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $categorySlug === $cat['slug'] ? 'active' : ''; ?>">
                                    <span><i class="<?= e($cat['icon']); ?> me-2" style="color: <?= e($cat['color']); ?>"></i><?= e(get_localized($cat, 'name')); ?></span>
                                    <span class="badge bg-secondary"><?= $cat['post_count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar Filter -->
                <div class="card card-shadow mb-3">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i><?= __('filter_by_date'); ?></h5>
                        <?php if ($selectedDate !== ''): ?>
                            <a href="<?= url('/public/blog.php'); ?><?= $categorySlug ? '?category=' . urlencode($categorySlug) : ''; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="get">
                            <?php if ($categorySlug): ?>
                                <input type="hidden" name="category" value="<?= e($categorySlug); ?>">
                            <?php endif; ?>
                            <input type="date" class="form-control" name="date" value="<?= e($selectedDate); ?>" max="<?= date('Y-m-d'); ?>" onchange="this.form.submit()">
                        </form>
                        <?php if (!empty($postDates)): ?>
                            <div class="mt-3 d-flex flex-wrap gap-1">
                                <?php foreach (array_slice($postDates, 0, 10) as $date): ?>
                                    <a href="?date=<?= urlencode($date); ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?>" class="btn btn-sm <?= $selectedDate === $date ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        <?= e(date('M j', strtotime($date))); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Posts -->
                <div class="card card-shadow" id="recentPostsCard">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="bi bi-newspaper me-2 text-primary"></i><?= __('recent_posts'); ?></h5>
                    </div>
                    <div class="card-body pt-0">
                        <?php if (empty($posts)): ?>
                            <p class="text-muted mb-0"><?= __('no_posts'); ?></p>
                        <?php else: ?>
                            <div class="list-group list-group-flush" id="recentPostsList">
                                <?php foreach (array_slice($posts, 0, 5) as $post): ?>
                                    <?php $isActive = $selectedPost && $selectedPost['id'] === $post['id']; ?>
                                    <a class="list-group-item list-group-item-action border-0 px-2 py-2 rounded mb-1 <?= $isActive ? 'bg-primary text-white' : ''; ?>" 
                                       href="?<?= $categorySlug ? 'category=' . urlencode($categorySlug) . '&' : ''; ?>post=<?= rawurlencode($post['slug']); ?>">
                                        <strong class="d-block mb-1 small"><?= e(get_localized($post, 'title')); ?></strong>
                                        <small class="<?= $isActive ? 'text-white-50' : 'text-muted'; ?>">
                                            <i class="bi bi-clock me-1"></i><?= e(date('M d, Y', strtotime($post['published_at'] ?? $post['created_at']))); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav class="mt-3">
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1; ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?><?= $selectedDate ? '&date=' . urlencode($selectedDate) : ''; ?>">«</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li class="page-item disabled">
                                            <span class="page-link"><?= $page; ?>/<?= $totalPages; ?></span>
                                        </li>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1; ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?><?= $selectedDate ? '&date=' . urlencode($selectedDate) : ''; ?>">»</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
