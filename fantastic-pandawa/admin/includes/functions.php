<?php
// Fungsi-fungsi untuk statistik dasbor
function getTotalOrderCount($type) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$type}_orders");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Jika tabel belum ada atau error lainnya, kembalikan 0
        return 0;
    }
}

function getPendingOrderCount($type) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$type}_orders WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getCompletedOrderCount($type) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$type}_orders WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getUserCount() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getTotalRevenue() {
    global $conn;
    try {
        $print_revenue = 0;
        $cetak_revenue = 0;
        
        $stmt = $conn->prepare("SELECT SUM(price) FROM print_orders WHERE status = 'completed'");
        $stmt->execute();
        $print_revenue = $stmt->fetchColumn() ?: 0;
        
        $stmt = $conn->prepare("SELECT SUM(price) FROM cetak_orders WHERE status = 'completed'");
        $stmt->execute();
        $cetak_revenue = $stmt->fetchColumn() ?: 0;
        
        return $print_revenue + $cetak_revenue;
    } catch (PDOException $e) {
        return 0;
    }
}

function getRecentOrders($type, $limit) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT o.*, u.name as customer_name FROM {$type}_orders o 
                                LEFT JOIN users u ON o.user_id = u.id 
                                ORDER BY o.created_at DESC LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

// Fungsi untuk formatting data
function formatDate($date, $showTime = false) {
    if (empty($date)) return '-';
    $dateTime = new DateTime($date);
    return $dateTime->format($showTime ? 'd M Y H:i' : 'd M Y');
}

function formatTime($date) {
    if (empty($date)) return '-';
    $dateTime = new DateTime($date);
    return $dateTime->format('H:i');
}

// Fungsi untuk mendapatkan kelas badge
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'info';
        case 'processing':
            return 'primary';
        case 'ready':
            return 'success';
        case 'completed':
            return 'success';
        case 'canceled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getRoleBadgeClass($role) {
    switch ($role) {
        case 'admin':
            return 'danger';
        case 'manager':
            return 'success';
        case 'staff':
            return 'primary';
        case 'customer':
            return 'info';
        default:
            return 'secondary';
    }
}

// Fungsi penerjemah
function translateStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Tertunda';
        case 'confirmed':
            return 'Dikonfirmasi';
        case 'processing':
            return 'Diproses';
        case 'ready':
            return 'Siap';
        case 'completed':
            return 'Selesai';
        case 'canceled':
            return 'Dibatalkan';
        default:
            return ucfirst($status);
    }
}

function translateUserStatus($status) {
    switch ($status) {
        case 'active':
            return 'Aktif';
        case 'inactive':
            return 'Tidak Aktif';
        case 'suspended':
            return 'Ditangguhkan';
        default:
            return ucfirst($status);
    }
}

function translateRole($role) {
    switch ($role) {
        case 'admin':
            return 'Administrator';
        case 'manager':
            return 'Manajer';
        case 'staff':
            return 'Staff';
        case 'customer':
            return 'Pelanggan';
        default:
            return ucfirst($role);
    }
}

function translatePaymentStatus($status) {
    switch ($status) {
        case 'pending':
            return 'Tertunda';
        case 'paid':
            return 'Dibayar';
        case 'failed':
            return 'Gagal';
        case 'refunded':
            return 'Dikembalikan';
        default:
            return ucfirst($status);
    }
}

function translatePrintColor($color) {
    return $color == 'BW' ? 'Hitam Putih' : 'Berwarna';
}

function translateCetakType($type) {
    $types = [
        'brosur' => 'Brosur',
        'kartu-nama' => 'Kartu Nama',
        'undangan' => 'Undangan',
        'banner' => 'Banner',
        'stiker' => 'Stiker',
        'foto' => 'Foto',
        'lainnya' => 'Lainnya'
    ];
    
    return isset($types[$type]) ? $types[$type] : $type;
}

