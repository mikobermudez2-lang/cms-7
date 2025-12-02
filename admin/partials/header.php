<?php
require_once __DIR__ . '/../../includes/init.php';
require_admin_or_staff();

$pageTitle = $pageTitle ?? 'Admin';
$activePage = $activePage ?? '';
$user = current_user();
$isAdmin = is_admin();
$isStaff = is_staff();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> — <?= e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/styles.css'); ?>">
    <style>
        /* ===== CSS Variables ===== */
        :root {
            /* Primary Colors */
            --bs-primary: #2563EB;
            --bs-primary-rgb: 37, 99, 235;
            --bs-primary-dark: #1E3A8A;
            --bs-primary-light: #93C5FD;
            
            /* Layout Dimensions */
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 60px;
            
            /* Sidebar Colors */
            --sidebar-bg: #1E3A8A;
            --sidebar-hover-bg: rgba(255, 255, 255, 0.1);
            --sidebar-active-bg: linear-gradient(135deg, #4a9eff 0%, #357abd 100%);
            --sidebar-text: rgba(255, 255, 255, 0.8);
            --sidebar-text-hover: #fff;
            --sidebar-border: rgba(255, 255, 255, 0.1);
            
            /* Transitions */
            --transition-speed: 0.3s;
            --transition-fast: 0.2s;
        }
        
        /* ===== Bootstrap Primary Color Override ===== */
        .btn-primary {
            --bs-btn-bg: var(--bs-primary);
            --bs-btn-border-color: var(--bs-primary);
            --bs-btn-hover-bg: var(--bs-primary-dark);
            --bs-btn-hover-border-color: var(--bs-primary-dark);
            --bs-btn-active-bg: var(--bs-primary-dark);
            --bs-btn-active-border-color: var(--bs-primary-dark);
        }
        
        .text-primary {
            color: var(--bs-primary) !important;
        }
        
        .bg-primary {
            background-color: var(--bs-primary) !important;
        }
        
        .border-primary {
            border-color: var(--bs-primary) !important;
        }
        
        /* ===== Base Layout ===== */
        body {
            overflow-x: hidden;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* ===== Sidebar ===== */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        /* ===== Collapsed State ===== */
        .admin-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .admin-sidebar.collapsed .sidebar-brand-text,
        .admin-sidebar.collapsed .nav-link span,
        .admin-sidebar.collapsed .sidebar-toggle-link span,
        .admin-sidebar.collapsed .sidebar-user-details,
        .admin-sidebar.collapsed .sidebar-footer-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        .admin-sidebar.collapsed .sidebar-brand-content,
        .admin-sidebar.collapsed .nav-link,
        .admin-sidebar.collapsed .sidebar-logout-btn {
            justify-content: center;
            padding-left: 12px;
            padding-right: 12px;
        }
        
        .admin-sidebar.collapsed .sidebar-brand {
            justify-content: center;
            padding: 12px;
            flex-direction: column;
            gap: 8px;
        }
        
        .admin-sidebar.collapsed .sidebar-brand-content {
            justify-content: center;
        }
        
        .admin-sidebar:not(.collapsed) .sidebar-brand {
            flex-direction: row;
        }
        
        .admin-sidebar.collapsed .nav-link,
        .admin-sidebar.collapsed .sidebar-logout-btn {
            gap: 0;
        }
        
        .admin-sidebar.collapsed .nav-link i,
        .admin-sidebar.collapsed .sidebar-logout-btn i {
            margin: 0;
        }
        
        .admin-sidebar.collapsed .sidebar-user-info {
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .admin-sidebar.collapsed .sidebar-user-avatar {
            margin: 0 auto;
        }
        
        /* ===== Sidebar Brand ===== */
        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: var(--topbar-height);
            flex-shrink: 0;
            text-decoration: none;
            color: inherit;
            transition: background-color var(--transition-fast);
            cursor: pointer;
        }
        
        .sidebar-brand:hover {
            background-color: var(--sidebar-hover-bg);
        }
        
        .sidebar-brand-content {
            display: flex;
            align-items: center;
            gap: 12px;
            transition: justify-content var(--transition-speed) ease;
        }
        
        .sidebar-brand-icon {
            font-size: 24px;
            color: #4a9eff;
            flex-shrink: 0;
        }
        
        .sidebar-brand-text {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
            transition: opacity var(--transition-speed) ease, width var(--transition-speed) ease;
        }
        
        /* ===== Sidebar Toggle Link ===== */
        /* Make toggle link identical to nav-link */
        .sidebar-toggle-link {
            /* Inherits all nav-link styles */
        }
        
        .sidebar-toggle-link:hover {
            /* Inherits nav-link hover styles */
        }
        
        /* Prevent navigation when clicking toggle */
        .sidebar-toggle-link {
            cursor: pointer;
        }
        
        /* ===== Sidebar Navigation ===== */
        .sidebar-nav {
            padding: 12px 4px 12px 4px;
            list-style: none;
            margin: 0;
            flex: 1;
            overflow-y: auto;
        }
        
        .sidebar-nav .nav-item {
            margin: 4px 8px 4px 4px;
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav .nav-item:first-child {
            margin-top: 0;
        }
        
        .sidebar-nav .nav-link {
            color: var(--sidebar-text);
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: background-color var(--transition-fast) ease, color var(--transition-fast) ease, transform var(--transition-fast) ease;
            white-space: nowrap;
            gap: 12px;
            position: relative;
        }
        
        .sidebar-nav .nav-link:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-text-hover);
            transform: translateX(2px);
        }
        
        .sidebar-nav .nav-link:focus {
            outline: 2px solid var(--bs-primary-light);
            outline-offset: 2px;
        }
        
        .sidebar-nav .nav-link.active {
            background: var(--sidebar-active-bg);
            color: #fff;
            box-shadow: 0 4px 12px rgba(74, 158, 255, 0.3);
        }
        
        .sidebar-nav .nav-link i {
            font-size: 20px;
            width: 24px;
            min-width: 24px;
            max-width: 24px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: margin-right var(--transition-speed) ease;
        }
        
        .sidebar-nav .nav-link span {
            flex: 1;
            transition: opacity var(--transition-speed) ease, width var(--transition-speed) ease;
        }
        
        /* ===== Tooltips for Collapsed State ===== */
        .admin-sidebar.collapsed .nav-link::after,
        .admin-sidebar.collapsed .sidebar-logout-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #1a1f35;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 14px;
            box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            pointer-events: none;
            opacity: 0;
            transition: opacity var(--transition-fast) ease;
        }
        
        .admin-sidebar.collapsed .nav-link:hover::after,
        .admin-sidebar.collapsed .sidebar-logout-btn:hover::after {
            opacity: 1;
        }
        
        .admin-sidebar.collapsed .nav-link::before,
        .admin-sidebar.collapsed .sidebar-logout-btn::before {
            content: '';
            position: absolute;
            left: calc(100% + 6px);
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #1a1f35;
            z-index: 1001;
            pointer-events: none;
            opacity: 0;
            transition: opacity var(--transition-fast) ease;
        }
        
        .admin-sidebar.collapsed .nav-link:hover::before,
        .admin-sidebar.collapsed .sidebar-logout-btn:hover::before {
            opacity: 1;
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn::after {
            content: 'Logout';
        }
        
        /* ===== Sidebar Footer ===== */
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--sidebar-border);
            background: rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
            transition: padding var(--transition-speed) ease;
        }
        
        .admin-sidebar.collapsed .sidebar-footer {
            padding: 20px 12px;
        }
        
        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            transition: justify-content var(--transition-speed) ease;
        }
        
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a9eff 0%, #357abd 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .sidebar-user-details {
            flex: 1;
            min-width: 0;
            transition: opacity var(--transition-speed) ease, width var(--transition-speed) ease;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .sidebar-logout-btn {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background-color var(--transition-fast) ease, transform var(--transition-fast) ease;
            white-space: nowrap;
            font-size: 14px;
            position: relative;
        }
        
        .sidebar-logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateX(2px);
        }
        
        .sidebar-logout-btn:focus {
            outline: 2px solid var(--bs-primary-light);
            outline-offset: 2px;
        }
        
        .sidebar-logout-btn i {
            font-size: 20px;
            width: 24px;
            min-width: 24px;
            flex-shrink: 0;
            transition: margin-right var(--transition-speed) ease;
        }
        
        .sidebar-logout-btn .sidebar-footer-text {
            flex: 1;
            transition: opacity var(--transition-speed) ease, width var(--transition-speed) ease;
        }
        
        /* ===== Main Content ===== */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
            background: #f5f7fb;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .admin-sidebar.collapsed ~ .admin-main {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .admin-topbar {
            background: #fff;
            height: var(--topbar-height);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-shrink: 0;
        }
        
        .admin-content {
            padding: 24px;
            flex: 1;
        }
        
        /* ===== Mobile Menu Button ===== */
        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #1c1f24;
            cursor: pointer;
            padding: 8px;
            display: none;
        }
        
        .mobile-menu-btn:focus {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }
        
        /* ===== Sidebar Backdrop ===== */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }
        
        .sidebar-backdrop.show {
            display: block;
            opacity: 1;
        }
        
        /* ===== Mobile Responsive ===== */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform var(--transition-speed) ease;
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            /* Disable tooltips on mobile */
            .admin-sidebar.collapsed .nav-link::after,
            .admin-sidebar.collapsed .nav-link::before,
            .admin-sidebar.collapsed .sidebar-logout-btn::after,
            .admin-sidebar.collapsed .sidebar-logout-btn::before {
                display: none;
            }
        }
        
        /* ===== Scrollbar Styling ===== */
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar <?= (isset($_COOKIE['adminSidebarCollapsed']) && $_COOKIE['adminSidebarCollapsed'] === 'true') ? 'collapsed' : ''; ?>" 
               id="adminSidebar" 
               role="navigation" 
               aria-label="Admin Navigation">
            <div class="sidebar-brand">
                <div class="sidebar-brand-content">
                    <i class="bi bi-hospital sidebar-brand-icon" aria-hidden="true"></i>
                    <span class="sidebar-brand-text"><?= $isAdmin ? 'Admin' : 'Staff'; ?></span>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="#" 
                       class="nav-link sidebar-toggle-link" 
                       id="sidebarToggle" 
                       role="button"
                       aria-label="Toggle Sidebar"
                       aria-expanded="<?= (isset($_COOKIE['adminSidebarCollapsed']) && $_COOKIE['adminSidebarCollapsed'] === 'true') ? 'false' : 'true'; ?>"
                       data-tooltip="Toggle Menu">
                        <i class="bi bi-list" aria-hidden="true"></i>
                        <span>Toggle Menu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/dashboard.php'); ?>" 
                       data-tooltip="Dashboard"
                       aria-label="Dashboard"
                       <?= $activePage === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-speedometer2" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'posts' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/posts.php'); ?>" 
                       data-tooltip="Posts"
                       aria-label="Posts"
                       <?= $activePage === 'posts' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        <span>Posts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'jobs' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/jobs.php'); ?>" 
                       data-tooltip="Jobs"
                       aria-label="Jobs"
                       <?= $activePage === 'jobs' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-briefcase" aria-hidden="true"></i>
                        <span>Jobs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'categories' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/categories.php'); ?>" 
                       data-tooltip="Categories"
                       aria-label="Categories"
                       <?= $activePage === 'categories' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-folder" aria-hidden="true"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'users' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/users.php'); ?>" 
                       data-tooltip="Users"
                       aria-label="User Management"
                       <?= $activePage === 'users' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'activity' ? 'active' : ''; ?>" 
                       href="<?= url('/admin/activity.php'); ?>" 
                       data-tooltip="Activity"
                       aria-label="Activity Logs"
                       <?= $activePage === 'activity' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        <span>Activity</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" 
                       href="<?= url('/public/index.php'); ?>" 
                       target="_blank"
                       data-tooltip="View Public Site"
                       aria-label="View Public Site">
                        <i class="bi bi-globe" aria-hidden="true"></i>
                        <span>View Site</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar" aria-hidden="true">
                        <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="sidebar-user-details sidebar-footer-text">
                        <div class="sidebar-user-name"><?= e($user['username'] ?? ''); ?></div>
                        <div class="sidebar-user-role">
                            <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-secondary'; ?>">
                                <?= $isAdmin ? 'Admin' : 'Staff'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="<?= url('/logout.php'); ?>" 
                   class="sidebar-logout-btn" 
                   data-tooltip="Logout"
                   aria-label="Logout">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span class="sidebar-footer-text">Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-topbar">
                <button class="mobile-menu-btn" 
                        id="mobileMenuToggle"
                        aria-label="Toggle Mobile Menu"
                        aria-controls="adminSidebar">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <h5 class="mb-0"><?= e($pageTitle); ?></h5>
                <div></div>
            </div>
            <div class="admin-content">
                <div class="container-fluid">