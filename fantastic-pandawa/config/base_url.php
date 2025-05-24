<?php
// config/base_url.php

// Deteksi protokol (http atau https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Dapatkan host name
$host = $_SERVER['HTTP_HOST'];

// Dapatkan nama folder project (jika ada)
$script_name = $_SERVER['SCRIPT_NAME'];
$path_parts = explode('/', dirname($script_name));

// Cari folder root project
$project_folder = '';
foreach ($path_parts as $part) {
    if (!empty($part) && $part !== '.' && $part !== '..') {
        // Sesuaikan dengan nama folder project Anda
        if (in_array($part, ['printshop', 'print-shop', 'project', 'website'])) {
            $project_folder = '/' . $part;
            break;
        }
    }
}

// Jika tidak ditemukan folder khusus, gunakan auto detection
if (empty($project_folder)) {
    // Ambil folder pertama dari path jika tidak di root
    $first_folder = isset($path_parts[1]) ? $path_parts[1] : '';
    if (!empty($first_folder) && $first_folder !== 'index.php') {
        $project_folder = '/' . $first_folder;
    }
}

// Definisikan BASE_URL
define('BASE_URL', $protocol . '://' . $host . $project_folder);

// Definisikan ROOT_PATH untuk file includes
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . $project_folder);

// Helper functions
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset($path = '') {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}
?>