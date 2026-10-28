<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sesi PHP yang dimulai hanya bila dibutuhkan (CSRF, flash, login).
 * Halaman publik tanpa form tidak membuat cookie sesi, sehingga tetap ringan dan bisa di-cache.
 */
final class Session
{
    private static bool $started = false;

    /** @var array<string, mixed> flash dari request sebelumnya */
    private static array $flash = [];

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $url = (string) Config::get('app.url', '');
            session_name(self::name());
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => rtrim((string) parse_url($url, PHP_URL_PATH), '/') . '/',
                'secure'   => str_starts_with($url, 'https://'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start([
                'use_strict_mode'  => true,
                'use_only_cookies' => true,
                'use_trans_sid'    => false,
                'cache_limiter'    => '', // header cache diatur App::finalize()
            ]);
        }

        self::$started = true;
        self::$flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
    }

    public static function isStarted(): bool
    {
        return self::$started;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::resume()) {
            return $default;
        }

        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        if (self::resume()) {
            unset($_SESSION[$key]);
        }
    }

    /** Simpan nilai untuk request berikutnya saja (pesan sukses, input lama, error validasi). */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        if (!self::resume()) {
            return $default;
        }

        return self::$flash[$key] ?? $default;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (!self::resume()) {
            return;
        }

        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(self::name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
        session_destroy();
        self::$started = false;
    }

    /** Lanjutkan sesi hanya bila sudah berjalan atau browser mengirim cookie sesi. */
    private static function resume(): bool
    {
        if (!self::$started && !isset($_COOKIE[self::name()])) {
            return false;
        }
        self::start();

        return true;
    }

    private static function name(): string
    {
        return (string) Config::get('app.session.name', 'baznas_sid');
    }
}
