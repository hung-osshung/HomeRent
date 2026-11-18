<?php
require_once __DIR__ . "/../config/kiem_tra_quyen.php";
require_once __DIR__ . "/helpers.php";

$current_page = basename($_SERVER["PHP_SELF"]);
$da_dang_nhap = daDangNhap();
$co_quyen_dang_tin = $da_dang_nhap && kiemTraQuyen("THEM_TIN");
$ten_hien_thi = $_SESSION["ho_ten"] ?? "";
?>
<a class="skip-link" href="#noi-dung">Bỏ qua điều hướng</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php">
            <svg viewBox="0 0 32 32" aria-hidden="true">
                <rect width="32" height="32" rx="9" fill="#117A8B"/>
                <path d="M8 15 16 8l8 7v7H8z" fill="#fff"/>
                <path d="M5 26c3-2.2 5 1.8 8 0s5 1.8 8 0 4-1 6-1" fill="none" stroke="#F4A83A" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>HomeRent</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false"
                aria-controls="siteNav" aria-label="Mở menu">
            <?php echo icon("menu", 22); ?>
        </button>

        <nav id="siteNav" class="site-nav" aria-label="Điều hướng chính">
            <a class="nav-link <?php echo $current_page == 'index.php' ? 'is-active' : ''; ?>" href="index.php">Trang chủ</a>
            <a class="nav-link <?php echo in_array($current_page, ['tim_nha.php', 'chi_tiet_tin.php']) ? 'is-active' : ''; ?>" href="tim_nha.php">Tìm nhà</a>
            <?php if ($co_quyen_dang_tin): ?>
                <a class="nav-link <?php echo $current_page == 'dang_tin.php' ? 'is-active' : ''; ?>" href="dang_tin.php">Đăng tin</a>
            <?php endif; ?>
            <?php if ($da_dang_nhap): ?>
                <a class="nav-link <?php echo $current_page == 'trung_tam_ho_tro.php' ? 'is-active' : ''; ?>" href="trung_tam_ho_tro.php">Hỗ trợ</a>
            <?php endif; ?>

            <div class="nav-account">
                <?php if ($da_dang_nhap): ?>
                    <span class="nav-user">Xin chào, <strong><?php echo e($ten_hien_thi); ?></strong></span>
                    <a class="btn btn-outline btn-sm" href="logout.php">Đăng xuất</a>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="login.php" data-auth="login.php">Đăng nhập</a>
                    <a class="btn btn-primary btn-sm" href="register.php" data-auth="register.php">Đăng ký</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
