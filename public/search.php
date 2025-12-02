<?php
require_once __DIR__ . '/../includes/init.php';

$query = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$categorySlug = trim($_GET['category'] ?? '');
$perPage = 10;

$pageTitle = $query ? __('search') . ': ' . $query : __('search');
$currentPage = 'search';

// Get category if specified
$category = null;
if ($categorySlug) {
    $category = get_category_by_slug($categorySlug);
}

// Search options
$options = [];
if ($category) {
    $options['category_id'] = $category['id'];
}

// Perform search
$results = ['items' => [], 'total' => 0, 'totalPages' => 0];
if ($query !== '' || $category) {
    try {
        $results = search_posts($query, $options, $page, $perPage);
    } catch (Throwable $e) {
        $results = search_posts_simple($query, $page, $perPage);
    }
}

// Get all categories for filter
$categories = get_categories();

include __DIR__ . '/includes/header.php';
?>

<section class="py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-0">
                    <i class="bi bi-search me-2 text-primary"></i>
                    <?php if ($query): ?>
                        <?= __('search'); ?>: "<?= e($query); ?>"
                    <?php else: ?>
                        <?= __('search'); ?>
                    <?php endif; ?>
                </h1>
                <?php if ($results['total'] > 0): ?>
                    <p class="text-muted mb-0 mt-2">
                        <?= $results['total']; ?> <?= $results['total'] === 1 ? 'result' : 'results'; ?> found
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Search Results -->
            <div class="col-lg-8">
                <!-- Search Form -->
                <form action="" method="get" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="search" class="form-control" name="q" value="<?= e($query); ?>" placeholder="<?= __('search_placeholder'); ?>">
                        <?php if ($categorySlug): ?>
                            <input type="hidden" name="category" value="<?= e($categorySlug); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> <?= __('search'); ?>
                        </button>
                    </div>
                </form>
                
                <?php if (empty($results['items'])): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <?php if ($query): ?>
                            <?= __('no_results'); ?> "<?= e($query); ?>"
                        <?php else: ?>
                            Enter a search term to find posts.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="search-results">
                        <?php foreach ($results['items'] as $post): ?>
                            <article class="card card-shadow mb-3">
                                <div class="card-body">
                                    <?php if (!empty($post['category_name'])): ?>
                                        <a href="?category=<?= e($post['category_slug']); ?>" class="badge text-decoration-none mb-2" style="background-color: <?= e($post['category_color'] ?? '#2563EB'); ?>">
                                            <?= e(get_localized($post, 'category_name')); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <h2 class="h5 mb-2">
                                        <a href="<?= url('/public/blog.php?post=' . rawurlencode($post['slug'])); ?>" class="text-decoration-none text-dark">
                                            <?= e(get_localized($post, 'title')); ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="text-muted mb-2">
                                        <?= e(excerpt($post['content'] ?? '', 200)); ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?= e(date('M d, Y', strtotime($post['published_at'] ?? $post['created_at']))); ?>
                                            <?php if (!empty($post['author_name'])): ?>
                                                <span class="mx-1">•</span>
                                                <i class="bi bi-person me-1"></i><?= e($post['author_name']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($post['view_count'])): ?>
                                                <span class="mx-1">•</span>
                                                <i class="bi bi-eye me-1"></i><?= number_format($post['view_count']); ?> <?= __('views'); ?>
                                            <?php endif; ?>
                                        </small>
                                        
                                        <a href="<?= url('/public/blog.php?post=' . rawurlencode($post['slug'])); ?>" class="btn btn-sm btn-outline-primary">
                                            <?= __('read_more'); ?> <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                    
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($results['totalPages'] > 1): ?>
                        <nav aria-label="Search results pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?= urlencode($query); ?>&page=<?= $page - 1; ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?>">
                                            <i class="bi bi-chevron-left"></i> <?= __('previous'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($results['totalPages'], $page + 2);
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?q=<?= urlencode($query); ?>&page=<?= $i; ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $results['totalPages']): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?= urlencode($query); ?>&page=<?= $page + 1; ?><?= $categorySlug ? '&category=' . urlencode($categorySlug) : ''; ?>">
                                            <?= __('next'); ?> <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Categories Filter -->
                <div class="card card-shadow mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-folder me-2 text-primary"></i><?= __('categories'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="?q=<?= urlencode($query); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= !$categorySlug ? 'active' : ''; ?>">
                                <?= __('all'); ?>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="?q=<?= urlencode($query); ?>&category=<?= urlencode($cat['slug']); ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $categorySlug === $cat['slug'] ? 'active' : ''; ?>">
                                    <span>
                                        <i class="<?= e($cat['icon'] ?? 'bi-folder'); ?> me-2" style="color: <?= e($cat['color']); ?>"></i>
                                        <?= e(get_localized($cat, 'name')); ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill"><?= $cat['post_count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

