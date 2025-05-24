<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Layanan Cetak Custom";
$page_description = "Cetak custom untuk berbagai kebutuhan promosi dan personal dengan kualitas terbaik";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $cetak_type = cleanInput($_POST['cetak_type']);
    $quantity = (int)$_POST['quantity'];
    $paper_type = cleanInput($_POST['paper_type']);
    $finishing = cleanInput($_POST['finishing']);
    $delivery = cleanInput($_POST['delivery']);
    $description = cleanInput($_POST['description']);
    
    // Handle file upload (optional)
    $design_file_path = null;
    if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] === UPLOAD_ERR_OK) {
        $design_file = $_FILES['design_file'];
        
        // Validate design file
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/postscript', 'image/vnd.adobe.photoshop'];
        $max_size = 50 * 1024 * 1024; // 50MB
        
        $file_validation = validateUploadFile($design_file, $allowed_types, $max_size);
        
        if (!$file_validation['success']) {
            $error_message = $file_validation['message'];
        } else {
            // Upload design file
            $upload_dir = '../uploads/design-files/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($design_file['name'], PATHINFO_EXTENSION);
            $design_filename = 'design_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $design_file_path = 'uploads/design-files/' . $design_filename;
            
            if (!move_uploaded_file($design_file['tmp_name'], $upload_dir . $design_filename)) {
                $error_message = "Gagal upload file design";
            }
        }
    }
    
    // Validasi input
    if (!isset($error_message)) {
        if (empty($cetak_type) || $quantity < 1 || empty($paper_type) || empty($finishing) || empty($delivery)) {
            $error_message = "Semua field wajib diisi";
        } elseif ($quantity > 10000) {
            $error_message = "Jumlah maksimal 10.000 pcs";
        } elseif (!in_array($cetak_type, ['kartu-nama', 'brosur', 'undangan', 'banner', 'stiker', 'foto', 'lainnya'])) {
            $error_message = "Jenis cetakan tidak valid";
        } else {
            // Proses pembuatan pesanan
            try {
                $conn->beginTransaction();
                
                // Generate order number
                $order_number = 'C' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Calculate price based on type and quantity
                $base_prices = [
                    'kartu-nama' => 50000,
                    'brosur' => 5000,
                    'undangan' => 8000,
                    'banner' => 25000,
                    'stiker' => 3000,
                    'foto' => 2000,
                    'lainnya' => 10000
                ];
                
                $base_price = $base_prices[$cetak_type] ?? 10000;
                $total_price = max($quantity * $base_price, $base_price);
                
                // Insert order dengan payment_status 'pending'
                $stmt = $conn->prepare("INSERT INTO cetak_orders (
                    order_number, user_id, cetak_type, quantity, paper_type, 
                    finishing, delivery, description, design_file_path, price, 
                    status, payment_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())");
                
                $stmt->execute([
                    $order_number,
                    $user_id,
                    $cetak_type,
                    $quantity,
                    $paper_type,
                    $finishing,
                    $delivery,
                    $description,
                    $design_file_path,
                    $total_price
                ]);
                
                $order_id = $conn->lastInsertId();
                
                // Add to history
                $stmt = $conn->prepare("INSERT INTO cetak_order_history (order_id, status, notes, changed_by) VALUES (?, 'pending', 'Pesanan dibuat', ?)");
                $stmt->execute([$order_id, $user_id]);
                
                $conn->commit();
                
                // Redirect ke halaman pembayaran
                $_SESSION['success_message'] = "Pesanan cetak berhasil dibuat dengan nomor: $order_number";
                header("Location: payment.php?order_id={$order_id}&type=cetak");
                exit;
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_SESSION['user_id'])) {
    $_SESSION['warning_message'] = "Silakan login terlebih dahulu untuk membuat pesanan";
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../auth/login.php");
    exit;
}

include '../includes/header.php';
?>

<!-- Cetak Service Section -->
<section class="cetak-service-section py-5">
    <div class="container">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="service-title">Layanan Cetak Custom</h1>
                <p class="service-subtitle">Cetak berbagai keperluan promosi dan personal dengan kualitas profesional</p>
            </div>
        </div>
        
        <!-- Service Info & Form -->
        <div class="row g-5">
            <!-- Service Information -->
            <div class="col-lg-5">
                <div class="service-info">
                    <h3 class="mb-4">Informasi Layanan</h3>
                    
                    <!-- Price List -->
                    <div class="price-card mb-4">
                        <h5 class="price-title">
                            <i class="fas fa-dollar-sign me-2"></i>Daftar Harga
                        </h5>
                        <div class="price-list">
                            <div class="price-item">
                                <span class="price-label">Kartu Nama (100 pcs)</span>
                                <span class="price-value">Rp 50.000</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Brosur A4 (per pcs)</span>
                                <span class="price-value">Rp 5.000</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Undangan (per pcs)</span>
                                <span class="price-value">Rp 8.000</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Banner (per m²)</span>
                                <span class="price-value">Rp 25.000</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Stiker (per pcs)</span>
                                <span class="price-value">Rp 3.000</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Foto 4R (per pcs)</span>
                                <span class="price-value">Rp 2.000</span>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">*Harga dapat berubah tergantung kompleksitas design</small>
                    </div>
                    
                    <!-- Features -->
                    <div class="features-card mb-4">
                        <h5 class="features-title">
                            <i class="fas fa-star me-2"></i>Keunggulan Kami
                        </h5>
                        <ul class="features-list">
                            <li><i class="fas fa-check text-success me-2"></i>Kualitas cetak premium</li>
                            <li><i class="fas fa-check text-success me-2"></i>Berbagai pilihan material</li>
                            <li><i class="fas fa-check text-success me-2"></i>Finishing berkualitas tinggi</li>
                            <li><i class="fas fa-check text-success me-2"></i>Konsultasi design gratis</li>
                            <li><i class="fas fa-check text-success me-2"></i>Pengerjaan cepat</li>
                            <li><i class="fas fa-check text-success me-2"></i>Hasil tahan lama</li>
                        </ul>
                    </div>
                    
                    <!-- Materials -->
                    <div class="supported-files-card">
                        <h5 class="supported-title">
                            <i class="fas fa-palette me-2"></i>Material & Finishing
                        </h5>
                        <div class="file-types">
                            <span class="file-type">Art Paper</span>
                            <span class="file-type">Art Carton</span>
                            <span class="file-type">Ivory</span>
                            <span class="file-type">BC</span>
                            <span class="file-type">Vinyl</span>
                            <span class="file-type">Photo Paper</span>
                        </div>
                        <p class="file-note">Finishing: Glossy, Doff, Emboss, Spot UV, Laminating</p>
                    </div>
                </div>
            </div>
            
            <!-- Order Form -->
            <div class="col-lg-7">
                <div class="order-form-card">
                    <h3 class="form-title mb-4">
                        <i class="fas fa-copy me-2"></i>Buat Pesanan Cetak
                    </h3>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $error_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda harus <a href="../auth/login.php" class="alert-link">login</a> terlebih dahulu untuk membuat pesanan.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="cetak-order-form needs-validation" enctype="multipart/form-data" novalidate>
                        <!-- File Upload (Optional) -->
                        <div class="mb-4">
                            <label for="design_file" class="form-label">Upload Design (Opsional)</label>
                            <div class="file-upload-area" id="designUploadArea">
                                <input type="file" class="form-control file-input" id="design_file" name="design_file" accept=".pdf,.jpg,.jpeg,.png,.ai,.psd,.cdr">
                                <div class="file-upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Drag & Drop file design di sini</h5>
                                    <p class="text-muted">atau klik untuk memilih file</p>
                                    <small class="text-muted">Format: PDF, JPG, PNG, AI, PSD, CDR (Max: 50MB)</small>
                                </div>
                                <div class="file-preview" id="designPreview" style="display: none;">
                                    <i class="fas fa-file fa-2x text-primary mb-2"></i>
                                    <span class="file-name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeDesignFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Jika tidak ada file design, kami akan membuatkan design sesuai deskripsi Anda</small>
                        </div>

                        <!-- Cetak Type Selection -->
                        <div class="mb-4">
                            <label class="form-label">Jenis Cetakan *</label>
                            <div class="cetak-type-grid">
                                <div class="cetak-type-card" onclick="selectCetakType('kartu-nama')">
                                    <input type="radio" name="cetak_type" value="kartu-nama" id="kartu-nama" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Kartu Nama</h5>
                                    <p class="cetak-type-description">Profesional business card</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('brosur')">
                                    <input type="radio" name="cetak_type" value="brosur" id="brosur" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Brosur</h5>
                                    <p class="cetak-type-description">Flyer promosi berkualitas</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('undangan')">
                                    <input type="radio" name="cetak_type" value="undangan" id="undangan" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Undangan</h5>
                                    <p class="cetak-type-description">Undangan acara spesial</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('banner')">
                                    <input type="radio" name="cetak_type" value="banner" id="banner" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-flag"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Banner</h5>
                                    <p class="cetak-type-description">Banner besar promosi</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('stiker')">
                                    <input type="radio" name="cetak_type" value="stiker" id="stiker" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Stiker</h5>
                                    <p class="cetak-type-description">Stiker custom cutting</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('foto')">
                                    <input type="radio" name="cetak_type" value="foto" id="foto" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Foto</h5>
                                    <p class="cetak-type-description">Cetak foto premium</p>
                                </div>
                                
                                <div class="cetak-type-card" onclick="selectCetakType('lainnya')">
                                    <input type="radio" name="cetak_type" value="lainnya" id="lainnya" required>
                                    <div class="cetak-type-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <h5 class="cetak-type-title">Lainnya</h5>
                                    <p class="cetak-type-description">Kebutuhan khusus</p>
                                </div>
                            </div>
                            <div class="invalid-feedback">
                                Silakan pilih jenis cetakan
                            </div>
                        </div>
                        
                        <!-- Quantity & Specifications -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Jumlah *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="10000" required>
                                <div class="invalid-feedback">
                                    Jumlah harus antara 1-10.000
                                </div>
                                <small class="text-muted">Minimal 1, maksimal 10.000 pcs</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="paper_type" class="form-label">Jenis Kertas *</label>
                                <select class="form-control" id="paper_type" name="paper_type" required>
                                    <option value="">Pilih jenis kertas</option>
                                    <option value="Art Paper 120gsm">Art Paper 120gsm</option>
                                    <option value="Art Paper 150gsm">Art Paper 150gsm</option>
                                    <option value="Art Carton 210gsm">Art Carton 210gsm</option>
                                    <option value="Art Carton 260gsm">Art Carton 260gsm</option>
                                    <option value="Ivory 230gsm">Ivory 230gsm</option>
                                    <option value="BC 220gsm">BC 220gsm</option>
                                    <option value="Vinyl">Vinyl (untuk banner)</option>
                                    <option value="Photo Paper">Photo Paper</option>
                                </select>
                                <div class="invalid-feedback">
                                    Jenis kertas wajib dipilih
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="finishing" class="form-label">Finishing *</label>
                                <select class="form-control" id="finishing" name="finishing" required>
                                    <option value="">Pilih finishing</option>
                                    <option value="Glossy">Glossy (mengkilap)</option>
                                    <option value="Doff">Doff (tidak mengkilap)</option>
                                    <option value="Emboss">Emboss (timbul)</option>
                                    <option value="Spot UV">Spot UV</option>
                                    <option value="Laminating Glossy">Laminating Glossy</option>
                                    <option value="Laminating Doff">Laminating Doff</option>
                                    <option value="Tanpa Finishing">Tanpa Finishing</option>
                                </select>
                                <div class="invalid-feedback">
                                    Finishing wajib dipilih
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pengiriman *</label>
                                <div class="delivery-options">
                                    <div class="delivery-option" onclick="selectDelivery('pickup')">
                                        <input type="radio" name="delivery" value="pickup" id="pickup" required>
                                        <div class="delivery-option-icon">
                                            <i class="fas fa-store"></i>
                                        </div>
                                        <h6 class="delivery-option-title">Ambil Sendiri</h6>
                                        <p class="delivery-option-description">Ambil di toko (gratis)</p>
                                    </div>
                                    
                                    <div class="delivery-option" onclick="selectDelivery('delivery')">
                                        <input type="radio" name="delivery" value="delivery" id="delivery" required>
                                        <div class="delivery-option-icon">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <h6 class="delivery-option-title">Dikirim</h6>
                                        <p class="delivery-option-description">Ongkir sesuai jarak</p>
                                    </div>
                                </div>
                                <div class="invalid-feedback">
                                    Pilih metode pengiriman
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Deskripsi Pesanan *</label>
                            <textarea class="form-control auto-resize" id="description" name="description" rows="4" placeholder="Jelaskan detail pesanan Anda:&#10;- Ukuran yang diinginkan&#10;- Warna/design yang diinginkan&#10;- Spesifikasi khusus lainnya&#10;- Apakah sudah ada design atau perlu bantuan design" required></textarea>
                            <div class="invalid-feedback">
                                Deskripsi pesanan wajib diisi
                            </div>
                            <small class="text-muted">Jelaskan detail pesanan sejelas mungkin agar kami dapat memberikan hasil terbaik</small>
                        </div>
                        
                        <!-- Price Estimate -->
                        <div class="price-estimate-card mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="estimate-label">Estimasi Harga:</span>
                                <span class="estimate-price" id="price-estimate">Rp 0</span>
                            </div>
                            <small class="text-muted">Harga final akan dikonfirmasi setelah konsultasi dengan tim kami</small>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" <?= !isset($_SESSION['user_id']) ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane me-2"></i>
                                <?= isset($_SESSION['user_id']) ? 'Lanjut ke Pembayaran' : 'Login untuk Memesan' ?>
                            </button>
                        </div>
                        
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="text-center mt-3">
                                <a href="../auth/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<link rel="stylesheet" href="../assets/css/services.css">

<!-- Custom JavaScript with jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/cetak-service.js"></script>

<!-- Vanilla JavaScript fallback for basic functionality -->
<script>
// Cetak Type Selection - Pure JavaScript (no jQuery dependency)
function selectCetakType(type) {
    // Remove selected class from all cards
    document.querySelectorAll('.cetak-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById(type).checked = true;
    
    // Remove validation error
    document.querySelector('.cetak-type-grid').classList.remove('is-invalid');
    
    // Calculate price
    calculateCetakPrice();
    
    // Add animation
    event.currentTarget.style.transform = 'scale(0.95)';
    setTimeout(() => {
        event.currentTarget.style.transform = 'scale(1)';
    }, 150);
}

// Delivery Selection
function selectDelivery(type) {
    // Remove selected class from all delivery options
    document.querySelectorAll('.delivery-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById(type).checked = true;
    
    // Remove validation error
    document.querySelector('.delivery-options').classList.remove('is-invalid');
}

// Price Calculator
function calculateCetakPrice() {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const selectedType = document.querySelector('input[name="cetak_type"]:checked');
    
    if (!selectedType || quantity === 0) {
        document.getElementById('price-estimate').textContent = 'Rp 0';
        return;
    }
    
    // Base prices
    const basePrices = {
        'kartu-nama': 50000,
        'brosur': 5000,
        'undangan': 8000,
        'banner': 25000,
        'stiker': 3000,
        'foto': 2000,
        'lainnya': 10000
    };
    
    const basePrice = basePrices[selectedType.value] || 10000;
    const totalPrice = Math.max(quantity * basePrice, basePrice);
    
    // Update price display with animation
    const priceElement = document.getElementById('price-estimate');
    priceElement.style.opacity = '0.5';
    setTimeout(() => {
        priceElement.textContent = 'Rp ' + formatNumber(totalPrice);
        priceElement.style.opacity = '1';
        priceElement.classList.add('price-updated');
        setTimeout(() => {
            priceElement.classList.remove('price-updated');
        }, 500);
    }, 200);
}

// Function to remove design file
function removeDesignFile() {
    const fileInput = document.getElementById('design_file');
    const filePreview = document.getElementById('designPreview');
    const fileUploadContent = document.querySelector('#designUploadArea .file-upload-content');
    
    if (fileInput) fileInput.value = '';
    if (filePreview) filePreview.style.display = 'none';
    if (fileUploadContent) fileUploadContent.style.display = 'block';
}

// File Upload for Design
function initDesignFileUpload() {
    const fileUploadArea = document.getElementById('designUploadArea');
    const fileInput = document.getElementById('design_file');
    const filePreview = document.getElementById('designPreview');
    const fileUploadContent = fileUploadArea?.querySelector('.file-upload-content');
    
    if (!fileUploadArea || !fileInput) return;
    
    // Click to upload
    fileUploadArea.addEventListener('click', function(e) {
        if (e.target !== fileInput) {
            fileInput.click();
        }
    });
    
    // File input change
    fileInput.addEventListener('change', function() {
        handleDesignFileSelect(this.files[0]);
    });
    
    // Drag and drop
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });
    
    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });
    
    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleDesignFileSelect(files[0]);
        }
    });
}

