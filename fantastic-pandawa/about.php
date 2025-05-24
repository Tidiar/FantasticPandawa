<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Dapatkan pengaturan website
$settings = getSettings();
$page_title = "Tentang Kami";
$page_description = "Pelajari lebih lanjut tentang " . ($settings['site_name'] ?? 'Fantastic Pandawa') . " - layanan print dan cetak terpercaya";

include 'includes/header.php';
?>

<!-- About Section -->
<section class="about-section py-5">
    <div class="container">
        <!-- Hero About -->
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <div class="about-content">
                    <h1 class="about-title"><?= $settings['site_name'] ?? 'Fantastic Pandawa' ?></h1>
                    <h2 class="about-subtitle">Solusi Print & Cetak Terpercaya Sejak 2020</h2>
                    <p class="about-description">
                        Kami adalah penyedia layanan print dan cetak terkemuka yang berkomitmen memberikan 
                        kualitas terbaik dengan harga terjangkau. Dengan pengalaman lebih dari 5 tahun, 
                        kami telah melayani ribuan pelanggan dari berbagai kalangan.
                    </p>
                    <div class="about-stats">
                        <div class="stat-item">
                            <h3 class="stat-number">50K+</h3>
                            <p class="stat-label">Dokumen Terprint</p>
                        </div>
                        <div class="stat-item">
                            <h3 class="stat-number">24/7</h3>
                            <p class="stat-label">Layanan Online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mission & Vision -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="mission-card">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="card-title">Misi Kami</h3>
                    <p class="card-description">
                        Memberikan layanan print dan cetak berkualitas tinggi dengan teknologi terdepan, 
                        pelayanan yang ramah, dan harga yang kompetitif untuk memenuhi kebutuhan setiap pelanggan.
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="vision-card">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="card-title">Visi Kami</h3>
                    <p class="card-description">
                        Menjadi penyedia layanan print dan cetak digital terkemuka di Indonesia yang dikenal 
                        karena inovasi, kualitas, dan kepuasan pelanggan.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Our Values -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="section-title">Nilai-Nilai Kami</h2>
                <p class="section-subtitle">Prinsip yang memandu setiap langkah kami</p>
            </div>
        </div>
        
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h4 class="value-title">Kualitas</h4>
                    <p class="value-description">
                        Kami menggunakan teknologi printing terbaru dan bahan berkualitas tinggi untuk hasil yang sempurna.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4 class="value-title">Kecepatan</h4>
                    <p class="value-description">
                        Proses yang efisien dan pengerjaan yang cepat tanpa mengorbankan kualitas hasil.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4 class="value-title">Kepercayaan</h4>
                    <p class="value-description">
                        Membangun hubungan jangka panjang dengan pelanggan berdasarkan kepercayaan dan integritas.
                    </p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h4 class="value-title">Harga Terjangkau</h4>
                    <p class="value-description">
                        Memberikan nilai terbaik dengan harga yang kompetitif dan transparan.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Our Services Overview -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="section-title">Layanan Kami</h2>
                <p class="section-subtitle">Berbagai solusi print dan cetak untuk kebutuhan Anda</p>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center mb-5">
            <div class="col-lg-5 col-md-6">
                <div class="service-overview-card">
                    <div class="service-icon-header">
                        <div class="service-main-icon">
                            <i class="fas fa-print"></i>
                        </div>
                        <div class="service-decorative-icons">
                            <i class="fas fa-file-alt"></i>
                            <i class="fas fa-copy"></i>
                            <i class="fas fa-download"></i>
                        </div>
                    </div>
                    <div class="service-content">
                        <h4 class="service-title">Layanan Print</h4>
                        <p class="service-description">
                            Print dokumen hitam putih dan berwarna dengan kualitas profesional. 
                            Mendukung berbagai format file dan ukuran kertas.
                        </p>
                        <ul class="service-features">
                            <li>Print hitam putih mulai Rp <?= number_format($settings['print_bw_price'] ?? 500, 0, ',', '.') ?></li>
                            <li>Print berwarna mulai Rp <?= number_format($settings['print_color_price'] ?? 1000, 0, ',', '.') ?></li>
                            <li>Berbagai ukuran kertas (A4, A3, F4)</li>
                            <li>Upload file online 24/7</li>
                            <li>Hasil berkualitas tinggi</li>
                        </ul>
                        <a href="services/print.php" class="btn btn-outline-primary">Mulai Print Sekarang</a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5 col-md-6">
                <div class="service-overview-card">
                    <div class="service-icon-header cetak-header">
                        <div class="service-main-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="service-decorative-icons">
                            <i class="fas fa-image"></i>
                            <i class="fas fa-id-card"></i>
                            <i class="fas fa-flag"></i>
                        </div>
                    </div>
                    <div class="service-content">
                        <h4 class="service-title">Layanan Cetak</h4>
                        <p class="service-description">
                            Cetak custom untuk berbagai kebutuhan promosi dan personal. 
                            Dari kartu nama hingga banner besar dengan finishing berkualitas.
                        </p>
                        <ul class="service-features">
                            <li>Kartu nama & brosur profesional</li>
                            <li>Banner & spanduk besar</li>
                            <li>Undangan & stiker custom</li>
                            <li>Foto berkualitas tinggi</li>
                            <li>Konsultasi gratis</li>
                        </ul>
                        <a href="services/cetak.php" class="btn btn-outline-primary">Pesan Cetak Custom</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Why Choose Us -->
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2 class="section-title">Mengapa Memilih Kami?</h2>
                <p class="section-subtitle">Keunggulan yang membedakan kami dari yang lain</p>
            </div>
        </div>
        
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="advantage-item">
                    <div class="advantage-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="advantage-content">
                        <h4 class="advantage-title">Pengalaman & Expertise</h4>
                        <p class="advantage-description">
                            Dengan pengalaman lebih dari 5 tahun di industri printing, kami memahami 
                            kebutuhan dan ekspektasi pelanggan.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="advantage-item">
                    <div class="advantage-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="advantage-content">
                        <h4 class="advantage-title">Teknologi Modern</h4>
                        <p class="advantage-description">
                            Mesin printing terbaru dan teknologi digital yang memastikan hasil cetak 
                            berkualitas tinggi dan konsisten.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="advantage-item">
                    <div class="advantage-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="advantage-content">
                        <h4 class="advantage-title">Pengerjaan Cepat</h4>
                        <p class="advantage-description">
                            Sistem workflow yang efisien memungkinkan kami menyelesaikan pesanan 
                            dengan cepat tanpa mengurangi kualitas.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="advantage-item">
                    <div class="advantage-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="advantage-content">
                        <h4 class="advantage-title">Customer Support 24/7</h4>
                        <p class="advantage-description">
                            Tim customer service yang responsif dan siap membantu Anda kapan saja 
                            melalui berbagai channel komunikasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="row">
            <div class="col-12">
                <div class="cta-card">
                    <div class="cta-content">
                        <h2 class="cta-title">Siap Memulai Project Anda?</h2>
                        <p class="cta-description">
                            Bergabunglah dengan ribuan pelanggan yang sudah mempercayai layanan kami. 
                            Mulai pesanan pertama Anda hari ini!
                        </p>
                        <div class="cta-buttons">
                            <a href="services/print.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-print me-2"></i>Mulai Print
                            </a>
                            <a href="services/cetak.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-copy me-2"></i>Pesan Cetak
                            </a>
                            <a href="contact.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-phone me-2"></i>Hubungi Kami
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom CSS -->
<style>
.about-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.about-title {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.about-subtitle {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1.5rem;
}

.about-description {
    font-size: 1.125rem;
    color: var(--secondary-color);
    line-height: 1.7;
    margin-bottom: 2rem;
}

.about-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--secondary-color);
    font-weight: 500;
    margin: 0;
}

