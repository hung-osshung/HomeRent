<?php
$da_dang_nhap_footer = isset($_SESSION["ma_nguoi_dung"]);
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <p class="footer-brand">HomeRent</p>
            <p class="footer-text">Tìm và cho thuê nhà, phòng trọ tại Đà Nẵng.</p>
        </div>
        <nav class="footer-links" aria-label="Liên kết cuối trang">
            <a href="tim_nha.php">Tìm nhà</a>
            <a href="trung_tam_ho_tro.php">Trung tâm hỗ trợ</a>
        </nav>
    </div>
    <div class="container footer-copy">© 2026 HomeRent</div>
</footer>

<?php if (!$da_dang_nhap_footer): ?>
    <div class="auth-modal" id="authModal" role="dialog" aria-modal="true"
         aria-label="Đăng nhập hoặc đăng ký" hidden>
        <div class="auth-modal-box">
            <button type="button" class="auth-modal-close" data-auth-close aria-label="Đóng">
                <?php echo icon("close", 20); ?>
            </button>
            <iframe id="authFrame" title="Đăng nhập hoặc đăng ký" src="about:blank"></iframe>
        </div>
    </div>
<?php endif; ?>

<script src="js/script.js"></script>
