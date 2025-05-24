<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Cek apakah user sudah login
requireLogin('../auth/login.php');

$settings = getSettings();
$page_title = "Profil Saya";
$user_id = $_SESSION['user_id'];

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "User tidak ditemukan";
        header("Location: dashboard.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan sistem";
    header("Location: dashboard.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = cleanInput($_POST['name']);
        $phone = cleanInput($_POST['phone']);
        $address = cleanInput($_POST['address']);
        
        if (empty($name)) {
            $error_message = "Nama tidak boleh kosong";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $user_id]);
                
                $_SESSION['user_name'] = $name;
                $success_message = "Profil berhasil diperbarui";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error_message = "Gagal memperbarui profil";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = "Semua field password wajib diisi";
        } elseif (strlen($new_password) < 6) {
            $password_error = "Password baru minimal 6 karakter";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "Konfirmasi password tidak cocok";
        } elseif (!password_verify($old_password, $user['password'])) {
            $password_error = "Password lama salah";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $password_success = "Password berhasil diubah";
            } catch (PDOException $e) {
                $password_error = "Gagal mengubah password";
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="profile-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="profile-header mb-4">
                    <h1 class="profile-title">Profil Saya</h1>
                    <p class="profile-subtitle">Kelola informasi profil dan keamanan akun Anda</p>
                </div>

                <!-- Profile Info Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Informasi Profil
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                    <small class="text-muted">Email tidak dapat diubah</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" 
                                           value="<?= ucfirst($user['role']) ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Alamat</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-lock me-2"></i>Ubah Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($password_success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= $password_success ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $password_error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="old_password" class="form-label">Password Lama *</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">Password Baru *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="6" required>
                                    <small class="text-muted">Minimal 6 karakter</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="6" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="mt-4">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="account-stat">
                                <h5>Bergabung Sejak</h5>
                                <p class="text-muted"><?= formatDate($user['created_at']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="account-stat">
                                <h5>Terakhir Login</h5>
                                <p class="text-muted"><?= $user['last_login'] ? formatDate($user['last_login'], true) : 'Belum pernah' ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="account-stat">
                                <h5>Status Akun</h5>
                                <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.profile-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.profile-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
}

.profile-subtitle {
    color: var(--secondary-color);
    font-size: 1.125rem;
}

.account-stat h5 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: var(--shadow);
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 1rem 1rem 0 0 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswordMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
});
</script>

<?php include '../includes/footer.php'; ?>