<?php

declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/..');
if ($rootDir === false) {
    http_response_code(500);
    exit('Server misconfigured.');
}

$requested = (string)($_GET['path'] ?? '');
$requested = ltrim($requested, '/');

if ($requested === '' || str_contains($requested, '..') || str_contains($requested, "\0")) {
    http_response_code(400);
    exit('Bad request.');
}

$assetPath = $rootDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $requested);
$real = realpath($assetPath);
$assetsRoot = realpath($rootDir . DIRECTORY_SEPARATOR . 'assets');

if ($real === false || $assetsRoot === false || !str_starts_with($real, $assetsRoot) || !is_file($real)) {
    http_response_code(404);
    exit('Not found.');
}

$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'css' => 'text/css; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8',
    'png' => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=604800');

readfile($real);
