<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Pengumpulan;
use App\Models\PerangkatDaerah;
use DateTimeImmutable;

/**
 * Statistik pengumpulan ZIS dari Perangkat Daerah untuk dasbor (pimpinan).
 * Semua angka menurut kolom `periode` (bulan & tahun setoran), bukan tanggal uang diterima.
 * Filter sendiri: ?pg_tahun=2026&pg_bulan=10 (bulan 0/kosong = setahun penuh).
 */
final class StatistikPengumpulanService
{
    private const SINGKAT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public static function untuk(Request $request): array
    {
        $tahunIni = (int) date('Y');
        $daftarTahun = self::daftarTahun($tahunIni);
        $tahun = (int) $request->query('pg_tahun', $tahunIni);
        $tahun = in_array($tahun, $daftarTahun, true) ? $tahun : $tahunIni;
        $bulan = (int) $request->query('pg_bulan', 0);
        $bulan = $bulan >= 1 && $bulan <= 12 ? $bulan : 0;

        $dari = new DateTimeImmutable(sprintf('%04d-%02d-01', $tahun, $bulan ?: 1));
        $sampai = $bulan > 0 ? $dari->modify('+1 month') : $dari->modify('+1 year');
        // Pembanding: bulan sebelumnya / tahun sebelumnya
        $banding = $bulan > 0 ? [$dari->modify('-1 month'), $dari] : [$dari->modify('-1 year'), $dari];

        $pdAktif = PerangkatDaerah::aktif();
        $perPd = self::perPd($dari, $sampai);
        $menyetor = array_flip(array_column($perPd, 'id'));

        return [
            'tahun'       => $tahun,
            'bulan'       => $bulan,
            'daftarTahun' => $daftarTahun,
            'label'       => $bulan > 0 ? Pengumpulan::BULAN[$bulan] . ' ' . $tahun : 'Tahun ' . $tahun,
            'labelBanding' => $bulan > 0 ? Pengumpulan::namaPeriode($banding[0]->format('Y-m-d')) : 'tahun ' . ($tahun - 1),
            'ringkasan'   => self::ringkasan($dari, $sampai, $banding) + ['pd_aktif' => count($pdAktif)],
            'bulanan'     => self::bulanan($tahun),
            'perPd'       => $perPd,
            'belum'       => array_values(array_filter($pdAktif, static fn (array $p): bool => !isset($menyetor[$p['id']]))),
        ];
    }

    /** Tahun yang bisa dipilih: tahun data pertama s.d. tahun ini (atau tahun data terakhir bila lebih). @return list<int> menurun */
    public static function daftarTahun(int $tahunIni): array
    {
        $r = Database::fetch('SELECT MIN(YEAR(periode)) AS awal, MAX(YEAR(periode)) AS akhir FROM pengumpulan') ?? [];
        $awal = min($tahunIni, (int) ($r['awal'] ?? $tahunIni) ?: $tahunIni);
        $akhir = max($tahunIni, (int) ($r['akhir'] ?? $tahunIni));

        return range($akhir, $awal);
    }

    /**
     * @param array{0: DateTimeImmutable, 1: DateTimeImmutable} $banding
     * @return array{total: int, nilai: array<string, int>, setoran: int, pd_menyetor: int, total_lalu: int}
     */
    public static function ringkasan(DateTimeImmutable $dari, DateTimeImmutable $sampai, array $banding): array
    {
        $r = Database::fetch(
            'SELECT COALESCE(SUM(zakat), 0) AS zakat, COALESCE(SUM(infaq), 0) AS infaq, COALESCE(SUM(dskl), 0) AS dskl,
                    COALESCE(SUM(total), 0) AS total, COUNT(*) AS setoran, COUNT(DISTINCT id_perangkat_daerah) AS pd
               FROM pengumpulan WHERE periode >= ? AND periode < ?',
            self::batas($dari, $sampai)
        ) ?? [];
        $lalu = (int) Database::fetchColumn('SELECT COALESCE(SUM(total), 0) FROM pengumpulan WHERE periode >= ? AND periode < ?', self::batas(...$banding));
        $nilai = [];
        foreach (array_keys(Pengumpulan::JENIS) as $k) {
            $nilai[$k] = (int) ($r[$k] ?? 0);
        }

        return [
            'total' => (int) ($r['total'] ?? 0), 'nilai' => $nilai, 'setoran' => (int) ($r['setoran'] ?? 0),
            'pd_menyetor' => (int) ($r['pd'] ?? 0), 'total_lalu' => $lalu,
        ];
    }

