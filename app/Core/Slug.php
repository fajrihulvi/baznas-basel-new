<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

final class Slug
{
    /** "Zakat & Infaq: 2026!" → "zakat-dan-infaq-2026" */
    public static function make(string $text, int $max = 200): string
    {
        $text = str_replace('&', ' dan ', $text);
        if (function_exists('transliterator_transliterate')) {
            $text = (string) transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        } else {
            $text = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text));
        }

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
        if (strlen($slug) > $max) {
            $slug = substr($slug, 0, $max);
            $slug = str_contains($slug, '-') ? substr($slug, 0, (int) strrpos($slug, '-')) : $slug;
        }

        return $slug !== '' ? $slug : 'tanpa-judul';
    }

    /**
     * Slug yang belum dipakai di tabel: "judul", "judul-2", "judul-3", ...
     * $table/$column/$idColumn harus nama internal, bukan input pengguna.
     */
    public static function unique(string $text, string $table, string $column = 'slug', ?int $ignoreId = null, string $idColumn = 'id', int $max = 200): string
    {
        foreach ([$table, $column, $idColumn] as $name) {
            if (!preg_match('/^[a-z_]+$/', $name)) {
                throw new InvalidArgumentException("Nama tabel/kolom tidak valid: {$name}");
            }
        }

        $base = self::make($text, $max - 4); // sisakan ruang untuk akhiran "-N"
        $slug = $base;
        for ($n = 2; ; $n++) {
            $taken = Database::fetchColumn(
                "SELECT 1 FROM {$table} WHERE {$column} = ?" . ($ignoreId !== null ? " AND {$idColumn} <> ?" : '') . ' LIMIT 1',
                $ignoreId !== null ? [$slug, $ignoreId] : [$slug]
            );
            if ($taken === null) {
                return $slug;
            }
            $slug = $base . '-' . $n;
        }
    }
}
