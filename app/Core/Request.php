<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request HTTP. Input string di-trim dan dibersihkan dari null byte;
 * escape HTML dilakukan saat output (e()), bukan di sini.
 */
final class Request
{
    /** Request yang sedang diproses — dipakai helper view (menu aktif, pagination). */
    private static ?self $current = null;

    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
        private readonly array $files = [],
    ) {
    }

    /** @param string $basePath subfolder aplikasi dari APP_URL, mis. "/baznas-basel" (kosong bila di root domain) */
    public static function capture(string $basePath = ''): self
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) strtok($uri, '?'));

        if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . ltrim((string) preg_replace('#/+#', '/', $path), '/');

        return self::$current = new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            self::clean($_GET),
            self::clean($_POST),
            $_SERVER,
            $_FILES,
        );
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    /**
     * File unggahan tunggal (struktur $_FILES[name]) atau null bila tidak ada.
     *
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null
     */
    public function file(string $name): ?array
    {
        $file = $this->files[$name] ?? null;

        return is_array($file) && isset($file['error']) && !is_array($file['error']) ? $file : null;
    }

    /** Body POST melebihi post_max_size: PHP mengosongkan $_POST & $_FILES (token CSRF ikut hilang). */
    public function melebihiBatasPost(): bool
    {
        $panjang = (int) ($this->server['CONTENT_LENGTH'] ?? 0);

        return $this->method === 'POST' && $panjang > 0 && $panjang > ukuran_ini((string) ini_get('post_max_size'));
    }

    /** @return array<string, mixed> */
    public function queryAll(): array
    {
        return $this->query;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->post;
    }

    /** @return array<string, mixed> */
    public function only(array $keys): array
    {
        return array_intersect_key($this->post, array_flip($keys));
    }

    public function queryString(): string
    {
        return (string) ($this->server['QUERY_STRING'] ?? '');
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return isset($this->server[$key]) ? (string) $this->server[$key] : null;
    }

    /** HTTPS? Termasuk di balik proxy/CDN yang mengirim X-Forwarded-Proto. */
    public function secure(): bool
    {
        $https = strtolower((string) ($this->server['HTTPS'] ?? ''));

        return ($https !== '' && $https !== 'off')
            || (string) ($this->server['SERVER_PORT'] ?? '') === '443'
            || strtolower((string) $this->header('X-Forwarded-Proto')) === 'https';
    }

    /** Nama host dari header Host, huruf kecil, tanpa port. */
    public function host(): string
    {
        return strtolower((string) preg_replace('/:\d+$/', '', (string) ($this->server['HTTP_HOST'] ?? '')));
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'clean'], $value);
        }

        return is_string($value) ? trim(str_replace("\0", '', $value)) : $value;
    }
}
