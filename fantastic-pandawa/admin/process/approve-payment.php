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
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : 'Pembayaran disetujui oleh admin';

// Validasi payment_id sebagai integer
if (!is_numeric($payment_id)) {
    $_SESSION['error_message'] = "ID Pembayaran tidak valid.";
    header("Location: ../payment-verification.php");
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

if (!$payment['payment_proof']) {
    $_SESSION['error_message'] = "Belum ada bukti pembayaran yang diupload.";
    header("Location: ../payment-detail.php?id=" . $payment_id);
    exit;
}

// Setujui pembayaran
try {
    $conn->beginTransaction();
    
    // PERBAIKAN: Update status pembayaran menjadi paid (tanpa kolom yang tidak ada)
    $stmt = $conn->prepare("UPDATE payments SET 
        payment_status = 'paid', 
        notes = ?,
        updated_at = NOW() 
        WHERE id = ?");
    $stmt->execute([$notes, $payment_id]);
    
    // Update status pesanan terkait
    if ($payment['order_type'] === 'print') {
        $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
        $stmt->execute([$payment['order_id']]);
        
        // Tambahkan history untuk print order
        $stmt = $conn->prepare("INSERT INTO print_order_history (order_id, status, notes, changed_by, created_at) VALUES (?, 'confirmed', ?, ?, NOW())");
        $stmt->execute([$payment['order_id'], 'Pesanan dikonfirmasi - Pembayaran disetujui', $_SESSION['user_id']]);
    } else {
        $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
        $stmt->execute([$payment['order_id']]);
        
        // Tambahkan history untuk cetak order
        $stmt = $conn->prepare("INSERT INTO cetak_order_history (order_id, status, notes, changed_by, created_at) VALUES (?, 'confirmed', ?, ?, NOW())");
        $stmt->execute([$payment['order_id'], 'Pesanan dikonfirmasi - Pembayaran disetujui', $_SESSION['user_id']]);
    }
    
    // Tambahkan riwayat pembayaran
    $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by, created_at) VALUES (?, 'paid', ?, ?, NOW())");
    $stmt->execute([$payment_id, $notes, $_SESSION['user_id']]);
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Pembayaran dengan kode {$payment['payment_code']} berhasil disetujui.";
    
    // Redirect kembali ke halaman yang sesuai
    if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'detail') {
        header("Location: ../payment-detail.php?id=" . $payment_id);
    } else {
        header("Location: ../payment-verification.php");
    }
    exit;
    
} catch (PDOException $e) {
    $conn->rollBack();
    
    $_SESSION['error_message'] = "Gagal menyetujui pembayaran: " . $e->getMessage();
    header("Location: ../payment-detail.php?id=" . $payment_id);
    exit;
}
?>