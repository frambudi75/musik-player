<?php
/**
 * Generates PWA Icons (icon-192.png, icon-512.png, icon-maskable.png)
 */
$iconDir = __DIR__ . '/assets/icons';
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0777, true);
}

function createIcon($size, $filename, $isMaskable = false) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // Background
    if ($isMaskable) {
        $bg = imagecolorallocate($img, 8, 9, 13);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
    } else {
        $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $trans);
        $bg = imagecolorallocate($img, 15, 18, 26);
        $radius = (int)($size * 0.22);
        // Rounded rectangle background
        imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size * 0.92), (int)($size * 0.92), $bg);
    }

    // Inner electric blue gradient circle
    $blue = imagecolorallocate($img, 59, 130, 246);
    $darkBlue = imagecolorallocate($img, 30, 58, 138);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size * 0.65), (int)($size * 0.65), $darkBlue);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size * 0.55), (int)($size * 0.55), $blue);

    // Center vinyl center point
    $center = imagecolorallocate($img, 8, 9, 13);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size * 0.16), (int)($size * 0.16), $center);

    $white = imagecolorallocate($img, 243, 244, 246);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size * 0.06), (int)($size * 0.06), $white);

    imagepng($img, $filename);
    imagedestroy($img);
}

if (function_exists('imagecreatetruecolor')) {
    createIcon(192, $iconDir . '/icon-192.png');
    createIcon(512, $iconDir . '/icon-512.png');
    createIcon(512, $iconDir . '/icon-maskable.png', true);
    echo "PWA Icons generated successfully!\n";
} else {
    echo "GD library not available, fallback to SVG icons.\n";
}
