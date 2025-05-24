<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
requireLogin('../auth/login.php');

$user_id = $_SESSION['user_id'];
$settings = getSettings();
$page_title = "Status Pembayaran";

// Ambil parameter payment_id
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    $_SESSION['error_message'] = "ID pembayaran tidak valid";
    header('Location: ../user/orders.php');
    exit;
}

// Ambil data pembayaran dan pesanan
try {
    // Query untuk mendapatkan data pembayaran
    $payment_query = "SELECT * FROM payments WHERE id = ?";
    $stmt = $conn->prepare($payment_query);
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        $_SESSION['error_message'] = "Data pembayaran tidak ditemukan";
        header('Location: ../user/orders.php');
        exit;
    }
    
    // Pastikan payment memiliki kolom 'status', jika tidak, gunakan 'payment_status'
    if (!isset($payment['status'])) {
        $payment['status'] = isset($payment['payment_status']) ? $payment['payment_status'] : 'pending';
    }
    
    // Cari pesanan yang terkait dengan pembayaran ini
    $order = null;
    $order_type = '';
    
    // Cek di print_orders
    $stmt = $conn->prepare("SELECT *, 'print' as order_type FROM print_orders WHERE payment_id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika tidak ditemukan, cek di cetak_orders
    if (!$order) {
        $stmt = $conn->prepare("SELECT *, 'cetak' as order_type FROM cetak_orders WHERE payment_id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$order) {
        $_SESSION['error_message'] = "Pesanan terkait tidak ditemukan atau bukan milik Anda";
        header('Location: ../user/orders.php');
        exit;
    }
    
    $order_type = $order['order_type'];
    
    // Pastikan order memiliki kolom 'status', jika tidak, berikan nilai default
    if (!isset($order['status'])) {
        $order['status'] = 'pending';
    }
    
    // Ambil history pembayaran jika ada
    $payment_history = [];
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE 'payment_history'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->prepare("SELECT * FROM payment_history WHERE payment_id = ? ORDER BY created_at DESC");
            $stmt->execute([$payment_id]);
            $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Table tidak ada, tidak masalah
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header('Location: ../user/orders.php');
    exit;
}

// Tentukan status dan pesan
$status_info = getPaymentStatusInfo($payment['status'], $order['status']);

// Function untuk mendapatkan informasi status
function getPaymentStatusInfo($payment_status, $order_status) {
    switch ($payment_status) {
        case 'pending':
            return [
                'class' => 'warning',
                'icon' => 'fas fa-clock',
                'title' => 'Menunggu Pembayaran',
                'message' => 'Pembayaran Anda sedang menunggu konfirmasi. Silakan lakukan pembayaran sesuai instruksi.',
                'action' => 'pay'
            ];
        case 'paid':
            return [
                'class' => 'info',
                'icon' => 'fas fa-credit-card',
                'title' => 'Pembayaran Diterima',
                'message' => 'Pembayaran Anda telah diterima dan sedang diverifikasi oleh admin.',
                'action' => 'wait'
            ];
        case 'verified':
            return [
                'class' => 'success',
                'icon' => 'fas fa-check-circle',
                'title' => 'Pembayaran Terverifikasi',
                'message' => 'Pembayaran Anda telah diverifikasi. Pesanan sedang diproses.',
                'action' => 'track'
            ];
        case 'failed':
            // Check if this is a cancellation by looking at notes
            $is_canceled = isset($payment['notes']) && strpos($payment['notes'], 'dibatalkan') !== false;
            
            if ($is_canceled) {
                return [
                    'class' => 'secondary',
                    'icon' => 'fas fa-ban',
                    'title' => 'Pembayaran Dibatalkan',
                    'message' => 'Pembayaran dan pesanan telah dibatalkan.',
                    'action' => 'none'
                ];
            } else {
                return [
                    'class' => 'danger',
                    'icon' => 'fas fa-times-circle',
                    'title' => 'Pembayaran Gagal',
                    'message' => 'Pembayaran Anda gagal diproses. Silakan coba lagi.',
                    'action' => 'retry'
                ];
            }
        case 'canceled':
            return [
                'class' => 'secondary',
                'icon' => 'fas fa-ban',
                'title' => 'Pembayaran Dibatalkan',
                'message' => 'Pembayaran dan pesanan telah dibatalkan.',
                'action' => 'none'
            ];
        case 'refunded':
            return [
                'class' => 'info',
                'icon' => 'fas fa-undo',
                'title' => 'Pembayaran Dikembalikan',
                'message' => 'Pembayaran Anda telah dikembalikan.',
                'action' => 'none'
            ];
        default:
            return [
                'class' => 'secondary',
                'icon' => 'fas fa-question-circle',
                'title' => 'Status Tidak Dikenal',
                'message' => 'Status pembayaran tidak dapat diidentifikasi.',
                'action' => 'contact'
            ];
    }
}

include '../includes/header.php';
?>

<section class="payment-status-section py-5">
    <div class="container">
        <!-- Back button -->
        <div class="mb-4">
            <a href="../user/orders.php" class="btn btn-primary quick-action-btn">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Pesanan
            </a>
        </div>
        
        <!-- Payment Status Card -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card payment-status-card">
                    <div class="card-body text-center p-5">
                        <!-- Status Icon -->
                        <div class="status-icon mb-4">
                            <div class="icon-circle bg-<?= $status_info['class'] ?>">
                                <i class="<?= $status_info['icon'] ?>"></i>
                            </div>
                        </div>
                        
                        <!-- Status Title -->
                        <h2 class="status-title mb-3"><?= $status_info['title'] ?></h2>
                        
                        <!-- Status Message -->
                        <p class="status-message mb-4"><?= $status_info['message'] ?></p>
                        
                        <!-- Payment Details -->
                        <div class="payment-details mb-4">
                            <div class="row text-start">
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>ID Pembayaran:</label>
                                        <span class="fw-bold"><?= $payment['id'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Nomor Pesanan:</label>
                                        <span class="fw-bold"><?= $order['order_number'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Total Pembayaran:</label>
                                        <span class="fw-bold">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label>Metode Pembayaran:</label>
                                        <span><?= $payment['payment_method'] ?? 'Belum dipilih' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Tanggal Dibuat:</label>
                                        <span><?= formatDate($payment['created_at'], true) ?></span>
                                    </div>
                                    <?php if (isset($payment['payment_date']) && $payment['payment_date']): ?>
                                        <div class="detail-item">
                                            <label>Tanggal Pembayaran:</label>
                                            <span><?= formatDate($payment['payment_date'], true) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <?php if ($status_info['action'] === 'pay'): ?>
                                <a href="../services/payment_gateway.php?payment_id=<?= $payment_id ?>" 
                                   class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Lakukan Pembayaran
                                </a>
                            <?php elseif ($status_info['action'] === 'retry'): ?>
                                <a href="../services/payment_gateway.php?payment_id=<?= $payment_id ?>" 
                                   class="btn btn-warning btn-lg">
                                    <i class="fas fa-redo me-2"></i>Coba Lagi
                                </a>
                            <?php elseif ($status_info['action'] === 'track'): ?>
                                <a href="../user/order-detail.php?order=<?= $order['order_number'] ?>" 
                                   class="btn btn-success btn-lg">
                                    <i class="fas fa-eye me-2"></i>Lihat Detail Pesanan
                                </a>
                            <?php endif; ?>
                            
                            <a href="../user/orders.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-list me-2"></i>Lihat Semua Pesanan
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Payment History -->
                <?php if (!empty($payment_history)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Pembayaran
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="timeline-container">
                            <ul class="timeline">
                                <?php foreach ($payment_history as $history): ?>
                                    <li class="timeline-item">
                                        <div class="timeline-badge bg-<?= getStatusBadgeClass($history['status'] ?? 'pending') ?>">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title"><?= translateStatus($history['status'] ?? 'pending') ?></h6>
                                            <p class="timeline-text"><?= htmlspecialchars($history['notes'] ?? 'Tidak ada catatan') ?></p>
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
                
                <!-- Order Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Ringkasan Pesanan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label>Jenis Layanan:</label>
                                    <span class="badge bg-<?= $order_type == 'print' ? 'primary' : 'success' ?>">
                                        <?= $order_type == 'print' ? 'Print' : 'Cetak' ?>
                                    </span>
                                </div>
                                
                                <?php if ($order_type == 'print'): ?>
                                    <div class="detail-item">
                                        <label>File:</label>
                                        <span><?= htmlspecialchars($order['original_filename']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Jumlah Copy:</label>
                                        <span><?= $order['copies'] ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="detail-item">
                                        <label>Jenis Cetak:</label>
                                        <span><?= ucfirst(str_replace('-', ' ', $order['cetak_type'])) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Jumlah:</label>
                                        <span><?= $order['quantity'] ?> pcs</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label>Status Pesanan:</label>
                                    <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                        <?= translateStatus($order['status']) ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <label>Total Harga:</label>
                                    <span class="fw-bold">Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-item">
                                    <label>Tanggal Pesanan:</label>
                                    <span><?= formatDate($order['created_at'], true) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i>Butuh Bantuan?
                        </h5>
                    </div>
                    <div class="card-body">
                        <p>Jika Anda mengalami masalah dengan pembayaran atau memiliki pertanyaan, silakan hubungi kami:</p>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['contact_whatsapp'] ?? '08228243997') ?>?text=Halo, saya butuh bantuan dengan pembayaran ID <?= $payment_id ?>" 
                                   class="btn btn-success w-100" target="_blank">
                                    <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
                                </a>
                            </div>
                            <div class="col-md-6 mb-2">
                                <a href="tel:<?= $settings['contact_phone'] ?? '08228243997' ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-phone me-2"></i>Telepon
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.payment-status-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.payment-status-card {
    border: none;
    border-radius: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    background: white;
}

.status-icon {
    display: flex;
    justify-content: center;
    align-items: center;
}

.icon-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.icon-circle i {
    font-size: 3rem;
    color: white;
}

.status-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.status-message {
    font-size: 1.125rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

.payment-details {
    background: #f8fafc;
    border-radius: 1rem;
    padding: 1.5rem;
    margin: 2rem auto;
}

.detail-item {
    margin-bottom: 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.detail-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.detail-item label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0;
    flex: 1;
}

.detail-item span {
    color: #1f2937;
    text-align: right;
    flex: 1;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.action-buttons .btn {
    font-weight: 600;
    padding: 0.875rem 2rem;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    border: 2px solid;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 1rem 1rem 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.card-header h5 {
    font-weight: 600;
    color: #1f2937;
    margin: 0;
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
    background: #e5e7eb;
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

.timeline-content {
    padding: 1rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
}

.timeline-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.timeline-text {
    margin-bottom: 0.5rem;
    color: #6b7280;
}

.timeline-date {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 0;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 0.5rem;
}

/* Status-specific colors */
.bg-warning {
    background-color: #f59e0b !important;
}

.bg-success {
    background-color: #10b981 !important;
}

.bg-danger {
    background-color: #ef4444 !important;
}

.bg-info {
    background-color: #3b82f6 !important;
}

.bg-secondary {
    background-color: #6b7280 !important;
}

.bg-primary {
    background-color: #3b82f6 !important;
}

/* Auto-refresh indicator */
.refresh-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.refresh-indicator .toast {
    background-color: #3b82f6;
    color: white;
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.refresh-indicator .toast-body {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .status-title {
        font-size: 1.5rem;
    }
    
    .status-message {
        font-size: 1rem;
    }
    
    .icon-circle {
        width: 80px;
        height: 80px;
    }
    
    .icon-circle i {
        font-size: 2rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }
    
    .detail-item span {
        text-align: left;
        margin-top: 0.25rem;
    }
    
    .payment-details {
        padding: 1rem;
    }
    
    .timeline-item {
        padding-left: 35px;
    }
    
    .timeline-badge {
        width: 30px;
        height: 30px;
        line-height: 30px;
    }
    
    .timeline:before {
        left: 15px;
    }
}

@media (max-width: 576px) {
    .payment-status-card .card-body {
        padding: 2rem 1.5rem;
    }
    
    .status-title {
        font-size: 1.25rem;
    }
    
    .icon-circle {
        width: 60px;
        height: 60px;
    }
    
    .icon-circle i {
        font-size: 1.5rem;
    }
    
    .action-buttons .btn {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
    }
}
</style>

<script>
// Auto refresh untuk status pending
document.addEventListener('DOMContentLoaded', function() {
    const paymentStatus = '<?= $payment['status'] ?>';
    const orderStatus = '<?= $order['status'] ?>';
    
    // Auto refresh jika status masih pending atau paid
    if (paymentStatus === 'pending' || paymentStatus === 'paid') {
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                // Show refresh indicator
                showRefreshIndicator();
                
                // Refresh page after 1 second
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }, 30000); // 30 seconds
    }
    
    // Add loading state to payment buttons
    const paymentButtons = document.querySelectorAll('a[href*="payment_gateway"]');
    paymentButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memproses...';
            this.style.pointerEvents = 'none';
            
            // Restore after 5 seconds in case of issues
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.style.pointerEvents = 'auto';
            }, 5000);
        });
    });
    
    // Add smooth scroll animation for timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateX(0)';
            }
        });
    }, observerOptions);
    
    timelineItems.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        item.style.transition = 'all 0.5s ease-out';
        observer.observe(item);
    });
});

function showRefreshIndicator() {
    // Remove existing indicator
    const existingIndicator = document.querySelector('.refresh-indicator');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    // Create new refresh indicator
    const indicator = document.createElement('div');
    indicator.className = 'refresh-indicator';
    indicator.innerHTML = `
        <div class="toast show" role="alert">
            <div class="toast-body">
                <i class="fas fa-sync-alt fa-spin me-2"></i>
                Memperbarui status pembayaran...
            </div>
        </div>
    `;
    
    document.body.appendChild(indicator);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (indicator.parentNode) {
            indicator.remove();
        }
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F5 or Ctrl/Cmd + R: Refresh
    if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && e.key === 'r')) {
        e.preventDefault();
        showRefreshIndicator();
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
    
    // Escape: Go back to orders
    if (e.key === 'Escape') {
        window.location.href = '../user/orders.php';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
