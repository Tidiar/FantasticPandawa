<?php
// Sertakan file koneksi database
require_once 'db_connect.php';

// Array query untuk membuat tabel
$tables = [
    // Tabel users
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `address` text,
        `role` enum('admin','manager','staff','customer') NOT NULL DEFAULT 'customer',
        `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `last_login` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel print_orders
    "CREATE TABLE IF NOT EXISTS `print_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_number` varchar(20) NOT NULL,
        `user_id` int(11) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `original_filename` varchar(255) NOT NULL,
        `copies` int(11) NOT NULL DEFAULT 1,
        `paper_size` varchar(50) NOT NULL,
        `print_color` enum('BW','Color') NOT NULL DEFAULT 'BW',
        `paper_type` varchar(50) NOT NULL,
        `notes` text,
        `price` decimal(10,2) NOT NULL DEFAULT 0.00,
        `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
        `status` enum('pending','confirmed','processing','ready','completed','canceled') NOT NULL DEFAULT 'pending',
        `assigned_to` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `assigned_to` (`assigned_to`),
        CONSTRAINT `print_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `print_orders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel print_order_history
    "CREATE TABLE IF NOT EXISTS `print_order_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `status` enum('pending','confirmed','processing','ready','completed','canceled') NOT NULL,
        `notes` text,
        `changed_by` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `changed_by` (`changed_by`),
        CONSTRAINT `print_order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `print_orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `print_order_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel cetak_orders
    "CREATE TABLE IF NOT EXISTS `cetak_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_number` varchar(20) NOT NULL,
        `user_id` int(11) NOT NULL,
        `cetak_type` varchar(50) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `original_filename` varchar(255) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 1,
        `paper_type` varchar(50) NOT NULL,
        `finishing` varchar(50) NOT NULL,
        `delivery` enum('pickup','delivery') NOT NULL DEFAULT 'pickup',
        `notes` text,
        `price` decimal(10,2) NOT NULL DEFAULT 0.00,
        `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
        `status` enum('pending','confirmed','processing','ready','completed','canceled') NOT NULL DEFAULT 'pending',
        `assigned_to` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `assigned_to` (`assigned_to`),
        CONSTRAINT `cetak_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `cetak_orders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel cetak_order_history
    "CREATE TABLE IF NOT EXISTS `cetak_order_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `status` enum('pending','confirmed','processing','ready','completed','canceled') NOT NULL,
        `notes` text,
        `changed_by` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `changed_by` (`changed_by`),
        CONSTRAINT `cetak_order_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `cetak_orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `cetak_order_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    // Tabel inventory
    "CREATE TABLE IF NOT EXISTS `inventory` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `item_code` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `category` enum('paper','ink','other') NOT NULL,
        `description` text,
        `quantity` int(11) NOT NULL DEFAULT 0,
        `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
        `unit` varchar(20) NOT NULL,
        `price` decimal(10,2) NOT NULL DEFAULT 0.00,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `item_code` (`item_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// Eksekusi query untuk membuat tabel
foreach ($tables as $query) {
    try {
        $conn->exec($query);
        echo "Tabel berhasil dibuat atau sudah ada.<br>";
    } catch (PDOException $e) {
        echo "Error membuat tabel: " . $e->getMessage() . "<br>";
    }
}

// Insert admin default jika belum ada
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();
    
    if ($admin_count == 0) {
        // Password: password (hashed)
        $admin_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute(['Admin Utama', 'admin@fantasticpandawa.com', $admin_password]);
        
        echo "Admin default berhasil ditambahkan.<br>";
    }
} catch (PDOException $e) {
    echo "Error menambahkan admin default: " . $e->getMessage() . "<br>";
}

echo "Proses setup database selesai.";
?>