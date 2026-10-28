<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\JenisZakat;

/**
 * Rumus zakat (§12). Kalkulator di browser (zakat.js) memakai rumus yang sama; kelas ini dipakai
 * untuk hasil tanpa JavaScript dan untuk parameter yang dikirim ke halaman (data-*).
 *
 * Parameter dari tabel `setting`. Bila harga emas / nilai fitrah belum diisi admin, pengguna
 * diminta mengisinya sendiri — angka tidak pernah ditebak.
 */
final class ZakatCalculator
{
    public const NISHAB_EMAS_GRAM = 85;
    public const KADAR = 0.025;

    /** Jenis kalkulator: kunci => label */
    public const JENIS = [
        'penghasilan' => 'Penghasilan',
        'emas'        => 'Emas',
        'perdagangan' => 'Perdagangan',
        'fitrah'      => 'Fitrah',
        'fidyah'      => 'Fidyah',
    ];

    /** @return array{harga_emas: ?int, nishab_bulanan: ?int, nishab_tahunan: ?int, nilai_fitrah: ?int, nilai_fidyah: ?int} */
    public static function parameter(): array
    {
        $hargaEmas = self::angkaSetting('harga_emas_per_gram');
        $nishabBulanan = self::angkaSetting('nishab_bulanan')
            ?? ($hargaEmas ? (int) round(self::NISHAB_EMAS_GRAM * $hargaEmas / 12) : null);

        $fidyah = self::angkaSetting('nilai_fidyah');
        if ($fidyah === null) {
            foreach (JenisZakat::semua() as $j) {
                if (stripos($j['nama'], 'fidyah') !== false && $j['fidyah_perhari']) {
                    $fidyah = (int) $j['fidyah_perhari'];
                }
            }
        }

        return [
            'harga_emas'     => $hargaEmas,
            'nishab_bulanan' => $nishabBulanan,
            'nishab_tahunan' => $hargaEmas ? self::NISHAB_EMAS_GRAM * $hargaEmas : ($nishabBulanan ? $nishabBulanan * 12 : null),
            'nilai_fitrah'   => self::angkaSetting('nilai_fitrah'),
            'nilai_fidyah'   => $fidyah,
        ];
    }

    /**
     * Hitung di server (fallback tanpa JS). Mengembalikan null bila input belum lengkap.
     *
     * @return array{rincian: list<array{0: string, 1: string}>, wajib: ?bool, zakat: int, pesan: string}|null
     */
    public static function hitung(string $jenis, array $in): ?array
    {
        $p = self::parameter();
        $hargaEmas = $p['harga_emas'] ?? (angka($in['harga_emas'] ?? '') ?: null);

        switch ($jenis) {
            case 'penghasilan':
                $total = angka($in['gaji'] ?? '') + angka($in['bonus'] ?? '');
                $nishab = $p['nishab_bulanan'] ?? ($hargaEmas ? (int) round(self::NISHAB_EMAS_GRAM * $hargaEmas / 12) : null);
                if ($total === 0 || $nishab === null) {
                    return null;
                }
                return self::maal([['Total penghasilan per bulan', format_rupiah($total)], ['Nishab per bulan', format_rupiah($nishab)]], $total, $nishab);

            case 'emas':
                $gram = (float) str_replace(',', '.', (string) ($in['gram'] ?? ''));
                if ($gram <= 0 || $hargaEmas === null) {
                    return null;
                }
                $nilai = (int) round($gram * $hargaEmas);
                return self::maal([['Nilai emas', format_rupiah($nilai)], ['Nishab', self::NISHAB_EMAS_GRAM . ' gram (' . format_rupiah(self::NISHAB_EMAS_GRAM * $hargaEmas) . ')']], $nilai, self::NISHAB_EMAS_GRAM * $hargaEmas);

            case 'perdagangan':
                $harta = angka($in['modal'] ?? '') + angka($in['untung'] ?? '') + angka($in['piutang'] ?? '') - angka($in['utang'] ?? '');
                $nishab = $p['nishab_tahunan'] ?? ($hargaEmas ? self::NISHAB_EMAS_GRAM * $hargaEmas : null);
                if ($harta <= 0 || $nishab === null) {
                    return $harta < 0 && $nishab !== null ? self::maal([['Harta bersih', format_rupiah(0)]], 0, $nishab) : null;
                }
                return self::maal([['Harta perdagangan bersih', format_rupiah($harta)], ['Nishab per tahun', format_rupiah($nishab)]], $harta, $nishab);

            case 'fitrah':
                $jiwa = angka($in['jiwa'] ?? '');
                $nilai = $p['nilai_fitrah'] ?? (angka($in['nilai'] ?? '') ?: null);
                if ($jiwa === 0 || $nilai === null) {
                    return null;
                }
                return ['rincian' => [['Jumlah jiwa', (string) $jiwa], ['Nilai per jiwa', format_rupiah($nilai)]], 'wajib' => null, 'zakat' => $jiwa * $nilai, 'pesan' => 'Zakat fitrah yang ditunaikan'];

            case 'fidyah':
                $hari = angka($in['hari'] ?? '');
                $nilai = $p['nilai_fidyah'] ?? (angka($in['nilai'] ?? '') ?: null);
                if ($hari === 0 || $nilai === null) {
                    return null;
                }
                return ['rincian' => [['Jumlah hari', (string) $hari], ['Fidyah per hari', format_rupiah($nilai)]], 'wajib' => null, 'zakat' => $hari * $nilai, 'pesan' => 'Fidyah yang ditunaikan'];
        }

        return null;
    }

    /** 2,5% = 1/40, dibulatkan ke atas — aritmetika bulat agar sama persis dengan zakat.js. */
    public static function kadar(int $harta): int
    {
        return intdiv($harta + 39, 40);
    }

    /** Zakat maal: wajib bila harta ≥ nishab, kadar 2,5% (dibulatkan ke atas). */
    private static function maal(array $rincian, int $harta, int $nishab): array
    {
        $wajib = $harta >= $nishab;

        return [
            'rincian' => $rincian,
            'wajib'   => $wajib,
            'zakat'   => $wajib ? self::kadar($harta) : 0,
            'pesan'   => $wajib ? 'Wajib zakat (2,5%)' : 'Belum mencapai nishab — belum wajib zakat, namun dapat berinfaq.',
        ];
    }

    private static function angkaSetting(string $kunci): ?int
    {
        $nilai = angka(setting($kunci) ?? '');

        return $nilai > 0 ? $nilai : null;
    }
}
