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

// Cek apakah ada ID pengguna yang diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID Pengguna tidak valid.";
    header("Location: users.php");
    exit;
}

$user_id = $_GET['id'];

// Validasi user_id adalah angka
if (!is_numeric($user_id)) {
    $_SESSION['error_message'] = "ID Pengguna tidak valid.";
    header("Location: users.php");
    exit;
}

// Dapatkan data pengguna
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error_message'] = "Pengguna tidak ditemukan.";
    header("Location: users.php");
    exit;
}

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
    $role = $_POST['role'];
    $status = $_POST['status'];
    $change_password = isset($_POST['change_password']) && $_POST['change_password'] == 1;
    
    // Validasi input
    if (empty($name) || empty($email) || empty($role) || empty($status)) {
        $error = "Nama, Email, Peran, dan Status wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } else {
        // Cek apakah email sudah digunakan oleh pengguna lain
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email sudah digunakan oleh pengguna lain. Silakan gunakan email lain.";
        } else {
            try {
                $conn->beginTransaction();
                
                // Update data pengguna
                if ($change_password) {
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if (empty($password) || empty($confirm_password)) {
                        $error = "Password dan Konfirmasi Password wajib diisi jika ingin mengubah password.";
                    } elseif (strlen($password) < 6) {
                        $error = "Password minimal 6 karakter.";
                    } elseif ($password !== $confirm_password) {
                        $error = "Password dan Konfirmasi Password tidak cocok.";
                    } else {
                        // Hash password baru
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Update data pengguna dengan password baru
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, phone = ?, address = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $hashed_password, $phone, $address, $role, $status, $user_id]);
                    }
                } else {
                    // Update data pengguna tanpa mengubah password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, role = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $role, $status, $user_id]);
                }
                
                if (empty($error)) {
                    $conn->commit();
                    $success = "Data pengguna berhasil diperbarui.";
                    
                    // Perbarui data pengguna untuk ditampilkan di form
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $conn->rollBack();
                }
            } catch(PDOException $e) {
                $conn->rollBack();
                $error = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}

// Judul halaman
$page_title = "Edit Pengguna - Panel Admin";

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
                    <h1 class="m-0">Edit Pengguna</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dasbor</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Pengguna</a></li>
                        <li class="breadcrumb-item active">Edit Pengguna</li>
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
                            <h3 class="card-title">Form Edit Pengguna</h3>
                        </div>
                        <!-- Form mulai -->
                        <form method="POST" action="">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="change_password" name="change_password" value="1">
                                                <label class="custom-control-label" for="change_password">Ubah Password</label>
                                            </div>
                                        </div>
                                        
                                        <div id="password_fields" style="display: none;">
                                            <div class="form-group">
                                                <label for="password">Password Baru <span class="text-danger">*</span></label>
                                                <input type="password" name="password" id="password" class="form-control" minlength="6">
                                                <small class="form-text text-muted">Minimal 6 karakter</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="confirm_password">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">Nomor Telepon</label>
                                            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="address">Alamat</label>
                                            <textarea name="address" id="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="role">Peran <span class="text-danger">*</span></label>
                                            <select name="role" id="role" class="form-control" required>
                                                <option value="customer" <?= $user['role'] == 'customer' ? 'selected' : '' ?>>Pelanggan</option>
                                                <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                                                <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Manajer</option>
                                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="status">Status <span class="text-danger">*</span></label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                                                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                                                <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>Ditangguhkan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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

<!-- Script untuk toggle password fields -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordFields = document.getElementById('password_fields');
    
    // Toggle password fields saat checkbox diubah
    changePasswordCheckbox.addEventListener('change', function() {
        passwordFields.style.display = this.checked ? 'block' : 'none';
        
        // Reset password fields saat checkbox di-uncheck
        if (!this.checked) {
            document.getElementById('password').value = '';
            document.getElementById('confirm_password').value = '';
        }
    });
    
    // Validasi password saat form disubmit
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (changePasswordCheckbox.checked) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Password Baru dan Konfirmasi Password Baru tidak cocok!');
                e.preventDefault();
                return false;
            }
        }
        return true;
    });
});
</script>

<?php include 'includes/footer.php'; ?>