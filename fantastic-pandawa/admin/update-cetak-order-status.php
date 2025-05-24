<?php
// Update cetak order status directly
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function logDebug($message, $data = null) {
    $debug_file = '../logs/cetak-status-update-' . date('Y-m-d') . '.log';
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

// Debug POST data
logDebug("POST data received", $_POST);

// Check if required parameters exist
if (!isset($_POST['order_id'])) {
    logDebug("Error: Missing order_id parameter");
    $_SESSION['error_message'] = "Kesalahan: Parameter order_id tidak ada.";
    header("Location: cetak-orders.php");
    exit;
}

if (!isset($_POST['new_status'])) {
    logDebug("Error: Missing new_status parameter");
    $_SESSION['error_message'] = "Kesalahan: Parameter new_status tidak ada.";
    header("Location: cetak-orders.php");
    exit;
}

// Get parameters
$order_id = (int)$_POST['order_id'];
$new_status = $_POST['new_status'];
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'detail';

logDebug("Processing update", [
    'order_id' => $order_id,
    'new_status' => $new_status,
    'notes' => $notes,
    'user_id' => $user_id,
    'redirect_to' => $redirect_to
]);

try {
    // Begin transaction
    $conn->beginTransaction();
    logDebug("Transaction started");
    
    // Update order status
    $stmt = $conn->prepare("UPDATE cetak_orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result1 = $stmt->execute([$new_status, $order_id]);
    $updated_rows = $stmt->rowCount();
    
    logDebug("Order status updated", [
        'success' => $result1,
        'rows_updated' => $updated_rows
    ]);
    
    // Add to history if table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'cetak_order_status_history'");
    if ($check_table->rowCount() > 0) {
        $stmt = $conn->prepare("INSERT INTO cetak_order_status_history (order_id, status, notes, changed_by, created_at) 
                                VALUES (?, ?, ?, ?, NOW())");
        $result2 = $stmt->execute([$order_id, $new_status, $notes, $user_id]);
        
        logDebug("History record created", [
            'success' => $result2,
            'rows_inserted' => $stmt->rowCount()
        ]);
    } else {
        logDebug("Status history table does not exist, creating it");
        
        // Create history table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS cetak_order_status_history (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes TEXT NULL,
            changed_by INT(11) UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_table);
        
        // Now insert the record
        $stmt = $conn->prepare("INSERT INTO cetak_order_status_history (order_id, status, notes, changed_by, created_at) 
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
    $_SESSION['success_message'] = "Status pesanan berhasil diperbarui menjadi " . translateStatus($new_status) . ".";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollBack();
    logDebug("ERROR: " . $e->getMessage());
    $_SESSION['error_message'] = "Gagal memperbarui status pesanan: " . $e->getMessage();
}

// Redirect back to appropriate page
if ($redirect_to == 'list') {
    header("Location: cetak-orders.php");
} else {
    header("Location: cetak-orders-detail.php?id=" . $order_id);
}
exit;
?>