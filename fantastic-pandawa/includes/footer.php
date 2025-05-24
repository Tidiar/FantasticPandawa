</main>
    
    <!-- Footer -->
    <footer class="footer bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-section">
                        <h5 class="footer-title mb-3">
                            <?= $settings['site_name'] ?? 'Fantastic Pandawa' ?>
                        </h5>
                        <p class="footer-text">
                            <?= $settings['site_description'] ?? 'Jasa print dan cetak terpercaya dengan kualitas terbaik dan harga terjangkau.' ?>
                        </p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h6 class="footer-title mb-3">Menu Cepat</h6>
                        <ul class="footer-links">
                            <li><a href="index.php">Beranda</a></li>
                            <li><a href="about.php">Tentang Kami</a></li>
                            <li><a href="services/print.php">Layanan Print</a></li>
                            <li><a href="services/cetak.php">Layanan Cetak</a></li>
                            <li><a href="contact.php">Kontak</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Services -->
                <div class="col-lg-2 col-md-6">
                    <div class="footer-section">
                        <h6 class="footer-title mb-3">Layanan</h6>
                        <ul class="footer-links">
                            <li><a href="services/print.php">Print Hitam Putih</a></li>
                            <li><a href="services/print.php">Print Berwarna</a></li>
                            <li><a href="services/cetak.php">Kartu Nama</a></li>
                            <li><a href="services/cetak.php">Brosur</a></li>
                            <li><a href="services/cetak.php">Banner</a></li>
                            <li><a href="services/cetak.php">Undangan</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-section">
                        <h6 class="footer-title mb-3">Kontak Kami</h6>
                        <div class="contact-info">
                            <div class="contact-item mb-3">
                                <i class="fas fa-map-marker-alt me-3"></i>
                                <span><?= $settings['contact_address'] ?? 'Alamat belum diatur' ?></span>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fas fa-phone me-3"></i>
                                <a href="tel:<?= $settings['contact_phone'] ?? '0822-8243-9997' ?>">
                                    <?= $settings['contact_phone'] ?? '0822-8243-9997' ?>
                                </a>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fab fa-whatsapp me-3"></i>
                                <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>" target="_blank">
                                    <?= $settings['contact_whatsapp'] ?? '0822-8243-9997' ?>
                                </a>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fas fa-envelope me-3"></i>
                                <a href="mailto:<?= $settings['contact_email'] ?? 'fantasticphotocopy@gmail.com' ?>">
                                    <?= $settings['contact_email'] ?? 'fantasticphotocopy@gmail.com' ?>
                                </a>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-clock me-3"></i>
                                <span>
                                    <?= $settings['operation_days'] ?? 'Senin - Sabtu' ?><br>
                                    <?= $settings['operation_hours'] ?? '08.00 - 20.00' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="row mt-4 pt-4 border-top border-secondary">
                <div class="col-md-6">
                    <p class="footer-copyright mb-0">
                        &copy; <?= date('Y') ?> <?= $settings['site_name'] ?? 'Fantastic Pandawa' ?>. 
                        All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="footer-links mb-0">
                        <a href="#" class="me-3">Privacy Policy</a>
                        <a href="#" class="me-3">Terms of Service</a>
                        <a href="#">Sitemap</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Float Button -->
    <div class="whatsapp-float">
        <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $settings['contact_whatsapp'] ?? '0822-8243-9997') ?>?text=Halo, saya ingin bertanya tentang layanan print dan cetak" 
           target="_blank" 
           class="whatsapp-btn" 
           title="Chat WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>

    <!-- Back to Top Button -->
    <button class="btn-back-to-top" id="backToTop" title="Back to Top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>