<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug($message, $data = null) {
    echo "<!-- DEBUG: $message";
    if ($data !== null) {
        echo " - " . print_r($data, true);
    }
    echo " -->\n";
}

// Periksa apakah ID disediakan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    debug("ID parameter missing", $_GET);
    $_SESSION['error_message'] = "ID Pesanan tidak valid.";
    header("Location: print-orders.php");
    exit;
}

$order_id = $_GET['id'];
debug("Order ID received", $order_id);

// Validasi order_id adalah angka
if (!is_numeric($order_id)) {
    debug("Order ID not numeric", $order_id);
    $_SESSION['error_message'] = "ID Pesanan harus berupa angka.";
    header("Location: print-orders.php");
    exit;
}

$order_id = (int)$order_id;

// Dapatkan detail pesanan print dengan query yang lebih robust
try {
    debug("Attempting to fetch order details");
    
    // Query yang lebih sederhana dan debug-friendly
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
    
    debug("Query executed, result", $order ? "Found" : "Not found");
    
    if (!$order) {
        // Cek apakah order_id ada di tabel tapi dengan kondisi berbeda
        $check_stmt = $conn->prepare("SELECT id, order_number, status FROM print_orders WHERE id = ?");
        $check_stmt->execute([$order_id]);
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        debug("Check query result", $check_result);
        
        if ($check_result) {
            $_SESSION['error_message'] = "Pesanan ditemukan tapi data tidak lengkap. ID: {$order_id}, Order: {$check_result['order_number']}";
        } else {
            $_SESSION['error_message'] = "Pesanan dengan ID {$order_id} tidak ditemukan di database.";
        }
        
        header("Location: print-orders.php");
        exit;
    }
    
} catch (PDOException $e) {
    debug("Database error", $e->getMessage());
    $_SESSION['error_message'] = "Error database: " . $e->getMessage();
    header("Location: print-orders.php");
    exit;
}

