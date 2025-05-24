<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Inisialisasi variabel
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Menangani ekspor CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    exportUsersCSV($role_filter, $status_filter, $search);
    exit;
}

// Dapatkan pengguna dengan filter
$users = getUsers($role_filter, $status_filter, $search, $limit, $offset);
$total_users = countUsers($role_filter, $status_filter, $search);
$total_pages = ceil($total_users / $limit);

// Judul halaman
$page_title = "Pengguna - Panel Admin";

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
                    <h1 class="m-0">Pengguna</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item active">Pengguna</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Tampilkan pesan sukses/error jika ada -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?= $_SESSION['error_message'] ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Tombol Tambah Pengguna -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="user-add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Tambah Pengguna Baru
                    </a>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Peran:</label>
                                    <select name="role" class="form-control">
                                        <option value="">Semua Peran</option>
                                        <option value="customer" <?= $role_filter == 'customer' ? 'selected' : '' ?>>Pelanggan</option>
                                        <option value="staff" <?= $role_filter == 'staff' ? 'selected' : '' ?>>Staff</option>
                                        <option value="manager" <?= $role_filter == 'manager' ? 'selected' : '' ?>>Manajer</option>
                                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select name="status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                        <option value="suspended" <?= $status_filter == 'suspended' ? 'selected' : '' ?>>Ditangguhkan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cari:</label>
                                    <input type="text" name="search" class="form-control" placeholder="Nama, Email, dll." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                                <a href="users.php" class="btn btn-default">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Kartu Tabel Pengguna -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Daftar Pengguna
                        <?= $role_filter ? '- ' . translateRole($role_filter) : '' ?>
                        <?= $status_filter ? '- ' . translateUserStatus($status_filter) : '' ?>
                        <?= $search ? '- Pencarian: ' . htmlspecialchars($search) : '' ?>
                    </h3>
                    <div class="card-tools">
                        <a href="users.php?export=csv<?= $role_filter ? '&role=' . $role_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-download mr-1"></i> Ekspor CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Peran</th>
                                    <th>Status</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Login Terakhir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= getRoleBadgeClass($user['role']) ?>">
                                                    <?= translateRole($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= getStatusBadgeClass($user['status']) ?>">
                                                    <?= translateUserStatus($user['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($user['created_at']) ?></td>
                                            <td><?= $user['last_login'] ? formatDate($user['last_login']) : 'Belum Pernah' ?></td>
                                            <td>
                                                <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger delete-user-btn" data-toggle="modal" data-target="#deleteUserModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada pengguna ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $role_filter ? '&role=' . $role_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            Sebelumnya
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $role_filter ? '&role=' . $role_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $role_filter ? '&role=' . $role_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
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

<!-- Modal Hapus Pengguna -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pengguna <strong id="userNameToDelete"></strong>? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data yang terkait dengan pengguna ini.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form method="POST" action="process/delete-user.php">
                    <input type="hidden" name="user_id" id="userIdToDelete">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Script untuk modal hapus pengguna
    document.addEventListener('DOMContentLoaded', function() {
        const deleteUserBtns = document.querySelectorAll('.delete-user-btn');
        deleteUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                
                document.getElementById('userIdToDelete').value = userId;
                document.getElementById('userNameToDelete').textContent = userName;
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>