<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Halaman
{
    /**
     * Judul, slug & gambar kartu halaman terbit, dikelompokkan per grup (untuk menu, footer & Profil Kami).
     * SELECT * (tanpa konten di hasil) agar tidak error bila kode terunggah sebelum migrasi 016 (kolom gambar).
     *
     * @return array<string, list<array{judul: string, slug: string, gambar: ?string, gambar_w: ?int, gambar_h: ?int}>>
     */
    public static function terbitPerGrup(): array
    {
        $rows = Database::fetchAll(
            "SELECT * FROM halaman WHERE publish = 'Y' ORDER BY grup, urutan, id"
        );

        $grup = [];
        foreach ($rows as $row) {
            $grup[$row['grup']][] = [
                'judul' => $row['judul'], 'slug' => $row['slug'], 'gambar' => $row['gambar'] ?? null,
                'gambar_w' => isset($row['gambar_w']) ? (int) $row['gambar_w'] : null, 'gambar_h' => isset($row['gambar_h']) ? (int) $row['gambar_h'] : null,
            ];
        }

        return $grup;
    }

    /** Halaman terbit berdasarkan slug (grup 'tentang' tampil di /tentang/{slug}, lainnya di /halaman/{slug}). */
    /**
     * Satu halaman terbit. SELECT * agar tetap jalan sebelum migrasi 016 (kolom gambar belum ada → null).
     *
     * @return array{id: int, grup: string, judul: string, slug: string, konten: string, updated_at: ?string, gambar: ?string, gambar_w: ?int, gambar_h: ?int}|null
     */
    public static function terbit(string $slug): ?array
    {
        $b = Database::fetch("SELECT * FROM halaman WHERE slug = ? AND publish = 'Y'", [$slug]);
        if ($b === null) {
            return null;
        }

        return [
            'id'         => (int) $b['id'],
            'grup'       => (string) $b['grup'],
            'judul'      => (string) $b['judul'],
            'slug'       => (string) $b['slug'],
            'konten'     => (string) $b['konten'],
            'updated_at' => $b['updated_at'] ?? null,
            'gambar'     => ($b['gambar'] ?? '') !== '' ? (string) $b['gambar'] : null,
            'gambar_w'   => isset($b['gambar_w']) ? (int) $b['gambar_w'] : null,
            'gambar_h'   => isset($b['gambar_h']) ? (int) $b['gambar_h'] : null,
        ];
    }

    /** Untuk sitemap.xml */
    public static function untukSitemap(): array
    {
        return Database::fetchAll("SELECT grup, slug, updated_at FROM halaman WHERE publish = 'Y'");
    }
}
