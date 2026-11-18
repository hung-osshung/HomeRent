<?php
session_start();
require_once "config/database.php";
require_once "includes/helpers.php";

/* ==================================================
   KIỂM TRA ĐĂNG NHẬP
================================================== */

if (!isset($_SESSION["ma_nguoi_dung"])) {
    header("Location: login.php");
    exit();
}

$message = "";
$message_type = "";


/* ==================================================
   XỬ LÝ GỬI YÊU CẦU HỖ TRỢ
================================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ma_nguoi_dung = $_SESSION["ma_nguoi_dung"];

    $loai_ho_tro = trim($_POST["loai_ho_tro"] ?? "");
    $tieu_de = trim($_POST["tieu_de"] ?? "");
    $noi_dung = trim($_POST["noi_dung"] ?? "");


    if (
        empty($loai_ho_tro) ||
        empty($tieu_de) ||
        empty($noi_dung)
    ) {

        $message = "Vui lòng nhập đầy đủ thông tin!";
        $message_type = "error";

    } else {

        $sql = "
            INSERT INTO yeu_cau_ho_tro
            (
                ma_nguoi_dung,
                loai_ho_tro,
                tieu_de,
                noi_dung
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "isss",
                $ma_nguoi_dung,
                $loai_ho_tro,
                $tieu_de,
                $noi_dung
            );

            if ($stmt->execute()) {

                $message = "Yêu cầu hỗ trợ đã được gửi thành công!";
                $message_type = "success";

            } else {

                $message = "Không thể gửi yêu cầu. Vui lòng thử lại!";
                $message_type = "error";
            }

            $stmt->close();

        } else {

            $message = "Không thể kết nối với cơ sở dữ liệu!";
            $message_type = "error";
        }
    }
}

?>

<?php
$page_title = "Trung tâm hỗ trợ";
include "includes/head.php";
?>
<body>
<?php include "includes/header.php"; ?>

<div class="page-head">
    <div class="container">
        <h1>Bạn cần chúng tôi hỗ trợ gì?</h1>
        <p>Gửi yêu cầu cho HomeRent hoặc xem câu trả lời cho những câu hỏi thường gặp.</p>
    </div>
</div>

<main id="noi-dung" class="container" style="padding-top:32px">

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $message_type == "success" ? "success" : "error"; ?>" role="alert">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <div class="two-col" style="padding-top:0; grid-template-columns: minmax(0,1fr) minmax(0,1fr);">

        <section class="panel">
            <h2>Gửi yêu cầu hỗ trợ</h2>
            <form method="POST" class="form-grid" style="grid-template-columns:1fr">
                <div class="field">
                    <label for="loai_ho_tro">Loại hỗ trợ <span class="req">*</span></label>
                    <select class="select" id="loai_ho_tro" name="loai_ho_tro" required>
                        <option value="">Chọn loại hỗ trợ</option>
                        <?php foreach (["Tài khoản", "Đăng tin", "Tìm nhà", "Thuê nhà", "Báo cáo tin đăng", "Khác"] as $loai): ?>
                            <option value="<?php echo e($loai); ?>"
                                <?php echo ($_POST["loai_ho_tro"] ?? "") == $loai && $message_type == "error" ? "selected" : ""; ?>>
                                <?php echo e($loai); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="tieu_de">Tiêu đề <span class="req">*</span></label>
                    <input class="input" type="text" id="tieu_de" name="tieu_de" maxlength="255" required
                           placeholder="Tóm tắt vấn đề của bạn"
                           value="<?php echo $message_type == "error" ? e($_POST["tieu_de"] ?? "") : ""; ?>">
                </div>
                <div class="field">
                    <label for="noi_dung">Nội dung <span class="req">*</span></label>
                    <textarea class="textarea" id="noi_dung" name="noi_dung" required
                              placeholder="Mô tả chi tiết để chúng tôi hỗ trợ nhanh hơn"><?php echo $message_type == "error" ? e($_POST["noi_dung"] ?? "") : ""; ?></textarea>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <?php echo icon("send", 18); ?> Gửi yêu cầu
                    </button>
                </div>
            </form>
        </section>

        <section class="panel faq">
            <h2>Câu hỏi thường gặp</h2>
            <details>
                <summary>Làm sao để tìm nhà?</summary>
                <p>Chọn <strong>Tìm nhà</strong> trên thanh menu, sau đó dùng từ khóa, quận/huyện và khoảng giá để tìm tin phù hợp.</p>
            </details>
            <details>
                <summary>Làm sao để đăng tin cho thuê?</summary>
                <p>Tài khoản người cho thuê có thể chọn <strong>Đăng tin</strong> trên thanh menu và nhập thông tin căn nhà cần cho thuê.</p>
            </details>
            <details>
                <summary>Tôi có thể chỉnh sửa tin đã đăng không?</summary>
                <p>Chức năng quản lý và chỉnh sửa tin đăng sẽ được HomeRent phát triển trong các phiên bản tiếp theo.</p>
            </details>
            <details>
                <summary>Làm sao báo cáo một tin đăng?</summary>
                <p>Gửi yêu cầu hỗ trợ với loại <strong>Báo cáo tin đăng</strong> và mô tả rõ vấn đề cần báo cáo.</p>
            </details>
            <details>
                <summary>Tôi quên thông tin đăng nhập thì sao?</summary>
                <p>Gửi yêu cầu hỗ trợ kèm thông tin cần thiết để HomeRent kiểm tra tài khoản của bạn.</p>
            </details>
        </section>
    </div>

    <div class="contact-row">
        <div class="contact-item">
            <?php echo icon("phone", 22); ?>
            <div><h3>Điện thoại</h3><p>1900 0000</p></div>
        </div>
        <div class="contact-item">
            <?php echo icon("mail", 22); ?>
            <div><h3>Email</h3><p>support@homerent.vn</p></div>
        </div>
        <div class="contact-item">
            <?php echo icon("clock", 22); ?>
            <div><h3>Giờ hỗ trợ</h3><p>08:00 - 22:00</p></div>
        </div>
        <div class="contact-item">
            <?php echo icon("shield", 22); ?>
            <div><h3>An toàn</h3><p>Hỗ trợ bảo vệ người dùng</p></div>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>
</body>
</html>
