<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Image;
use App\Core\Request;
use App\Core\Session;
use App\Services\SiteService;

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** Escape untuk output HTML (teks & atribut). */
function e(string|int|float|bool|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL absolut dalam aplikasi: url('berita/slug', ['hal' => 2]).
 * URL eksternal (https://, mailto:, tel:) dan anchor (#kontak) dikembalikan apa adanya;
 * skema lain (javascript:, data:, vbscript:, …) diganti "#" agar isian admin tidak bisa menyisipkan skrip.
 */
function url(string $path = '', array $query = []): string
{
    $path = trim($path);
    if (str_starts_with($path, '#')) {
        return $path;
    }
    if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $path, $m)) {
        return in_array(strtolower($m[1]), ['http', 'https', 'mailto', 'tel'], true) ? $path : '#';
    }
    if (str_starts_with($path, '//')) {
        return '#'; // URL tanpa skema ke domain lain
    }

    $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    if ($path === '' || $path === '/') {
        $url = rtrim($url, '/') . '/';
    }

    return $query === [] ? $url : $url . '?' . http_build_query($query);
}

/** URL aset di public/assets dengan cache-busting ?v={filemtime}. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = base_path('public/assets/' . $path);

    return url('assets/' . $path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . Csrf::FIELD . '" value="' . e(Csrf::token()) . '">';
}

/** Nilai input dari submit sebelumnya (diisi lewat Session::flash('_old', $input)). */
function old(string $key, string $default = ''): string
{
    $old = Session::getFlash('_old', []);

    return is_array($old) && isset($old[$key]) && is_scalar($old[$key]) ? (string) $old[$key] : $default;
}

/** 1500000 → "Rp1.500.000" */
function format_rupiah(int|float|string|null $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

/** "2026-09-07" → "7 September 2026" */
function tgl_id(DateTimeInterface|string|null $date): string
{
    if ($date === null || $date === '' || (is_string($date) && str_starts_with($date, '0000'))) {
        return '';
    }

    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date);

    return $d->format('j') . ' ' . $bulan[(int) $d->format('n')] . ' ' . $d->format('Y');
}

/** Path request aktif tanpa base path & query, mis. "/berita/judul". */
function current_path(): string
{
    return Request::current()?->path() ?? '/';
}

/** Nilai dari tabel `setting` (di-cache). Nilai kosong → $default. */
function setting(string $key, ?string $default = null): ?string
{
    return SiteService::setting($key, $default);
}

/** Ikon SVG inline dari app/Views/icons.php. Dekoratif (aria-hidden) — beri teks/aria-label di elemen induknya. */
function icon(string $name, string $class = 'size-6'): string
{
    static $icons = null;
    $icons ??= require base_path('app/Views/icons.php');

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . ($icons[$name] ?? $icons['info']) . '</svg>';
}

/**
 * <img> responsif dari public/uploads/{folder}: srcset WebP 480/800/1200, width/height (anti-CLS), lazy.
 *
 * Opsi: width, height (pakai foto_w/foto_h bila ada, lebih cepat dari membaca file), sizes (default 100vw),
 *       class, eager (true untuk gambar LCP: loading=eager + fetchpriority=high),
 *       rendah (true: fetchpriority=low, mis. slide carousel yang belum terlihat).
 */
