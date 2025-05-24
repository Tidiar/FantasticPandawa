<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['warning_message'] = "Silakan login terlebih dahulu";
    header("Location: ../auth/login.php");
    exit;
}

$settings = getSettings();
$page_title = "Pesanan Saya";
$page_description = "Daftar semua pesanan print dan cetak Anda";

$user_id = $_SESSION['user_id'];

// Filter parameters
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Debug mode - set to false on production
$debug_mode = false;

// Check database structure
function checkTableColumn($conn, $table, $column) {
    try {
        $stmt = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function checkTable($conn, $table) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Check required tables and columns
$db_problems = [];

// Check main tables
if (!checkTable($conn, 'print_orders')) {
    $db_problems[] = "Tabel 'print_orders' tidak ditemukan";
}
if (!checkTable($conn, 'cetak_orders')) {
    $db_problems[] = "Tabel 'cetak_orders' tidak ditemukan";
}

// Check columns if tables exist
if (checkTable($conn, 'print_orders')) {
    if (!checkTableColumn($conn, 'print_orders', 'payment_id')) {
        $db_problems[] = "Kolom 'payment_id' tidak ditemukan di tabel 'print_orders'";
    }
    if (!checkTableColumn($conn, 'print_orders', 'payment_status')) {
        $db_problems[] = "Kolom 'payment_status' tidak ditemukan di tabel 'print_orders'";
    }
}

if (checkTable($conn, 'cetak_orders')) {
    if (!checkTableColumn($conn, 'cetak_orders', 'payment_id')) {
        $db_problems[] = "Kolom 'payment_id' tidak ditemukan di tabel 'cetak_orders'";
    }
    if (!checkTableColumn($conn, 'cetak_orders', 'payment_status')) {
        $db_problems[] = "Kolom 'payment_status' tidak ditemukan di tabel 'cetak_orders'";
    }
}

// Get orders with payments
try {
    $orders = [];
    $total_orders = 0;
    
    // If database has problems, throw exception
    if (!empty($db_problems)) {
        throw new Exception("Masalah struktur database: " . implode(", ", $db_problems));
    }
    
    // Build WHERE conditions
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
    
    // Search filter
    $search_condition = "";
    if (!empty($search)) {
        $search_param = '%' . $search . '%';
    }
    
    // Query for print orders
    if ($type_filter === '' || $type_filter === 'print') {
        $print_where = implode(' AND ', $where_conditions);
        if (!empty($search)) {
            $print_where .= " AND (order_number LIKE ? OR original_filename LIKE ?)";
        }
        
        $print_query = "SELECT 
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
        ORDER BY created_at DESC";
        
        $print_params = $params;
        if (!empty($search)) {
            $print_params[] = $search_param;
            $print_params[] = $search_param;
        }
        
        if ($debug_mode) {
            echo "<pre>Print Query: " . $print_query . "</pre>";
            echo "<pre>Print Params: "; print_r($print_params); echo "</pre>";
        }
        
        $stmt = $conn->prepare($print_query);
        $stmt->execute($print_params);
        $print_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orders = array_merge($orders, $print_orders);
    }
    
    // Query for cetak orders
    if ($type_filter === '' || $type_filter === 'cetak') {
        $cetak_where = implode(' AND ', $where_conditions);
        if (!empty($search)) {
            $cetak_where .= " AND (order_number LIKE ? OR cetak_type LIKE ?)";
        }
        
        $cetak_query = "SELECT 
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
        ORDER BY created_at DESC";
        
        $cetak_params = $params;
        if (!empty($search)) {
            $cetak_params[] = $search_param;
            $cetak_params[] = $search_param;
        }
        
        if ($debug_mode) {
            echo "<pre>Cetak Query: " . $cetak_query . "</pre>";
            echo "<pre>Cetak Params: "; print_r($cetak_params); echo "</pre>";
        }
        
        $stmt = $conn->prepare($cetak_query);
        $stmt->execute($cetak_params);
        $cetak_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $orders = array_merge($orders, $cetak_orders);
    }
    
    // Sort by created_at descending
   usort($orders, function($a, $b) {
       return strtotime($b['created_at']) - strtotime($a['created_at']);
   });
   
   // Calculate total and pagination
   $total_orders = count($orders);
   $total_pages = max(1, ceil($total_orders / $limit));
   $orders = array_slice($orders, $offset, $limit);
   
} catch (Exception $e) {
    $orders = [];
    $total_orders = 0;
    $total_pages = 1; // Ensure at least one page
    
    // Detailed error message
    $error_message = "Terjadi kesalahan saat mengambil data pesanan: " . $e->getMessage();
    
    // Log error for debugging
    error_log("Error in orders.php: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Show specific error messages for database structure issues
    if (!empty($db_problems)) {
        $fix_instructions = "
            <strong>Perbaiki dengan SQL berikut:</strong><br>
            <pre>";
            
        if (!checkTable($conn, 'print_orders')) {
            $fix_instructions .= "
CREATE TABLE print_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    copies INT NOT NULL DEFAULT 1,
    paper_size VARCHAR(20) NOT NULL DEFAULT 'A4',
    print_color VARCHAR(20) NOT NULL DEFAULT 'BW',
    paper_type VARCHAR(50) NOT NULL DEFAULT 'HVS 70gsm',
    notes TEXT,
    price DECIMAL(10,2) NOT NULL,
    payment_id INT,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);\n";
        }
            
        if (!checkTable($conn, 'cetak_orders')) {
            $fix_instructions .= "
CREATE TABLE cetak_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    cetak_type VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    paper_type VARCHAR(50) NOT NULL,
    finishing VARCHAR(50) NOT NULL,
    delivery VARCHAR(50) NOT NULL,
    description TEXT,
    design_file_path VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    payment_id INT,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);\n";
        }
            
        if (checkTable($conn, 'print_orders') && !checkTableColumn($conn, 'print_orders', 'payment_id')) {
            $fix_instructions .= "ALTER TABLE print_orders ADD COLUMN payment_id INT AFTER price;\n";
        }
            
        if (checkTable($conn, 'print_orders') && !checkTableColumn($conn, 'print_orders', 'payment_status')) {
            $fix_instructions .= "ALTER TABLE print_orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER status;\n";
        }
            
        if (checkTable($conn, 'cetak_orders') && !checkTableColumn($conn, 'cetak_orders', 'payment_id')) {
            $fix_instructions .= "ALTER TABLE cetak_orders ADD COLUMN payment_id INT AFTER price;\n";
        }
            
        if (checkTable($conn, 'cetak_orders') && !checkTableColumn($conn, 'cetak_orders', 'payment_status')) {
            $fix_instructions .= "ALTER TABLE cetak_orders ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER status;\n";
        }
            
        $fix_instructions .= "</pre>";
        
        // Only show fix instructions for admin users
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            $error_message .= $fix_instructions;
        }
    }
}

