<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Halaman
{
    /**
     * Judul & slug halaman terbit, dikelompokkan per grup (untuk menu & footer).
     *
     * @return array<string, list<array{judul: string, slug: string}>>
     */
    public static function terbitPerGrup(): array
    {
        $rows = Database::fetchAll(
            "SELECT grup, judul, slug FROM halaman WHERE publish = 'Y' ORDER BY grup, urutan, id"
        );

        $grup = [];
        foreach ($rows as $row) {
            $grup[$row['grup']][] = ['judul' => $row['judul'], 'slug' => $row['slug']];
        }

        return $grup;
    }

    /** Halaman terbit berdasarkan slug (grup 'tentang' tampil di /tentang/{slug}, lainnya di /halaman/{slug}). */
    public static function terbit(string $slug): ?array
    {
        return Database::fetch(
            "SELECT id, grup, judul, slug, konten, updated_at FROM halaman WHERE slug = ? AND publish = 'Y'",
            [$slug]
        );
    }

    /** Untuk sitemap.xml */
    public static function untukSitemap(): array
    {
        return Database::fetchAll("SELECT grup, slug, updated_at FROM halaman WHERE publish = 'Y'");
    }
}
