<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['warning_message'] = "Silakan login terlebih dahulu";
    header("Location: ../auth/login.php");
    exit;
}

// Get payment settings - PERBAIKAN: Gunakan fungsi yang sudah ada di functions.php
$payment_settings = getPaymentSettings();

// Get order details from URL parameters
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';

if (!$order_id || !in_array($order_type, ['print', 'cetak'])) {
    $_SESSION['error_message'] = "Pesanan tidak valid";
    header("Location: ../user/orders.php");
    exit;
}

// Get order details
$order = null;
if ($order_type === 'print') {
    $stmt = $conn->prepare("SELECT * FROM print_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM cetak_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    $_SESSION['error_message'] = "Pesanan tidak ditemukan";
    header("Location: ../user/orders.php");
    exit;
}

// Check if payment already exists
$existing_payment = null;
$stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? AND order_type = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$order_id, $order_type]);
$existing_payment = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = cleanInput($_POST['payment_method']);
    
    if (!in_array($payment_method, ['qris', 'bank_transfer'])) {
        $error_message = "Metode pembayaran tidak valid";
    } else {
        try {
            $conn->beginTransaction();
            
            // Calculate total amount with admin fee
            $admin_fee = ($payment_method === 'qris') ? 
                (int)($payment_settings['admin_fee_qris'] ?? 0) : 
                (int)($payment_settings['admin_fee_transfer'] ?? 2500);
            
            $total_amount = $order['price'] + $admin_fee;
            
            // Generate payment code
            $payment_code = generatePaymentCode();
            
            // Set expired time (24 hours from now)
            $expired_hours = (int)($payment_settings['payment_expired_hours'] ?? 24);
            $expired_at = date('Y-m-d H:i:s', strtotime("+{$expired_hours} hours"));
            
            // PERBAIKAN: Pastikan jumlah placeholder sesuai dengan jumlah parameter
            $stmt = $conn->prepare("INSERT INTO payments (
                order_id, order_type, order_number, user_id, amount, 
                payment_method, payment_code, bank_name, bank_account, 
                account_holder, payment_status, expired_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            // PERBAIKAN: Pastikan jumlah parameter sesuai dengan placeholder (12 parameter)
            $stmt->execute([
                $order_id,                                          // 1
                $order_type,                                        // 2
                $order['order_number'],                             // 3
                $_SESSION['user_id'],                               // 4
                $total_amount,                                      // 5
                $payment_method,                                    // 6
                $payment_code,                                      // 7
                $payment_settings['bank_transfer_name'] ?? 'BCA',   // 8
                $payment_settings['bank_transfer_account'] ?? '8905992312', // 9
                $payment_settings['bank_transfer_holder'] ?? 'Fantastic Pandawa', // 10
                'pending',                                          // 11
                $expired_at                                         // 12
            ]);
            
            $payment_id = $conn->lastInsertId();
            
            // Update order payment status and payment_id
            if ($order_type === 'print') {
                $stmt = $conn->prepare("UPDATE print_orders SET payment_status = ?, payment_id = ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = ?, payment_id = ? WHERE id = ?");
            }
            $stmt->execute(['pending', $payment_id, $order_id]);
            
            // Add payment history
            $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$payment_id, 'pending', 'Pembayaran dibuat', $_SESSION['user_id']]);
            
            $conn->commit();
            
            // Redirect to payment detail
            header("Location: payment.php?order_id={$order_id}&type={$order_type}&payment_id={$payment_id}");
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Get payment detail if payment_id is provided
$payment_detail = null;
if (isset($_GET['payment_id'])) {
    $payment_id = (int)$_GET['payment_id'];
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment_id, $_SESSION['user_id']]);
    $payment_detail = $stmt->fetch(PDO::FETCH_ASSOC);
}

$settings = getSettings();
$page_title = "Pembayaran - " . $order['order_number'];
$page_description = "Halaman pembayaran untuk pesanan " . $order['order_number'];

include '../includes/header.php';
?>

