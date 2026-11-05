<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\Banner;
use App\Models\DeskripsiZakat;
use App\Models\Layanan;
use App\Models\Mitra;
use App\Models\Post;
use App\Models\Testimoni;
use App\Models\Video;

/**
 * Semua data beranda dalam satu fungsi ber-cache (TTL 10 menit).
 * Panggil HomeService::forget() setelah admin menyimpan banner, layanan, post, video,
 * testimoni, mitra, atau penjelasan zakat (Fase 7). Pilar, setting & rekening ada di SiteService.
 */
final class HomeService
{
    public const CACHE_KEY = 'home';

    /** Tab "Kabar BAZNAS": kunci => [label, jenis post (null = semua)] */
    public const TAB_KABAR = [
        'semua'        => ['Semua', null],
        'berita'       => ['Berita', 'berita'],
        'artikel'      => ['Artikel', 'artikel'],
        'berkah_zakat' => ['Berkah Zakat', 'berkah_zakat'],
    ];

    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, static function (): array {
            $kabar = [];
            foreach (self::TAB_KABAR as $kunci => [, $jenis]) {
                $kabar[$kunci] = Post::terbaru(8, $jenis);
            }

            return [
                'banner'    => array_map(static fn (array $b): array => $b + [
                    'dim'        => self::ukuran('banner', $b['foto']),
                    'dim_mobile' => self::ukuran('banner', $b['foto_mobile']),
                ], Banner::terbit(5)),
                'layanan'   => Layanan::terbit(Layanan::MAKS_BERANDA),
                'ticker'    => Post::sorotan(5),
                'kabar'     => $kabar,
                'video'     => Video::terbaru(6),
                'zakat'     => DeskripsiZakat::daftar(),
                'testimoni' => Testimoni::terbit(8),
                'mitra'     => array_map(static fn (array $m): array => $m + [
                    'dim' => self::ukuran('mitra', $m['logo']),
                ], Mitra::all()),
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{0: int, 1: int}|null */
    private static function ukuran(string $folder, ?string $file): ?array
    {
        return $file ? image_size(public_path("uploads/{$folder}/" . basename($file))) : null;
    }
}
