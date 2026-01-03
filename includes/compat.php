<?php

// Compatibility helpers for shared hosting environments.
// Keeps the app running on PHP 7.4+ by polyfilling PHP 8 string helpers.

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $needleLen = strlen($needle);
        if ($needleLen > strlen($haystack)) {
            return false;
        }
        return substr($haystack, -$needleLen) === $needle;
    }
}
