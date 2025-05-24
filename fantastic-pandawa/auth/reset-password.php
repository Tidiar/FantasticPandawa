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
$page_title = "Reset Password";
$page_description = "Buat password baru untuk akun Anda";

$token = isset($_GET['token']) ? cleanInput($_GET['token']) : '';
$success_message = '';
$error_message = '';
$token_valid = false;
$user_data = null;

// Validasi token
if (empty($token)) {
    $error_message = "Token reset password tidak valid.";
} else {
    try {
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'");
        $stmt->execute([$token]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $token_valid = true;
        } else {
            $error_message = "Token reset password tidak valid atau sudah kadaluarsa.";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan sistem.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error_message = "Password dan konfirmasi password wajib diisi";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password dan konfirmasi password tidak cocok";
    } else {
        try {
            // Hash password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password dan hapus token
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user_data['id']]);
            
            $success_message = "Password berhasil direset! Silakan login dengan password baru Anda.";
            
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan saat menyimpan password baru.";
        }
    }
}

include '../includes/header.php';
?>

<!-- Reset Password Section -->
<section class="reset-password-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="reset-password-card">
                    <div class="text-center mb-4">
                        <div class="reset-icon mb-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h2 class="reset-title">Reset Password</h2>
                        <p class="reset-subtitle">
                            Buat password baru yang kuat untuk akun Anda
                        </p>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $success_message ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                            </a>
                        </div>
                    <?php elseif (!$token_valid): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= $error_message ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="forgot-password.php" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>Minta Token Baru
                            </a>
                            <a href="login.php" class="btn btn-primary ms-2">
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
                        
                        <div class="user-info mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-user me-2"></i>
                                Reset password untuk: <strong><?= htmlspecialchars($user_data['email']) ?></strong>
                            </div>
                        </div>
                        
                        <form method="POST" action="" class="reset-form needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="password" class="form-label">Password Baru *</label>
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
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Password minimal 6 karakter
                                        </div>
                                    </div>
                                    <div class="password-strength mt-2" id="passwordStrength">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="strengthFill"></div>
                                        </div>
                                        <small class="strength-text" id="strengthText">Masukkan password</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Ulangi password baru"
                                               minlength="6"
                                               required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Konfirmasi password harus sama
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="password-requirements mb-4">
                                <h6 class="mb-2">
                                    <i class="fas fa-info-circle me-2"></i>Syarat Password:
                                </h6>
                                <ul class="requirements-list">
                                    <li id="length-req" class="requirement">
                                        <i class="fas fa-times text-danger"></i>
                                        Minimal 6 karakter
                                    </li>
                                    <li id="lowercase-req" class="requirement">
                                        <i class="fas fa-times text-danger"></i>
                                        Mengandung huruf kecil
                                    </li>
                                    <li id="uppercase-req" class="requirement">
                                        <i class="fas fa-times text-danger"></i>
                                        Mengandung huruf besar
                                    </li>
                                    <li id="number-req" class="requirement">
                                        <i class="fas fa-times text-danger"></i>
                                        Mengandung angka
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Simpan Password Baru
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="login.php" class="text-muted">
                                <i class="fas fa-arrow-left me-1"></i>
                                Kembali ke halaman login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.reset-password-section {
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.reset-password-card {
    background: white;
    border-radius: 1rem;
    padding: 3rem 2rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.reset-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--success-color), #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.reset-icon i {
    font-size: 2rem;
    color: white;
}

.reset-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.reset-subtitle {
    color: var(--secondary-color);
    margin-bottom: 0;
    line-height: 1.5;
}

.reset-form .form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.reset-form .input-group-text {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: var(--secondary-color);
}

.reset-form .form-control {
    border-color: #e2e8f0;
    padding: 0.75rem 1rem;
}

.reset-form .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
}

.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.strength-text {
    color: var(--secondary-color);
    font-size: 0.875rem;
}

.password-requirements {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.requirement {
    padding: 0.25rem 0;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.requirement i {
    width: 16px;
    margin-right: 0.5rem;
}

.requirement.valid i {
    color: var(--success-color) !important;
}

.requirement.valid i:before {
    content: "\f00c";
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
    .reset-password-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
    }
    
    .reset-title {
        font-size: 1.75rem;
    }
    
    .reset-icon {
        width: 60px;
        height: 60px;
    }
    
    .reset-icon i {
        font-size: 1.5rem;
    }
}
</style>

<!-- Custom JavaScript -->
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
    
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordRequirements(this.value);
        });
    }
    
    function checkPasswordStrength(password) {
        let strength = 0;
        let text = 'Sangat Lemah';
        let color = '#ef4444';
        
        if (password.length >= 6) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        const percentage = (strength / 5) * 100;
        
        switch (strength) {
            case 0:
            case 1:
                text = 'Sangat Lemah';
                color = '#ef4444';
                break;
            case 2:
                text = 'Lemah';
                color = '#f59e0b';
                break;
            case 3:
                text = 'Sedang';
                color = '#3b82f6';
                break;
            case 4:
                text = 'Kuat';
                color = '#10b981';
                break;
            case 5:
                text = 'Sangat Kuat';
                color = '#059669';
                break;
        }
        
        if (strengthFill) {
            strengthFill.style.width = percentage + '%';
            strengthFill.style.backgroundColor = color;
        }
        
        if (strengthText) {
            strengthText.textContent = text;
            strengthText.style.color = color;
        }
    }
    
    function checkPasswordRequirements(password) {
        const requirements = [
            { id: 'length-req', test: password.length >= 6 },
            { id: 'lowercase-req', test: /[a-z]/.test(password) },
            { id: 'uppercase-req', test: /[A-Z]/.test(password) },
            { id: 'number-req', test: /[0-9]/.test(password) }
        ];
        
        requirements.forEach(req => {
            const element = document.getElementById(req.id);
            if (element) {
                if (req.test) {
                    element.classList.add('valid');
                } else {
                    element.classList.remove('valid');
                }
            }
        });
    }
    
    // Password confirmation validation
    function validatePasswordMatch() {
        if (confirmPasswordInput && passwordInput) {
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('Password tidak cocok');
                confirmPasswordInput.classList.add('is-invalid');
            } else {
                confirmPasswordInput.setCustomValidity('');
                confirmPasswordInput.classList.remove('is-invalid');
            }
        }
    }
    
    if (passwordInput) passwordInput.addEventListener('input', validatePasswordMatch);
    if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            validatePasswordMatch();
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto focus on password field
    if (passwordInput) {
        passwordInput.focus();
    }
});
</script>

<?php include '../includes/footer.php'; ?>