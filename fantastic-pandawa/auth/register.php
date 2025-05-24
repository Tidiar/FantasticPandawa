<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Daftar";
$page_description = "Daftar akun baru untuk menggunakan layanan print dan cetak";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $agree_terms = isset($_POST['agree_terms']);
    
    // Validasi input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua field wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak cocok";
    } elseif (!$agree_terms) {
        $error_message = "Anda harus menyetujui syarat dan ketentuan";
    } else {
        // Proses registrasi
        try {
            // Cek apakah email sudah terdaftar
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Email sudah terdaftar. Silakan gunakan email lain atau <a href='login.php'>masuk di sini</a>";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Simpan user baru
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, 'customer', 'active', NOW())");
                $stmt->execute([$name, $email, $hashed_password, $phone]);
                
                $user_id = $conn->lastInsertId();
                
                // Auto login setelah registrasi
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['is_admin'] = 0;
                
                $_SESSION['success_message'] = "Selamat datang, $name! Registrasi berhasil dan Anda sudah masuk ke akun.";
                
                // Redirect ke dashboard atau halaman yang diminta
                $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '../user/dashboard.php';
                unset($_SESSION['redirect_url']);
                
                header("Location: $redirect_url");
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi";
        }
    }
}

include '../includes/header.php';
?>

<!-- Register Section -->
<section class="register-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="register-card">
                    <div class="text-center mb-4">
                        <h2 class="register-title">Daftar Akun Baru</h2>
                        <p class="register-subtitle">Bergabunglah dengan kami untuk mendapatkan layanan print dan cetak terbaik</p>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $error_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="register-form needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label">Nama Lengkap *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                                           placeholder="Masukkan nama lengkap"
                                           required>
                                    <div class="invalid-feedback">
                                        Nama lengkap wajib diisi
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">No. Telepon</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Minimal 6 karakter"
                                           minlength="6"
                                           required>
                                    <span class="input-group-text password-toggle" id="togglePassword" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">
                                        Password minimal 6 karakter
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Ulangi password"
                                           minlength="6"
                                           required>
                                    <span class="input-group-text password-toggle" id="toggleConfirmPassword" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">
                                        Konfirmasi password harus sama
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat dan Ketentuan</a> yang berlaku
                                </label>
                                <div class="invalid-feedback">
                                    Anda harus menyetujui syarat dan ketentuan
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>
                                Daftar Sekarang
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Sudah punya akun? 
                            <a href="login.php" class="text-primary fw-bold">Masuk di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Syarat dan Ketentuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Ketentuan Umum</h6>
                <p>Dengan mendaftar di website <?= $settings['site_name'] ?? 'Fantastic Pandawa' ?>, Anda menyetujui untuk terikat dengan syarat dan ketentuan berikut.</p>
                
                <h6>2. Layanan</h6>
                <ul>
                    <li>Kami menyediakan layanan print dokumen dan cetak custom</li>
                    <li>Harga dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya</li>
                    <li>Kami berhak menolak pesanan yang melanggar hukum atau norma</li>
                </ul>
                
                <h6>3. Privasi</h6>
                <p>Data pribadi Anda akan kami lindungi dan tidak akan dibagikan kepada pihak ketiga tanpa izin Anda.</p>
                
                <h6>4. Pembayaran</h6>
                <ul>
                    <li>Pembayaran dilakukan setelah pesanan selesai</li>
                    <li>Kami menerima pembayaran tunai, transfer bank, dan e-wallet</li>
                </ul>
                
                <h6>5. Tanggung Jawab</h6>
                <p>Kami tidak bertanggung jawab atas kerugian yang timbul akibat keterlambatan atau kesalahan dari pihak pelanggan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="acceptTerms()">Saya Setuju</button>
            </div>
        </div>
    </div>
</div>

<!-- Refined Custom CSS - Matching Login Style -->
<style>
:root {
    --primary-color: #3b82f6;
    --primary-dark: #1e40af;
    --dark-color: #1f2937;
    --secondary-color: #6b7280;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --border-color: #e5e7eb;
    --border-light: #f3f4f6;
    --background-light: #fafbfc;
}

.register-section {
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.register-card {
    background: white;
    border-radius: 0.75rem;
    padding: 2.5rem 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--border-light);
    transition: all 0.2s ease;
}

.register-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.register-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.register-subtitle {
    color: var(--secondary-color);
    margin-bottom: 0;
    font-size: 0.95rem;
}

