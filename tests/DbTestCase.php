<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Lớp nền cho test chạy trên MySQL thật (DB "homerent").
 * Mỗi test chạy trong một transaction và ROLLBACK ở cuối => không để rác trong DB.
 * Yêu cầu các bảng dùng InnoDB (đúng như script SQL của đề).
 */
abstract class DbTestCase extends TestCase
{
    protected mysqli $conn;

    protected function setUp(): void
    {
        global $conn;
        $this->conn = $conn;
        $this->conn->begin_transaction();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->conn->rollback();
        $_SESSION = [];
    }

    /** Giả lập đăng nhập: kiemTraQuyen() đọc $_SESSION['ma_nguoi_dung']. */
    protected function loginAs(?int $userId): void
    {
        $_SESSION = [];
        if ($userId !== null) {
            $_SESSION['ma_nguoi_dung'] = $userId;
        }
    }

    protected function roleId(string $role): int
    {
        $stmt = $this->conn->prepare('SELECT ma_vai_tro FROM vai_tro WHERE ten_vai_tro = ?');
        $stmt->bind_param('s', $role);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row === null) {
            $this->fail("Không có vai trò $role trong bảng vai_tro (đã import dữ liệu mẫu chưa?)");
        }
        return (int) $row['ma_vai_tro'];
    }

    /** Tạo tài khoản + gán vai trò qua nguoi_dung_vai_tro (giống register.php). */
    protected function createUser(string $role, string $status = 'HOAT_DONG'): int
    {
        $email = uniqid('test_', true) . '@test.local';
        $name = "Test $role";
        $phone = '0900000000';
        $hash = password_hash('Test@123', PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            'INSERT INTO nguoi_dung (ho_ten, email, so_dien_thoai, mat_khau, trang_thai) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $name, $email, $phone, $hash, $status);
        $stmt->execute();
        $userId = (int) $this->conn->insert_id;
        $stmt->close();

        $roleId = $this->roleId($role);
        $stmt = $this->conn->prepare('INSERT INTO nguoi_dung_vai_tro (ma_nguoi_dung, ma_vai_tro) VALUES (?, ?)');
        $stmt->bind_param('ii', $userId, $roleId);
        $stmt->execute();
        $stmt->close();

        return $userId;
    }

    /** Đổi vai trò chính của tài khoản (thay bản ghi trong nguoi_dung_vai_tro). */
    protected function changeRole(int $userId, string $newRole): void
    {
        $roleId = $this->roleId($newRole);
        $stmt = $this->conn->prepare('UPDATE nguoi_dung_vai_tro SET ma_vai_tro = ? WHERE ma_nguoi_dung = ?');
        $stmt->bind_param('ii', $roleId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /** Cấp quyền cho vai trò (vai_tro_quyen). */
    protected function grantToRole(string $role, string $permission): void
    {
        $stmt = $this->conn->prepare(
            'INSERT IGNORE INTO vai_tro_quyen (ma_vai_tro, ma_quyen)
             SELECT vt.ma_vai_tro, q.ma_quyen FROM vai_tro vt, quyen q
              WHERE vt.ten_vai_tro = ? AND q.ten_quyen = ?'
        );
        $stmt->bind_param('ss', $role, $permission);
        $stmt->execute();
        $stmt->close();
    }

    /** Thu hồi quyền của vai trò. */
    protected function revokeFromRole(string $role, string $permission): void
    {
        $stmt = $this->conn->prepare(
            'DELETE vtq FROM vai_tro_quyen vtq
               JOIN vai_tro vt ON vt.ma_vai_tro = vtq.ma_vai_tro
               JOIN quyen q ON q.ma_quyen = vtq.ma_quyen
              WHERE vt.ten_vai_tro = ? AND q.ten_quyen = ?'
        );
        $stmt->bind_param('ss', $role, $permission);
        $stmt->execute();
        $stmt->close();
    }

    protected function createListing(int $ownerId): int
    {
        $title = 'Phòng trọ test';
        $desc = 'Mô tả';
        $addr = '1 Đường Test';
        $city = 'Nha Trang';
        $district = 'Test';
        $price = 3000000;

        $stmt = $this->conn->prepare(
            'INSERT INTO tin_cho_thue (tieu_de, mo_ta, dia_chi, thanh_pho, quan_huyen, gia_thue, ma_nguoi_dang)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssdi', $title, $desc, $addr, $city, $district, $price, $ownerId);
        $stmt->execute();
        $id = (int) $this->conn->insert_id;
        $stmt->close();
        return $id;
    }

    /** @return int[] */
    protected function listingIdsOf(int $ownerId): array
    {
        $stmt = $this->conn->prepare('SELECT ma_tin FROM tin_cho_thue WHERE ma_nguoi_dang = ? ORDER BY ma_tin');
        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $ids = array_map(static fn ($r) => (int) $r['ma_tin'], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        $stmt->close();
        return $ids;
    }
}
