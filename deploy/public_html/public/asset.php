<?php

declare(strict_types=1);

$rootDir = realpath(__DIR__ . '/..');
if ($rootDir === false) {
    http_response_code(500);
    exit('Server misconfigured.');
}

require_once $rootDir . '/includes/compat.php';

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
$mime = 'application/octet-stream';
switch ($ext) {
    case 'css':
        $mime = 'text/css; charset=utf-8';
        break;
    case 'js':
        $mime = 'application/javascript; charset=utf-8';
        break;
    case 'png':
        $mime = 'image/png';
        break;
    case 'jpg':
    case 'jpeg':
        $mime = 'image/jpeg';
        break;
    case 'gif':
        $mime = 'image/gif';
        break;
    case 'svg':
        $mime = 'image/svg+xml';
        break;
    case 'webp':
        $mime = 'image/webp';
        break;
    case 'ico':
        $mime = 'image/x-icon';
        break;
    case 'woff':
        $mime = 'font/woff';
        break;
    case 'woff2':
        $mime = 'font/woff2';
        break;
    case 'ttf':
        $mime = 'font/ttf';
        break;
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=604800');

readfile($real);
