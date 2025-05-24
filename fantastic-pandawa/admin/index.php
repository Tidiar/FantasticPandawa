<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Coba dapatkan statistik dasbor, dengan penanganan error jika database belum siap
try {
    // Definisikan variabel statistik yang dibutuhkan oleh header.php dan sidebar.php
    $pending_print_orders = getPendingOrderCount('print');
    $pending_cetak_orders = getPendingOrderCount('cetak');
    
    // Dapatkan statistik dasbor yang dibutuhkan
    $stats = [
        'total_print_orders' => getTotalOrderCount('print'),
        'total_cetak_orders' => getTotalOrderCount('cetak'),
        'pending_print_orders' => $pending_print_orders,
        'pending_cetak_orders' => $pending_cetak_orders,
        'completed_print_orders' => getCompletedOrderCount('print'),
        'completed_cetak_orders' => getCompletedOrderCount('cetak'),
        'total_users' => getUserCount(),
        'total_revenue' => getTotalRevenue()
    ];

    // Dapatkan pesanan print terbaru
    $recent_print_orders = getRecentOrders('print', 5);

    // Dapatkan pesanan cetak terbaru
    $recent_cetak_orders = getRecentOrders('cetak', 5);
} catch (Exception $e) {
    // Jika gagal mendapatkan data, buat variabel kosong
    $stats = [
        'total_print_orders' => 0,
        'total_cetak_orders' => 0,
        'pending_print_orders' => 0,
        'pending_cetak_orders' => 0,
        'completed_print_orders' => 0,
        'completed_cetak_orders' => 0,
        'total_users' => 0,
        'total_revenue' => 0
    ];
    $recent_print_orders = [];
    $recent_cetak_orders = [];
    
    // Tetapkan variabel untuk header dan sidebar
    $pending_print_orders = 0;
    $pending_cetak_orders = 0;
}

// Judul halaman
$page_title = "Dasbor - Panel Admin";

