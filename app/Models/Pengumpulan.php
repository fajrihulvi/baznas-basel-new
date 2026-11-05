<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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

    /** @return array<int, int> id PD => jumlah setoran pada bulan itu (untuk impor: lewati/ganti yang sudah ada) */
    public static function jumlahPerPd(string $periode): array
    {
        return array_map('intval', array_column(Database::fetchAll(
            'SELECT id_perangkat_daerah AS pd, COUNT(*) AS n FROM pengumpulan WHERE periode = ? GROUP BY id_perangkat_daerah',
            [$periode]
        ), 'n', 'pd'));
    }

    public static function hapusPdPeriode(int $idPd, string $periode): int
    {
        return Database::execute('DELETE FROM pengumpulan WHERE id_perangkat_daerah = ? AND periode = ?', [$idPd, $periode]);
    }

    /** @param array{periode: string, tgl: string, id_perangkat_daerah: int, zakat: ?int, infaq: ?int, dskl: ?int, keterangan: ?string, petugas: ?string} $d */
    public static function tambah(array $d): int
    {
        Database::execute(
            'INSERT INTO pengumpulan (periode, tgl, id_perangkat_daerah, zakat, infaq, dskl, total, keterangan, petugas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['periode'], $d['tgl'], $d['id_perangkat_daerah'], $d['zakat'], $d['infaq'], $d['dskl'],
             (int) $d['zakat'] + (int) $d['infaq'] + (int) $d['dskl'], $d['keterangan'], $d['petugas']]
        );

        return Database::lastInsertId();
    }

    /** "2026-10-01" → "Oktober 2026" (kosong bila bukan tanggal). */
    public static function namaPeriode(?string $periode): string
    {
        if ($periode === null || !preg_match('/^(\d{4})-(\d{2})/', $periode, $m) || !isset(self::BULAN[(int) $m[2]])) {
            return '';
        }

        return self::BULAN[(int) $m[2]] . ' ' . $m[1];
    }
}
