<?php

require_once __DIR__ . '/AppContext.php';

final class PageBootstrap
{
    public static function fromRootDir(string $rootDir): AppContext
    {
        $existing = $GLOBALS['rms_app'] ?? null;
        if ($existing instanceof AppContext) {
            return $existing;
        }

        $app = AppContext::fromRootDir($rootDir);
        $GLOBALS['rms_app'] = $app;
        return $app;
    }

    public static function requireUser(string $rootDir, string $redirect = 'login.php'): AppContext
    {
        $app = self::fromRootDir($rootDir);
        $app->requireUser($redirect);
        return $app;
    }

    /** @param string[] $roles */
    public static function requireRole(string $rootDir, array $roles, string $redirect = 'index.php'): AppContext
    {
        $app = self::fromRootDir($rootDir);
        $app->requireRole($roles, $redirect);
        return $app;
    }

    public static function requireAdmin(string $rootDir, string $redirect = 'index.php'): AppContext
    {
        return self::requireRole($rootDir, ['admin'], $redirect);
    }
}