<!-- Payment Section -->
<section class="payment-section py-5">
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <a href="../user/orders.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <div>
                        <h1 class="h3 mb-0">Pembayaran Pesanan</h1>
                        <p class="text-muted mb-0"><?= $order['order_number'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <?php if ($payment_detail): ?>
            <!-- Payment Detail -->
            <div class="row g-4">
                <!-- Payment Info -->
                <div class="col-lg-8">
                    <div class="card payment-detail-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Detail Pembayaran
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($payment_detail['payment_method'] === 'qris'): ?>
                                <!-- QRIS Payment -->
                                <div class="qris-payment text-center">
                                    <h6 class="mb-3">Scan QRIS Code untuk Pembayaran</h6>
                                    <div class="qris-code-container mb-3">
                                        <img src="<?= $payment_settings['qris_code'] ?? '../assets/images/qris-code.png' ?>" alt="QRIS Code" class="qris-code-img">
                                    </div>
                                    <div class="qris-info">
                                        <p class="mb-2"><strong>Merchant:</strong> Percetakan Fantastic Pandawa</p>
                                        <p class="mb-2"><strong>Kode Pembayaran:</strong> <span class="fw-bold text-primary"><?= $payment_detail['payment_code'] ?></span></p>
                                        <p class="mb-0"><strong>Total Bayar:</strong> <span class="fw-bold text-success">Rp <?= number_format($payment_detail['amount'], 0, ',', '.') ?></span></p>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <h6><i class="fas fa-info-circle me-2"></i>Cara Pembayaran QRIS:</h6>
                                    <ol class="mb-0 ps-3">
                                        <li>Buka aplikasi mobile banking atau e-wallet</li>
                                        <li>Pilih menu "Scan QR" atau "QRIS"</li>
                                        <li>Scan kode QR di atas</li>
                                        <li>Masukkan nominal: <strong>Rp <?= number_format($payment_detail['amount'], 0, ',', '.') ?></strong></li>
                                        <li>Konfirmasi pembayaran</li>
                                        <li>Screenshot bukti pembayaran</li>
                                    </ol>
                                </div>
                                
                            <?php else: ?>
                                <!-- Bank Transfer Payment -->
                                <div class="bank-transfer-payment">
                                    <h6 class="mb-3">Transfer Bank</h6>
                                    <div class="bank-info">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Nama Bank:</label>
                                                <div class="bank-value"><?= $payment_detail['bank_name'] ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">No. Rekening:</label>
                                                <div class="bank-value">
                                                    <?= $payment_detail['bank_account'] ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?= $payment_detail['bank_account'] ?>')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Atas Nama:</label>
                                                <div class="bank-value"><?= $payment_detail['account_holder'] ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Jumlah Transfer:</label>
                                                <div class="bank-value text-success fw-bold">
                                                    Rp <?= number_format($payment_detail['amount'], 0, ',', '.') ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?= $payment_detail['amount'] ?>')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-bold">Kode Pembayaran:</label>
                                                <div class="bank-value text-primary fw-bold">
                                                    <?= $payment_detail['payment_code'] ?>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('<?= $payment_detail['payment_code'] ?>')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">*Sertakan kode ini sebagai berita transfer</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <h6><i class="fas fa-info-circle me-2"></i>Cara Transfer:</h6>
                                        <ol class="mb-0 ps-3">
                                            <li>Transfer ke rekening <?= $payment_detail['bank_name'] ?>: <strong><?= $payment_detail['bank_account'] ?></strong></li>
                                            <li>Atas nama: <strong><?= $payment_detail['account_holder'] ?></strong></li>
                                            <li>Jumlah: <strong>Rp <?= number_format($payment_detail['amount'], 0, ',', '.') ?></strong></li>
                                            <li>Berita transfer: <strong><?= $payment_detail['payment_code'] ?></strong></li>
                                            <li>Screenshot bukti transfer</li>
                                        </ol>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Upload Payment Proof -->
                            <div class="upload-proof-section mt-4">
                                <h6 class="mb-3">Upload Bukti Pembayaran</h6>
                                <form method="POST" action="confirm_payment.php" enctype="multipart/form-data" class="upload-proof-form">
                                    <input type="hidden" name="payment_id" value="<?= $payment_detail['id'] ?>">
                                    
                                    <div class="mb-3">
                                        <label for="payment_proof" class="form-label">Bukti Pembayaran *</label>
                                        <div class="file-upload-area" id="proofUploadArea">
                                            <input type="file" class="form-control file-input" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                            <div class="file-upload-content">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <h6>Upload bukti pembayaran</h6>
                                                <p class="text-muted mb-0">JPG, PNG, atau PDF (Max: 5MB)</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_notes" class="form-label">Catatan (Opsional)</label>
                                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" placeholder="Tambahkan catatan jika diperlukan"></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-check me-2"></i>Konfirmasi Pembayaran
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Status & Timer -->
                <div class="col-lg-4">
                    <div class="card payment-status-card">
                        <div class="card-header">
                            <h6 class="mb-0">Status Pembayaran</h6>
                        </div>
                        <div class="card-body">
                            <div class="payment-status mb-3">
                                <?php
                                $status_class = 'warning';
                                $status_text = 'Menunggu Pembayaran';
                                $status_icon = 'clock';
                                
                                switch($payment_detail['payment_status']) {
                                    case 'paid':
                                        $status_class = 'success';
                                        $status_text = 'Pembayaran Berhasil';
                                        $status_icon = 'check-circle';
                                        break;
                                    case 'failed':
                                        $status_class = 'danger';
                                        $status_text = 'Pembayaran Gagal';
                                        $status_icon = 'times-circle';
                                        break;
                                }
                                ?>
                                <div class="alert alert-<?= $status_class ?> text-center">
                                    <i class="fas fa-<?= $status_icon ?> fa-2x mb-2"></i>
                                    <h6 class="mb-0"><?= $status_text ?></h6>
                                </div>
                            </div>
                            
                            <?php if ($payment_detail['payment_status'] === 'pending'): ?>
                                <!-- Payment Timer -->
                                <div class="payment-timer mb-3">
                                    <h6>Batas Waktu Pembayaran:</h6>
                                    <div class="timer-display" id="paymentTimer" data-expired="<?= $payment_detail['expired_at'] ?>">
                                        <div class="timer-item">
                                            <span class="timer-value" id="hours">00</span>
                                            <span class="timer-label">Jam</span>
                                        </div>
                                        <div class="timer-item">
                                            <span class="timer-value" id="minutes">00</span>
                                            <span class="timer-label">Menit</span>
                                        </div>
                                        <div class="timer-item">
                                            <span class="timer-value" id="seconds">00</span>
                                            <span class="timer-label">Detik</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">Pembayaran akan otomatis dibatalkan jika melewati batas waktu</small>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Order Summary -->
                            <div class="order-summary">
                                <h6>Ringkasan Pesanan</h6>
                                <div class="summary-item">
                                    <span>Subtotal:</span>
                                    <span>Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                                </div>
                                <?php 
                                $admin_fee = $payment_detail['amount'] - $order['price'];
                                if ($admin_fee > 0): 
                                ?>
                                    <div class="summary-item">
                                        <span>Biaya Admin:</span>
                                        <span>Rp <?= number_format($admin_fee, 0, ',', '.') ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr>
                                <div class="summary-item total">
                                    <span><strong>Total:</strong></span>
                                    <span><strong>Rp <?= number_format($payment_detail['amount'], 0, ',', '.') ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Payment Method Selection -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card payment-method-card">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Pilih Metode Pembayaran
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <!-- Order Summary -->
                            <div class="order-summary-preview mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-file-invoice text-primary me-2"></i>
                                    <h6 class="mb-0">Detail Pesanan</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td class="text-muted" width="40%">Nomor Pesanan:</td>
                                            <td><strong class="text-primary"><?= $order['order_number'] ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Jenis Layanan:</td>
                                            <td><span class="badge bg-info"><?= ucfirst($order_type) ?></span></td>
                                        </tr>
                                        <?php if ($order_type === 'print'): ?>
                                            <tr>
                                                <td class="text-muted">File:</td>
                                                <td><?= $order['original_filename'] ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Jumlah Copy:</td>
                                                <td><strong><?= $order['copies'] ?></strong> lembar</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Ukuran:</td>
                                                <td><?= $order['paper_size'] ?> - <?= $order['print_color'] ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td class="text-muted">Jenis Cetak:</td>
                                                <td><?= str_replace('-', ' ', ucwords($order['cetak_type'])) ?></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Quantity:</td>
                                                <td><strong><?= $order['quantity'] ?></strong> pcs</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Material:</td>
                                                <td><?= $order['paper_type'] ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <div class="border-top pt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">Total Harga:</span>
                                        <h5 class="mb-0 text-success">Rp <?= number_format($order['price'], 0, ',', '.') ?></h5>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method Form -->
                            <form method="POST" class="payment-method-form">
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-wallet text-primary me-2"></i>
                                        <h6 class="mb-0">Pilih Metode Pembayaran</h6>
                                    </div>
                                    
                                    <div class="payment-methods">
                                        <!-- QRIS Payment -->
                                        <div class="payment-method-option" onclick="selectPaymentMethod('qris')">
                                            <input type="radio" name="payment_method" value="qris" id="qris" required style="display: none;">
                                            <div class="payment-method-card">
                                                <div class="payment-method-header">
                                                    <div class="payment-method-icon bg-success">
                                                        <i class="fas fa-qrcode"></i>
                                                    </div>
                                                    <div class="payment-method-info">
                                                        <h6 class="mb-1">QRIS</h6>
                                                        <p class="mb-0 text-muted">Scan QR Code untuk pembayaran instant</p>
                                                    </div>
                                                    <div class="payment-method-check">
                                                        <i class="fas fa-check-circle text-success d-none"></i>
                                                        <i class="far fa-circle text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="payment-method-details">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <small class="text-muted d-block">Metode:</small>
                                                            <span class="fw-bold">E-Wallet & Mobile Banking</span>
                                                        </div>
                                                        <div class="col-md-6 text-md-end">
                                                            <small class="text-muted d-block">Biaya Admin:</small>
                                                            <span class="badge bg-success">GRATIS</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <div class="payment-features">
                                                            <span class="feature-tag"><i class="fas fa-bolt"></i> Instant</span>
                                                            <span class="feature-tag"><i class="fas fa-shield-alt"></i> Aman</span>
                                                            <span class="feature-tag"><i class="fas fa-mobile-alt"></i> Mobile</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Bank Transfer -->
                                        <div class="payment-method-option" onclick="selectPaymentMethod('bank_transfer')">
                                            <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" required style="display: none;">
                                            <div class="payment-method-card">
                                                <div class="payment-method-header">
                                                    <div class="payment-method-icon bg-primary">
                                                        <i class="fas fa-university"></i>
                                                    </div>
                                                    <div class="payment-method-info">
                                                        <h6 class="mb-1">Bank Transfer</h6>
                                                        <p class="mb-0 text-muted">Transfer manual ke rekening <?= $payment_settings['bank_transfer_name'] ?></p>
                                                    </div>
                                                    <div class="payment-method-check">
                                                        <i class="fas fa-check-circle text-success d-none"></i>
                                                        <i class="far fa-circle text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="payment-method-details">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <small class="text-muted d-block">Bank:</small>
                                                            <span class="fw-bold"><?= $payment_settings['bank_transfer_name'] ?></span>
                                                        </div>
                                                        <div class="col-md-6 text-md-end">
                                                            <small class="text-muted d-block">Biaya Admin:</small>
                                                            <span class="badge bg-warning text-dark">Rp <?= number_format($payment_settings['admin_fee_transfer'], 0, ',', '.') ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <div class="payment-features">
                                                            <span class="feature-tag"><i class="fas fa-clock"></i> Manual</span>
                                                            <span class="feature-tag"><i class="fas fa-shield-alt"></i> Aman</span>
                                                            <span class="feature-tag"><i class="fas fa-landmark"></i> Bank</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Total Calculation -->
                                <div class="total-calculation-card mb-4">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <h6 class="mb-3">
                                                <i class="fas fa-calculator text-primary me-2"></i>
                                                Rincian Pembayaran
                                            </h6>
                                            <div class="calculation-details">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Subtotal:</span>
                                                    <span class="fw-bold">Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Biaya Admin:</span>
                                                    <span class="fw-bold" id="admin-fee-display">Rp 0</span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="h6 mb-0 text-primary">Total Bayar:</span>
                                                    <span class="h5 mb-0 text-success fw-bold" id="total-amount-display">Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg" disabled id="submitBtn">
                                        <i class="fas fa-arrow-right me-2"></i>Lanjut ke Pembayaran
                                    </button>
                                </div>
                                
                                <!-- Powered by Midtrans -->
                                <div class="payment-provider text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <span class="text-muted me-2">Powered by</span>
                                        <span class="fw-bold text-primary ms-2">Midtrans</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Pembayaran aman dan terpercaya</small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Custom CSS -->
<style>
.payment-section {
    background-color: #f8f9fa;
    min-height: 80vh;
}

.payment-detail-card, .payment-status-card, .payment-method-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.qris-code-img {
    max-width: 200px;
    border: 1px solid #ddd;
    padding: 10px;
    background: white;
}

.bank-info .bank-value {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 4px;
    font-family: monospace;
    font-weight: bold;
}

.timer-display {
    display: flex;
    justify-content: center;
    gap: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: white;
}

.timer-item {
    text-align: center;
}

.timer-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
}

