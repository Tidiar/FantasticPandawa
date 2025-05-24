<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
requireLogin('../auth/login.php');

$user_id = $_SESSION['user_id'];
$settings = getSettings();
$page_title = "Detail Pesanan";

// Ambil parameter order number
$order_number = isset($_GET['order']) ? cleanInput($_GET['order']) : '';

if (empty($order_number)) {
    $_SESSION['error_message'] = "Nomor pesanan tidak valid";
    header('Location: orders.php');
    exit;
}

// Variabel untuk debugging
$debug_info = [];

// Ambil detail pesanan
try {
    // STEP 1: Cek pesanan di print_orders
    $stmt = $conn->prepare("SELECT 
        'print' as order_type,
        po.*
    FROM print_orders po
    WHERE po.order_number = ? AND po.user_id = ?");
    
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika tidak ditemukan di print_orders, cek di cetak_orders
    if (!$order) {
        $stmt = $conn->prepare("SELECT 
            'cetak' as order_type,
            co.*
        FROM cetak_orders co
        WHERE co.order_number = ? AND co.user_id = ?");
        
        $stmt->execute([$order_number, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$order) {
        $_SESSION['error_message'] = "Pesanan tidak ditemukan";
        header('Location: orders.php');
        exit;
    }
    
    $debug_info['order'] = $order;
    $order_type = $order['order_type'];
    $order_id = $order['id'];
    
    // STEP 2: Ambil data user
    try {
        $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $order['customer_name'] = $user['name'];
            $order['customer_email'] = $user['email'];
            $order['customer_phone'] = $user['phone'];
            $debug_info['user'] = $user;
        }
    } catch (Exception $e) {
        $debug_info['user_error'] = $e->getMessage();
    }
    
    // STEP 3: Ambil data pembayaran jika ada payment_id
    if (!empty($order['payment_id'])) {
        try {
            $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$order['payment_id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                $order['payment_info'] = $payment;
                $debug_info['payment'] = $payment;
            }
        } catch (Exception $e) {
            $debug_info['payment_error'] = $e->getMessage();
        }
    }
    
    // STEP 4: Cek apakah tabel riwayat pesanan ada
    try {
        $history_table = $order_type . '_order_history';
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$history_table]);
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            try {
                $stmt = $conn->prepare("SELECT * FROM $history_table WHERE order_id = ? ORDER BY created_at DESC");
                $stmt->execute([$order_id]);
                $order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $debug_info['history_exists'] = true;
                $debug_info['order_history'] = $order_history;
            } catch (Exception $e) {
                $debug_info['history_query_error'] = $e->getMessage();
            }
        } else {
            $debug_info['history_exists'] = false;
        }
    } catch (Exception $e) {
        $debug_info['check_history_table_error'] = $e->getMessage();
    }
    
    // STEP 5: Cek apakah tabel payment_history ada
    if (!empty($order['payment_id'])) {
        try {
            $stmt = $conn->prepare("SHOW TABLES LIKE 'payment_history'");
            $stmt->execute();
            $table_exists = $stmt->rowCount() > 0;
            
            if ($table_exists) {
                try {
                    $stmt = $conn->prepare("SELECT * FROM payment_history WHERE payment_id = ? ORDER BY created_at DESC");
                    $stmt->execute([$order['payment_id']]);
                    $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $debug_info['payment_history_exists'] = true;
                    $debug_info['payment_history'] = $payment_history;
                } catch (Exception $e) {
                    $debug_info['payment_history_query_error'] = $e->getMessage();
                }
            } else {
                $debug_info['payment_history_exists'] = false;
            }
        } catch (Exception $e) {
            $debug_info['check_payment_history_table_error'] = $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $debug_info['main_error'] = $e->getMessage();
    $debug_info['main_error_trace'] = $e->getTraceAsString();
}

include '../includes/header.php';
?>

<section class="order-detail-section py-5">
    <div class="container">
        <!-- Back button -->
        <div class="mb-4">
            <a href="orders.php" class="btn btn-primary quick-action-btn">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pesanan
            </a>
        </div>
        
        <!-- Order Detail Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="order-title">
                            Pesanan #<?= htmlspecialchars($order['order_number']) ?>
                        </h1>
                        <p class="order-meta text-muted">
                            <i class="far fa-calendar-alt me-1"></i> <?= formatDate($order['created_at'], true) ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="order-status-badges">
                            <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?> me-2 p-2">
                                <?= translateStatus($order['status']) ?>
                            </span>
                            <span class="badge bg-<?= getPaymentStatusBadgeClass($order['payment_status']) ?> p-2">
                                <?= translateStatus($order['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-<?= $order['order_type'] == 'print' ? 'print' : 'copy' ?> me-2"></i>
                            Detail <?= $order['order_type'] == 'print' ? 'Print' : 'Cetak' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($order['order_type'] == 'print'): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="fw-bold">Nama File:</label>
                                        <p><?= htmlspecialchars($order['original_filename']) ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Jumlah Copy:</label>
                                        <p><?= $order['copies'] ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Ukuran Kertas:</label>
                                        <p><?= $order['paper_size'] ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="fw-bold">Warna:</label>
                                        <p><?= $order['print_color'] == 'BW' ? 'Hitam Putih' : 'Berwarna' ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Jenis Kertas:</label>
                                        <p><?= $order['paper_type'] ?></p>
                                    </div>
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="mb-3">
                                            <label class="fw-bold">Catatan:</label>
                                            <p><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="fw-bold">Jenis Cetak:</label>
                                        <p><?= ucfirst(str_replace('-', ' ', $order['cetak_type'])) ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Jumlah:</label>
                                        <p><?= $order['quantity'] ?> pcs</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Jenis Kertas:</label>
                                        <p><?= $order['paper_type'] ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="fw-bold">Finishing:</label>
                                        <p><?= $order['finishing'] ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Pengiriman:</label>
                                        <p><?= $order['delivery'] ?></p>
                                    </div>
                                    <?php if (!empty($order['description'])): ?>
                                        <div class="mb-3">
                                            <label class="fw-bold">Deskripsi:</label>
                                            <p><?= nl2br(htmlspecialchars($order['description'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order History -->
                <?php if (!empty($debug_info['order_history'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Pesanan
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="timeline-container">
                            <ul class="timeline">
                                <?php foreach ($debug_info['order_history'] as $history): ?>
                                    <li class="timeline-item">
                                        <div class="timeline-badge bg-<?= getStatusBadgeClass($history['status']) ?>">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title"><?= translateStatus($history['status']) ?></h6>
                                            <p class="timeline-text"><?= $history['notes'] ?></p>
                                            <p class="timeline-date">
                                                <i class="far fa-clock me-1"></i>
                                                <?= formatDate($history['created_at'], true) ?>
                                            </p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Payment Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-wallet me-2"></i>Informasi Pembayaran
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="price-summary mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Total Harga</span>
                                <span class="fw-bold">Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                        
                        <div class="payment-details">
                            <div class="mb-2">
                                <label class="fw-bold d-block">Status Pembayaran:</label>
                                <span class="badge bg-<?= getPaymentStatusBadgeClass($order['payment_status']) ?>">
                                    <?= translateStatus($order['payment_status']) ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($order['payment_info'])): ?>
                                <?php if (!empty($order['payment_info']['payment_method'])): ?>
                                    <div class="mb-2">
                                        <label class="fw-bold d-block">Metode Pembayaran:</label>
                                        <?= $order['payment_info']['payment_method'] ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['payment_info']['transaction_id'])): ?>
                                    <div class="mb-2">
                                        <label class="fw-bold d-block">ID Transaksi:</label>
                                        <?= $order['payment_info']['transaction_id'] ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['payment_info']['payment_date'])): ?>
                                    <div class="mb-2">
                                        <label class="fw-bold d-block">Tanggal Pembayaran:</label>
                                        <?= formatDate($order['payment_info']['payment_date'], true) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($order['payment_status'] == 'pending' && empty($order['payment_id'])): ?>
                            <div class="mt-3">
                                <a href="../services/payment.php?order_id=<?= $order['id'] ?>&type=<?= $order['order_type'] ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-money-bill me-2"></i>Bayar Sekarang
                                </a>
                            </div>
                        <?php elseif ($order['payment_status'] == 'pending' && !empty($order['payment_id'])): ?>
                            <div class="mt-3">
                                <a href="../services/payment_status.php?payment_id=<?= $order['payment_id'] ?>" 
                                   class="btn btn-warning w-100">
                                    <i class="fas fa-credit-card me-2"></i>Lihat Status Pembayaran
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Informasi Pelanggan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="fw-bold d-block">Nama:</label>
                            <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                        </div>
                        <div class="mb-2">
                            <label class="fw-bold d-block">Email:</label>
                            <?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?>
                        </div>
                        <?php if (!empty($order['customer_phone'])): ?>
                            <div class="mb-2">
                                <label class="fw-bold d-block">Telepon:</label>
                                <?= htmlspecialchars($order['customer_phone']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Help Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-headset me-2"></i>Butuh Bantuan?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Jika Anda memiliki pertanyaan atau butuh bantuan terkait pesanan ini, silakan hubungi kami:</p>
                        <div class="d-grid gap-2">
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['contact_whatsapp'] ?? '08228243997') ?>?text=Halo, saya ingin menanyakan pesanan <?= $order['order_number'] ?>" 
                               class="btn btn-success" target="_blank">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </a>
                            <a href="tel:<?= $settings['contact_phone'] ?? '08228243997' ?>" class="btn btn-primary quick-action-btn">
                                <i class="fas fa-phone me-2"></i>Telepon
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Debug Section (hanya tampil jika ada error) -->
        <?php if (!empty($debug_info['main_error']) || !empty($debug_info['user_error']) || !empty($debug_info['payment_error'])): ?>
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Debug Information</h5>
            </div>
            <div class="card-body">
                <pre><?php print_r($debug_info); ?></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.order-detail-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.order-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.order-meta {
    font-size: 0.9rem;
}

.order-status-badges .badge {
    font-size: 0.9rem;
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1rem 1.5rem;
}

.timeline-container {
    padding: 1.5rem;
}

.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 2px;
    background: #e2e8f0;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-badge {
    position: absolute;
    left: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: white;
    z-index: 1;
}

.timeline-badge i {
    font-size: 1rem;
}

.timeline-content {
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
}

.timeline-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.timeline-text {
    margin-bottom: 0.5rem;
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .order-title {
        font-size: 1.5rem;
    }
    
    .order-actions {
        margin-top: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .order-actions .btn {
        width: 100%;
        margin: 0 !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>