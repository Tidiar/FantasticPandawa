<?php
// File: admin/process/delete-print-order.php
session_start();
require_once '../../config/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth-check.php';

// Cek apakah request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Metode request tidak valid.";
    header("Location: ../print-orders.php");
    exit;
}

// Ambil order_id dari form
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

// Validasi input
if (empty($order_id)) {
    $_SESSION['error_message'] = "ID Pesanan tidak valid.";
    header("Location: ../print-orders.php");
    exit;
}

try {
    $conn->beginTransaction();
    
    // Dapatkan informasi pesanan terlebih dahulu
    $stmt = $conn->prepare("SELECT order_number, file_path FROM print_orders WHERE id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Pesanan tidak ditemukan.");
    }
    
    // Hapus file jika ada
    if (!empty($order['file_path']) && file_exists('../../' . $order['file_path'])) {
        unlink('../../' . $order['file_path']);
    }
    
    // Hapus riwayat status terlebih dahulu
    $stmt = $conn->prepare("DELETE FROM print_order_status_history WHERE order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Hapus pembayaran terkait jika ada
    $stmt = $conn->prepare("DELETE FROM payments WHERE order_id = :order_id AND order_type = 'print'");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Hapus pesanan
    $stmt = $conn->prepare("DELETE FROM print_orders WHERE id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("Gagal menghapus pesanan.");
    }
    
    $conn->commit();
    $_SESSION['success_message'] = "Pesanan print " . $order['order_number'] . " berhasil dihapus.";
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error_message'] = "Gagal menghapus pesanan: " . $e->getMessage();
}

// Redirect kembali ke halaman daftar pesanan
header("Location: ../print-orders.php");
exit;
?>