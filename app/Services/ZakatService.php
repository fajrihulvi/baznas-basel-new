<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\DeskripsiZakat;

/**
 * Penjelasan jenis zakat. Konten `deskripsi_zakat` masih bisa diubah admin lama tanpa sanitasi,
 * jadi HTML-nya disanitasi di sini sebelum tampil, lalu di-cache (10 menit).
 */
final class ZakatService
{
    public const CACHE_KEY = 'zakat';

    /** @return list<array{id: int, judul: string, slug: string, ringkasan: string, foto: ?string, id_jenis_zakat: ?int}> */
    public static function daftar(): array
    {
        return self::data()['daftar'];
    }

    /** @return array{id: int, judul: string, slug: string, konten: string, ringkasan: string, foto: ?string, id_jenis_zakat: ?int, tgl: string}|null */
    public static function bySlug(string $slug): ?array
    {
        return self::data()['detail'][$slug] ?? null;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function data(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, static function (): array {
            $sanitizer = new HtmlSanitizer();
            $daftar = [];
            $detail = [];
            foreach (DeskripsiZakat::semua() as $z) {
                $konten = $sanitizer->clean((string) $z['deskripsi']);
                $item = [
                    'id'             => (int) $z['id'],
                    'judul'          => $z['judul'],
                    'slug'           => $z['slug'],
                    'ringkasan'      => ringkas($konten, 150),
                    'foto'           => $z['foto'] ?: null,
                    'id_jenis_zakat' => $z['id_jenis_zakat'] !== null ? (int) $z['id_jenis_zakat'] : null,
                ];
                $daftar[] = $item;
                $detail[$z['slug']] = $item + ['konten' => $konten, 'tgl' => $z['tgl']];
            }

            return ['daftar' => $daftar, 'detail' => $detail];
        });
    }
}
