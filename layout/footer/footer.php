<?php
require_once __DIR__ . '/../../config/config.php';
?>
<footer class="footer-main-footer">
    <div class="footer-content">
        <div class="footer-left">
            <div class="footer-logo">
                <i class="fas fa-map-marked-alt"></i>
                <span>Sistem Pendataan Desa</span>
            </div>
            <p class="footer-description">
                Sistem Informasi Analisis dan Pelaporan Data Desa Terintegrasi
                <br>Kecamatan Tibawa, Kabupaten Gorontalo
            </p>
        </div>
        
        <div class="footer-right">
            <div class="footer-info">
                <div class="footer-info-item">
                    <i class="fas fa-clock"></i>
                    <span>Last Update: <?php echo date('d F Y, H:i'); ?></span>
                </div>
                <div class="footer-info-item">
                    <i class="fas fa-user"></i>
                    <span><?php echo $_SESSION['user_name'] ?? 'Guest'; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-copyright">
            <p>&copy; <?php echo date('Y'); ?> Sistem Pendataan Desa. All rights reserved.</p>
        </div>
        <div class="footer-version">
            <span>Version 1.0.0</span>
        </div>
    </div>
</footer>