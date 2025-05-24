<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Beranda";
$page_description = $settings['site_description'] ?? 'Jasa Print & Fotokopi Terpercaya';

include 'includes/header.php';
?>

<!-- Hero Section - Posisi Dibalik (Gambar Kiri, Teks Kanan) -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-container">
            <!-- Hero Image (kiri) -->
            <div class="hero-image-wrapper">
                <div class="hero-image-container">
                    <img src="assets/images/hero-print1.jpg" 
                         alt="Print Services <?= $settings['site_name'] ?? 'Fantastic Pandawa' ?>" 
                         class="hero-image">
                </div>
            </div>
            
            <!-- Hero Content (kanan) -->
            <div class="hero-content-wrapper">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-star me-2"></i>
                        Terpercaya sejak 2020
                    </div>
                    
                    <h1 class="hero-title hero-fade-in">
                        <span class="text-primary"><?= $settings['site_name'] ?? 'Fantastic Pandawa' ?></span><br>
                        Solusi Print & Cetak Terbaik
                    </h1>
                    
                    <p class="hero-description hero-fade-in delay-1">
                        Layanan print dokumen dan cetak custom berkualitas tinggi dengan harga terjangkau. 
                        Melayani print hitam putih, berwarna, cetak brosur, kartu nama, undangan, dan masih banyak lagi.
                    </p>
                    
                    <!-- Hero Features -->
                    <ul class="hero-features hero-fade-in delay-2">
                        <li><i class="fas fa-check-circle text-success me-2"></i>Print mulai Rp <?= number_format($settings['print_bw_price'] ?? 500, 0, ',', '.') ?></li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Order online 24/7</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Kualitas premium</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Gratis konsultasi design</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Layanan Kami</h2>
                <p class="section-subtitle">Berbagai layanan print dan cetak untuk kebutuhan Anda</p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Print Service -->
            <div class="col-lg-6 col-md-6">
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <h3 class="service-title">Layanan Print</h3>
                    <p class="service-description">
                        Print dokumen hitam putih dan berwarna dengan kualitas terbaik. 
                        Mendukung berbagai ukuran kertas dan jenis dokumen.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check text-success me-2"></i>Print Hitam Putih mulai Rp <?= number_format($settings['print_bw_price'] ?? 500, 0, ',', '.') ?></li>
                        <li><i class="fas fa-check text-success me-2"></i>Print Berwarna mulai Rp <?= number_format($settings['print_color_price'] ?? 1000, 0, ',', '.') ?></li>
                        <li><i class="fas fa-check text-success me-2"></i>Berbagai ukuran kertas (A4, A3, F4)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Upload file online</li>
                    </ul>
                    <a href="services/print.php" class="btn btn-primary">Print Sekarang</a>
                </div>
            </div>
            
            <!-- Cetak Service -->
            <div class="col-lg-6 col-md-6">
                <div class="service-card h-100">
                    <div class="service-icon">
                        <i class="fas fa-copy"></i>
                    </div>
                    <h3 class="service-title">Layanan Cetak</h3>
                    <p class="service-description">
                        Cetak custom untuk berbagai kebutuhan promosi dan personal. 
                        Dari kartu nama hingga banner besar.
                    </p>
                    <ul class="service-features">
                        <li><i class="fas fa-check text-success me-2"></i>Kartu Nama & Brosur</li>
                        <li><i class="fas fa-check text-success me-2"></i>Undangan & Banner</li>
                        <li><i class="fas fa-check text-success me-2"></i>Stiker & Foto</li>
                        <li><i class="fas fa-check text-success me-2"></i>Kualitas premium</li>
                    </ul>
                    <a href="services/cetak.php" class="btn btn-primary">Cetak Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Mengapa Memilih Kami?</h2>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="feature-item text-center">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Cepat & Tepat Waktu</h4>
                    <p>Proses pesanan cepat dengan hasil berkualitas tinggi sesuai deadline yang dijanjikan.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="feature-item text-center">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4>Kualitas Terbaik</h4>
                    <p>Menggunakan mesin print modern dan kertas berkualitas untuk hasil yang memuaskan.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="feature-item text-center">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h4>Harga Terjangkau</h4>
                    <p>Tarif kompetitif dengan berbagai paket hemat untuk kebutuhan print dan cetak Anda.</p>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="feature-item text-center">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>Pelayanan 24/7</h4>
                    <p>Tim customer service siap membantu Anda kapan saja melalui WhatsApp dan telepon.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h2 class="text-white mb-3">Siap untuk Memulai Project Anda?</h2>
                <p class="text-white-50 mb-4">Hubungi kami sekarang untuk konsultasi gratis dan penawaran terbaik</p>
                <div class="cta-buttons">
                    <a href="<?= isset($_SESSION['user_id']) ? 'user/dashboard.php' : 'auth/register.php' ?>" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-user-plus me-2"></i>
                        <?= isset($_SESSION['user_id']) ? 'Dashboard' : 'Daftar Sekarang' ?>
                    </a>
                    <a href="contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-phone me-2"></i>Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Info Section -->
<section class="contact-info-section py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-content">
                        <h5>Alamat Kami</h5>
                        <p><?= $settings['contact_address'] ?? 'Alamat belum diatur' ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-content">
                        <h5>Telepon</h5>
                        <p><a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>"><?= $settings['contact_phone'] ?? '0822-8243-9997' ?></a></p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-content">
                        <h5>Email</h5>
                        <p><a href="mailto:<?= $settings['contact_email'] ?? 'info@fantasticpandawa.com' ?>"><?= $settings['contact_email'] ?? 'info@fantasticpandawa.com' ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced CSS untuk Hero Section dengan Equal Height -->