function img(?string $file, string $folder, string $alt, array $opt = []): string
{
    $class = (string) ($opt['class'] ?? '');
    $eager = !empty($opt['eager']);
    $attrs = ' alt="' . e($alt) . '"' . ($class !== '' ? ' class="' . e($class) . '"' : '')
        . ($eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"')
        . (!$eager && !empty($opt['rendah']) ? ' fetchpriority="low"' : '') . ' decoding="async"';

    if ($file === null || trim($file) === '') {
        return '<img src="' . e(asset('img/placeholder.svg')) . '" width="' . (int) ($opt['width'] ?? 800)
            . '" height="' . (int) ($opt['height'] ?? 450) . '"' . $attrs . '>';
    }

    $dir  = 'uploads/' . trim($folder, '/') . '/';
    $file = basename($file);
    $w = (int) ($opt['width'] ?? 0);
    $h = (int) ($opt['height'] ?? 0);
    if ($w <= 0 || $h <= 0) {
        [$w, $h] = image_size(base_path('public/' . $dir . $file)) ?? [800, 450];
    }

    $srcset = img_srcset($file, $folder, $w);
    if ($srcset !== null) {
        $src = url($dir . rawurlencode(Image::variant($file, 800)));
        $responsive = ' srcset="' . e($srcset) . '" sizes="' . e((string) ($opt['sizes'] ?? '100vw')) . '"';
    } else {
        $src = url($dir . rawurlencode($file));
        $responsive = '';
    }

    return '<img src="' . e($src) . '"' . $responsive . ' width="' . $w . '" height="' . $h . '"' . $attrs . '>';
}

/**
 * Nilai srcset varian WebP ("…-480.webp 480w, …") atau null bila varian belum dibuat.
 * Deskriptor tidak melebihi lebar asli $width (varian tidak pernah diperbesar).
 */
function img_srcset(string $file, string $folder, int $width): ?string
{
    $dir  = 'uploads/' . trim($folder, '/') . '/';
    $file = basename($file);

    // Hanya varian yang benar-benar ada (gambar lama bisa belum punya varian lebar baru)
    $srcset = [];
    foreach (Image::WIDTHS as $w) {
        $variant = Image::variant($file, $w);
        if (!is_file(base_path('public/' . $dir . $variant))) {
            continue;
        }
        $descriptor = min($w, $width);
        $srcset[$descriptor] ??= url($dir . rawurlencode($variant)) . ' ' . $descriptor . 'w';
    }

    return $srcset === [] ? null : implode(', ', $srcset);
}

/** @return array{0: int, 1: int}|null lebar & tinggi gambar (di-cache per request) */
function image_size(string $path): ?array
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        $info = is_file($path) ? @getimagesize($path) : false;
        $cache[$path] = $info === false ? null : [$info[0], $info[1]];
    }

    return $cache[$path];
}

/** Teks polos ±$max karakter dari HTML, dipotong di batas kata (untuk ringkasan & meta description). */
function ringkas(string $html, int $max = 160): string
{
    $teks = html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $teks = trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $teks)));
    if (mb_strlen($teks) <= $max) {
        return $teks;
    }
    $potong = mb_substr($teks, 0, $max);
    $spasi  = mb_strrpos($potong, ' ');

    return rtrim(mb_substr($potong, 0, $spasi !== false ? $spasi : $max), ' ,.;:-') . '…';
}

/** Ukuran file di public/ yang mudah dibaca, mis. "1,9 MB"; null bila file tidak ada. */
function ukuran_file(string $pathPublik): ?string
{
    $file = base_path('public/' . ltrim($pathPublik, '/'));
    if (!is_file($file)) {
        return null;
    }
    $byte = (int) filesize($file);

    return $byte >= 1048576
        ? number_format($byte / 1048576, 1, ',', '.') . ' MB'
        : max(1, (int) round($byte / 1024)) . ' KB';
}

/** Nilai ukuran php.ini ("8M", "512K", "1G") → byte. */
function ukuran_ini(string $nilai): int
{
    $nilai = trim($nilai);
    $angka = (int) $nilai;

    return match (strtoupper(substr($nilai, -1))) {
        'G'     => $angka * 1073741824,
        'M'     => $angka * 1048576,
        'K'     => $angka * 1024,
        default => $angka,
    };
}

/** Batas unggah efektif: yang terkecil dari $maks, upload_max_filesize, dan post_max_size. */
function batas_unggah(int $maks): int
{
    return min($maks, ukuran_ini((string) ini_get('upload_max_filesize')), ukuran_ini((string) ini_get('post_max_size')));
}

/** "Rp1.500.000" / "1.500.000" / 1500000 → 1500000 (hanya digit). */
function angka(mixed $nilai): int
{
    $digit = preg_replace('/\D/', '', (string) $nilai);

    return $digit === '' || strlen($digit) > 15 ? 0 : (int) $digit;
}

/** Pesan error validasi dari submit sebelumnya untuk satu field (Session::flash('errors', [...])). */
function galat(string $field): ?string
{
    $errors = Session::getFlash('errors', []);

    return is_array($errors) && isset($errors[$field]) ? (string) $errors[$field] : null;
}
