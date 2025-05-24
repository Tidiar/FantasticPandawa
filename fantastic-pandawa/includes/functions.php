<?php
// Functions untuk frontend website Fantastic Pandawa

// Fungsi untuk mendapatkan pengaturan website
function getSettings() {
    global $conn;
    try {
        // Cek apakah tabel settings ada
        $table_exists = false;
        try {
            $stmt = $conn->query("SELECT 1 FROM settings LIMIT 1");
            $table_exists = true;
        } catch (PDOException $e) {
            $table_exists = false;
        }
        
        // Jika tabel belum ada, kembalikan default settings
        if (!$table_exists) {
            return [
                'site_name' => 'Fantastic Pandawa',
                'site_description' => 'Jasa Print & Fotokopi',
                'contact_email' => 'info@fantasticpandawa.com',
                'contact_phone' => '0822-8243-9997',
                'contact_whatsapp' => '0822-8243-9997',
                'contact_address' => 'Jl. Pandawa Raya No.Kel, Korpri Jaya, Kec. Sukarame, Kota Bandar Lampung, Lampung 35131',
                'operation_days' => 'Senin - Sabtu',
                'operation_hours' => '08.00 - 20.00',
                'print_bw_price' => '500',
                'print_color_price' => '1000',
                'cetak_base_price' => '5000'
            ];
        }
        
        // Ambil semua pengaturan
        $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        return []; // Kembalikan array kosong jika terjadi error
    }
}

// Fungsi untuk validasi file upload - DIPERBAIKI untuk mendukung file design
function validateUploadFile($file, $allowed_types = [], $max_size = 10485760) { // 10MB default
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File tidak terupload sempurna',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi'
        ];
        
        return ['success' => false, 'message' => $error_messages[$file['error']] ?? 'Error upload file'];
    }
    
    if ($file['size'] > $max_size) {
        $max_mb = round($max_size / 1024 / 1024);
        return ['success' => false, 'message' => "Ukuran file terlalu besar (maksimal {$max_mb}MB)"];
    }
    
    if (!empty($allowed_types)) {
        $file_type = $file['type'];
        $file_name = $file['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Mapping MIME types yang diizinkan
        $mime_extensions = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'application/postscript' => 'ai',
            'image/vnd.adobe.photoshop' => 'psd'
        ];
        
        // Validasi berdasarkan MIME type
        if (!in_array($file_type, $allowed_types)) {
            return ['success' => false, 'message' => 'Format file tidak didukung'];
        }
        
        // Validasi berdasarkan ekstensi untuk keamanan tambahan
        $expected_extension = $mime_extensions[$file_type] ?? null;
        if ($expected_extension && $file_extension !== $expected_extension && $file_extension !== 'jpeg') {
            return ['success' => false, 'message' => 'Ekstensi file tidak sesuai dengan format'];
        }
    }
    
    return ['success' => true];
}

// Fungsi untuk formatting tanggal
function formatDate($date, $show_time = false) {
    if (empty($date)) return '-';
    $datetime = new DateTime($date);
    return $datetime->format($show_time ? 'd M Y H:i' : 'd M Y');
}

// Fungsi untuk formatting harga
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Fungsi untuk translate status - DIPERBAIKI dengan lebih banyak status dan dukungan canceled
function translateStatus($status) {
    $translations = [
        // Order statuses
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'processing' => 'Diproses',
        'ready' => 'Siap',
        'completed' => 'Selesai',
        'canceled' => 'Dibatalkan',
        'cancelled' => 'Dibatalkan',
        'failed' => 'Gagal',
        
        // Payment statuses
        'paid' => 'Lunas',
        'verified' => 'Terverifikasi',
        'expired' => 'Kedaluwarsa',
        'refunded' => 'Dikembalikan',
        
        // Additional statuses
        'draft' => 'Draft',
        'submitted' => 'Dikirim',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak'
    ];
    
    return $translations[$status] ?? ucfirst($status);
}

// Fungsi untuk mendapatkan class badge status - DIPERBAIKI dengan dukungan canceled
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'ready' => 'success',
        'completed' => 'success',
        'canceled' => 'secondary',
        'cancelled' => 'secondary',
        'paid' => 'success',
        'verified' => 'success',
        'failed' => 'danger',
        'expired' => 'secondary'
    ];
    
    return $classes[$status] ?? 'secondary';
}

// Fungsi untuk mendapatkan class badge status pembayaran - DIPERBAIKI dengan dukungan canceled
function getPaymentStatusBadgeClass($status) {
    $classes = [
        'pending' => 'warning',
        'paid' => 'info',
        'verified' => 'success',
        'failed' => 'danger',
        'expired' => 'secondary',
        'canceled' => 'secondary',
        'cancelled' => 'secondary',
        'refunded' => 'info'
    ];
    
    return $classes[$status] ?? 'secondary';
}

// FUNGSI BARU: Check if order can be canceled
function canCancelOrder($order_status, $payment_status) {
    // Order can only be canceled if:
    // 1. Order status is still pending
    // 2. Payment is not yet verified or completed
    
    $cancelable_order_statuses = ['pending'];
    $cancelable_payment_statuses = ['pending', 'failed', 'expired'];
    
    return in_array($order_status, $cancelable_order_statuses) && 
           in_array($payment_status, $cancelable_payment_statuses);
}

// FUNGSI BARU: Get cancellation reason based on status
function getCancellationReason($order_status, $payment_status) {
    if (!canCancelOrder($order_status, $payment_status)) {
        if (in_array($payment_status, ['verified', 'paid'])) {
            return 'Pesanan tidak dapat dibatalkan karena pembayaran sudah diverifikasi';
        }
        
        if (in_array($order_status, ['confirmed', 'processing', 'ready', 'completed'])) {
            return 'Pesanan tidak dapat dibatalkan karena sudah diproses';
        }
        
        return 'Pesanan tidak dapat dibatalkan';
    }
    
    return null; // Can be canceled
}

