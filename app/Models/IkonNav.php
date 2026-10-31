<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

/** Ikon & label bottom navigation yang diatur admin (tabel ikon_nav, migrasi 011). */
final class IkonNav
{
    /**
     * Per kunci (beranda, program, bayar-zakat, kabar, menu). Tabel belum ada (migrasi 011 belum
     * dijalankan) → [] sehingga situs tetap memakai ikon & label bawaan, tidak error.
     *
     * @return array<string, array{label: string, ikon: ?string}>
     */
    public static function semua(): array
    {
        try {
            $baris = Database::fetchAll('SELECT kunci, label, ikon FROM ikon_nav');
        } catch (PDOException) {
            return [];
        }
        $hasil = [];
        foreach ($baris as $b) {
            $hasil[(string) $b['kunci']] = ['label' => trim((string) $b['label']), 'ikon' => $b['ikon'] ?: null];
        }

        return $hasil;
    }
}
