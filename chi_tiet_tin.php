<?php

session_start();

require_once "config/database.php";
require_once "config/kiem_tra_quyen.php";
require_once "includes/helpers.php";


/* ==================================================
   KIỂM TRA QUYỀN XEM TIN
================================================== */

if (!kiemTraQuyen("XEM_TIN")) {

    if (!isset($_SESSION["ma_nguoi_dung"])) {

        header("Location: login.php");
        exit();

    }

    header("Location: index.php");
    exit();
}


/* ==================================================
   KIỂM TRA MÃ TIN
================================================== */

if (
    !isset($_GET["ma_tin"]) ||
    !is_numeric($_GET["ma_tin"])
) {

    header("Location: tim_nha.php");
    exit();
}

$ma_tin = (int) $_GET["ma_tin"];


/* ==================================================
   LẤY THÔNG TIN TIN
================================================== */

$sql = "
    SELECT
        t.ma_tin,
        t.tieu_de,
        t.mo_ta,
        t.dia_chi,
        t.thanh_pho,
        t.quan_huyen,
        t.gia_thue,
        t.trang_thai,
        t.ngay_dang,
        t.ma_nguoi_dang,

        n.ho_ten,
        n.email,
        n.so_dien_thoai

    FROM tin_cho_thue t

    LEFT JOIN nguoi_dung n
        ON t.ma_nguoi_dang = n.ma_nguoi_dung

    WHERE t.ma_tin = ?

    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Không thể chuẩn bị truy vấn!");

}


