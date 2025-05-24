<?php
// File: update-cetak-order-price.php
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function logDebug($message, $data = null) {
    $debug_file = '../logs/cetak-price-update-' . date('Y-m-d') . '.log';
    $log_message = date('Y-m-d H:i:s') . " - $message";
    
    if ($data !== null) {
        $log_message .= " - " . print_r($data, true);
    }
    
    // Ensure the logs directory exists
    if (!file_exists('../logs')) {
        mkdir('../logs', 0777, true);
    }
    
    // Write to log file
    error_log($log_message . "\n", 3, $debug_file);
}

// Log initial request
logDebug("Price update request started", [
    'POST' => $_POST,
    'SESSION' => $_SESSION
]);

// Check if required parameters exist
if (!isset($_POST['order_id']) || !isset($_POST['price'])) {
    logDebug("Error: Missing parameters");
    $_SESSION['error_message'] = "Kesalahan: Parameter yang diperlukan tidak ada.";
    header("Location: cetak-orders.php");
    exit;
}

// Get parameters
$order_id = (int)$_POST['order_id'];
$price = (float)$_POST['price'];

// Validate price
if ($price < 0) {
    logDebug("Error: Invalid price", $price);
    $_SESSION['error_message'] = "Harga tidak boleh negatif.";
    header("Location: cetak-orders-detail.php?id=" . $order_id);
    exit;
}

logDebug("Processing price update", [
    'order_id' => $order_id,
    'price' => $price
]);

try {
    // Update price
    $stmt = $conn->prepare("UPDATE cetak_orders SET price = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$price, $order_id]);
    $updated_rows = $stmt->rowCount();
    
    logDebug("Price updated", [
        'success' => $result,
        'rows_updated' => $updated_rows
    ]);
    
    // Set success message
    $_SESSION['success_message'] = "Harga pesanan berhasil diperbarui menjadi Rp " . number_format($price, 0, ',', '.');
    
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    $_SESSION['error_message'] = "Gagal memperbarui harga pesanan: " . $e->getMessage();
}

// Redirect back to detail page
header("Location: cetak-orders-detail.php?id=" . $order_id);
exit;
?>