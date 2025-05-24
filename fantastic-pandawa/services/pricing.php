<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

$settings = getSettings();
$page_title = "Daftar Harga";
$page_description = "Daftar harga lengkap layanan print dan cetak " . ($settings['site_name'] ?? 'Fantastic Pandawa');

include '../includes/header.php';
?>

<section class="pricing-section py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="pricing-title">Daftar Harga</h1>
                <p class="pricing-subtitle">Harga transparan dan kompetitif untuk semua layanan kami</p>
            </div>
        </div>

        <!-- Print Services Pricing -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3><i class="fas fa-print me-2"></i>Layanan Print</h3>
                        <p>Print dokumen dengan kualitas terbaik</p>
                    </div>
                    <div class="pricing-table">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Jenis Print</th>
                                    <th>Ukuran</th>
                                    <th>Harga per Lembar</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Hitam Putih</td>
                                    <td>A4</td>
                                    <td class="price">Rp <?= number_format($settings['print_bw_price'] ?? 500, 0, ',', '.') ?></td>
                                    <td>Standar office printing</td>
                                </tr>
                                <tr>
                                    <td>Berwarna</td>
                                    <td>A4</td>
                                    <td class="price">Rp <?= number_format($settings['print_color_price'] ?? 1000, 0, ',', '.') ?></td>
                                    <td>Full color printing</td>
                                </tr>
                                <tr>
                                    <td>Hitam Putih</td>
                                    <td>A3</td>
                                    <td class="price">Rp <?= number_format(($settings['print_bw_price'] ?? 500) * 2, 0, ',', '.') ?></td>
                                    <td>2x harga A4</td>
                                </tr>
                                <tr>
                                    <td>Berwarna</td>
                                    <td>A3</td>
                                    <td class="price">Rp <?= number_format(($settings['print_color_price'] ?? 1000) * 2, 0, ',', '.') ?></td>
                                    <td>2x harga A4</td>
                                </tr>
                                <tr>
                                    <td>Hitam Putih</td>
                                    <td>F4</td>
                                    <td class="price">Rp <?= number_format(($settings['print_bw_price'] ?? 500) * 1.2, 0, ',', '.') ?></td>
                                    <td>1.2x harga A4</td>
                                </tr>
                                <tr>
                                    <td>Berwarna</td>
                                    <td>F4</td>
                                    <td class="price">Rp <?= number_format(($settings['print_color_price'] ?? 1000) * 1.2, 0, ',', '.') ?></td>
                                    <td>1.2x harga A4</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pricing-footer">
                        <a href="print.php" class="btn btn-primary">
                            <i class="fas fa-print me-2"></i>Print Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cetak Services Pricing -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3><i class="fas fa-copy me-2"></i>Layanan Cetak Custom</h3>
                        <p>Cetak berbagai keperluan dengan kualitas premium</p>
                    </div>
                    <div class="pricing-table">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Jenis Cetakan</th>
                                    <th>Spesifikasi</th>
                                    <th>Harga</th>
                                    <th>Minimum Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Kartu Nama</td>
                                    <td>Art Carton 260gsm, full color, dua sisi</td>
                                    <td class="price">Rp 50.000</td>
                                    <td>100 pcs</td>
                                </tr>
                                <tr>
                                    <td>Brosur A4</td>
                                    <td>Art Paper 120gsm, full color, satu sisi</td>
                                    <td class="price">Rp 5.000</td>
                                    <td>1 pcs</td>
                                </tr>
                                <tr>
                                    <td>Undangan</td>
                                    <td>Art Carton 210gsm, full color, finishing</td>
                                    <td class="price">Rp 8.000</td>
                                    <td>1 pcs</td>
                                </tr>
                                <tr>
                                    <td>Banner</td>
                                    <td>Vinyl, full color, per meter persegi</td>
                                    <td class="price">Rp 25.000</td>
                                    <td>1 m²</td>
                                </tr>
                                <tr>
                                    <td>Stiker</td>
                                    <td>Vinyl, full color, cutting</td>
                                    <td class="price">Rp 3.000</td>
                                    <td>1 pcs</td>
                                </tr>
                                <tr>
                                    <td>Foto 4R</td>
                                    <td>Photo paper, glossy</td>
                                    <td class="price">Rp 2.000</td>
                                    <td>1 pcs</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pricing-footer">
                        <a href="cetak.php" class="btn btn-success">
                            <i class="fas fa-copy me-2"></i>Pesan Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="info-card">
                    <h4><i class="fas fa-info-circle me-2"></i>Informasi Penting</h4>
                    <ul class="info-list">
                        <li>Harga dapat berubah sewaktu-waktu tanpa pemberitahuan</li>
                        <li>Harga belum termasuk design (jika diperlukan)</li>
                        <li>Untuk quantity besar, hubungi kami untuk harga khusus</li>
                        <li>Finishing tambahan dikenakan biaya terpisah</li>
                        <li>Pembayaran dilakukan setelah barang selesai</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="info-card">
                    <h4><i class="fas fa-truck me-2"></i>Pengiriman</h4>
                    <ul class="info-list">
                        <li>Gratis ongkir untuk wilayah <?= explode(',', $settings['contact_address'] ?? 'Bandar Lampung')[0] ?></li>
                        <li>Ongkir luar kota sesuai tarif ekspedisi</li>
                        <li>Bisa ambil sendiri di toko (gratis)</li>
                        <li>Estimasi pengerjaan 1-3 hari kerja</li>
                        <li>Rush order bisa diatur (tambahan biaya)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Contact CTA -->
        <div class="row">
            <div class="col-12">
                <div class="cta-card text-center">
                    <h3>Butuh Konsultasi Harga?</h3>
                    <p>Tim kami siap membantu menentukan solusi terbaik untuk kebutuhan Anda</p>
                    <div class="cta-buttons">
                        <a href="../contact.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-phone me-2"></i>Hubungi Kami
                        </a>
                        <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya ingin konsultasi harga" target="_blank" class="btn btn-success btn-lg">
                            <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.pricing-section {
    background: #f8fafc;
    min-height: calc(100vh - 160px);
}

