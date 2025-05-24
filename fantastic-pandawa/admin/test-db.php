<?php
// File: admin/test-db.php
// File untuk testing koneksi database dan struktur tabel

session_start();
require_once '../config/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

try {
    // Test koneksi database
    $test = $conn->query("SELECT 1");
    echo "<p>✅ Database connection: <strong>SUCCESS</strong></p>";
    
    // Test tabel users
    echo "<h3>Testing Tables:</h3>";
    
    $tables_to_check = [
        'users',
        'print_orders', 
        'cetak_orders',
        'print_order_status_history',
        'cetak_order_status_history',
        'payments',
        'payment_history',
        'settings'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Table '$table': <strong>EXISTS</strong> ($count records)</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Table '$table': <strong>MISSING</strong> - " . $e->getMessage() . "</p>";
        }
    }
    
    // Test data users
    echo "<h3>Testing User Data:</h3>";
    try {
        $stmt = $conn->query("SELECT id, name, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . $user['role'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No users found in database</p>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ Error getting users: " . $e->getMessage() . "</p>";
    }
    
    // Test data print_orders
    echo "<h3>Testing Print Orders Data:</h3>";
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM print_orders");
        $count = $stmt->fetchColumn();
        echo "<p>Print orders count: <strong>$count</strong></p>";
        
        if ($count > 0) {
            $stmt = $conn->query("SELECT o.id, o.order_number, o.status, u.name as customer_name 
                                  FROM print_orders o 
                                  LEFT JOIN users u ON o.user_id = u.id 
                                  LIMIT 5");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Order Number</th><th>Customer</th><th>Status</th></tr>";
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
                echo "<td>" . htmlspecialchars($order['customer_name'] ?? 'N/A') . "</td>";
                echo "<td>" . $order['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ Error getting print orders: " . $e->getMessage() . "</p>";
    }
    
    // Test session
    echo "<h3>Testing Session:</h3>";
    echo "<p>Session status: " . (session_status() == PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ INACTIVE") . "</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? '❌ NOT SET') . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? '❌ NOT SET') . "</p>";
    echo "<p>Is Admin: " . ($_SESSION['is_admin'] ?? '❌ NOT SET') . "</p>";
    
    // Test functions
    echo "<h3>Testing Functions:</h3>";
    
    if (function_exists('getPendingOrderCount')) {
        $pending_print = getPendingOrderCount('print');
        echo "<p>✅ getPendingOrderCount('print'): $pending_print</p>";
    } else {
        echo "<p>❌ Function getPendingOrderCount not found</p>";
    }
    
    if (function_exists('getPrintOrders')) {
        try {
            $orders = getPrintOrders('', '', '', '', 5, 0);
            echo "<p>✅ getPrintOrders(): returned " . count($orders) . " orders</p>";
        } catch (Exception $e) {
            echo "<p>❌ getPrintOrders() error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ Function getPrintOrders not found</p>";
    }
    
    // Test file paths
    echo "<h3>Testing File Paths:</h3>";
    $files_to_check = [
        '../config/db_connect.php',
        'includes/functions.php',
        'includes/auth-check.php',
        'includes/header.php',
        'includes/sidebar.php',
        'includes/footer.php',
        'process/update-print-order-status.php',
        'process/update-cetak-order-status.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            echo "<p>✅ File '$file': <strong>EXISTS</strong></p>";
        } else {
            echo "<p>❌ File '$file': <strong>MISSING</strong></p>";
        }
    }
    
    // Create missing process files if they don't exist
    echo "<h3>Creating Missing Process Files:</h3>";
    
    $process_dir = 'process';
    if (!is_dir($process_dir)) {
        if (mkdir($process_dir, 0755, true)) {
            echo "<p>✅ Created directory: $process_dir</p>";
        } else {
            echo "<p>❌ Failed to create directory: $process_dir</p>";
        }
    }
    
    // Create update-print-order-status.php
    $update_print_status_content = '<?php
session_start();
require_once \'../../config/db_connect.php\';
require_once \'../includes/functions.php\';
require_once \'../includes/auth-check.php\';

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    $_SESSION[\'error_message\'] = "Invalid request method.";
    header("Location: ../print-orders.php");
    exit;
}

$order_id = isset($_POST[\'order_id\']) ? (int)$_POST[\'order_id\'] : 0;
$new_status = isset($_POST[\'new_status\']) ? trim($_POST[\'new_status\']) : \'\';
$notes = isset($_POST[\'notes\']) ? trim($_POST[\'notes\']) : \'\';

if (empty($order_id) || empty($new_status)) {
    $_SESSION[\'error_message\'] = "Data tidak lengkap.";
    header("Location: ../print-orders.php");
    exit;
}

$allowed_statuses = [\'pending\', \'confirmed\', \'processing\', \'ready\', \'completed\', \'canceled\'];
if (!in_array($new_status, $allowed_statuses)) {
    $_SESSION[\'error_message\'] = "Status tidak valid.";
    header("Location: ../print-orders.php");
    exit;
}

try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("UPDATE print_orders SET status = :status, updated_at = NOW() WHERE id = :order_id");
    $stmt->bindParam(\':status\', $new_status);
    $stmt->bindParam(\':order_id\', $order_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("Pesanan tidak ditemukan atau tidak ada perubahan.");
    }
    
    // Create history table if not exists
    $create_table = "CREATE TABLE IF NOT EXISTS print_order_status_history (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT(11) UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL,
        notes TEXT NULL,
        changed_by INT(11) UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($create_table);
    
    $stmt = $conn->prepare("INSERT INTO print_order_status_history (order_id, status, notes, changed_by, created_at) 
                          VALUES (:order_id, :status, :notes, :changed_by, NOW())");
    $stmt->bindParam(\':order_id\', $order_id);
    $stmt->bindParam(\':status\', $new_status);
    $stmt->bindParam(\':notes\', $notes);
    $stmt->bindParam(\':changed_by\', $_SESSION[\'user_id\']);
    $stmt->execute();
    
    $conn->commit();
    $_SESSION[\'success_message\'] = "Status pesanan berhasil diperbarui.";
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION[\'error_message\'] = "Gagal memperbarui status: " . $e->getMessage();
}

if (isset($_POST[\'redirect_to\']) && $_POST[\'redirect_to\'] === \'detail\') {
    header("Location: ../print-orders-detail.php?id=" . $order_id);
} else {
    header("Location: ../print-orders.php");
}
exit;
?>';
    
    if (!file_exists('process/update-print-order-status.php')) {
        if (file_put_contents('process/update-print-order-status.php', $update_print_status_content)) {
            echo "<p>✅ Created: process/update-print-order-status.php</p>";
        } else {
            echo "<p>❌ Failed to create: process/update-print-order-status.php</p>";
        }
    }
    
    // Create update-cetak-order-status.php
    $update_cetak_status_content = str_replace('print_orders', 'cetak_orders', 
                                 str_replace('print_order_status_history', 'cetak_order_status_history',
                                 str_replace('print-orders', 'cetak-orders', $update_print_status_content)));
    
    if (!file_exists('process/update-cetak-order-status.php')) {
        if (file_put_contents('process/update-cetak-order-status.php', $update_cetak_status_content)) {
            echo "<p>✅ Created: process/update-cetak-order-status.php</p>";
        } else {
            echo "<p>❌ Failed to create: process/update-cetak-order-status.php</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ General error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='print-orders.php'>← Back to Print Orders</a></p>";
echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>