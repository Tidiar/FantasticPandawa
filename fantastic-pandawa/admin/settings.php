<?php
// Mulai sesi dan sertakan file yang diperlukan
session_start();
require_once '../config/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth-check.php';

// Dapatkan jumlah pesanan tertunda untuk header dan sidebar
$pending_print_orders = getPendingOrderCount('print');
$pending_cetak_orders = getPendingOrderCount('cetak');

// Inisialisasi variabel pesan
$success = '';
$error = '';

// Dapatkan pengaturan saat ini
$settings = getSettings();

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'general') {
        // Perbarui pengaturan umum
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $contact_email = trim($_POST['contact_email']);
        $contact_phone = trim($_POST['contact_phone']);
        $contact_whatsapp = trim($_POST['contact_whatsapp']);
        $contact_address = trim($_POST['contact_address']);
        
        // Validasi input
        if (empty($site_name)) {
            $error = "Nama situs tidak boleh kosong.";
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } else {
            // Simpan pengaturan
            $settings_update = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'contact_whatsapp' => $contact_whatsapp,
                'contact_address' => $contact_address
            ];
            
            if (updateSettings($settings_update)) {
                $success = "Pengaturan umum berhasil diperbarui.";
                $settings = getSettings(); // Refresh pengaturan
            } else {
                $error = "Gagal memperbarui pengaturan umum.";
            }
        }
    } elseif ($action == 'logo') {
        // Perbarui logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 1048576; // 1MB
            
            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                $error = "Tipe file tidak didukung. Hanya file JPG, PNG, dan GIF yang diperbolehkan.";
            } elseif ($_FILES['logo']['size'] > $max_size) {
                $error = "Ukuran file terlalu besar. Maksimal 1MB.";
            } else {
                $upload_dir = '../uploads/logo/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = 'logo_' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $destination = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                    // Simpan path logo baru ke database
                    $logo_path = 'uploads/logo/' . $filename;
                    if (updateSettings(['logo' => $logo_path])) {
                        $success = "Logo berhasil diperbarui.";
                        $settings = getSettings(); // Refresh pengaturan
                    } else {
                        $error = "Gagal memperbarui logo di database.";
                    }
                } else {
                    $error = "Gagal mengunggah logo.";
                }
            }
        } else {
            $error = "Silakan pilih file logo untuk diunggah.";
        }
    } elseif ($action == 'operation') {
        // Perbarui jam operasional
        $operation_days = trim($_POST['operation_days']);
        $operation_hours = trim($_POST['operation_hours']);
        
        // Simpan pengaturan
        $settings_update = [
            'operation_days' => $operation_days,
            'operation_hours' => $operation_hours
        ];
        
        if (updateSettings($settings_update)) {
            $success = "Jam operasional berhasil diperbarui.";
            $settings = getSettings(); // Refresh pengaturan
        } else {
            $error = "Gagal memperbarui jam operasional.";
        }
    } elseif ($action == 'pricing') {
        // Perbarui harga dasar
        $print_bw_price = floatval($_POST['print_bw_price']);
        $print_color_price = floatval($_POST['print_color_price']);
        $cetak_base_price = floatval($_POST['cetak_base_price']);
        
        if ($print_bw_price <= 0 || $print_color_price <= 0 || $cetak_base_price <= 0) {
            $error = "Harga harus lebih dari 0.";
        } else {
            // Simpan pengaturan harga
            $pricing_update = [
                'print_bw_price' => $print_bw_price,
                'print_color_price' => $print_color_price,
                'cetak_base_price' => $cetak_base_price
            ];
            
            if (updateSettings($pricing_update)) {
                $success = "Harga dasar berhasil diperbarui.";
                $settings = getSettings(); // Refresh pengaturan
            } else {
                $error = "Gagal memperbarui harga dasar.";
            }
        }
    }
}

// Judul halaman
$page_title = "Pengaturan - Panel Admin";