<style>
:root {
    --primary-color: #3b82f6;
    --primary-dark: #1e40af;
    --dark-color: #1f2937;
    --secondary-color: #6b7280;
    --success-color: #10b981;
}

/* Global Button Styles */
.btn {
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary-color);
    color: white !important;
    border: 2px solid var(--primary-color);
}

.btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-outline-primary {
    background: transparent;
    color: var(--primary-color) !important;
    border: 2px solid var(--primary-color);
}

.btn-outline-primary:hover {
    background: var(--primary-color);
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-lg {
    padding: 0.875rem 2rem;
    font-size: 1.1rem;
}

.btn-outline-light {
    background: transparent;
    color: white !important;
    border: 2px solid white;
}

.btn-outline-light:hover {
    background: white;
    color: var(--primary-color) !important;
    transform: translateY(-2px);
}

.btn-light {
    background: white;
    color: var(--primary-color) !important;
    border: 2px solid white;
}

.btn-light:hover {
    background: #f8f9fa;
    color: var(--primary-dark) !important;
    transform: translateY(-2px);
}

.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 5rem 0;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.1);
}

/* Equal Height Hero Container */
.hero-container {
    display: flex;
    align-items: stretch;
    gap: 3rem;
    width: 100%;
    min-height: 600px;
}

.hero-content-wrapper,
.hero-image-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.hero-content {
    position: relative;
    z-index: 2;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 3rem 2.5rem;
    border-radius: 1.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.2);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    width: fit-content;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    color: var(--dark-color);
}

.hero-title .text-primary {
    color: var(--primary-color) !important;
}

.hero-description {
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
    color: var(--secondary-color);
    line-height: 1.6;
}

.hero-features {
    list-style: none;
    padding: 0;
    margin-bottom: 2.5rem;
}

.hero-features li {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    color: var(--secondary-color);
}

.hero-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: var(--secondary-color);
    margin-top: 0.25rem;
}

/* Hero Image Styling */
.hero-image-wrapper {
    position: relative;
}

.hero-image-container {
    position: relative;
    height: 100%;
    min-height: 500px;
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.hero-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.hero-image-container:hover .hero-image {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

/* Animations */
.hero-fade-in {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.8s ease forwards;
}

.hero-fade-in.delay-1 {
    animation-delay: 0.2s;
}

.hero-fade-in.delay-2 {
    animation-delay: 0.4s;
}

.hero-fade-in.delay-3 {
    animation-delay: 0.6s;
}

.hero-fade-in.delay-4 {
    animation-delay: 0.8s;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .hero-container {
        flex-direction: column-reverse; /* Ubah ke column-reverse agar pada mobile gambar di atas dan teks di bawah */
        gap: 2rem;
        min-height: auto;
    }
    
    .hero-content {
        padding: 2rem 1.5rem;
    }
    
    .hero-title {
        font-size: 2.25rem;
    }
    
    .hero-description {
        font-size: 1rem;
    }
    
    .hero-stats {
        justify-content: center;
        gap: 1.5rem;
    }
    
    .hero-image-container {
        min-height: 300px;
    }
}

@media (max-width: 576px) {
    .hero-section {
        padding: 3rem 0;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-stats {
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 1.25rem;
    }
}

/* Rest of the existing styles remain the same */
.service-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.service-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.service-icon i {
    font-size: 2rem;
    color: white;
}

.service-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #1f2937;
}

.service-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.service-features {
    list-style: none;
    margin-bottom: 2rem;
    padding: 0;
}

.service-features li {
    margin-bottom: 0.5rem;
    color: #6b7280;
}

.feature-item {
    padding: 2rem 1rem;
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    transition: all 0.3s ease;
}

.feature-icon i {
    font-size: 2rem;
    color: white;
}

.feature-item:hover .feature-icon {
    transform: scale(1.1);
    background: var(--primary-dark);
}

.cta-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.contact-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.contact-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.contact-icon i {
    font-size: 1.5rem;
    color: white;
}

.contact-content h5 {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.contact-content p {
    margin: 0;
    color: #6b7280;
}

.contact-content a {
    color: #6b7280;
    text-decoration: none;
}

.contact-content a:hover {
    color: var(--primary-color);
}
</style>

<!-- JavaScript untuk Equal Height dan Animasi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure equal heights between content and image
    function setEqualHeroHeight() {
        const heroContent = document.querySelector('.hero-content-wrapper');
        const heroImage = document.querySelector('.hero-image-wrapper');
        
        if (heroContent && heroImage && window.innerWidth > 768) {
            // Reset heights
            heroContent.style.minHeight = 'auto';
            heroImage.style.minHeight = 'auto';
            
            // Get natural heights
            const contentHeight = heroContent.offsetHeight;
            const imageHeight = heroImage.offsetHeight;
            
            // Set equal height to the taller one
            const maxHeight = Math.max(contentHeight, imageHeight, 500);
            heroContent.style.minHeight = maxHeight + 'px';
            heroImage.style.minHeight = maxHeight + 'px';
        }
    }
    
    // Set equal height on load and resize
    setEqualHeroHeight();
    window.addEventListener('resize', setEqualHeroHeight);
    
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all fade-in elements
    document.querySelectorAll('.hero-fade-in').forEach(el => {
        observer.observe(el);
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
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
});
</script>

<?php include 'includes/footer.php'; ?>