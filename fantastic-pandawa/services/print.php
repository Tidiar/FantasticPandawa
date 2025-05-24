<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Layanan Print";
$page_description = "Print dokumen hitam putih dan berwarna dengan kualitas terbaik dan harga terjangkau";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Validasi file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Silakan pilih file untuk diprint";
    } else {
        $file = $_FILES['file'];
        $copies = (int)$_POST['copies'];
        $paper_size = cleanInput($_POST['paper_size']);
        $print_color = cleanInput($_POST['print_color']);
        $paper_type = cleanInput($_POST['paper_type']);
        $notes = cleanInput($_POST['notes']);
        
        // Validasi input
        if ($copies < 1 || $copies > 1000) {
            $error_message = "Jumlah copy harus antara 1 - 1000";
        } elseif (!in_array($paper_size, ['A4', 'A3', 'F4'])) {
            $error_message = "Ukuran kertas tidak valid";
        } elseif (!in_array($print_color, ['BW', 'Color'])) {
            $error_message = "Jenis warna tidak valid";
        } else {
            // Validasi file
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            $file_validation = validateUploadFile($file, $allowed_types, $max_size);
            
            if (!$file_validation['success']) {
                $error_message = $file_validation['message'];
            } else {
                // Proses upload dan simpan pesanan
                try {
                    $conn->beginTransaction();
                    
                    // Generate order number
                    $order_number = 'P' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    // Upload file
                    $upload_dir = '../uploads/print-files/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = $order_number . '_' . time() . '.' . $file_extension;
                    $file_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                        throw new Exception('Gagal upload file');
                    }
                    
                    // Calculate price
                    $base_price = ($print_color === 'BW') ? ($settings['print_bw_price'] ?? 500) : ($settings['print_color_price'] ?? 1000);
                    $size_multipliers = ['A4' => 1, 'A3' => 2, 'F4' => 1.2];
                    $multiplier = $size_multipliers[$paper_size] ?? 1;
                    $total_price = $copies * $base_price * $multiplier;
                    
                    // Insert order dengan payment_status 'pending'
                    $stmt = $conn->prepare("INSERT INTO print_orders (
                        order_number, user_id, original_filename, file_path, copies, 
                        paper_size, print_color, paper_type, notes, price, 
                        status, payment_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())");
                    
                    $stmt->execute([
                        $order_number,
                        $user_id,
                        $file['name'],
                        'uploads/print-files/' . $filename,
                        $copies,
                        $paper_size,
                        $print_color,
                        $paper_type,
                        $notes,
                        $total_price
                    ]);
                    
                    $order_id = $conn->lastInsertId();
                    
                    // Add to history
                    $stmt = $conn->prepare("INSERT INTO print_order_history (order_id, status, notes, changed_by) VALUES (?, 'pending', 'Pesanan dibuat', ?)");
                    $stmt->execute([$order_id, $user_id]);
                    
                    $conn->commit();
                    
                    // Redirect ke halaman pembayaran
                    $_SESSION['success_message'] = "Pesanan print berhasil dibuat dengan nomor: $order_number";
                    header("Location: payment.php?order_id={$order_id}&type=print");
                    exit;
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error_message = "Terjadi kesalahan: " . $e->getMessage();
                }
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

<!-- Print Service Section -->
<section class="print-service-section py-5">
    <div class="container">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="service-title">Layanan Print Dokumen</h1>
                <p class="service-subtitle">Print dokumen hitam putih dan berwarna dengan kualitas terbaik</p>
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
                                <span class="price-label">Print Hitam Putih (A4)</span>
                                <span class="price-value">Rp <?= number_format($settings['print_bw_price'] ?? 500, 0, ',', '.') ?></span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Print Berwarna (A4)</span>
                                <span class="price-value">Rp <?= number_format($settings['print_color_price'] ?? 1000, 0, ',', '.') ?></span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Ukuran A3</span>
                                <span class="price-value">2x harga A4</span>
                            </div>
                            <div class="price-item">
                                <span class="price-label">Ukuran F4</span>
                                <span class="price-value">1.2x harga A4</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <div class="features-card mb-4">
                        <h5 class="features-title">
                            <i class="fas fa-star me-2"></i>Keunggulan Kami
                        </h5>
                        <ul class="features-list">
                            <li><i class="fas fa-check text-success me-2"></i>Kualitas print tajam dan jelas</li>
                            <li><i class="fas fa-check text-success me-2"></i>Berbagai ukuran kertas tersedia</li>
                            <li><i class="fas fa-check text-success me-2"></i>Proses cepat dan tepat waktu</li>
                            <li><i class="fas fa-check text-success me-2"></i>Harga kompetitif</li>
                            <li><i class="fas fa-check text-success me-2"></i>Upload file online 24/7</li>
                        </ul>
                    </div>
                    
                    <!-- Supported Files -->
                    <div class="supported-files-card">
                        <h5 class="supported-title">
                            <i class="fas fa-file me-2"></i>Format File Didukung
                        </h5>
                        <div class="file-types">
                            <span class="file-type">PDF</span>
                            <span class="file-type">DOC</span>
                            <span class="file-type">DOCX</span>
                            <span class="file-type">TXT</span>
                        </div>
                        <p class="file-note">Maksimal ukuran file: 10MB</p>
                    </div>
                </div>
            </div>
            
            <!-- Order Form -->
            <div class="col-lg-7">
                <div class="order-form-card">
                    <h3 class="form-title mb-4">
                        <i class="fas fa-print me-2"></i>Buat Pesanan Print
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
                    
                    <form method="POST" enctype="multipart/form-data" class="print-order-form needs-validation" novalidate>
                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file" class="form-label">Upload File *</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" class="form-control file-input" id="file" name="file" accept=".pdf,.doc,.docx,.txt" required>
                                <div class="file-upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Drag & Drop file di sini</h5>
                                    <p class="text-muted">atau klik untuk memilih file</p>
                                    <small class="text-muted">Format: PDF, DOC, DOCX, TXT (Max: 10MB)</small>
                                </div>
                                <div class="file-preview" id="filePreview" style="display: none;">
                                    <i class="fas fa-file fa-2x text-primary mb-2"></i>
                                    <span class="file-name"></span>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="invalid-feedback">
                                Silakan pilih file untuk diprint
                            </div>
                        </div>
                        
                        <!-- Print Options -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="copies" class="form-label">Jumlah Copy *</label>
                                <input type="number" class="form-control" id="copies" name="copies" value="1" min="1" max="1000" required>
                                <div class="invalid-feedback">
                                    Jumlah copy harus antara 1-1000
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="paper_size" class="form-label">Ukuran Kertas *</label>
                                <select class="form-control" id="paper_size" name="paper_size" required>
                                    <option value="A4">A4 (21 x 29.7 cm)</option>
                                    <option value="A3">A3 (29.7 x 42 cm)</option>
                                    <option value="F4">F4 (21.5 x 33 cm)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="print_color" class="form-label">Jenis Warna *</label>
                                <select class="form-control" id="print_color" name="print_color" required>
                                    <option value="BW">Hitam Putih</option>
                                    <option value="Color">Berwarna</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="paper_type" class="form-label">Jenis Kertas *</label>
                                <select class="form-control" id="paper_type" name="paper_type" required>
                                    <option value="HVS 70gsm">HVS 70gsm</option>
                                    <option value="HVS 80gsm">HVS 80gsm</option>
                                    <option value="Art Paper 120gsm">Art Paper 120gsm</option>
                                    <option value="Photo Paper">Photo Paper</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">Catatan Tambahan</label>
                            <textarea class="form-control auto-resize" id="notes" name="notes" rows="3" placeholder="Tuliskan catatan khusus untuk pesanan Anda (opsional)"></textarea>
                        </div>
                        
                        <!-- Price Estimate -->
                        <div class="price-estimate-card mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="estimate-label">Estimasi Harga:</span>
                                <span class="estimate-price" id="price-estimate">Rp 0</span>
                            </div>
                            <small class="text-muted">Harga dapat berubah tergantung kompleksitas dokumen</small>
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

<!-- Custom JavaScript -->
<script>
// File upload handling
function removeFile() {
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('filePreview');
    const fileUploadContent = document.querySelector('#fileUploadArea .file-upload-content');
    
    if (fileInput) fileInput.value = '';
    if (filePreview) filePreview.style.display = 'none';
    if (fileUploadContent) fileUploadContent.style.display = 'block';
}

function initFileUpload() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('file');
    const filePreview = document.getElementById('filePreview');
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
        handleFileSelect(this.files[0]);
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
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    });
}

