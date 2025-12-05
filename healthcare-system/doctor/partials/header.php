<?php
require_once __DIR__ . '/../../includes/init.php';
require_doctor();

$pageTitle = $pageTitle ?? 'Doctor';
$activePage = $activePage ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> — Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/styles.css'); ?>">
    <style>
        /* Bootstrap Primary Color Override */
        :root {
            --bs-primary: #2563EB;
            --bs-primary-rgb: 37, 99, 235;
        }
        
        .btn-primary {
            --bs-btn-bg: #2563EB;
            --bs-btn-border-color: #2563EB;
            --bs-btn-hover-bg: #1E3A8A;
            --bs-btn-hover-border-color: #1E3A8A;
        }
        
        .navbar-dark.bg-primary {
            background-color: #1E3A8A !important; /* Deep Navy for navbar */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= url('/doctor/dashboard.php'); ?>">Doctor Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#doctorNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="doctorNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'dashboard' ? 'active' : ''; ?>" href="<?= url('/doctor/dashboard.php'); ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'appointments' ? 'active' : ''; ?>" href="<?= url('/doctor/appointments.php'); ?>">Appointments</a></li>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'patients' ? 'active' : ''; ?>" href="<?= url('/doctor/patients.php'); ?>">Patients</a></li>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'records' ? 'active' : ''; ?>" href="<?= url('/doctor/records.php'); ?>">Records</a></li>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'calendar' ? 'active' : ''; ?>" href="<?= url('/doctor/calendar.php'); ?>">Calendar</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small"><?= e(current_user()['username'] ?? ''); ?></span>
                    <a class="btn btn-outline-light btn-sm" href="<?= url('/logout.php'); ?>">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4">

