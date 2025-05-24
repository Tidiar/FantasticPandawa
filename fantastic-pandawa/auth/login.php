<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Jika user sudah login, redirect berdasarkan role
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole();
}

$error_message = '';
$success_message = '';

// Tampilkan pesan dari session jika ada
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error_message = "Email dan password wajib diisi";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error_message = "Akun Anda tidak aktif. Hubungi administrator.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['is_admin'] = in_array($user['role'], ['admin', 'manager', 'staff']) ? 1 : 0;
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Handle remember me
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                        
                        // Store token in database
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->execute([$token, $user['id']]);
                    }
                    
                    // Check if there's a redirect URL
                    $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : null;
                    unset($_SESSION['redirect_url']);
                    
                    // Redirect based on role or to requested page
                    if ($redirect_url) {
                        header("Location: $redirect_url");
                    } else {
                        redirectBasedOnRole();
                    }
                    exit;
                }
            } else {
                $error_message = "Email atau password salah";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
            // Log error untuk debugging
            error_log("Login error: " . $e->getMessage());
        }
    }
}

$page_title = "Login";
$page_description = "Login ke akun Anda";

include '../includes/header.php';
?>

<!-- Login Section -->
<section class="login-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <h2 class="login-title">Masuk ke Akun</h2>
                        <p class="login-subtitle">Silakan masuk untuk menggunakan layanan kami</p>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="login-form" novalidate>
                        <div class="mb-3">
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
                            </div>
                            <div class="invalid-feedback" id="email-error" style="display: none;">
                                Email valid wajib diisi
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Masukkan password"
                                       required>
                                <span class="input-group-text password-toggle" id="togglePassword" style="cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback" id="password-error" style="display: none;">
                                Password wajib diisi
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ingat saya selama 30 hari
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Masuk Sekarang
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mb-3">
                        <a href="forgot-password.php" class="text-muted">
                            <i class="fas fa-question-circle me-1"></i>
                            Lupa password?
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-0">Belum punya akun? 
                            <a href="register.php" class="text-primary fw-bold">Daftar sekarang</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Refined Custom CSS -->
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

.login-section {
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.login-card {
    background: white;
    border-radius: 0.75rem;
    padding: 2.5rem 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--border-light);
    transition: all 0.2s ease;
}

.login-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.login-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.login-subtitle {
    color: var(--secondary-color);
    margin-bottom: 0;
    font-size: 0.95rem;
}

.login-form .form-label {
    font-weight: 500;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

/* Clear input styling with complete visible borders */
.login-form .input-group {
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1.5px solid #d1d5db;
    overflow: hidden;
}

.login-form .input-group-text {
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

.login-form .form-control {
    border: none;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    background: white;
    transition: all 0.2s ease;
}

/* Clear focus states with visible borders */
.login-form .input-group:focus-within .input-group-text {
    border-color: var(--primary-color);
    background: rgba(59, 130, 246, 0.05);
    color: var(--primary-color);
}

.login-form .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: white;
    outline: none;
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

.login-form .input-group:focus-within .password-toggle {
    border-color: var(--primary-color);
    background: rgba(59, 130, 246, 0.05);
}

/* Cleaner button styling */
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

/* Subtle checkbox styling */
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

/* Cleaner alert styling */
.alert {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    border: none;
    font-weight: 400;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.alert-success {
    background-color: #ecfdf5;
    color: #047857;
}

.alert-danger {
    background-color: #fef2f2;
    color: #b91c1c;
}

/* Subtle link styling */
.text-muted {
    color: var(--secondary-color) !important;
    text-decoration: none;
    font-weight: 400;
    transition: color 0.2s ease;
    font-size: 0.9rem;
}

.text-muted:hover {
    color: var(--primary-color) !important;
}

.text-primary {
    color: var(--primary-color) !important;
    text-decoration: none;
    transition: color 0.2s ease;
}

.text-primary:hover {
    color: var(--primary-dark) !important;
}

/* Subtle HR styling */
hr {
    border-color: var(--border-light);
    opacity: 0.8;
    margin: 1.5rem 0;
}

/* Clear validation styling with visible borders */
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

/* Responsive design */
@media (max-width: 768px) {
    .login-card {
        padding: 2rem 1.5rem;
        margin: 1rem;
        border-radius: 0.5rem;
    }
    
    .login-title {
        font-size: 1.5rem;
    }
    
    .login-subtitle {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .login-section {
        padding: 1.5rem 0;
    }
    
    .login-card {
        padding: 1.5rem 1rem;
        margin: 0.5rem;
    }
    
    .login-title {
        font-size: 1.4rem;
    }
}

/* Remove excessive animations and effects */
.login-form .input-group:focus-within {
    transform: none;
}

.form-control:focus {
    animation: none;
}

/* Cleaner transitions */
* {
    transition-duration: 0.2s !important;
}
</style>

<!-- Simplified JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Simple form validation
    const form = document.querySelector('.login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    
    function resetValidation() {
        emailInput.classList.remove('is-invalid', 'is-valid');
        passwordInput.classList.remove('is-invalid', 'is-valid');
        emailError.style.display = 'none';
        passwordError.style.display = 'none';
    }
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        resetValidation();
        
        // Validate email
        if (!emailInput.value.trim()) {
            emailInput.classList.add('is-invalid');
            emailError.style.display = 'block';
            isValid = false;
        } else if (!emailInput.checkValidity()) {
            emailInput.classList.add('is-invalid');
            emailError.textContent = 'Format email tidak valid';
            emailError.style.display = 'block';
            isValid = false;
        }
        
        // Validate password
        if (!passwordInput.value.trim()) {
            passwordInput.classList.add('is-invalid');
            passwordError.style.display = 'block';
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    
    // Clear validation on input
    emailInput.addEventListener('input', resetValidation);
    passwordInput.addEventListener('input', resetValidation);
    
    // Auto focus
    emailInput.focus();
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });
});
</script>

<?php include '../includes/footer.php'; ?>