// Handle Design File Selection
function handleDesignFileSelect(file) {
    if (!file) return;
    
    // Validate file
    const validation = validateDesignFile(file);
    if (!validation.success) {
        alert(validation.message);
        return;
    }
    
    // Show file preview
    showDesignFilePreview(file);
}

// Validate Design File
function validateDesignFile(file) {
    const maxSize = 50 * 1024 * 1024; // 50MB
    const allowedExtensions = ['.pdf', '.jpg', '.jpeg', '.png', '.ai', '.psd', '.cdr'];
    const fileName = file.name.toLowerCase();
    
    if (file.size > maxSize) {
        return {
            success: false,
            message: 'Ukuran file terlalu besar. Maksimal 50MB.'
        };
    }
    
    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
    if (!hasValidExtension) {
        return {
            success: false,
            message: 'Format file tidak didukung. Gunakan PDF, JPG, PNG, AI, PSD, atau CDR.'
        };
    }
    
    return { success: true };
}

// Show Design File Preview
function showDesignFilePreview(file) {
    const filePreview = document.getElementById('designPreview');
    const fileUploadContent = document.querySelector('#designUploadArea .file-upload-content');
    const fileName = filePreview?.querySelector('.file-name');
    
    if (fileName) fileName.textContent = file.name;
    if (fileUploadContent) fileUploadContent.style.display = 'none';
    if (filePreview) filePreview.style.display = 'flex';
}