// FUNGSI BARU: Get order action buttons HTML
function getOrderActionButtons($order) {
    $buttons = [];
    $order_number = htmlspecialchars($order['order_number']);
    $order_id = (int)$order['id'];
    $order_type = htmlspecialchars($order['order_type']);
    
    // Detail button - always available
    $buttons[] = sprintf(
        '<a href="order-detail.php?order=%s" class="btn btn-sm btn-outline-info" title="Lihat Detail">
            <i class="fas fa-eye"></i>
        </a>',
        $order_number
    );
    
    // Payment buttons
    if ($order['payment_status'] === 'pending') {
        if (!empty($order['payment_id'])) {
            // Payment status button
            $buttons[] = sprintf(
                '<a href="../services/payment_status.php?payment_id=%d" class="btn btn-sm btn-warning" title="Lihat Status Pembayaran">
                    <i class="fas fa-credit-card"></i>
                </a>',
                (int)$order['payment_id']
            );
        } else {
            // Pay now button
            $buttons[] = sprintf(
                '<a href="../services/payment.php?order_id=%d&type=%s" class="btn btn-sm btn-primary" title="Bayar Sekarang">
                    <i class="fas fa-money-bill"></i>
                </a>',
                $order_id,
                $order_type
            );
        }
    }
    
    // Download invoice button - only for completed orders
    if ($order['status'] === 'completed') {
        $buttons[] = sprintf(
            '<a href="generate_invoice.php?order_number=%s" class="btn btn-sm btn-success" title="Download Invoice">
                <i class="fas fa-download"></i>
            </a>',
            $order_number
        );
    }
    
    // Cancel button - only if cancellable
    if (canCancelOrder($order['status'], $order['payment_status'])) {
        $buttons[] = sprintf(
            '<button type="button" class="btn btn-sm btn-outline-danger cancel-btn" 
                    onclick="cancelOrder(\'%s\', \'%s\', %d)" title="Batalkan Pesanan">
                <i class="fas fa-times"></i>
            </button>',
            $order_number,
            $order_type,
            $order_id
        );
    }
    
    return '<div class="action-buttons">' . implode('', $buttons) . '</div>';
}

// FUNGSI BARU: Get payment status description
function getPaymentStatusDescription($payment_status, $order_status = null) {
    switch ($payment_status) {
        case 'pending':
            return 'Menunggu pembayaran dari pelanggan';
        case 'paid':
            return 'Pembayaran diterima, menunggu verifikasi admin';
        case 'verified':
            return 'Pembayaran telah diverifikasi oleh admin';
        case 'failed':
            return 'Pembayaran gagal diproses';
        case 'canceled':
        case 'cancelled':
            return 'Pembayaran dibatalkan';
        case 'expired':
            return 'Pembayaran kedaluwarsa';
        case 'refunded':
            return 'Pembayaran telah dikembalikan';
        default:
            return 'Status pembayaran tidak dikenal';
    }
}

// FUNGSI BARU: Get order status description
function getOrderStatusDescription($order_status, $payment_status = null) {
    switch ($order_status) {
        case 'pending':
            if ($payment_status === 'pending') {
                return 'Menunggu pembayaran';
            }
            return 'Pesanan sedang menunggu konfirmasi';
        case 'confirmed':
            return 'Pesanan telah dikonfirmasi dan akan segera diproses';
        case 'processing':
            return 'Pesanan sedang dalam proses pengerjaan';
        case 'ready':
            return 'Pesanan sudah selesai dan siap diambil';
        case 'completed':
            return 'Pesanan telah selesai dan diserahkan';
        case 'canceled':
        case 'cancelled':
            return 'Pesanan telah dibatalkan';
        case 'failed':
            return 'Pesanan gagal diproses';
        default:
            return 'Status pesanan tidak dikenal';
    }
}

