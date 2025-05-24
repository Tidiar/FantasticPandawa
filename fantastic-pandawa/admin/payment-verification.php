<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Inisialisasi variabel
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$payment_method_filter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$order_type_filter = isset($_GET['order_type']) ? $_GET['order_type'] : '';
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
if (isset($_POST['bulk_action']) && isset($_POST['selected_payments'])) {
    $bulk_action = $_POST['bulk_action'];
    $selected_payments = $_POST['selected_payments'];
    
    if ($bulk_action == 'approve') {
        approvePayments($selected_payments);
        $success_message = "Pembayaran yang dipilih berhasil disetujui.";
    } elseif ($bulk_action == 'reject') {
        rejectPayments($selected_payments);
        $success_message = "Pembayaran yang dipilih berhasil ditolak.";
    }
}

// Menangani ekspor CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    exportPaymentsCSV($status_filter, $payment_method_filter, $order_type_filter, $search, $date_from, $date_to);
    exit;
}

// Dapatkan pembayaran dengan filter
$payments = getPayments($status_filter, $payment_method_filter, $order_type_filter, $search, $date_from, $date_to, $limit, $offset);
$total_payments = countPayments($status_filter, $payment_method_filter, $order_type_filter, $search, $date_from, $date_to);
$total_pages = ceil($total_payments / $limit);

// Dapatkan statistik pembayaran
$payment_stats = getPaymentStats();

