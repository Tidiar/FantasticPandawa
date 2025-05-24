<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Inisialisasi variabel
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Menangani tindakan massal
if (isset($_POST['bulk_action']) && isset($_POST['selected_orders'])) {
    $bulk_action = $_POST['bulk_action'];
    $selected_orders = $_POST['selected_orders'];
    
    if ($bulk_action == 'change_status' && isset($_POST['new_status'])) {
        $new_status = $_POST['new_status'];
        $result = updateOrdersStatus($selected_orders, $new_status, 'cetak');
        if ($result) {
            $_SESSION['success_message'] = "Status berhasil diperbarui untuk " . count($selected_orders) . " pesanan yang dipilih.";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui status pesanan.";
        }
        // Redirect untuk mencegah resubmission
        header("Location: cetak-orders.php?" . http_build_query($_GET));
        exit;
    } elseif ($bulk_action == 'delete') {
        $result = deleteOrders($selected_orders, 'cetak');
        if ($result) {
            $_SESSION['success_message'] = count($selected_orders) . " pesanan berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus pesanan.";
        }
        // Redirect untuk mencegah resubmission
        header("Location: cetak-orders.php?" . http_build_query($_GET));
        exit;
    }
}

// Menangani ekspor CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    exportCetakOrdersCSV($status_filter, $search, $date_from, $date_to);
    exit;
}

// Dapatkan pesanan cetak dengan filter
$orders = getCetakOrders($status_filter, $search, $date_from, $date_to, $limit, $offset);
$total_orders = countCetakOrders($status_filter, $search, $date_from, $date_to);
$total_pages = ceil($total_orders / $limit);

// Statistik singkat
$stats = [
    'total' => countCetakOrders(),
    'pending' => countCetakOrders('pending'),
    'processing' => countCetakOrders('processing'),
    'completed' => countCetakOrders('completed'),
    'canceled' => countCetakOrders('canceled')
];

