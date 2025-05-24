<?php
// File: admin/debug-order.php
// Tool untuk debug masalah order detail

session_start();
require_once '../config/db_connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Order Detail Debug Tool</h2>";
echo "<p>Tool ini membantu mendiagnosis masalah 'Pesanan tidak ditemukan'</p>";

// Get order ID from URL
$order_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$order_id) {
    echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>⚠️ Tidak ada ID pesanan yang diberikan</strong><br>";
    echo "Silakan akses dengan: <code>debug-order.php?id=NOMOR_ID</code>";
    echo "</div>";
    
    // Tampilkan semua order yang ada
    try {
        $stmt = $conn->query("SELECT id, order_number, status, created_at FROM print_orders ORDER BY id DESC LIMIT 10");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orders) > 0) {
            echo "<h3>📋 Daftar 10 Pesanan Terakhir:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Order Number</th><th>Status</th><th>Created</th><th>Test Link</th></tr>";
            
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>{$order['id']}</td>";
                echo "<td>{$order['order_number']}</td>";
                echo "<td>{$order['status']}</td>";
                echo "<td>{$order['created_at']}</td>";
                echo "<td><a href='debug-order.php?id={$order['id']}' style='color: blue;'>🔍 Debug ID {$order['id']}</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ Tidak ada pesanan print di database</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error getting orders: " . $e->getMessage() . "</p>";
    }
    
    exit;
}

echo "<h3>🎯 Testing Order ID: <strong style='color: blue;'>$order_id</strong></h3>";

// Test 1: Basic validation
echo "<div style='background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📝 Test 1: Basic Validation</h4>";

if (!is_numeric($order_id)) {
    echo "❌ <strong>GAGAL:</strong> Order ID bukan angka: '$order_id'<br>";
    echo "💡 <strong>Solusi:</strong> Pastikan menggunakan ID yang valid (angka)<br>";
} else {
    echo "✅ <strong>LULUS:</strong> Order ID adalah angka: $order_id<br>";
    $order_id = (int)$order_id;
}
echo "</div>";

// Test 2: Database connection
echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🔌 Test 2: Database Connection</h4>";

try {
    $test = $conn->query("SELECT 1");
    echo "✅ <strong>LULUS:</strong> Database connection OK<br>";
} catch (Exception $e) {
    echo "❌ <strong>GAGAL:</strong> Database connection error: " . $e->getMessage() . "<br>";
    echo "💡 <strong>Solusi:</strong> Periksa konfigurasi database di config/db_connect.php<br>";
    exit;
}
echo "</div>";

// Test 3: Table exists
echo "<div style='background: #fff3e0; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🗃️ Test 3: Table Structure</h4>";

