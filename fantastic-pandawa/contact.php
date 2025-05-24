<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Hubungi Kami";
$page_description = "Hubungi " . ($settings['site_name'] ?? 'Fantastic Pandawa') . " untuk informasi layanan print dan cetak";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $subject = cleanInput($_POST['subject']);
    $message = cleanInput($_POST['message']);
    
    // Validasi input
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Semua field wajib diisi kecuali nomor telepon";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid";
    } elseif (strlen($message) < 10) {
        $error_message = "Pesan minimal 10 karakter";
    } else {
        // Simpan pesan kontak ke database (opsional)
        try {
            // Cek apakah tabel contact_messages ada
            $table_exists = false;
            try {
                $stmt = $conn->query("SELECT 1 FROM contact_messages LIMIT 1");
                $table_exists = true;
            } catch (PDOException $e) {
                // Buat tabel jika belum ada
                $sql = "CREATE TABLE contact_messages (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('new', 'read', 'replied') DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->exec($sql);
            }
            
            // Insert pesan
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            
            $success_message = "Terima kasih atas pesan Anda! Kami akan segera menghubungi Anda kembali.";
            
            // Reset form
            $name = $email = $phone = $subject = $message = '';
            
        } catch (PDOException $e) {
            $success_message = "Terima kasih atas pesan Anda! Kami akan segera menghubungi Anda kembali.";
        }
    }
}

include 'includes/header.php';
?>

