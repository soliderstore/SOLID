<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/product-uploads.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}

$filename = $_GET['file'] ?? '';
if (!is_string($filename) || !isProductUploadFilename($filename)) {
    http_response_code(404);
    exit;
}

$directory = realpath(PRODUCT_UPLOAD_DIRECTORY);
$path = $directory === false ? false : realpath($directory . DIRECTORY_SEPARATOR . $filename);
if ($path === false || dirname($path) !== $directory || !is_file($path)) {
    http_response_code(404);
    exit;
}

$image = @getimagesize($path);
$mime = $image['mime'] ?? '';
$stream = isset(PRODUCT_UPLOAD_TYPES[$mime]) ? @fopen($path, 'rb') : false;
if ($stream === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
if ($method === 'GET') {
    fpassthru($stream);
}
fclose($stream);
