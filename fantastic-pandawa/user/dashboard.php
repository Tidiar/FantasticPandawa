<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
requireLogin('../auth/login.php');

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Dashboard";
$page_description = "Dashboard pengguna untuk mengelola pesanan print dan cetak";

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Dapatkan statistik user
try {
    // Total pesanan print
    $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_print_orders = $stmt->fetchColumn();
    
    // Total pesanan cetak
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cetak_orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_cetak_orders = $stmt->fetchColumn();
    
    // Pesanan tertunda (print)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE user_id = ? AND status IN ('pending', 'confirmed', 'processing')");
    $stmt->execute([$user_id]);
    $pending_print_orders = $stmt->fetchColumn();
    
    // Pesanan tertunda (cetak)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cetak_orders WHERE user_id = ? AND status IN ('pending', 'confirmed', 'processing')");
    $stmt->execute([$user_id]);
    $pending_cetak_orders = $stmt->fetchColumn();
    
    // Total pengeluaran
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COALESCE(SUM(price), 0) FROM print_orders WHERE user_id = ? AND status = 'completed') +
            (SELECT COALESCE(SUM(price), 0) FROM cetak_orders WHERE user_id = ? AND status = 'completed') as total_spent
    ");
    $stmt->execute([$user_id, $user_id]);
    $total_spent = $stmt->fetchColumn();
    
    // Pesanan terbaru (gabungan print dan cetak)
    $recent_orders = [];
    
    // Ambil pesanan print terbaru
    $stmt = $conn->prepare("SELECT 'print' as type, order_number, created_at, status, price FROM print_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $print_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil pesanan cetak terbaru
    $stmt = $conn->prepare("SELECT 'cetak' as type, order_number, created_at, status, price FROM cetak_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$user_id]);
    $cetak_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gabungkan dan urutkan
    $recent_orders = array_merge($print_orders, $cetak_orders);
    usort($recent_orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recent_orders = array_slice($recent_orders, 0, 5);
    
} catch (PDOException $e) {
    // Jika ada error, set nilai default
    $total_print_orders = 0;
    $total_cetak_orders = 0;
    $pending_print_orders = 0;
    $pending_cetak_orders = 0;
    $total_spent = 0;
    $recent_orders = [];
}

include '../includes/header.php';
?>

<!-- Dashboard Section -->
<section class="dashboard-section py-5">
    <div class="container">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8 col-md-7">
                            <h1 class="welcome-title">Selamat datang, <?= htmlspecialchars($user_name) ?>!</h1>
                            <p class="welcome-subtitle">Kelola pesanan print dan cetak Anda dengan mudah</p>
                        </div>
                        <div class="col-lg-4 col-md-5">
                            <div class="welcome-buttons">
                                <a href="../services/print.php" class="btn btn-light">
                                    <i class="fas fa-print me-2"></i>Print Dokumen
                                </a>
                                <a href="../services/cetak.php" class="btn btn-light">
                                    <i class="fas fa-copy me-2"></i>Cetak Custom
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $total_print_orders ?></h3>
                        <p class="stat-label">Total Print</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-copy"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $total_cetak_orders ?></h3>
                        <p class="stat-label">Total Cetak</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $pending_print_orders + $pending_cetak_orders ?></h3>
                        <p class="stat-label">Dalam Proses</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number">Rp <?= number_format($total_spent, 0, ',', '.') ?></h3>
                        <p class="stat-label">Total Belanja</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions & Recent Orders -->
        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>Aksi Cepat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="../services/print.php" class="btn btn-primary quick-action-btn">
                                <i class="fas fa-print me-2"></i>Print Dokumen Baru
                            </a>
                            <a href="../services/cetak.php" class="btn btn-primary quick-action-btn">
                                <i class="fas fa-copy me-2"></i>Pesan Cetak Custom
                            </a>
                            <a href="orders.php" class="btn btn-primary quick-action-btn">
                                <i class="fas fa-list me-2"></i>Lihat Semua Pesanan
                            </a>
                            <a href="profile.php" class="btn btn-primary quick-action-btn">
                                <i class="fas fa-user-edit me-2"></i>Edit Profil
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-phone me-2"></i>Butuh Bantuan?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="contact-info">
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>">
                                    <?= $settings['contact_phone'] ?? '0822-8243-9997' ?>
                                </a>
                            </p>
                            <p class="mb-2">
                                <i class="fab fa-whatsapp text-success me-2"></i>
                                <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya butuh bantuan" target="_blank">
                                    WhatsApp
                                </a>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-envelope text-info me-2"></i>
                                <a href="mailto:<?= $settings['contact_email'] ?? 'info@fantasticpandawa.com' ?>">
                                    Email Support
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Pesanan Terbaru
                        </h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No. Pesanan</th>
                                            <th>Jenis</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Harga</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td class="fw-bold"><?= $order['order_number'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $order['type'] == 'print' ? 'primary' : 'success' ?>">
                                                        <?= $order['type'] == 'print' ? 'Print' : 'Cetak' ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($order['created_at']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                                        <?= translateStatus($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="fw-bold">Rp <?= number_format($order['price'], 0, ',', '.') ?></td>
                                                <td>
                                                    <a href="order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Belum ada pesanan</h5>
                                <p class="text-muted">Mulai pesanan pertama Anda sekarang!</p>
                                <a href="../services/print.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Buat Pesanan
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.dashboard-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.welcome-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-lg);
}

.welcome-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.welcome-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Welcome Buttons Container */
.welcome-buttons {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    align-items: center;
}

.welcome-buttons .btn {
    font-weight: 600;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    white-space: nowrap;
    min-width: 140px;
    text-align: center;
}

.welcome-buttons .btn-light {
    background: white !important;
    color: var(--primary-color) !important;
    border: 2px solid white !important;
}

.welcome-buttons .btn-light:hover {
    background: #f8f9fa !important;
    color: var(--primary-dark) !important;
    border-color: #f8f9fa !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.stat-icon i {
    font-size: 1.5rem;
    color: white;
}

.stat-content {
    flex-grow: 1;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--dark-color);
}

.stat-label {
    color: var(--secondary-color);
    margin-bottom: 0;
    font-weight: 500;
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: var(--shadow-lg);
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.card-title {
    color: var(--dark-color);
    font-weight: 600;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: var(--dark-color);
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

/* Quick Action Buttons */
.quick-action-btn {
    font-weight: 600;
    padding: 0.875rem 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    border: 2px solid;
    font-size: 0.95rem;
}

.quick-action-btn.btn-primary {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: white !important;
}

.quick-action-btn.btn-primary:hover {
    background: var(--primary-dark) !important;
    border-color: var(--primary-dark) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

.quick-action-btn.btn-outline-primary {
    background: transparent !important;
    border-color: var(--primary-color) !important;
    color: var(--primary-color) !important;
}

.quick-action-btn.btn-outline-primary:hover {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

.quick-action-btn i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

.contact-info a {
    color: var(--secondary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-info a:hover {
    color: var(--primary-color);
}

/* Responsive Design */
@media (max-width: 992px) {
    .welcome-buttons {
        justify-content: center;
        margin-top: 1.5rem;
    }
}

@media (max-width: 768px) {
    .welcome-card {
        text-align: center;
        padding: 1.5rem;
    }
    
    .welcome-title {
        font-size: 1.5rem;
    }
    
    .welcome-buttons {
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .welcome-buttons .btn {
        width: 100%;
        min-width: auto;
    }
    
    .stat-card {
        text-align: center;
        flex-direction: column;
    }
    
    .stat-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .welcome-title {
        font-size: 1.25rem;
    }
    
    .welcome-subtitle {
        font-size: 1rem;
    }
    
    .welcome-buttons .btn {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<?php include '../includes/footer.php'; ?>