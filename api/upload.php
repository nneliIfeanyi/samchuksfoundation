<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
request_method('POST');
require_admin(true);

if (!extension_loaded('gd') || !function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
    json_response(['error' => 'Image resizing is unavailable on the server. Enable GD with WebP support.'], 503);
}

if (!isset($_FILES['images'])) {
    json_response(['error' => 'Select one or more images to upload.'], 422);
}

$files = $_FILES['images'];
$names = is_array($files['name']) ? $files['name'] : [$files['name']];
$tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
$errors = is_array($files['error']) ? $files['error'] : [$files['error']];
$sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
if (count($names) > MAX_EVENT_IMAGES) {
    json_response(['error' => 'Upload no more than ' . MAX_EVENT_IMAGES . ' images at a time.'], 422);
}

$projectRoot = basename(__DIR__) === 'api' ? dirname(__DIR__) : __DIR__;
$uploadDirectory = $projectRoot . '/assets/uploads/events';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    json_response(['error' => 'Unable to prepare the image upload directory.'], 500);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$canvasWidth = 1600;
$canvasHeight = 1200;
$maxInputPixels = 20000000;
$finfo = new finfo(FILEINFO_MIME_TYPE);
$uploaded = [];
foreach ($names as $index => $originalName) {
    if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_response(['error' => 'One or more images could not be uploaded.'], 422);
    }
    if (($sizes[$index] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file($tmpNames[$index])) {
        json_response(['error' => 'Each image must be a valid upload no larger than 5 MB.'], 422);
    }

    $mime = $finfo->file($tmpNames[$index]);
    $imageInfo = getimagesize($tmpNames[$index]);
    if (!in_array($mime, $allowedTypes, true) || $imageInfo === false ||
        $imageInfo[0] > 10000 || $imageInfo[1] > 10000 ||
        $imageInfo[0] * $imageInfo[1] > $maxInputPixels) {
        json_response(['error' => 'Images must be JPEG, PNG, or WebP, with dimensions no larger than 10,000 pixels per side or 20 megapixels total.'], 422);
    }

    $imageData = file_get_contents($tmpNames[$index]);
    if ($imageData === false) {
        json_response(['error' => 'An uploaded image could not be read.'], 500);
    }
    $source = imagecreatefromstring($imageData);
    unset($imageData);
    if ($source === false) {
        json_response(['error' => 'An uploaded image could not be decoded.'], 422);
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $canvasAspect = $canvasWidth / $canvasHeight;
    $sourceAspect = $sourceWidth / $sourceHeight;
    $sourceX = 0;
    $sourceY = 0;
    $cropWidth = $sourceWidth;
    $cropHeight = $sourceHeight;
    if ($sourceAspect > $canvasAspect) {
        $cropWidth = max(1, (int)round($sourceHeight * $canvasAspect));
        $sourceX = intdiv($sourceWidth - $cropWidth, 2);
    } else {
        $cropHeight = max(1, (int)round($sourceWidth / $canvasAspect));
        $sourceY = intdiv($sourceHeight - $cropHeight, 2);
    }

    $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);
    if ($canvas === false) {
        imagedestroy($source);
        json_response(['error' => 'The server could not prepare the resized image.'], 500);
    }

    $background = imagecolorallocate($canvas, 255, 255, 255);
    if ($background === false || !imagefill($canvas, 0, 0, $background)) {
        imagedestroy($source);
        imagedestroy($canvas);
        json_response(['error' => 'The server could not prepare the image background.'], 500);
    }

    if (!imagecopyresampled(
        $canvas,
        $source,
        0,
        0,
        $sourceX,
        $sourceY,
        $canvasWidth,
        $canvasHeight,
        $cropWidth,
        $cropHeight
    )) {
        imagedestroy($source);
        imagedestroy($canvas);
        json_response(['error' => 'The server could not resize an uploaded image.'], 500);
    }

    $filename = bin2hex(random_bytes(16)) . '.webp';
    $destination = $uploadDirectory . '/' . $filename;
    if (!imagewebp($canvas, $destination, 82)) {
        imagedestroy($source);
        imagedestroy($canvas);
        if (is_file($destination) && !unlink($destination)) {
            error_log('Events API could not remove a partial image upload: ' . $destination);
        }
        json_response(['error' => 'The server could not compress and save an uploaded image.'], 500);
    }
    imagedestroy($source);
    imagedestroy($canvas);
    $uploaded[] = 'assets/uploads/events/' . $filename;
}

json_response(['images' => $uploaded], 201);