// Sertakan header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper">
    <!-- Header Konten -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Pengaturan</h1>
                </div>
                <div class="col-sm-6">
                    <!-- Breadcrumb cleaned up -->
                </div>
            </div>
        </div>
    </div>

    <!-- Konten utama -->
    <section class="content">
        <div class="container-fluid">
            <!-- Pesan sukses/error -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Sidebar Menu -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-2"></i>Menu Pengaturan
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="#general" class="nav-link active" data-toggle="tab">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Umum
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#logo" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-image mr-2"></i>Logo
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#operation" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-clock mr-2"></i>Jam Operasional
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#pricing" class="nav-link" data-toggle="tab">
                                        <i class="fas fa-tags mr-2"></i>Harga Layanan
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Profile Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-store mr-2"></i>Info Bisnis
                            </h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img class="img-fluid rounded" 
                                     src="<?= isset($settings['logo']) && !empty($settings['logo']) ? '../' . $settings['logo'] : 'https://via.placeholder.com/150x80?text=Logo' ?>" 
                                     alt="Logo" style="max-height: 80px; max-width: 150px;">
                            </div>
                            <h5 class="text-primary"><?= isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Fantastic Pandawa' ?></h5>
                            <p class="text-muted mb-2"><?= isset($settings['site_description']) ? htmlspecialchars($settings['site_description']) : 'Jasa Print & Fotokopi' ?></p>
                            
                            <?php if (isset($settings['contact_whatsapp']) && !empty($settings['contact_whatsapp'])): ?>
                                <a href="https://wa.me/<?= $settings['contact_whatsapp'] ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Content Area -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Pengaturan Umum -->
                                <div class="tab-pane active" id="general">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">
                                            <i class="fas fa-info-circle text-primary mr-2"></i>Informasi Umum
                                        </h4>
                                    </div>
                                    
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="general">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="site_name">
                                                        <i class="fas fa-store mr-1"></i>Nama Bisnis <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="text" name="site_name" id="site_name" class="form-control" 
                                                           value="<?= isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Fantastic Pandawa' ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="site_description">
                                                        <i class="fas fa-tag mr-1"></i>Tagline/Deskripsi
                                                    </label>
                                                    <input type="text" name="site_description" id="site_description" class="form-control" 
                                                           value="<?= isset($settings['site_description']) ? htmlspecialchars($settings['site_description']) : 'Jasa Print & Fotokopi' ?>"
                                                           placeholder="Contoh: Jasa Print & Fotokopi Terpercaya">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_email">
                                                        <i class="fas fa-envelope mr-1"></i>Email Bisnis <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="email" name="contact_email" id="contact_email" class="form-control" 
                                                           value="<?= isset($settings['contact_email']) ? htmlspecialchars($settings['contact_email']) : '' ?>" 
                                                           placeholder="info@bisnis.com" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_phone">
                                                        <i class="fas fa-phone mr-1"></i>Nomor Telepon
                                                    </label>
                                                    <input type="text" name="contact_phone" id="contact_phone" class="form-control" 
                                                           value="<?= isset($settings['contact_phone']) ? htmlspecialchars($settings['contact_phone']) : '' ?>"
                                                           placeholder="021-12345678">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_whatsapp">
                                                        <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                                                    </label>
                                                    <input type="text" name="contact_whatsapp" id="contact_whatsapp" class="form-control" 
                                                           value="<?= isset($settings['contact_whatsapp']) ? htmlspecialchars($settings['contact_whatsapp']) : '' ?>"
                                                           placeholder="628123456789">
                                                    <small class="form-text text-muted">Format: 628123456789 (tanpa + dan spasi)</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contact_address">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>Alamat Bisnis
                                                    </label>
                                                    <textarea name="contact_address" id="contact_address" class="form-control" rows="3"
                                                              placeholder="Jl. Contoh No. 123, Kota, Provinsi"><?= isset($settings['contact_address']) ? htmlspecialchars($settings['contact_address']) : '' ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Logo -->
                                <div class="tab-pane" id="logo">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">
                                            <i class="fas fa-image text-primary mr-2"></i>Kelola Logo
                                        </h4>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <form method="POST" action="" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="logo">
                                                
                                                <div class="form-group">
                                                    <label for="logo">
                                                        <i class="fas fa-upload mr-1"></i>Unggah Logo Baru
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="custom-file">
                                                            <input type="file" name="logo" id="logo" class="custom-file-input" 
                                                                   accept="image/jpeg,image/png,image/gif">
                                                            <label class="custom-file-label" for="logo">Pilih file logo...</label>
                                                        </div>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        <i class="fas fa-info-circle mr-1"></i>
                                                        Format: JPG, PNG, GIF | Maksimal: 1MB | Rekomendasi: 300x150px
                                                    </small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-upload mr-2"></i>Unggah Logo
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Logo Saat Ini</label>
                                                <div class="border rounded p-3 text-center bg-light">
                                                    <img src="<?= isset($settings['logo']) && !empty($settings['logo']) ? '../' . $settings['logo'] : 'https://via.placeholder.com/300x150?text=Belum+Ada+Logo' ?>" 
                                                         alt="Logo Saat Ini" class="img-fluid" style="max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Jam Operasional -->
                                <div class="tab-pane" id="operation">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">
                                            <i class="fas fa-clock text-primary mr-2"></i>Jam Operasional
                                        </h4>
                                    </div>
                                    
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="operation">
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="operation_days">
                                                        <i class="fas fa-calendar-alt mr-1"></i>Hari Operasional
                                                    </label>
                                                    <input type="text" name="operation_days" id="operation_days" class="form-control" 
                                                           value="<?= isset($settings['operation_days']) ? htmlspecialchars($settings['operation_days']) : 'Senin - Sabtu' ?>" 
                                                           placeholder="Contoh: Senin - Sabtu">
                                                    <small class="form-text text-muted">Contoh: Senin - Sabtu, Setiap Hari, dll.</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="operation_hours">
                                                        <i class="fas fa-clock mr-1"></i>Jam Operasional
                                                    </label>
                                                    <input type="text" name="operation_hours" id="operation_hours" class="form-control" 
                                                           value="<?= isset($settings['operation_hours']) ? htmlspecialchars($settings['operation_hours']) : '08.00 - 20.00' ?>" 
                                                           placeholder="Contoh: 08.00 - 20.00">
                                                    <small class="form-text text-muted">Contoh: 08.00 - 20.00, 24 Jam, dll.</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Harga Dasar -->
                                <div class="tab-pane" id="pricing">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">
                                            <i class="fas fa-tags text-primary mr-2"></i>Pengaturan Harga
                                        </h4>
                                    </div>
                                    
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="pricing">
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="print_bw_price">
                                                        <i class="fas fa-print mr-1"></i>Print Hitam Putih
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="print_bw_price" id="print_bw_price" class="form-control" 
                                                               value="<?= isset($settings['print_bw_price']) ? $settings['print_bw_price'] : '500' ?>" 
                                                               min="0" step="100">
                                                    </div>
                                                    <small class="form-text text-muted">Per lembar</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="print_color_price">
                                                        <i class="fas fa-palette mr-1"></i>Print Berwarna
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="print_color_price" id="print_color_price" class="form-control" 
                                                               value="<?= isset($settings['print_color_price']) ? $settings['print_color_price'] : '1000' ?>" 
                                                               min="0" step="100">
                                                    </div>
                                                    <small class="form-text text-muted">Per lembar</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="cetak_base_price">
                                                        <i class="fas fa-copy mr-1"></i>Cetak Custom
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="cetak_base_price" id="cetak_base_price" class="form-control" 
                                                               value="<?= isset($settings['cetak_base_price']) ? $settings['cetak_base_price'] : '5000' ?>" 
                                                               min="0" step="1000">
                                                    </div>
                                                    <small class="form-text text-muted">Harga dasar</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Catatan:</strong> Harga ini adalah harga dasar yang dapat disesuaikan untuk setiap pesanan berdasarkan spesifikasi khusus.
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Custom CSS -->
<style>
/* Content header styling */
.content-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 0;
    padding: 1rem 0;
}