.mission-card,
.vision-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    height: 100%;
    transition: all 0.3s ease;
}

.mission-card:hover,
.vision-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.card-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.card-icon i {
    font-size: 1.5rem;
    color: white;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.card-description {
    color: var(--secondary-color);
    line-height: 1.6;
    margin: 0;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--secondary-color);
    margin-bottom: 0;
}

.value-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    text-align: center;
    height: 100%;
    transition: all 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.value-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.value-icon i {
    font-size: 2rem;
    color: white;
}

.value-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.value-description {
    color: var(--secondary-color);
    line-height: 1.6;
    margin: 0;
}

.service-overview-card {
    background: white;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
}

.service-overview-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

/* UPDATED: Pastikan kedua service header benar-benar sama persis */
.service-icon-header {
    height: 200px;
    background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    overflow: hidden;
}

.service-icon-header.cetak-header {
    background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 50%, #6366f1 100%);
}

.service-main-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.25);
    z-index: 2;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.service-main-icon i {
    font-size: 3rem;
    color: white;
}

.service-decorative-icons {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-around;
    opacity: 0.2;
}

.service-decorative-icons i {
    font-size: 1.2rem;
    color: white;
    animation: float 4s ease-in-out infinite;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.service-decorative-icons i:nth-child(1) {
    animation-delay: 0s;
    position: absolute;
    top: 20px;
    left: 20px;
}

.service-decorative-icons i:nth-child(2) {
    animation-delay: 1s;
    position: absolute;
    top: 20px;
    right: 20px;
}

.service-decorative-icons i:nth-child(3) {
    animation-delay: 2s;
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    33% {
        transform: translateY(-8px) rotate(2deg);
    }
    66% {
        transform: translateY(-4px) rotate(-1deg);
    }
}

.service-content {
    padding: 1.5rem;
}

.service-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.service-description {
    color: var(--secondary-color);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.service-features {
    list-style: none;
    padding: 0;
    margin-bottom: 1.5rem;
}

.service-features li {
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
    position: relative;
    padding-left: 1.5rem;
}

.service-features li:before {
    content: "✓";
    color: var(--success-color);
    font-weight: bold;
    position: absolute;
    left: 0;
}

.advantage-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.advantage-item:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.advantage-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.advantage-icon i {
    font-size: 1.5rem;
    color: white;
}

.advantage-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.advantage-description {
    color: var(--secondary-color);
    line-height: 1.6;
    margin: 0;
}

.cta-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 1rem;
    text-align: center;
    box-shadow: var(--shadow-lg);
}

.cta-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-description {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .about-title {
        font-size: 2.5rem;
    }
    
    .about-subtitle {
        font-size: 1.25rem;
    }
    
    .about-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .cta-buttons .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .advantage-item {
        flex-direction: column;
        text-align: center;
    }
    
    .service-icon-header {
        height: 150px;
    }
    
    .service-main-icon {
        width: 80px;
        height: 80px;
    }
    
    .service-main-icon i {
        font-size: 2.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>