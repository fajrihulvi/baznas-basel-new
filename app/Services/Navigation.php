<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Menyusun menu dari config/menu.php + data dinamis (pilar, halaman), lengkap dengan status aktif
 * berdasarkan path request — tanpa JavaScript.
 */
final class Navigation
{
    /** @return list<array{label: string, aktif: bool, anak: list<array{label: string, url: string}>}> */
    public static function utama(): array
    {
        return array_map(static fn (array $menu): array => [
            'label' => $menu['label'],
            'aktif' => self::aktif($menu['aktif']),
            'anak'  => self::rakit($menu['anak']),
        ], (array) config('menu.utama', []));
    }

    /** @return list<array{label: string, url: ?string, ikon: string, svg: ?string, gambar: ?string, aktif: bool, tengah: bool}> */
    public static function bawah(): array
    {
        $atur = SiteService::ikonNav(); // admin → Ikon Navigasi (label & gambar), per kunci = 'svg'

        return array_map(static fn (array $item): array => [
            'label'  => ($atur[$item['svg'] ?? '']['label'] ?? '') ?: $item['label'],
            'url'    => $item['url'],
            'ikon'   => $item['ikon'],
            'svg'    => $item['svg'] ?? null, // ikon dua warna (app/Views/partials/ikon-nav.php) menggantikan 'ikon'
            'gambar' => $atur[$item['svg'] ?? '']['ikon'] ?? null, // gambar unggahan (uploads/ikon-nav) menggantikan svg
            'aktif'  => isset($item['aktif']) && self::aktif($item['aktif'], !empty($item['persis'])),
            'tengah' => !empty($item['tengah']),
        ], (array) config('menu.bawah', []));
    }

    /** Sub-menu satu item 'utama' (mis. 'Tentang') yang sudah dirakit. @return list<array{label: string, url: string, ikon: string}> */
    public static function anakUtama(string $label): array
    {
        foreach ((array) config('menu.utama', []) as $m) {
            if ($m['label'] === $label) {
                return self::rakit($m['anak']);
            }
        }

        return [];
    }

    /**
     * Panel menu HP: tautan langsung ('url') atau grup bersub-menu yang mengambil anak & penanda aktif dari menu 'utama' ('dari').
     *
     * @return list<array{label: string, url: ?string, aktif: bool, anak: list<array{label: string, url: string, ikon: string}>}>
     */
    public static function sheet(): array
    {
        $utama = [];
        foreach ((array) config('menu.utama', []) as $m) {
            $utama[$m['label']] = $m;
        }

        $hasil = [];
        foreach ((array) config('menu.sheet', []) as $item) {
            if (isset($item['url'])) {
                $aktif = isset($item['aktif']) ? self::aktif($item['aktif']) : self::aktif([$item['url']], true);
                $hasil[] = ['label' => $item['label'], 'url' => $item['url'], 'aktif' => $aktif, 'anak' => []];
                continue;
            }
            $sumber = $utama[$item['dari']] ?? null;
            if ($sumber === null) {
                continue;
            }
            $hasil[] = ['label' => $item['label'], 'url' => null, 'aktif' => self::aktif($sumber['aktif']), 'anak' => self::rakit($sumber['anak'])];
        }

        return $hasil;
    }

    /** @param list<string> $awalan */
    public static function aktif(array $awalan, bool $persis = false): bool
    {
        $path = current_path();
        foreach ($awalan as $a) {
            if ($path === $a || (!$persis && $a !== '/' && str_starts_with($path, rtrim($a, '/') . '/'))) {
                return true;
            }
        }

        return false;
    }

    /** Ganti entri 'dinamis' dengan data dari database. */
    private static function rakit(array $items): array
    {
        $hasil = [];
        foreach ($items as $item) {
            $ikon = $item['ikon'] ?? 'chevron-right';
            if (($item['dinamis'] ?? null) === 'pilar') {
                foreach (SiteService::pilar() as $p) {
                    $hasil[] = ['label' => $p['label'], 'url' => '/program/' . $p['slug'], 'ikon' => $ikon];
                }
            } elseif (str_starts_with($item['dinamis'] ?? '', 'halaman:')) {
                foreach (SiteService::halaman(substr($item['dinamis'], 8)) as $h) {
                    $hasil[] = ['label' => $h['judul'], 'url' => '/tentang/' . $h['slug'], 'ikon' => $ikon];
                }
            } else {
                $hasil[] = ['label' => $item['label'], 'url' => $item['url'], 'ikon' => $ikon];
            }
        }

        return $hasil;
    }
}