// Judul halaman
$page_title = "Verifikasi Pembayaran - Panel Admin";

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
                    <h1 class="m-0">Verifikasi Pembayaran</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item active">Verifikasi Pembayaran</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Tampilkan pesan sukses jika ada -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <!-- Kartu Statistik Pembayaran -->
            <div class="row mb-4">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $payment_stats['pending'] ?></h3>
                            <p>Menunggu Verifikasi</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="payment-verification.php?status=pending" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $payment_stats['paid'] ?></h3>
                            <p>Pembayaran Disetujui</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="payment-verification.php?status=paid" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $payment_stats['failed'] ?></h3>
                            <p>Pembayaran Ditolak</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <a href="payment-verification.php?status=failed" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= $payment_stats['expired'] ?></h3>
                            <p>Pembayaran Kedaluwarsa</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-hourglass-end"></i>
                        </div>
                        <a href="payment-verification.php?status=expired" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Kartu Filter -->
            <div class="card collapsed-card">
                <div class="card-header">
                    <h3 class="card-title">Filter</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                        <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="failed" <?= $status_filter == 'failed' ? 'selected' : '' ?>>Ditolak</option>
                                        <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Kedaluwarsa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Metode:</label>
                                    <select name="payment_method" class="form-control">
                                        <option value="">Semua Metode</option>
                                        <option value="qris" <?= $payment_method_filter == 'qris' ? 'selected' : '' ?>>QRIS</option>
                                        <option value="bank_transfer" <?= $payment_method_filter == 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Jenis Pesanan:</label>
                                    <select name="order_type" class="form-control">
                                        <option value="">Semua Jenis</option>
                                        <option value="print" <?= $order_type_filter == 'print' ? 'selected' : '' ?>>Print</option>
                                        <option value="cetak" <?= $order_type_filter == 'cetak' ? 'selected' : '' ?>>Cetak</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Cari:</label>
                                    <input type="text" name="search" class="form-control" placeholder="No. Pesanan, Nama, dll." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tanggal Dari:</label>
                                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tanggal Sampai:</label>
                                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                                <a href="payment-verification.php" class="btn btn-default">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Kartu Tabel Pembayaran -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Daftar Pembayaran <?= $status_filter ? '- ' . translatePaymentStatus($status_filter) : '' ?>
                        <?= $search ? '- Pencarian: ' . htmlspecialchars($search) : '' ?>
                    </h3>
                    <div class="card-tools">
                        <a href="payment-verification.php?export=csv<?= $status_filter ? '&status=' . $status_filter : '' ?><?= $payment_method_filter ? '&payment_method=' . $payment_method_filter : '' ?><?= $order_type_filter ? '&order_type=' . $order_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-download mr-1"></i> Ekspor CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="bulkActionForm" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select name="bulk_action" class="form-control" id="bulkAction">
                                    <option value="">Tindakan Massal</option>
                                    <option value="approve">Setujui Pembayaran</option>
                                    <option value="reject">Tolak Pembayaran</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" id="applyBulkAction" class="btn btn-primary" disabled>Terapkan</button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="30px">
                                            <input type="checkbox" id="checkAll">
                                        </th>
                                        <th>Kode Pembayaran</th>
                                        <th>No. Pesanan</th>
                                        <th>Pelanggan</th>
                                        <th>Jenis</th>
                                        <th>Metode</th>
                                        <th>Jumlah</th>
                                        <th>Bukti</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($payments) > 0): ?>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_payments[]" value="<?= $payment['id'] ?>" class="payment-checkbox">
                                                </td>
                                                <td>
                                                    <strong><?= $payment['payment_code'] ?></strong>
                                                    <?php if ($payment['payment_status'] == 'pending' && $payment['expired_at'] && strtotime($payment['expired_at']) < time()): ?>
                                                        <br><small class="text-danger">Kedaluwarsa</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?= $payment['order_type'] ?>-orders-detail.php?id=<?= $payment['order_id'] ?>" target="_blank">
                                                        <?= $payment['order_number'] ?>
                                                    </a>
                                                </td>
                                                <td><?= $payment['customer_name'] ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $payment['order_type'] == 'print' ? 'info' : 'success' ?>">
                                                        <?= ucfirst($payment['order_type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($payment['payment_method'] == 'qris'): ?>
                                                        <i class="fas fa-qrcode text-primary"></i> QRIS
                                                    <?php else: ?>
                                                        <i class="fas fa-university text-success"></i> Bank Transfer
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong>Rp <?= number_format($payment['amount'], 0, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($payment['payment_proof']): ?>
                                                        <button type="button" class="btn btn-sm btn-info view-proof-btn" 
                                                                data-toggle="modal" data-target="#proofModal" 
                                                                data-proof="<?= $payment['payment_proof'] ?>" 
                                                                data-payment-code="<?= $payment['payment_code'] ?>">
                                                            <i class="fas fa-eye"></i> Lihat
                                                        </button>
                                                    <?php else: ?>
                                                        <small class="text-muted">Belum upload</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= getPaymentStatusBadgeClass($payment['payment_status']) ?>">
                                                        <?= translatePaymentStatus($payment['payment_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= formatDate($payment['created_at']) ?></td>
                                                <td>
                                                    <a href="payment-detail.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                    <?php if ($payment['payment_status'] == 'pending' && $payment['payment_proof']): ?>
                                                        <button type="button" class="btn btn-sm btn-success approve-btn" 
                                                                data-toggle="modal" data-target="#approveModal" 
                                                                data-payment-id="<?= $payment['id'] ?>" 
                                                                data-payment-code="<?= $payment['payment_code'] ?>">
                                                            <i class="fas fa-check"></i> Setujui
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger reject-btn" 
                                                                data-toggle="modal" data-target="#rejectModal" 
                                                                data-payment-id="<?= $payment['id'] ?>" 
                                                                data-payment-code="<?= $payment['payment_code'] ?>">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada pembayaran ditemukan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $payment_method_filter ? '&payment_method=' . $payment_method_filter : '' ?><?= $order_type_filter ? '&order_type=' . $order_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                            Sebelumnya
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $payment_method_filter ? '&payment_method=' . $payment_method_filter : '' ?><?= $order_type_filter ? '&order_type=' . $order_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $payment_method_filter ? '&payment_method=' . $payment_method_filter : '' ?><?= $order_type_filter ? '&order_type=' . $order_type_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $date_from ? '&date_from=' . $date_from : '' ?><?= $date_to ? '&date_to=' . $date_to : '' ?>">
                                            Selanjutnya
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Lihat Bukti Pembayaran -->
<div class="modal fade" id="proofModal" tabindex="-1" role="dialog" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofModalLabel">Bukti Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="proofContent"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Setujui Pembayaran -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="approveForm" method="POST" action="process/approve-payment.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Setujui Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="approvePaymentId">
                    
                    <div class="alert alert-success">
                        <h5><i class="icon fas fa-check"></i> Konfirmasi</h5>
                        Apakah Anda yakin ingin menyetujui pembayaran dengan kode <strong id="approvePaymentCode"></strong>?
                    </div>
                    
                    <div class="form-group">
                        <label for="approve_notes">Catatan Admin (Opsional):</label>
                        <textarea name="notes" id="approve_notes" class="form-control" rows="3" placeholder="Tambahkan catatan untuk pelanggan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i>Setujui Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak Pembayaran -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="rejectForm" method="POST" action="process/reject-payment.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Tolak Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="rejectPaymentId">
                    
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan</h5>
                        Apakah Anda yakin ingin menolak pembayaran dengan kode <strong id="rejectPaymentCode"></strong>?
                    </div>
                    
                    <div class="form-group">
                        <label for="reject_notes">Alasan Penolakan <span class="text-danger">*</span>:</label>
                        <textarea name="notes" id="reject_notes" class="form-control" rows="3" placeholder="Jelaskan alasan penolakan pembayaran..." required></textarea>
                        <small class="form-text text-muted">Alasan ini akan dikirimkan kepada pelanggan</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i>Tolak Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script untuk checkbox dan tindakan massal
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAll');
        const paymentCheckboxes = document.querySelectorAll('.payment-checkbox');
        const bulkAction = document.getElementById('bulkAction');
        const applyBulkAction = document.getElementById('applyBulkAction');
        
        // Centang/hapus centang semua checkbox
        checkAll.addEventListener('change', function() {
            const isChecked = this.checked;
            paymentCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // Aktifkan/nonaktifkan tombol "Terapkan"
            applyBulkAction.disabled = !isChecked;
        });
        
        // Perbarui status tombol "Terapkan" saat checkbox individu berubah
        paymentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const anyChecked = [...paymentCheckboxes].some(cb => cb.checked);
                applyBulkAction.disabled = !anyChecked;
                
                // Periksa jika semua checkbox dicentang
                const allChecked = [...paymentCheckboxes].every(cb => cb.checked);
                checkAll.checked = allChecked;
            });
        });
        
        // Konfirmasi sebelum tindakan massal
        document.getElementById('bulkActionForm').addEventListener('submit', function(e) {
            const action = bulkAction.value;
            const selectedCount = [...paymentCheckboxes].filter(cb => cb.checked).length;
            
            let confirmMessage = '';
            if (action === 'approve') {
                confirmMessage = `Apakah Anda yakin ingin menyetujui ${selectedCount} pembayaran?`;
            } else if (action === 'reject') {
                confirmMessage = `Apakah Anda yakin ingin menolak ${selectedCount} pembayaran?`;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
        
        // Script untuk modal lihat bukti
        const viewProofBtns = document.querySelectorAll('.view-proof-btn');
        viewProofBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const proofPath = this.getAttribute('data-proof');
                const paymentCode = this.getAttribute('data-payment-code');
                
                document.getElementById('proofModalLabel').textContent = 'Bukti Pembayaran - ' + paymentCode;
                
                const fileExtension = proofPath.split('.').pop().toLowerCase();
                const proofContent = document.getElementById('proofContent');
                
                if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                    proofContent.innerHTML = `<img src="../${proofPath}" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 500px;">`;
                } else if (fileExtension === 'pdf') {
                    proofContent.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-file-pdf fa-5x text-danger mb-3"></i>
                            <p>File PDF</p>
                            <a href="../${proofPath}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-external-link-alt mr-1"></i>Buka PDF
                            </a>
                        </div>
                    `;
                } else {
                    proofContent.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-file fa-5x text-muted mb-3"></i>
                            <p>File tidak dapat ditampilkan</p>
                            <a href="../${proofPath}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-download mr-1"></i>Download File
                            </a>
                        </div>
                    `;
                }
            });
        });
        
        // Script untuk modal approve
        const approveBtns = document.querySelectorAll('.approve-btn');
        approveBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                const paymentCode = this.getAttribute('data-payment-code');
                
                document.getElementById('approvePaymentId').value = paymentId;
                document.getElementById('approvePaymentCode').textContent = paymentCode;
            });
        });
        
        // Script untuk modal reject
        const rejectBtns = document.querySelectorAll('.reject-btn');
        rejectBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                const paymentCode = this.getAttribute('data-payment-code');
                
                document.getElementById('rejectPaymentId').value = paymentId;
                document.getElementById('rejectPaymentCode').textContent = paymentCode;
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>