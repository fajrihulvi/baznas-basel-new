<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\Halaman;
use App\Models\IkonNav;
use App\Models\KategoriProgram;
use App\Models\Rekening;
use App\Models\Setting;
use App\Models\SubProgram;

/**
 * Data yang dibutuhkan layout di setiap halaman (header, menu, footer), diambil sekali lalu di-cache.
 * Panggil SiteService::forget() setelah admin mengubah setting, pilar, rekening, halaman, atau ikon navigasi.
 */
final class SiteService
{
    public const CACHE_KEY = 'site';

    /** @var array{setting: array, pilar: array, rekening: array, halaman: array}|null */
    private static ?array $data = null;

    public static function setting(string $key, ?string $default = null): ?string
    {
        $value = self::data()['setting'][$key] ?? null;

        return $value === null || $value === '' ? $default : (string) $value;
    }

    public static function pilar(): array
    {
        return self::data()['pilar'];
    }

    /**
     * Kegiatan BAZNAS (sub-program) dengan foto & deskripsi, urut pilar, masing-masing membawa data pilarnya
     * ('pilar': tautan /program/{pilar}?sub= & kotak ikon pengganti foto). Kegiatan tanpa pilar terbit dilewati.
     *
     * @return list<array> lihat SubProgram::semua() + ['pilar' => array]
     */
    public static function kegiatan(): array
    {
        $pilarPerId = array_column(self::pilar(), null, 'id');
        $hasil = [];
        foreach (self::data()['kegiatan'] ?? [] as $k) {
            if (isset($pilarPerId[$k['id_pilar']])) {
                $hasil[] = $k + ['pilar' => $pilarPerId[$k['id_pilar']]];
            }
        }

        return $hasil;
    }

    /** Ikon & label bottom navigation dari admin, per kunci. @return array<string, array{label: string, ikon: ?string}> */
    public static function ikonNav(): array
    {
        return self::data()['ikonNav'] ?? [];
    }

    public static function rekening(): array
    {
        return self::data()['rekening'];
    }

    /** @return list<array{judul: string, slug: string}> */
    public static function halaman(string $grup): array
    {
        return self::data()['halaman'][$grup] ?? [];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$data = null;
    }

    private static function data(): array
    {
        return self::$data ??= Cache::remember(self::CACHE_KEY, 600, static fn (): array => [
            'setting'  => Setting::all(),
            'pilar'    => KategoriProgram::pilar(),
            'kegiatan' => SubProgram::semua(),
            'rekening' => Rekening::all(),
            'halaman'  => Halaman::terbitPerGrup(),
            'ikonNav'  => IkonNav::semua(),
        ]);
    }
}
