<?php

declare(strict_types=1);

/**
 * Kết quả KỲ VỌNG theo đề bài (script SQL mục 12-15 + user story).
 * Viết tách khỏi CSDL để test đối chiếu hệ thống thật với đề.
 */
final class ExpectedMatrix
{
    public const ALL = [
        'XEM_NGUOI_DUNG', 'THEM_NGUOI_DUNG', 'SUA_NGUOI_DUNG', 'KHOA_MO_KHOA_NGUOI_DUNG',
        'XEM_VAI_TRO', 'QUAN_LY_VAI_TRO', 'XEM_TIN', 'THEM_TIN', 'SUA_TIN', 'AN_HIEN_TIN',
        'GUI_YEU_CAU', 'XEM_YEU_CAU', 'XU_LY_YEU_CAU', 'XEM_QUYEN', 'QUAN_LY_QUYEN',
        'GAN_VAI_TRO', 'HUY_YEU_CAU', 'GUI_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO',
        'XU_LY_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO_CUA_MINH',
    ];

    public const BY_ROLE = [
        'ADMIN'    => self::ALL,
        'MANAGER'  => ['XEM_NGUOI_DUNG', 'XEM_TIN', 'XEM_YEU_CAU',
                       'GUI_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO_CUA_MINH'],
        'STAFF'    => ['XEM_NGUOI_DUNG', 'XEM_TIN', 'XEM_YEU_CAU',
                       'GUI_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO_CUA_MINH',
                       'XEM_YEU_CAU_HO_TRO', 'XU_LY_YEU_CAU_HO_TRO'],
        'LANDLORD' => ['XEM_TIN', 'THEM_TIN', 'SUA_TIN', 'AN_HIEN_TIN', 'XEM_YEU_CAU', 'XU_LY_YEU_CAU',
                       'GUI_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO_CUA_MINH'],
        'RENTER'   => ['XEM_TIN', 'GUI_YEU_CAU', 'XEM_YEU_CAU', 'HUY_YEU_CAU',
                       'GUI_YEU_CAU_HO_TRO', 'XEM_YEU_CAU_HO_TRO_CUA_MINH'],
    ];

    /** Mỗi dòng: [vai trò, quyền, có quyền hay không] => 5 x 21 = 105 ca. */
    public static function cases(): iterable
    {
        foreach (self::BY_ROLE as $role => $granted) {
            foreach (self::ALL as $permission) {
                yield "$role / $permission" => [$role, $permission, in_array($permission, $granted, true)];
            }
        }
    }
}
