<?php

/* Hàm dùng chung: escape HTML, icon SVG, thẻ tin đăng */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function icon($name, $size = 20)
{
    static $paths = [
        "home" => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>',
        "search" => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        "pin" => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        "user" => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        "phone" => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        "mail" => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        "clock" => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        "shield" => '<path d="M12 3 5 6v5c0 4.5 3 8.2 7 10 4-1.8 7-5.5 7-10V6l-7-3z"/><path d="m9 12 2 2 4-4"/>',
        "arrow-right" => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        "arrow-left" => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        "chev-left" => '<path d="m15 6-6 6 6 6"/>',
        "chev-right" => '<path d="m9 6 6 6-6 6"/>',
        "upload" => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
        "image" => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        "link" => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3A4 4 0 0 0 13 5.3l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 18.7l1-1"/>',
        "flag" => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>',
        "logout" => '<path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4"/><path d="m16 8 4 4-4 4"/><path d="M20 12H9"/>',
        "menu" => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        "close" => '<path d="M6 6l12 12M18 6 6 18"/>',
        "check" => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        "send" => '<path d="M21 3 10 14"/><path d="m21 3-7 18-4-7-7-4z"/>',
    ];

    if (!isset($paths[$name])) {
        return "";
    }

    return '<svg class="icon" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        . ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true">' . $paths[$name] . '</svg>';
}

function format_gia($gia)
{
    return number_format((float) $gia, 0, ",", ".");
}

function render_listing_card($row)
{
    ?>
    <article class="listing">
        <div class="listing-media">
            <?php if (!empty($row["hinh_dai_dien"])): ?>
                <img src="<?php echo e($row["hinh_dai_dien"]); ?>"
                     alt="<?php echo e($row["tieu_de"]); ?>" loading="lazy">
            <?php else: ?>
                <?php echo icon("image", 36); ?>
            <?php endif; ?>
        </div>
        <div class="listing-body">
            <p class="listing-price">
                <?php echo format_gia($row["gia_thue"]); ?> đ<span>/tháng</span>
            </p>
            <h3 class="listing-title">
                <a href="chi_tiet_tin.php?ma_tin=<?php echo (int) $row["ma_tin"]; ?>">
                    <?php echo e($row["tieu_de"]); ?>
                </a>
            </h3>
            <p class="listing-meta">
                <?php echo icon("pin", 16); ?>
                <?php echo e($row["quan_huyen"]); ?>, <?php echo e($row["thanh_pho"]); ?>
            </p>
            <p class="listing-address"><?php echo e($row["dia_chi"]); ?></p>
        </div>
    </article>
    <?php
}
