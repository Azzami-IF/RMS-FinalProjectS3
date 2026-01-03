<?php

declare(strict_types=1);

/**
 * Public front controller.
 *
 * Goal: allow deploying with DocumentRoot pointed at /public while keeping
 * existing URLs and existing file layout one directory above.
 */

$rootDir = realpath(__DIR__ . '/..');
if ($rootDir === false) {
    http_response_code(500);
    echo 'Server misconfigured.';
    exit;
}

// Polyfill PHP 8 string helpers for shared hosting environments.
require_once $rootDir . '/includes/compat.php';

$uriPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

// Support deployments under a subdirectory.
// Example 1: URL includes /public
//   SCRIPT_NAME=/RMS/public/index.php, REQUEST_URI=/RMS/public/login.php
// Example 2: URL does NOT include /public (rewrite from project root)
//   SCRIPT_NAME=/RMS/public/index.php, REQUEST_URI=/RMS/login.php
$scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$scriptDir = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');

$baseCandidates = [];
if ($scriptDir !== '') {
    $baseCandidates[] = $scriptDir;
    if (str_ends_with($scriptDir, '/public')) {
        $baseNoPublic = substr($scriptDir, 0, -strlen('/public'));
        if ($baseNoPublic !== '') {
            $baseCandidates[] = $baseNoPublic;
        }
    }
}

foreach ($baseCandidates as $base) {
    if (str_starts_with($uriPath, $base . '/')) {
        $uriPath = substr($uriPath, strlen($base) + 1);
        break;
    }
}

$path = ltrim($uriPath, '/');

if ($path === '' || $path === 'index.php') {
    $path = 'index.php';
}

// Normalize directory-style requests.
if (str_ends_with($path, '/')) {
    $path .= 'index.php';
}

// Convenience: /admin -> /admin/dashboard.php
if ($path === 'admin' || $path === 'admin/') {
    $path = 'admin/dashboard.php';
}

// Basic traversal hardening.
if (str_contains($path, '..') || str_contains($path, "\0")) {
    http_response_code(400);
    echo 'Bad request.';
    exit;
}

// Proxy assets (also handled via .htaccess).
if (str_starts_with($path, 'assets/')) {
    $_GET['path'] = substr($path, strlen('assets/'));
    require __DIR__ . '/asset.php';
    exit;
}

// Only allow serving PHP entrypoints from selected locations.
$allowedDirs = [
    'admin',
    'process',
    'charts',
    'notifications',
    'export',
];

$target = null;

// Root PHP files.
if (preg_match('/^[A-Za-z0-9_.-]+\.php$/', $path) === 1) {
    $candidate = $rootDir . DIRECTORY_SEPARATOR . $path;
    if (is_file($candidate)) {
        $target = $candidate;
    }
}

// Whitelisted directories.
if ($target === null) {
    foreach ($allowedDirs as $dir) {
        $prefix = $dir . '/';
        if (!str_starts_with($path, $prefix)) {
            continue;
        }

        if (preg_match('/^[A-Za-z0-9_\/-]+\.php$/', $path) !== 1) {
            break;
        }

        $candidate = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($candidate)) {
            $target = $candidate;
        }
        break;
    }
}

if ($target === null) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

require $target;