<!-- Contact Section -->
<section class="contact-section py-5">
    <div class="container">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="contact-title">Hubungi Kami</h1>
                <p class="contact-subtitle">Ada pertanyaan? Kami siap membantu Anda 24/7</p>
            </div>
        </div>
        
        <!-- Contact Content -->
        <div class="row g-5">
            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="contact-info-card">
                    <h3 class="info-title">Informasi Kontak</h3>
                    <p class="info-description">
                        Hubungi kami melalui berbagai cara di bawah ini. Tim customer service kami siap membantu Anda.
                    </p>
                    
                    <!-- Contact Items -->
                    <div class="contact-items">
                        <!-- Address -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Alamat</h5>
                                <p><?= $settings['contact_address'] ?? 'Jl. Pandawa Raya No.Kel, Korpri Jaya, Kec. Sukarame, Kota Bandar Lampung, Lampung 35131' ?></p>
                            </div>
                        </div>
                        
                        <!-- Phone -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Telepon</h5>
                                <p>
                                    <a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>">
                                        <?= $settings['contact_phone'] ?? '0822-8243-9997' ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- WhatsApp -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="contact-details">
                                <h5>WhatsApp</h5>
                                <p>
                                    <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya ingin bertanya tentang layanan" target="_blank">
                                        <?= $settings['contact_whatsapp'] ?? '0822-8243-9997' ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Email</h5>
                                <p>
                                    <a href="mailto:<?= $settings['contact_email'] ?? 'info@fantasticpandawa.com' ?>">
                                        <?= $settings['contact_email'] ?? 'info@fantasticpandawa.com' ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Operating Hours -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h5>Jam Operasional</h5>
                                <p>
                                    <?= $settings['operation_days'] ?? 'Senin - Sabtu' ?><br>
                                    <?= $settings['operation_hours'] ?? '08.00 - 20.00' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions mt-4">
                        <h5 class="mb-3">Hubungi Langsung</h5>
                        <div class="d-grid gap-2">
                            <a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>" class="btn btn-outline-primary">
                                <i class="fas fa-phone me-2"></i>Telepon Sekarang
                            </a>
                            <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya ingin bertanya tentang layanan" target="_blank" class="btn btn-success">
                                <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="contact-form-card">
                    <h3 class="form-title">Kirim Pesan</h3>
                    <p class="form-description">
                        Isi formulir di bawah ini dan kami akan menghubungi Anda sesegera mungkin.
                    </p>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $success_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $error_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="contact-form needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nama Lengkap *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= isset($name) ? htmlspecialchars($name) : '' ?>"
                                       placeholder="Masukkan nama lengkap Anda"
                                       required>
                                <div class="invalid-feedback">
                                    Nama lengkap wajib diisi
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                                       placeholder="nama@email.com"
                                       required>
                                <div class="invalid-feedback">
                                    Email valid wajib diisi
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">No. Telepon</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>"
                                       placeholder="08xxxxxxxxxx">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="subject" class="form-label">Subjek *</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Pilih subjek pesan</option>
                                    <option value="Pertanyaan Umum" <?= (isset($subject) && $subject == 'Pertanyaan Umum') ? 'selected' : '' ?>>Pertanyaan Umum</option>
                                    <option value="Layanan Print" <?= (isset($subject) && $subject == 'Layanan Print') ? 'selected' : '' ?>>Layanan Print</option>
                                    <option value="Layanan Cetak" <?= (isset($subject) && $subject == 'Layanan Cetak') ? 'selected' : '' ?>>Layanan Cetak</option>
                                    <option value="Keluhan" <?= (isset($subject) && $subject == 'Keluhan') ? 'selected' : '' ?>>Keluhan</option>
                                    <option value="Saran" <?= (isset($subject) && $subject == 'Saran') ? 'selected' : '' ?>>Saran</option>
                                    <option value="Kerjasama" <?= (isset($subject) && $subject == 'Kerjasama') ? 'selected' : '' ?>>Kerjasama</option>
                                    <option value="Lainnya" <?= (isset($subject) && $subject == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                                </select>
                                <div class="invalid-feedback">
                                    Subjek wajib dipilih
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="message" class="form-label">Pesan *</label>
                            <textarea class="form-control auto-resize" 
                                      id="message" 
                                      name="message" 
                                      rows="5" 
                                      placeholder="Tuliskan pesan Anda di sini..."
                                      minlength="10"
                                      required><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                            <div class="invalid-feedback">
                                Pesan minimal 10 karakter
                            </div>
                            <small class="form-text text-muted">Minimal 10 karakter</small>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Map Section (Optional) -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="map-card">
                    <h3 class="map-title">Lokasi Kami</h3>
                    <p class="map-description">Kunjungi toko fisik kami untuk konsultasi langsung</p>
                    
                    <!-- Google Maps Embed -->
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3971.7234567890!2d105.2534567890!3d-5.4234567890!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNcKwMjUnMjQuNCJTIDEwNcKwMTUnMTIuNCJF!5e0!3m2!1sen!2sid!4v1234567890" 
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="https://maps.google.com/maps?q=<?= urlencode($settings['contact_address'] ?? 'Bandar Lampung') ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-map-marked-alt me-2"></i>Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.contact-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.contact-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.contact-subtitle {
    font-size: 1.125rem;
    color: var(--secondary-color);
    margin-bottom: 0;
}

.contact-info-card,
.contact-form-card,
.map-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    height: fit-content;
}

.info-title,
.form-title,
.map-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.info-description,
.form-description,
.map-description {
    color: var(--secondary-color);
    margin-bottom: 2rem;
}

.contact-items {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.contact-icon {
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-icon i {
    font-size: 1.25rem;
    color: white;
}

.contact-details h5 {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.contact-details p {
    color: var(--secondary-color);
    margin: 0;
    line-height: 1.5;
}

.contact-details a {
    color: var(--secondary-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-details a:hover {
    color: var(--primary-color);
}

.contact-form .form-label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.contact-form .form-control {
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.contact-form .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
}

.auto-resize {
    resize: none;
    overflow: hidden;
}

.map-container {
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: var(--shadow);
    margin-bottom: 1rem;
}

.map-container iframe {
    display: block;
}

@media (max-width: 768px) {
    .contact-title {
        font-size: 2rem;
    }
    
    .contact-info-card,
    .contact-form-card,
    .map-card {
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .contact-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .contact-icon {
        margin-bottom: 0.5rem;
    }
}
</style>

<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Auto-resize textarea
    const textarea = document.getElementById('message');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Character counter
    textarea.addEventListener('input', function() {
        const minLength = 10;
        const currentLength = this.value.length;
        
        if (currentLength < minLength) {
            this.setCustomValidity(`Pesan minimal ${minLength} karakter (${currentLength}/${minLength})`);
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>