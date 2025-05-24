<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
requireLogin('../auth/login.php');

$user_id = $_SESSION['user_id'];
$settings = getSettings();

// Ambil parameter order number
$order_number = isset($_GET['order_number']) ? cleanInput($_GET['order_number']) : '';

if (empty($order_number)) {
    $_SESSION['error_message'] = "Nomor pesanan tidak valid";
    header('Location: orders.php');
    exit;
}

// Get order details
try {
    // Cek apakah pesanan adalah print atau cetak
    $stmt = $conn->prepare("SELECT 'print' as order_type, id FROM print_orders WHERE order_number = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $stmt = $conn->prepare("SELECT 'cetak' as order_type, id FROM cetak_orders WHERE order_number = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$order_number, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$order) {
        $_SESSION['error_message'] = "Pesanan tidak ditemukan";
        header('Location: orders.php');
        exit;
    }
    
    $order_type = $order['order_type'];
    $order_id = $order['id'];
    
    // Get order details
    if ($order_type == 'print') {
        $stmt = $conn->prepare("
            SELECT 
                o.*,
                p.payment_method,
                IFNULL(p.payment_date, o.updated_at) as payment_date,
                IFNULL(p.id, 0) as payment_id
            FROM print_orders o
            LEFT JOIN payments p ON o.payment_id = p.id
            WHERE o.id = ? AND o.user_id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT 
                o.*,
                p.payment_method,
                IFNULL(p.payment_date, o.updated_at) as payment_date,
                IFNULL(p.id, 0) as payment_id
            FROM cetak_orders o
            LEFT JOIN payments p ON o.payment_id = p.id
            WHERE o.id = ? AND o.user_id = ?
        ");
    }
    
    $stmt->execute([$order_id, $user_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order_details) {
        $_SESSION['error_message'] = "Detail pesanan tidak ditemukan";
        header('Location: orders.php');
        exit;
    }
    
    // Get user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create invoice
    $invoice_number = "INV-" . date('Ymd') . "-" . $order_number;
    $invoice_date = date('Y-m-d');
    
    // Generate simple HTML invoice
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="invoice_' . $order_number . '.html"');
    
    // Output the HTML content
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #' . $invoice_number . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h2 {
            margin: 0;
            color: #3b82f6;
        }
        
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .address-block {
            width: 45%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #6c757d;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .invoice-container {
                border: none;
                padding: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="print-button" style="text-align: right; margin-bottom: 20px;">
            <button onclick="window.print()">Cetak Invoice</button>
        </div>
        
        <div class="invoice-header">
            <div class="logo">
                ' . ($settings['site_name'] ?? 'Fantastic Pandawa') . '
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p>No. ' . $invoice_number . '</p>
                <p>Tanggal: ' . date('d/m/Y', strtotime($invoice_date)) . '</p>
            </div>
        </div>
        
        <div class="addresses">
            <div class="address-block">
                <strong>Dari:</strong><br>
                ' . ($settings['site_name'] ?? 'Fantastic Pandawa') . '<br>
                ' . ($settings['contact_address'] ?? 'Alamat belum diatur') . '<br>
                Telp: ' . ($settings['contact_phone'] ?? '0822-8243-9997') . '<br>
                Email: ' . ($settings['contact_email'] ?? 'info@fantasticpandawa.com') . '
            </div>
            <div class="address-block">
                <strong>Kepada:</strong><br>
                ' . $user['name'] . '<br>
                ' . ($user['address'] ? $user['address'] . '<br>' : '') . '
                Telp: ' . ($user['phone'] ? $user['phone'] : 'Tidak ada') . '<br>
                Email: ' . $user['email'] . '
            </div>
        </div>
        
        <table>
            <tr>
                <th width="10%" class="text-center">No.</th>
                <th width="50%">Deskripsi</th>
                <th width="15%" class="text-right">Jumlah</th>
                <th width="25%" class="text-right">Harga</th>
            </tr>';
    
    // Item details
    if ($order_type == 'print') {
        echo '
            <tr>
                <td class="text-center">1</td>
                <td>' . $order_details['original_filename'] . ' - ' . $order_details['print_color'] . ' - ' . $order_details['paper_size'] . ' - ' . $order_details['paper_type'] . '</td>
                <td class="text-right">' . $order_details['copies'] . '</td>
                <td class="text-right">Rp ' . number_format($order_details['price'], 0, ',', '.') . '</td>
            </tr>';
    } else {
        echo '
            <tr>
                <td class="text-center">1</td>
                <td>' . ucfirst(str_replace('-', ' ', $order_details['cetak_type'])) . ' - ' . $order_details['paper_type'] . ' - ' . $order_details['finishing'] . '</td>
                <td class="text-right">' . $order_details['quantity'] . '</td>
                <td class="text-right">Rp ' . number_format($order_details['price'], 0, ',', '.') . '</td>
            </tr>';
    }
    
    // Add subtotal, tax, and total
    echo '
            <tr>
                <td colspan="3" class="text-right"><strong>Sub Total</strong></td>
                <td class="text-right">Rp ' . number_format($order_details['price'], 0, ',', '.') . '</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>Pajak (0%)</strong></td>
                <td class="text-right">Rp 0</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>Total</strong></td>
                <td class="text-right"><strong>Rp ' . number_format($order_details['price'], 0, ',', '.') . '</strong></td>
            </tr>
        </table>
        
        <div>
            <strong>Informasi Pembayaran:</strong><br>
            Metode Pembayaran: ' . ($order_details['payment_method'] ?? 'Tunai') . '<br>
            Tanggal Pembayaran: ' . date('d/m/Y H:i', strtotime($order_details['payment_date'])) . '<br>
            Status: ' . translateStatus($order_details['payment_status']) . '
        </div>
        
        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda!</p>
            <p>Jika ada pertanyaan, silakan hubungi kami di ' . ($settings['contact_phone'] ?? '0822-8243-9997') . ' atau ' . ($settings['contact_email'] ?? 'info@fantasticpandawa.com') . '</p>
        </div>
    </div>
</body>
</html>';
    
    exit;
    
} catch (Exception $e) {
    // Log error
    error_log("Error generating invoice: " . $e->getMessage());
    
    $_SESSION['error_message'] = "Terjadi kesalahan saat membuat invoice: " . $e->getMessage();
    header('Location: orders.php');
    exit;
}
?>