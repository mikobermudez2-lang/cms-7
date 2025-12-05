<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (attempt_login($username, $password)) {
        $user = current_user();
        if ($user && $user['role'] === 'doctor') {
            redirect('/doctor/dashboard.php');
        }
        $error = 'Unauthorized access.';
        session_destroy();
    } else {
        $error = 'Invalid credentials.';
    }
}

$existingUser = current_user();
if ($existingUser && $existingUser['role'] === 'doctor') {
    redirect('/doctor/dashboard.php');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Doctor Login — <?= e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/styles.css'); ?>">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="form-section">
                    <h2 class="mb-3 text-center">Doctor Portal</h2>
                    <p class="text-center text-muted mb-4">Authorized physicians only</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" type="submit">Login</button>
                        </div>
                    </form>
                    <p class="text-center text-muted mt-4 mb-0">doctor1 / doctor123</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