.timer-label {
    font-size: 0.8rem;
    opacity: 0.8;
}

.file-upload-area {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-upload-area:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.summary-item.total {
    font-size: 1.1rem;
    padding-top: 8px;
}

/* Enhanced Payment Method Styles */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.payment-method-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-bottom: 15px;
    overflow: hidden;
    background: #fff;
}

.payment-method-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 15px rgba(0,123,255,0.1);
    transform: translateY(-2px);
}

.payment-method-option.selected .payment-method-card {
    border-color: #007bff;
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
    box-shadow: 0 4px 20px rgba(0,123,255,0.15);
}

.payment-method-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.payment-method-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-right: 15px;
    font-size: 1.2rem;
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.payment-method-info {
    flex: 1;
}

.payment-method-info h6 {
    color: #333;
    font-weight: 600;
}

.payment-method-check {
    font-size: 1.5rem;
}

.payment-method-option.selected .payment-method-check .fa-check-circle {
    display: inline-block !important;
}

.payment-method-option.selected .payment-method-check .fa-circle {
    display: none !important;
}

.payment-method-details {
    padding: 0 20px 20px;
    background: rgba(248,249,250,0.5);
}

.payment-method-option.selected .payment-method-details {
    background: rgba(255,255,255,0.7);
}

.payment-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.feature-tag {
    background: rgba(0,123,255,0.1);
    color: #0056b3;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.feature-tag i {
    margin-right: 4px;
}

