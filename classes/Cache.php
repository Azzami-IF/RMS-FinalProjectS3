<?php

class Cache
{
    private string $dir;

    public function __construct(string $dir = __DIR__ . '/../cache')
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->dir = $dir;
    }

    private function file(string $key): string
    {
        return $this->dir . '/' . md5($key) . '.json';
    }

    public function get(string $key, int $ttl)
    {
        $file = $this->file($key);

        if (!file_exists($file)) {
            return null;
        }

        if (time() - filemtime($file) > $ttl) {
            unlink($file);
            return null;
        }

        return json_decode(file_get_contents($file), true);
    }

    public function set(string $key, array $data): void
    {
        file_put_contents($this->file($key), json_encode($data));
    }
}