// Format number with thousand separators
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Listen to quantity changes
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            // Validate quantity
            const quantity = parseInt(this.value);
            if (quantity > 0 && quantity <= 10000) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
            calculateCetakPrice();
        });
    }
    
    // Auto-resize textarea
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Validate description
            if (this.value.trim().length >= 10) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Check cetak type selection
            const selectedType = document.querySelector('input[name="cetak_type"]:checked');
            if (!selectedType) {
                document.querySelector('.cetak-type-grid').classList.add('is-invalid');
                isValid = false;
            } else {
                document.querySelector('.cetak-type-grid').classList.remove('is-invalid');
            }
            
            // Check delivery selection
            const selectedDelivery = document.querySelector('input[name="delivery"]:checked');
            if (!selectedDelivery) {
                document.querySelector('.delivery-options').classList.add('is-invalid');
                isValid = false;
            } else {
                document.querySelector('.delivery-options').classList.remove('is-invalid');
            }
            
            // Check description
            const description = document.getElementById('description');
            if (description && description.value.trim().length < 10) {
                description.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!form.checkValidity() || !isValid) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
                    submitBtn.disabled = true;
                }
            }
            
            form.classList.add('was-validated');
        });
    }
    
    // Initial price calculation
    calculateCetakPrice();
    
    // Initialize design file upload
    initDesignFileUpload();
    
    // Add hover effects to cards
    document.querySelectorAll('.cetak-type-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
                this.classList.add('card-hover');
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('card-hover');
        });
    });
    
    // Add click handlers for cetak type cards
    document.querySelectorAll('.cetak-type-card').forEach(card => {
        card.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"]');
            if (input) {
                selectCetakType(input.value);
            }
        });
    });
    
    // Add click handlers for delivery options
    document.querySelectorAll('.delivery-option').forEach(option => {
        option.addEventListener('click', function() {
            const input = this.querySelector('input[type="radio"]');
            if (input) {
                selectDelivery(input.value);
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>