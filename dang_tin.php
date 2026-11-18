<?php

session_start();

require_once "config/database.php";
require_once "config/kiem_tra_quyen.php";
require_once "includes/helpers.php";


/* =========================
   KIỂM TRA ĐĂNG NHẬP
========================= */

if (!isset($_SESSION["ma_nguoi_dung"])) {

    header("Location: login.php");
    exit();
}


/* =========================
   KIỂM TRA QUYỀN ĐĂNG TIN
========================= */

if (!kiemTraQuyen("THEM_TIN")) {

    header("Location: index.php");
    exit();
}


$message = "";
$message_type = "";


/* =========================
   XỬ LÝ ĐĂNG TIN
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /*
     * Lấy dữ liệu từ form
     */

    $tieu_de = trim($_POST["tieu_de"] ?? "");
    $dia_chi = trim($_POST["dia_chi"] ?? "");
    $thanh_pho = trim($_POST["thanh_pho"] ?? "");
    $quan_huyen = trim($_POST["quan_huyen"] ?? "");
    $gia_thue = trim($_POST["gia_thue"] ?? "");
    $mo_ta = trim($_POST["mo_ta"] ?? "");


    /*
     * Lấy mã người dùng đang đăng nhập
     */

    $ma_nguoi_dang = (int) $_SESSION["ma_nguoi_dung"];


    /* =========================
       KIỂM TRA DỮ LIỆU
    ========================= */

    if (
        empty($tieu_de) ||
        empty($dia_chi) ||
        empty($thanh_pho) ||
        empty($quan_huyen) ||
        $gia_thue === "" ||
        empty($mo_ta)
    ) {

        $message = "Vui lòng nhập đầy đủ thông tin!";
        $message_type = "error";
    }


    /* =========================
       KIỂM TRA GIÁ THUÊ
    ========================= */

    else if (!is_numeric($gia_thue) || $gia_thue < 0) {

        $message = "Giá thuê không hợp lệ!";
        $message_type = "error";
    }


    /* =========================
       KIỂM TRA ẢNH
    ========================= */

    else if (
        !isset($_FILES["hinh_anh"]) ||
        $_FILES["hinh_anh"]["error"] != UPLOAD_ERR_OK
    ) {

        $message = "Vui lòng chọn hình ảnh cho tin đăng!";
        $message_type = "error";
    }


    else {

        $file = $_FILES["hinh_anh"];


        /*
         * Thông tin file
         */

        $file_name = $file["name"];
        $file_tmp = $file["tmp_name"];
        $file_size = $file["size"];


        /*
         * Lấy phần mở rộng
         */

        $file_ext = strtolower(
            pathinfo(
                $file_name,
                PATHINFO_EXTENSION
            )
        );


        /*
         * Định dạng cho phép
         */

        $allowed_extensions = [
            "jpg",
            "jpeg",
            "png"
        ];


        /* =========================
           KIỂM TRA ĐỊNH DẠNG
        ========================= */

        $image_info = @getimagesize($file_tmp);
        if (
            !in_array($file_ext, $allowed_extensions) ||
            $image_info === false ||
            !in_array($image_info["mime"], ["image/jpeg", "image/png"])
        ) {

            $message =
                "Chỉ chấp nhận hình ảnh JPG, JPEG hoặc PNG!";

            $message_type = "error";
        }


        /* =========================
           KIỂM TRA KÍCH THƯỚC
        ========================= */

        else if ($file_size > 5 * 1024 * 1024) {

            $message =
                "Hình ảnh không được vượt quá 5MB!";

            $message_type = "error";
        }


        else {

            /*
             * Tạo tên file mới
             */

            $new_file_name =
                time() .
                "_" .
                uniqid() .
                "." .
                $file_ext;


            /*
             * Thư mục upload
             */

            $upload_folder = "uploads/";


            /*
             * Tạo thư mục nếu chưa tồn tại
             */

            if (!is_dir($upload_folder)) {

                if (!mkdir(
                    $upload_folder,
                    0755,
                    true
                )) {

                    $message =
                        "Không thể tạo thư mục upload!";

                    $message_type = "error";
                }
            }


            /*
             * Chỉ tiếp tục nếu không có lỗi
             */

            if ($message == "") {

                $upload_path =
                    $upload_folder .
                    $new_file_name;


                /* =========================
                   TRANSACTION
                ========================= */

                $conn->begin_transaction();


                try {

                    /* =========================
                       THÊM TIN CHO THUÊ
                    ========================= */

                    $sql = "
                        INSERT INTO tin_cho_thue
                        (
                            tieu_de,
                            mo_ta,
                            dia_chi,
                            thanh_pho,
                            quan_huyen,
                            gia_thue,
                            ma_nguoi_dang
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ";


                    $stmt = $conn->prepare($sql);


                    if (!$stmt) {

                        throw new Exception(
                            "Không thể chuẩn bị câu lệnh thêm tin!"
                        );
                    }


                    /*
                     * Chuyển giá thuê thành số
                     */

                    $gia_thue_number =
                        (float) $gia_thue;


                    /*
                     * Gắn dữ liệu
                     */

                    $stmt->bind_param(
                        "sssssdi",
                        $tieu_de,
                        $mo_ta,
                        $dia_chi,
                        $thanh_pho,
                        $quan_huyen,
                        $gia_thue_number,
                        $ma_nguoi_dang
                    );


                    /*
                     * Thực thi
                     */

                    if (!$stmt->execute()) {

                        throw new Exception(
                            "Không thể lưu thông tin tin đăng!"
                        );
                    }


                    /*
                     * Lấy mã tin vừa tạo
                     */

                    $ma_tin = $conn->insert_id;


                    $stmt->close();


                    /* =========================
                       UPLOAD HÌNH ẢNH
                    ========================= */

                    if (!move_uploaded_file(
                        $file_tmp,
                        $upload_path
                    )) {

                        throw new Exception(
                            "Không thể tải hình ảnh lên!"
                        );
                    }


                    /* =========================
                       LƯU ẢNH VÀO DATABASE
                    ========================= */

                    $sql_image = "
                        INSERT INTO hinh_anh
                        (
                            duong_dan_hinh,
                            ma_tin
                        )
                        VALUES (?, ?)
                    ";


                    $stmt_image =
                        $conn->prepare($sql_image);


                    if (!$stmt_image) {

                        if (file_exists($upload_path)) {

                            unlink($upload_path);
                        }

                        throw new Exception(
                            "Không thể lưu thông tin hình ảnh!"
                        );
                    }


                    /*
                     * Lưu đường dẫn hình
                     */

                    $stmt_image->bind_param(
                        "si",
                        $upload_path,
                        $ma_tin
                    );


                    /*
                     * Thực thi
                     */

                    if (!$stmt_image->execute()) {

                        if (file_exists($upload_path)) {

                            unlink($upload_path);
                        }

                        throw new Exception(
                            "Không thể lưu hình ảnh vào database!"
                        );
                    }


                    $stmt_image->close();


                    /* =========================
                       HOÀN TẤT
                    ========================= */

                    $conn->commit();


                    $message =
                        "Đăng tin thành công!";

                    $message_type =
                        "success";


                    /*
                     * Xóa dữ liệu form
                     */

                    $_POST = [];


                } catch (Exception $e) {

                    /*
                     * Rollback database
                     */

                    $conn->rollback();


                    /*
                     * Xóa file nếu đã upload
                     */

                    if (
                        isset($upload_path) &&
                        file_exists($upload_path)
                    ) {

                        unlink($upload_path);
                    }


                    $message =
                        $e->getMessage();

                    $message_type =
                        "error";
                }
            }
        }
    }
}

