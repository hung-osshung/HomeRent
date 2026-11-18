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

    ?>

    <script>
        if (
            window.parent &&
            window.parent !== window &&
            typeof window.parent.location !== "undefined"
        ) {
            window.parent.location.href = "index.php";
        } else {
            window.location.href = "index.php";
        }
    </script>

    <?php

    exit();
}


/* =====================================
   XỬ LÝ ĐĂNG KÝ
===================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ho_ten = trim($_POST["ho_ten"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $so_dien_thoai = trim($_POST["so_dien_thoai"] ?? "");

    $ma_vai_tro = intval($_POST["ma_vai_tro"] ?? 0);

    $mat_khau = $_POST["mat_khau"] ?? "";
    $xac_nhan_mat_khau = $_POST["xac_nhan_mat_khau"] ?? "";


    /* =====================================
       KIỂM TRA MẬT KHẨU
    ====================================== */

    if (strlen($mat_khau) < 6) {
        $message = "Mật khẩu cần có ít nhất 6 ký tự!";
        $message_type = "error";
    }
    else if ($mat_khau != $xac_nhan_mat_khau) {

        $message = "Mật khẩu xác nhận không khớp!";
        $message_type = "error";

    }


    /* =====================================
       KIỂM TRA VAI TRÒ ĐƯỢC PHÉP ĐĂNG KÝ
    ====================================== */

    else if ($ma_vai_tro != 4 && $ma_vai_tro != 5) {

        $message = "Vui lòng chọn loại tài khoản hợp lệ!";
        $message_type = "error";

    }


    /* =====================================
       KIỂM TRA THÔNG TIN CƠ BẢN
    ====================================== */

    else if ($ho_ten == "" || $email == "" || $so_dien_thoai == "") {

        $message = "Vui lòng nhập đầy đủ thông tin!";
        $message_type = "error";

    }


    /* =====================================
       KIỂM TRA EMAIL HỢP LỆ
    ====================================== */

    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Email không hợp lệ!";
        $message_type = "error";

    }


    /* =====================================
       TIẾN HÀNH ĐĂNG KÝ
    ====================================== */

    else {

        /* =====================================
           KIỂM TRA VAI TRÒ CÓ TỒN TẠI
        ====================================== */

        $sql_role_check = "
            SELECT ma_vai_tro
            FROM vai_tro
            WHERE ma_vai_tro = ?
            AND ma_vai_tro IN (4, 5)
            LIMIT 1
        ";

        $stmt_role_check = $conn->prepare($sql_role_check);


        if (!$stmt_role_check) {

            $message = "Không thể kiểm tra vai trò!";
            $message_type = "error";

        } else {

            $stmt_role_check->bind_param(
                "i",
                $ma_vai_tro
            );

            $stmt_role_check->execute();

            $result_role_check = $stmt_role_check->get_result();


            if ($result_role_check->num_rows == 0) {

                $message = "Vai trò tài khoản không hợp lệ!";
                $message_type = "error";

            } else {

                /* =====================================
                   KIỂM TRA EMAIL
                ====================================== */

                $sql_check = "
                    SELECT ma_nguoi_dung
                    FROM nguoi_dung
                    WHERE email = ?
                    LIMIT 1
                ";

                $stmt_check = $conn->prepare($sql_check);


                if ($stmt_check) {

                    $stmt_check->bind_param(
                        "s",
                        $email
                    );

                    $stmt_check->execute();

                    $result_check = $stmt_check->get_result();


                    /* =====================================
                       EMAIL ĐÃ TỒN TẠI
                    ====================================== */

                    if ($result_check->num_rows > 0) {

                        $message = "Email này đã được sử dụng!";
                        $message_type = "error";

                    }


                    /* =====================================
                       EMAIL CHƯA TỒN TẠI
                    ====================================== */

                    else {

                        /* =====================================
                           MÃ HÓA MẬT KHẨU
                        ====================================== */

                        $mat_khau_hash = password_hash(
                            $mat_khau,
                            PASSWORD_DEFAULT
                        );


                        /*
                        ==================================================
                        BẮT ĐẦU GIAO DỊCH

                        Nếu tạo người dùng thành công nhưng gán vai trò
                        thất bại -> rollback để không tạo tài khoản
                        thiếu vai trò.
                        ==================================================
                        */

                        $conn->begin_transaction();


                        try {

                            /* =====================================
                               THÊM NGƯỜI DÙNG
                            ====================================== */

                            $sql_user = "
                                INSERT INTO nguoi_dung
                                (
                                    ho_ten,
                                    email,
                                    so_dien_thoai,
                                    mat_khau
                                )
                                VALUES (?, ?, ?, ?)
                            ";

                            $stmt_user = $conn->prepare($sql_user);


                            if (!$stmt_user) {
                                throw new Exception(
                                    "Không thể chuẩn bị truy vấn người dùng!"
                                );
                            }


                            $stmt_user->bind_param(
                                "ssss",
                                $ho_ten,
                                $email,
                                $so_dien_thoai,
                                $mat_khau_hash
                            );


                            if (!$stmt_user->execute()) {

                                $stmt_user->close();

                                throw new Exception(
                                    "Đăng ký tài khoản thất bại!"
                                );

                            }


                            $ma_nguoi_dung = $conn->insert_id;

                            $stmt_user->close();


                            /* =====================================
                               GÁN VAI TRÒ CHO NGƯỜI DÙNG
                            ====================================== */

                            $sql_role = "
                                INSERT INTO nguoi_dung_vai_tro
                                (
                                    ma_nguoi_dung,
                                    ma_vai_tro
                                )
                                VALUES (?, ?)
                            ";

                            $stmt_role = $conn->prepare($sql_role);


                            if (!$stmt_role) {

                                throw new Exception(
                                    "Không thể chuẩn bị dữ liệu vai trò!"
                                );

                            }


                            $stmt_role->bind_param(
                                "ii",
                                $ma_nguoi_dung,
                                $ma_vai_tro
                            );


                            if (!$stmt_role->execute()) {

                                $stmt_role->close();

                                throw new Exception(
                                    "Không thể tạo vai trò tài khoản!"
                                );

                            }


                            $stmt_role->close();


                            /* =====================================
                               HOÀN TẤT GIAO DỊCH
                            ====================================== */

                            $conn->commit();


                            ?>

                            <script>

                                alert(
                                    "Đăng ký tài khoản thành công!"
                                );


                                if (
                                    window.parent &&
                                    typeof window.parent.openAuth === "function"
                                ) {

                                    window.parent.openAuth(
                                        "login.php"
                                    );

                                } else {

                                    window.location.href =
                                        "login.php";

                                }

                            </script>

                            <?php

                            exit();

                        } catch (Exception $e) {

                            /*
                            ==========================================
                            CÓ LỖI -> HỦY TOÀN BỘ GIAO DỊCH
                            ==========================================
                            */

                            $conn->rollback();

                            $message = $e->getMessage();
                            $message_type = "error";

                        }

                    }


                    $stmt_check->close();

                } else {

                    $message =
                        "Không thể kiểm tra email!";

                    $message_type =
                        "error";

                }

            }


            $stmt_role_check->close();

        }

    }

}

