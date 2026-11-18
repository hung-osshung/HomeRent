<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Quyền theo vai trò (RBAC) - đối chiếu dữ liệu phân quyền thật với đề bài.
 */
final class RoleAuthorizationTest extends DbTestCase
{
    public static function matrixProvider(): iterable
    {
        return ExpectedMatrix::cases();
    }

    /** Ma trận 5 vai trò x 21 quyền, kiểm tra qua kiemTraQuyen() với tài khoản thật. */
    #[DataProvider('matrixProvider')]
    public function testRolePermissionMatrix(string $role, string $permission, bool $expected): void
    {
        $this->loginAs($this->createUser($role));

        $this->assertSame($expected, kiemTraQuyen($permission));
    }

    public function testNumberOfPermissionsPerRole(): void
    {
        $counts = ['ADMIN' => 21, 'MANAGER' => 5, 'STAFF' => 7, 'LANDLORD' => 8, 'RENTER' => 6];

        foreach ($counts as $role => $expected) {
            $stmt = $this->conn->prepare(
                'SELECT COUNT(*) AS n
                   FROM vai_tro_quyen vtq JOIN vai_tro vt ON vt.ma_vai_tro = vtq.ma_vai_tro
                  WHERE vt.ten_vai_tro = ?'
            );
            $stmt->bind_param('s', $role);
            $stmt->execute();
            $actual = (int) $stmt->get_result()->fetch_assoc()['n'];
            $stmt->close();

            $this->assertSame($expected, $actual, $role);
        }
    }

    /** TC01 */
    public function testAdminHasEveryPermission(): void
    {
        $this->loginAs($this->createUser('ADMIN'));

        foreach (ExpectedMatrix::ALL as $p) {
            $this->assertTrue(kiemTraQuyen($p), $p);
        }
    }

    /** TC02 */
    public function testRenterCannotManageAccounts(): void
    {
        $this->loginAs($this->createUser('RENTER'));

        foreach (['THEM_NGUOI_DUNG', 'SUA_NGUOI_DUNG', 'KHOA_MO_KHOA_NGUOI_DUNG', 'QUAN_LY_VAI_TRO'] as $p) {
            $this->assertFalse(kiemTraQuyen($p), $p);
        }
    }

    /** TC03 (mức chức năng; kiểm tra chủ tin xem ResourceAuthorizationTest) */
    public function testLandlordHasListingAndRequestPermissions(): void
    {
        $this->loginAs($this->createUser('LANDLORD'));

        foreach (['XEM_TIN', 'THEM_TIN', 'SUA_TIN', 'AN_HIEN_TIN', 'XEM_YEU_CAU', 'XU_LY_YEU_CAU'] as $p) {
            $this->assertTrue(kiemTraQuyen($p), $p);
        }
    }

    /** TC05 */
    public function testStaffCannotAccessAdminFunctions(): void
    {
        $this->loginAs($this->createUser('STAFF'));

        foreach (['QUAN_LY_VAI_TRO', 'GAN_VAI_TRO', 'THEM_NGUOI_DUNG', 'QUAN_LY_QUYEN', 'KHOA_MO_KHOA_NGUOI_DUNG'] as $p) {
            $this->assertFalse(kiemTraQuyen($p), $p);
        }
    }

    /** US06 */
    public function testManagerCanViewUsersButNotModifyThem(): void
    {
        $this->loginAs($this->createUser('MANAGER'));

        $this->assertTrue(kiemTraQuyen('XEM_NGUOI_DUNG'));
        foreach (['SUA_NGUOI_DUNG', 'KHOA_MO_KHOA_NGUOI_DUNG', 'GAN_VAI_TRO', 'QUAN_LY_QUYEN', 'XU_LY_YEU_CAU'] as $p) {
            $this->assertFalse(kiemTraQuyen($p), $p);
        }
    }

    /** US11 */
    public function testOnlyLandlordAndAdminMayProcessRentalRequests(): void
    {
        foreach (['MANAGER', 'STAFF', 'RENTER'] as $role) {
            $this->loginAs($this->createUser($role));
            $this->assertFalse(kiemTraQuyen('XU_LY_YEU_CAU'), $role);
        }
        $this->loginAs($this->createUser('LANDLORD'));
        $this->assertTrue(kiemTraQuyen('XU_LY_YEU_CAU'));
    }

    /** US12, US19 */
    public function testSupportTicketPermissions(): void
    {
        foreach (array_keys(ExpectedMatrix::BY_ROLE) as $role) {
            $this->loginAs($this->createUser($role));
            $this->assertTrue(kiemTraQuyen('GUI_YEU_CAU_HO_TRO'), "$role gửi phiếu");
            $this->assertTrue(kiemTraQuyen('XEM_YEU_CAU_HO_TRO_CUA_MINH'), "$role xem phiếu của mình");
        }
        foreach (['MANAGER', 'LANDLORD', 'RENTER'] as $role) {
            $this->loginAs($this->createUser($role));
            $this->assertFalse(kiemTraQuyen('XU_LY_YEU_CAU_HO_TRO'), $role);
        }
        $this->loginAs($this->createUser('STAFF'));
        $this->assertTrue(kiemTraQuyen('XU_LY_YEU_CAU_HO_TRO'));
    }
}
