<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Pengumpulan ZIS dari Perangkat Daerah (tabel `pengumpulan`, CRUD lewat config/admin.php).
 * Satu baris = satu setoran satu PD untuk satu bulan, berisi 1–3 jenis (kolom zakat/infaq/dskl). Data internal.
 */
final class Pengumpulan
{
    /**
     * Jenis → kolom, label & warna grafik (urutan tetap; palet hijau–biru–kuning tervalidasi aman buta warna.
     * Biru infaq sama dengan "Infaq & Sedekah" di grafik zakat masuk).
     */
    public const JENIS = [
        'zakat' => ['label' => 'Zakat', 'warna' => '#0b6b3a'],
        'infaq' => ['label' => 'Infaq/Sedekah', 'warna' => '#2a78d6'],
        'dskl'  => ['label' => 'DSKL', 'warna' => '#eda100'],
    ];

    public const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /** "2026-10-01" → "Oktober 2026" (kosong bila bukan tanggal). */
    public static function namaPeriode(?string $periode): string
    {
        if ($periode === null || !preg_match('/^(\d{4})-(\d{2})/', $periode, $m) || !isset(self::BULAN[(int) $m[2]])) {
            return '';
        }

        return self::BULAN[(int) $m[2]] . ' ' . $m[1];
    }
}
