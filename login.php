<?php

session_start();

require_once "config/database.php";
require_once "includes/helpers.php";

$message = "";
$message_type = "";


/* =====================================
   NẾU ĐÃ ĐĂNG NHẬP
===================================== */

if (isset($_SESSION["ma_nguoi_dung"])) {

    /*
     * Vì login.php đang nằm trong iframe,
     * chuyển trang cha về trang chủ.
     */
    ?>

<script>
    if (window.top !== window) { window.top.location.reload(); } else { window.location.href = "index.php"; }
</script>

<?php

exit();

}

/* =====================================
XỬ LÝ ĐĂNG NHẬP
===================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$email = trim($_POST["email"] ?? "");
$mat_khau = $_POST["mat_khau"] ?? "";


/* =====================================
   KIỂM TRA DỮ LIỆU
===================================== */

if (empty($email) || empty($mat_khau)) {

    $message = "Vui lòng nhập đầy đủ email và mật khẩu!";
    $message_type = "error";

} else {


    /* =====================================
       TÌM NGƯỜI DÙNG
    ===================================== */

    $sql = "

        SELECT
            nguoi_dung.ma_nguoi_dung,
            nguoi_dung.ho_ten,
            nguoi_dung.email,
            nguoi_dung.mat_khau,
            nguoi_dung.trang_thai,

            vai_tro.ma_vai_tro,
            vai_tro.ten_vai_tro

        FROM nguoi_dung

        LEFT JOIN nguoi_dung_vai_tro
        ON nguoi_dung.ma_nguoi_dung
        = nguoi_dung_vai_tro.ma_nguoi_dung

        LEFT JOIN vai_tro
        ON nguoi_dung_vai_tro.ma_vai_tro
        = vai_tro.ma_vai_tro

        WHERE nguoi_dung.email = ?

        LIMIT 1

    ";


    $stmt = $conn->prepare($sql);


    if ($stmt) {

        $stmt->bind_param(
            "s",
            $email
        );


        if ($stmt->execute()) {

            $result = $stmt->get_result();


            /* =====================================
               KIỂM TRA TÀI KHOẢN
            ===================================== */

            if ($result->num_rows == 1) {

                $user = $result->fetch_assoc();


                /* =====================================
                   KIỂM TRA TRẠNG THÁI
                ===================================== */

                if ($user["trang_thai"] != "HOAT_DONG") {

                    $message =
                        "Tài khoản hiện không hoạt động!";

                    $message_type = "error";

                }


                /* =====================================
                   KIỂM TRA MẬT KHẨU
                ===================================== */

                else if (
                    password_verify(
                        $mat_khau,
                        $user["mat_khau"]
                    )
                ) {


                    /* =====================================
                       TẠO SESSION MỚI
                    ===================================== */

                    session_regenerate_id(true);


                    /* =====================================
                       LƯU THÔNG TIN NGƯỜI DÙNG VÀO SESSION
                    ===================================== */

                    $_SESSION["ma_nguoi_dung"] =
                        $user["ma_nguoi_dung"];

                    $_SESSION["ho_ten"] =
                        $user["ho_ten"];

                    $_SESSION["email"] =
                        $user["email"];

                    $_SESSION["ma_vai_tro"] =
                        $user["ma_vai_tro"];

                    $_SESSION["vai_tro"] =
                        $user["ten_vai_tro"];


                    /*
                     * Không dùng header Location ở đây
                     * vì trang đang nằm trong iframe.
                     *
                     * Chuyển trang cha về index.php.
                     */

                    ?>

                    <script>

                        if (window.top !== window) { window.top.location.reload(); } else { window.location.href = "index.php"; }

                    </script>

                    <?php

                    exit();

                }


                else {

                    $message =
                        "Email hoặc mật khẩu không đúng!";

                    $message_type = "error";

                }


            }

            else {

                $message =
                    "Email hoặc mật khẩu không đúng!";

                $message_type = "error";

            }

        }

        else {

            $message =
                "Không thể thực hiện truy vấn!";

            $message_type = "error";

        }


        $stmt->close();

    }

    else {

        $message =
            "Không thể chuẩn bị truy vấn!";

        $message_type = "error";

    }

}

}

?>

<?php
$page_title = "Đăng nhập";
$extra_css = ["css/auth.css"];
include "includes/head.php";
?>
<body class="auth-body">
<script>if (window.top !== window) { document.documentElement.classList.add("in-frame"); }</script>

<main class="auth-card">
    <a class="brand" href="index.php" target="_top">
        <svg viewBox="0 0 32 32" aria-hidden="true">
            <rect width="32" height="32" rx="9" fill="#117A8B"/>
            <path d="M8 15 16 8l8 7v7H8z" fill="#fff"/>
            <path d="M5 26c3-2.2 5 1.8 8 0s5 1.8 8 0 4-1 6-1" fill="none" stroke="#F4A83A" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>HomeRent</span>
    </a>

    <h1>Đăng nhập</h1>
    <p class="auth-sub">Đăng nhập để xem chi tiết tin và liên hệ chủ nhà.</p>

    <?php if ($message != ""): ?>
        <div class="alert alert-error" role="alert"><?php echo e($message); ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <div class="field">
            <label for="email">Email</label>
            <input class="input" type="email" id="email" name="email" required autocomplete="email"
                   placeholder="ten@email.com"
                   value="<?php echo e($_POST["email"] ?? ""); ?>">
        </div>
        <div class="field">
            <label for="mat_khau">Mật khẩu</label>
            <input class="input" type="password" id="mat_khau" name="mat_khau" required
                   autocomplete="current-password" placeholder="Nhập mật khẩu">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
    </form>

    <p class="auth-switch">
        Chưa có tài khoản?
        <a href="register.php" onclick="return openRegister();">Tạo tài khoản</a>
    </p>
</main>

<script>
function openRegister() {
    if (window.parent && typeof window.parent.openAuth === "function") {
        window.parent.openAuth("register.php");
        return false;
    }
    return true;
}
</script>
</body>
</html>
