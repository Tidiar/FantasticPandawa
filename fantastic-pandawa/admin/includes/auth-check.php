<?php
// Periksa apakah pengguna sudah login dan adalah admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Simpan URL yang diminta untuk pengalihan setelah login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Atur pesan error
    $_SESSION['error_message'] = "Anda harus login sebagai administrator untuk mengakses halaman ini.";
    
    // Alihkan ke halaman login
    header("Location: ../login.php");
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
    
    header("Location: ../login.php");
    exit;
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

