<?php

require_once __DIR__ . '/../includes/compat.php';

class AppContext
{
    private string $rootDir;
    private array $config;
    private PDO $db;
    private ?array $user;
    private ?string $role;
    private string $pathPrefix;

    private function __construct(string $rootDir, array $config, PDO $db, ?array $user, ?string $role, string $pathPrefix)
    {
        $this->rootDir = $rootDir;
        $this->config = $config;
        $this->db = $db;
        $this->user = $user;
        $this->role = $role;
        $this->pathPrefix = $pathPrefix;
    }

    public static function fromRootDir(string $rootDir): self
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rootDir = rtrim($rootDir, "\\/ ");

        try {
            require_once $rootDir . '/classes/Database.php';
            $config = require $rootDir . '/config/env.php';

            $appTz = $config['APP_TIMEZONE'] ?? '';
            if (is_string($appTz) && $appTz !== '') {
                @date_default_timezone_set($appTz);
            }

            $db = (new Database($config))->getConnection();
        } catch (Throwable $e) {
            // Log for debugging on shared hosting where errors are hidden.
            $logDir = $rootDir . '/cache';
            $logFile = $logDir . '/error.log';
            $line = '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage() . "\n";
            // Always send to server error log too.
            @error_log(rtrim($line));
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            if (is_dir($logDir)) {
                @file_put_contents($logFile, $line, FILE_APPEND);
            }

            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
                echo '<h3>RMS belum bisa dijalankan (Server Error)</h3>';
                echo '<p>Silakan cek konfigurasi <b>.env</b> (DB_HOST/DB_NAME/DB_USER/DB_PASS) dan pastikan database sudah di-import.</p>';
                echo '<p>Cek log: <code>cache/error.log</code></p>';
            }
            throw $e;
        }

        $user = $_SESSION['user'] ?? null;
        $role = is_array($user) ? ($user['role'] ?? null) : null;

        $requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $pathPrefix = (preg_match('~/(?:admin)(?:/|$)~', $requestPath) === 1) ? '../' : '';

        return new self($rootDir, $config, $db, is_array($user) ? $user : null, is_string($role) ? $role : null, $pathPrefix);
    }

    public function rootDir(): string
    {
        return $this->rootDir;
    }

    public function config(): array
    {
        return $this->config;
    }

    public function db(): PDO
    {
        return $this->db;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function role(): ?string
    {
        return $this->role;
    }

    public function pathPrefix(): string
    {
        return $this->pathPrefix;
    }

    public function requireUser(string $redirect = 'login.php'): void
    {
        if (!$this->user) {
            header('Location: ' . $this->pathPrefix . $redirect);
            exit;
        }
    }

    /** @param string[] $roles */
    public function requireRole(array $roles, string $redirect = 'index.php'): void
    {
        $this->requireUser($redirect);
        if (!$this->role || !in_array($this->role, $roles, true)) {
            header('Location: ' . $this->pathPrefix . $redirect);
            exit;
        }
    }
}
