<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\PenerimaManfaat;
use App\Models\Pengumpulan;
use PDOException;

/**
 * Laporan penerima manfaat 5 pilar: akumulasi satu tahun atau satu bulan (dasbor & halaman publik).
 * Filter: ?pm_tahun=2026&pm_bulan=10 (bulan 0/kosong = akumulasi semua bulan tercatat tahun itu).
 */
final class StatistikPenerimaManfaatService
{
    /** null bila tabel belum dimigrasi (kode terunggah lebih dulu) — halaman lainnya tetap tampil. */
    public static function untuk(Request $request): ?array
    {
        try {
            return self::hitung($request->query('pm_tahun'), $request->query('pm_bulan'));
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                return null;
            }
            throw $e;
        }
    }

    private static function hitung(mixed $tahun, mixed $bulan): array
    {
        $daftarTahun = PenerimaManfaat::tahun();
        $tahun = is_numeric($tahun) && in_array((int) $tahun, $daftarTahun, true) ? (int) $tahun : ($daftarTahun[0] ?? (int) date('Y'));
        $baris = array_map(static fn (array $b): array => [
            'bulan'        => (int) $b['bulan'],
            'judul'        => Pengumpulan::namaPeriode($b['periode']),
            'nilai'        => array_map('intval', array_intersect_key($b, PenerimaManfaat::PROGRAM)),
            'total'        => (int) $b['total'],
            'penghimpunan' => $b['penghimpunan'] !== null ? (int) $b['penghimpunan'] : null,
        ], $daftarTahun === [] ? [] : PenerimaManfaat::perTahun($tahun));

        // Hanya bulan yang punya data yang bisa dipilih
        $daftarBulan = array_column($baris, 'bulan');
        $bulan = is_numeric($bulan) && in_array((int) $bulan, $daftarBulan, true) ? (int) $bulan : 0;
        $dipakai = $bulan > 0 ? array_values(array_filter($baris, static fn (array $b): bool => $b['bulan'] === $bulan)) : $baris;

        $total = array_sum(array_column($dipakai, 'total'));
        $pilar = array_column(SiteService::pilar(), null, 'slug');
        $program = [];
        foreach (PenerimaManfaat::PROGRAM as $k => $p) {
            $jiwa = array_sum(array_map(static fn (array $b): int => $b['nilai'][$k], $dipakai));
            $program[$k] = [
                'nama'   => 'Program ' . (trim((string) ($pilar[$p['slug']]['nama'] ?? '')) ?: $p['nama']),
                'pilar'  => $pilar[$p['slug']] ?? null,
                'warna'  => $p['warna'],
                'teks'   => $p['teks'],
                'jiwa'   => $jiwa,
                'persen' => PenerimaManfaat::persen($jiwa, $total),
            ];
        }
        $terisi = array_filter(array_column($dipakai, 'penghimpunan'), static fn (?int $n): bool => $n !== null);

        return [
            'tahun'       => $tahun,
            'bulan'       => $bulan,
            'daftarTahun' => $daftarTahun,
            'daftarBulan' => $daftarBulan,
            'label'       => self::label($dipakai, $tahun),
            'program'     => $program,
            'total'       => $total,
            'bulanan'     => $baris,
            // Penghimpunan hanya diumumkan bila SEMUA bulan pada rentang sudah diisi (total sebagian menyesatkan)
            'penghimpunan'       => $terisi !== [] && count($terisi) === count($dipakai) ? array_sum($terisi) : null,
            'penghimpunanTerisi' => count($terisi),
            'bulanTercatat'      => count($dipakai),
        ];
    }

    /** "Oktober 2026" atau "Januari – Oktober 2026" (bulan tercatat pertama – terakhir). */
    private static function label(array $baris, int $tahun): string
    {
        if ($baris === []) {
            return 'Tahun ' . $tahun;
        }
        $awal = $baris[0]['bulan'];
        $akhir = $baris[array_key_last($baris)]['bulan'];

        return ($awal === $akhir ? '' : Pengumpulan::BULAN[$awal] . ' – ') . Pengumpulan::BULAN[$akhir] . ' ' . $tahun;
    }
}