// Dapatkan riwayat status pesanan
$status_history = [];
try {
    // Cek apakah tabel history ada
    $check_table = $conn->query("SHOW TABLES LIKE 'print_order_status_history'");
    if ($check_table->rowCount() > 0) {
        $stmt = $conn->prepare("SELECT h.*, u.name as changed_by_name
                               FROM print_order_status_history h
                               LEFT JOIN users u ON h.changed_by = u.id
                               WHERE h.order_id = ?
                               ORDER BY h.created_at DESC");
        $stmt->execute([$order_id]);
        $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        debug("Status history found", count($status_history) . " records");
    } else {
        debug("Status history table not found");
        // Buat tabel history jika belum ada
        $create_table = "CREATE TABLE IF NOT EXISTS print_order_status_history (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes TEXT NULL,
            changed_by INT(11) UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($create_table);
        debug("Created status history table");
    }
} catch (PDOException $e) {
    debug("Error getting status history", $e->getMessage());
    $status_history = [];
}

// Dapatkan daftar staff untuk penugasan
$staff_list = [];
try {
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role IN ('admin', 'manager', 'staff') AND status = 'active' ORDER BY name");
    $stmt->execute();
    $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug("Staff list found", count($staff_list) . " staff members");
} catch (PDOException $e) {
    debug("Error getting staff list", $e->getMessage());
    $staff_list = [];
}

// Handle pembaruan status
if (isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'];
    
    debug("Status update request", ['new_status' => $new_status, 'notes' => $notes]);
    
    try {
        $conn->beginTransaction();
        
        // Update status pesanan
        $stmt = $conn->prepare("UPDATE print_orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Tambahkan ke riwayat
        $stmt = $conn->prepare("INSERT INTO print_order_status_history (order_id, status, notes, changed_by, created_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$order_id, $new_status, $notes, $_SESSION['user_id']]);
        
        $conn->commit();
        $success_message = "Status pesanan berhasil diperbarui menjadi " . translateStatus($new_status) . ".";
        
        // Refresh data pesanan
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
        
        // Refresh status history
        $stmt = $conn->prepare("SELECT h.*, u.name as changed_by_name
                               FROM print_order_status_history h
                               LEFT JOIN users u ON h.changed_by = u.id
                               WHERE h.order_id = ?
                               ORDER BY h.created_at DESC");
        $stmt->execute([$order_id]);
        $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        debug("Status updated successfully");
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = "Gagal memperbarui status pesanan: " . $e->getMessage();
        debug("Status update failed", $e->getMessage());
    }
}

// Handle penugasan staff
if (isset($_POST['assign_staff'])) {
    $staff_id = $_POST['staff_id'];
    
    debug("Staff assignment request", ['staff_id' => $staff_id]);
    
    try {
        $stmt = $conn->prepare("UPDATE print_orders SET assigned_to = ? WHERE id = ?");
        $stmt->execute([$staff_id, $order_id]);
        
        $success_message = "Pesanan berhasil ditugaskan kepada staff.";
        
        // Refresh data pesanan
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
        
        debug("Staff assigned successfully");
    } catch (Exception $e) {
        $error_message = "Gagal menugaskan pesanan kepada staff: " . $e->getMessage();
        debug("Staff assignment failed", $e->getMessage());
    }
}

// Handle pembaruan harga
if (isset($_POST['update_price'])) {
    $price = $_POST['price'];
    
    debug("Price update request", ['price' => $price]);
    
    try {
        $stmt = $conn->prepare("UPDATE print_orders SET price = ? WHERE id = ?");
        $stmt->execute([$price, $order_id]);
        
        $success_message = "Harga pesanan berhasil diperbarui.";
        
        // Refresh data pesanan
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
        
        debug("Price updated successfully");
    } catch (Exception $e) {
        $error_message = "Gagal memperbarui harga pesanan: " . $e->getMessage();
        debug("Price update failed", $e->getMessage());
    }
}

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Judul halaman
$page_title = "Detail Pesanan Print #{$order['order_number']} - Panel Admin";

// Sertakan header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <!-- Header Konten -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-file-alt"></i> Detail Pesanan Print
                        <small class="text-muted">#<?= htmlspecialchars($order['order_number']) ?></small>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item"><a href="print-orders.php">Pesanan Print</a></li>
                        <li class="breadcrumb-item active">Detail Pesanan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Debug info untuk development -->
            <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <h5>Debug Information:</h5>
                    <p><strong>Order ID:</strong> <?= $order_id ?></p>
                    <p><strong>Order Data:</strong> <?= $order ? 'Found' : 'Not Found' ?></p>
                    <p><strong>Database Connection:</strong> <?= $conn ? 'OK' : 'Failed' ?></p>
                    <p><strong>Session User:</strong> <?= $_SESSION['user_id'] ?? 'Not set' ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Tampilkan pesan sukses/error jika ada -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Kesalahan!</h5>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-<?= getStatusBadgeClass($order['status']) == 'warning' ? 'warning' : (getStatusBadgeClass($order['status']) == 'success' ? 'success' : 'info') ?>">
                        <h4><i class="fas fa-flag"></i> Status: 
                            <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?> p-2">
                                <?= translateStatus($order['status']) ?>
                            </span>
                        </h4>
                        <p class="mb-0">
                            Pesanan dibuat pada <?= formatDate($order['created_at'], true) ?>
                            <?php if ($order['updated_at'] != $order['created_at']): ?>
                                | Terakhir diperbarui <?= formatDate($order['updated_at'], true) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Detail Pesanan -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Informasi Pesanan #<?= htmlspecialchars($order['order_number']) ?>
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?> p-2">
                                    <?= translateStatus($order['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt><i class="fas fa-hashtag"></i> Nomor Pesanan</dt>
                                        <dd><strong><?= htmlspecialchars($order['order_number']) ?></strong></dd>
                                        
                                        <dt><i class="fas fa-calendar"></i> Tanggal Pesanan</dt>
                                        <dd><?= formatDate($order['created_at'], true) ?></dd>
                                        
                                        <dt><i class="fas fa-flag"></i> Status</dt>
                                        <dd>
                                            <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?> p-2">
                                                <?= translateStatus($order['status']) ?>
                                            </span>
                                        </dd>
                                        
                                        <dt><i class="fas fa-money-bill"></i> Harga</dt>
                                        <dd><strong class="text-success">Rp <?= number_format($order['price'], 0, ',', '.') ?></strong></dd>
                                        
                                        <dt><i class="fas fa-credit-card"></i> Status Pembayaran</dt>
                                        <dd>
                                            <span class="badge badge-<?= getPaymentStatusBadgeClass($order['payment_status']) ?>">
                                                <?= translatePaymentStatus($order['payment_status']) ?>
                                            </span>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt><i class="fas fa-user"></i> Pelanggan</dt>
                                        <dd><strong><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></strong></dd>
                                        
                                        <dt><i class="fas fa-envelope"></i> Email</dt>
                                        <dd>
                                            <?php if ($order['customer_email']): ?>
                                                <a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>">
                                                    <?= htmlspecialchars($order['customer_email']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak tersedia</span>
                                            <?php endif; ?>
                                        </dd>
                                        
                                        <dt><i class="fas fa-phone"></i> Telepon</dt>
                                        <dd>
                                            <?php if ($order['customer_phone']): ?>
                                                <a href="tel:<?= htmlspecialchars($order['customer_phone']) ?>">
                                                    <?= htmlspecialchars($order['customer_phone']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak tersedia</span>
                                            <?php endif; ?>
                                        </dd>
                                        
                                        <dt><i class="fas fa-user-tie"></i> Ditugaskan Kepada</dt>
                                        <dd>
                                            <?php if ($order['assigned_to_name']): ?>
                                                <span class="badge badge-info"><?= htmlspecialchars($order['assigned_to_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Belum ditugaskan</span>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <h5 class="mt-4"><i class="fas fa-print"></i> Detail Pesanan Print</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th style="width: 30%"><i class="fas fa-file"></i> Nama File</th>
                                            <td><?= htmlspecialchars($order['original_filename']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-copy"></i> Jumlah Salinan</th>
                                            <td><span class="badge badge-primary"><?= $order['copies'] ?> eksemplar</span></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-expand"></i> Ukuran Kertas</th>
                                            <td><?= htmlspecialchars($order['paper_size']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-palette"></i> Warna Cetakan</th>
                                            <td>
                                                <?php if ($order['print_color'] == 'Color'): ?>
                                                    <span class="badge badge-warning">🌈 Berwarna</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">⚫ Hitam Putih</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-layer-group"></i> Jenis Kertas</th>
                                            <td><?= htmlspecialchars($order['paper_type']) ?></td>
                                        </tr>
                                        <tr>
                                            <th><i class="fas fa-sticky-note"></i> Catatan</th>
                                            <td>
                                                <?php if ($order['notes']): ?>
                                                    <div class="bg-light p-2 rounded">
                                                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada catatan</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4">
                                <?php if (!empty($order['file_path']) && file_exists('../' . $order['file_path'])): ?>
                                    <a href="../<?= htmlspecialchars($order['file_path']) ?>" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-download"></i> Download File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-exclamation-triangle"></i> File tidak tersedia</span>
                                <?php endif; ?>
                                
                                <a href="print-orders.php" class="btn btn-default">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                                </a>
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#updatePriceModal">
                                    <i class="fas fa-money-bill"></i> Perbarui Harga
                                </button>
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deletePrintOrderModal">
                                    <i class="fas fa-trash"></i> Hapus Pesanan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar Aksi -->
                <div class="col-md-4">
                    <!-- Kartu Perbarui Status -->
<div class="card-body">
    <form method="POST" action="update-print-order-status.php" id="updateStatusForm">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-group">
            <label for="new_status"><i class="fas fa-flag"></i> Status Baru:</label>
            <select class="form-control form-control-lg" id="new_status" name="new_status">
                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>🕐 Tertunda</option>
                <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>✅ Dikonfirmasi</option>
                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>⚙️ Diproses</option>
                <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>📦 Siap</option>
                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>🎉 Selesai</option>
                <option value="canceled" <?= $order['status'] == 'canceled' ? 'selected' : '' ?>>❌ Dibatalkan</option>
            </select>
        </div>
        <div class="form-group">
            <label for="notes"><i class="fas fa-sticky-note"></i> Catatan (Opsional):</label>
            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan catatan perubahan status..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fas fa-save"></i> Perbarui Status
        </button>
    </form>
</div>
                    
<!-- Kartu Tugaskan Staff -->
<div class="card">
    <div class="card-header bg-info text-white">
        <h3 class="card-title">
            <i class="fas fa-user-tie"></i> Tugaskan Staff
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="assign-print-order-staff.php" id="assignStaffForm">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <div class="form-group">
                <label for="staff_id"><i class="fas fa-users"></i> Pilih Staff:</label>
                <select class="form-control" id="staff_id" name="staff_id">
                    <option value="">-- Pilih Staff --</option>
                    <?php foreach ($staff_list as $staff): ?>
                        <option value="<?= $staff['id'] ?>" <?= $order['assigned_to'] == $staff['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($staff['name']) ?> (<?= ucfirst($staff['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-info btn-block">
                <i class="fas fa-user-plus"></i> Tugaskan Staff
            </button>
        </form>
    </div>
</div>


                    
                    <!-- Kartu Riwayat Status -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h3 class="card-title">
                                <i class="fas fa-history"></i> Riwayat Status
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline timeline-inverse" style="margin: 0 10px 0 10px; padding-top: 10px;">
                                <?php if (count($status_history) > 0): ?>
                                    <?php foreach ($status_history as $index => $history): ?>
                                        <div class="time-label">
                                            <span class="bg-primary">
                                                <?= formatDate($history['created_at'], true) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <i class="fas fa-flag bg-<?= getStatusBadgeClass($history['status']) ?>"></i>
                                            <div class="timeline-item">
                                                <span class="time">
                                                    <i class="far fa-clock"></i> <?= formatTime($history['created_at']) ?>
                                                </span>
                                                <h3 class="timeline-header">
                                                    Status diubah menjadi <strong><?= translateStatus($history['status']) ?></strong>
                                                </h3>
                                                
                                                <?php if (!empty($history['notes'])): ?>
                                                    <div class="timeline-body">
                                                        <div class="bg-light p-2 rounded">
                                                            <?= nl2br(htmlspecialchars($history['notes'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="timeline-footer">
                                                    <small class="text-muted">
                                                        oleh <?= htmlspecialchars($history['changed_by_name'] ?? 'System') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div>
                                        <i class="far fa-clock bg-gray"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline-item">
                                        <h3 class="timeline-header text-muted">
                                            <i class="fas fa-info-circle"></i> Belum ada riwayat perubahan status
                                        </h3>
                                    </div>
                                    <div>
                                        <i class="far fa-clock bg-gray"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Perbarui Harga -->
<div class="modal fade" id="updatePriceModal" tabindex="-1" role="dialog" aria-labelledby="updatePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="updatePriceModalLabel">
                        <i class="fas fa-money-bill"></i> Perbarui Harga Pesanan
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Harga saat ini: <strong>Rp <?= number_format($order['price'], 0, ',', '.') ?></strong>
                    </div>
                    <div class="form-group">
                        <label for="price"><i class="fas fa-money-bill"></i> Harga Baru (Rp):</label>
                        <input type="number" class="form-control form-control-lg" id="price" name="price" 
                               value="<?= $order['price'] ?>" min="0" step="100" required>
                        <small class="form-text text-muted">Masukkan harga dalam Rupiah (tanpa titik atau koma)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" name="update_price" class="btn btn-warning">
                        <i class="fas fa-save"></i> Perbarui Harga
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Pesanan -->
<div class="modal fade" id="deletePrintOrderModal" tabindex="-1" role="dialog" aria-labelledby="deletePrintOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deletePrintOrderModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Pesanan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Peringatan!</h5>
                    <p>Apakah Anda yakin ingin menghapus pesanan print ini?</p>
                    <ul>
                        <li><strong>Nomor Pesanan:</strong> <?= htmlspecialchars($order['order_number']) ?></li>
                        <li><strong>Pelanggan:</strong> <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></li>
                        <li><strong>File:</strong> <?= htmlspecialchars($order['original_filename']) ?></li>
                    </ul>
                    <p class="text-danger"><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
                </div>
                <div class="form-group">
                    <label for="delete_reason">Alasan Penghapusan (Opsional):</label>
                    <textarea class="form-control" id="delete_reason" name="delete_reason" rows="3" 
                              placeholder="Jelaskan alasan penghapusan pesanan ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <form method="POST" action="process/delete-print-order.php" style="display: inline;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="delete_reason" id="hidden_delete_reason">
                    <button type="submit" class="btn btn-danger" onclick="setDeleteReason()">
                        <i class="fas fa-trash"></i> Ya, Hapus Pesanan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 Print Order Detail Page initialized');
    
    // Auto-refresh data setiap 30 detik (opsional)
    let autoRefreshInterval;
    
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(() => {
            // Hanya refresh jika tidak ada modal yang terbuka
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000); // 30 detik
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                // Disable button dan tampilkan loading
                submitButton.disabled = true;
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                
                // Re-enable button setelah 5 detik sebagai fallback
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }, 5000);
            }
        });
    });
    
    // Price input formatting
    const priceInput = document.getElementById('price');
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            // Remove non-numeric characters except decimal point
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Format with thousand separators for display
            if (this.value) {
                const formatted = parseInt(this.value).toLocaleString('id-ID');
                // Show formatted value in a helper element if exists
                const helper = document.getElementById('price-formatted');
                if (helper) {
                    helper.textContent = 'Rp ' + formatted;
                }
            }
        });
    }
    
    // Status change confirmation
    const statusForm = document.querySelector('form[method="POST"]');
    if (statusForm && statusForm.querySelector('select[name="new_status"]')) {
        statusForm.addEventListener('submit', function(e) {
            const newStatus = this.querySelector('select[name="new_status"]').value;
            const currentStatus = '<?= $order['status'] ?>';
            
            if (newStatus === currentStatus) {
                e.preventDefault();
                alert('Status yang dipilih sama dengan status saat ini!');
                return false;
            }
            
            // Konfirmasi untuk status tertentu
            if (newStatus === 'canceled') {
                if (!confirm('🚫 Apakah Anda yakin ingin membatalkan pesanan ini?\n\nPesanan yang dibatalkan tidak dapat dikembalikan ke status sebelumnya.')) {
                    e.preventDefault();
                    return false;
                }
            } else if (newStatus === 'completed') {
                if (!confirm('✅ Apakah Anda yakin pesanan ini sudah selesai?\n\nPastikan semua pekerjaan telah diselesaikan dan hasil sudah diberikan kepada pelanggan.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
    
    // File download tracking
    const downloadLink = document.querySelector('a[href*="uploads"]');
    if (downloadLink) {
        downloadLink.addEventListener('click', function() {
            console.log('📥 File downloaded:', this.href);
            
            // Track download (bisa dikirim ke analytics)
            // gtag('event', 'file_download', {
            //     'file_name': '<?= $order['original_filename'] ?>',
            //     'order_id': '<?= $order['id'] ?>'
            // });
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S untuk save (form yang visible)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const visibleForm = document.querySelector('form:not([style*="display: none"])');
            if (visibleForm) {
                const submitBtn = visibleForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                }
            }
        }
        
        // Ctrl+B untuk kembali
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            window.location.href = 'print-orders.php';
        }
        
        // Escape untuk tutup modal
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                $(openModal).modal('hide');
            }
        }
    });
    
    // Tooltip initialization
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Print functionality
    function printOrderDetails() {
        const printContent = document.querySelector('.content-wrapper').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
        
        // Reinitialize page
        location.reload();
    }
    
    // Add print button if needed
    const printButton = document.createElement('button');
    printButton.className = 'btn btn-outline-secondary ml-2';
    printButton.innerHTML = '<i class="fas fa-print"></i> Print';
    printButton.onclick = printOrderDetails;
    
    const backButton = document.querySelector('a[href="print-orders.php"]');
    if (backButton) {
        backButton.parentNode.insertBefore(printButton, backButton.nextSibling);
    }
    
    console.log('✅ Print Order Detail Page ready!');
    
    // Start auto-refresh (uncomment if needed)
    // startAutoRefresh();
});

// Function untuk set delete reason
function setDeleteReason() {
    const reason = document.getElementById('delete_reason').value;
    document.getElementById('hidden_delete_reason').value = reason;
}

// Function untuk quick status update (bisa dipanggil dari luar)
function quickStatusUpdate(newStatus) {
    const form = document.querySelector('form[method="POST"]');
    const statusSelect = form.querySelector('select[name="new_status"]');
    const notesField = form.querySelector('textarea[name="notes"]');
    
    statusSelect.value = newStatus;
    notesField.value = 'Status diubah melalui quick action';
    
    form.submit();
}

// Function untuk refresh page data
function refreshData() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('refresh', Date.now());
    window.location.href = currentUrl.toString();
}
</script>

<style>
/* Custom styles untuk detail page */
.timeline {
    position: relative;
    margin: 0 0 30px 0;
    padding: 0;
    list-style: none;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #ddd;
    left: 31px;
    margin: 0;
    border-radius: 2px;
}

.timeline-item {
    margin-right: 10px;
    margin-left: 55px;
    margin-top: 0;
    color: #333;
}

.timeline-item .time {
    color: #999;
    float: right;
    padding: 10px;
    font-size: 12px;
}

.timeline-item .timeline-header {
    margin: 0;
    color: #555;
    border-bottom: 1px solid #f4f4f4;
    padding: 10px;
    font-weight: 600;
    font-size: 16px;
}

.timeline-item .timeline-body {
    padding: 10px;
    background: #fff;
}

.timeline-item .timeline-footer {
    padding: 10px;
    background: #f4f4f4;
}

.timeline > li > .fa,
.timeline > li > .fas,
.timeline > li > .far,
.timeline > li > .fab,
.timeline > li > .fal,
.timeline > li > .fad,
.timeline > li > .fas {
    width: 30px;
    height: 30px;
    font-size: 15px;
    line-height: 30px;
    position: absolute;
    color: #666;
    background: #d2d6de;
    border-radius: 50%;
    text-align: center;
    left: 18px;
    top: 0;
}

.bg-warning { background-color: #ffc107 !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-primary { background-color: #007bff !important; }
.bg-success { background-color: #28a745 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-secondary { background-color: #6c757d !important; }

.time-label > span {
    font-weight: 600;
    color: #fff;
    background-color: #00a65a;
    display: inline-block;
    padding: 5px;
}

.card-header.bg-primary,
.card-header.bg-info,
.card-header.bg-warning,
.card-header.bg-secondary {
    border-bottom: 0;
}

.form-control-lg {
    height: calc(1.5em + 1rem + 2px);
    padding: 0.5rem 1rem;
    font-size: 1.25rem;
    line-height: 1.5;
    border-radius: 0.3rem;
}

.badge {
    font-size: 0.75em;
}

.alert {
    border-left: 4px solid;
}

.alert-success { border-left-color: #28a745; }
.alert-danger { border-left-color: #dc3545; }
.alert-warning { border-left-color: #ffc107; }
.alert-info { border-left-color: #17a2b8; }

/* Print styles */
@media print {
    .btn, .modal, .card-tools {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    .badge {
        border: 1px solid #000;
        color: #000 !important;
        background: transparent !important;
    }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .timeline-item {
        margin-left: 35px;
    }
    
    .timeline:before {
        left: 21px;
    }
    
    .timeline > li > .fa,
    .timeline > li > .fas {
        left: 8px;
        width: 25px;
        height: 25px;
        line-height: 25px;
        font-size: 13px;
    }
    
    .col-md-6 {
        margin-bottom: 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>