include '../includes/header.php';
?>

<!-- Orders Section -->
<section class="orders-section py-5">
   <div class="container">
       <!-- Header -->
       <div class="row mb-4">
           <div class="col-md-6">
               <h1 class="orders-title">Pesanan Saya</h1>
               <p class="orders-subtitle">Kelola dan lacak semua pesanan Anda</p>
           </div>
           <div class="col-md-6 text-md-end">
               <div class="header-actions">
                   <a href="../services/print.php" class="btn btn-primary">
                       <i class="fas fa-print me-2"></i>Print Baru
                   </a>
                   <a href="../services/cetak.php" class="btn btn-primary">
                       <i class="fas fa-copy me-2"></i>Cetak Baru
                   </a>
               </div>
           </div>
       </div>
       
       <?php if (isset($error_message)): ?>
           <div class="alert alert-danger" role="alert">
               <i class="fas fa-exclamation-circle me-2"></i>
               <?= $error_message ?>
           </div>
       <?php endif; ?>
       
       <?php if (isset($_SESSION['error_message'])): ?>
           <div class="alert alert-danger" role="alert">
               <i class="fas fa-exclamation-circle me-2"></i>
               <?= $_SESSION['error_message'] ?>
           </div>
           <?php unset($_SESSION['error_message']); ?>
       <?php endif; ?>
       
       <?php if (isset($_SESSION['success_message'])): ?>
           <div class="alert alert-success" role="alert">
               <i class="fas fa-check-circle me-2"></i>
               <?= $_SESSION['success_message'] ?>
           </div>
           <?php unset($_SESSION['success_message']); ?>
       <?php endif; ?>
       
       <!-- Filters -->
       <div class="row mb-4">
           <div class="col-12">
               <div class="filter-card">
                   <form method="GET" action="" class="row g-3">
                       <div class="col-md-3">
                           <label class="form-label">Jenis Layanan</label>
                           <select name="type" class="form-select">
                               <option value="">Semua Layanan</option>
                               <option value="print" <?= $type_filter == 'print' ? 'selected' : '' ?>>Print</option>
                               <option value="cetak" <?= $type_filter == 'cetak' ? 'selected' : '' ?>>Cetak</option>
                           </select>
                       </div>
                       <div class="col-md-3">
                           <label class="form-label">Status</label>
                           <select name="status" class="form-select">
                               <option value="">Semua Status</option>
                               <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Menunggu</option>
                               <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                               <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Diproses</option>
                               <option value="ready" <?= $status_filter == 'ready' ? 'selected' : '' ?>>Siap</option>
                               <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Selesai</option>
                               <option value="canceled" <?= $status_filter == 'canceled' ? 'selected' : '' ?>>Dibatalkan</option>
                           </select>
                       </div>
                       <div class="col-md-4">
                           <label class="form-label">Cari Pesanan</label>
                           <input type="text" name="search" class="form-control" placeholder="No. pesanan atau detail..." value="<?= htmlspecialchars($search) ?>">
                       </div>
                       <div class="col-md-2">
                           <label class="form-label">&nbsp;</label>
                           <div class="d-grid">
                               <button type="submit" class="btn btn-outline-primary">
                                   <i class="fas fa-search"></i> Filter
                               </button>
                           </div>
                       </div>
                   </form>
                   
                   <?php if (!empty($status_filter) || !empty($type_filter) || !empty($search)): ?>
                       <div class="mt-3">
                           <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                               <i class="fas fa-times me-1"></i>Reset Filter
                           </a>
                       </div>
                   <?php endif; ?>
               </div>
           </div>
       </div>
       
       <!-- Orders List -->
       <div class="row">
           <div class="col-12">
               <div class="orders-card">
                   <div class="card-header d-flex justify-content-between align-items-center">
                       <h5 class="mb-0">
                           <i class="fas fa-list me-2"></i>
                           Daftar Pesanan 
                           <?php if ($total_orders > 0): ?>
                               (<?= $total_orders ?> pesanan)
                           <?php endif; ?>
                       </h5>
                       
                       <?php if (!empty($status_filter) || !empty($type_filter) || !empty($search)): ?>
                           <div class="filter-info">
                               <?php if (!empty($type_filter)): ?>
                                   <span class="badge bg-primary me-1"><?= ucfirst($type_filter) ?></span>
                               <?php endif; ?>
                               <?php if (!empty($status_filter)): ?>
                                   <span class="badge bg-<?= getStatusBadgeClass($status_filter) ?> me-1"><?= translateStatus($status_filter) ?></span>
                               <?php endif; ?>
                               <?php if (!empty($search)): ?>
                                   <span class="badge bg-secondary me-1">Pencarian: <?= htmlspecialchars($search) ?></span>
                               <?php endif; ?>
                           </div>
                       <?php endif; ?>
                   </div>
                   
                   <div class="card-body p-0">
                       <?php if (count($orders) > 0): ?>
                           <div class="table-responsive">
                               <table class="table table-hover mb-0">
                                   <thead class="table-light">
                                       <tr>
                                           <th>No. Pesanan</th>
                                           <th>Jenis</th>
                                           <th>Detail</th>
                                           <th>Tanggal</th>
                                           <th>Status</th>
                                           <th>Pembayaran</th>
                                           <th>Harga</th>
                                           <th>Aksi</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       <?php foreach ($orders as $order): ?>
                                           <tr>
                                               <td>
                                                   <span class="fw-bold text-primary"><?= $order['order_number'] ?></span>
                                               </td>
                                               <td>
                                                   <span class="badge bg-<?= $order['order_type'] == 'print' ? 'primary' : 'success' ?>">
                                                       <?= $order['order_type'] == 'print' ? 'Print' : 'Cetak' ?>
                                                   </span>
                                               </td>
                                               <td>
                                                   <?php if ($order['order_type'] == 'print'): ?>
                                                       <div class="order-detail">
                                                           <strong><?= htmlspecialchars($order['detail']) ?></strong><br>
                                                           <small class="text-muted">
                                                               <?= $order['copies'] ?> copy • <?= $order['paper_size'] ?> • 
                                                               <?= $order['print_color'] == 'BW' ? 'Hitam Putih' : 'Berwarna' ?>
                                                           </small>
                                                       </div>
                                                   <?php else: ?>
                                                       <div class="order-detail">
                                                           <strong><?= ucfirst(str_replace('-', ' ', $order['detail'])) ?></strong><br>
                                                           <small class="text-muted">
                                                               <?= $order['quantity'] ?> pcs • <?= $order['paper_type'] ?> • <?= $order['finishing'] ?>
                                                           </small>
                                                       </div>
                                                   <?php endif; ?>
                                               </td>
                                               <td>
                                                   <div class="order-date">
                                                       <?= formatDate($order['created_at']) ?><br>
                                                       <small class="text-muted"><?= date('H:i', strtotime($order['created_at'])) ?></small>
                                                   </div>
                                               </td>
                                               <td>
                                                   <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                                       <?= translateStatus($order['status']) ?>
                                                   </span>
                                               </td>
                                               <td>
                                                   <span class="badge bg-<?= getPaymentStatusBadgeClass($order['payment_status']) ?>">
                                                       <?= translateStatus($order['payment_status']) ?>
                                                   </span>
                                               </td>
                                               <td>
                                                   <span class="fw-bold">Rp <?= number_format($order['price'], 0, ',', '.') ?></span>
                                               </td>
                                               <td>
                                                   <div class="action-buttons">
                                                       <!-- Detail Button -->
                                                       <a href="order-detail.php?order=<?= $order['order_number'] ?>" 
                                                          class="btn btn-sm btn-outline-info" 
                                                          title="Lihat Detail">
                                                           <i class="fas fa-eye"></i>
                                                       </a>
                                                       
                                                       <!-- Payment Button -->
                                                       <?php if ($order['payment_status'] == 'pending' && $order['payment_id']): ?>
                                                           <a href="../services/payment_status.php?payment_id=<?= $order['payment_id'] ?>" 
                                                              class="btn btn-sm btn-warning" 
                                                              title="Lihat Status Pembayaran">
                                                               <i class="fas fa-credit-card"></i>
                                                           </a>
                                                       <?php elseif ($order['payment_status'] == 'pending' && !$order['payment_id']): ?>
                                                           <a href="../services/payment.php?order_id=<?= $order['id'] ?>&type=<?= $order['order_type'] ?>" 
                                                              class="btn btn-sm btn-primary" 
                                                              title="Bayar Sekarang">
                                                               <i class="fas fa-money-bill"></i>
                                                           </a>
                                                       <?php endif; ?>
                                                       
                                                       <!-- Download Invoice Button -->
                                                       <?php if ($order['status'] == 'completed'): ?>
                                                           <a href="generate_invoice.php?order_number=<?= $order['order_number'] ?>" 
                                                              class="btn btn-sm btn-success" 
                                                              title="Download Invoice">
                                                               <i class="fas fa-download"></i>
                                                           </a>
                                                       <?php endif; ?>
                                                       
                                                       <!-- Cancel Button -->
                                                       <?php if ($order['status'] == 'pending' && $order['payment_status'] == 'pending'): ?>
                                                           <button type="button" 
                                                                   class="btn btn-sm btn-outline-danger cancel-btn" 
                                                                   onclick="cancelOrder('<?= $order['order_number'] ?>', '<?= $order['order_type'] ?>', <?= $order['id'] ?>)"
                                                                   title="Batalkan Pesanan">
                                                               <i class="fas fa-times"></i>
                                                           </button>
                                                       <?php endif; ?>
                                                   </div>
                                               </td>
                                           </tr>
                                       <?php endforeach; ?>
                                   </tbody>
                               </table>
                           </div>
                           
                           <!-- Pagination -->
                           <?php if ($total_pages > 1): ?>
                               <div class="d-flex justify-content-center mt-4">
                                   <nav aria-label="Orders pagination">
                                       <ul class="pagination">
                                           <?php if ($page > 1): ?>
                                               <li class="page-item">
                                                   <a class="page-link" href="?page=<?= $page - 1 ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                       <i class="fas fa-chevron-left"></i>
                                                   </a>
                                               </li>
                                           <?php endif; ?>
                                           
                                           <?php 
                                           $start_page = max(1, $page - 2);
                                           $end_page = min($total_pages, $page + 2);
                                           
                                           if ($start_page > 1): ?>
                                               <li class="page-item">
                                                   <a class="page-link" href="?page=1<?= $type_filter ? '&type=' . $type_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">1</a>
                                               </li>
                                               <?php if ($start_page > 2): ?>
                                                   <li class="page-item disabled"><span class="page-link">...</span></li>
                                               <?php endif; ?>
                                           <?php endif; ?>
                                           
                                           <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                               <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                   <a class="page-link" href="?page=<?= $i ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                       <?= $i ?>
                                                   </a>
                                               </li>
                                           <?php endfor; ?>
                                           
                                           <?php if ($end_page < $total_pages): ?>
                                               <?php if ($end_page < $total_pages - 1): ?>
                                                   <li class="page-item disabled"><span class="page-link">...</span></li>
                                               <?php endif; ?>
                                               <li class="page-item">
                                                   <a class="page-link" href="?page=<?= $total_pages ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $total_pages ?></a>
                                               </li>
                                           <?php endif; ?>
                                           
                                           <?php if ($page < $total_pages): ?>
                                               <li class="page-item">
                                                   <a class="page-link" href="?page=<?= $page + 1 ?><?= $type_filter ? '&type=' . $type_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                       <i class="fas fa-chevron-right"></i>
                                                   </a>
                                               </li>
                                           <?php endif; ?>
                                       </ul>
                                   </nav>
                               </div>
                           <?php endif; ?>
                           
                       <?php else: ?>
                           <!-- Empty State -->
                           <div class="empty-state text-center py-5">
                               <i class="fas fa-inbox text-muted mb-3" style="font-size: 4rem;"></i>
                               <h4 class="text-muted">
                                   <?php if (!empty($status_filter) || !empty($type_filter) || !empty($search)): ?>
                                       Tidak ada pesanan ditemukan
                                   <?php else: ?>
                                       Belum ada pesanan
                                   <?php endif; ?>
                               </h4>
                               <p class="text-muted mb-4">
                                   <?php if (!empty($status_filter) || !empty($type_filter) || !empty($search)): ?>
                                       Coba ubah filter pencarian atau buat pesanan baru
                                   <?php else: ?>
                                       Mulai pesanan pertama Anda sekarang!
                                   <?php endif; ?>
                               </p>
                               <div class="empty-actions">
                                   <?php if (!empty($status_filter) || !empty($type_filter) || !empty($search)): ?>
                                       <a href="orders.php" class="btn btn-outline-primary me-2">
                                           <i class="fas fa-refresh me-2"></i>Reset Filter
                                       </a>
                                   <?php endif; ?>
                                   <a href="../services/print.php" class="btn btn-primary me-2">
                                       <i class="fas fa-print me-2"></i>Print Dokumen
                                   </a>
                                   <a href="../services/cetak.php" class="btn btn-success">
                                       <i class="fas fa-copy me-2"></i>Cetak Custom
                                   </a>
                               </div>
                           </div>
                       <?php endif; ?>
                   </div>
               </div>
           </div>
       </div>
   </div>
