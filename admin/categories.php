<?php
require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$pageTitle = 'Categories';
$action = $_GET['action'] ?? 'list';
$editingId = isset($_GET['edit']) ? trim($_GET['edit']) : null;
$deleteId = isset($_GET['delete']) ? trim($_GET['delete']) : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $namePh = trim($_POST['name_ph'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $descriptionPh = trim($_POST['description_ph'] ?? '');
    $color = trim($_POST['color'] ?? '#2563EB');
    $icon = trim($_POST['icon'] ?? 'bi-folder');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    
    if ($name === '') {
        set_flash('Category name is required.', 'danger');
    } else {
        $data = [
            'name' => $name,
            'name_ph' => $namePh ?: null,
            'description' => $description ?: null,
            'description_ph' => $descriptionPh ?: null,
            'color' => $color,
            'icon' => $icon,
            'sort_order' => $sortOrder,
        ];
        
        if ($editingId) {
            if (update_category($editingId, $data)) {
                set_flash('Category updated successfully.', 'success');
            } else {
                set_flash('Failed to update category.', 'danger');
            }
        } else {
            if (create_category($data)) {
                set_flash('Category created successfully.', 'success');
            } else {
                set_flash('Failed to create category.', 'danger');
            }
        }
        redirect('/admin/categories.php');
    }
}

// Handle delete
if ($deleteId && verify_csrf($_GET['token'] ?? '')) {
    if (delete_category($deleteId)) {
        set_flash('Category deleted successfully.', 'success');
    } else {
        set_flash('Failed to delete category.', 'danger');
    }
    redirect('/admin/categories.php');
}

// Get data
$categories = get_categories();
$editingCategory = $editingId ? get_category($editingId) : null;

// Bootstrap Icons list for dropdown
$icons = ['bi-folder', 'bi-heart-pulse', 'bi-newspaper', 'bi-capsule', 'bi-people', 'bi-emoji-smile', 'bi-hospital', 'bi-calendar-event', 'bi-megaphone', 'bi-lightbulb', 'bi-shield-check', 'bi-award', 'bi-book', 'bi-briefcase', 'bi-chat-dots', 'bi-gear', 'bi-graph-up', 'bi-house', 'bi-star'];

include __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-folder me-2"></i>Categories
    </h1>
    <?php if (!$editingCategory): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    <?php endif; ?>
</div>

<?php if ($flash = get_flash()): ?>
    <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show">
        <?= e($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Categories Table -->
<div class="card card-shadow">
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <p class="text-muted text-center py-4 mb-0">No categories yet. Create your first category!</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Order</th>
                            <th>Category</th>
                            <th>Filipino Name</th>
                            <th class="text-center">Posts</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $cat['sort_order']; ?></td>
                                <td>
                                    <i class="<?= e($cat['icon']); ?> me-2" style="color: <?= e($cat['color']); ?>"></i>
                                    <span class="badge me-2" style="background-color: <?= e($cat['color']); ?>">&nbsp;</span>
                                    <strong><?= e($cat['name']); ?></strong>
                                    <?php if ($cat['description']): ?>
                                        <br><small class="text-muted"><?= e(excerpt($cat['description'], 50)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($cat['name_ph'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $cat['post_count']; ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editCategory(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?= urlencode($cat['id']); ?>&token=<?= csrf_token(); ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this category? Posts will be uncategorized.');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="category_id" id="categoryId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name (English) *</label>
                            <input type="text" class="form-control" name="name" id="catName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (Filipino)</label>
                            <input type="text" class="form-control" name="name_ph" id="catNamePh">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="color" id="catColor" value="#2563EB">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Icon</label>
                            <select class="form-select" name="icon" id="catIcon">
                                <?php foreach ($icons as $icon): ?>
                                    <option value="<?= $icon; ?>"><?= $icon; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (English)</label>
                            <textarea class="form-control" name="description" id="catDesc" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Filipino)</label>
                            <textarea class="form-control" name="description_ph" id="catDescPh" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="catOrder" value="0" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(cat) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('catName').value = cat.name || '';
    document.getElementById('catNamePh').value = cat.name_ph || '';
    document.getElementById('catColor').value = cat.color || '#2563EB';
    document.getElementById('catIcon').value = cat.icon || 'bi-folder';
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('catDescPh').value = cat.description_ph || '';
    document.getElementById('catOrder').value = cat.sort_order || 0;
    
    // Update form action to include edit parameter
    const form = document.querySelector('#categoryModal form');
    form.action = '?edit=' + encodeURIComponent(cat.id);
    
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

// Reset modal on close
document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.querySelector('#categoryModal form').reset();
    document.querySelector('#categoryModal form').action = '';
    document.getElementById('categoryId').value = '';
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>

