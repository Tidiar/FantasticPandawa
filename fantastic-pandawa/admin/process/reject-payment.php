<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth-check.php';

// Cek apakah ada payment_id yang dikirim
if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
    $_SESSION['error_message'] = "ID Pembayaran tidak valid.";
    header("Location: ../payment-verification.php");
    exit;
}

$payment_id = $_POST['payment_id'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validasi payment_id sebagai integer
if (!is_numeric($payment_id)) {
    $_SESSION['error_message'] = "ID Pembayaran tidak valid.";
    header("Location: ../payment-verification.php");
    exit;
}

// Validasi catatan penolakan
if (empty($notes)) {
    $_SESSION['error_message'] = "Alasan penolakan harus diisi.";
    header("Location: ../payment-detail.php?id=" . $payment_id);
    exit;
}

// Cek apakah pembayaran ada dan masih pending
$payment = getPaymentDetails($payment_id);

if (!$payment) {
    $_SESSION['error_message'] = "Pembayaran tidak ditemukan.";
    header("Location: ../payment-verification.php");
    exit;
}

if ($payment['payment_status'] !== 'pending') {
    $_SESSION['error_message'] = "Pembayaran sudah diproses sebelumnya.";
    header("Location: ../payment-detail.php?id=" . $payment_id);
    exit;
}

// Tolak pembayaran
try {
    $conn->beginTransaction();
    
    // Update status pembayaran menjadi failed
    $stmt = $conn->prepare("UPDATE payments SET 
        payment_status = 'failed', 
        notes = ?,
        updated_at = NOW() 
        WHERE id = ?");
    $stmt->execute([$notes, $payment_id]);
    
    // Update status pesanan terkait
    if ($payment['order_type'] === 'print') {
        $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'failed' WHERE id = ?");
        $stmt->execute([$payment['order_id']]);
        
        // Tambahkan history untuk print order
        $stmt = $conn->prepare("INSERT INTO print_order_history (order_id, status, notes, changed_by) VALUES (?, 'pending', ?, ?)");
        $stmt->execute([$payment['order_id'], 'Pembayaran ditolak - ' . $notes, $_SESSION['user_id']]);
    } else {
        $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'failed' WHERE id = ?");
        $stmt->execute([$payment['order_id']]);
        
        // Tambahkan history untuk cetak order
        $stmt = $conn->prepare("INSERT INTO cetak_order_history (order_id, status, notes, changed_by) VALUES (?, 'pending', ?, ?)");
        $stmt->execute([$payment['order_id'], 'Pembayaran ditolak - ' . $notes, $_SESSION['user_id']]);
    }
    
    // Tambahkan riwayat pembayaran
    $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'failed', ?, ?)");
    $stmt->execute([$payment_id, 'Pembayaran ditolak: ' . $notes, $_SESSION['user_id']]);
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Pembayaran dengan kode {$payment['payment_code']} berhasil ditolak.";
    
    // Redirect kembali ke halaman yang sesuai
    if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'detail') {
        header("Location: ../payment-detail.php?id=" . $payment_id);
    } else {
        header("Location: ../payment-verification.php");
    }
    exit;
    
} catch (PDOException $e) {
    $conn->rollBack();
    
    $_SESSION['error_message'] = "Gagal menolak pembayaran: " . $e->getMessage();
    header("Location: ../payment-detail.php?id=" . $payment_id);
    exit;
}
?>