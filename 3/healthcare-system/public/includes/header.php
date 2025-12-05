<?php
require_once __DIR__ . '/../../includes/init.php';

$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Healthcare Center';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= e($pageTitle); ?> — Healthcare Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        
        .text-primary {
            color: #2563EB !important;
        }
    </style>
</head>
<body data-app-root="..">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?= url('/public/index.php'); ?>">Healthcare Center</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' ? 'active' : ''; ?>" href="<?= url('/public/index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'about' ? 'active' : ''; ?>" href="<?= url('/public/about.php'); ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'blog' ? 'active' : ''; ?>" href="<?= url('/public/blog.php'); ?>">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'careers' ? 'active' : ''; ?>" href="<?= url('/public/careers.php'); ?>">Careers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'contact' ? 'active' : ''; ?>" href="<?= url('/public/contact.php'); ?>">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