.content-header h1 {
    color: #1e293b;
    font-weight: 600;
}

/* Card improvements */
.card {
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 0.75rem 0.75rem 0 0 !important;
}

.card-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0;
}

/* Navigation pills */
.nav-pills .nav-link {
    border-radius: 0.5rem;
    margin-bottom: 0.25rem;
    color: #64748b;
    transition: all 0.2s ease;
    border: none;
}

.nav-pills .nav-link:hover {
    background-color: #f1f5f9;
    color: #3b82f6;
}

.nav-pills .nav-link.active {
    background-color: #3b82f6;
    color: white;
}

/* Form styling */
.form-group label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

/* Input group styling */
.input-group-text {
    background-color: #f9fafb;
    border: 1.5px solid #d1d5db;
    color: #6b7280;
}

.input-group:focus-within .input-group-text {
    border-color: #3b82f6;
    background-color: rgba(59, 130, 246, 0.05);
}

/* Button styling */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.btn-primary:hover {
    background-color: #2563eb;
    border-color: #2563eb;
    transform: translateY(-1px);
}

.btn-success {
    background-color: #10b981;
    border-color: #10b981;
}

.btn-success:hover {
    background-color: #059669;
    border-color: #059669;
    transform: translateY(-1px);
}

/* Alert styling */
.alert {
    border-radius: 0.75rem;
    border: none;
    font-weight: 500;
}

