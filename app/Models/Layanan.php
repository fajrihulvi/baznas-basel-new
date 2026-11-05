<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Kartu "Tunaikan Zakat" di beranda (tabel `layanan`, admin → Layanan Beranda). */
final class Layanan
{
    /** Jumlah kartu di beranda. */
    public const MAKS_BERANDA = 4;

    /**
     * SELECT * agar situs tidak error bila kode terunggah sebelum migrasi 013 (kolom gambar belum ada).
     *
     * @return list<array{id: int, judul: string, deskripsi: ?string, link: string, gambar: ?string, gambar_w: ?int, gambar_h: ?int}>
     */
    public static function terbit(?int $limit = null): array
    {
        $baris = Database::fetchAll(
            "SELECT * FROM layanan WHERE publish = 'Y' ORDER BY urutan, id" . ($limit !== null ? ' LIMIT ' . max(1, $limit) : '')
        );

        return array_map(static fn (array $b): array => [
            'id' => (int) $b['id'], 'judul' => (string) $b['judul'], 'deskripsi' => $b['deskripsi'], 'link' => (string) $b['link'],
            'gambar' => $b['gambar'] ?? null,
            'gambar_w' => isset($b['gambar_w']) ? (int) $b['gambar_w'] : null,
            'gambar_h' => isset($b['gambar_h']) ? (int) $b['gambar_h'] : null,
        ], $baris);
    }
}
