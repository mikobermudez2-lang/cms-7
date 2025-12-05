<?php
require_once __DIR__ . '/../includes/init.php';
require_admin(); // Only admins can manage users

$pageTitle = 'User Management';
$editingId = isset($_GET['edit']) ? trim($_GET['edit']) : null;
$deleteId = isset($_GET['delete']) ? trim($_GET['delete']) : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $displayName = trim($_POST['display_name'] ?? '');
        
        if (empty($username) || empty($password)) {
            set_flash('Username and password are required.', 'danger');
        } else {
            $passwordErrors = validate_password($password);
            if (!empty($passwordErrors)) {
                set_flash(implode(' ', $passwordErrors), 'danger');
            } elseif (get_user_by_username($username)) {
                set_flash('Username already exists.', 'danger');
            } else {
                $userId = create_user([
                    'username' => $username,
                    'email' => $email ?: null,
                    'password' => $password,
                    'role' => $role,
                    'display_name' => $displayName ?: $username,
                ]);
                
                if ($userId) {
                    set_flash('User created successfully.', 'success');
                } else {
                    set_flash('Failed to create user.', 'danger');
                }
            }
        }
        redirect('/admin/users.php');
        
    } elseif ($action === 'update') {
        $userId = trim($_POST['user_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'staff';
        $displayName = trim($_POST['display_name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (update_user($userId, [
            'email' => $email ?: null,
            'role' => $role,
            'display_name' => $displayName,
            'is_active' => $isActive,
        ])) {
            set_flash('User updated successfully.', 'success');
        } else {
            set_flash('Failed to update user.', 'danger');
        }
        redirect('/admin/users.php');
        
    } elseif ($action === 'reset_password') {
        $userId = trim($_POST['user_id'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        
        $passwordErrors = validate_password($newPassword);
        if (!empty($passwordErrors)) {
            set_flash(implode(' ', $passwordErrors), 'danger');
        } elseif (reset_password($userId, $newPassword)) {
            set_flash('Password reset successfully.', 'success');
        } else {
            set_flash('Failed to reset password.', 'danger');
        }
        redirect('/admin/users.php');
    }
}

// Handle delete
if ($deleteId && verify_csrf($_GET['token'] ?? '')) {
    $currentUser = current_user();
    if ($deleteId === $currentUser['id']) {
        set_flash('You cannot delete your own account.', 'danger');
    } elseif (delete_user($deleteId)) {
        set_flash('User deleted successfully.', 'success');
    } else {
        set_flash('Failed to delete user.', 'danger');
    }
    redirect('/admin/users.php');
}

// Get users
$users = get_users();
$editingUser = $editingId ? get_user($editingId) : null;
$currentUser = current_user();

include __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-people me-2"></i>User Management
    </h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus me-1"></i> Add User
    </button>
</div>

<?php if ($flash = get_flash()): ?>
    <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show">
        <?= e($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Users Table -->
<div class="card card-shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <div>
                                        <strong><?= e($user['display_name'] ?? $user['username']); ?></strong>
                                        <?php if ($user['id'] === $currentUser['id']): ?>
                                            <span class="badge bg-info ms-1">You</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">@<?= e($user['username']); ?></small>
                                        <?php if ($user['email']): ?>
                                            <br><small class="text-muted"><?= e($user['email']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'editor' ? 'primary' : 'secondary'); ?>">
                                    <?= ucfirst(e($user['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login_at']): ?>
                                    <?= e(date('M d, Y H:i', strtotime($user['last_login_at']))); ?>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M d, Y', strtotime($user['created_at']))); ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editUser(<?= htmlspecialchars(json_encode($user), ENT_QUOTES); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        onclick="resetPassword('<?= e($user['id']); ?>', '<?= e($user['username']); ?>')">
                                    <i class="bi bi-key"></i>
                                </button>
                                <?php if ($user['id'] !== $currentUser['id']): ?>
                                    <a href="?delete=<?= urlencode($user['id']); ?>&token=<?= csrf_token(); ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete user <?= e($user['username']); ?>?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" name="display_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                        <small class="text-muted">Min 8 chars, 1 uppercase, 1 lowercase, 1 number</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="staff">Staff</option>
                            <option value="author">Author</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="editUsername" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" name="display_name" id="editDisplayName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="editEmail">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="editRole">
                            <option value="staff">Staff</option>
                            <option value="author">Author</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="editIsActive" value="1">
                        <label class="form-check-label" for="editIsActive">Active</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token(); ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Reset password for: <strong id="resetUsername"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <small class="text-muted">Min 8 chars, 1 uppercase, 1 lowercase, 1 number</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editDisplayName').value = user.display_name || '';
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editRole').value = user.role;
    document.getElementById('editIsActive').checked = user.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function resetPassword(userId, username) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUsername').textContent = username;
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>

