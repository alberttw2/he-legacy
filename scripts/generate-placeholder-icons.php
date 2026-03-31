<?php
/**
 * Creates minimal placeholder images so the game doesn't show broken images.
 *
 * Usage: php scripts/generate-placeholder-icons.php
 *
 * Generates:
 *   - images/unsub.jpg        (100x100 grey placeholder)
 *   - images/profile/default.jpg (100x100 dark placeholder for profiles)
 */

$basePath = dirname(__DIR__) . '/';

$placeholders = [
    $basePath . 'images/unsub.jpg' => [100, 100, [128, 128, 128]],
    $basePath . 'images/profile/default.jpg' => [100, 100, [64, 64, 64]],
];

foreach ($placeholders as $path => $spec) {
    [$width, $height, $rgb] = $spec;

    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    imagefill($img, 0, 0, $color);

    // Add a subtle "?" text in the center for profile placeholders
    $textColor = imagecolorallocate($img, 200, 200, 200);
    imagestring($img, 5, (int)($width / 2 - 4), (int)($height / 2 - 8), '?', $textColor);

    imagejpeg($img, $path, 85);
    imagedestroy($img);

    echo "Created: $path\n";
}

echo "Done. Directories images/icons, images/profile, and iconset/icons are ready.\n";
echo "Note: famfamfam icons (images/icons/) must be obtained separately.\n";
