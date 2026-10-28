<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cache berbasis file di storage/cache. Dipakai untuk data beranda, menu, setting, sitemap.
 * Nilai disimpan dengan serialize() tanpa objek (hanya array/skalar).
 */
final class Cache
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        [$hit, $value] = self::read($key);
        if ($hit) {
            return $value;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        [$hit, $value] = self::read($key);

        return $hit ? $value : $default;
    }

    /** @param int $ttl detik; 0 = pakai CACHE_TTL */
    public static function set(string $key, mixed $value, int $ttl = 0): void
    {
        if (!self::enabled()) {
            return;
        }

        $ttl  = $ttl > 0 ? $ttl : (int) Config::get('cache.ttl', 600);
        $file = self::file($key);
        $tmp  = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($tmp, serialize(['expires' => time() + $ttl, 'value' => $value]), LOCK_EX) !== false) {
            rename($tmp, $file);
        }
    }

    public static function forget(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            unlink($file);
        }
    }

    public static function flush(): void
    {
        foreach (glob(self::dir() . '/*.cache') ?: [] as $file) {
            unlink($file);
        }
    }

    /** @return array{0: bool, 1: mixed} */
    private static function read(string $key): array
    {
        if (!self::enabled()) {
            return [false, null];
        }

        $file = self::file($key);
        $raw  = is_file($file) ? file_get_contents($file) : false;
        if ($raw === false) {
            return [false, null];
        }

        $data = unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || ($data['expires'] ?? 0) < time()) {
            return [false, null];
        }

        return [true, $data['value']];
    }

    private static function enabled(): bool
    {
        return (bool) Config::get('cache.enabled', true);
    }

    private static function dir(): string
    {
        return (string) Config::get('cache.path', base_path('storage/cache'));
    }

    /** Kunci disertai APP_URL: cache berisi URL absolut (sitemap, menu) tidak terbawa saat alamat situs berubah. */
    private static function file(string $key): string
    {
        return self::dir() . '/' . sha1(Config::get('app.url', '') . '|' . $key) . '.cache';
    }
}
