<?php
// File: assign-cetak-order-staff.php
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function logDebug($message, $data = null) {
    $debug_file = '../logs/cetak-staff-assign-' . date('Y-m-d') . '.log';
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
logDebug("Staff assignment request started", [
    'POST' => $_POST,
    'SESSION' => $_SESSION
]);

// Check if required parameters exist
if (!isset($_POST['order_id']) || !isset($_POST['staff_id'])) {
    logDebug("Error: Missing parameters");
    $_SESSION['error_message'] = "Kesalahan: Parameter yang diperlukan tidak ada.";
    header("Location: cetak-orders.php");
    exit;
}

// Get parameters
$order_id = (int)$_POST['order_id'];
$staff_id = $_POST['staff_id'];

logDebug("Processing staff assignment", [
    'order_id' => $order_id,
    'staff_id' => $staff_id
]);

try {
    // Update assigned staff
    $stmt = $conn->prepare("UPDATE cetak_orders SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$staff_id ? $staff_id : null, $order_id]);
    $updated_rows = $stmt->rowCount();
    
    logDebug("Staff assignment updated", [
        'success' => $result,
        'rows_updated' => $updated_rows
    ]);
    
    // Get staff name for the message
    $staff_name = "Staff";
    if (!empty($staff_id)) {
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($staff) {
            $staff_name = $staff['name'];
        }
    }
    
    // Set success message
    if (empty($staff_id)) {
        $_SESSION['success_message'] = "Penugasan staff berhasil dihapus.";
    } else {
        $_SESSION['success_message'] = "Pesanan berhasil ditugaskan kepada " . $staff_name . ".";
    }
    
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    $_SESSION['error_message'] = "Gagal menugaskan pesanan kepada staff: " . $e->getMessage();
}

// Redirect back to detail page
header("Location: cetak-orders-detail.php?id=" . $order_id);
exit;
?>