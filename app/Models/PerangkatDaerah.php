<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Perangkat Daerah (OPD & kecamatan) penyetor pengumpulan ZIS — tabel `perangkat_daerah`. */
final class PerangkatDaerah
{
    /** id PD bernama sama (tanpa membedakan huruf besar/kecil & spasi berlebih), atau null. */
    public static function idDenganNama(string $nama): ?int
    {
        $id = Database::fetchColumn('SELECT id FROM perangkat_daerah WHERE nama = ? LIMIT 1', [self::rapikan($nama)]);

        return $id === false || $id === null ? null : (int) $id;
    }

    public static function rapikan(string $nama): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $nama));
    }

    /** @return list<array{id: int, nama: string, singkatan: ?string, aktif: bool}> semua PD (untuk mencocokkan baris impor) */
    public static function semua(): array
    {
        return array_map(static fn (array $b): array => ['id' => (int) $b['id'], 'nama' => (string) $b['nama'], 'singkatan' => $b['singkatan'], 'aktif' => (int) $b['aktif'] === 1],
            Database::fetchAll('SELECT id, nama, singkatan, aktif FROM perangkat_daerah ORDER BY urutan, nama'));
    }

    /** @return list<array{id: int, nama: string, singkatan: ?string}> PD aktif, urut tampilan */
    public static function aktif(): array
    {
        return array_map(static fn (array $b): array => ['id' => (int) $b['id'], 'nama' => (string) $b['nama'], 'singkatan' => $b['singkatan']],
            Database::fetchAll('SELECT id, nama, singkatan FROM perangkat_daerah WHERE aktif = 1 ORDER BY urutan, nama'));
    }
}