    /**
     * 12 bulan satu tahun per jenis; bulan tanpa setoran tetap muncul (0).
     *
     * @return list<array{bulan: int, label: string, judul: string, nilai: array<string, int>, total: int, pd: int}>
     */
    public static function bulanan(int $tahun): array
    {
        $baris = Database::fetchAll(
            'SELECT MONTH(periode) AS bl, COALESCE(SUM(zakat), 0) AS zakat, COALESCE(SUM(infaq), 0) AS infaq, COALESCE(SUM(dskl), 0) AS dskl,
                    COUNT(DISTINCT id_perangkat_daerah) AS pd
               FROM pengumpulan WHERE periode >= ? AND periode < ? GROUP BY bl',
            [sprintf('%04d-01-01', $tahun), sprintf('%04d-01-01', $tahun + 1)]
        );
        $per = array_column($baris, null, 'bl');

        $hasil = [];
        for ($m = 1; $m <= 12; $m++) {
            $nilai = [];
            foreach (array_keys(Pengumpulan::JENIS) as $k) {
                $nilai[$k] = (int) ($per[$m][$k] ?? 0);
            }
            $hasil[] = [
                'bulan' => $m, 'label' => self::SINGKAT[$m], 'judul' => Pengumpulan::BULAN[$m] . ' ' . $tahun,
                'nilai' => $nilai, 'total' => array_sum($nilai), 'pd' => (int) ($per[$m]['pd'] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Per Perangkat Daerah yang menyetor pada periode (urut terbesar).
     *
     * @return list<array{id: int, nama: string, singkatan: string, nilai: array<string, int>, total: int, setoran: int}>
     */
    public static function perPd(DateTimeImmutable $dari, DateTimeImmutable $sampai): array
    {
        $baris = Database::fetchAll(
            'SELECT d.id, d.nama, d.singkatan, COALESCE(SUM(p.zakat), 0) AS zakat, COALESCE(SUM(p.infaq), 0) AS infaq,
                    COALESCE(SUM(p.dskl), 0) AS dskl, SUM(p.total) AS total, COUNT(*) AS setoran
               FROM pengumpulan p JOIN perangkat_daerah d ON d.id = p.id_perangkat_daerah
              WHERE p.periode >= ? AND p.periode < ?
              GROUP BY d.id, d.nama, d.singkatan
              ORDER BY total DESC, d.urutan, d.nama',
            self::batas($dari, $sampai)
        );

        return array_map(static function (array $b): array {
            $nilai = [];
            foreach (array_keys(Pengumpulan::JENIS) as $k) {
                $nilai[$k] = (int) $b[$k];
            }

            return [
                'id' => (int) $b['id'], 'nama' => (string) $b['nama'],
                'singkatan' => trim((string) $b['singkatan']) !== '' ? (string) $b['singkatan'] : (string) $b['nama'],
                'nilai' => $nilai, 'total' => (int) $b['total'], 'setoran' => (int) $b['setoran'],
            ];
        }, $baris);
    }

    /** @return array{0: string, 1: string} [dari, sampai) dalam Y-m-d */
    private static function batas(DateTimeImmutable $dari, DateTimeImmutable $sampai): array
    {
        return [$dari->format('Y-m-d'), $sampai->format('Y-m-d')];
    }
}
