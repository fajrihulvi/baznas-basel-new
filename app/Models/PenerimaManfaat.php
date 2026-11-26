<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Jumlah penerima manfaat (jiwa) per bulan untuk 5 pilar (tabel `penerima_manfaat`, CRUD lewat config/admin.php).
 * Satu baris = satu bulan; hanya angka agregat. Persentase tidak disimpan — selalu dihitung dari jumlah jiwa.
 */
final class PenerimaManfaat
{
    /** Halaman (admin → Halaman, kelompok Tentang Kami) yang menampilkan laporan di bawah isinya. */
    public const SLUG_HALAMAN = 'sistem-pelaporan-zakat';

    /**
     * Kolom → pilar (slug), nama cadangan, warna irisan & warna teks di atasnya. Urutan & warna tetap:
     * palet tervalidasi aman buta warna untuk semua pasangan (donat bercelah + tabel) — jangan diubah tanpa validasi ulang.
     */
    public const PROGRAM = [
        'peduli' => ['slug' => 'basel-peduli', 'nama' => 'Kemanusiaan', 'warna' => '#0b6b3a', 'teks' => '#ffffff'],
        'sehat'  => ['slug' => 'basel-sehat', 'nama' => 'Kesehatan', 'warna' => '#2a78d6', 'teks' => '#ffffff'],
        'makmur' => ['slug' => 'basel-makmur', 'nama' => 'Ekonomi', 'warna' => '#e2540f', 'teks' => '#ffffff'],
        'cerdas' => ['slug' => 'basel-cerdas', 'nama' => 'Pendidikan', 'warna' => '#6d32b5', 'teks' => '#ffffff'],
        'taqwa'  => ['slug' => 'basel-taqwa', 'nama' => 'Dakwah', 'warna' => '#9cb806', 'teks' => '#1f2937'],
    ];

    /** Sudah ada data bulan itu (selain baris $kecuali)? */
    public static function adaPeriode(string $periode, ?int $kecuali): bool
    {
        return (bool) Database::fetchColumn('SELECT COUNT(*) FROM penerima_manfaat WHERE periode = ? AND id <> ?', [$periode, $kecuali ?? 0]);
    }

    /** @return list<int> tahun yang punya data, terbaru dulu */
    public static function tahun(): array
    {
        return array_map('intval', array_column(
            Database::fetchAll('SELECT DISTINCT YEAR(periode) AS th FROM penerima_manfaat ORDER BY th DESC'),
            'th'
        ));
    }

    /** Semua bulan tercatat pada satu tahun, urut Januari → Desember (tanpa keterangan/petugas: dipakai halaman publik). */
    public static function perTahun(int $tahun): array
    {
        return Database::fetchAll(
            'SELECT periode, MONTH(periode) AS bulan, peduli, sehat, makmur, cerdas, taqwa, total, penghimpunan
               FROM penerima_manfaat WHERE periode >= ? AND periode < ? ORDER BY periode',
            [sprintf('%04d-01-01', $tahun), sprintf('%04d-01-01', $tahun + 1)]
        );
    }

    /** 1590 dari 2145 → "74,13%" */
    public static function persen(int $nilai, int $total): string
    {
        return number_format($total > 0 ? $nilai / $total * 100 : 0, 2, ',', '.') . '%';
    }
}