.register-form .form-label {
    font-weight: 500;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

/* Clear input styling with complete visible borders */
.register-form .input-group {
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1.5px solid #d1d5db;
    overflow: hidden;
}

.register-form .input-group-text {
    background: var(--background-light);
    border: none;
    border-right: 1px solid #d1d5db;
    color: var(--secondary-color);
    font-size: 0.9rem;
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.register-form .form-control {
    border: none;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    background: white;
    transition: all 0.2s ease;
}

/* Clear focus states with complete borders */
.register-form .input-group:focus-within {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.register-form .input-group:focus-within .input-group-text {
    background: rgba(59, 130, 246, 0.05);
    color: var(--primary-color);
    border-right-color: var(--primary-color);
}

.register-form .form-control:focus {
    outline: none;
    box-shadow: none;
    background: white;
}

/* Password toggle - complete borders */
.password-toggle {
    background: var(--background-light);
    border: none;
    border-left: 1px solid #d1d5db;
    color: var(--secondary-color);
    font-size: 0.9rem;
    width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    user-select: none;
}

.password-toggle:hover {
    background: rgba(59, 130, 246, 0.05);
    color: var(--primary-color);
}

.register-form .input-group:focus-within .password-toggle {
    background: rgba(59, 130, 246, 0.05);
    border-left-color: var(--primary-color);
}

/* Cleaner button styling - matching login */
.btn-primary {
    background: var(--primary-color);
    border: 1px solid var(--primary-color);
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 0.5rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.btn-primary:focus {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    outline: none;
}

/* Subtle checkbox styling - matching login */
.form-check-input {
    border-radius: 0.25rem;
    border: 1px solid var(--border-color);
    width: 1rem;
    height: 1rem;
    transition: all 0.2s ease;
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.form-check-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    outline: none;
}

.form-check-label {
    color: var(--secondary-color);
    font-size: 0.9rem;
    font-weight: 400;
    margin-left: 0.5rem;
}

/* Cleaner alert styling - matching login */
.alert {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    border: none;
    font-weight: 400;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.alert-danger {
    background-color: #fef2f2;
    color: #b91c1c;
}

/* Subtle link styling - matching login */
.text-primary {
    color: var(--primary-color) !important;
    text-decoration: none;
    transition: color 0.2s ease;
}

.text-primary:hover {
    color: var(--primary-dark) !important;
}

/* Subtle HR styling - matching login */
hr {
    border-color: var(--border-light);
    opacity: 0.8;
    margin: 1.5rem 0;
}

/* Clear validation styling with visible borders - matching login */
.form-control.is-invalid {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.form-control.is-valid {
    border-color: var(--success-color);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.invalid-feedback {
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.8rem;
    color: var(--danger-color);
    font-weight: 400;
}

/* Modal styling improvements */
.modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid var(--border-light);
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: var(--dark-color);
}

.modal-body {
    padding: 1.5rem;
    color: var(--secondary-color);
}

.modal-body h6 {
    color: var(--dark-color);
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.modal-body h6:first-child {
    margin-top: 0;
}

.modal-footer {
    border-top: 1px solid var(--border-light);
    padding: 1.5rem;
}

.btn-secondary {
    background: #6b7280;
    border: 1px solid #6b7280;
    color: white;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.btn-secondary:hover {
    background: #4b5563;
    border-color: #4b5563;
}

/* Responsive design - matching login */
@media (max-width: 768px) {
    .register-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
        border-radius: 0.5rem;
    }
    
    .register-title {
        font-size: 1.5rem;
    }
    
    .register-subtitle {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .register-section {
        padding: 1.5rem 0;
    }
    
    .register-card {
        padding: 1.5rem 1rem;
        margin: 0.5rem;
    }
    
    .register-title {
        font-size: 1.4rem;
    }
}

/* Remove excessive animations and effects - matching login */
.register-form .input-group:focus-within {
    transform: none;
}

/* Cleaner transitions - matching login */
* {
    transition-duration: 0.2s !important;
}
</style>

<!-- Simplified JavaScript - Matching Login Style -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        if (toggle && input) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
    }
    
    setupPasswordToggle('togglePassword', 'password');
    setupPasswordToggle('toggleConfirmPassword', 'confirm_password');
    
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    password.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        validatePasswordMatch();
        
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Auto focus on name field
    document.getElementById('name').focus();
    
    // Auto-hide alerts - matching login
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });
});

// Accept terms function
function acceptTerms() {
    document.getElementById('agree_terms').checked = true;
    bootstrap.Modal.getInstance(document.getElementById('termsModal')).hide();
}
</script>

<?php include '../includes/footer.php'; ?>