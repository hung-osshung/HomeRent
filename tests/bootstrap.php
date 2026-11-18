<?php

declare(strict_types=1);

// Không cho PHP gửi cookie/header khi session_start() chạy trong môi trường CLI.
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.cache_limiter', '');

// Nạp code thật của dự án. File này tự require config/database.php (kết nối MySQL).
// $conn được tạo bên trong closure nên phải đưa ra phạm vi toàn cục,
// vì kiemTraQuyen() dùng "global $conn".
(static function (): void {
    require_once __DIR__ . '/../config/kiem_tra_quyen.php';

    // Test chạy trên DB RIÊNG (mặc định "homerent_test", tạo bằng homerent_test.sql)
    // để không làm ảnh hưởng dữ liệu của website. Đổi bằng biến môi trường nếu cần:
    //   set HOMERENT_TEST_DB=ten_db_khac
    $testDb = getenv('HOMERENT_TEST_DB') ?: 'homerent_test';
    $conn->select_db($testDb);

    $GLOBALS['conn'] = $conn;
})();

require_once __DIR__ . '/ExpectedMatrix.php';
require_once __DIR__ . '/DbTestCase.php';
