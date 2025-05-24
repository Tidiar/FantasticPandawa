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

// Get payment settings
$payment_settings = getPaymentSettings();

// Get payment ID from URL parameters
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if (!$payment_id) {
    $_SESSION['error_message'] = "ID pembayaran tidak valid";
    header("Location: ../user/orders.php");
    exit;
}

// Get payment details
$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
$stmt->execute([$payment_id, $_SESSION['user_id']]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    $_SESSION['error_message'] = "Pembayaran tidak ditemukan";
    header("Location: ../user/orders.php");
    exit;
}

// Check if payment is already processed
if (in_array($payment['payment_status'], ['paid', 'verified'])) {
    $_SESSION['info_message'] = "Pembayaran ini sudah diproses";
    header("Location: payment_status.php?payment_id={$payment_id}");
    exit;
}

// Check if payment is expired
$now = new DateTime();
$expired_at = new DateTime($payment['expired_at']);
if ($now > $expired_at) {
    try {
        $conn->beginTransaction();
        
        // Update payment status to expired
        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'expired' WHERE id = ?");
        $stmt->execute([$payment_id]);
        
        // Add to payment history
        $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'expired', 'Pembayaran kedaluwarsa', ?)");
        $stmt->execute([$payment_id, $_SESSION['user_id']]);
        
        $conn->commit();
        
        $_SESSION['error_message'] = "Pembayaran telah kedaluwarsa. Silakan buat pesanan baru.";
        header("Location: ../user/orders.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Terjadi kesalahan sistem";
        header("Location: ../user/orders.php");
        exit;
    }
}