try {
    // Check if print_orders table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'print_orders'");
    if ($stmt->rowCount() > 0) {
        echo "✅ <strong>LULUS:</strong> Tabel 'print_orders' ada<br>";
        
        // Check table structure
        $stmt = $conn->query("DESCRIBE print_orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 <strong>Kolom tabel:</strong> ";
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . "), ";
        }
        echo "<br>";
        
    } else {
        echo "❌ <strong>GAGAL:</strong> Tabel 'print_orders' tidak ada<br>";
        echo "💡 <strong>Solusi:</strong> Buat tabel print_orders atau import database<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ <strong>GAGAL:</strong> Error checking table: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 4: Order exists
echo "<div style='background: #fce4ec; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🔍 Test 4: Order Existence</h4>";

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "✅ <strong>LULUS:</strong> Order dengan ID $order_id ditemukan<br>";
    } else {
        echo "❌ <strong>GAGAL:</strong> Order dengan ID $order_id tidak ditemukan<br>";
        
        // Show available IDs
        $stmt = $conn->query("SELECT id, order_number FROM print_orders ORDER BY id DESC LIMIT 5");
        $available = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($available) > 0) {
            echo "📋 <strong>ID yang tersedia:</strong> ";
            foreach ($available as $avail) {
                echo "<a href='debug-order.php?id={$avail['id']}' style='color: blue;'>{$avail['id']} ({$avail['order_number']})</a>, ";
            }
            echo "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ <strong>GAGAL:</strong> Error checking order existence: " . $e->getMessage() . "<br>";
    exit;
}
echo "</div>";

// Test 5: Full order data
echo "<div style='background: #f3e5f5; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>📊 Test 5: Full Order Data</h4>";

try {
    $stmt = $conn->prepare("SELECT 
        o.*,
        u.name as customer_name, 
        u.email as customer_email, 
        u.phone as customer_phone,
        s.name as assigned_to_name
        FROM print_orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users s ON o.assigned_to = s.id
        WHERE o.id = ?");
    
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "✅ <strong>LULUS:</strong> Data order lengkap berhasil diambil<br>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f8f9fa;'><th>Field</th><th>Value</th></tr>";
        
        foreach ($order as $key => $value) {
            $displayValue = $value === null ? '<em style="color: #999;">NULL</em>' : htmlspecialchars($value);
            echo "<tr><td><strong>$key</strong></td><td>$displayValue</td></tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ <strong>GAGAL:</strong> Data order tidak dapat diambil dengan JOIN<br>";
        
        // Try simple query
        $stmt = $conn->prepare("SELECT * FROM print_orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $simple_order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($simple_order) {
            echo "⚠️ <strong>PERINGATAN:</strong> Order ada tapi JOIN gagal. Kemungkinan masalah di relasi users<br>";
            echo "<pre>" . print_r($simple_order, true) . "</pre>";
        }
    }
} catch (Exception $e) {
    echo "❌ <strong>GAGAL:</strong> Error getting full order data: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 6: User data relation
echo "<div style='background: #e1f5fe; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>👤 Test 6: User Data Relation</h4>";

try {
    // Get user_id from order
    $stmt = $conn->prepare("SELECT user_id FROM print_orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $user_id = $stmt->fetchColumn();
    
    if ($user_id) {
        echo "📋 <strong>User ID dari order:</strong> $user_id<br>";
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ <strong>LULUS:</strong> User ditemukan - {$user['name']} ({$user['email']})<br>";
        } else {
            echo "⚠️ <strong>PERINGATAN:</strong> User dengan ID $user_id tidak ditemukan<br>";
            echo "💡 <strong>Dampak:</strong> customer_name akan NULL di JOIN query<br>";
        }
    } else {
        echo "⚠️ <strong>PERINGATAN:</strong> Order tidak memiliki user_id<br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>GAGAL:</strong> Error checking user relation: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test 7: Functions test
echo "<div style='background: #fff9c4; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>⚙️ Test 7: Functions Test</h4>";

if (file_exists('includes/functions.php')) {
    echo "✅ <strong>LULUS:</strong> File functions.php ada<br>";
    
    require_once 'includes/functions.php';
    
    // Test specific function
    if (function_exists('getPrintOrderDetails')) {
        try {
            $function_result = getPrintOrderDetails($order_id);
            if ($function_result) {
                echo "✅ <strong>LULUS:</strong> Function getPrintOrderDetails() bekerja<br>";
            } else {
                echo "❌ <strong>GAGAL:</strong> Function getPrintOrderDetails() return false<br>";
            }
        } catch (Exception $e) {
            echo "❌ <strong>GAGAL:</strong> Function getPrintOrderDetails() error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ <strong>GAGAL:</strong> Function getPrintOrderDetails() tidak ada<br>";
    }
} else {
    echo "❌ <strong>GAGAL:</strong> File includes/functions.php tidak ditemukan<br>";
}
echo "</div>";

// Test 8: Session test
echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "<h4>🔐 Test 8: Session & Auth</h4>";

echo "📋 <strong>Session status:</strong> " . (session_status() == PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "<br>";
echo "📋 <strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "📋 <strong>Is Admin:</strong> " . ($_SESSION['is_admin'] ?? 'Not set') . "<br>";

if (file_exists('includes/auth-check.php')) {
    echo "✅ <strong>LULUS:</strong> File auth-check.php ada<br>";
} else {
    echo "❌ <strong>GAGAL:</strong> File includes/auth-check.php tidak ditemukan<br>";
}
echo "</div>";

// Recommendations
echo "<div style='background: #f0f8ff; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #007bff;'>";
echo "<h3>💡 Rekomendasi Perbaikan</h3>";

echo "<ol>";
echo "<li><strong>Jika order tidak ditemukan:</strong> Pastikan ID yang digunakan benar dan data ada di database</li>";
echo "<li><strong>Jika JOIN gagal:</strong> Periksa relasi antara print_orders dan users table</li>";
echo "<li><strong>Jika function error:</strong> Pastikan file functions.php lengkap dan tidak ada syntax error</li>";
echo "<li><strong>Jika auth error:</strong> Pastikan sudah login sebagai admin</li>";
echo "<li><strong>Debug mode:</strong> Tambahkan ?debug=1 di URL untuk melihat detail error</li>";
echo "</ol>";

echo "<h4>🔗 Quick Links:</h4>";
echo "<ul>";
echo "<li><a href='print-orders.php' style='color: blue;'>← Kembali ke Print Orders</a></li>";
echo "<li><a href='print-orders-detail.php?id=$order_id' style='color: blue;'>🔍 Test Print Order Detail</a></li>";
echo "<li><a href='print-orders-detail.php?id=$order_id&debug=1' style='color: blue;'>🐛 Print Order Detail + Debug</a></li>";
echo "<li><a href='index.php' style='color: blue;'>🏠 Dashboard</a></li>";
echo "</ul>";
echo "</div>";

// Create sample data if no orders exist
echo "<div style='background: #fffbf0; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ff9800;'>";
echo "<h3>🛠️ Quick Fix Options</h3>";

if (isset($_GET['create_sample']) && $_GET['create_sample'] == '1') {
    try {
        // Create sample order
        $stmt = $conn->prepare("INSERT INTO print_orders (user_id, order_number, original_filename, file_path, copies, paper_size, print_color, paper_type, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $sample_data = [
            1, // user_id (pastikan user dengan ID 1 ada)
            'PRT' . date('Ymd') . rand(1000, 9999),
            'sample_document.pdf',
            'uploads/print/sample_document.pdf',
            2,
            'A4',
            'BW',
            'Regular',
            1000,
            'pending'
        ];
        
        $stmt->execute($sample_data);
        $new_id = $conn->lastInsertId();
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Sample order created successfully!</strong><br>";
        echo "📋 <strong>New Order ID:</strong> $new_id<br>";
        echo "<a href='debug-order.php?id=$new_id' style='color: blue;'>🔍 Test New Order</a>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Failed to create sample order:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<p><strong>Jika tidak ada data order untuk testing:</strong></p>";
echo "<a href='debug-order.php?create_sample=1' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>🆕 Create Sample Order</a>";

echo "</div>";

// Footer
echo "<hr>";
echo "<p style='text-align: center; color: #666; margin-top: 30px;'>";
echo "🔧 Debug Tool | Fantastic Pandawa Admin Panel<br>";
echo "<small>Current Time: " . date('Y-m-d H:i:s') . " | PHP Version: " . PHP_VERSION . "</small>";
echo "</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}

h2, h3, h4 {
    color: #333;
}

table {
    width: 100%;
    margin: 10px 0;
}

table th {
    background-color: #f8f9fa;
    padding: 8px;
    text-align: left;
    border: 1px solid #dee2e6;
}

table td {
    padding: 8px;
    border: 1px solid #dee2e6;
    vertical-align: top;
}

code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

pre {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 12px;
}

a {
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>