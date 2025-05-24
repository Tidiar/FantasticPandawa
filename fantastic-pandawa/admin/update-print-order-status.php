<?php
// Update print order status directly
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function logDebug($message, $data = null) {
    $debug_file = '../logs/status-update-' . date('Y-m-d') . '.log';
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
logDebug("Status update request started", [
    'POST' => $_POST,
    'GET' => $_GET,
    'SESSION' => $_SESSION
]);

// Check if required parameters exist
if (!isset($_POST['order_id']) || !isset($_POST['new_status'])) {
    logDebug("Error: Missing parameters");
    $_SESSION['error_message'] = "Kesalahan: Parameter yang diperlukan tidak ada.";
    header("Location: print-orders.php");
    exit;
}

// Get parameters
$order_id = (int)$_POST['order_id'];
$new_status = $_POST['new_status'];
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

logDebug("Processing update", [
    'order_id' => $order_id,
    'new_status' => $new_status,
    'notes' => $notes,
    'user_id' => $user_id
]);

try {
    // Begin transaction
    $conn->beginTransaction();
    logDebug("Transaction started");
    
    // Update order status
    $stmt = $conn->prepare("UPDATE print_orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result1 = $stmt->execute([$new_status, $order_id]);
    $updated_rows = $stmt->rowCount();
    
    logDebug("Order status updated", [
        'success' => $result1,
        'rows_updated' => $updated_rows
    ]);
    
    // Add to history if table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'print_order_status_history'");
    if ($check_table->rowCount() > 0) {
        $stmt = $conn->prepare("INSERT INTO print_order_status_history (order_id, status, notes, changed_by, created_at) 
                                VALUES (?, ?, ?, ?, NOW())");
        $result2 = $stmt->execute([$order_id, $new_status, $notes, $user_id]);
        
        logDebug("History record created", [
            'success' => $result2,
            'rows_inserted' => $stmt->rowCount()
        ]);
    } else {
        logDebug("Status history table does not exist, creating it");
        
        // Create history table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS print_order_status_history (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes TEXT NULL,
            changed_by INT(11) UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_table);
        
        // Now insert the record
        $stmt = $conn->prepare("INSERT INTO print_order_status_history (order_id, status, notes, changed_by, created_at) 
                                VALUES (?, ?, ?, ?, NOW())");
        $result2 = $stmt->execute([$order_id, $new_status, $notes, $user_id]);
        
        logDebug("Created table and added history record", [
            'success' => $result2
        ]);
    }
    
    // Commit the transaction
    $conn->commit();
    logDebug("Transaction committed successfully");
    
    // Set success message
    $_SESSION['success_message'] = "Status pesanan berhasil diperbarui menjadi " . ucfirst($new_status) . ".";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollBack();
    logDebug("ERROR: " . $e->getMessage());
    $_SESSION['error_message'] = "Gagal memperbarui status pesanan: " . $e->getMessage();
}

// Redirect back to detail page
header("Location: print-orders-detail.php?id=" . $order_id);
exit;
?>