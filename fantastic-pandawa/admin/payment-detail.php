<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Periksa apakah ID disediakan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pembayaran tidak valid.";
    header("Location: payment-verification.php");
    exit;
}

$payment_id = $_GET['id'];

// Dapatkan detail pembayaran
$payment = getPaymentDetails($payment_id);

if (!$payment) {
    $_SESSION['error_message'] = "Pembayaran tidak ditemukan.";
    header("Location: payment-verification.php");
    exit;
}

// Dapatkan riwayat pembayaran
$payment_history = getPaymentHistory($payment_id);

// Dapatkan detail pesanan
if ($payment['order_type'] === 'print') {
    $order = getPrintOrderDetails($payment['order_id']);
} else {
    $order = getCetakOrderDetails($payment['order_id']);
}

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Handle pembaruan status pembayaran
if (isset($_POST['update_payment_status'])) {
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'];
    
    if (updatePaymentStatus($payment_id, $new_status, $notes)) {
        $success_message = "Status pembayaran berhasil diperbarui.";
        
        // Perbarui data pembayaran setelah perubahan status
        $payment = getPaymentDetails($payment_id);
        $payment_history = getPaymentHistory($payment_id);
    } else {
        $error_message = "Gagal memperbarui status pembayaran.";
    }
}

// Judul halaman
$page_title = "Detail Pembayaran - Panel Admin";

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
                    <h1 class="m-0">Detail Pembayaran</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item"><a href="payment-verification.php">Verifikasi Pembayaran</a></li>
                        <li class="breadcrumb-item active">Detail Pembayaran</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
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
            
            <div class="row">
                <!-- Detail Pembayaran -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Pembayaran #<?= $payment['payment_code'] ?></h3>
                            <div class="card-tools">
                                <span class="badge badge-<?= getPaymentStatusBadgeClass($payment['payment_status']) ?>">
                                    <?= translatePaymentStatus($payment['payment_status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt>Kode Pembayaran</dt>
                                        <dd><?= $payment['payment_code'] ?></dd>
                                        
                                        <dt>Nomor Pesanan</dt>
                                        <dd>
                                            <a href="<?= $payment['order_type'] ?>-orders-detail.php?id=<?= $payment['order_id'] ?>" target="_blank">
                                                <?= $payment['order_number'] ?>
                                            </a>
                                        </dd>
                                        
                                        <dt>Jenis Pesanan</dt>
                                        <dd>
                                            <span class="badge badge-<?= $payment['order_type'] == 'print' ? 'info' : 'success' ?>">
                                                <?= ucfirst($payment['order_type']) ?>
                                            </span>
                                        </dd>
                                        
                                        <dt>Tanggal Pembayaran</dt>
                                        <dd><?= formatDate($payment['created_at']) ?></dd>
                                        
                                        <dt>Status Pembayaran</dt>
                                        <dd>
                                            <span class="badge badge-<?= getPaymentStatusBadgeClass($payment['payment_status']) ?>">
                                                <?= translatePaymentStatus($payment['payment_status']) ?>
                                            </span>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt>Pelanggan</dt>
                                        <dd><?= $payment['customer_name'] ?></dd>
                                        
                                        <dt>Email</dt>
                                        <dd><?= $payment['customer_email'] ?></dd>
                                        
                                        <dt>Metode Pembayaran</dt>
                                        <dd>
                                            <?php if ($payment['payment_method'] == 'qris'): ?>
                                                <i class="fas fa-qrcode text-primary"></i> QRIS
                                            <?php else: ?>
                                                <i class="fas fa-university text-success"></i> Bank Transfer
                                            <?php endif; ?>
                                        </dd>
                                        
                                        <dt>Jumlah Pembayaran</dt>
                                        <dd><strong class="text-success">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></strong></dd>
                                        
                                        <?php if ($payment['expired_at']): ?>
                                            <dt>Batas Waktu</dt>
                                            <dd>
                                                <?= formatDate($payment['expired_at'], true) ?>
                                                <?php if (strtotime($payment['expired_at']) < time()): ?>
                                                    <span class="badge badge-danger ml-2">Kedaluwarsa</span>
                                                <?php endif; ?>
                                            </dd>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                            </div>
                            
                            <?php if ($payment['payment_method'] === 'bank_transfer'): ?>
                                <h5 class="mt-4">Detail Transfer Bank</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%">Nama Bank</th>
                                                <td><?= $payment['bank_name'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>No. Rekening</th>
                                                <td><?= $payment['bank_account'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Atas Nama</th>
                                                <td><?= $payment['account_holder'] ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($payment['payment_proof']): ?>
                                <h5 class="mt-4">Bukti Pembayaran</h5>
                                <div class="text-center">
                                    <?php
                                    $file_extension = strtolower(pathinfo($payment['payment_proof'], PATHINFO_EXTENSION));
                                    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                    ?>
                                        <img src="../<?= $payment['payment_proof'] ?>" alt="Bukti Pembayaran" class="img-fluid" style="max-height: 400px; cursor: pointer;" onclick="openImageModal(this.src)">
                                        <br><small class="text-muted">Klik gambar untuk memperbesar</small>
                                    <?php elseif ($file_extension === 'pdf'): ?>
                                        <div class="file-preview p-4">
                                            <i class="fas fa-file-pdf fa-5x text-danger mb-3"></i>
                                            <h5>Bukti Pembayaran (PDF)</h5>
                                            <a href="../<?= $payment['payment_proof'] ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>Buka PDF
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="file-preview p-4">
                                            <i class="fas fa-file fa-5x text-muted mb-3"></i>
                                            <h5>File Bukti Pembayaran</h5>
                                            <a href="../<?= $payment['payment_proof'] ?>" target="_blank" class="btn btn-primary">
                                                <i class="fas fa-download me-1"></i>Download File
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mt-4">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Pelanggan belum mengupload bukti pembayaran
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($payment['notes']): ?>
                                <h5 class="mt-4">Catatan Pelanggan</h5>
                                <div class="notes-box p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($payment['notes'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <a href="payment-verification.php" class="btn btn-default">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                                </a>
                                <?php if ($payment['payment_status'] == 'pending' && $payment['payment_proof']): ?>
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#approveModal">
                                        <i class="fas fa-check"></i> Setujui Pembayaran
                                    </button>
                                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                                        <i class="fas fa-times"></i> Tolak Pembayaran
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detail Pesanan -->
                    <?php if ($order): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3 class="card-title">Detail Pesanan</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <?php if ($payment['order_type'] === 'print'): ?>
                                                <tr>
                                                    <th style="width: 30%">Nama File</th>
                                                    <td><?= $order['original_filename'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Jumlah Copy</th>
                                                    <td><?= $order['copies'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Ukuran Kertas</th>
                                                    <td><?= $order['paper_size'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Warna Cetakan</th>
                                                    <td><?= translatePrintColor($order['print_color']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis Kertas</th>
                                                    <td><?= $order['paper_type'] ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <th style="width: 30%">Jenis Cetakan</th>
                                                    <td><?= translateCetakType($order['cetak_type']) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Quantity</th>
                                                    <td><?= $order['quantity'] ?> pcs</td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis Kertas</th>
                                                    <td><?= $order['paper_type'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Finishing</th>
                                                    <td><?= $order['finishing'] ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Pengiriman</th>
                                                    <td><?= $order['delivery'] == 'pickup' ? 'Ambil Sendiri' : 'Dikirim' ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th>Harga Pesanan</th>
                                                <td><strong>Rp <?= number_format($order['price'], 0, ',', '.') ?></strong></td>
                                            </tr>
                                            <tr>
                                                <th>Status Pesanan</th>
                                                <td>
                                                    <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>">
                                                        <?= translateStatus($order['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar Aksi -->
                <div class="col-md-4">
                    <!-- Kartu Update Status -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Update Status Pembayaran</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="new_status">Status Baru:</label>
                                    <select class="form-control" id="new_status" name="new_status">
                                        <option value="pending" <?= $payment['payment_status'] == 'pending' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                        <option value="paid" <?= $payment['payment_status'] == 'paid' ? 'selected' : '' ?>>Disetujui</option>
                                        <option value="failed" <?= $payment['payment_status'] == 'failed' ? 'selected' : '' ?>>Ditolak</option>
                                        <option value="expired" <?= $payment['payment_status'] == 'expired' ? 'selected' : '' ?>>Kedaluwarsa</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Catatan (Opsional):</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>
                                <button type="submit" name="update_payment_status" class="btn btn-primary btn-block">Update Status</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Kartu Riwayat Pembayaran -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Riwayat Pembayaran</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="timeline timeline-inverse" style="margin: 0 10px 0 10px; padding-top: 10px;">
                                <?php if (count($payment_history) > 0): ?>
                                    <?php foreach ($payment_history as $index => $history): ?>
                                        <div class="time-label">
                                            <span class="bg-primary">
                                                <?= formatDate($history['created_at'], true) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <i class="fas fa-credit-card bg-<?= getPaymentStatusBadgeClass($history['status']) ?>"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="far fa-clock"></i> <?= formatTime($history['created_at']) ?></span>
                                                <h3 class="timeline-header">Status diubah menjadi <strong><?= translatePaymentStatus($history['status']) ?></strong></h3>
                                                
                                                <?php if (!empty($history['notes'])): ?>
                                                    <div class="timeline-body">
                                                        <?= nl2br(htmlspecialchars($history['notes'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="timeline-footer">
                                                    <small>oleh <?= $history['changed_by_name'] ?: 'Sistem' ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div>
                                        <i class="far fa-clock bg-gray"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline-item">
                                        <h3 class="timeline-header">Tidak ada riwayat pembayaran</h3>
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

<!-- Modal Setujui Pembayaran -->
<div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="process/approve-payment.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Setujui Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                    
                    <div class="alert alert-success">
                        <h5><i class="icon fas fa-check"></i> Konfirmasi</h5>
                        Apakah Anda yakin ingin menyetujui pembayaran dengan kode <strong><?= $payment['payment_code'] ?></strong>?
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
            <form method="POST" action="process/reject-payment.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Tolak Pembayaran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                    
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan</h5>
                        Apakah Anda yakin ingin menolak pembayaran dengan kode <strong><?= $payment['payment_code'] ?></strong>?
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

<!-- Modal Image -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bukti Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="Bukti Pembayaran" class="img-fluid" id="modalImage">
            </div>
        </div>
    </div>
</div>

<script>
// Open image modal
function openImageModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    $('#imageModal').modal('show');
}
</script>

<?php include 'includes/footer.php'; ?>