.alert-success {
    background-color: #ecfdf5;
    color: #047857;
}

.alert-danger {
    background-color: #fef2f2;
    color: #b91c1c;
}

.alert-info {
    background-color: #eff6ff;
    color: #1d4ed8;
}

/* Custom file input */
.custom-file-label {
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
}

.custom-file-input:focus ~ .custom-file-label {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Image styling */
.img-fluid {
    max-width: 100%;
    height: auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .col-md-3,
    .col-md-9 {
        margin-bottom: 1rem;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}

/* Tab content spacing */
.tab-pane {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Profile card enhancements */
.card-body.text-center img {
    transition: transform 0.2s ease;
}

.card-body.text-center img:hover {
    transform: scale(1.05);
}

/* Form section headers */
.tab-pane h4 {
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
}

/* Help text styling */
.form-text {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Success states */
.was-validated .form-control:valid {
    border-color: #10b981;
}

.was-validated .form-control:invalid {
    border-color: #ef4444;
}
</style>

<!-- Enhanced JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Custom file input functionality
    const fileInput = document.querySelector('.custom-file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Pilih file logo...';
            const label = e.target.nextElementSibling;
            label.textContent = fileName;
            
            // Preview image if it's an image file
            if (e.target.files[0] && e.target.files[0].type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here if needed
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
    
    // Tab management with URL hash
    const hash = window.location.hash;
    if (hash) {
        const tabLink = document.querySelector(`.nav-pills a[href="${hash}"]`);
        if (tabLink) {
            // Remove active class from all tabs
            document.querySelectorAll('.nav-pills .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Add active class to selected tab
            tabLink.classList.add('active');
            document.querySelector(hash).classList.add('active');
        }
    }
    
    // Update URL hash when tab changes
    document.querySelectorAll('.nav-pills a[data-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            window.location.hash = e.target.getAttribute('href');
        });
    });
    
    // Form validation enhancement
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add loading state to submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
                submitBtn.disabled = true;
                
                // Re-enable button after 3 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    });
    
    // WhatsApp number formatting
    const whatsappInput = document.getElementById('contact_whatsapp');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            // Auto-add 62 prefix for Indonesian numbers starting with 08
            if (value.startsWith('08')) {
                value = '62' + value.substring(1);
            }
            
            e.target.value = value;
        });
    }
    
    // Input validation indicators
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() !== '') {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('is-valid', 'is-invalid');
        });
    });
    
    // Smooth scrolling for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    console.log('✅ Settings page initialized successfully');
});

// Utility functions
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = value;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showNotification(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
        ${message}
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>