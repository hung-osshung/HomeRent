<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Kiểm tra truy cập trái phép - gọi trực tiếp daDangNhap(), kiemTraQuyen(), yeuCauQuyen()
 * trong config/kiem_tra_quyen.php.
 */
final class AccessControlTest extends DbTestCase
{
    public function testDaDangNhapIsFalseWithoutSession(): void
    {
        $this->assertFalse(daDangNhap());
    }

    public function testDaDangNhapIsTrueWithSession(): void
    {
        $this->loginAs($this->createUser('RENTER'));

        $this->assertTrue(daDangNhap());
    }

    public static function sensitivePermissions(): iterable
    {
        foreach (['QUAN_LY_VAI_TRO', 'THEM_NGUOI_DUNG', 'GAN_VAI_TRO', 'XEM_TIN', 'THEM_TIN', 'GUI_YEU_CAU'] as $p) {
            yield $p => [$p];
        }
    }

    #[DataProvider('sensitivePermissions')]
    public function testUnauthenticatedUserHasNoPermission(string $permission): void
    {
        $this->loginAs(null);

        $this->assertFalse(kiemTraQuyen($permission));
    }

    /** TC01 */
    public function testAdminCanManageAccountsAndRoles(): void
    {
        $this->loginAs($this->createUser('ADMIN'));

        foreach (['THEM_NGUOI_DUNG', 'SUA_NGUOI_DUNG', 'KHOA_MO_KHOA_NGUOI_DUNG', 'QUAN_LY_VAI_TRO', 'GAN_VAI_TRO'] as $p) {
            $this->assertTrue(kiemTraQuyen($p), $p);
        }
    }

    /** TC02 */
    public function testRenterIsDeniedAccountManagement(): void
    {
        $this->loginAs($this->createUser('RENTER'));

        foreach (['THEM_NGUOI_DUNG', 'SUA_NGUOI_DUNG', 'KHOA_MO_KHOA_NGUOI_DUNG', 'QUAN_LY_VAI_TRO'] as $p) {
            $this->assertFalse(kiemTraQuyen($p), $p);
        }
    }

    public function testForgedSessionRoleDoesNotGrantAccess(): void
    {
        // Kẻ tấn công là RENTER nhưng sửa session thành ADMIN: quyền vẫn lấy từ CSDL.
        $this->loginAs($this->createUser('RENTER'));
        $_SESSION['vai_tro'] = 'ADMIN';

        $this->assertFalse(kiemTraQuyen('QUAN_LY_VAI_TRO'));
    }

    public function testNonExistentUserIdHasNoPermission(): void
    {
        $this->loginAs(99999999);

        $this->assertFalse(kiemTraQuyen('XEM_TIN'));
    }

    public function testUnknownPermissionIsDeniedForEveryone(): void
    {
        $this->loginAs($this->createUser('ADMIN'));

        $this->assertFalse(kiemTraQuyen('QUYEN_KHONG_TON_TAI'));
    }

    public function testYeuCauQuyenReturnsNormallyWhenUserHasPermission(): void
    {
        // Nhánh được phép: không redirect, không exit => hàm trả về bình thường.
        $this->loginAs($this->createUser('RENTER'));

        yeuCauQuyen('XEM_TIN');

        $this->addToAssertionCount(1);
    }

    /**
     * Bị khóa tài khoản sau khi đã đăng nhập thì phải mất quyền (US17).
     * Test này sẽ FAIL cho tới khi kiemTraQuyen() kiểm tra nguoi_dung.trang_thai
     * (xem file HUONG_DAN_SUA_KIEM_TRA_QUYEN.md).
     */
    public function testLockedAccountHasNoPermission(): void
    {
        $this->loginAs($this->createUser('ADMIN', 'BI_KHOA'));

        $this->assertFalse(kiemTraQuyen('QUAN_LY_VAI_TRO'));
    }

    // Nhánh bị từ chối của yeuCauQuyen() gọi header() + exit() nên PHPUnit không kiểm tra
    // tự động được. Kiểm thử THỦ CÔNG trên trình duyệt:
    //   - Chưa đăng nhập, mở dang_tin.php        -> chuyển về login.php
    //   - Đăng nhập RENTER, mở dang_tin.php      -> chuyển về index.php
}