// Judul halaman
$page_title = "Pesanan Cetak - Panel Admin";

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
                        <i class="fas fa-print"></i> Pesanan Cetak
                        <?php if ($status_filter): ?>
                            <small class="text-muted">- <?= translateStatus($status_filter) ?></small>
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item active">Pesanan Cetak</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Tampilkan pesan sukses/error dari session -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-check"></i> <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-ban"></i> <?= $_SESSION['error_message'] ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Notifikasi dari GET parameter -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-check"></i> Status pesanan berhasil diperbarui!
                </div>
            <?php endif; ?>
            
            <!-- Statistik Singkat -->
            <div class="row mb-3">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['total'] ?></h3>
                            <p>Total Pesanan</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <a href="cetak-orders.php" class="small-box-footer">
                            Lihat Semua <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['pending'] ?></h3>
                            <p>Tertunda</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="cetak-orders.php?status=pending" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= $stats['processing'] ?></h3>
                            <p>Diproses</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <a href="cetak-orders.php?status=processing" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['completed'] ?></h3>
                            <p>Selesai</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <a href="cetak-orders.php?status=completed" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Kartu Filter -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i> Filter Pencarian
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-flag"></i> Status:</label>
                                    <select name="status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>
                                            Tertunda (<?= $stats['pending'] ?>)
                                        </option>
                                        <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                        <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>
                                            Diproses (<?= $stats['processing'] ?>)
                                        </option>
                                        <option value="ready" <?= $status_filter == 'ready' ? 'selected' : '' ?>>Siap</option>
                                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>
                                            Selesai (<?= $stats['completed'] ?>)
                                        </option>
                                        <option value="canceled" <?= $status_filter == 'canceled' ? 'selected' : '' ?>>
                                            Dibatalkan (<?= $stats['canceled'] ?>)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-search"></i> Cari:</label>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="No. Pesanan, Nama Pelanggan, dll." 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Tanggal Dari:</label>
                                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Tanggal Sampai:</label>
                                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Terapkan Filter
                                </button>
                                <a href="cetak-orders.php" class="btn btn-default">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                                <span class="text-muted ml-3">
                                    Menampilkan <?= count($orders) ?> dari <?= $total_orders ?> pesanan
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Kartu Tabel Pesanan -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Daftar Pesanan Cetak
                        <?= $search ? '- Pencarian: ' . htmlspecialchars($search) : '' ?>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#helpModal">
                            <i class="fas fa-question-circle"></i> Bantuan
                        </button>
                        <a href="cetak-orders.php?export=csv<?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>" 
                           class="btn btn-sm btn-success">
                            <i class="fas fa-download mr-1"></i> Ekspor CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($orders) > 0): ?>
                        <form id="bulkActionForm" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select name="bulk_action" class="form-control" id="bulkAction">
                                        <option value="">Tindakan Massal</option>
                                        <option value="change_status">Ubah Status</option>
                                        <option value="delete">Hapus</option>
                                    </select>
                                </div>
                                <div class="col-md-3" id="statusSelectContainer" style="display: none;">
                                    <select name="new_status" class="form-control">
                                        <option value="pending">Tertunda</option>
                                        <option value="confirmed">Dikonfirmasi</option>
                                        <option value="processing">Diproses</option>
                                        <option value="ready">Siap</option>
                                        <option value="completed">Selesai</option>
                                        <option value="canceled">Dibatalkan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" id="applyBulkAction" class="btn btn-primary" disabled>
                                        <i class="fas fa-check"></i> Terapkan
                                    </button>
                                    <span id="selectedCount" class="text-muted ml-2"></span>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead class="thead-primary">
                                        <tr>
                                            <th width="30px">
                                                <input type="checkbox" id="checkAll" title="Pilih Semua">
                                            </th>
                                            <th>No. Pesanan</th>
                                            <th>Pelanggan</th>
                                            <th>Jenis Cetakan</th>
                                            <th>Detail</th>
                                            <th>Tanggal</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                            <th width="140px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_orders[]" 
                                                           value="<?= $order['id'] ?>" class="order-checkbox">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                                    <br><small class="text-muted">ID: <?= $order['id'] ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></strong>
                                                    <?php if (isset($order['customer_email'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= translateCetakType($order['cetak_type']) ?>
                                                </td>
                                                <td>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <strong>Jumlah:</strong> <?= $order['quantity'] ?><br>
                                                            <strong>Kertas:</strong> <?= htmlspecialchars($order['paper_type']) ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Finishing:</strong> <?= htmlspecialchars($order['finishing']) ?><br>
                                                            <strong>Pengiriman:</strong> <?= $order['delivery'] == 'pickup' ? 'Ambil Sendiri' : 'Dikirim' ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= formatDate($order['created_at']) ?>
                                                    <br><small class="text-muted"><?= formatTime($order['created_at']) ?></small>
                                                </td>
                                                <td>
                                                    <strong class="text-success">Rp <?= number_format($order['price'], 0, ',', '.') ?></strong>
                                                    <?php if (isset($order['payment_status'])): ?>
                                                        <br><small class="badge badge-<?= getPaymentStatusBadgeClass($order['payment_status']) ?>">
                                                            <?= translatePaymentStatus($order['payment_status']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?> p-2">
                                                        <?= translateStatus($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                                        <a href="cetak-orders-detail.php?id=<?= $order['id'] ?>" 
                                                           class="btn btn-info" title="Lihat Detail">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </a>
                                                        <button type="button" class="btn btn-primary status-change-btn" 
                                                                data-toggle="modal" 
                                                                data-target="#changeStatusModal" 
                                                                data-order-id="<?= $order['id'] ?>" 
                                                                data-order-number="<?= htmlspecialchars($order['order_number']) ?>"
                                                                data-current-status="<?= $order['status'] ?>"
                                                                title="Ubah Status">
                                                            <i class="fas fa-sync-alt"></i> Status
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Tidak ada pesanan cetak ditemukan</h4>
                            <p class="text-muted">
                                <?php if ($status_filter || $search || $date_from || $date_to): ?>
                                    Coba ubah filter pencarian atau <a href="cetak-orders.php" class="btn btn-sm btn-primary">reset filter</a>
                                <?php else: ?>
                                    Belum ada pesanan cetak yang masuk
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                                <i class="fas fa-angle-left"></i> Sebelumnya
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                                Selanjutnya <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $total_pages ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <div class="text-center text-muted">
                                Halaman <?= $page ?> dari <?= $total_pages ?> 
                                (<?= $total_orders ?> total pesanan)
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Ubah Status -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST" action="update-cetak-order-status.php">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="changeStatusModalLabel">
                        <i class="fas fa-sync-alt"></i> Ubah Status Pesanan
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="orderIdInput">
                    <input type="hidden" name="redirect_to" value="list">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Mengubah status untuk pesanan: <strong id="orderNumberDisplay"></strong>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="statusSelect">
                                    <i class="fas fa-flag"></i> Status Baru: <span class="text-danger">*</span>
                                </label>
                                <select name="new_status" id="statusSelect" class="form-control form-control-lg" required>
                                    <option value="pending">🕐 Tertunda</option>
                                    <option value="confirmed">✅ Dikonfirmasi</option>
                                    <option value="processing">⚙️ Diproses</option>
                                    <option value="ready">📦 Siap</option>
                                    <option value="completed">🎉 Selesai</option>
                                    <option value="canceled">❌ Dibatalkan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status Saat Ini:</label>
                                <div id="currentStatusDisplay" class="form-control-plaintext"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="statusNotes">
                            <i class="fas fa-sticky-note"></i> Catatan Perubahan (Opsional):
                        </label>
                        <textarea name="notes" id="statusNotes" class="form-control" rows="3" 
                                  placeholder="Tambahkan catatan atau alasan perubahan status..."></textarea>
                        <small class="form-text text-muted">
                            Catatan ini akan tersimpan dalam riwayat pesanan
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notifyCustomer">
                            <label class="custom-control-label" for="notifyCustomer">
                                <i class="fas fa-envelope"></i> Kirim notifikasi email ke pelanggan
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bantuan -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle"></i> Bantuan - Manajemen Pesanan Cetak
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6><i class="fas fa-flag"></i> Status Pesanan:</h6>
                <ul>
                    <li><span class="badge badge-warning">Tertunda</span> - Pesanan baru yang belum diproses</li>
                    <li><span class="badge badge-info">Dikonfirmasi</span> - Pesanan telah dikonfirmasi dan siap diproses</li>
                    <li><span class="badge badge-primary">Diproses</span> - Pesanan sedang dalam proses pencetakan</li>
                    <li><span class="badge badge-success">Siap</span> - Pesanan selesai dan siap diambil/dikirim</li>
                    <li><span class="badge badge-success">Selesai</span> - Pesanan telah diselesaikan</li>
                    <li><span class="badge badge-danger">Dibatalkan</span> - Pesanan dibatalkan</li>
                </ul>
                
                <h6><i class="fas fa-tools"></i> Fitur Tindakan Massal:</h6>
                <ul>
                    <li>Pilih beberapa pesanan dengan checkbox</li>
                    <li>Gunakan dropdown "Tindakan Massal" untuk mengubah status atau menghapus</li>
                    <li>Klik "Terapkan" untuk menjalankan tindakan</li>
                </ul>
                
                <h6><i class="fas fa-search"></i> Tips Pencarian:</h6>
                <ul>
                    <li>Cari berdasarkan nomor pesanan, nama pelanggan, atau email</li>
                    <li>Gunakan filter tanggal untuk mencari pesanan dalam periode tertentu</li>
                    <li>Kombinasikan filter status dengan pencarian untuk hasil yang lebih spesifik</li>
                </ul>
                
                <h6><i class="fas fa-keyboard"></i> Shortcut Keyboard:</h6>
                <ul>
                    <li><kbd>Ctrl + A</kbd> - Pilih semua pesanan</li>
                    <li><kbd>Ctrl + F</kbd> - Fokus ke field pencarian</li>
                    <li><kbd>Enter</kbd> - Submit form aktif</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-check"></i> Mengerti
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk manajemen pesanan cetak
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Cetak Orders Management System initialized');
    
    // Elemen-elemen utama
    const checkAll = document.getElementById('checkAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkAction = document.getElementById('bulkAction');
    const applyBulkAction = document.getElementById('applyBulkAction');
    const statusSelectContainer = document.getElementById('statusSelectContainer');
    const selectedCount = document.getElementById('selectedCount');
    
    // Fungsi untuk update jumlah item terpilih
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        if (selectedCount) {
            selectedCount.textContent = checkedCount > 0 ? `(${checkedCount} dipilih)` : '';
        }
        return checkedCount;
    }
    
    // Centang/hapus centang semua checkbox
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const isChecked = this.checked;
            orderCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            const checkedCount = updateSelectedCount();
            if (applyBulkAction) {
                applyBulkAction.disabled = checkedCount === 0;
            }
        });
    }
    
    // Event listener untuk checkbox individual
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = updateSelectedCount();
            
            // Update tombol terapkan
            if (applyBulkAction) {
                applyBulkAction.disabled = checkedCount === 0;
            }
            
            // Update checkbox "pilih semua"
            if (checkAll) {
                const allChecked = checkedCount === orderCheckboxes.length;
                const someChecked = checkedCount > 0;
                checkAll.checked = allChecked;
                checkAll.indeterminate = someChecked && !allChecked;
            }
        });
    });
    
    // Tampilkan/sembunyikan dropdown status untuk tindakan massal
    if (bulkAction && statusSelectContainer) {
        bulkAction.addEventListener('change', function() {
            const showStatusSelect = this.value === 'change_status';
            statusSelectContainer.style.display = showStatusSelect ? 'block' : 'none';
            
            // Update text tombol
            if (applyBulkAction) {
                const buttonText = this.value === 'delete' ? 
                    '<i class="fas fa-trash"></i> Hapus' : 
                    '<i class="fas fa-check"></i> Terapkan';
                applyBulkAction.innerHTML = buttonText;
                
                // Update warna tombol
                applyBulkAction.className = this.value === 'delete' ? 
                    'btn btn-danger' : 'btn btn-primary';
            }
        });
    }
    
    // Konfirmasi untuk tindakan berbahaya
    const bulkActionForm = document.getElementById('bulkActionForm');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const action = bulkAction ? bulkAction.value : '';
            const checkedCount = updateSelectedCount();
            
            if (checkedCount === 0) {
                e.preventDefault();
                alert('⚠️ Pilih minimal satu pesanan terlebih dahulu!');
                return false;
            }
            
            let confirmMessage = '';
            if (action === 'delete') {
                confirmMessage = `🗑️ Apakah Anda yakin ingin menghapus ${checkedCount} pesanan?\n\nTindakan ini tidak dapat dibatalkan!`;
            } else if (action === 'change_status') {
                const newStatus = document.querySelector('select[name="new_status"]').value;
                const statusText = document.querySelector(`select[name="new_status"] option[value="${newStatus}"]`).textContent;
                confirmMessage = `🔄 Apakah Anda yakin ingin mengubah status ${checkedCount} pesanan menjadi "${statusText}"?`;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    // Script untuk modal ubah status
    const statusChangeBtns = document.querySelectorAll('.status-change-btn');
    console.log(`📝 Found ${statusChangeBtns.length} status change buttons`);
    
    statusChangeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const orderNumber = this.getAttribute('data-order-number');
            const currentStatus = this.getAttribute('data-current-status');
            
            console.log(`🔧 Opening status modal for order: ${orderNumber} (ID: ${orderId})`);
            
            // Update modal content
            const orderIdInput = document.getElementById('orderIdInput');
            const statusSelect = document.getElementById('statusSelect');
            const orderNumberDisplay = document.getElementById('orderNumberDisplay');
            const currentStatusDisplay = document.getElementById('currentStatusDisplay');
            const notesField = document.getElementById('statusNotes');
            
            if (orderIdInput) orderIdInput.value = orderId;
            if (statusSelect) statusSelect.value = currentStatus;
            if (orderNumberDisplay) orderNumberDisplay.textContent = orderNumber;
            if (notesField) notesField.value = '';
            
            if (currentStatusDisplay) {
                const statusBadgeClass = getStatusBadgeClass(currentStatus);
                const statusText = translateStatus(currentStatus);
                currentStatusDisplay.innerHTML = `<span class="badge badge-${statusBadgeClass} p-2">${statusText}</span>`;
            }
        });
    });
    
    // Validasi form modal
    const changeStatusForm = document.getElementById('changeStatusForm');
    if (changeStatusForm) {
        changeStatusForm.addEventListener('submit', function(e) {
            const orderId = document.getElementById('orderIdInput').value;
            const newStatus = document.getElementById('statusSelect').value;
            
            if (!orderId || !newStatus) {
                e.preventDefault();
                alert('❌ Data tidak lengkap! Pastikan semua field terisi.');
                return false;
            }
            
            // Tambahkan loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            }
            
            console.log(`💾 Submitting status change: Order ${orderId} -> ${newStatus}`);
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+A untuk select all (jika tidak dalam input field)
        if (e.ctrlKey && e.key === 'a' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
            e.preventDefault();
            if (checkAll) {
                checkAll.checked = true;
                checkAll.dispatchEvent(new Event('change'));
            }
        }
        
        // Ctrl+F untuk fokus ke search
        if (e.ctrlKey && e.key === 'f') {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape untuk tutup modal
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                $(openModal).modal('hide');
            }
        }
    });
    
    // Helper functions
    function getStatusBadgeClass(status) {
        const badgeClasses = {
            'pending': 'warning',
            'confirmed': 'info',
            'processing': 'primary',
            'ready': 'success',
            'completed': 'success',
            'canceled': 'danger'
        };
        return badgeClasses[status] || 'secondary';
    }
    
    function translateStatus(status) {
        const translations = {
            'pending': 'Tertunda',
            'confirmed': 'Dikonfirmasi',
            'processing': 'Diproses',
            'ready': 'Siap',
            'completed': 'Selesai',
            'canceled': 'Dibatalkan'
        };
        return translations[status] || status;
    }
    
    // Tooltip initialization
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    console.log('✅ Cetak Orders Management System ready!');
});

