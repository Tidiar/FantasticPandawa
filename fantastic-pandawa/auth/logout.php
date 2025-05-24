<?php
session_start();
require_once '../config/db_connect.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: login.php");
    exit;
}

try {
    // Hapus remember token dari database jika ada
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    // Hapus cookie remember token jika ada
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
} catch (PDOException $e) {
    // Log error tapi tetap lanjutkan proses logout
    error_log("Logout error: " . $e->getMessage());
}

// Simpan pesan logout sebelum menghancurkan session
$logout_message = "Anda telah berhasil logout. Terima kasih telah menggunakan layanan kami.";

// Hancurkan semua data session
session_unset();
session_destroy();

// Mulai session baru untuk pesan
session_start();
$_SESSION['success_message'] = $logout_message;

// Redirect ke halaman login
header("Location: login.php");
exit;
?>