function handleFileSelect(file) {
    if (!file) return;
    
    // Validate file
    const validation = validateFile(file);
    if (!validation.success) {
        alert(validation.message);
        return;
    }
    
    // Show file preview
    showFilePreview(file);
    
    // Calculate price
    calculatePrintPrice();
}

function validateFile(file) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
    const fileName = file.name.toLowerCase();
    
    if (file.size > maxSize) {
        return {
            success: false,
            message: 'Ukuran file terlalu besar. Maksimal 10MB.'
        };
    }
    
    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
    if (!hasValidExtension) {
        return {
            success: false,
            message: 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, atau TXT.'
        };
    }
    
    return { success: true };
}

function showFilePreview(file) {
    const filePreview = document.getElementById('filePreview');
    const fileUploadContent = document.querySelector('#fileUploadArea .file-upload-content');
    const fileName = filePreview?.querySelector('.file-name');
    
    if (fileName) fileName.textContent = file.name;
    if (fileUploadContent) fileUploadContent.style.display = 'none';
    if (filePreview) filePreview.style.display = 'flex';
}

function calculatePrintPrice() {
    const copies = parseInt(document.getElementById('copies').value) || 0;
    const paperSize = document.getElementById('paper_size').value;
    const printColor = document.getElementById('print_color').value;
    
    if (copies === 0) {
        document.getElementById('price-estimate').textContent = 'Rp 0';
        return;
    }
    
    // Base prices
    const bwPrice = <?= $settings['print_bw_price'] ?? 500 ?>;
    const colorPrice = <?= $settings['print_color_price'] ?? 1000 ?>;
    const basePrice = (printColor === 'BW') ? bwPrice : colorPrice;
    
    // Size multipliers
    const sizeMultipliers = { 'A4': 1, 'A3': 2, 'F4': 1.2 };
    const multiplier = sizeMultipliers[paperSize] || 1;
    
    const totalPrice = copies * basePrice * multiplier;
    
    // Update price display
    const priceElement = document.getElementById('price-estimate');
    priceElement.style.opacity = '0.5';
    setTimeout(() => {
        priceElement.textContent = 'Rp ' + formatNumber(totalPrice);
        priceElement.style.opacity = '1';
    }, 200);
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize file upload
    initFileUpload();
    
    // Listen to input changes for price calculation
    ['copies', 'paper_size', 'print_color'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', calculatePrintPrice);
            element.addEventListener('input', calculatePrintPrice);
        }
    });
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(event) {
            const fileInput = document.getElementById('file');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                event.preventDefault();
                event.stopPropagation();
                alert('Silakan pilih file untuk diprint');
                return;
            }
            
            if (!form.checkValidity()) {
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
    calculatePrintPrice();
});
</script>

<?php include '../includes/footer.php'; ?>