.total-calculation-card {
    border-radius: 12px;
    overflow: hidden;
}

.calculation-details {
    font-size: 0.95rem;
}

.payment-provider {
    padding: 15px;
    background: rgba(248,249,250,0.8);
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

/* Responsive Design */
@media (max-width: 768px) {
    .payment-method-header {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .payment-method-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .payment-method-details {
        padding: 15px;
    }
    
    .payment-method-details .row {
        text-align: center;
    }
    
    .payment-method-details .col-md-6:last-child {
        margin-top: 10px;
    }
    
    .feature-tag {
        font-size: 0.7rem;
        padding: 3px 6px;
    }
    
    .timer-display {
        gap: 10px;
    }
    
    .timer-value {
        font-size: 1.2rem;
    }
}

/* Animation */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.payment-method-option {
    animation: slideInUp 0.5s ease forwards;
}

.payment-method-option:nth-child(2) {
    animation-delay: 0.1s;
}
</style>

<!-- Custom JavaScript -->
<script>
// Enhanced Payment Method Selection
function selectPaymentMethod(method) {
    // Remove selected class from all options
    document.querySelectorAll('.payment-method-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById(method).checked = true;
    
    // Enable submit button
    document.getElementById('submitBtn').disabled = false;
    
    // Update total calculation
    updateTotalCalculation(method);
    
    // Add animation effect
    event.currentTarget.style.transform = 'scale(1.02)';
    setTimeout(() => {
        event.currentTarget.style.transform = '';
    }, 200);
}

// Enhanced Total Calculation
function updateTotalCalculation(method) {
    const subtotal = <?= $order['price'] ?>;
    const qrisAdminFee = <?= $payment_settings['admin_fee_qris'] ?? 0 ?>;
    const transferAdminFee = <?= $payment_settings['admin_fee_transfer'] ?? 2500 ?>;
    
    let adminFee = 0;
    if (method === 'qris') {
        adminFee = qrisAdminFee;
    } else if (method === 'bank_transfer') {
        adminFee = transferAdminFee;
    }
    
    const total = subtotal + adminFee;
    
    // Update display with animation
    const adminFeeElement = document.getElementById('admin-fee-display');
    const totalElement = document.getElementById('total-amount-display');
    
    adminFeeElement.style.opacity = '0.5';
    totalElement.style.opacity = '0.5';
    
    setTimeout(() => {
        adminFeeElement.textContent = 'Rp ' + formatNumber(adminFee);
        totalElement.textContent = 'Rp ' + formatNumber(total);
        
        adminFeeElement.style.opacity = '1';
        totalElement.style.opacity = '1';
        
        // Highlight total for a moment
        totalElement.style.transform = 'scale(1.05)';
        setTimeout(() => {
            totalElement.style.transform = '';
        }, 300);
    }, 150);
}

// Format number with thousand separators
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}

// Payment Timer
<?php if ($payment_detail && $payment_detail['payment_status'] === 'pending'): ?>
function updatePaymentTimer() {
    const expiredDate = new Date('<?= $payment_detail['expired_at'] ?>').getTime();
    const now = new Date().getTime();
    const distance = expiredDate - now;
    
    if (distance < 0) {
        // Timer expired
        document.getElementById('paymentTimer').innerHTML = '<div class="alert alert-danger">Waktu pembayaran telah habis</div>';
        return;
    }
    
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
}

// Update timer every second
setInterval(updatePaymentTimer, 1000);
updatePaymentTimer(); // Initial call
<?php endif; ?>

// File upload preview
document.getElementById('payment_proof')?.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const uploadArea = document.getElementById('proofUploadArea');
        uploadArea.style.border = '2px solid #28a745';
        uploadArea.querySelector('.file-upload-content h6').textContent = file.name;
        uploadArea.querySelector('.file-upload-content p').textContent = 'File berhasil dipilih';
    }
});

// Enhanced initialization
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for payment method cards
    document.querySelectorAll('.payment-method-option').forEach((option, index) => {
        option.style.animationDelay = (index * 0.1) + 's';
        
        option.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"]');
            if (input) {
                selectPaymentMethod(input.value);
            }
        });
    });
    
    // Form validation
    const form = document.querySelector('.payment-method-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                e.preventDefault();
                alert('Silakan pilih metode pembayaran terlebih dahulu');
            }
        });
    }
    
    // Initialize total calculation if no method selected yet
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (selectedMethod) {
        updateTotalCalculation(selectedMethod.value);
    }
});
</script>

<?php include '../includes/footer.php'; ?>