// Fungsi untuk quick actions (bisa dipanggil dari luar)
function quickStatusChange(orderId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'update-cetak-order-status.php';
    form.style.display = 'none';
    
    const orderIdInput = document.createElement('input');
    orderIdInput.type = 'hidden';
    orderIdInput.name = 'order_id';
    orderIdInput.value = orderId;
    
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = newStatus;
    
    const redirectInput = document.createElement('input');
    redirectInput.type = 'hidden';
    redirectInput.name = 'redirect_to';
    redirectInput.value = 'list';
    
    form.appendChild(orderIdInput);
    form.appendChild(statusInput);
    form.appendChild(redirectInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Refresh page data tanpa reload penuh
function refreshOrderData() {
    location.reload();
}
</script>

<style>
/* Updated Table Header Styles - Blue Theme */
.thead-primary {
    background-color: #3b82f6 !important;
    color: white !important;
}

.thead-primary th {
    background-color: #3b82f6 !important;
    color: white !important;
    border-color: #2563eb !important;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    font-size: 0.9rem;
}

.thead-primary th:hover {
    background-color: #2563eb !important;
    transition: background-color 0.2s ease;
}

/* Custom styles untuk Cetak Orders */
.table-hover tbody tr:hover {
    background-color: rgba(59, 130, 246, 0.075) !important;
}

.badge {
    font-size: 0.75em;
}

.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.modal-lg {
    max-width: 800px;
}

.form-control-lg {
    font-size: 1.1rem;
}

.small-box .icon {
    font-size: 70px;
}

.pagination .page-link {
    border-radius: 0.25rem;
    margin: 0 2px;
}

.alert {
    border-left: 4px solid;
}

.alert-success {
    border-left-color: #10b981;
}

.alert-danger {
    border-left-color: #ef4444;
}

.alert-info {
    border-left-color: #3b82f6;
}

/* Table improvements */
.table {
    background-color: white;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table th {
    border-top: none;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.table-bordered {
    border: 1px solid #e5e7eb;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #e5e7eb;
}

/* Row striping with blue theme */
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(59, 130, 246, 0.02);
}

/* Loading animation */
.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(359deg); }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-group-vertical .btn {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .small-box .inner h3 {
        font-size: 1.5rem;
    }
    
    .thead-primary th {
        font-size: 0.8rem;
        padding: 8px 6px;
    }
}

/* Print styles */
@media print {
    .card-tools,
    .btn,
    .pagination,
    .modal {
        display: none !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
    
    .thead-primary th {
        background-color: #3b82f6 !important;
        color: white !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>