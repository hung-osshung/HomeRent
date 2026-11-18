<?php
/* Dùng: $page_title = "..."; include "includes/head.php"; rồi tự mở <body> */
$page_title = $page_title ?? "HomeRent";
$extra_css = $extra_css ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - HomeRent</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/homerent.css">
    <?php foreach ($extra_css as $css): ?>
        <link rel="stylesheet" href="<?php echo e($css); ?>">
    <?php endforeach; ?>
</head>