// Get order details
$order = null;
if ($payment['order_type'] === 'print') {
    $stmt = $conn->prepare("SELECT * FROM print_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment['order_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM cetak_orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$payment['order_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    $_SESSION['error_message'] = "Pesanan tidak ditemukan";
    header("Location: ../user/orders.php");
    exit;
}

// Simulating a payment gateway process
// In a real implementation, this would connect to a payment provider API
$is_simulation = true;

// Handle payment simulation (for demo purposes)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simulate_payment'])) {
    $simulation_result = $_POST['simulation_result'];
    
    try {
        $conn->beginTransaction();
        
        if ($simulation_result === 'success') {
            // Update payment status to paid
            $stmt = $conn->prepare("UPDATE payments SET 
                payment_status = 'paid',
                payment_date = NOW(),
                updated_at = NOW(),
                payment_method = CONCAT(payment_method, ' (Simulasi)'),
                payment_response = ?
            WHERE id = ?");
            
            $payment_response = json_encode([
                'transaction_id' => 'SIM' . time() . rand(1000, 9999),
                'status' => 'success',
                'method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $stmt->execute([$payment_response, $payment_id]);
            
            // Update order payment status
            if ($payment['order_type'] === 'print') {
                $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'paid' WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'paid' WHERE id = ?");
            }
            $stmt->execute([$payment['order_id']]);
            
            // Add to payment history
            $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'paid', 'Pembayaran berhasil (Simulasi)', ?)");
            $stmt->execute([$payment_id, $_SESSION['user_id']]);
            
            $conn->commit();
            
            $_SESSION['success_message'] = "Pembayaran berhasil disimulasikan! Pesanan Anda sedang diproses.";
            header("Location: payment_status.php?payment_id={$payment_id}");
            exit;
            
        } else {
            // Failed payment
            $stmt = $conn->prepare("UPDATE payments SET 
                payment_status = 'failed',
                updated_at = NOW(),
                payment_response = ?
            WHERE id = ?");
            
            $payment_response = json_encode([
                'transaction_id' => 'SIM' . time() . rand(1000, 9999),
                'status' => 'failed',
                'method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => 'Simulasi pembayaran gagal'
            ]);
            
            $stmt->execute([$payment_response, $payment_id]);
            
            // Add to payment history
            $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'failed', 'Pembayaran gagal (Simulasi)', ?)");
            $stmt->execute([$payment_id, $_SESSION['user_id']]);
            
            $conn->commit();
            
            $_SESSION['error_message'] = "Simulasi pembayaran gagal. Silakan coba lagi.";
            header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
            exit;
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
        exit;
    }
}

// For actual payment gateway integration
// This would normally include redirecting to the payment provider or handling API requests

// Initialize payment gateway based on method
if ($payment['payment_method'] === 'qris') {
    // Integration with QRIS provider would go here
    $gateway_type = 'qris';
    $payment_title = 'QRIS Payment';
    
} elseif ($payment['payment_method'] === 'bank_transfer') {
    // For bank transfers, we show transfer instructions
    $gateway_type = 'bank_transfer';
    $payment_title = 'Bank Transfer';
    
} else {
    // Unknown payment method
    $_SESSION['error_message'] = "Metode pembayaran tidak dikenali";
    header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
    exit;
}

$settings = getSettings();
$page_title = "Gateway Pembayaran - " . $payment['order_number'];
$page_description = "Proses pembayaran untuk pesanan " . $payment['order_number'];

include '../includes/header.php';
?>

<section class="payment-gateway-section py-5">
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <a href="payment.php?order_id=<?= $payment['order_id'] ?>&type=<?= $payment['order_type'] ?>&payment_id=<?= $payment_id ?>" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <div>
                        <h1 class="h3 mb-0"><?= $payment_title ?></h1>
                        <p class="text-muted mb-0">Order: <?= $payment['order_number'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Gateway Content -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card payment-gateway-card">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            <?= $payment_title ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($gateway_type === 'qris'): ?>
                            <!-- QRIS Payment Display -->
                            <div class="qris-payment-section text-center mb-4">
                                <div class="qr-code-container mb-3">
                                    <img src="<?= $payment_settings['qris_code'] ?? '../assets/images/qris-code.png' ?>" 
                                         alt="QRIS Code" class="qr-code-img">
                                </div>
                                <div class="payment-instructions">
                                    <h6 class="mb-3">Cara Pembayaran QRIS</h6>
                                    <ol class="text-start instruction-list">
                                        <li>Buka aplikasi e-wallet atau mobile banking yang mendukung QRIS</li>
                                        <li>Pilih menu Scan QR atau QRIS</li>
                                        <li>Scan kode QR di atas</li>
                                        <li>Periksa detail transaksi:
                                            <ul>
                                                <li>Merchant: <strong>Fantastic Pandawa</strong></li>
                                                <li>Total: <strong>Rp <?= number_format(intval($payment['amount'] ?? 0), 0, ',', '.') ?></strong></li>
                                            </ul>
                                        </li>
                                        <li>Konfirmasi dan selesaikan pembayaran</li>
                                        <li>Simpan bukti pembayaran</li>
                                    </ol>
                                </div>
                            </div>
                        <?php elseif ($gateway_type === 'bank_transfer'): ?>
                            <!-- Bank Transfer Instructions -->
                            <div class="bank-transfer-section mb-4">
                                <div class="bank-info-container mb-3">
                                    <h6 class="mb-3">Instruksi Transfer Bank</h6>
                                    <div class="bank-account-details p-3 mb-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted mb-1">Bank</label>
                                                <div class="bank-value"><?= htmlspecialchars($payment['bank_name'] ?? 'BCA') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted mb-1">Nomor Rekening</label>
                                                <div class="bank-value d-flex align-items-center">
                                                    <span id="account-number"><?= htmlspecialchars($payment['bank_account'] ?? '8905992312') ?></span>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('account-number')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted mb-1">Atas Nama</label>
                                                <div class="bank-value"><?= htmlspecialchars($payment['account_holder'] ?? 'Fantastic Pandawa') ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted mb-1">Jumlah Transfer</label>
                                                <div class="bank-value d-flex align-items-center">
                                                    <span id="transfer-amount" class="text-success">Rp <?= number_format(intval($payment['amount'] ?? 0), 0, ',', '.') ?></span>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('transfer-amount', true)">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted mb-1">Kode Pembayaran</label>
                                                <div class="bank-value d-flex align-items-center">
                                                    <span id="payment-code" class="text-primary"><?= htmlspecialchars($payment['payment_code'] ?? 'PAYMENT' . rand(1000, 9999)) ?></span>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('payment-code')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">* Penting: Sertakan kode ini pada berita transfer</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="transfer-instructions">
                                        <h6 class="mb-2">Langkah-langkah Transfer:</h6>
                                        <ol class="instruction-list">
                                            <li>Login ke akun internet/mobile banking atau kunjungi ATM terdekat</li>
                                            <li>Pilih menu transfer atau transfer antar bank</li>
                                            <li>Masukkan nomor rekening tujuan: <strong><?= htmlspecialchars($payment['bank_account'] ?? '8905992312') ?></strong></li>
                                            <li>Masukkan jumlah transfer: <strong>Rp <?= number_format(intval($payment['amount'] ?? 0), 0, ',', '.') ?></strong></li>
                                            <li>Tambahkan kode pembayaran <strong><?= htmlspecialchars($payment['payment_code'] ?? 'PAYMENT' . rand(1000, 9999)) ?></strong> pada berita transfer</li>
                                            <li>Konfirmasi dan selesaikan transaksi</li>
                                            <li>Simpan bukti transfer sebagai referensi</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Payment Confirmation -->
                        <div class="payment-confirmation">
                            <hr>
                            <h6 class="mb-3">Konfirmasi Pembayaran</h6>
                            <p>Setelah melakukan pembayaran, silakan upload bukti pembayaran:</p>
                            
                            <div class="d-grid">
                                <a href="payment.php?order_id=<?= htmlspecialchars($payment['order_id'] ?? 0) ?>&type=<?= htmlspecialchars($payment['order_type'] ?? 'print') ?>&payment_id=<?= htmlspecialchars($payment_id) ?>" 
                                   class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Bukti Pembayaran
                                </a>
                            </div>
                        </div>
                        
                        <!-- Support Info -->
                        <div class="support-info mt-4">
                            <hr>
                            <h6 class="mb-2">Butuh Bantuan?</h6>
                            <p class="mb-3">Jika Anda mengalami kendala dalam pembayaran, silakan hubungi kami:</p>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya butuh bantuan untuk pembayaran order <?= htmlspecialchars($payment['order_number'] ?? '-') ?>" 
                                       target="_blank" class="btn btn-success w-100">
                                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="tel:<?= htmlspecialchars($settings['contact_phone'] ?? '0822-8243-9997') ?>" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-phone me-2"></i>Hubungi CS
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Support Info -->
                        <div class="support-info mt-4">
                            <hr>
                            <h6 class="mb-2">Butuh Bantuan?</h6>
                            <p class="mb-3">Jika Anda mengalami kendala dalam pembayaran, silakan hubungi kami:</p>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya butuh bantuan untuk pembayaran order <?= $payment['order_number'] ?>" 
                                       target="_blank" class="btn btn-success w-100">
                                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                    </a>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-phone me-2"></i>Hubungi CS
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.payment-gateway-section {
    background-color: #f8f9fa;
    min-height: 80vh;
}

.payment-gateway-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.payment-details-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
}

.payment-details-table tr:last-child td {
    border-bottom: none;
}

.qr-code-img {
    max-width: 250px;
    border: 1px solid #ddd;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.instruction-list {
    padding-left: 20px;
}

.instruction-list li {
    margin-bottom: 10px;
}

.instruction-list ul {
    padding-left: 20px;
    margin-top: 5px;
}

.bank-account-details {
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.bank-value {
    background: white;
    padding: 10px 15px;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
    font-weight: 500;
}

.simulation-controls .btn-group {
    width: 100%;
}

.simulation-controls .btn {
    padding: 15px;
    font-size: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bank-account-details .col-md-6 {
        margin-bottom: 15px;
    }
    
    .simulation-controls .btn-group {
        flex-direction: column;
    }
    
    .simulation-controls .btn {
        margin-bottom: 10px;
    }
}
</style>

<script>
// Countdown timer for expiration time
function updateCountdown() {
    const expirationElement = document.getElementById('expiration-time');
    const countdownBadge = document.getElementById('countdown-badge');
    
    if (!expirationElement || !countdownBadge) return;
    
    const expirationTime = new Date(expirationElement.getAttribute('data-time')).getTime();
    const now = new Date().getTime();
    const timeLeft = expirationTime - now;
    
    if (timeLeft <= 0) {
        // Expired
        countdownBadge.innerHTML = 'Kedaluwarsa';
        countdownBadge.classList.remove('bg-warning');
        countdownBadge.classList.add('bg-danger');
        
        // Reload page after 3 seconds to show expired status
        setTimeout(() => {
            window.location.reload();
        }, 3000);
        
        return;
    }
    
    // Calculate hours, minutes, seconds
    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
    
    // Format countdown
    let countdownText = '';
    if (hours > 0) {
        countdownText = `${hours}j ${minutes}m ${seconds}d`;
    } else if (minutes > 0) {
        countdownText = `${minutes}m ${seconds}d`;
    } else {
        countdownText = `${seconds}d`;
    }
    
    countdownBadge.innerHTML = countdownText;
    
    // Change color when less than 30 minutes
    if (timeLeft < 30 * 60 * 1000) {
        countdownBadge.classList.remove('bg-warning');
        countdownBadge.classList.add('bg-danger');
    }
}

// Copy to clipboard function
function copyToClipboard(elementId, isAmount = false) {
    const element = document.getElementById(elementId);
    let textToCopy = element.innerText;
    
    // If copying amount, remove formatting
    if (isAmount) {
        textToCopy = textToCopy.replace(/[^0-9]/g, '');
    }
    
    // Create temporary input
    const tempInput = document.createElement('input');
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Show success indicator
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
}

document.addEventListener('DOMContentLoaded', function() {
    // Start countdown timer
    updateCountdown();
    setInterval(updateCountdown, 1000);
}); (hours > 0) {
        countdownText = `${hours}j ${minutes}m ${seconds}d`;
    } else if (minutes > 0) {
        countdownText = `${minutes}m ${seconds}d`;
    } else {
        countdownText = `${seconds}d`;
    }
    
    countdownBadge.innerHTML = countdownText;
    
    // Change color when less than 30 minutes
    if (timeLeft < 30 * 60 * 1000) {
        countdownBadge.classList.remove('bg-warning');
        countdownBadge.classList.add('bg-danger');
    }
}

// Copy to clipboard function
function copyToClipboard(elementId, isAmount = false) {
    const element = document.getElementById(elementId);
    let textToCopy = element.innerText;
    
    // If copying amount, remove formatting
    if (isAmount) {
        textToCopy = textToCopy.replace(/[^0-9]/g, '');
    }
    
    // Create temporary input
    const tempInput = document.createElement('input');
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Show success indicator
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
}

document.addEventListener('DOMContentLoaded', function() {
    // Start countdown timer
    updateCountdown();
    setInterval(updateCountdown, 1000);
}); (hours > 0) {
        countdownText = `${hours}j ${minutes}m ${seconds}d`;
    } else if (minutes > 0) {
        countdownText = `${minutes}m ${seconds}d`;
    } else {
        countdownText = `${seconds}d`;
    }
    
    countdownBadge.innerHTML = countdownText;
    
    // Change color when less than 30 minutes
    if (timeLeft < 30 * 60 * 1000) {
        countdownBadge.classList.remove('bg-warning');
        countdownBadge.classList.add('bg-danger');
    }
}

// Copy to clipboard function
function copyToClipboard(elementId, isAmount = false) {
    const element = document.getElementById(elementId);
    let textToCopy = element.innerText;
    
    // If copying amount, remove formatting
    if (isAmount) {
        textToCopy = textToCopy.replace(/[^0-9]/g, '');
    }
    
    // Create temporary input
    const tempInput = document.createElement('input');
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Show success indicator
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
}

// Fix for the form buttons
document.addEventListener('DOMContentLoaded', function() {
    // Start countdown timer
    updateCountdown();
    setInterval(updateCountdown, 1000);
    
    // Fix the simulation form
    const form = document.querySelector('.simulation-controls form');
    if (form) {
        const successBtn = form.querySelector('button[value="success"]');
        const failBtn = form.querySelector('button[value="fail"]');
        
        if (successBtn && failBtn) {
            successBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const simulateInput = document.createElement('input');
                simulateInput.type = 'hidden';
                simulateInput.name = 'simulate_payment';
                simulateInput.value = 'simulate';
                
                const resultInput = document.createElement('input');
                resultInput.type = 'hidden';
                resultInput.name = 'simulation_result';
                resultInput.value = 'success';
                
                form.appendChild(simulateInput);
                form.appendChild(resultInput);
                
                form.submit();
            });
            
            failBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const simulateInput = document.createElement('input');
                simulateInput.type = 'hidden';
                simulateInput.name = 'simulate_payment';
                simulateInput.value = 'simulate';
                
                const resultInput = document.createElement('input');
                resultInput.type = 'hidden';
                resultInput.name = 'simulation_result';
                resultInput.value = 'fail';
                
                form.appendChild(simulateInput);
                form.appendChild(resultInput);
                
                form.submit();
            });
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>