</section>

<!-- Custom CSS -->
<style>
:root {
    --primary-color: #3b82f6;
    --primary-dark: #1e40af;
    --secondary-color: #6b7280;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
    --dark-color: #1f2937;
    --light-color: #f8fafc;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.orders-section {
    background: var(--light-color);
    min-height: calc(100vh - 160px);
}

.orders-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.orders-subtitle {
    color: var(--secondary-color);
    font-size: 1.1rem;
    margin-bottom: 0;
}

/* Header Actions */
.header-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.header-actions .btn {
    font-weight: 600;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    border: 2px solid;
}

.header-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

.header-actions .btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.header-actions .btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}

.header-actions .btn-outline-primary {
    background: transparent;
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.header-actions .btn-outline-primary:hover {
    background: var(--primary-color);
    color: white;
}

.filter-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
    border: none;
    transition: all 0.3s ease;
}

.filter-card:hover {
    box-shadow: var(--shadow-lg);
}

.filter-card .form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.filter-card .form-select,
.filter-card .form-control {
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.filter-card .form-select:focus,
.filter-card .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.filter-card .btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
}

.filter-card .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.filter-card .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.orders-card {
    background: white;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    border: none;
    transition: all 0.3s ease;
}

.orders-card:hover {
    box-shadow: var(--shadow-lg);
}

