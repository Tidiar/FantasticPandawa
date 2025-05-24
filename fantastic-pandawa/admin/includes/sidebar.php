<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link text-center">
        <span class="brand-text font-weight-light">Fantastic Pandawa</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info mx-auto">
                <span class="d-block text-light">
                    <?= isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin' ?>
                </span>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dasbor -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'index.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dasbor</p>
                    </a>
                </li>
                
                <!-- Pastikan variabel sudah didefinisikan -->
                <?php 
                if (!isset($pending_print_orders)) $pending_print_orders = 0;
                if (!isset($pending_cetak_orders)) $pending_cetak_orders = 0;
                if (!isset($pending_payments)) {
                    $pending_payments = function_exists('getPendingPaymentCount') ? getPendingPaymentCount() : 0;
                }
                ?>
                
                <!-- Pesanan Print -->
                <li class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'print-orders') !== false ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'print-orders') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-print"></i>
                        <p>
                            Pesanan Print
                            <i class="right fas fa-angle-left"></i>
                            <?php if ($pending_print_orders > 0): ?>
                                <span class="badge badge-warning right"><?= $pending_print_orders ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="print-orders.php" class="nav-link <?= ($_SERVER['PHP_SELF'] == '/admin/print-orders.php' && !isset($_GET['status'])) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Semua Pesanan</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="print-orders.php?status=pending" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'pending' && strpos($_SERVER['PHP_SELF'], 'print-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Tertunda
                                    <?php if ($pending_print_orders > 0): ?>
                                        <span class="badge badge-warning right"><?= $pending_print_orders ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="print-orders.php?status=processing" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'processing' && strpos($_SERVER['PHP_SELF'], 'print-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Diproses</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="print-orders.php?status=completed" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'completed' && strpos($_SERVER['PHP_SELF'], 'print-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Selesai</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Pesanan Cetak -->
                <li class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'cetak-orders') !== false ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'cetak-orders') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-copy"></i>
                        <p>
                            Pesanan Cetak
                            <i class="right fas fa-angle-left"></i>
                            <?php if ($pending_cetak_orders > 0): ?>
                                <span class="badge badge-warning right"><?= $pending_cetak_orders ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="cetak-orders.php" class="nav-link <?= ($_SERVER['PHP_SELF'] == '/admin/cetak-orders.php' && !isset($_GET['status'])) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Semua Pesanan</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="cetak-orders.php?status=pending" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'pending' && strpos($_SERVER['PHP_SELF'], 'cetak-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Tertunda
                                    <?php if ($pending_cetak_orders > 0): ?>
                                        <span class="badge badge-warning right"><?= $pending_cetak_orders ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="cetak-orders.php?status=processing" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'processing' && strpos($_SERVER['PHP_SELF'], 'cetak-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Diproses</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="cetak-orders.php?status=completed" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'completed' && strpos($_SERVER['PHP_SELF'], 'cetak-orders.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Selesai</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Verifikasi Pembayaran -->
                <li class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'payment-') !== false ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'payment-') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-credit-card"></i>
                        <p>
                            Verifikasi Pembayaran
                            <i class="right fas fa-angle-left"></i>
                            <?php if ($pending_payments > 0): ?>
                                <span class="badge badge-danger right"><?= $pending_payments ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="payment-verification.php" class="nav-link <?= ($_SERVER['PHP_SELF'] == '/admin/payment-verification.php' && !isset($_GET['status'])) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Semua Pembayaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="payment-verification.php?status=pending" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'pending' && strpos($_SERVER['PHP_SELF'], 'payment-verification.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Menunggu Verifikasi
                                    <?php if ($pending_payments > 0): ?>
                                        <span class="badge badge-warning right"><?= $pending_payments ?></span>
                                    <?php endif; ?>
                                </p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="payment-verification.php?status=paid" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'paid' && strpos($_SERVER['PHP_SELF'], 'payment-verification.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Disetujui</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="payment-verification.php?status=failed" class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'failed' && strpos($_SERVER['PHP_SELF'], 'payment-verification.php') !== false) ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ditolak</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Pengguna -->
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'user') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Pengguna</p>
                    </a>
                </li>
                
                <!-- Pengaturan -->
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'settings.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>Pengaturan</p>
                    </a>
                </li>
                
                <!-- Logout - Fixed path -->
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Keluar</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>