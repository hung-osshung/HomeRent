<?php

declare(strict_types=1);

/**
 * Danh mục quyền, gán và thu hồi quyền của vai trò (bảng quyen, vai_tro_quyen).
 */
final class PermissionAuthorizationTest extends DbTestCase
{
    public function testPermissionCatalogMatchesSpecification(): void
    {
        $result = $this->conn->query('SELECT ten_quyen FROM quyen');
        $actual = array_column($result->fetch_all(MYSQLI_ASSOC), 'ten_quyen');

        $this->assertCount(21, $actual);
        $this->assertEqualsCanonicalizing(ExpectedMatrix::ALL, $actual);
    }

    /** TC06 */
    public function testGrantedPermissionAllowsAccess(): void
    {
        $renter = $this->createUser('RENTER');
        $this->loginAs($renter);
        $this->assertFalse(kiemTraQuyen('THEM_TIN'), 'tiền điều kiện: Renter chưa có THEM_TIN');

        $this->grantToRole('RENTER', 'THEM_TIN');

        $this->assertTrue(kiemTraQuyen('THEM_TIN'));
    }

    /** TC07 */
    public function testRevokedPermissionDeniesAccess(): void
    {
        $landlord = $this->createUser('LANDLORD');
        $this->loginAs($landlord);
        $this->assertTrue(kiemTraQuyen('SUA_TIN'), 'tiền điều kiện: Landlord có SUA_TIN');

        $this->revokeFromRole('LANDLORD', 'SUA_TIN');

        $this->assertFalse(kiemTraQuyen('SUA_TIN'));
    }

    public function testRevokeKeepsOtherPermissionsOfTheRole(): void
    {
        $this->loginAs($this->createUser('LANDLORD'));

        $this->revokeFromRole('LANDLORD', 'SUA_TIN');

        $this->assertTrue(kiemTraQuyen('THEM_TIN'));
        $this->assertTrue(kiemTraQuyen('XU_LY_YEU_CAU'));
    }

    public function testRevokeAffectsEveryUserOfTheRole(): void
    {
        $a = $this->createUser('LANDLORD');
        $b = $this->createUser('LANDLORD');

        $this->revokeFromRole('LANDLORD', 'AN_HIEN_TIN');

        foreach ([$a, $b] as $user) {
            $this->loginAs($user);
            $this->assertFalse(kiemTraQuyen('AN_HIEN_TIN'));
        }
    }

    public function testGrantDoesNotAffectOtherRoles(): void
    {
        $staff = $this->createUser('STAFF');
        $this->grantToRole('RENTER', 'THEM_TIN');

        $this->loginAs($staff);
        $this->assertFalse(kiemTraQuyen('THEM_TIN'));
    }

    public function testGrantingAnAlreadyGrantedPermissionIsHarmless(): void
    {
        $this->loginAs($this->createUser('RENTER'));

        $this->grantToRole('RENTER', 'GUI_YEU_CAU');

        $this->assertTrue(kiemTraQuyen('GUI_YEU_CAU'));
    }
}
