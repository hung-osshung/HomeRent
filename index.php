<?php
session_start();

require_once "config/kiem_tra_quyen.php";
require_once "includes/helpers.php";

$districts = [
    "Hải Châu", "Thanh Khê", "Sơn Trà", "Ngũ Hành Sơn",
    "Liên Chiểu", "Cẩm Lệ", "Hòa Vang"
];

/* Số tin theo từng quận */
$dem_quan = [];
$res = $conn->query("SELECT quan_huyen, COUNT(*) AS so_tin FROM tin_cho_thue GROUP BY quan_huyen");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $dem_quan[$r["quan_huyen"]] = (int) $r["so_tin"];
    }
}

/* 6 tin mới nhất */
$tin_moi = [];
$res = $conn->query("
    SELECT tin_cho_thue.*,
        (SELECT duong_dan_hinh FROM hinh_anh
         WHERE hinh_anh.ma_tin = tin_cho_thue.ma_tin LIMIT 1) AS hinh_dai_dien
    FROM tin_cho_thue
    ORDER BY tin_cho_thue.ngay_dang DESC
    LIMIT 6
");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $tin_moi[] = $r;
    }
}

$page_title = "Tìm nhà thuê tại Đà Nẵng";
include "includes/head.php";
?>
<body>
<?php include "includes/header.php"; ?>

<main id="noi-dung">

    <section class="hero">
        <div class="container hero-grid">
            <div>
                <h1>Tìm nhà thuê ở Đà&nbsp;Nẵng, gọi thẳng chủ&nbsp;nhà</h1>
                <p class="hero-sub">
                    Phòng trọ, căn hộ và nhà nguyên căn do chủ nhà đăng trực tiếp,
                    có giá và địa chỉ rõ ràng.
                </p>
            </div>
            <figure class="hero-photo">
                <img src="images/da_nang.jpg"
                     alt="Toàn cảnh Đà Nẵng nhìn từ trên cao: sông Hàn, bãi biển và núi phía xa">
            </figure>
        </div>
    </section>

    <div class="container search-dock">
        <form class="hero-search" action="tim_nha.php" method="GET">
            <label class="hs-field">
                <span>Quận / Huyện</span>
                <select name="quan_huyen">
                    <option value="">Tất cả quận</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo e($d); ?>"><?php echo e($d); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="hs-field">
                <span>Mức giá</span>
                <select name="gia">
                    <option value="">Mọi mức giá</option>
                    <option value="0-2000000">Dưới 2 triệu</option>
                    <option value="2000000-5000000">2 - 5 triệu</option>
                    <option value="5000000-10000000">5 - 10 triệu</option>
                    <option value="10000000-">Trên 10 triệu</option>
                </select>
            </label>
            <label class="hs-field grow">
                <span>Từ khóa</span>
                <input type="text" name="tu_khoa" placeholder="Tên đường, tiêu đề tin...">
            </label>
            <button type="submit" class="btn btn-accent">
                <?php echo icon("search", 18); ?> Tìm nhà
            </button>
        </form>

        <div class="chips">
            <span class="chips-label">Khu vực:</span>
            <?php foreach ($districts as $d): ?>
                <a class="chip" href="tim_nha.php?quan_huyen=<?php echo urlencode($d); ?>">
                    <?php echo e($d); ?>
                    <?php if (!empty($dem_quan[$d])): ?>
                        <b><?php echo $dem_quan[$d]; ?></b>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <h2>Tin mới đăng</h2>
                <a class="link-arrow" href="tim_nha.php">Xem tất cả <?php echo icon("arrow-right", 18); ?></a>
            </div>

            <?php if (!empty($tin_moi)): ?>
                <div class="listing-grid">
                    <?php foreach ($tin_moi as $row) { render_listing_card($row); } ?>
                </div>
            <?php else: ?>
                <div class="empty">
                    <h3>Chưa có tin nào được đăng</h3>
                    <p>Tin cho thuê mới sẽ xuất hiện ở đây ngay khi chủ nhà đăng.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head"><h2>Thuê nhà qua HomeRent</h2></div>
            <ol class="steps">
                <li>
                    <h3>Lọc theo quận và mức giá</h3>
                    <p>Chọn khu vực, khoảng giá hoặc gõ tên đường để thu hẹp danh sách.</p>
                </li>
                <li>
                    <h3>Xem ảnh, giá và địa chỉ</h3>
                    <p>Mỗi tin có ảnh thật, giá thuê hằng tháng và mô tả do chủ nhà viết.</p>
                </li>
                <li>
                    <h3>Gọi chủ nhà để hẹn xem</h3>
                    <p>Bấm gọi ngay trên trang chi tiết, không qua trung gian.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cta-band">
                <div>
                    <h2>Bạn có nhà, phòng trọ cần cho thuê?</h2>
                    <p>Đăng tin kèm ảnh và giá để người thuê ở Đà Nẵng tìm thấy bạn.</p>
                </div>
                <?php if (daDangNhap() && kiemTraQuyen("THEM_TIN")): ?>
                    <a class="btn btn-accent" href="dang_tin.php">Đăng tin cho thuê</a>
                <?php elseif (daDangNhap()): ?>
                    <a class="btn btn-accent" href="trung_tam_ho_tro.php">Liên hệ hỗ trợ</a>
                <?php else: ?>
                    <a class="btn btn-accent" href="register.php" data-auth="register.php">Tạo tài khoản chủ nhà</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php include "includes/footer.php"; ?>
</body>
</html>
