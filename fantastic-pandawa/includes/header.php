<?php
// Include base URL configuration
if (!defined('BASE_URL')) {
    // Auto-detect base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get script path and find project root
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path_parts = explode('/', dirname($script_name));
    
    // Find project folder automatically
    $project_folder = '';
    $current_path = '';
    
    // Build path by checking each directory level
    foreach ($path_parts as $part) {
        if (!empty($part) && $part !== '.' && $part !== '..') {
            $current_path .= '/' . $part;
            // Check if this looks like a project root by looking for common files
            $check_path = $_SERVER['DOCUMENT_ROOT'] . $current_path;
            if (file_exists($check_path . '/index.php') || 
                file_exists($check_path . '/config') || 
                file_exists($check_path . '/includes')) {
                $project_folder = $current_path;
                // Once we found the project root, break the loop
                break;
            }
        }
    }
    
    define('BASE_URL', $protocol . '://' . $host . $project_folder);
}

// Helper function for URLs
if (!function_exists('url')) {
    function url($path = '') {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

// Helper function for assets
if (!function_exists('asset')) {
    function asset($path = '') {
        return BASE_URL . '/assets/' . ltrim($path, '/');
    }
}

// Dapatkan pengaturan jika belum didapat
if (!isset($settings)) {
    $settings = getSettings();
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
$is_user_area = strpos($_SERVER['PHP_SELF'], '/user/') !== false;

$site_name = $settings['site_name'] ?? 'Fantastic Pandawa';
$site_description = $settings['site_description'] ?? 'Jasa Print & Fotokopi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' . $site_name : $site_name ?></title>
    <meta name="description" content="<?= isset($page_description) ? $page_description : $site_description ?>">
    <meta name="keywords" content="print, cetak, fotokopi, brosur, kartu nama, undangan, banner, stiker">
    <meta name="author" content="<?= $site_name ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('images/favicon.ico') ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Base Style CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    
    <!-- Custom CSS untuk header konsisten -->
    <style>
    :root {
        --primary-color: #3b82f6;
        --primary-dark: #1e40af;
        --secondary-color: #6b7280;
        --dark-color: #1f2937;
    }
    
    /* Styling khusus untuk navbar brand */
    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
    }
    
    .navbar-brand .text-primary {
        color: var(--primary-color) !important;
    }
    
    /* Navbar styling konsisten */
    .navbar {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        background-color: #fff !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .navbar-brand {
            font-size: 1.25rem;
        }
    }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <!-- Brand - Selalu Teks -->
            <a class="navbar-brand" href="<?= url() ?>">
                <span class="fw-bold text-primary"><?= $site_name ?></span>
            </a>
            
            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="<?= url() ?>">Beranda</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= (strpos($current_page, 'print.php') !== false || strpos($current_page, 'cetak.php') !== false) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                            Layanan
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('services/print.php') ?>">
                                <i class="fas fa-print me-2"></i>Print Dokumen
                            </a></li>
                            <li><a class="dropdown-item" href="<?= url('services/cetak.php') ?>">
                                <i class="fas fa-copy me-2"></i>Cetak Custom
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('services/pricing.php') ?>">
                                <i class="fas fa-dollar-sign me-2"></i>Daftar Harga
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'about.php' ? 'active' : '' ?>" href="<?= url('about.php') ?>">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Kontak</a>
                    </li>
                    
                    <?php if ($is_user_area): ?>
                    <!-- User Area Menu Items (only visible in mobile view) -->
                    <li class="nav-item d-block d-lg-none">
                        <a class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= url('user/dashboard.php') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item d-block d-lg-none">
                        <a class="nav-link <?= $current_page == 'orders.php' ? 'active' : '' ?>" href="<?= url('user/orders.php') ?>">
                            <i class="fas fa-list me-2"></i>Pesanan Saya
                        </a>
                    </li>
                    <li class="nav-item d-block d-lg-none">
                        <a class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>" href="<?= url('user/profile.php') ?>">
                            <i class="fas fa-user-edit me-2"></i>Profil
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Auth Menu -->
                <ul class="navbar-nav align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i>
                                <?= $_SESSION['user_name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>" href="<?= url('user/dashboard.php') ?>">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                                <li><a class="dropdown-item <?= $current_page == 'orders.php' ? 'active' : '' ?>" href="<?= url('user/orders.php') ?>">
                                    <i class="fas fa-list me-2"></i>Pesanan Saya
                                </a></li>
                                <li><a class="dropdown-item <?= $current_page == 'profile.php' ? 'active' : '' ?>" href="<?= url('user/profile.php') ?>">
                                    <i class="fas fa-user-edit me-2"></i>Profil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url('auth/logout.php') ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i>Keluar
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <li class="nav-item">
                            <a class="nav-link navbar-login-link" href="<?= url('auth/login.php') ?>">Masuk</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary navbar-register-btn" href="<?= url('auth/register.php') ?>">
                                <i class="fas fa-user-plus me-1"></i>Daftar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning_message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['warning_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info_message'])): ?>
            <div class="container mt-3">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= $_SESSION['info_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php unset($_SESSION['info_message']); ?>
        <?php endif; ?>

        <!-- Content will be inserted here by individual pages -->