<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses tidak diizinkan']);
    exit;
}

// Cek apakah request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_number = isset($_POST['order_number']) ? cleanInput($_POST['order_number']) : '';
$order_type = isset($_POST['order_type']) ? cleanInput($_POST['order_type']) : '';
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

// Validasi input
if (empty($order_number) || empty($order_type) || $order_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
    exit;
}

try {
    // Mulai transaksi
    $conn->beginTransaction();
    
    // Periksa apakah pesanan ada dan milik user
    if ($order_type === 'print') {
        $query = "SELECT * FROM print_orders WHERE id = ? AND user_id = ? AND order_number = ? AND status = 'pending'";
    } else {
        $query = "SELECT * FROM cetak_orders WHERE id = ? AND user_id = ? AND order_number = ? AND status = 'pending'";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$order_id, $user_id, $order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Pesanan tidak ditemukan atau tidak dapat dibatalkan');
    }
    
    // Cek apakah pesanan sudah dibayar atau sedang diproses
    if ($order['payment_status'] === 'paid' || $order['payment_status'] === 'verified') {
        throw new Exception('Pesanan tidak dapat dibatalkan karena pembayaran sudah diverifikasi');
    }
    
    if (in_array($order['status'], ['confirmed', 'processing', 'ready', 'completed'])) {
        throw new Exception('Pesanan tidak dapat dibatalkan karena sudah diproses');
    }
    
    // STEP 1: Update status pesanan menjadi canceled
    if ($order_type === 'print') {
        $update_order_query = "UPDATE print_orders SET 
            status = 'canceled', 
            payment_status = 'failed',
            updated_at = NOW() 
            WHERE id = ?";
    } else {
        $update_order_query = "UPDATE cetak_orders SET 
            status = 'canceled', 
            payment_status = 'failed',
            updated_at = NOW() 
            WHERE id = ?";
    }
    
    $stmt = $conn->prepare($update_order_query);
    $result = $stmt->execute([$order_id]);
    
    if (!$result) {
        throw new Exception('Gagal membatalkan pesanan');
    }
    
    // STEP 2: Update payment status jika ada payment_id
    if (!empty($order['payment_id'])) {
        try {
            // Cek apakah tabel payments ada
            $stmt = $conn->prepare("SHOW TABLES LIKE 'payments'");
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update payment status menjadi canceled
                $update_payment_query = "UPDATE payments SET 
                    status = 'canceled',
                    notes = CONCAT(COALESCE(notes, ''), '\nPesanan dibatalkan oleh pelanggan pada ', NOW()),
                    updated_at = NOW()
                    WHERE id = ?";
                
                $stmt = $conn->prepare($update_payment_query);
                $payment_updated = $stmt->execute([$order['payment_id']]);
                
                if ($payment_updated) {
                    // Log payment history jika tabel ada
                    try {
                        $stmt = $conn->prepare("SHOW TABLES LIKE 'payment_history'");
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            $log_payment_query = "INSERT INTO payment_history 
                                (payment_id, status, notes, changed_by, created_at) 
                                VALUES (?, 'failed', 'Pembayaran dibatalkan karena pesanan dibatalkan oleh pelanggan', ?, NOW())";
                            
                            $stmt = $conn->prepare($log_payment_query);
                            $stmt->execute([$order['payment_id'], $user_id]);
                        }
                    } catch (Exception $e) {
                        // Log error tapi jangan gagalkan transaksi
                        error_log("Error logging payment history: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            // Log error tapi jangan gagalkan transaksi utama
            error_log("Error updating payment status: " . $e->getMessage());
        }
    }
    
    // STEP 3: Catat log pembatalan pesanan
    try {
        $history_table = $order_type . '_order_history';
        
        // Cek apakah tabel history ada
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$history_table]);
        
        if ($stmt->rowCount() > 0) {
            $log_query = "INSERT INTO {$history_table} 
                (order_id, status, notes, changed_by, created_at) 
                VALUES (?, 'canceled', 'Pesanan dibatalkan oleh pelanggan', ?, NOW())";
            
            $stmt = $conn->prepare($log_query);
            $stmt->execute([$order_id, $user_id]);
        }
    } catch (Exception $e) {
        // Log error tapi jangan gagalkan transaksi
        error_log("Error logging order history: " . $e->getMessage());
    }
    
    // STEP 4: Hapus file yang diupload jika ada (untuk print orders)
    if ($order_type === 'print' && !empty($order['file_path'])) {
        try {
            $file_path = '../' . $order['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        } catch (Exception $e) {
            // Log error tapi jangan gagalkan transaksi
            error_log("Error deleting file: " . $e->getMessage());
        }
    }
    
    // STEP 5: Hapus file design jika ada (untuk cetak orders)
    if ($order_type === 'cetak' && !empty($order['design_file_path'])) {
        try {
            $file_path = '../' . $order['design_file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        } catch (Exception $e) {
            // Log error tapi jangan gagalkan transaksi
            error_log("Error deleting design file: " . $e->getMessage());
        }
    }
    
    // Commit transaksi
    $conn->commit();
    
    // Siapkan response data
    $response_data = [
        'success' => true,
        'message' => 'Pesanan berhasil dibatalkan',
        'order_number' => $order_number,
        'order_type' => $order_type,
        'canceled_at' => date('Y-m-d H:i:s')
    ];
    
    // Tambahkan informasi payment jika ada
    if (!empty($order['payment_id'])) {
        $response_data['payment_canceled'] = true;
        $response_data['payment_id'] = $order['payment_id'];
    }
    
    // Set session message untuk redirect
    $_SESSION['success_message'] = "Pesanan #{$order_number} berhasil dibatalkan";
    
    // Kirim respons sukses
    header('Content-Type: application/json');
    echo json_encode($response_data);
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    $conn->rollBack();
    
    // Log error untuk debugging
    error_log("Cancel order error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    
    // Kirim respons error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_code' => 'CANCEL_ORDER_FAILED'
    ]);
}
?>