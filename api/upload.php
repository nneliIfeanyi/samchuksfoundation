<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
request_method('POST');
require_admin(true);

if (!isset($_FILES['images'])) {
    json_response(['error' => 'Select one or more images to upload.'], 422);
}

$files = $_FILES['images'];
$names = is_array($files['name']) ? $files['name'] : [$files['name']];
$tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
$errors = is_array($files['error']) ? $files['error'] : [$files['error']];
$sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
if (count($names) > 10) {
    json_response(['error' => 'Upload no more than 10 images at a time.'], 422);
}

$uploadDirectory = dirname(__DIR__) . '/assets/uploads/events';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
    json_response(['error' => 'Unable to prepare the image upload directory.'], 500);
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
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
    if (!isset($allowedTypes[$mime]) || $imageInfo === false ||
        $imageInfo[0] > 10000 || $imageInfo[1] > 10000) {
        json_response(['error' => 'Images must be JPEG, PNG, WebP, or GIF, with dimensions no larger than 10,000 by 10,000 pixels.'], 422);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
    if (!move_uploaded_file($tmpNames[$index], $uploadDirectory . '/' . $filename)) {
        json_response(['error' => 'An image could not be saved.'], 500);
    }
    $uploaded[] = 'assets/uploads/events/' . $filename;
}

json_response(['images' => $uploaded], 201);
