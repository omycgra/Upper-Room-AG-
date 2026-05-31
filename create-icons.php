<?php
// Original logo path
$original = __DIR__ . '/public/assets/img/logo.png';
 
// Sizes we need for web manifest
$sizes = [16, 32, 48, 64, 128, 192, 256, 512];

// Load original image once
$image = imagecreatefrompng($original);
if (!$image) {
    die("Could not load original logo!");
}

// Get original dimensions
$originalWidth = imagesx($image);
$originalHeight = imagesy($image);

// Create all PNG sizes first
foreach ($sizes as $size) {
    // Create a square canvas
    $newImage = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
    imagefilledrectangle($newImage, 0, 0, $size, $size, $transparent);

    // Calculate scaling to fit while maintaining aspect ratio
    $scale = min($size / $originalWidth, $size / $originalHeight);
    $newWidth = $originalWidth * $scale;
    $newHeight = $originalHeight * $scale;
    $x = ($size - $newWidth) / 2;
    $y = ($size - $newHeight) / 2;

    // Resample and copy
    imagecopyresampled(
        $newImage,
        $image,
        $x, $y,
        0, 0,
        $newWidth, $newHeight,
        $originalWidth, $originalHeight
    );

    // Save the resized image
    imagepng($newImage, __DIR__ . "/icon-{$size}.png");
    imagedestroy($newImage);
}

// Copy the church-logo.ico to the main directory if available
if (file_exists(__DIR__ . '/installer/assets/church-logo.ico')) {
    copy(__DIR__ . '/installer/assets/church-logo.ico', __DIR__ . '/logo.ico');
    copy(__DIR__ . '/installer/assets/church-logo.ico', __DIR__ . '/public/assets/img/logo.ico');
}

imagedestroy($image);

echo "Icons created successfully!";
?>