// Sertakan header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <!-- Header Konten -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dasbor</h1>
                </div>
                <div class="col-sm-6">
                    <!-- Breadcrumb dihapus untuk tampilan yang lebih bersih -->
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Kartu Statistik -->
            <div class="row">
                <!-- Kartu Pesanan Print -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['total_print_orders'] ?></h3>
                            <p>Total Pesanan Print</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-print"></i>
                        </div>
                        <a href="print-orders.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                
                <!-- Kartu Pesanan Cetak -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['total_cetak_orders'] ?></h3>
                            <p>Total Pesanan Cetak</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-copy"></i>
                        </div>
                        <a href="cetak-orders.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                
                <!-- Kartu Pengguna -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['total_users'] ?></h3>
                            <p>Pengguna Terdaftar</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="users.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                
                <!-- Kartu Pendapatan -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></h3>
                            <p>Total Pendapatan</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <a href="#" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Statistik Baris Kedua -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="far fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pesanan Print Tertunda</span>
                            <span class="info-box-number"><?= $stats['pending_print_orders'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="far fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pesanan Cetak Tertunda</span>
                            <span class="info-box-number"><?= $stats['pending_cetak_orders'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pesanan Print Selesai</span>
                            <span class="info-box-number"><?= $stats['completed_print_orders'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pesanan Cetak Selesai</span>
                            <span class="info-box-number"><?= $stats['completed_cetak_orders'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pesanan Terbaru -->
            <div class="row">
                <!-- Pesanan Print Terbaru -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-print mr-2"></i>Pesanan Print Terbaru
                            </h3>
                            <div class="card-tools">
                                <a href="print-orders.php" class="btn btn-tool" title="Lihat Semua">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_print_orders) > 0): ?>
                                        <?php foreach ($recent_print_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $order['order_number'] ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= formatDate($order['created_at']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>">
                                                        <?= ucfirst(translateStatus($order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="print-orders-detail.php?id=<?= $order['id'] ?>" 
                                                       class="btn btn-xs btn-outline-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                <br>
                                                <span class="text-muted">Tidak ada pesanan print terbaru</span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($recent_print_orders) > 0): ?>
                            <div class="card-footer text-center">
                                <a href="print-orders.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-list mr-1"></i> Lihat Semua Pesanan Print
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pesanan Cetak Terbaru -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-copy mr-2"></i>Pesanan Cetak Terbaru
                            </h3>
                            <div class="card-tools">
                                <a href="cetak-orders.php" class="btn btn-tool" title="Lihat Semua">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_cetak_orders) > 0): ?>
                                        <?php foreach ($recent_cetak_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= $order['order_number'] ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= formatDate($order['created_at']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>">
                                                        <?= ucfirst(translateStatus($order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="cetak-orders-detail.php?id=<?= $order['id'] ?>" 
                                                       class="btn btn-xs btn-outline-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                <br>
                                                <span class="text-muted">Tidak ada pesanan cetak terbaru</span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($recent_cetak_orders) > 0): ?>
                            <div class="card-footer text-center">
                                <a href="cetak-orders.php" class="btn btn-sm btn-success">
                                    <i class="fas fa-list mr-1"></i> Lihat Semua Pesanan Cetak
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tautan Cepat -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-2"></i>Tautan Cepat
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="print-orders.php?status=pending" class="btn btn-block btn-outline-primary">
                                        <i class="fas fa-print mr-2"></i> Pesanan Print Tertunda
                                        <small class="d-block text-muted"><?= $stats['pending_print_orders'] ?> pesanan</small>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="cetak-orders.php?status=pending" class="btn btn-block btn-outline-success">
                                        <i class="fas fa-copy mr-2"></i> Pesanan Cetak Tertunda
                                        <small class="d-block text-muted"><?= $stats['pending_cetak_orders'] ?> pesanan</small>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="print-orders.php?status=completed" class="btn btn-block btn-outline-info">
                                        <i class="fas fa-check-circle mr-2"></i> Pesanan Print Selesai
                                        <small class="d-block text-muted"><?= $stats['completed_print_orders'] ?> pesanan</small>
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <a href="cetak-orders.php?status=completed" class="btn btn-block btn-outline-warning">
                                        <i class="fas fa-check-circle mr-2"></i> Pesanan Cetak Selesai
                                        <small class="d-block text-muted"><?= $stats['completed_cetak_orders'] ?> pesanan</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Custom CSS untuk Dashboard -->
<style>
/* Dashboard enhancements */
.content-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 0;
    padding: 1rem 0;
}

.content-header h1 {
    color: #1e293b;
    font-weight: 600;
}

/* Breadcrumb styling */
.breadcrumb {
    background: transparent;
    margin-bottom: 0;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    color: #64748b;
    content: ">";
}

.breadcrumb-item a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-item a:hover {
    color: #1e40af;
}

.breadcrumb-item.active {
    color: #64748b;
}

/* Small box improvements */
.small-box {
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.small-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.small-box .inner h3 {
    font-weight: 700;
}

/* Info box improvements */
.info-box {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.info-box:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Card improvements */
.card {
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 0.75rem 0.75rem 0 0 !important;
}

.card-title {
    font-weight: 600;
    color: #1e293b;
}

/* Table improvements */
.table thead th {
    border-top: none;
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.table td {
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9fafb;
}

/* Button improvements */
.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover,
.btn-outline-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Badge improvements */
.badge {
    font-weight: 500;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Empty state styling */
.text-center.py-3 {
    padding: 2rem 1rem !important;
}

/* Quick links section */
.card-body .btn-block {
    text-align: left;
    padding: 1rem;
    height: auto;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.card-body .btn-block small {
    margin-top: 0.25rem;
    font-size: 0.75rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .small-box .inner h3 {
        font-size: 1.5rem;
    }
    
    .info-box-number {
        font-size: 1.25rem;
    }
    
    .card-body .btn-block {
        margin-bottom: 0.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>