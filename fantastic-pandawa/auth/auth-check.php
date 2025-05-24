<?php
// admin/includes/auth-check.php - Updated version

// Periksa apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Simpan URL yang diminta untuk pengalihan setelah login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Atur pesan error
    $_SESSION['error_message'] = "Anda harus login sebagai administrator untuk mengakses halaman ini.";
    
    // Alihkan ke halaman login
    header("Location: ../auth/login.php");
    exit;
}

// Periksa apakah user memiliki role admin
$allowed_roles = ['admin', 'manager', 'staff'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    // Jika bukan admin, redirect ke dashboard user biasa
    $_SESSION['error_message'] = "Anda tidak memiliki akses ke halaman administrator.";
    header("Location: ../user/dashboard.php");
    exit;
}

// Periksa batas waktu sesi (30 menit tidak aktif)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Hancurkan sesi dan alihkan ke login
    session_unset();
    session_destroy();
    
    // Mulai sesi baru
    session_start();
    $_SESSION['error_message'] = "Sesi Anda telah berakhir. Silakan login kembali.";
    
    header("Location: ../auth/login.php");
    exit;
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Set admin flag untuk kompatibilitas
$_SESSION['is_admin'] = 1;
?>