.pricing-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 1rem;
}

.pricing-subtitle {
    font-size: 1.125rem;
    color: var(--secondary-color);
    margin-bottom: 0;
}

.pricing-card {
    background: white;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 2rem;
}

.pricing-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 2rem;
    text-align: center;
}

.pricing-header h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.pricing-header p {
    margin: 0;
    opacity: 0.9;
}

.pricing-table {
    padding: 0;
}

.pricing-table .table {
    margin: 0;
}

.pricing-table th {
    background: #f8fafc;
    font-weight: 600;
    color: var(--dark-color);
    border-top: none;
    padding: 1rem;
}

.pricing-table td {
    padding: 1rem;
    vertical-align: middle;
}

.pricing-table .price {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.pricing-footer {
    background: #f8fafc;
    padding: 1.5rem;
    text-align: center;
    border-top: 1px solid #e2e8f0;
}

.info-card {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow);
    height: 100%;
}

.info-card h4 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 1.5rem;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li {
    padding: 0.5rem 0;
    color: var(--secondary-color);
    position: relative;
    padding-left: 1.5rem;
}

.info-list li:before {
    content: "•";
    color: var(--primary-color);
    font-weight: bold;
    position: absolute;
    left: 0;
}

.cta-card {
    background: linear-gradient(135deg, var(--success-color), #059669);
    color: white;
    padding: 3rem 2rem;
    border-radius: 1rem;
    box-shadow: var(--shadow-lg);
}

.cta-card h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-card p {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .pricing-title {
        font-size: 2rem;
    }
    
    .pricing-table {
        overflow-x: auto;
    }
    
    .pricing-table .table {
        min-width: 600px;
    }
    
    .cta-buttons .btn {
        display: block;
        width: 100%;
        margin: 0.5rem 0;
    }
}
</style>

<?php include '../includes/footer.php'; ?>