?>


<?php
$districts = [
    "Hải Châu", "Thanh Khê", "Sơn Trà", "Ngũ Hành Sơn",
    "Liên Chiểu", "Cẩm Lệ", "Hòa Vang"
];
$page_title = "Đăng tin cho thuê";
include "includes/head.php";
?>
<body>
<?php include "includes/header.php"; ?>

<div class="page-head">
    <div class="container">
        <h1>Đăng tin cho thuê</h1>
        <p>Nhập thông tin và thêm một ảnh rõ nét để người thuê dễ tìm thấy bạn.</p>
    </div>
</div>

<main id="noi-dung" class="container two-col">

    <section class="panel">
        <?php if ($message != ""): ?>
            <div class="alert alert-<?php echo $message_type == "success" ? "success" : "error"; ?>" role="alert">
                <?php echo e($message); ?>
                <?php if ($message_type == "success"): ?>
                    <a href="tim_nha.php">Xem danh sách tin</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-grid">

            <div class="field span-2">
                <label for="tieu_de">Tiêu đề tin <span class="req">*</span></label>
                <input class="input" type="text" id="tieu_de" name="tieu_de" maxlength="255" required
                       placeholder="Ví dụ: Phòng trọ gần biển Mỹ Khê, có gác lửng"
                       value="<?php echo e($_POST["tieu_de"] ?? ""); ?>">
            </div>

            <div class="field">
                <label for="gia_thue">Giá thuê <span class="req">*</span></label>
                <div class="price-input">
                    <input class="input" type="number" id="gia_thue" name="gia_thue" min="0" step="1000" required
                           placeholder="3500000"
                           value="<?php echo e($_POST["gia_thue"] ?? ""); ?>">
                    <span>đ/tháng</span>
                </div>
            </div>

            <div class="field">
                <label for="thanh_pho">Thành phố <span class="req">*</span></label>
                <select class="select" id="thanh_pho" name="thanh_pho" required>
                    <option value="">Chọn thành phố</option>
                    <option value="Đà Nẵng" <?php echo ($_POST["thanh_pho"] ?? "") == "Đà Nẵng" ? "selected" : ""; ?>>Đà Nẵng</option>
                </select>
            </div>

            <div class="field">
                <label for="quan_huyen">Quận / Huyện <span class="req">*</span></label>
                <select class="select" id="quan_huyen" name="quan_huyen" required>
                    <option value="">Chọn quận / huyện</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo e($d); ?>" <?php echo ($_POST["quan_huyen"] ?? "") == $d ? "selected" : ""; ?>>
                            <?php echo e($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="dia_chi">Địa chỉ <span class="req">*</span></label>
                <input class="input" type="text" id="dia_chi" name="dia_chi" maxlength="255" required
                       placeholder="Số nhà, tên đường"
                       value="<?php echo e($_POST["dia_chi"] ?? ""); ?>">
            </div>

            <div class="field span-2">
                <label for="mo_ta">Mô tả <span class="req">*</span></label>
                <textarea class="textarea" id="mo_ta" name="mo_ta" maxlength="1000" required
                          placeholder="Diện tích, nội thất, điện nước, giờ giấc, khoảng cách đến biển hoặc trường học..."><?php echo e($_POST["mo_ta"] ?? ""); ?></textarea>
                <span class="hint counter" id="demKyTu">0 / 1000 ký tự</span>
            </div>

            <div class="field span-2">
                <span class="label">Hình ảnh <span class="req">*</span></span>
                <div class="dropzone" id="dropzone">
                    <?php echo icon("upload", 28); ?>
                    <p style="margin:8px 0 2px"><strong>Kéo ảnh vào đây</strong> hoặc bấm để chọn</p>
                    <span class="hint">JPG hoặc PNG, tối đa 5MB</span>
                    <input type="file" id="hinh_anh" name="hinh_anh" accept=".jpg,.jpeg,.png" required
                           aria-label="Chọn hình ảnh">
                </div>
                <div class="preview" id="preview">
                    <img id="previewImg" alt="Ảnh xem trước">
                    <p id="previewName"></p>
                </div>
            </div>

            <div class="form-actions span-2">
                <button type="submit" class="btn btn-primary">
                    <?php echo icon("send", 18); ?> Đăng tin
                </button>
                <button type="reset" class="btn btn-outline" id="btnReset">Nhập lại</button>
            </div>
        </form>
    </section>

    <aside class="panel">
        <h2>Để tin được quan tâm</h2>
        <ul class="tips">
            <li>Dùng ảnh chụp thật, đủ sáng, thấy rõ phòng.</li>
            <li>Ghi đúng giá thuê hằng tháng để tránh bị hỏi lại.</li>
            <li>Mô tả rõ điện nước, giờ giấc và khoảng cách đến nơi quen thuộc.</li>
            <li>Địa chỉ càng cụ thể, người thuê càng dễ quyết định.</li>
        </ul>
    </aside>

</main>

<?php include "includes/footer.php"; ?>

<script>
(function () {
    var moTa = document.getElementById("mo_ta");
    var dem = document.getElementById("demKyTu");
    function capNhat() { dem.textContent = moTa.value.length + " / 1000 ký tự"; }
    moTa.addEventListener("input", capNhat);
    capNhat();

    var input = document.getElementById("hinh_anh");
    var zone = document.getElementById("dropzone");
    var preview = document.getElementById("preview");
    var img = document.getElementById("previewImg");
    var name = document.getElementById("previewName");

    function hienAnh() {
        var f = input.files && input.files[0];
        if (!f) { preview.style.display = "none"; return; }
        img.src = URL.createObjectURL(f);
        name.textContent = f.name + " (" + (f.size / 1048576).toFixed(1) + " MB)";
        preview.style.display = "block";
    }
    input.addEventListener("change", hienAnh);
    ["dragenter", "dragover"].forEach(function (ev) {
        zone.addEventListener(ev, function () { zone.classList.add("is-over"); });
    });
    ["dragleave", "drop"].forEach(function (ev) {
        zone.addEventListener(ev, function () { zone.classList.remove("is-over"); });
    });
    document.getElementById("btnReset").addEventListener("click", function () {
        setTimeout(function () { hienAnh(); capNhat(); }, 0);
    });
})();
</script>
</body>
</html>
