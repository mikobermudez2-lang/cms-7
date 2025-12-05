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
        /* Bootstrap Primary Color Override */
        :root {
            --bs-primary: #2563EB;
            --bs-primary-rgb: 37, 99, 235;
            --bs-primary-dark: #1E3A8A;
            --bs-primary-light: #93C5FD;
        }
        
        .btn-primary {
            --bs-btn-bg: #2563EB;
            --bs-btn-border-color: #2563EB;
            --bs-btn-hover-bg: #1E3A8A;
            --bs-btn-hover-border-color: #1E3A8A;
            --bs-btn-active-bg: #1E3A8A;
            --bs-btn-active-border-color: #1E3A8A;
        }
        
        .text-primary {
            --bs-text-opacity: 1;
            color: #2563EB !important;
        }
        
        .bg-primary {
            --bs-bg-opacity: 1;
            background-color: #2563EB !important;
        }
        
        .border-primary {
            --bs-border-opacity: 1;
            border-color: #2563EB !important;
        }
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 60px;
        }
        
        body {
            overflow-x: hidden;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: #1E3A8A; /* Deep Navy */
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .admin-sidebar.collapsed .sidebar-brand-text,
        .admin-sidebar.collapsed .nav-link span,
        .admin-sidebar.collapsed .sidebar-footer-text,
        .admin-sidebar.collapsed .sidebar-user-details {
            opacity: 0;
            width: 0;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        .admin-sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px;
            position: relative;
        }
        
        .admin-sidebar.collapsed .nav-link i {
            margin-right: 0;
        }
        
        .admin-sidebar.collapsed .sidebar-brand {
            justify-content: center;
            padding: 20px 12px;
            position: relative;
        }
        
        .admin-sidebar.collapsed .sidebar-brand-content {
            justify-content: center;
        }
        
        .admin-sidebar:not(.collapsed) .sidebar-toggle-btn {
            position: relative;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn {
            width: 100% !important;
            justify-content: center !important;
            padding: 10px !important;
            background: transparent !important;
            gap: 0 !important;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn span {
            opacity: 0;
            width: 0;
            overflow: hidden;
            margin: 0;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.1) !important;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn i {
            color: rgba(255,255,255,0.8) !important;
            margin: 0 !important;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn:hover i {
            color: #fff !important;
        }
        
        .admin-sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 10px !important;
            gap: 0 !important;
        }
        
        .admin-sidebar.collapsed .nav-link i {
            margin: 0 !important;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn i {
            color: #1a1f35;
        }
        
        .admin-sidebar.collapsed .sidebar-footer {
            padding: 20px 12px;
        }
        
        .admin-sidebar.collapsed .sidebar-user-info {
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .admin-sidebar.collapsed .sidebar-user-avatar {
            margin: 0 auto;
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn {
            padding: 12px;
            justify-content: center;
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn span {
            display: none;
        }
        
        /* Tooltips for collapsed state */
        .admin-sidebar.collapsed .nav-link {
            position: relative;
        }
        
        .admin-sidebar.collapsed .nav-link:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background: #1a1f35;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 14px;
            box-shadow: 2px 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            pointer-events: none;
            animation: tooltipFadeIn 0.2s ease;
        }
        
        .admin-sidebar.collapsed .nav-link:hover::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 6px;
            border: 6px solid transparent;
            border-right-color: #1a1f35;
            z-index: 1001;
            pointer-events: none;
        }
        
        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50%) translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(-50%) translateX(0);
            }
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn:hover::after {
            content: 'Logout';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background: #1a1f35;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            white-space: nowrap;
            font-size: 14px;
            box-shadow: 2px 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            pointer-events: none;
            animation: tooltipFadeIn 0.2s ease;
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn:hover::before {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 6px;
            border: 6px solid transparent;
            border-right-color: #1a1f35;
            z-index: 1001;
            pointer-events: none;
        }
        
        .admin-sidebar.collapsed .sidebar-logout-btn {
            position: relative;
        }
        
        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: var(--topbar-height);
        }
        
        .sidebar-brand-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-brand-icon {
            font-size: 24px;
            color: #4a9eff;
        }
        
        .sidebar-brand-text {
            font-size: 18px;
            font-weight: 700;
            white-space: nowrap;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-toggle-btn {
            background: transparent !important;
            border: none;
            color: rgba(255,255,255,0.8) !important;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 10px 12px;
            width: 100%;
            text-align: left;
            font-size: 14px;
            gap: 12px;
            margin-bottom: 4px;
        }
        
        .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.1) !important;
            color: #fff !important;
        }
        
        .sidebar-toggle-btn:active,
        .sidebar-toggle-btn:focus {
            background: rgba(255,255,255,0.1) !important;
            outline: none;
            box-shadow: none;
        }
        
        .sidebar-toggle-btn i {
            font-size: 20px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.8);
        }
        
        .sidebar-toggle-btn:hover i {
            color: #fff;
        }
        
        .sidebar-toggle-btn span {
            transition: opacity 0.3s ease;
            color: rgba(255,255,255,0.8);
            flex: 1;
        }
        
        .sidebar-toggle-btn:hover span {
            color: #fff;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle-btn i {
            transform: none;
        }
        
        .sidebar-nav {
            padding: 12px 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-nav .nav-item {
            margin: 2px 12px;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            gap: 12px;
        }
        
        .sidebar-nav .nav-link i {
            font-size: 20px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-nav .nav-link span {
            transition: opacity 0.3s ease;
            flex: 1;
        }
        
        .sidebar-nav .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, #4a9eff 0%, #357abd 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(74, 158, 255, 0.3);
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
            transition: padding 0.3s ease;
        }
        
        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
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
            color: rgba(255,255,255,0.6);
        }
        
        .sidebar-footer-text {
            transition: opacity 0.3s ease;
        }
        
        .sidebar-logout-btn {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .sidebar-logout-btn:hover {
            background: rgba(255,255,255,0.2);
            color: #fff;
            transform: translateX(2px);
        }
        
        .sidebar-logout-btn i {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            background: #f5f7fb;
            min-height: 100vh;
        }
        
        .admin-sidebar.collapsed ~ .admin-main {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .admin-topbar {
            background: #fff;
            height: var(--topbar-height);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .admin-content {
            padding: 24px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block !important;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #1c1f24;
            cursor: pointer;
        }
        
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar.show ~ .sidebar-backdrop,
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Mobile Backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar <?= (isset($_COOKIE['adminSidebarCollapsed']) && $_COOKIE['adminSidebarCollapsed'] === 'true') ? 'collapsed' : ''; ?>" id="adminSidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-content">
                    <i class="bi bi-hospital sidebar-brand-icon"></i>
                    <span class="sidebar-brand-text"><?= $isAdmin ? 'Admin' : 'Staff'; ?></span>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar" type="button" style="width: 100%; margin-bottom: 8px;">
                        <i class="bi bi-list"></i>
                        <span>Toggle Menu</span>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : ''; ?>" href="<?= url('/admin/dashboard.php'); ?>" data-tooltip="Dashboard">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'doctors' ? 'active' : ''; ?>" href="<?= url('/admin/doctors.php'); ?>" data-tooltip="<?= $isAdmin ? 'Doctors' : 'View Doctors'; ?>">
                        <i class="bi bi-person-badge"></i>
                        <span><?= $isAdmin ? 'Doctors' : 'View Doctors'; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'patients' ? 'active' : ''; ?>" href="<?= url('/admin/patients.php'); ?>" data-tooltip="Active Patients">
                        <i class="bi bi-people"></i>
                        <span>Active Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'appointments' ? 'active' : ''; ?>" href="<?= url('/admin/appointments.php'); ?>" data-tooltip="Appointments">
                        <i class="bi bi-calendar-check"></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'records' ? 'active' : ''; ?>" href="<?= url('/admin/records.php'); ?>" data-tooltip="Records">
                        <i class="bi bi-file-medical"></i>
                        <span>Records</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'announcements' ? 'active' : ''; ?>" href="<?= url('/admin/announcements.php'); ?>" data-tooltip="Announcements">
                        <i class="bi bi-megaphone"></i>
                        <span>Announcements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'calendar' ? 'active' : ''; ?>" href="<?= url('/admin/calendar.php'); ?>" data-tooltip="Calendar">
                        <i class="bi bi-calendar3"></i>
                        <span>Calendar</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
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
                <a href="<?= url('/logout.php'); ?>" class="sidebar-logout-btn" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="sidebar-footer-text">Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-topbar">
                <button class="mobile-menu-btn" id="mobileMenuToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0"><?= e($pageTitle); ?></h5>
                <div></div>
            </div>
            <div class="admin-content">
                <div class="container-fluid">

