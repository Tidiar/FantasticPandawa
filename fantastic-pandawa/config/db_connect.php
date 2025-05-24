<?php
// Database configuration
$host = 'localhost';
$dbname = 'fantastic_pandawa';
$username = 'root';
$password = ''; // Sesuaikan dengan password database Anda

// Try to connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>