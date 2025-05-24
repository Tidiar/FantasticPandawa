<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole();
}

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Lupa Password";
$page_description = "Reset password akun Anda";

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Email wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid";
    } else {
        try {
            // Cek apakah email terdaftar
            $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', time() + 3600); // 1 jam
                
                // Simpan token ke database
                $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$reset_token, $reset_expires, $user['id']]);
                
                // Kirim email (simulasi - Anda bisa integrasikan dengan layanan email)
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset-password.php?token=" . $reset_token;
                
                // Simulasi pengiriman email
                if (sendResetEmail($user['email'], $user['name'], $reset_link)) {
                    $success_message = "Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.";
                } else {
                    $error_message = "Gagal mengirim email. Silakan coba lagi.";
                }
                
            } else {
                // Untuk keamanan, tampilkan pesan sukses meskipun email tidak ditemukan
                $success_message = "Jika email terdaftar di sistem kami, link reset password akan dikirim.";
            }
            
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}

include '../includes/header.php';
?>

<!-- Forgot Password Section -->
<section class="forgot-password-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="forgot-password-card">
                    <div class="text-center mb-4">
                        <div class="forgot-icon mb-3">
                            <i class="fas fa-key"></i>
                        </div>
                        <h2 class="forgot-title">Lupa Password?</h2>
                        <p class="forgot-subtitle">
                            Masukkan email Anda dan kami akan mengirimkan link untuk reset password
                        </p>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $success_message ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Login
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= $error_message ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="forgot-form needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                           placeholder="nama@email.com"
                                           required>
                                    <div class="invalid-feedback">
                                        Email valid wajib diisi
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Masukkan email yang terdaftar di akun Anda
                                </small>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Kirim Link Reset
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">
                                <a href="login.php" class="text-muted">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Kembali ke halaman login
                                </a>
                            </p>
                            <p class="mb-0">
                                Belum punya akun? 
                                <a href="register.php" class="text-primary fw-bold">Daftar sekarang</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.forgot-password-section {
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.forgot-password-card {
    background: white;
    border-radius: 1rem;
    padding: 3rem 2rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.forgot-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.forgot-icon i {
    font-size: 2rem;
    color: white;
}

.forgot-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.forgot-subtitle {
    color: var(--secondary-color);
    margin-bottom: 0;
    line-height: 1.5;
}

.forgot-form .form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.forgot-form .input-group-text {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: var(--secondary-color);
}

.forgot-form .form-control {
    border-color: #e2e8f0;
    padding: 0.75rem 1rem;
}

.forgot-form .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 0.5rem;
}

hr {
    border-color: #e2e8f0;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .forgot-password-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
    }
    
    .forgot-title {
        font-size: 1.75rem;
    }
    
    .forgot-icon {
        width: 60px;
        height: 60px;
    }
    
    .forgot-icon i {
        font-size: 1.5rem;
    }
}
</style>

<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto focus on email field
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.focus();
    }
});
</script>

<?php include '../includes/footer.php'; ?>