.orders-card .card-header {
    background: var(--light-color);
    border-bottom: 1px solid #e5e7eb;
    padding: 1.25rem 1.5rem;
    border-radius: 1rem 1rem 0 0 !important;
}

.orders-card .card-header h5 {
    font-weight: 600;
    color: var(--dark-color);
    font-size: 1.125rem;
    margin: 0;
}

.orders-card .card-header .filter-info {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.orders-card .card-header .badge {
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 0.5rem;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: var(--dark-color);
    border-bottom: 2px solid #e5e7eb;
    padding: 1rem 1.25rem;
    background: var(--light-color);
    font-size: 0.875rem;
}

.table td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.order-detail {
    max-width: 250px;
}

.order-detail strong {
    color: var(--dark-color);
    font-weight: 600;
    display: block;
    margin-bottom: 0.25rem;
}

.order-detail small.text-muted {
    color: var(--secondary-color) !important;
}

.order-date {
    color: var(--dark-color);
    font-weight: 500;
}

.order-date small.text-muted {
    color: var(--secondary-color) !important;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 0.5rem;
}

/* Action Buttons Container */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-start;
}

.action-buttons .btn {
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    transition: all 0.3s ease;
    border: 2px solid;
    font-weight: 600;
    min-width: 40px;
    text-align: center;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Specific button styles */
.action-buttons .btn-outline-info {
    background: transparent;
    border-color: var(--info-color);
    color: var(--info-color);
}

.action-buttons .btn-outline-info:hover {
    background: var(--info-color);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-buttons .btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.action-buttons .btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.action-buttons .btn-warning {
    background: var(--warning-color);
    border-color: var(--warning-color);
    color: white;
}

.action-buttons .btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.action-buttons .btn-success {
    background: var(--success-color);
    border-color: var(--success-color);
    color: white;
}

.action-buttons .btn-success:hover {
    background: #059669;
    border-color: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.action-buttons .btn-outline-danger {
    background: transparent;
    border-color: var(--danger-color);
    color: var(--danger-color);
}

.action-buttons .btn-outline-danger:hover {
    background: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.action-buttons .btn i {
    font-size: 0.875rem;
}

.empty-state {
    padding: 4rem 1rem;
    text-align: center;
}

.empty-state i {
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-state h4 {
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--secondary-color);
    margin-bottom: 2rem;
}

.empty-actions {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
}

.empty-actions .btn {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 0.75rem;
    transition: all 0.3s ease;
}

.empty-actions .btn:hover {
    transform: translateY(-2px);
}

.pagination {
    margin-top: 2rem;
}

.pagination .page-link {
    color: var(--primary-color);
    border-color: #e5e7eb;
    padding: 0.6rem 1rem;
    border-radius: 0.5rem;
    margin: 0 0.25rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination .page-link:hover {
    background-color: #f3f4f6;
    color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.pagination .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    font-weight: 600;
}

.pagination .page-item.disabled .page-link {
    color: #9ca3af;
    background-color: #f9fafb;
}

/* Alert styling */
.alert {
    border-radius: 0.75rem;
    padding: 1.25rem 1.5rem;
    border: none;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    font-weight: 500;
}

.alert i {
    font-size: 1.25rem;
    margin-right: 1rem;
}

.alert-danger {
    background-color: #fee2e2;
    color: #b91c1c;
}

.alert-success {
    background-color: #d1fae5;
    color: #047857;
}

.alert-warning {
    background-color: #fef3c7;
    color: #b45309;
}

/* Loading and disabled states */
.cancel-btn.loading {
    pointer-events: none;
    opacity: 0.7;
}

.cancel-btn.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 992px) {
    .orders-title {
        font-size: 2rem;
    }
    
    .header-actions {
        margin-top: 1rem;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .orders-section {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .orders-title {
        font-size: 1.75rem;
        text-align: center;
    }
    
    .orders-subtitle {
        text-align: center;
    }
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .filter-card {
        padding: 1.25rem;
    }
    
    .filter-card .row .col-md-2,
    .filter-card .row .col-md-3,
    .filter-card .row .col-md-4 {
        margin-bottom: 1rem;
    }
    
    .orders-card .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .orders-card .card-header .filter-info {
        margin-top: 0.75rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
    
    .empty-actions {
        flex-direction: column;
    }
    
    .empty-actions .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .orders-title {
        font-size: 1.5rem;
    }
    
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .order-detail {
        max-width: 100px;
    }
    
    .order-detail strong {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.4rem 0.6rem;
    }
    
    .action-buttons .btn {
        padding: 0.4rem 0.6rem;
        min-width: 35px;
    }
    
    .action-buttons .btn i {
        font-size: 0.75rem;
    }
    
    .pagination .page-link {
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
    }
}
</style>

<!-- JS for Cancel Order -->
<script>
function cancelOrder(orderNumber, orderType, orderId) {
    if (confirm(`Apakah Anda yakin ingin membatalkan pesanan ${orderNumber}?\n\nPesanan yang dibatalkan tidak dapat dikembalikan.`)) {
        // Tampilkan loading pada tombol yang diklik
        const clickedButton = event.target.closest('button');
        const originalHTML = clickedButton.innerHTML;
        clickedButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        clickedButton.disabled = true;
        clickedButton.classList.add('loading');
        
        // Kirim permintaan AJAX untuk membatalkan pesanan
        const formData = new FormData();
        formData.append('order_number', orderNumber);
        formData.append('order_type', orderType);
        formData.append('order_id', orderId);
        
        fetch('cancel_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Tampilkan pesan sukses
                showAlert('success', data.message);
                
                // Refresh halaman untuk menampilkan perubahan setelah 2 detik
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Tampilkan pesan error
                showAlert('danger', data.message || 'Terjadi kesalahan saat membatalkan pesanan');
                
                // Kembalikan tombol ke keadaan semula
                clickedButton.innerHTML = originalHTML;
                clickedButton.disabled = false;
                clickedButton.classList.remove('loading');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Terjadi kesalahan saat membatalkan pesanan');
            
            // Kembalikan tombol ke keadaan semula
            clickedButton.innerHTML = originalHTML;
            clickedButton.disabled = false;
            clickedButton.classList.remove('loading');
        });
    }
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert:not(.fixed-alert)');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container');
    const firstChild = container.firstElementChild;
    container.insertBefore(alertDiv, firstChild);
    
    // Scroll to alert
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Auto remove after 10 seconds for success, 15 seconds for error
    const autoRemoveTime = type === 'success' ? 10000 : 15000;
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, autoRemoveTime);
}

// Auto refresh orders every 60 seconds for pending payments
let autoRefreshInterval;

function startAutoRefresh() {
    // Check if there are pending payments
    const pendingPayments = document.querySelectorAll('.badge.bg-warning');
    
    if (pendingPayments.length > 0) {
        autoRefreshInterval = setInterval(function() {
            // Only refresh if page is visible
            if (document.visibilityState === 'visible') {
                // Add a subtle indicator that page is refreshing
                const refreshIndicator = document.createElement('div');
                refreshIndicator.className = 'position-fixed top-0 end-0 p-3';
                refreshIndicator.style.zIndex = '9999';
                refreshIndicator.innerHTML = `
                    <div class="toast show" role="alert">
                        <div class="toast-body bg-info text-white">
                            <i class="fas fa-sync-alt fa-spin me-2"></i>
                            Memperbarui status pesanan...
                        </div>
                    </div>
                `;
                document.body.appendChild(refreshIndicator);
                
                // Refresh page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        }, 60000); // 60 seconds
    }
}

// Stop auto refresh when page is hidden or user leaves
function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    
    // Add tooltips to action buttons
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add smooth transitions to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.style.transition = 'all 0.2s ease-in-out';
    });
    
    // Add loading state to filter button
    const filterForm = document.querySelector('.filter-card form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mencari...';
                submitBtn.disabled = true;
                
                // Re-enable after form submission
                setTimeout(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }, 2000);
            }
        });
    }
    
    // Add confirmation for payment links
    const paymentLinks = document.querySelectorAll('a[href*="payment.php"]');
    paymentLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const originalHTML = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memproses...';
            this.style.pointerEvents = 'none';
            
            // Restore after 3 seconds in case of issues
            setTimeout(() => {
                this.innerHTML = originalHTML;
                this.style.pointerEvents = 'auto';
            }, 3000);
        });
    });
    
    // Highlight new orders (created within last 24 hours)
    const orderRows = document.querySelectorAll('tbody tr');
    orderRows.forEach(row => {
        const dateCell = row.querySelector('.order-date');
        if (dateCell) {
            const dateText = dateCell.textContent.trim();
            const dateParts = dateText.split(' ')[0].split('/');
            if (dateParts.length === 3) {
                // Format date is dd/mm/yyyy
                const orderDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                const now = new Date();
                const timeDiff = now - orderDate;
                const hoursDiff = timeDiff / (1000 * 3600);
                
                if (hoursDiff < 24) {
                    row.classList.add('table-warning');
                    row.setAttribute('title', 'Pesanan baru');
                }
            }
        }
    });
});

// Clean up when page unloads
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R: Refresh page
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    
    // Escape: Close any open modals or clear search
    if (e.key === 'Escape') {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value) {
            searchInput.value = '';
            searchInput.focus();
        }
    }
});

// Add status refresh indicator for pending orders
function addStatusRefreshIndicator() {
    const pendingRows = document.querySelectorAll('tbody tr');
    pendingRows.forEach(row => {
        const statusBadge = row.querySelector('.badge.bg-warning');
        if (statusBadge && statusBadge.textContent.includes('Menunggu')) {
            // Check if indicator already exists
            if (!statusBadge.querySelector('.fas.fa-clock')) {
                const indicator = document.createElement('i');
                indicator.className = 'fas fa-clock ms-1 text-warning';
                indicator.title = 'Status akan diperbarui otomatis';
                statusBadge.appendChild(indicator);
            }
        }
    });
}

// Initialize status indicators
document.addEventListener('DOMContentLoaded', function() {
    addStatusRefreshIndicator();
});
</script>

<?php include '../includes/footer.php'; ?>