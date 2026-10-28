<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Penjelasan jenis zakat (halaman /zakat/{slug}). Kolom `deskripsi` masih ditulis admin lama
 * tanpa sanitasi — selalu tampilkan lewat ZakatService (disanitasi + di-cache).
 */
final class DeskripsiZakat
{
    /** @return list<array{judul: string, slug: string}> */
    public static function daftar(): array
    {
        return Database::fetchAll(
            'SELECT TRIM(judul) AS judul, slug FROM deskripsi_zakat ORDER BY id_deskripsi_zakat'
        );
    }

    /** Lengkap dengan deskripsi (untuk ringkasan) dan id jenis_zakat (untuk tautan bayar). */
    public static function semua(): array
    {
        return Database::fetchAll(
            'SELECT id_deskripsi_zakat AS id, TRIM(judul) AS judul, slug, deskripsi, foto, jenis_zakat AS id_jenis_zakat, tgl
               FROM deskripsi_zakat ORDER BY id_deskripsi_zakat'
        );
    }

    public static function bySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT id_deskripsi_zakat AS id, TRIM(judul) AS judul, slug, deskripsi, foto, jenis_zakat AS id_jenis_zakat, tgl
               FROM deskripsi_zakat WHERE slug = ?',
            [$slug]
        );
    }
}
