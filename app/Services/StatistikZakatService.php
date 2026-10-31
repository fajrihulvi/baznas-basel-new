<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Pembayaran;
use DateTimeImmutable;

/**
 * Statistik zakat masuk untuk dasbor admin (pimpinan BAZNAS).
 * "Zakat masuk" = pembayaran berstatus Selesai (sudah diverifikasi), menurut tanggal pembayaran dibuat (`waktu`).
 * Jenis zakat dikelompokkan menjadi 4 kelompok agar grafik tetap terbaca (maks. 4 warna).
 */
final class StatistikZakatService
{
    /** Kelompok → label & warna (urutan tetap; palet tervalidasi aman buta warna untuk pasangan bersebelahan). */
    public const KELOMPOK = [
        'maal'   => ['label' => 'Zakat Maal', 'warna' => '#0b6b3a'],
        'fitrah' => ['label' => 'Zakat Fitrah', 'warna' => '#eb6834'],
        'infaq'  => ['label' => 'Infaq & Sedekah', 'warna' => '#2a78d6'],
        'fidyah' => ['label' => 'Fidyah', 'warna' => '#eda100'],
    ];

    public const PRESET = [
        '30h'   => '30 hari terakhir',
        '90h'   => '90 hari terakhir',
        '12b'   => '12 bulan terakhir',
        'ini'   => 'Tahun ini',
        'lalu'  => 'Tahun lalu',
        'semua' => 'Semua waktu',
    ];

    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Ekspresi SQL kelompok untuk alias tabel jenis_zakat `j`. */
    private const SQL_KELOMPOK = "CASE WHEN j.id_kategori_zakat = 2 THEN 'maal'
        WHEN j.jenis_zakat LIKE '%fitrah%' THEN 'fitrah'
        WHEN j.jenis_zakat LIKE '%fidyah%' THEN 'fidyah' ELSE 'infaq' END";

    /** Semua data untuk dasbor sesuai filter di URL (?periode=… atau ?dari=…&sampai=…). */
    public static function untuk(Request $request): array
    {
        $periode = self::periode((string) $request->query('periode', ''), (string) $request->query('dari', ''), (string) $request->query('sampai', ''));
        [$dari, $sampai] = [$periode['dari'], $periode['sampai']];
        $ringkasan = self::ringkasan($dari, $sampai, $periode['banding']);

        return [
            'periode'  => $periode,
            'ringkasan' => $ringkasan,
            'tren'     => self::tren($dari, $sampai, $periode['satuan']),
            'perJenis' => self::perJenis($dari, $sampai),
            'tahunan'  => self::kumulatifTahunan((int) $sampai->format('Y')),
            // Distribusi memakai periode yang sama; ACR dihitung terhadap zakat masuk periode ini
            'distribusi' => StatistikDistribusiService::untuk($periode, $ringkasan['total']),
        ];
    }

    /**
     * @return array{kunci: string, label: string, dari: DateTimeImmutable, sampai: DateTimeImmutable,
     *               satuan: string, banding: ?array{0: DateTimeImmutable, 1: DateTimeImmutable}}
     */
    public static function periode(string $preset, string $dari, string $sampai): array
    {
        $hariIni = new DateTimeImmutable('today');
        $tanggal = static function (string $s): ?DateTimeImmutable {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);

            return $d !== false && $d->format('Y-m-d') === $s ? $d : null;
        };

        $d1 = $tanggal($dari);
        $d2 = $tanggal($sampai);
        if ($d1 !== null && $d2 !== null) {
            [$d1, $d2] = $d1 > $d2 ? [$d2, $d1] : [$d1, $d2];
            $kunci = 'rentang';
            $label = tgl_id($d1) . ' – ' . tgl_id($d2);
        } else {
            $kunci = isset(self::PRESET[$preset]) ? $preset : '12b';
            $label = self::PRESET[$kunci];
            [$d1, $d2] = match ($kunci) {
                '30h'   => [$hariIni->modify('-29 days'), $hariIni],
                '90h'   => [$hariIni->modify('-89 days'), $hariIni],
                'ini'   => [$hariIni->modify('first day of january this year'), $hariIni],
                'lalu'  => [$hariIni->modify('first day of january last year'), $hariIni->modify('last day of december last year')],
                'semua' => [self::tanggalPertama() ?? $hariIni->modify('first day of january this year'), $hariIni],
                default => [$hariIni->modify('first day of this month')->modify('-11 months'), $hariIni],
            };
        }

        $hari = (int) $d1->diff($d2)->days + 1;
        $satuan = $hari <= 45 ? 'hari' : ($hari <= 731 ? 'bulan' : 'tahun');

        // Pembanding: periode sepanjang yang sama tepat sebelumnya (tidak untuk "semua waktu")
        $banding = $kunci === 'semua' ? null : [$d1->modify("-{$hari} days"), $d1->modify('-1 day')];

