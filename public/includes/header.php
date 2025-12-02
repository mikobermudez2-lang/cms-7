<?php
require_once __DIR__ . '/../../includes/init.php';

$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? 'Healthcare Center';
$metaDescription = $metaDescription ?? 'Healthcare Center - Your trusted partner in health. Providing world-class care and medical services.';
$metaKeywords = $metaKeywords ?? 'healthcare, hospital, medical, health tips, wellness';
$ogImage = $ogImage ?? url('/public/poster.jpg');
$lang = get_language();
?>
<!doctype html>
<html lang="<?= $lang === 'ph' ? 'fil' : 'en'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- SEO Meta Tags -->
    <title><?= e($pageTitle); ?> — <?= __('site_name'); ?></title>
    <meta name="description" content="<?= e($metaDescription); ?>">
    <meta name="keywords" content="<?= e($metaKeywords); ?>">
    <meta name="author" content="Healthcare Center">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <meta property="og:title" content="<?= e($pageTitle); ?> — <?= __('site_name'); ?>">
    <meta property="og:description" content="<?= e($metaDescription); ?>">
    <meta property="og:image" content="<?= e($ogImage); ?>">
    <meta property="og:locale" content="<?= $lang === 'ph' ? 'fil_PH' : 'en_US'; ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle); ?>">
    <meta name="twitter:description" content="<?= e($metaDescription); ?>">
    <meta name="twitter:image" content="<?= e($ogImage); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= url('/favicon.ico'); ?>">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= asset('css/styles.css'); ?>">
    
    <style>
        /* Bootstrap Primary Color Override */
        :root {
            --bs-primary: #2563EB;
            --bs-primary-rgb: 37, 99, 235;
            --hc-navy: #1E3A8A;
            --hc-sky: #93C5FD;
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
        
        /* Mobile Navigation Improvements */
        @media (max-width: 991.98px) {
            .navbar {
                padding: 0.5rem 0;
            }
            
            .navbar-nav {
                padding: 1rem 0;
                margin-top: 0.5rem;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(0,0,0,0.05);
                font-size: 1rem;
            }
            
            .navbar-nav .nav-link:last-child {
                border-bottom: none;
            }
            
            .navbar-nav .nav-link.active {
                background: rgba(37, 99, 235, 0.1);
                color: #2563EB;
            }
            
            .search-form-mobile {
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 0.5rem;
                margin-top: 0.5rem;
            }
            
            .search-form-mobile .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .lang-switcher-mobile {
                display: flex;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
            }
            
            .lang-switcher-mobile .btn {
                flex: 1;
                max-width: none;
            }
        }
        
        /* Search Box */
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-right: 2.5rem;
            border-radius: 2rem;
        }
        .search-box .search-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #6c757d;
        }
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1050;
            display: none;
        }
        .search-suggestions.show {
            display: block;
        }
        .search-suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .search-suggestion-item:last-child {
            border-bottom: none;
        }
        .search-suggestion-item:hover {
            background: #f8f9fa;
        }
        
        /* Language Switcher */
        .lang-switcher .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .lang-switcher .btn.active {
            background: var(--hc-navy);
            color: white;
        }
        
        /* Hero Section Styles */
        .hero-section {
            background: linear-gradient(135deg, #2563EB 0%, #1E3A8A 100%);
            color: white;
            padding: 4rem 0;
            min-height: 60vh;
            display: flex;
            align-items: center;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            line-height: 1.2;
        }
        
        .hero-lead {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
        }
        
        .hero-text {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }
        
        .hero-image-wrapper {
            text-align: center;
        }
        
        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .hero-cta .btn {
            font-size: 1.1rem;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
        }
        
        /* Post Cards */
        .post-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .post-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #1E3A8A;
        }
        
        .post-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .post-link {
            color: #2563EB;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .post-link:hover {
            color: #1E3A8A;
        }
        
        /* Mobile Optimizations */
        @media (max-width: 991.98px) {
            .hero-section {
                padding: 3rem 0;
                min-height: auto;
            }
            
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-lead {
                font-size: 1.1rem;
            }
            
            .hero-text {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 767.98px) {
            h1 { font-size: 1.75rem !important; }
            h2 { font-size: 1.5rem !important; }
            h3 { font-size: 1.25rem !important; }
            
            .hero-section {
                padding: 2rem 0 !important;
                text-align: center;
            }
            
            .hero-title {
                font-size: 1.75rem !important;
                margin-bottom: 1rem !important;
            }
            
            .hero-lead {
                font-size: 1rem !important;
                margin-bottom: 0.75rem !important;
            }
            
            .hero-text {
                font-size: 0.95rem !important;
                margin-bottom: 1.5rem !important;
            }
            
            .hero-cta .btn {
                font-size: 1rem !important;
                padding: 0.625rem 1.5rem !important;
                width: 100%;
                max-width: 300px;
            }
            
            .hero-image {
                margin-top: 2rem;
                max-width: 90%;
            }
            
            .card {
                margin-bottom: 1rem;
                border-radius: 0.5rem;
            }
            
            .card-body {
                padding: 1rem !important;
            }
            
            .btn {
                padding: 0.625rem 1rem;
                font-size: 0.95rem;
                width: 100%;
                max-width: 100%;
            }
            
            .btn-lg {
                padding: 0.75rem 1.5rem;
                font-size: 1rem;
            }
            
            section.py-5 {
                padding: 2rem 0 !important;
            }
            
            .container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .row {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }
            
            .row > * {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .post-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .post-title {
                font-size: 1.25rem;
            }
            
            .post-excerpt {
                font-size: 0.9rem;
            }
            
            /* Navbar mobile */
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            /* Tables */
            table {
                font-size: 0.85rem;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Forms */
            .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 0.75rem;
            }
            
            /* Images */
            img {
                max-width: 100%;
                height: auto;
            }
            
            /* Spacing adjustments */
            .mb-5 {
                margin-bottom: 2rem !important;
            }
            
            .mb-4 {
                margin-bottom: 1.5rem !important;
            }
            
            .mt-5 {
                margin-top: 2rem !important;
            }
            
            .mt-4 {
                margin-top: 1.5rem !important;
            }
            
            /* Text alignment */
            .text-center-mobile {
                text-align: center;
            }
        }
        
        @media (max-width: 575.98px) {
            .hero-title {
                font-size: 1.5rem !important;
            }
            
            .hero-lead {
                font-size: 0.95rem !important;
            }
            
            .container {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
            
            .btn {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
        }
        
        /* Touch-friendly elements */
        @media (pointer: coarse) {
            .nav-link, .btn, .list-group-item-action {
                min-height: 44px;
                display: flex;
                align-items: center;
            }
        }
        
        /* Sidebar Card Spacing Fix */
        .card-header {
            padding-bottom: 0.5rem !important;
            margin-bottom: 0 !important;
        }
        
        /* Fix pt-0 class to actually remove top padding */
        .card-body.pt-0 {
            padding-top: 0 !important;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        /* Reduce spacing in sidebar cards */
        .card .list-group-flush {
            margin-top: 0;
        }
        
        .card .list-group-item:first-child {
            border-top: none;
            padding-top: 0.5rem;
        }
        
        .card .form-control {
            margin-top: 0;
        }
        
        /* Ensure list group items have proper spacing */
        .card .list-group-item {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        /* Card Body - Prevent Content Overlap */
        .card-body {
            overflow: visible !important;
            position: relative;
        }
        
        /* Default card body padding - but allow pt-0 to override */
        .card-body:not(.pt-0) {
            padding: 1.5rem;
        }
        
        /* Blog Post Content Styling - Prevent Overlap */
        .announcement-content {
            line-height: 1.8;
            color: #2D3436 !important;
            font-size: 1.05rem;
            overflow-wrap: break-word;
            word-wrap: break-word;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 1;
        }
        
        /* Reset all margins and padding that might cause overlap */
        .announcement-content > * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .announcement-content > *:first-child {
            margin-top: 0 !important;
        }
        
        .announcement-content > *:last-child {
            margin-bottom: 0 !important;
        }
        
        .announcement-content p {
            margin-bottom: 1.25rem;
            margin-top: 0;
            margin-left: 0;
            margin-right: 0;
            line-height: 1.8;
            clear: both;
            max-width: 100%;
            overflow-wrap: break-word;
        }
        
        .announcement-content p:first-of-type {
            margin-top: 0;
        }
        
        .announcement-content h1,
        .announcement-content h2,
        .announcement-content h3,
        .announcement-content h4,
        .announcement-content h5,
        .announcement-content h6 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            margin-left: 0;
            margin-right: 0;
            font-weight: 700;
            color: #1E3A8A;
            line-height: 1.3;
            clear: both;
            page-break-after: avoid;
            max-width: 100%;
        }
        
        .announcement-content h1:first-child,
        .announcement-content h2:first-child,
        .announcement-content h3:first-child,
        .announcement-content h4:first-child,
        .announcement-content h5:first-child,
        .announcement-content h6:first-child {
            margin-top: 0;
        }
        
        .announcement-content h1 { font-size: 2rem; }
        .announcement-content h2 { font-size: 1.75rem; }
        .announcement-content h3 { font-size: 1.5rem; }
        .announcement-content h4 { font-size: 1.25rem; }
        .announcement-content h5 { font-size: 1.1rem; }
        .announcement-content h6 { font-size: 1rem; }
        
        .announcement-content img {
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
            border-radius: 0.5rem;
            margin: 2rem auto !important;
            display: block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            clear: both;
            box-sizing: border-box;
        }
        
        .announcement-content figure {
            margin: 2rem 0;
            margin-left: 0;
            margin-right: 0;
            clear: both;
            display: block;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .announcement-content figure img {
            margin: 0 auto !important;
            display: block;
            width: 100%;
            max-width: 100%;
        }
        
        .announcement-content figcaption {
            margin-top: 0.5rem;
            margin-left: 0;
            margin-right: 0;
            font-size: 0.9rem;
            color: #6c757d;
            font-style: italic;
            text-align: center;
            display: block;
            clear: both;
        }
        
        .announcement-content div {
            clear: both;
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .announcement-content ul,
        .announcement-content ol {
            margin-bottom: 1.25rem;
            margin-top: 1rem;
            margin-left: 2rem;
            margin-right: 0;
            padding-left: 0;
            clear: both;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .announcement-content li {
            margin-bottom: 0.5rem;
            line-height: 1.8;
            max-width: 100%;
            overflow-wrap: break-word;
        }
        
        .announcement-content blockquote {
            border-left: 4px solid #2563EB;
            padding-left: 1.5rem;
            padding-right: 1rem;
            margin: 2rem 0;
            margin-left: 0;
            margin-right: 0;
            font-style: italic;
            color: #555;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.25rem;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .announcement-content a {
            color: #2563EB !important;
            text-decoration: underline;
            word-break: break-word;
        }
        
        .announcement-content a:hover {
            color: #1E3A8A !important;
        }
        
        .announcement-content table {
            width: 100% !important;
            max-width: 100%;
            margin: 2rem 0;
            margin-left: 0;
            margin-right: 0;
            border-collapse: collapse;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            overflow: hidden;
            clear: both;
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
        }
        
        .announcement-content table th,
        .announcement-content table td {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        
        .announcement-content hr {
            margin: 2rem 0;
            margin-left: 0;
            margin-right: 0;
            border: none;
            border-top: 2px solid #e0e0e0;
            width: 100%;
            max-width: 100%;
        }
        
        .announcement-content strong,
        .announcement-content b {
            font-weight: 700;
            color: #1E3A8A !important;
        }
        
        .announcement-content em,
        .announcement-content i {
            font-style: italic;
            color: #2D3436 !important;
        }
        
        .announcement-content code {
            background: #f4f4f4 !important;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #d63384 !important;
            word-break: break-word;
            max-width: 100%;
        }
        
        .announcement-content pre {
            background: #f4f4f4 !important;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1.5rem 0;
            margin-left: 0;
            margin-right: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        
        .announcement-content pre code {
            background: none !important;
            padding: 0;
            color: #2D3436 !important;
        }
        
        /* Override any problematic inline styles */
        .announcement-content * {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .announcement-content *[style*="width"] {
            max-width: 100% !important;
        }
        
        .announcement-content *[style*="margin-left"],
        .announcement-content *[style*="margin-right"] {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        
        /* Mobile adjustments */
        @media (max-width: 767.98px) {
            .card-body {
                padding: 1rem !important;
            }
            
            .announcement-content {
                font-size: 1rem;
            }
            
            .announcement-content h1 { font-size: 1.75rem; }
            .announcement-content h2 { font-size: 1.5rem; }
            .announcement-content h3 { font-size: 1.25rem; }
            
            .announcement-content img {
                margin: 1.5rem 0 !important;
            }
            
            .announcement-content figure {
                margin: 1.5rem 0;
            }
            
            .announcement-content table {
                font-size: 0.9rem;
                display: block;
                width: 100%;
                overflow-x: auto;
            }
            
            .announcement-content table th,
            .announcement-content table td {
                padding: 0.5rem;
                min-width: 100px;
            }
        }
    </style>
</head>
<body data-app-root="..">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= url('/public/index.php'); ?>">
                <i class="bi bi-heart-pulse text-primary me-2"></i><?= __('site_name'); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' ? 'active fw-semibold' : ''; ?>" href="<?= url('/public/index.php'); ?>"><?= __('home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'about' ? 'active fw-semibold' : ''; ?>" href="<?= url('/public/about.php'); ?>"><?= __('about'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'blog' ? 'active fw-semibold' : ''; ?>" href="<?= url('/public/blog.php'); ?>"><?= __('blog'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'careers' ? 'active fw-semibold' : ''; ?>" href="<?= url('/public/careers.php'); ?>"><?= __('careers'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'contact' ? 'active fw-semibold' : ''; ?>" href="<?= url('/public/contact.php'); ?>"><?= __('contact'); ?></a>
                    </li>
                </ul>
                
                <!-- Search Box (Desktop) -->
                <form class="d-none d-lg-flex search-box me-3" action="<?= url('/public/search.php'); ?>" method="get">
                    <input type="search" class="form-control form-control-sm" name="q" placeholder="<?= __('search_placeholder'); ?>" autocomplete="off" id="searchInput">
                    <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </form>
                
                <!-- Language Switcher -->
                <div class="lang-switcher d-none d-lg-flex me-3">
                    <a href="<?= e(get_language_switch_url('en')); ?>" class="btn btn-sm btn-outline-secondary <?= $lang === 'en' ? 'active' : ''; ?>">EN</a>
                    <a href="<?= e(get_language_switch_url('ph')); ?>" class="btn btn-sm btn-outline-secondary <?= $lang === 'ph' ? 'active' : ''; ?>">PH</a>
                </div>
                
                <!-- Admin Login Button (Desktop) -->
                <a href="<?= url('/admin/login.php'); ?>" class="btn btn-outline-primary btn-sm d-none d-lg-inline-flex align-items-center">
                    <i class="bi bi-person-circle me-1"></i> <?= __('login'); ?>
                </a>
                
                <!-- Mobile Search, Language & Login -->
                <div class="d-lg-none">
                    <form class="search-form-mobile" action="<?= url('/public/search.php'); ?>" method="get">
                        <div class="input-group">
                            <input type="search" class="form-control" name="q" placeholder="<?= __('search_placeholder'); ?>">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="lang-switcher-mobile mb-0 p-0">
                            <a href="<?= e(get_language_switch_url('en')); ?>" class="btn btn-sm <?= $lang === 'en' ? 'btn-primary' : 'btn-outline-secondary'; ?>">EN</a>
                            <a href="<?= e(get_language_switch_url('ph')); ?>" class="btn btn-sm <?= $lang === 'ph' ? 'btn-primary' : 'btn-outline-secondary'; ?>">PH</a>
                        </div>
                        <a href="<?= url('/admin/login.php'); ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person-circle me-1"></i> <?= __('login'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
    // Search suggestions
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const suggestions = document.getElementById('searchSuggestions');
        
        if (searchInput && suggestions) {
            let debounceTimer;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    suggestions.classList.remove('show');
                    return;
                }
                
                debounceTimer = setTimeout(async () => {
                    try {
                        const response = await fetch('<?= url("/api/suggestions.php"); ?>?q=' + encodeURIComponent(query));
                        const data = await response.json();
                        
                        if (data.success && data.data.length > 0) {
                            suggestions.innerHTML = data.data.map(item => 
                                `<div class="search-suggestion-item" data-value="${item}">${item}</div>`
                            ).join('');
                            suggestions.classList.add('show');
                        } else {
                            suggestions.classList.remove('show');
                        }
                    } catch (e) {
                        suggestions.classList.remove('show');
                    }
                }, 300);
            });
            
            suggestions.addEventListener('click', function(e) {
                if (e.target.classList.contains('search-suggestion-item')) {
                    searchInput.value = e.target.dataset.value;
                    suggestions.classList.remove('show');
                    searchInput.closest('form').submit();
                }
            });
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-box')) {
                    suggestions.classList.remove('show');
                }
            });
        }
    });
    </script>