// Fungsi untuk mengelola pesanan
function getPrintOrders($status = '', $search = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
    global $conn;
    try {
        $query = "SELECT o.*, u.name as customer_name 
                 FROM print_orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key == ':limit' || $key == ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

function getCetakOrders($status = '', $search = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
    global $conn;
    try {
        $query = "SELECT o.*, u.name as customer_name 
                 FROM cetak_orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key == ':limit' || $key == ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

function countPrintOrders($status = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $query = "SELECT COUNT(*) 
                 FROM print_orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function countCetakOrders($status = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $query = "SELECT COUNT(*) 
                 FROM cetak_orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(o.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(o.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getPrintOrderDetails($order_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                               s.name as assigned_to_name
                               FROM print_orders o
                               LEFT JOIN users u ON o.user_id = u.id
                               LEFT JOIN users s ON o.assigned_to = s.id
                               WHERE o.id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function getCetakOrderDetails($order_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                               s.name as assigned_to_name, p.status as payment_status
                               FROM cetak_orders o
                               LEFT JOIN users u ON o.user_id = u.id
                               LEFT JOIN users s ON o.assigned_to = s.id
                               LEFT JOIN payments p ON p.order_id = o.id AND p.order_type = 'cetak'
                               WHERE o.id = :order_id");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function getOrderStatusHistory($order_id, $type) {
    global $conn;
    try {
        $table = $type === 'print' ? 'print_order_status_history' : 'cetak_order_status_history';
        $stmt = $conn->prepare("SELECT h.*, u.name as changed_by_name
                               FROM {$table} h
                               LEFT JOIN users u ON h.changed_by = u.id
                               WHERE h.order_id = :order_id
                               ORDER BY h.created_at DESC");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

function updateOrderStatus($order_id, $new_status, $notes, $type) {
    global $conn;
    try {
        $conn->beginTransaction();
        
        // Update status pesanan
        $stmt = $conn->prepare("UPDATE {$type}_orders SET status = :status WHERE id = :order_id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        // Tambahkan riwayat status
        $table = $type === 'print' ? 'print_order_status_history' : 'cetak_order_status_history';
        $stmt = $conn->prepare("INSERT INTO {$table} (order_id, status, notes, changed_by, created_at) 
                              VALUES (:order_id, :status, :notes, :changed_by, NOW())");
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':changed_by', $_SESSION['user_id']);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

function updateOrdersStatus($order_ids, $new_status, $type) {
    global $conn;
    try {
        $conn->beginTransaction();
        
        foreach ($order_ids as $order_id) {
            // Update status pesanan
            $stmt = $conn->prepare("UPDATE {$type}_orders SET status = :status WHERE id = :order_id");
            $stmt->bindParam(':status', $new_status);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            
            // Tambahkan riwayat status
            $table = $type === 'print' ? 'print_order_status_history' : 'cetak_order_status_history';
            $stmt = $conn->prepare("INSERT INTO {$table} (order_id, status, notes, changed_by, created_at) 
                                  VALUES (:order_id, :status, :notes, :changed_by, NOW())");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':status', $new_status);
            $notes = "Status diubah melalui update massal.";
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':changed_by', $_SESSION['user_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

function assignOrderToStaff($order_id, $staff_id, $type) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE {$type}_orders SET assigned_to = :staff_id WHERE id = :order_id");
        $stmt->bindParam(':staff_id', $staff_id);
        $stmt->bindParam(':order_id', $order_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

function updateOrderPrice($order_id, $price, $type) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE {$type}_orders SET price = :price WHERE id = :order_id");
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':order_id', $order_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}

function deleteOrders($order_ids, $type) {
    global $conn;
    try {
        $conn->beginTransaction();
        
        foreach ($order_ids as $order_id) {
            // Hapus riwayat status terlebih dahulu
            $table = $type === 'print' ? 'print_order_status_history' : 'cetak_order_status_history';
            $stmt = $conn->prepare("DELETE FROM {$table} WHERE order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            
            // Kemudian hapus pesanan
            $stmt = $conn->prepare("DELETE FROM {$type}_orders WHERE id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

// Fungsi untuk mengelola pengguna
function getUsers($role = '', $status = '', $search = '', $limit = 10, $offset = 0) {
    global $conn;
    try {
        $query = "SELECT * FROM users WHERE 1=1";
        $params = array();
        
        if (!empty($role)) {
            $query .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key == ':limit' || $key == ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

function countUsers($role = '', $status = '', $search = '') {
    global $conn;
    try {
        $query = "SELECT COUNT(*) FROM users WHERE 1=1";
        $params = array();
        
        if (!empty($role)) {
            $query .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getStaffList() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role IN ('admin', 'manager', 'staff') AND status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

// Fungsi ekspor
function exportPrintOrdersCSV($status = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $orders = getPrintOrders($status, $search, $date_from, $date_to, 9999, 0);
        
        // Set header untuk download file CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=print_orders_' . date('Y-m-d') . '.csv');
        
        // Buat output stream
        $output = fopen('php://output', 'w');
        
        // Tambahkan BOM untuk UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Tambahkan header kolom
        fputcsv($output, array('No. Pesanan', 'Pelanggan', 'File', 'Jumlah Copy', 'Ukuran Kertas', 'Warna', 'Jenis Kertas', 'Tanggal', 'Harga', 'Status'));
        
        // Tambahkan data pesanan
        foreach ($orders as $order) {
            fputcsv($output, array(
                $order['order_number'],
                $order['customer_name'],
                $order['original_filename'],
                $order['copies'],
                $order['paper_size'],
                translatePrintColor($order['print_color']),
                $order['paper_type'],
                formatDate($order['created_at']),
                $order['price'],
                translateStatus($order['status'])
            ));
        }
        
        fclose($output);
        exit;
    } catch (PDOException $e) {
        exit("Error exporting orders: " . $e->getMessage());
    }
}

function exportCetakOrdersCSV($status = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $orders = getCetakOrders($status, $search, $date_from, $date_to, 9999, 0);
        
        // Set header untuk download file CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=cetak_orders_' . date('Y-m-d') . '.csv');
        
        // Buat output stream
        $output = fopen('php://output', 'w');
        
        // Tambahkan BOM untuk UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Tambahkan header kolom
        fputcsv($output, array('No. Pesanan', 'Pelanggan', 'Jenis Cetakan', 'Jumlah', 'Jenis Kertas', 'Finishing', 'Pengiriman', 'Tanggal', 'Harga', 'Status'));
        
        // Tambahkan data pesanan
        foreach ($orders as $order) {
            fputcsv($output, array(
                $order['order_number'],
                $order['customer_name'],
                translateCetakType($order['cetak_type']),
                $order['quantity'],
                $order['paper_type'],
                $order['finishing'],
                $order['delivery'] == 'pickup' ? 'Ambil Sendiri' : 'Dikirim',
                formatDate($order['created_at']),
                $order['price'],
                translateStatus($order['status'])
            ));
        }
        
        fclose($output);
        exit;
    } catch (PDOException $e) {
        exit("Error exporting orders: " . $e->getMessage());
    }
}

function exportUsersCSV($role = '', $status = '', $search = '') {
    global $conn;
    try {
        $users = getUsers($role, $status, $search, 9999, 0);
        
        // Set header untuk download file CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_' . date('Y-m-d') . '.csv');
        
        // Buat output stream
        $output = fopen('php://output', 'w');
        
        // Tambahkan BOM untuk UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Tambahkan header kolom
        fputcsv($output, array('ID', 'Nama', 'Email', 'Telepon', 'Peran', 'Status', 'Tanggal Daftar', 'Login Terakhir'));
        
        // Tambahkan data pengguna
        foreach ($users as $user) {
            fputcsv($output, array(
                $user['id'],
                $user['name'],
                $user['email'],
                $user['phone'] ?? '-',
                translateRole($user['role']),
                translateUserStatus($user['status']),
                formatDate($user['created_at']),
                $user['last_login'] ? formatDate($user['last_login']) : 'Belum Pernah'
            ));
        }
        
        fclose($output);
        exit;
    } catch (PDOException $e) {
        exit("Error exporting users: " . $e->getMessage());
    }
}

// Fungsi untuk mendapatkan semua pengaturan
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
        
        // Jika tabel belum ada, buat tabel
        if (!$table_exists) {
            $sql = "CREATE TABLE settings (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(50) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->exec($sql);
            
            // Masukkan pengaturan default
            $default_settings = [
                ['site_name', 'Fantastic Pandawa'],
                ['site_description', 'Jasa Print & Fotokopi'],
                ['contact_email', 'info@fantasticpandawa.com'],
                ['contact_phone', '0822-8243-9997'],
                ['contact_whatsapp', '0822-8243-9997'],
                ['contact_address', 'Jl. Pandawa Raya No.Kel, Korpri Jaya, Kec. Sukarame, Kota Bandar Lampung, Lampung 35131'],
                ['operation_days', 'Senin - Sabtu'],
                ['operation_hours', '08.00 - 20.00'],
                ['print_bw_price', '500'],
                ['print_color_price', '1000'],
                ['cetak_base_price', '5000']
            ];
            
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($default_settings as $setting) {
                $stmt->execute($setting);
            }
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

// Fungsi untuk memperbarui pengaturan
function updateSettings($settings) {
    global $conn;
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

// Fungsi dummy untuk getLowStockCount jika dibutuhkan
function getLowStockCount() {
    return 0; // Implementasi dummy karena tidak menggunakan fitur inventaris
}

// Fungsi untuk mendapatkan badge class payment status
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'paid':
            return 'success';
        case 'failed':
            return 'danger';
        case 'expired':
            return 'secondary';
        default:
            return 'secondary';
    }
}

// Fungsi untuk mendapatkan statistik pembayaran
function getPaymentStats() {
    global $conn;
    try {
        $stats = [
            'pending' => 0,
            'paid' => 0,
            'failed' => 0,
            'expired' => 0
        ];
        
        $stmt = $conn->prepare("SELECT payment_status, COUNT(*) as count FROM payments GROUP BY payment_status");
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats[$row['payment_status']] = $row['count'];
        }
        
        return $stats;
    } catch (PDOException $e) {
        return [
            'pending' => 0,
            'paid' => 0,
            'failed' => 0,
            'expired' => 0
        ];
    }
}

// Fungsi untuk mendapatkan pembayaran dengan filter
function getPayments($status = '', $payment_method = '', $order_type = '', $search = '', $date_from = '', $date_to = '', $limit = 10, $offset = 0) {
    global $conn;
    try {
        $query = "SELECT p.*, u.name as customer_name, u.email as customer_email,
                 CASE 
                     WHEN p.order_type = 'print' THEN po.order_number 
                     ELSE co.order_number 
                 END as order_number
                 FROM payments p 
                 LEFT JOIN users u ON p.user_id = u.id 
                 LEFT JOIN print_orders po ON p.order_id = po.id AND p.order_type = 'print'
                 LEFT JOIN cetak_orders co ON p.order_id = co.id AND p.order_type = 'cetak'
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND p.payment_status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($payment_method)) {
            $query .= " AND p.payment_method = :payment_method";
            $params[':payment_method'] = $payment_method;
        }
        
        if (!empty($order_type)) {
            $query .= " AND p.order_type = :order_type";
            $params[':order_type'] = $order_type;
        }
        
        if (!empty($search)) {
            $query .= " AND (p.payment_code LIKE :search OR p.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(p.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(p.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            if ($key == ':limit' || $key == ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

// Fungsi untuk menghitung total pembayaran
function countPayments($status = '', $payment_method = '', $order_type = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $query = "SELECT COUNT(*) 
                 FROM payments p 
                 LEFT JOIN users u ON p.user_id = u.id 
                 LEFT JOIN print_orders po ON p.order_id = po.id AND p.order_type = 'print'
                 LEFT JOIN cetak_orders co ON p.order_id = co.id AND p.order_type = 'cetak'
                 WHERE 1=1";
        $params = array();
        
        if (!empty($status)) {
            $query .= " AND p.payment_status = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($payment_method)) {
            $query .= " AND p.payment_method = :payment_method";
            $params[':payment_method'] = $payment_method;
        }
        
        if (!empty($order_type)) {
            $query .= " AND p.order_type = :order_type";
            $params[':order_type'] = $order_type;
        }
        
        if (!empty($search)) {
            $query .= " AND (p.payment_code LIKE :search OR p.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND DATE(p.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $query .= " AND DATE(p.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }
        
        $stmt = $conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Fungsi untuk mendapatkan detail pembayaran
function getPaymentDetails($payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT p.*, u.name as customer_name, u.email as customer_email,
                               CASE 
                                   WHEN p.order_type = 'print' THEN po.order_number 
                                   ELSE co.order_number 
                               END as order_number
                               FROM payments p
                               LEFT JOIN users u ON p.user_id = u.id
                               LEFT JOIN print_orders po ON p.order_id = po.id AND p.order_type = 'print'
                               LEFT JOIN cetak_orders co ON p.order_id = co.id AND p.order_type = 'cetak'
                               WHERE p.id = :payment_id");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk mendapatkan riwayat pembayaran
function getPaymentHistory($payment_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT ph.*, u.name as changed_by_name
                               FROM payment_history ph
                               LEFT JOIN users u ON ph.changed_by = u.id
                               WHERE ph.payment_id = :payment_id
                               ORDER BY ph.created_at DESC");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return array();
    }
}

// Fungsi untuk update status pembayaran
function updatePaymentStatus($payment_id, $new_status, $notes = '') {
    global $conn;
    try {
        $conn->beginTransaction();
        
        // Update status pembayaran
        $stmt = $conn->prepare("UPDATE payments SET payment_status = :status, updated_at = NOW() WHERE id = :payment_id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        
        // Jika status disetujui, update juga tanggal pembayaran dan admin yang mengonfirmasi
        if ($new_status === 'paid') {
            $stmt = $conn->prepare("UPDATE payments SET payment_date = NOW(), confirmed_by = :admin_id WHERE id = :payment_id");
            $stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->execute();
            
            // Update status pesanan terkait menjadi confirmed
            $payment = getPaymentDetails($payment_id);
            if ($payment) {
                if ($payment['order_type'] === 'print') {
                    $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'paid', status = 'confirmed' WHERE id = :order_id");
                } else {
                    $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'paid', status = 'confirmed' WHERE id = :order_id");
                }
                $stmt->bindParam(':order_id', $payment['order_id']);
                $stmt->execute();
            }
        } elseif ($new_status === 'failed') {
            // Update status pesanan terkait
            $payment = getPaymentDetails($payment_id);
            if ($payment) {
                if ($payment['order_type'] === 'print') {
                    $stmt = $conn->prepare("UPDATE print_orders SET payment_status = 'failed' WHERE id = :order_id");
                } else {
                    $stmt = $conn->prepare("UPDATE cetak_orders SET payment_status = 'failed' WHERE id = :order_id");
                }
                $stmt->bindParam(':order_id', $payment['order_id']);
                $stmt->execute();
            }
        }
        
        // Tambahkan riwayat pembayaran
        $stmt = $conn->prepare("INSERT INTO payment_history (payment_id, status, notes, changed_by, created_at) 
                              VALUES (:payment_id, :status, :notes, :changed_by, NOW())");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':changed_by', $_SESSION['user_id']);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        return false;
    }
}

// Fungsi untuk menyetujui pembayaran (approve)
function approvePayments($payment_ids) {
    global $conn;
    try {
        foreach ($payment_ids as $payment_id) {
            updatePaymentStatus($payment_id, 'paid', 'Pembayaran disetujui melalui tindakan massal');
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Fungsi untuk menolak pembayaran (reject)
function rejectPayments($payment_ids) {
    global $conn;
    try {
        foreach ($payment_ids as $payment_id) {
            updatePaymentStatus($payment_id, 'failed', 'Pembayaran ditolak melalui tindakan massal');
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Fungsi untuk ekspor CSV pembayaran
function exportPaymentsCSV($status = '', $payment_method = '', $order_type = '', $search = '', $date_from = '', $date_to = '') {
    global $conn;
    try {
        $payments = getPayments($status, $payment_method, $order_type, $search, $date_from, $date_to, 9999, 0);
        
        // Set header untuk download file CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=payments_' . date('Y-m-d') . '.csv');
        
        // Buat output stream
        $output = fopen('php://output', 'w');
        
        // Tambahkan BOM untuk UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Tambahkan header kolom
        fputcsv($output, array(
            'Kode Pembayaran', 'No. Pesanan', 'Pelanggan', 'Email', 'Jenis Pesanan', 
            'Metode Pembayaran', 'Jumlah', 'Status', 'Tanggal Dibuat', 'Tanggal Dikonfirmasi'
        ));
        
        // Tambahkan data pembayaran
        foreach ($payments as $payment) {
            fputcsv($output, array(
                $payment['payment_code'],
                $payment['order_number'],
                $payment['customer_name'],
                $payment['customer_email'],
                ucfirst($payment['order_type']),
                $payment['payment_method'] === 'qris' ? 'QRIS' : 'Bank Transfer',
                $payment['amount'],
                translatePaymentStatus($payment['payment_status']),
                formatDate($payment['created_at'], true),
                $payment['payment_date'] ? formatDate($payment['payment_date'], true) : '-'
            ));
        }
        
        fclose($output);
        exit;
    } catch (PDOException $e) {
        exit("Error exporting payments: " . $e->getMessage());
    }
}

// Fungsi untuk mendapatkan jumlah pembayaran pending untuk notifikasi
function getPendingPaymentCount() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending' AND payment_proof IS NOT NULL");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
?>