        return ['kunci' => $kunci, 'label' => $label, 'dari' => $d1, 'sampai' => $d2, 'satuan' => $satuan, 'banding' => $banding];
    }

    /** @return array{total: int, jumlah: int, rata: int, total_lalu: ?int, menunggu: int, nominal_menunggu: int} */
    public static function ringkasan(DateTimeImmutable $dari, DateTimeImmutable $sampai, ?array $banding): array
    {
        $r = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN status = ? THEN nominal END), 0) AS total,
                    SUM(status = ?) AS jumlah,
                    SUM(status = ?) AS menunggu,
                    COALESCE(SUM(CASE WHEN status = ? THEN nominal END), 0) AS nominal_menunggu
               FROM pembayaran WHERE waktu >= ? AND waktu < ?",
            [Pembayaran::SELESAI, Pembayaran::SELESAI, Pembayaran::MENUNGGU, Pembayaran::MENUNGGU, ...self::batas($dari, $sampai)]
        ) ?? [];
        $total = (int) ($r['total'] ?? 0);
        $jumlah = (int) ($r['jumlah'] ?? 0);

        $totalLalu = null;
        if ($banding !== null) {
            $totalLalu = (int) Database::fetchColumn(
                'SELECT COALESCE(SUM(nominal), 0) FROM pembayaran WHERE status = ? AND waktu >= ? AND waktu < ?',
                [Pembayaran::SELESAI, ...self::batas($banding[0], $banding[1])]
            );
        }

        return [
            'total' => $total, 'jumlah' => $jumlah, 'rata' => $jumlah > 0 ? intdiv($total, $jumlah) : 0, 'total_lalu' => $totalLalu,
            'menunggu' => (int) ($r['menunggu'] ?? 0), 'nominal_menunggu' => (int) ($r['nominal_menunggu'] ?? 0),
        ];
    }

    /**
     * Zakat masuk per hari/bulan/tahun per kelompok; periode tanpa transaksi tetap muncul (0).
     *
     * @return list<array{kunci: string, label: string, judul: string, nilai: array<string, int>, total: int}>
     */
    public static function tren(DateTimeImmutable $dari, DateTimeImmutable $sampai, string $satuan): array
    {
        $format = self::formatSql($satuan);
        $baris = Database::fetchAll(
            "SELECT DATE_FORMAT(p.waktu, '{$format}') AS kunci, " . self::SQL_KELOMPOK . " AS kelompok, SUM(p.nominal) AS total
               FROM pembayaran p JOIN jenis_zakat j ON j.id_jenis_zakat = p.jenis_zakat
              WHERE p.status = ? AND p.waktu >= ? AND p.waktu < ?
              GROUP BY kunci, kelompok",
            [Pembayaran::SELESAI, ...self::batas($dari, $sampai)]
        );
        $data = [];
        foreach ($baris as $b) {
            $data[$b['kunci']][$b['kelompok']] = (int) $b['total'];
        }

        $hasil = [];
        foreach (self::ember($dari, $sampai, $satuan) as $e) {
            $nilai = [];
            foreach (array_keys(self::KELOMPOK) as $k) {
                $nilai[$k] = $data[$e['kunci']][$k] ?? 0;
            }
            $hasil[] = $e + ['nilai' => $nilai, 'total' => array_sum($nilai)];
        }

        return $hasil;
    }

    /**
     * Urutan periode (hari/bulan/tahun) dari $dari s.d. $sampai — dipakai bersama grafik zakat & distribusi.
     * 'kunci' sama dengan hasil DATE_FORMAT(…, formatSql($satuan)).
     *
     * @return list<array{kunci: string, label: string, judul: string}>
     */
    public static function ember(DateTimeImmutable $dari, DateTimeImmutable $sampai, string $satuan): array
    {
        $langkah = ['hari' => '+1 day', 'bulan' => '+1 month', 'tahun' => '+1 year'][$satuan];
        $awal = match ($satuan) {
            'bulan' => $dari->modify('first day of this month'),
            'tahun' => $dari->modify('first day of january this year'),
            default => $dari,
        };
        $phpFormat = ['hari' => 'Y-m-d', 'bulan' => 'Y-m', 'tahun' => 'Y'][$satuan];
        $hasil = [];
        for ($t = $awal, $i = 0; $t <= $sampai; $t = $t->modify($langkah), $i++) {
            $bulan = self::BULAN[(int) $t->format('n')];
            [$label, $judul] = match ($satuan) {
                'hari'  => [$t->format('j') . ' ' . $bulan, tgl_id($t)],
                'bulan' => [$bulan . ($i === 0 || $t->format('n') === '1' ? ' ' . $t->format('y') : ''), self::namaBulan($t)],
                default => [$t->format('Y'), 'Tahun ' . $t->format('Y')],
            };
            $hasil[] = ['kunci' => $t->format($phpFormat), 'label' => $label, 'judul' => $judul];
        }

        return $hasil;
    }

    /** Format DATE_FORMAT MySQL per satuan (nilai tetap dari daftar, aman disisipkan ke SQL). */
    public static function formatSql(string $satuan): string
    {
        return ['hari' => '%Y-%m-%d', 'bulan' => '%Y-%m', 'tahun' => '%Y'][$satuan];
    }

    /** @return list<array{jenis: string, kelompok: string, total: int, jumlah: int}> urut terbesar */
    public static function perJenis(DateTimeImmutable $dari, DateTimeImmutable $sampai): array
    {
        $baris = Database::fetchAll(
            'SELECT TRIM(j.jenis_zakat) AS jenis, ' . self::SQL_KELOMPOK . ' AS kelompok, SUM(p.nominal) AS total, COUNT(*) AS jumlah
               FROM pembayaran p JOIN jenis_zakat j ON j.id_jenis_zakat = p.jenis_zakat
              WHERE p.status = ? AND p.waktu >= ? AND p.waktu < ?
              GROUP BY j.id_jenis_zakat, jenis, kelompok
              ORDER BY total DESC',
            [Pembayaran::SELESAI, ...self::batas($dari, $sampai)]
        );

        return array_map(static fn (array $b): array => [
            'jenis' => (string) $b['jenis'], 'kelompok' => (string) $b['kelompok'], 'total' => (int) $b['total'], 'jumlah' => (int) $b['jumlah'],
        ], $baris);
    }

    /**
     * Akumulasi zakat masuk per bulan: tahun $tahun vs tahun sebelumnya.
     * Tahun berjalan berhenti di bulan ini (bulan depan belum terjadi, bukan nol).
     *
     * @return array{tahun: int, bulan: list<string>, ini: list<?int>, lalu: list<int>}
     */
    public static function kumulatifTahunan(int $tahun): array
    {
        $baris = Database::fetchAll(
            'SELECT YEAR(waktu) AS th, MONTH(waktu) AS bl, SUM(nominal) AS total
               FROM pembayaran WHERE status = ? AND waktu >= ? AND waktu < ?
              GROUP BY th, bl',
            [Pembayaran::SELESAI, ($tahun - 1) . '-01-01', ($tahun + 1) . '-01-01']
        );
        $per = [];
        foreach ($baris as $b) {
            $per[(int) $b['th']][(int) $b['bl']] = (int) $b['total'];
        }

        $sekarang = new DateTimeImmutable('today');
        $batasBulan = (int) $sekarang->format('Y') === $tahun ? (int) $sekarang->format('n') : ((int) $sekarang->format('Y') < $tahun ? 0 : 12);
        $ini = $lalu = [];
        $a = $b = 0;
        for ($m = 1; $m <= 12; $m++) {
            $b += $per[$tahun - 1][$m] ?? 0;
            $lalu[] = $b;
            $a += $per[$tahun][$m] ?? 0;
            $ini[] = $m <= $batasBulan ? $a : null;
        }

        return ['tahun' => $tahun, 'bulan' => array_values(self::BULAN), 'ini' => $ini, 'lalu' => $lalu];
    }

    /**
     * Garis bantu sumbu Y dengan angka bulat (1/2/2,5/5 × 10^n) dari 0 sampai ≥ $maks.
     *
     * @return list<int>
     */
    public static function skala(int $maks, int $jumlah = 4): array
    {
        if ($maks <= 0) {
            return [0, 1];
        }
        $kasar = $maks / $jumlah;
        $pangkat = 10 ** floor(log10($kasar));
        foreach ([1, 2, 2.5, 5, 10] as $f) {
            if ($f * $pangkat >= $kasar) {
                $langkah = $f * $pangkat;
                break;
            }
        }
        $hasil = [];
        for ($v = 0.0; $v < $maks + $langkah; $v += $langkah) {
            $hasil[] = (int) round($v);
        }

        return $hasil;
    }

    private static function tanggalPertama(): ?DateTimeImmutable
    {
        $min = Database::fetchColumn('SELECT MIN(waktu) FROM pembayaran WHERE status = ?', [Pembayaran::SELESAI]);

        return $min ? new DateTimeImmutable(substr((string) $min, 0, 10)) : null;
    }

    /** @return array{0: string, 1: string} batas [dari 00:00, sampai+1 hari 00:00) */
    public static function batas(DateTimeImmutable $dari, DateTimeImmutable $sampai): array
    {
        return [$dari->format('Y-m-d 00:00:00'), $sampai->modify('+1 day')->format('Y-m-d 00:00:00')];
    }

    private static function namaBulan(DateTimeImmutable $t): string
    {
        // "Oktober 2026" — ambil dari tgl_id("1 Oktober 2026")
        return substr(tgl_id($t->format('Y-m-01')), 2);
    }
}