$stmt->bind_param(
    "i",
    $ma_tin
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {

    $stmt->close();

    header("Location: tim_nha.php");
    exit();

}


$tin = $result->fetch_assoc();

$stmt->close();


/* ==================================================
   LẤY HÌNH ẢNH
================================================== */

$sql_hinh = "
    SELECT duong_dan_hinh
    FROM hinh_anh
    WHERE ma_tin = ?
";


$stmt_hinh = $conn->prepare($sql_hinh);


if (!$stmt_hinh) {

    $danh_sach_hinh = [];

} else {

    $stmt_hinh->bind_param(
        "i",
        $ma_tin
    );

    $stmt_hinh->execute();

    $result_hinh = $stmt_hinh->get_result();

    $danh_sach_hinh = [];


    while (
        $row = $result_hinh->fetch_assoc()
    ) {

        $danh_sach_hinh[] =
            $row["duong_dan_hinh"];

    }


    $stmt_hinh->close();

}


/* ==================================================
   ẢNH CHÍNH
================================================== */

if (!empty($danh_sach_hinh)) {

    $anh_chinh = $danh_sach_hinh[0];

} else {

    $anh_chinh = "";

}


/* ==================================================
   FORMAT GIÁ
================================================== */

$gia = number_format(
    $tin["gia_thue"],
    0,
    ",",
    "."
);


/* ==================================================
   FORMAT NGÀY
================================================== */

$ngay_dang = date(
    "d/m/Y",
    strtotime($tin["ngay_dang"])
);

?>

<?php
$ten_chu = trim($tin["ho_ten"] ?? "") !== "" ? $tin["ho_ten"] : "Chủ nhà";
$chu_cai = mb_strtoupper(mb_substr($ten_chu, 0, 1, "UTF-8"), "UTF-8");
$la_tin_cua_toi = isset($_SESSION["ma_nguoi_dung"])
    && $_SESSION["ma_nguoi_dung"] == $tin["ma_nguoi_dang"];

$page_title = $tin["tieu_de"];
include "includes/head.php";
?>
<body>
<?php include "includes/header.php"; ?>

<main id="noi-dung" class="container">

    <div style="padding-top:28px">
        <a class="back-link" href="tim_nha.php">
            <?php echo icon("arrow-left", 18); ?> Quay lại danh sách
        </a>
        <h1 class="detail-title"><?php echo e($tin["tieu_de"]); ?></h1>
        <p class="detail-loc">
            <?php echo icon("pin", 18); ?>
            <?php echo e($tin["dia_chi"]); ?>, <?php echo e($tin["quan_huyen"]); ?>, <?php echo e($tin["thanh_pho"]); ?>
        </p>
    </div>

    <div class="detail">

        <div class="detail-main">

            <div>
                <div class="gallery-main">
                    <?php if ($anh_chinh != ""): ?>
                        <img id="mainImage" src="<?php echo e($anh_chinh); ?>" alt="<?php echo e($tin["tieu_de"]); ?>">
                    <?php else: ?>
                        <?php echo icon("image", 48); ?>
                    <?php endif; ?>

                    <?php if (count($danh_sach_hinh) > 1): ?>
                        <button type="button" class="gallery-arrow prev" onclick="previousImage()" aria-label="Ảnh trước">
                            <?php echo icon("chev-left", 22); ?>
                        </button>
                        <button type="button" class="gallery-arrow next" onclick="nextImage()" aria-label="Ảnh sau">
                            <?php echo icon("chev-right", 22); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (count($danh_sach_hinh) > 1): ?>
                    <div class="thumbs">
                        <?php foreach ($danh_sach_hinh as $index => $anh): ?>
                            <button type="button"
                                    class="thumb <?php echo $index == 0 ? "selected" : ""; ?>"
                                    onclick="selectImage(<?php echo $index; ?>, this)"
                                    aria-label="Xem ảnh <?php echo $index + 1; ?>">
                                <img src="<?php echo e($anh); ?>" alt="">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <section class="panel">
                <h2>Thông tin chi tiết</h2>
                <dl class="facts">
                    <dt>Giá thuê</dt>
                    <dd><?php echo $gia; ?> đ/tháng</dd>
                    <dt>Địa chỉ</dt>
                    <dd><?php echo e($tin["dia_chi"]); ?></dd>
                    <dt>Quận / Huyện</dt>
                    <dd><?php echo e($tin["quan_huyen"]); ?></dd>
                    <dt>Thành phố</dt>
                    <dd><?php echo e($tin["thanh_pho"]); ?></dd>
                    <dt>Ngày đăng</dt>
                    <dd><?php echo $ngay_dang; ?></dd>
                </dl>
            </section>

            <section class="panel">
                <h2>Mô tả</h2>
                <p class="desc"><?php echo nl2br(e($tin["mo_ta"])); ?></p>
            </section>

        </div>

        <aside class="side">
            <div class="panel">
                <p class="price-big"><?php echo $gia; ?> đ<span>/tháng</span></p>

                <div class="owner">
                    <div class="avatar" aria-hidden="true"><?php echo e($chu_cai); ?></div>
                    <div>
                        <p class="name"><?php echo e($ten_chu); ?></p>
                        <p class="role">Người đăng tin</p>
                    </div>
                </div>

                <div class="stack">
                    <?php if ($la_tin_cua_toi): ?>
                        <p class="hint" style="margin:0">Đây là tin đăng của bạn.</p>
                    <?php elseif (!empty($tin["so_dien_thoai"])): ?>
                        <a class="btn btn-accent btn-block" href="tel:<?php echo e($tin["so_dien_thoai"]); ?>">
                            <?php echo icon("phone", 18); ?> Gọi <?php echo e($tin["so_dien_thoai"]); ?>
                        </a>
                    <?php else: ?>
                        <p class="hint" style="margin:0">Người đăng chưa cung cấp số điện thoại.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <h2 style="font-size:1rem">Chia sẻ tin này</h2>
                <div class="share-row">
                    <button type="button" class="btn btn-outline btn-sm" onclick="copyLink(this)">
                        <?php echo icon("link", 16); ?> Sao chép liên kết
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="shareFacebook()">Facebook</button>
                </div>
                <p style="margin:16px 0 0">
                    <a class="link-arrow" href="trung_tam_ho_tro.php" style="font-size:.9rem">
                        <?php echo icon("flag", 16); ?> Báo cáo tin này
                    </a>
                </p>
            </div>
        </aside>

    </div>
</main>

<?php include "includes/footer.php"; ?>

<script>
const images = <?php echo json_encode($danh_sach_hinh); ?>;
let currentImage = 0;

function showImage(index) {
    currentImage = index;
    const mainImage = document.getElementById("mainImage");
    if (mainImage && images[index]) {
        mainImage.src = images[index];
    }
    document.querySelectorAll(".thumb").forEach(function (item, i) {
        item.classList.toggle("selected", i === index);
    });
}
function selectImage(index) { showImage(index); }
function previousImage() {
    if (images.length) { showImage((currentImage - 1 + images.length) % images.length); }
}
function nextImage() {
    if (images.length) { showImage((currentImage + 1) % images.length); }
}
function shareFacebook() {
    window.open(
        "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(window.location.href),
        "_blank", "noopener"
    );
}
function copyLink(button) {
    navigator.clipboard.writeText(window.location.href).then(function () {
        const old = button.innerHTML;
        button.textContent = "Đã sao chép";
        setTimeout(function () { button.innerHTML = old; }, 1800);
    });
}
</script>
</body>
</html>
