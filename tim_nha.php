<?php
session_start();

require_once "config/database.php";
require_once "config/kiem_tra_quyen.php";
require_once "includes/helpers.php";

/* =========================
   LẤY DỮ LIỆU TÌM KIẾM
========================= */
$tu_khoa    = trim($_GET["tu_khoa"] ?? "");
$thanh_pho  = trim($_GET["thanh_pho"] ?? "");
$quan_huyen = trim($_GET["quan_huyen"] ?? "");
$gia_tu     = trim($_GET["gia_tu"] ?? "");
$gia_den    = trim($_GET["gia_den"] ?? "");
$sap_xep    = $_GET["sap_xep"] ?? "moi";

/* Khoảng giá nhanh từ trang chủ: gia=2000000-5000000 */
if (isset($_GET["gia"]) && preg_match('/^(\d*)-(\d*)$/', $_GET["gia"], $m)) {
    if ($gia_tu === "" && $m[1] !== "") { $gia_tu = $m[1]; }
    if ($gia_den === "" && $m[2] !== "") { $gia_den = $m[2]; }
}

$orders = [
    "moi"      => "tin_cho_thue.ngay_dang DESC",
    "gia_tang" => "tin_cho_thue.gia_thue ASC",
    "gia_giam" => "tin_cho_thue.gia_thue DESC",
];
if (!isset($orders[$sap_xep])) {
    $sap_xep = "moi";
}

/* =========================
   TẠO TRUY VẤN
========================= */
$sql = "
    SELECT
        tin_cho_thue.*,
        (
            SELECT duong_dan_hinh
            FROM hinh_anh
            WHERE hinh_anh.ma_tin = tin_cho_thue.ma_tin
            LIMIT 1
        ) AS hinh_dai_dien
    FROM tin_cho_thue
    WHERE 1 = 1
";

$params = [];
$types = "";

if ($tu_khoa != "") {
    $sql .= " AND (tin_cho_thue.tieu_de LIKE ? OR tin_cho_thue.mo_ta LIKE ? OR tin_cho_thue.dia_chi LIKE ?)";
    $keyword = "%" . $tu_khoa . "%";
    array_push($params, $keyword, $keyword, $keyword);
    $types .= "sss";
}
if ($thanh_pho != "") {
    $sql .= " AND tin_cho_thue.thanh_pho = ?";
    $params[] = $thanh_pho;
    $types .= "s";
}
if ($quan_huyen != "") {
    $sql .= " AND tin_cho_thue.quan_huyen = ?";
    $params[] = $quan_huyen;
    $types .= "s";
}
if ($gia_tu != "" && is_numeric($gia_tu)) {
    $sql .= " AND tin_cho_thue.gia_thue >= ?";
    $params[] = (float) $gia_tu;
    $types .= "d";
}
if ($gia_den != "" && is_numeric($gia_den)) {
    $sql .= " AND tin_cho_thue.gia_thue <= ?";
    $params[] = (float) $gia_den;
    $types .= "d";
}

$sql .= " ORDER BY " . $orders[$sap_xep];

$result = false;
$total_results = 0;

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $total_results = $result->num_rows;
}

$districts = [
    "Hải Châu", "Thanh Khê", "Sơn Trà", "Ngũ Hành Sơn",
    "Liên Chiểu", "Cẩm Lệ", "Hòa Vang"
];

$page_title = "Tìm nhà";
include "includes/head.php";
?>
<body>
<?php include "includes/header.php"; ?>

<div class="page-head">
    <div class="container">
        <h1>Tìm nhà thuê</h1>
        <p>Lọc theo khu vực, mức giá hoặc từ khóa để tìm nơi ở phù hợp.</p>
    </div>
</div>

<main id="noi-dung" class="container finder">

    <aside>
        <form id="filterForm" class="panel filter-card" method="GET" action="tim_nha.php">
            <h2>Bộ lọc</h2>

            <div class="field">
                <label for="tu_khoa">Từ khóa</label>
                <input class="input" type="text" id="tu_khoa" name="tu_khoa"
                       placeholder="Tên đường, tiêu đề..."
                       value="<?php echo e($tu_khoa); ?>">
            </div>

            <div class="field">
                <label for="thanh_pho">Thành phố</label>
                <select class="select" id="thanh_pho" name="thanh_pho">
                    <option value="">Tất cả</option>
                    <option value="Đà Nẵng" <?php echo $thanh_pho == "Đà Nẵng" ? "selected" : ""; ?>>Đà Nẵng</option>
                </select>
            </div>

            <div class="field">
                <label for="quan_huyen">Quận / Huyện</label>
                <select class="select" id="quan_huyen" name="quan_huyen">
                    <option value="">Tất cả</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo e($d); ?>" <?php echo $quan_huyen == $d ? "selected" : ""; ?>>
                            <?php echo e($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <span class="label">Giá thuê (đ/tháng)</span>
                <div class="price-pair">
                    <input class="input" type="number" name="gia_tu" min="0" step="100000"
                           placeholder="Từ" aria-label="Giá từ"
                           value="<?php echo e($gia_tu); ?>">
                    <input class="input" type="number" name="gia_den" min="0" step="100000"
                           placeholder="Đến" aria-label="Giá đến"
                           value="<?php echo e($gia_den); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Áp dụng bộ lọc</button>
            <a href="tim_nha.php" class="btn btn-outline btn-block">Xóa bộ lọc</a>
        </form>
    </aside>

    <section>
        <div class="results-bar">
            <p><strong><?php echo $total_results; ?></strong> tin phù hợp</p>
            <div class="sort-box">
                <label for="sap_xep">Sắp xếp</label>
                <select class="select" id="sap_xep" name="sap_xep" form="filterForm"
                        onchange="this.form.submit()">
                    <option value="moi" <?php echo $sap_xep == "moi" ? "selected" : ""; ?>>Mới nhất</option>
                    <option value="gia_tang" <?php echo $sap_xep == "gia_tang" ? "selected" : ""; ?>>Giá thấp đến cao</option>
                    <option value="gia_giam" <?php echo $sap_xep == "gia_giam" ? "selected" : ""; ?>>Giá cao đến thấp</option>
                </select>
            </div>
        </div>

        <?php if ($total_results > 0 && $result): ?>
            <div class="listing-grid">
                <?php while ($row = $result->fetch_assoc()) { render_listing_card($row); } ?>
            </div>
        <?php else: ?>
            <div class="empty">
                <h3>Không tìm thấy tin phù hợp</h3>
                <p>Thử đổi từ khóa hoặc nới rộng khoảng giá.</p>
                <a class="btn btn-primary" href="tim_nha.php">Xóa bộ lọc</a>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php include "includes/footer.php"; ?>
</body>
</html>
