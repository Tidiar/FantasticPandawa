<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Inisialisasi variabel pesan
$success = '';
$error = '';

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Validasi input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($status)) {
        $error = "Semua field wajib diisi kecuali telepon dan alamat.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok.";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Simpan data pengguna baru
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            try {
                $stmt->execute([$name, $email, $hashed_password, $phone, $address, $role, $status]);
                $user_id = $conn->lastInsertId();
                $success = "Pengguna berhasil ditambahkan! ID Pengguna: " . $user_id;
                
                // Reset form setelah berhasil ditambahkan
                $name = $email = $password = $confirm_password = $phone = $address = '';
                $role = 'customer';
                $status = 'active';
            } catch(PDOException $e) {
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Judul halaman
$page_title = "Tambah Pengguna - Panel Admin";

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
                    <h1 class="m-0">Tambah Pengguna</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Pengguna</a></li>
                        <li class="breadcrumb-item active">Tambah Pengguna</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Pesan sukses/error -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Form Tambah Pengguna</h3>
                        </div>
                        <!-- Form mulai -->
                        <form method="POST" action="">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="name" class="form-control" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" id="email" class="form-control" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="password">Password <span class="text-danger">*</span></label>
                                            <input type="password" name="password" id="password" class="form-control" required minlength="6">
                                            <small class="form-text text-muted">Minimal 6 karakter</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="confirm_password">Konfirmasi Password <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">Nomor Telepon</label>
                                            <input type="text" name="phone" id="phone" class="form-control" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="address">Alamat</label>
                                            <textarea name="address" id="address" class="form-control" rows="3"><?= isset($address) ? htmlspecialchars($address) : '' ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="role">Peran <span class="text-danger">*</span></label>
                                            <select name="role" id="role" class="form-control" required>
                                                <option value="customer" <?= (isset($role) && $role == 'customer') ? 'selected' : '' ?>>Pelanggan</option>
                                                <option value="staff" <?= (isset($role) && $role == 'staff') ? 'selected' : '' ?>>Staff</option>
                                                <option value="manager" <?= (isset($role) && $role == 'manager') ? 'selected' : '' ?>>Manajer</option>
                                                <option value="admin" <?= (isset($role) && $role == 'admin') ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="active" <?= (isset($status) && $status == 'active') ? 'selected' : '' ?>>Aktif</option>
                                                <option value="inactive" <?= (isset($status) && $status == 'inactive') ? 'selected' : '' ?>>Tidak Aktif</option>
                                                <option value="suspended" <?= (isset($status) && $status == 'suspended') ? 'selected' : '' ?>>Ditangguhkan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="users.php" class="btn btn-default">Batal</a>
                            </div>
                        </form>
                        <!-- Form selesai -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Password validation script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            alert('Password dan Konfirmasi Password tidak cocok!');
            e.preventDefault();
            return false;
        }
        return true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>