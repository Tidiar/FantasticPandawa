<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($page_title) ? $page_title : 'Panel Admin' ?> | Fantastic Pandawa</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Preloader -->
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__shake" src="https://via.placeholder.com/60" alt="Fantastic Pandawa Logo" height="60" width="60">
    </div>

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link">Dasbor</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../index.php" target="_blank" class="nav-link">Lihat Situs</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">
                    <?php
                    // Jika variabel belum dideklarasikan, tentukan nilai defaultnya
                    if (!isset($pending_print_orders)) $pending_print_orders = 0;
                    if (!isset($pending_cetak_orders)) $pending_cetak_orders = 0;
                    
                    // Dapatkan jumlah pembayaran pending
                    if (!isset($pending_payments)) {
                        $pending_payments = function_exists('getPendingPaymentCount') ? getPendingPaymentCount() : 0;
                    }
                    
                    $total_notifications = $pending_print_orders + $pending_cetak_orders + $pending_payments;
                    echo $total_notifications;
                    ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?= $total_notifications ?> Notifikasi</span>
                    <div class="dropdown-divider"></div>
                    <a href="print-orders.php?status=pending" class="dropdown-item">
                        <i class="fas fa-print mr-2"></i> <?= $pending_print_orders ?> pesanan print tertunda
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="cetak-orders.php?status=pending" class="dropdown-item">
                        <i class="fas fa-copy mr-2"></i> <?= $pending_cetak_orders ?> pesanan cetak tertunda
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="payment-verification.php?status=pending" class="dropdown-item">
                        <i class="fas fa-credit-card mr-2"></i> <?= $pending_payments ?> pembayaran perlu verifikasi
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="payment-verification.php" class="dropdown-item dropdown-footer">Lihat Semua Notifikasi</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->