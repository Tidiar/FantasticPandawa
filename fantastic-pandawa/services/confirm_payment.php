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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Akses tidak valid";
    header("Location: ../user/orders.php");
    exit;
}

$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
$payment_notes = isset($_POST['payment_notes']) ? cleanInput($_POST['payment_notes']) : '';

if (!$payment_id) {
    $_SESSION['error_message'] = "Payment ID tidak valid";
    header("Location: ../user/orders.php");
    exit;
}

// Get payment details
$stmt = $conn->prepare("SELECT p.*, 
    CASE 
        WHEN p.order_type = 'print' THEN po.order_number 
        ELSE co.order_number 
    END as order_number,
    CASE 
        WHEN p.order_type = 'print' THEN po.status 
        ELSE co.status 
    END as order_status
    FROM payments p 
    LEFT JOIN print_orders po ON p.order_id = po.id AND p.order_type = 'print'
    LEFT JOIN cetak_orders co ON p.order_id = co.id AND p.order_type = 'cetak'
    WHERE p.id = ? AND p.user_id = ?");
$stmt->execute([$payment_id, $_SESSION['user_id']]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    $_SESSION['error_message'] = "Pembayaran tidak ditemukan";
    header("Location: ../user/orders.php");
    exit;
}

// Check if payment is still pending
if ($payment['payment_status'] !== 'pending') {
    $_SESSION['warning_message'] = "Status pembayaran: " . ucfirst($payment['payment_status']);
    header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
    exit;
}

// Check if payment is expired
$now = new DateTime();
$expired_at = new DateTime($payment['expired_at']);
if ($now > $expired_at) {
    // Update payment status to expired
    try {
        $conn->beginTransaction();
        
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

// Handle file upload
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = "Silakan upload bukti pembayaran";
    header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
    exit;
}

$proof_file = $_FILES['payment_proof'];

// Validate file
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
$max_size = 5 * 1024 * 1024; // 5MB

$file_validation = validateUploadFile($proof_file, $allowed_types, $max_size);

if (!$file_validation['success']) {
    $_SESSION['error_message'] = $file_validation['message'];
    header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
    exit;
}

try {
    $conn->beginTransaction();
    
    // Upload proof file
    $upload_dir = '../uploads/payment-proofs/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($proof_file['name'], PATHINFO_EXTENSION);
    $proof_filename = 'proof_' . $payment['payment_code'] . '_' . time() . '.' . $file_extension;
    $proof_file_path = $upload_dir . $proof_filename;
    
    if (!move_uploaded_file($proof_file['tmp_name'], $proof_file_path)) {
        throw new Exception('Gagal upload bukti pembayaran');
    }
    
    // Update payment with proof
    $stmt = $conn->prepare("UPDATE payments SET 
        payment_proof = ?, 
        notes = ?, 
        payment_status = 'pending',
        updated_at = NOW() 
        WHERE id = ?");
    
    $stmt->execute([
        'uploads/payment-proofs/' . $proof_filename,
        $payment_notes,
        $payment_id
    ]);
    
    // Add to payment history
    $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'pending', ?, ?)");
    $stmt->execute([
        $payment_id, 
        'Bukti pembayaran uploaded: ' . $proof_file['name'] . ($payment_notes ? '. Catatan: ' . $payment_notes : ''),
        $_SESSION['user_id']
    ]);
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Bukti pembayaran berhasil diupload. Silakan tunggu konfirmasi dari admin dalam 1x24 jam.";
    
    // Redirect to payment status page
    header("Location: payment_status.php?payment_id={$payment_id}");
    exit;
    
} catch (Exception $e) {
    $conn->rollBack();
    
    // Delete uploaded file if exists
    if (isset($proof_file_path) && file_exists($proof_file_path)) {
        unlink($proof_file_path);
    }
    
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: payment.php?order_id={$payment['order_id']}&type={$payment['order_type']}&payment_id={$payment_id}");
    exit;
}
?>