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

    /** @return list<array{label: string, url: ?string, ikon: string, aktif: bool, tengah: bool}> */
    public static function bawah(): array
    {
        return array_map(static fn (array $item): array => [
            'label'  => $item['label'],
            'url'    => $item['url'],
            'ikon'   => $item['ikon'],
            'aktif'  => isset($item['aktif']) && self::aktif($item['aktif'], !empty($item['persis'])),
            'tengah' => !empty($item['tengah']),
        ], (array) config('menu.bawah', []));
    }

    /** @return list<array{judul: string, item: list<array{label: string, url: string, ikon: string}>}> */
    public static function sheet(): array
    {
        return array_map(static fn (array $grup): array => [
            'judul' => $grup['judul'],
            'item'  => self::rakit($grup['item']),
        ], (array) config('menu.sheet', []));
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
