<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$pageTitle = 'Activity Logs';
$activePage = 'activity';

// Get activity logs
$logs = get_activity_logs(100);

include __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-clock-history me-2"></i>Activity Logs
    </h1>
</div>

<div class="card card-shadow">
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="text-muted text-center py-4 mb-0">No activity logs yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?= e(date('M d, Y H:i:s', strtotime($log['created_at']))); ?></small>
                                </td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <strong><?= e($log['display_name'] ?? $log['username']); ?></strong>
                                        <br><small class="text-muted">@<?= e($log['username']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $actionBadges = [
                                        'login' => 'bg-success',
                                        'logout' => 'bg-secondary',
                                        'create_post' => 'bg-primary',
                                        'update_post' => 'bg-info',
                                        'delete_post' => 'bg-danger',
                                        'create_user' => 'bg-primary',
                                        'update_user' => 'bg-info',
                                        'delete_user' => 'bg-danger',
                                        'upload_media' => 'bg-success',
                                        'delete_media' => 'bg-danger',
                                        'change_password' => 'bg-warning',
                                        'reset_password' => 'bg-warning',
                                    ];
                                    $badgeClass = $actionBadges[$log['action']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass; ?>">
                                        <?= e(str_replace('_', ' ', ucfirst($log['action']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <small class="text-muted"><?= e(ucfirst($log['entity_type'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ($log['description']): ?>
                                        <br><small><?= e($log['description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted font-monospace"><?= e($log['ip_address']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

