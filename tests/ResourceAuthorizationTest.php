<?php

declare(strict_types=1);

/**
 * Phân quyền mức dữ liệu (quyền sở hữu tin đăng).
 *
 * Trong code hiện tại, việc "là chủ tin" chỉ là so sánh trực tiếp bên trong trang
 * (chi_tiet_tin.php: $_SESSION['ma_nguoi_dung'] == $tin['ma_nguoi_dang']; dang_tin.php:
 * ma_nguoi_dang lấy từ session), chưa có hàm dùng chung nên chưa gọi trực tiếp được.
 * Các test dưới đây kiểm tra phần dữ liệu (CSDL) đảm bảo cô lập giữa các chủ cho thuê,
 * và test hàm laChuTin() sẽ tự bỏ qua (skipped) cho tới khi nhóm thêm hàm đó
 * (xem HUONG_DAN_SUA_KIEM_TRA_QUYEN.md).
 */
final class ResourceAuthorizationTest extends DbTestCase
{
    /** US08/US10: tin đăng được gắn đúng người đăng. */
    public function testListingIsOwnedByItsPoster(): void
    {
        $landlord = $this->createUser('LANDLORD');
        $listing = $this->createListing($landlord);

        $this->assertSame([$listing], $this->listingIdsOf($landlord));
    }

    /** US10: dữ liệu giữa các chủ cho thuê tách biệt. */
    public function testListingsAreIsolatedBetweenLandlords(): void
    {
        $a = $this->createUser('LANDLORD');
        $b = $this->createUser('LANDLORD');
        $a1 = $this->createListing($a);
        $a2 = $this->createListing($a);
        $b1 = $this->createListing($b);

        $this->assertSame([$a1, $a2], $this->listingIdsOf($a));
        $this->assertSame([$b1], $this->listingIdsOf($b));
    }

    /** Chức năng đăng tin chỉ dành cho vai trò có THEM_TIN (dang_tin.php). */
    public function testOnlyLandlordAndAdminMayPostListings(): void
    {
        foreach (['RENTER', 'STAFF', 'MANAGER'] as $role) {
            $this->loginAs($this->createUser($role));
            $this->assertFalse(kiemTraQuyen('THEM_TIN'), $role);
        }
        foreach (['LANDLORD', 'ADMIN'] as $role) {
            $this->loginAs($this->createUser($role));
            $this->assertTrue(kiemTraQuyen('THEM_TIN'), $role);
        }
    }

    /** Ràng buộc toàn vẹn: không có tin "mồ côi" (khóa ngoại ma_nguoi_dang). */
    public function testListingCannotReferenceNonExistentUser(): void
    {
        $this->expectException(mysqli_sql_exception::class);

        $this->createListing(99999999);
    }

    /** ON DELETE RESTRICT: không xóa được tài khoản đang có tin đăng. */
    public function testCannotDeleteUserWhoOwnsListings(): void
    {
        $landlord = $this->createUser('LANDLORD');
        $this->createListing($landlord);

        $this->expectException(mysqli_sql_exception::class);

        $this->conn->query("DELETE FROM nguoi_dung WHERE ma_nguoi_dung = $landlord");
    }

    /** TC03 - chạy khi đã có hàm laChuTin(). */
    public function testLandlordIsOwnerOfOwnListing(): void
    {
        if (!function_exists('laChuTin')) {
            $this->markTestSkipped('Chưa có hàm laChuTin() trong config/kiem_tra_quyen.php');
        }
        $landlord = $this->createUser('LANDLORD');
        $listing = $this->createListing($landlord);

        $this->assertTrue(laChuTin($listing, $landlord));
    }

    /** TC04 - chạy khi đã có hàm laChuTin(). */
    public function testLandlordIsNotOwnerOfOthersListing(): void
    {
        if (!function_exists('laChuTin')) {
            $this->markTestSkipped('Chưa có hàm laChuTin() trong config/kiem_tra_quyen.php');
        }
        $owner = $this->createUser('LANDLORD');
        $other = $this->createUser('LANDLORD');
        $listing = $this->createListing($owner);

        $this->assertFalse(laChuTin($listing, $other));
    }

    public function testLaChuTinIsFalseForNonExistentListing(): void
    {
        if (!function_exists('laChuTin')) {
            $this->markTestSkipped('Chưa có hàm laChuTin() trong config/kiem_tra_quyen.php');
        }

        $this->assertFalse(laChuTin(99999999, $this->createUser('LANDLORD')));
    }
}
