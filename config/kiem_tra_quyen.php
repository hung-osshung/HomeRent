<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| KẾT NỐI DATABASE
|--------------------------------------------------------------------------
| kiem_tra_quyen.php nằm trong thư mục config
| nên database.php nằm cùng thư mục.
*/

require_once __DIR__ . "/database.php";


/*
|--------------------------------------------------------------------------
| KIỂM TRA ĐÃ ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/
function daDangNhap()
{
    return isset($_SESSION["ma_nguoi_dung"]);
}


/*
|--------------------------------------------------------------------------
| KIỂM TRA QUYỀN
|--------------------------------------------------------------------------
| Ví dụ:
| kiemTraQuyen("THEM_TIN")
|
| Nếu người dùng có quyền -> true
| Nếu không có quyền -> false
|--------------------------------------------------------------------------
*/
function kiemTraQuyen($ten_quyen)
{
    global $conn;

    if (!isset($_SESSION["ma_nguoi_dung"])) {
        return false;
    }

    $ma_nguoi_dung = (int) $_SESSION["ma_nguoi_dung"];

    // Đã cập nhật JOIN chuẩn qua bảng trung gian nguoi_dung_vai_tro
        $sql = "
        SELECT quyen.ma_quyen
        FROM nguoi_dung_vai_tro
        INNER JOIN nguoi_dung
            ON nguoi_dung_vai_tro.ma_nguoi_dung = nguoi_dung.ma_nguoi_dung
        INNER JOIN vai_tro_quyen
            ON nguoi_dung_vai_tro.ma_vai_tro = vai_tro_quyen.ma_vai_tro
        INNER JOIN quyen
            ON vai_tro_quyen.ma_quyen = quyen.ma_quyen
        WHERE nguoi_dung_vai_tro.ma_nguoi_dung = ?
        AND nguoi_dung.trang_thai = 'HOAT_DONG'
        AND quyen.ten_quyen = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "is",
        $ma_nguoi_dung,
        $ten_quyen
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();

    $co_quyen = ($result->num_rows > 0);

    $stmt->close();

    return $co_quyen;
}


/*
|--------------------------------------------------------------------------
| YÊU CẦU PHẢI CÓ QUYỀN
|--------------------------------------------------------------------------
*/
function yeuCauQuyen($ten_quyen)
{
    if (!isset($_SESSION["ma_nguoi_dung"])) {
        header("Location: login.php");
        exit();
    }

    if (!kiemTraQuyen($ten_quyen)) {
        header("Location: index.php");
        exit();
    }
}

?>