// FUNGSI BARU: Log order cancellation
function logOrderCancellation($conn, $order_id, $order_type, $user_id, $reason = null) {
    try {
        $history_table = $order_type . '_order_history';
        
        // Check if history table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$history_table]);
        
        if ($stmt->rowCount() > 0) {
            $notes = $reason ?: 'Pesanan dibatalkan oleh pelanggan';
            
            $log_query = "INSERT INTO {$history_table} 
                (order_id, status, notes, changed_by, created_at) 
                VALUES (?, 'canceled', ?, ?, NOW())";
            
            $stmt = $conn->prepare($log_query);
            return $stmt->execute([$order_id, $notes, $user_id]);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error logging order cancellation: " . $e->getMessage());
        return false;
    }
}

// FUNGSI BARU: Log payment cancellation
function logPaymentCancellation($conn, $payment_id, $user_id, $reason = null) {
    try {
        // Check if payment_history table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE 'payment_history'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $notes = $reason ?: 'Pembayaran dibatalkan karena pesanan dibatalkan oleh pelanggan';
            
            $log_query = "INSERT INTO payment_history 
                (payment_id, status, notes, changed_by, created_at) 
                VALUES (?, 'failed', ?, ?, NOW())";
            
            $stmt = $conn->prepare($log_query);
            return $stmt->execute([$payment_id, $notes, $user_id]);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error logging payment cancellation: " . $e->getMessage());
        return false;
    }
}

// FUNGSI BARU: Cancel order and related payment
function cancelOrderAndPayment($conn, $order_id, $order_type, $user_id, $reason = null) {
    try {
        $conn->beginTransaction();
        
        // Get order details
        $table = $order_type === 'print' ? 'print_orders' : 'cetak_orders';
        $stmt = $conn->prepare("SELECT * FROM {$table} WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception('Pesanan tidak ditemukan');
        }
        
        // Check if order can be canceled
        if (!canCancelOrder($order['status'], $order['payment_status'])) {
            $cancel_reason = getCancellationReason($order['status'], $order['payment_status']);
            throw new Exception($cancel_reason);
        }
        
        // Update order status
        $update_query = "UPDATE {$table} SET 
            status = 'canceled', 
            payment_status = 'failed',
            updated_at = NOW() 
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        if (!$stmt->execute([$order_id])) {
            throw new Exception('Gagal membatalkan pesanan');
        }
        
        // Update payment if exists
        if (!empty($order['payment_id'])) {
            try {
                // Check if payments table exists
                $stmt = $conn->prepare("SHOW TABLES LIKE 'payments'");
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $update_payment_query = "UPDATE payments SET 
                        status = 'failed',
                        notes = CONCAT(COALESCE(notes, ''), '\nDibatalkan pada ', NOW()),
                        updated_at = NOW()
                        WHERE id = ?";
                    
                    $stmt = $conn->prepare($update_payment_query);
                    $stmt->execute([$order['payment_id']]);
                    
                    // Log payment cancellation
                    logPaymentCancellation($conn, $order['payment_id'], $user_id, $reason);
                }
            } catch (Exception $e) {
                // Log error but don't fail the main transaction
                error_log("Error updating payment status: " . $e->getMessage());
            }
        }
        
        // Log order cancellation
        logOrderCancellation($conn, $order_id, $order_type, $user_id, $reason);
        
        // Delete uploaded files if any
        if ($order_type === 'print' && !empty($order['file_path'])) {
            $file_path = '../' . $order['file_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        
        if ($order_type === 'cetak' && !empty($order['design_file_path'])) {
            $file_path = '../' . $order['design_file_path'];
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

// Fungsi untuk membersihkan input
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Fungsi untuk redirect dengan pesan
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION[$type . '_message'] = $message;
    header("Location: $url");
    exit;
}

// Fungsi untuk cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk require login
function requireLogin($redirect_to = '../auth/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['warning_message'] = 'Silakan login terlebih dahulu';
        header("Location: $redirect_to");
        exit;
    }
}

function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        header("Location: auth/login.php");
        exit;
    }
    
    $role = $_SESSION['user_role'];
    
    // Tentukan base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $host . dirname($_SERVER['SCRIPT_NAME']);
    
    // Hapus /auth dari path jika ada
    $base_url = str_replace('/auth', '', $base_url);
    
    switch ($role) {
        case 'admin':
        case 'manager': 
        case 'staff':
            // Redirect ke admin index.php dengan absolute URL
            header("Location: {$base_url}/admin/index.php");
            break;
        case 'customer':
        default:
            // Redirect ke homepage utama
            header("Location: {$base_url}/index.php");
            break;
    }
    exit;
}

// Fungsi untuk registrasi user
function registerUser($name, $email, $password, $phone = null) {
    global $conn;
    try {
        // Cek apakah email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email sudah terdaftar'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Simpan user baru
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'customer', 'active', NOW())");
        $stmt->execute([$name, $email, $hashed_password, $phone]);
        
        return ['success' => true, 'message' => 'Registrasi berhasil'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Fungsi untuk login user
function loginUser($email, $password) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email tidak ditemukan'];
        }
        
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Akun tidak aktif'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Password salah'];
        }
        
        // Update last login
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['is_admin'] = in_array($user['role'], ['admin', 'manager', 'staff']) ? 1 : 0;
        $_SESSION['last_activity'] = time();
        
        return ['success' => true, 'message' => 'Login berhasil'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// FUNGSI BARU: Untuk mendapatkan pesanan user dengan payment info
function getUserOrders($user_id, $type = 'all', $status_filter = '', $search = '', $limit = 10, $offset = 0) {
    global $conn;
    try {
        $orders = [];
        $where_conditions = [];
        $params = [];
        
        // Base condition for user
        $where_conditions[] = "user_id = ?";
        $params[] = $user_id;
        
        // Status filter
        if (!empty($status_filter)) {
            $where_conditions[] = "status = ?";
            $params[] = $status_filter;
        }
        
        // Search filter will be added per query
        $search_condition = "";
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
        }
        
        if ($type === 'all' || $type === 'print') {
            $print_where = implode(' AND ', $where_conditions);
            if (!empty($search)) {
                $print_where .= " AND (order_number LIKE ? OR original_filename LIKE ?)";
            }
            
            $stmt = $conn->prepare("SELECT 
                'print' as order_type,
                id,
                order_number,
                original_filename as detail,
                copies,
                paper_size,
                print_color,
                paper_type,
                notes,
                price,
                status,
                payment_status,
                payment_id,
                created_at,
                updated_at
            FROM print_orders 
            WHERE {$print_where}
            ORDER BY created_at DESC");
            
            $print_params = $params;
            if (!empty($search)) {
                $print_params[] = $search_param;
                $print_params[] = $search_param;
            }
            
            $stmt->execute($print_params);
            $print_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $orders = array_merge($orders, $print_orders);
        }
        
        if ($type === 'all' || $type === 'cetak') {
            $cetak_where = implode(' AND ', $where_conditions);
            if (!empty($search)) {
                $cetak_where .= " AND (order_number LIKE ? OR cetak_type LIKE ?)";
            }
            
            $stmt = $conn->prepare("SELECT 
                'cetak' as order_type,
                id,
                order_number,
                cetak_type as detail,
                quantity,
                paper_type,
                finishing,
                delivery,
                description as notes,
                price,
                status,
                payment_status,
                payment_id,
                created_at,
                updated_at
            FROM cetak_orders 
            WHERE {$cetak_where}
            ORDER BY created_at DESC");
            
            $cetak_params = $params;
            if (!empty($search)) {
                $cetak_params[] = $search_param;
                $cetak_params[] = $search_param;
            }
            
            $stmt->execute($cetak_params);
            $cetak_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $orders = array_merge($orders, $cetak_orders);
        }
        
        // Sort by created_at descending
        usort($orders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($orders, $offset, $limit);
    } catch (PDOException $e) {
        return [];
    }
}

// Fungsi untuk mendapatkan detail pesanan
function getOrderDetails($order_number) {
    global $conn;
    try {
        // Cek di print_orders
        $stmt = $conn->prepare("SELECT *, 'print' as type FROM print_orders WHERE order_number = ?");
        $stmt->execute([$order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            // Cek di cetak_orders
            $stmt = $conn->prepare("SELECT *, 'cetak' as type FROM cetak_orders WHERE order_number = ?");
            $stmt->execute([$order_number]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($order) {
            // Get order history
            $table = $order['type'] . '_order_history';
            try {
                $stmt = $conn->prepare("SELECT * FROM {$table} WHERE order_id = ? ORDER BY created_at DESC");
                $stmt->execute([$order['id']]);
                $order['history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $order['history'] = [];
            }
        }
        
        return $order;
    } catch (PDOException $e) {
        return null;
    }
}

// FUNGSI BARU: Untuk mendapatkan detail pembayaran
function getPaymentDetails($payment_id, $user_id = null) {
    global $conn;
    try {
        $query = "SELECT p.*, 
            CASE 
                WHEN p.order_type = 'print' THEN po.order_number 
                ELSE co.order_number 
            END as order_number,
            CASE 
                WHEN p.order_type = 'print' THEN po.status 
                ELSE co.status 
            END as order_status,
            CASE 
                WHEN p.order_type = 'print' THEN po.original_filename 
                ELSE co.cetak_type 
            END as order_detail,
            u.name as user_name
            FROM payments p 
            LEFT JOIN print_orders po ON p.order_id = po.id AND p.order_type = 'print'
            LEFT JOIN cetak_orders co ON p.order_id = co.id AND p.order_type = 'cetak'
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ?";
        
        $params = [$payment_id];
        
        if ($user_id !== null) {
            $query .= " AND p.user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// FUNGSI BARU: Untuk generate payment code
function generatePaymentCode() {
    return 'PAY' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// FUNGSI BARU: Format currency to Rupiah
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// FUNGSI BARU: Get next order number
function getNextOrderNumber($conn, $order_type) {
    $prefix = strtoupper($order_type);
    $date = date('Ymd');
    
    // Get last order number for today
    $table = $order_type === 'print' ? 'print_orders' : 'cetak_orders';
    $stmt = $conn->prepare("SELECT order_number FROM {$table} WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["{$prefix}-{$date}-%"]);
    $last_order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_order) {
        // Extract sequence number
        $parts = explode('-', $last_order['order_number']);
        $sequence = (int)end($parts) + 1;
    } else {
        $sequence = 1;
    }
    
    return sprintf('%s-%s-%03d', $prefix, $date, $sequence);
}

// FUNGSI BARU: Send notification email (placeholder function)
function sendOrderCancellationEmail($user_email, $order_number, $reason = null) {
    // This function should implement actual email sending
    // For now, just log the action
    error_log("Order cancellation email should be sent to {$user_email} for order {$order_number}");
    return true;
}

// FUNGSI BARU: Get order statistics for user
function getUserOrderStatistics($conn, $user_id) {
    try {
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0,
            'canceled_orders' => 0,
            'total_spent' => 0
        ];
        
        // Print orders statistics
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
            SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as spent
            FROM print_orders WHERE user_id = ?");
        
        $stmt->execute([$user_id]);
        $print_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cetak orders statistics
        $stmt = $conn->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
            SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as spent
            FROM cetak_orders WHERE user_id = ?");
        
        $stmt->execute([$user_id]);
        $cetak_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combine statistics
        $stats['total_orders'] = $print_stats['total'] + $cetak_stats['total'];
        $stats['pending_orders'] = $print_stats['pending'] + $cetak_stats['pending'];
        $stats['completed_orders'] = $print_stats['completed'] + $cetak_stats['completed'];
        $stats['canceled_orders'] = $print_stats['canceled'] + $cetak_stats['canceled'];
        $stats['total_spent'] = $print_stats['spent'] + $cetak_stats['spent'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting user order statistics: " . $e->getMessage());
        return [
            'total_orders' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0,
            'canceled_orders' => 0,
            'total_spent' => 0
        ];
    }
}

// Fungsi untuk update profil user
function updateUserProfile($user_id, $data) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$data['name'], $data['phone'], $data['address'], $user_id]);
        
        // Update session
        $_SESSION['user_name'] = $data['name'];
        
        return ['success' => true, 'message' => 'Profil berhasil diperbarui'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Fungsi untuk update password
function updateUserPassword($user_id, $old_password, $new_password) {
    global $conn;
    try {
        // Verifikasi password lama
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($old_password, $user['password'])) {
            return ['success' => false, 'message' => 'Password lama salah'];
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        return ['success' => true, 'message' => 'Password berhasil diubah'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
    }
}

// Fungsi untuk kirim email reset password (simulasi)
function sendResetEmail($email, $name, $reset_link) {
    // Untuk testing, return true
    // Dalam implementasi nyata, gunakan library seperti PHPMailer atau SwiftMailer
    
    // Simulasi pengiriman email
    $to = $email;
    $subject = "Reset Password - " . getSettings()['site_name'];
    $message = "
    <html>
    <head>
        <title>Reset Password</title>
    </head>
    <body>
        <h2>Halo, $name!</h2>
        <p>Anda telah meminta reset password untuk akun Anda.</p>
        <p>Klik link berikut untuk reset password:</p>
        <p><a href='$reset_link'>Reset Password</a></p>
        <p>Link ini akan expired dalam 1 jam.</p>
        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        <br>
        <p>Terima kasih,<br>Tim " . getSettings()['site_name'] . "</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . getSettings()['contact_email'] . "\r\n";
    
    // Untuk testing, selalu return true
    // Uncomment baris berikut untuk pengiriman email nyata:
    // return mail($to, $subject, $message, $headers);
    
    return true;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($date, $format = 'd M Y') {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

// Fungsi untuk generate CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi untuk verify CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fungsi untuk check user permission
function hasPermission($required_roles = []) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (empty($required_roles)) {
        return true;
    }
    
    return in_array($_SESSION['user_role'], $required_roles);
}

// Fungsi untuk require permission
function requirePermission($required_roles = []) {
    if (!hasPermission($required_roles)) {
        $_SESSION['error_message'] = "Anda tidak memiliki akses ke halaman ini.";
        header("Location: ../auth/login.php");
        exit;
    }
}

// Fungsi untuk format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Fungsi untuk auto login dengan remember token
function autoLoginWithRememberToken() {
    global $conn;
    
    if (isLoggedIn() || !isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $conn->prepare("SELECT id, name, email, role, status FROM users WHERE remember_token = ? AND status = 'active'");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_admin'] = in_array($user['role'], ['admin', 'manager', 'staff']) ? 1 : 0;
            $_SESSION['last_activity'] = time();
            
            return true;
        } else {
            // Token tidak valid, hapus cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        error_log("Auto login error: " . $e->getMessage());
    }
    
    return false;
}

// Fungsi untuk generate order number
function generateOrderNumber($type = 'print') {
    $prefix = strtoupper(substr($type, 0, 1)); // P untuk print, C untuk cetak
    $date = date('Ymd');
    $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $date . $random;
}

// Fungsi untuk log activity
function logActivity($user_id, $action, $description = '') {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

// Fungsi untuk check session timeout
function checkSessionTimeout() {
    $timeout = 1800; // 30 menit
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session expired
        session_unset();
        session_destroy();
        
        session_start();
        $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali.";
        
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Fungsi untuk sanitize filename
function sanitizeFilename($filename) {
    // Remove special characters and spaces
    $filename = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $filename);
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Remove leading/trailing underscores
    $filename = trim($filename, '_');
    
    return $filename;
}

// Fungsi untuk calculate print price
function calculatePrintPrice($pages, $copies, $color_type = 'bw', $paper_size = 'A4') {
    $settings = getSettings();
    $price_per_page = ($color_type === 'color') ? 
        intval($settings['print_color_price'] ?? 1000) : 
        intval($settings['print_bw_price'] ?? 500);
    
    // Size multipliers
    $size_multipliers = [
        'A4' => 1,
        'A3' => 2,
        'F4' => 1.2
    ];
    
    $multiplier = $size_multipliers[$paper_size] ?? 1;
    
    return $pages * $copies * $price_per_page * $multiplier;
}

// FUNGSI BARU: Untuk calculate cetak price
function calculateCetakPrice($cetak_type, $quantity) {
    $base_prices = [
        'kartu-nama' => 50000,
        'brosur' => 5000,
        'undangan' => 8000,
        'banner' => 25000,
        'stiker' => 3000,
        'foto' => 2000,
        'lainnya' => 10000
    ];
    
    $base_price = $base_prices[$cetak_type] ?? 10000;
    return max($quantity * $base_price, $base_price);
}

// FUNGSI BARU: Untuk mendapatkan payment settings
function getPaymentSettings() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Default settings jika tidak ada di database
        $defaults = [
            'admin_fee_qris' => '0',
            'admin_fee_transfer' => '2500',
            'payment_expired_hours' => '24',
            'bank_transfer_name' => 'BCA',
            'bank_transfer_account' => '8905992312',
            'bank_transfer_holder' => 'Fantastic Pandawa',
            'qris_code' => '../assets/images/qris-code.png'
        ];
        
        return array_merge($defaults, $settings);
    } catch (PDOException $e) {
        return [
            'admin_fee_qris' => '0',
            'admin_fee_transfer' => '2500',
            'payment_expired_hours' => '24',
            'bank_transfer_name' => 'BCA',
            'bank_transfer_account' => '8905992312',
            'bank_transfer_holder' => 'Fantastic Pandawa',
            'qris_code' => '../assets/images/qris-code.png'
        ];
    }
}

// FUNGSI BARU: Untuk check apakah file adalah gambar
function isImageFile($file_path) {
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    return in_array($extension, $image_extensions);
}

// FUNGSI BARU: Untuk validate payment proof file
function validatePaymentProof($file) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    return validateUploadFile($file, $allowed_types, $max_size);
}

// FUNGSI BARU: Untuk validate design file
function validateDesignFile($file) {
    $allowed_types = [
        'application/pdf', 
        'image/jpeg', 
        'image/jpg', 
        'image/png', 
        'application/postscript', 
        'image/vnd.adobe.photoshop'
    ];
    $max_size = 50 * 1024 * 1024; // 50MB
    
    return validateUploadFile($file, $allowed_types, $max_size);
}

// FUNGSI BARU: Untuk create payment record
function createPayment($order_id, $order_type, $order_number, $user_id, $amount, $payment_method) {
    global $conn;
    try {
        $conn->beginTransaction();
        
        $payment_settings = getPaymentSettings();
        
        // Calculate admin fee
        $admin_fee = ($payment_method === 'qris') ? 
            (int)$payment_settings['admin_fee_qris'] : 
            (int)$payment_settings['admin_fee_transfer'];
        
        $total_amount = $amount + $admin_fee;
        
        // Generate payment code
        $payment_code = generatePaymentCode();
        
        // Set expired time
        $expired_hours = (int)$payment_settings['payment_expired_hours'];
        $expired_at = date('Y-m-d H:i:s', strtotime("+{$expired_hours} hours"));
        
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (
            order_id, order_type, order_number, user_id, amount, 
            payment_method, payment_code, bank_name, bank_account, 
            account_holder, status, expired_at, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
        
        $stmt->execute([
            $order_id,
            $order_type,
            $order_number,
            $user_id,
            $total_amount,
            $payment_method,
            $payment_code,
            $payment_settings['bank_transfer_name'],
            $payment_settings['bank_transfer_account'],
            $payment_settings['bank_transfer_holder']
        ]);
        
        $payment_id = $conn->lastInsertId();
        
        // Update order payment status and payment_id
        if ($order_type === 'print') {
            $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'pending', payment_id = ? WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'pending', payment_id = ? WHERE id = ?");
        }
        $stmt->execute([$payment_id, $order_id]);
        
        // Add payment history
        try {
            $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'pending', 'Pembayaran dibuat', ?)");
            $stmt->execute([$payment_id, $user_id]);
        } catch (Exception $e) {
            // Table might not exist, continue without history
        }
        
        $conn->commit();
        
        return ['success' => true, 'payment_id' => $payment_id];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Gagal membuat pembayaran: ' . $e->getMessage()];
    }
}

// FUNGSI BARU: Untuk update payment proof
function updatePaymentProof($payment_id, $proof_file_path, $notes = '') {
    global $conn;
    try {
        $conn->beginTransaction();
        
        // Update payment with proof
        $stmt = $conn->prepare("UPDATE payments SET 
            payment_proof = ?, 
            notes = ?, 
            status = 'pending',
            updated_at = NOW() 
            WHERE id = ?");
        
        $stmt->execute([$proof_file_path, $notes, $payment_id]);
        
        // Add to payment history
        try {
            $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'pending', ?, ?)");
            $stmt->execute([
                $payment_id, 
                'Bukti pembayaran uploaded' . ($notes ? '. Catatan: ' . $notes : ''),
                $_SESSION['user_id']
            ]);
        } catch (Exception $e) {
            // Table might not exist, continue without history
        }
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Bukti pembayaran berhasil diupload'];
    } catch (Exception $e) {
        $conn->rollBack();
        return ['success' => false, 'message' => 'Gagal upload bukti pembayaran: ' . $e->getMessage()];
    }
}

// FUNGSI BARU: Untuk get payment history
function getPaymentHistory($payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT ph.*, u.name as changed_by_name 
            FROM payment_history ph 
            LEFT JOIN users u ON ph.changed_by = u.id 
            WHERE ph.payment_id = ? 
            ORDER BY ph.created_at DESC");
        $stmt->execute([$payment_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// FUNGSI BARU: Untuk check payment expiry
function checkPaymentExpiry($payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id, expired_at, status FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment || $payment['status'] !== 'pending') {
            return false;
        }
        
        $now = new DateTime();
        $expired_at = new DateTime($payment['expired_at']);
        
        if ($now > $expired_at) {
            // Update payment status to expired
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("UPDATE payments SET status = 'expired' WHERE id = ?");
            $stmt->execute([$payment_id]);
            
            // Add to payment history
            try {
                $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by) VALUES (?, 'expired', 'Pembayaran kedaluwarsa', NULL)");
                $stmt->execute([$payment_id]);
            } catch (Exception $e) {
                // Continue without history
            }
            
            $conn->commit();
            
            return true; // Payment has expired
        }
        
        return false; // Payment is still valid
    } catch (Exception $e) {
        return false;
    }
}

// FUNGSI BARU: Untuk format time remaining
function formatTimeRemaining($expired_at) {
    $now = new DateTime();
    $expired = new DateTime($expired_at);
    $diff = $expired->getTimestamp() - $now->getTimestamp();
    
    if ($diff <= 0) {
        return 'Expired';
    }
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    $seconds = $diff % 60;
    
    if ($hours > 0) {
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

// FUNGSI BARU: Untuk create order history
function createOrderHistory($order_id, $order_type, $status, $notes, $changed_by) {
    global $conn;
    try {
        $table = $order_type . '_order_history';
        $stmt = $conn->prepare("INSERT INTO {$table} (order_id, status, notes, changed_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$order_id, $status, $notes, $changed_by]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Untuk get order by payment ID
function getOrderByPaymentId($payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT order_id, order_type FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            return null;
        }
        
        if ($payment['order_type'] === 'print') {
            $stmt = $conn->prepare("SELECT * FROM print_orders WHERE id = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM cetak_orders WHERE id = ?");
        }
        
        $stmt->execute([$payment['order_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $order['order_type'] = $payment['order_type'];
        }
        
        return $order;
    } catch (PDOException $e) {
        return null;
    }
}

// FUNGSI BARU: Untuk send notification email (placeholder)
function sendNotificationEmail($to, $subject, $message) {
    // Placeholder untuk pengiriman email notifikasi
    // Implementasi nyata akan menggunakan PHPMailer atau service email lainnya
    
    $settings = getSettings();
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $settings['contact_email'] . "\r\n";
    
    // Untuk development, return true
    // Uncomment untuk pengiriman email nyata:
    // return mail($to, $subject, $message, $headers);
    
    return true;
}

// FUNGSI BARU: Untuk log error
function logError($error_message, $file = '', $line = '') {
    $log_message = date('Y-m-d H:i:s') . " - Error: $error_message";
    if ($file) {
        $log_message .= " in $file";
    }
    if ($line) {
        $log_message .= " on line $line";
    }
    $log_message .= "\n";
    
    error_log($log_message, 3, '../logs/error.log');
}

// FUNGSI BARU: Untuk validate required fields
function validateRequired($fields, $data) {
    $errors = [];
    
    foreach ($fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "$label wajib diisi";
        }
    }
    
    return $errors;
}

// FUNGSI BARU: Untuk validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// FUNGSI BARU: Untuk validate phone number (Indonesia)
function validatePhone($phone) {
    // Remove spaces, dashes, and other non-numeric characters except +
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it's a valid Indonesian phone number
    $patterns = [
        '/^(\+62|62|0)[8][1-9][0-9]{6,10}$/', // Mobile
        '/^(\+62|62|0)[2-9][1-9][0-9]{6,8}$/'  // Landline
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

// FUNGSI BARU: Untuk generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

// FUNGSI BARU: Untuk check if user owns order
function userOwnsOrder($user_id, $order_id, $order_type) {
    global $conn;
    try {
        if ($order_type === 'print') {
            $stmt = $conn->prepare("SELECT id FROM print_orders WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("SELECT id FROM cetak_orders WHERE id = ? AND user_id = ?");
        }
        
        $stmt->execute([$order_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Untuk check if user owns payment
function userOwnsPayment($user_id, $payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id FROM payments WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Untuk get site statistics (untuk homepage)
function getSiteStats() {
    global $conn;
    try {
        $stats = [];
        
        // Total orders
        $stmt = $conn->query("SELECT COUNT(*) as total FROM (
            SELECT id FROM print_orders 
            UNION ALL 
            SELECT id FROM cetak_orders
        ) as all_orders");
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Total customers
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
        $stats['total_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Completed orders
        $stmt = $conn->query("SELECT COUNT(*) as total FROM (
            SELECT id FROM print_orders WHERE status = 'completed'
            UNION ALL 
            SELECT id FROM cetak_orders WHERE status = 'completed'
        ) as completed_orders");
        $stats['completed_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Calculate satisfaction rate (assuming 95% for now)
        $stats['satisfaction_rate'] = 95;
        
        return $stats;
    } catch (PDOException $e) {
        return [
            'total_orders' => 0,
            'total_customers' => 0,
            'completed_orders' => 0,
            'satisfaction_rate' => 95
        ];
    }
}

// Note: getPaymentStatusInfo() function is defined in payment_status.php to avoid conflicts

// Note: checkTable() and checkTableColumn() functions are defined in orders.php to avoid conflicts

// FUNGSI BARU: Safe query execution with error handling
function safeQuery($conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

// FUNGSI BARU: Get order count by status for user
function getOrderCountByStatus($user_id, $status, $order_type = 'all') {
    global $conn;
    try {
        $count = 0;
        
        if ($order_type === 'all' || $order_type === 'print') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE user_id = ? AND status = ?");
            $stmt->execute([$user_id, $status]);
            $count += $stmt->fetchColumn();
        }
        
        if ($order_type === 'all' || $order_type === 'cetak') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM cetak_orders WHERE user_id = ? AND status = ?");
            $stmt->execute([$user_id, $status]);
            $count += $stmt->fetchColumn();
        }
        
        return $count;
    } catch (PDOException $e) {
        return 0;
    }
}

// FUNGSI BARU: Get recent orders for dashboard
function getRecentOrders($user_id, $limit = 5) {
    global $conn;
    try {
        $recent_orders = [];
        
        // Ambil pesanan print terbaru
        $stmt = $conn->prepare("SELECT 'print' as type, order_number, created_at, status, price, payment_status FROM print_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        $print_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ambil pesanan cetak terbaru
        $stmt = $conn->prepare("SELECT 'cetak' as type, order_number, created_at, status, price, payment_status FROM cetak_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        $cetak_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gabungkan dan urutkan
        $recent_orders = array_merge($print_orders, $cetak_orders);
        usort($recent_orders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($recent_orders, 0, $limit);
    } catch (PDOException $e) {
        return [];
    }
}

// FUNGSI BARU: Format order detail for display
function formatOrderDetail($order) {
    if ($order['type'] === 'print') {
        return [
            'title' => $order['original_filename'] ?? 'Print Document',
            'subtitle' => $order['copies'] . ' copy • ' . $order['paper_size'] . ' • ' . 
                         ($order['print_color'] === 'BW' ? 'Hitam Putih' : 'Berwarna')
        ];
    } else {
        return [
            'title' => ucfirst(str_replace('-', ' ', $order['cetak_type'] ?? 'Custom Print')),
            'subtitle' => $order['quantity'] . ' pcs • ' . $order['paper_type'] . ' • ' . $order['finishing']
        ];
    }
}

// FUNGSI BARU: Get user dashboard statistics
function getUserDashboardStats($user_id) {
    global $conn;
    try {
        // Total pesanan print
        $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_print_orders = $stmt->fetchColumn();
        
        // Total pesanan cetak
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cetak_orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_cetak_orders = $stmt->fetchColumn();
        
        // Pesanan tertunda (print)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM print_orders WHERE user_id = ? AND status IN ('pending', 'confirmed', 'processing')");
        $stmt->execute([$user_id]);
        $pending_print_orders = $stmt->fetchColumn();
        
        // Pesanan tertunda (cetak)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM cetak_orders WHERE user_id = ? AND status IN ('pending', 'confirmed', 'processing')");
        $stmt->execute([$user_id]);
        $pending_cetak_orders = $stmt->fetchColumn();
        
        // Total pengeluaran
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COALESCE(SUM(price), 0) FROM print_orders WHERE user_id = ? AND status = 'completed') +
                (SELECT COALESCE(SUM(price), 0) FROM cetak_orders WHERE user_id = ? AND status = 'completed') as total_spent
        ");
        $stmt->execute([$user_id, $user_id]);
        $total_spent = $stmt->fetchColumn();
        
        return [
            'total_print_orders' => $total_print_orders,
            'total_cetak_orders' => $total_cetak_orders,
            'pending_print_orders' => $pending_print_orders,
            'pending_cetak_orders' => $pending_cetak_orders,
            'total_spent' => $total_spent
        ];
        
    } catch (PDOException $e) {
        return [
            'total_print_orders' => 0,
            'total_cetak_orders' => 0,
            'pending_print_orders' => 0,
            'pending_cetak_orders' => 0,
            'total_spent' => 0
        ];
    }
}

// FUNGSI BARU: Validate order ownership
function validateOrderOwnership($user_id, $order_number) {
    global $conn;
    try {
        // Check in print_orders
        $stmt = $conn->prepare("SELECT id, 'print' as type FROM print_orders WHERE order_number = ? AND user_id = ?");
        $stmt->execute([$order_number, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            // Check in cetak_orders
            $stmt = $conn->prepare("SELECT id, 'cetak' as type FROM cetak_orders WHERE order_number = ? AND user_id = ?");
            $stmt->execute([$order_number, $user_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $order;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Get order summary for confirmation
function getOrderSummary($order_id, $order_type) {
    global $conn;
    try {
        if ($order_type === 'print') {
            $stmt = $conn->prepare("SELECT * FROM print_orders WHERE id = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM cetak_orders WHERE id = ?");
        }
        
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $order['order_type'] = $order_type;
            $order['formatted_detail'] = formatOrderDetail($order);
        }
        
        return $order;
    } catch (PDOException $e) {
        return null;
    }
}

// FUNGSI BARU: Clean old files (untuk maintenance)
function cleanOldFiles($directory, $days = 30) {
    try {
        $files = glob($directory . "/*");
        $now = time();
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    } catch (Exception $e) {
        error_log("Error cleaning old files: " . $e->getMessage());
        return 0;
    }
}

// FUNGSI BARU: Get system health status
function getSystemHealth() {
    global $conn;
    
    $health = [
        'database' => false,
        'uploads_dir' => false,
        'logs_dir' => false,
        'temp_dir' => false
    ];
    
    // Check database connection
    try {
        $conn->query("SELECT 1");
        $health['database'] = true;
    } catch (Exception $e) {
        $health['database'] = false;
    }
    
    // Check directories
    $health['uploads_dir'] = is_writable('../uploads/');
    $health['logs_dir'] = is_writable('../logs/');
    $health['temp_dir'] = is_writable(sys_get_temp_dir());
    
    return $health;
}

// FUNGSI BARU: Get notification count for user
function getNotificationCount($user_id) {
    global $conn;
    try {
        // Count unread notifications (assuming notifications table exists)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// FUNGSI BARU: Create notification
function createNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id, related_type, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$user_id, $title, $message, $type, $related_id, $related_type]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Mark notification as read
function markNotificationRead($notification_id, $user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// FUNGSI BARU: Get breadcrumb for pages
function getBreadcrumb($current_page, $additional_items = []) {
    $breadcrumb = [
        ['title' => 'Home', 'url' => '../index.php']
    ];
    
    $pages = [
        'dashboard' => ['title' => 'Dashboard', 'url' => 'dashboard.php'],
        'orders' => ['title' => 'Pesanan Saya', 'url' => 'orders.php'],
        'profile' => ['title' => 'Profil', 'url' => 'profile.php'],
        'print' => ['title' => 'Print Dokumen', 'url' => '../services/print.php'],
        'cetak' => ['title' => 'Cetak Custom', 'url' => '../services/cetak.php'],
        'payment' => ['title' => 'Pembayaran', 'url' => ''],
        'order-detail' => ['title' => 'Detail Pesanan', 'url' => '']
    ];
    
    if (isset($pages[$current_page])) {
        $breadcrumb[] = $pages[$current_page];
    }
    
    foreach ($additional_items as $item) {
        $breadcrumb[] = $item;
    }
    
    return $breadcrumb;
}

// FUNGSI BARU: Generate invoice number
function generateInvoiceNumber($order_number) {
    return 'INV-' . $order_number . '-' . date('YmdHis');
}

// FUNGSI BARU: Get file MIME type safely
function getFileMimeType($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    if (function_exists('mime_content_type')) {
        return mime_content_type($file_path);
    } elseif (function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime;
    } else {
        // Fallback based on extension
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain'
        ];
        
        return $mime_types[$extension] ?? 'application/octet-stream';
    }
}

// FUNGSI BARU: Rate limit check
function checkRateLimit($action, $user_id, $limit = 10, $window = 3600) {
    global $conn;
    try {
        $since = date('Y-m-d H:i:s', time() - $window);
        
        $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE action = ? AND user_id = ? AND created_at > ?");
        $stmt->execute([$action, $user_id, $since]);
        $count = $stmt->fetchColumn();
        
        if ($count >= $limit) {
            return false; // Rate limit exceeded
        }
        
        // Log this action
        $stmt = $conn->prepare("INSERT INTO rate_limits (action, user_id, ip_address, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$action, $user_id, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        return true;
    } catch (PDOException $e) {
        // If rate_limits table doesn't exist, allow the action
        return true;
    }
}

?>