?>


<?php
$page_title = "Đăng ký";
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

    <h1>Tạo tài khoản</h1>
    <p class="auth-sub">Chỉ mất vài phút.</p>

    <?php if ($message != ""): ?>
        <div class="alert alert-<?php echo $message_type == "success" ? "success" : "error"; ?>" role="alert">
            <?php echo e($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <div class="field">
            <span class="label">Bạn muốn làm gì?</span>
            <div class="choice-row">
                <label class="choice">
                    <input type="radio" name="ma_vai_tro" value="5" required
                        <?php echo ($_POST["ma_vai_tro"] ?? "") == 5 ? "checked" : ""; ?>>
                    <span class="choice-box">Thuê nhà<small>Tìm và xem tin</small></span>
                </label>
                <label class="choice">
                    <input type="radio" name="ma_vai_tro" value="4"
                        <?php echo ($_POST["ma_vai_tro"] ?? "") == 4 ? "checked" : ""; ?>>
                    <span class="choice-box">Cho thuê nhà<small>Đăng tin cho thuê</small></span>
                </label>
            </div>
        </div>

        <div class="two">
            <div class="field">
                <label for="ho_ten">Họ và tên</label>
                <input class="input" type="text" id="ho_ten" name="ho_ten" required autocomplete="name"
                       value="<?php echo e($_POST["ho_ten"] ?? ""); ?>">
            </div>
            <div class="field">
                <label for="so_dien_thoai">Số điện thoại</label>
                <input class="input" type="tel" id="so_dien_thoai" name="so_dien_thoai" required autocomplete="tel"
                       value="<?php echo e($_POST["so_dien_thoai"] ?? ""); ?>">
            </div>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input class="input" type="email" id="email" name="email" required autocomplete="email"
                   value="<?php echo e($_POST["email"] ?? ""); ?>">
        </div>

        <div class="two">
            <div class="field">
                <label for="mat_khau">Mật khẩu</label>
                <input class="input" type="password" id="mat_khau" name="mat_khau" required
                       minlength="6" autocomplete="new-password">
            </div>
            <div class="field">
                <label for="xac_nhan_mat_khau">Nhập lại mật khẩu</label>
                <input class="input" type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" required
                       minlength="6" autocomplete="new-password">
            </div>
        </div>
        <span class="hint" style="margin-top:-6px">Mật khẩu có ít nhất 6 ký tự.</span>

        <button type="submit" class="btn btn-primary btn-block">Tạo tài khoản</button>
    </form>

    <p class="auth-switch">
        Đã có tài khoản?
        <a href="login.php" onclick="return openLogin();">Đăng nhập</a>
    </p>
</main>

<script>
function openLogin() {
    if (window.parent && typeof window.parent.openAuth === "function") {
        window.parent.openAuth("login.php");
        return false;
    }
    return true;
}
</script>
</body>
</html>
