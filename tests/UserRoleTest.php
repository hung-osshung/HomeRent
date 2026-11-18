<?php

declare(strict_types=1);

/**
 * Vai trò của tài khoản (bảng vai_tro, nguoi_dung_vai_tro).
 */
final class UserRoleTest extends DbTestCase
{
    private function rolesOf(int $userId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT vt.ten_vai_tro
               FROM nguoi_dung_vai_tro ndvt JOIN vai_tro vt ON vt.ma_vai_tro = ndvt.ma_vai_tro
              WHERE ndvt.ma_nguoi_dung = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $roles = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'ten_vai_tro');
        $stmt->close();
        return $roles;
    }

    public function testFiveSystemRolesExist(): void
    {
        $result = $this->conn->query('SELECT ten_vai_tro FROM vai_tro');
        $roles = array_column($result->fetch_all(MYSQLI_ASSOC), 'ten_vai_tro');

        $this->assertEqualsCanonicalizing(['ADMIN', 'MANAGER', 'STAFF', 'LANDLORD', 'RENTER'], $roles);
    }

    /** register.php chỉ cho tự đăng ký ma_vai_tro IN (4, 5) => phải là LANDLORD và RENTER. */
    public function testSelfRegistrationRoleIdsAreLandlordAndRenter(): void
    {
        $result = $this->conn->query('SELECT ten_vai_tro FROM vai_tro WHERE ma_vai_tro IN (4, 5)');
        $roles = array_column($result->fetch_all(MYSQLI_ASSOC), 'ten_vai_tro');

        $this->assertEqualsCanonicalizing(['LANDLORD', 'RENTER'], $roles);
    }

    public function testNewUserHasExactlyOneRole(): void
    {
        $user = $this->createUser('LANDLORD');

        $this->assertSame(['LANDLORD'], $this->rolesOf($user));
    }

    public function testRoleAssignmentChangesAccessScope(): void
    {
        $user = $this->createUser('RENTER');
        $this->loginAs($user);
        $this->assertFalse(kiemTraQuyen('THEM_TIN'));
        $this->assertTrue(kiemTraQuyen('GUI_YEU_CAU'));

        $this->changeRole($user, 'LANDLORD');

        $this->assertSame(['LANDLORD'], $this->rolesOf($user));
        $this->assertTrue(kiemTraQuyen('THEM_TIN'));
        $this->assertFalse(kiemTraQuyen('GUI_YEU_CAU'));
    }

    public function testDemotionRemovesPreviousPrivileges(): void
    {
        $user = $this->createUser('ADMIN');
        $this->loginAs($user);
        $this->assertTrue(kiemTraQuyen('QUAN_LY_VAI_TRO'));

        $this->changeRole($user, 'RENTER');

        $this->assertFalse(kiemTraQuyen('QUAN_LY_VAI_TRO'));
    }

    public function testRoleChangeDoesNotAffectOtherUsers(): void
    {
        $a = $this->createUser('RENTER');
        $b = $this->createUser('RENTER');

        $this->changeRole($a, 'LANDLORD');

        $this->assertSame(['RENTER'], $this->rolesOf($b));
    }

    public function testCannotAssignNonExistentRole(): void
    {
        $user = $this->createUser('RENTER');

        $this->expectException(mysqli_sql_exception::class);

        $stmt = $this->conn->prepare('INSERT INTO nguoi_dung_vai_tro (ma_nguoi_dung, ma_vai_tro) VALUES (?, 9999)');
        $stmt->bind_param('i', $user);
        $stmt->execute();
    }

    /**
     * US02: không xóa được vai trò đang gán cho tài khoản.
     * Nếu test này FAIL nghĩa là bảng nguoi_dung_vai_tro thiếu khóa ngoại
     * ON DELETE RESTRICT tới vai_tro - cần bổ sung khóa ngoại trong CSDL.
     */
    public function testCannotDeleteRoleStillAssignedToUsers(): void
    {
        $this->createUser('RENTER');
        $roleId = $this->roleId('RENTER');

        $this->expectException(mysqli_sql_exception::class);

        $this->conn->query("DELETE FROM vai_tro WHERE ma_vai_tro = $roleId");
    }
}
