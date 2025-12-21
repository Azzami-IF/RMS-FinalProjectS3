<?php

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

        require_once $rootDir . '/classes/Database.php';
        $config = require $rootDir . '/config/env.php';
        $db = (new Database($config))->getConnection();

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
