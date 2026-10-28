<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Post
{
    private const KOLOM_KARTU = 'id, jenis, judul, slug, ringkasan, foto, foto_w, foto_h, tgl';

    public const JENIS = ['berita', 'artikel', 'berkah_zakat', 'program', 'event'];

    /** Kabar terbit terbaru untuk kartu; $jenis null = semua jenis. */
    public static function terbaru(int $limit, ?string $jenis = null): array
    {
        return self::daftar($jenis, $limit, 0);
    }

    /** Judul untuk ticker "Info BAZNAS": yang disorot dulu, lalu terbaru. */
    public static function sorotan(int $limit): array
    {
        return Database::fetchAll(
            "SELECT judul, slug FROM post WHERE publish = 'Y' ORDER BY is_featured DESC, tgl DESC, id DESC LIMIT ?",
            [$limit]
        );
    }

    public static function daftar(?string $jenis, int $limit, int $offset): array
    {
        [$where, $params] = self::filterJenis($jenis);

        return Database::fetchAll(
            'SELECT ' . self::KOLOM_KARTU . " FROM post WHERE publish = 'Y'{$where} ORDER BY tgl DESC, id DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public static function hitung(?string $jenis): int
    {
        [$where, $params] = self::filterJenis($jenis);

        return (int) Database::fetchColumn("SELECT COUNT(*) FROM post WHERE publish = 'Y'{$where}", $params);
    }

    /** Detail post terbit beserta pilar programnya (bila ada). */
    public static function bySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT p.id, p.jenis, p.judul, p.slug, p.ringkasan, p.konten, p.foto, p.foto_w, p.foto_h, p.penulis,
                    p.tgl, p.updated_at, p.id_sub_program, s.sub_program, s.slug AS sub_slug, k.label AS pilar, k.slug AS pilar_slug
               FROM post p
               LEFT JOIN sub_program s ON s.id_sub_program = p.id_sub_program
               LEFT JOIN kategori_program k ON k.id_kategori_program = s.id_kategori_program
              WHERE p.slug = ? AND p.publish = 'Y'",
            [$slug]
        );
    }

    /** Post terkait: sub-program sama dulu, lalu jenis sama; terbaru. */
    public static function terkait(array $post, int $limit): array
    {
        return Database::fetchAll(
            'SELECT ' . self::KOLOM_KARTU . " FROM post
              WHERE publish = 'Y' AND id <> ?
              ORDER BY (id_sub_program <=> ?) DESC, (jenis = ?) DESC, tgl DESC, id DESC
              LIMIT ?",
            [$post['id'], $post['id_sub_program'], $post['jenis'], $limit]
        );
    }

    public static function tambahDilihat(int $id): void
    {
        Database::execute('UPDATE post SET views = views + 1 WHERE id = ?', [$id]);
    }

    /** @param list<int> $idSub */
    public static function perSubProgram(array $idSub, int $limit, int $offset): array
    {
        if ($idSub === []) {
            return [];
        }
        $tanda = implode(',', array_fill(0, count($idSub), '?'));

        return Database::fetchAll(
            'SELECT ' . self::KOLOM_KARTU . " FROM post WHERE publish = 'Y' AND id_sub_program IN ({$tanda})
              ORDER BY tgl DESC, id DESC LIMIT ? OFFSET ?",
            [...$idSub, $limit, $offset]
        );
    }

    /** @param list<int> $idSub */
    public static function hitungPerSubProgram(array $idSub): int
    {
        if ($idSub === []) {
            return 0;
        }
        $tanda = implode(',', array_fill(0, count($idSub), '?'));

        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM post WHERE publish = 'Y' AND id_sub_program IN ({$tanda})",
            $idSub
        );
    }

    public static function cari(string $q, int $limit, int $offset): array
    {
        $pola = '%' . addcslashes($q, '%_\\') . '%';

        return Database::fetchAll(
            'SELECT ' . self::KOLOM_KARTU . " FROM post
              WHERE publish = 'Y' AND (judul LIKE ? OR ringkasan LIKE ?)
              ORDER BY (judul LIKE ?) DESC, tgl DESC, id DESC LIMIT ? OFFSET ?",
            [$pola, $pola, $pola, $limit, $offset]
        );
    }

    public static function hitungCari(string $q): int
    {
        $pola = '%' . addcslashes($q, '%_\\') . '%';

        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM post WHERE publish = 'Y' AND (judul LIKE ? OR ringkasan LIKE ?)",
            [$pola, $pola]
        );
    }

    /** Untuk sitemap.xml */
    public static function untukSitemap(): array
    {
        return Database::fetchAll(
            "SELECT slug, tgl, updated_at FROM post WHERE publish = 'Y' ORDER BY tgl DESC"
        );
    }

    /** @return array{0: string, 1: list<string>} */
    private static function filterJenis(?string $jenis): array
    {
        return $jenis !== null && in_array($jenis, self::JENIS, true) ? [' AND jenis = ?', [$jenis]] : ['', []];
    }
}
