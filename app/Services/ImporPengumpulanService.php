<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Xlsx;
use App\Models\Pengumpulan;
use App\Models\PerangkatDaerah;
use DateTimeImmutable;
use RuntimeException;

/**
 * Impor pengumpulan dari Excel (.xlsx): satu baris per Perangkat Daerah, kolom Zakat / Infaq/Sedekah / DSKL.
 * Bulan & tahun setoran serta tanggal bawaan dipilih di form impor. Seluruh file diperiksa dulu —
 * bila ada satu baris salah, tidak ada yang disimpan (semua atau tidak sama sekali).
 */
final class ImporPengumpulanService
{
    /** Bila PD sudah punya data pada bulan yang sama. */
    public const MODE = [
        'lewati' => 'Lewati PD tersebut (data lama dipertahankan)',
        'ganti'  => 'Ganti data lama dengan isi file',
        'tambah' => 'Tambahkan sebagai setoran baru',
    ];

    public const MAKS_NOMINAL = 999999999999;

    private const HEADER = ['No', 'ID', 'Perangkat Daerah', 'Zakat', 'Infaq/Sedekah', 'DSKL', 'Tanggal Pengumpulan', 'Keterangan'];

    /** Isi file format impor: semua PD aktif, nominal kosong untuk diisi. */
    public static function format(): string
    {
        $c = static fn (mixed $v, int $g = Xlsx::GAYA_BIASA): array => [$v, $g];
        $baris = [
            [$c('Format Impor Pengumpulan ZIS — BAZNAS Kabupaten Bangka Selatan', Xlsx::GAYA_TEBAL)],
            [$c('Isi nominal dalam rupiah tanpa titik/Rp, mis. 1500000. Kosongkan jenis yang tidak ada; baris tanpa nominal dilewati (PD belum menyetor).', Xlsx::GAYA_CATAT)],
            [$c('Bulan & tahun setoran dipilih saat impor di admin. Tanggal Pengumpulan boleh kosong (memakai tanggal di form impor). Jangan ubah kolom ID & Perangkat Daerah.', Xlsx::GAYA_CATAT)],
            [],
            array_map(static fn (string $h): array => [$h, Xlsx::GAYA_JUDUL], self::HEADER),
        ];
        foreach (PerangkatDaerah::aktif() as $i => $pd) {
            $baris[] = [
                $c($i + 1, Xlsx::GAYA_TEKS), $c($pd['id'], Xlsx::GAYA_TEKS), $c($pd['nama'], Xlsx::GAYA_TEKS),
                $c(null, Xlsx::GAYA_ANGKA), $c(null, Xlsx::GAYA_ANGKA), $c(null, Xlsx::GAYA_ANGKA), $c(null, Xlsx::GAYA_TGL), $c(null, Xlsx::GAYA_TEKS),
            ];
        }

        return Xlsx::tulis('Pengumpulan', $baris, [5, 6, 58, 16, 16, 16, 20, 34], 5);
    }

    /**
     * @param string $periode YYYY-MM-01
     * @param string $tglBawaan YYYY-MM-DD (dipakai bila kolom tanggal kosong)
     * @return array{galat: list<string>, simpan: int, ganti: int, lewati: int, kosong: int, total: int}
     */
    public static function impor(string $path, string $periode, string $tglBawaan, string $mode, ?string $petugas): array
    {
        $hasil = ['galat' => [], 'simpan' => 0, 'ganti' => 0, 'lewati' => 0, 'kosong' => 0, 'total' => 0];
        try {
            $sel = Xlsx::baca($path);
        } catch (RuntimeException $e) {
            $hasil['galat'][] = $e->getMessage();

            return $hasil;
        }

        [$barisJudul, $kolom] = self::cariJudul($sel);
        if ($barisJudul === null) {
            $hasil['galat'][] = 'Baris judul kolom tidak ditemukan. Pakai file format dari tombol "Unduh format Excel" (judul kolom: Perangkat Daerah, Zakat, Infaq/Sedekah, DSKL).';

            return $hasil;
        }

        // Pencocokan PD: ID dulu, lalu nama/singkatan (huruf besar/kecil & spasi diabaikan)
        $pdId = [];
        $pdNama = [];
        foreach (PerangkatDaerah::semua() as $pd) {
            $pdId[$pd['id']] = $pd;
            $pdNama[self::kunci($pd['nama'])] = $pd;
            if (trim((string) $pd['singkatan']) !== '') {
                $pdNama[self::kunci((string) $pd['singkatan'])] ??= $pd;
            }
        }

        $hariIni = date('Y-m-d');
        $siap = [];
        $dipakai = [];
        for ($r = $barisJudul + 1; $r < count($sel); $r++) {
            $b = $sel[$r];
            $no = $r + 1; // nomor baris seperti di Excel
            $ambil = static fn (string $k): mixed => isset($kolom[$k]) ? ($b[$kolom[$k]] ?? null) : null;

            $nominal = [];
            $salah = [];
            foreach (array_keys(Pengumpulan::JENIS) as $k) {
                [$nominal[$k], $pesan] = self::nominal($ambil($k));
                if ($pesan !== null) {
                    $salah[] = Pengumpulan::JENIS[$k]['label'] . ' ' . $pesan;
                }
            }
            $idTeks = trim((string) $ambil('id'));
            $namaTeks = trim((string) $ambil('nama'));
            $adaNominal = array_filter($nominal) !== [];

            if (!$adaNominal && $salah === []) {
                if ($idTeks !== '' || $namaTeks !== '') {
                    $hasil['kosong']++;
                }
                continue;
            }

            $pd = null;
            if ($idTeks !== '' && ctype_digit($idTeks) && isset($pdId[(int) $idTeks])) {
                $pd = $pdId[(int) $idTeks];
            } elseif ($namaTeks !== '') {
                $pd = $pdNama[self::kunci($namaTeks)] ?? null;
            }
            if ($pd === null) {
                $salah[] = 'Perangkat Daerah "' . ($namaTeks !== '' ? $namaTeks : 'ID ' . $idTeks) . '" tidak dikenal (cek menu Perangkat Daerah)';
            } elseif (isset($dipakai[$pd['id']])) {
                $salah[] = $pd['nama'] . ' sudah ada di baris ' . $dipakai[$pd['id']] . ' (satu PD satu baris)';
            }

            [$tgl, $pesanTgl] = self::tanggal($ambil('tgl'), $tglBawaan);
            if ($pesanTgl !== null) {
                $salah[] = $pesanTgl;
            } elseif ($tgl > $hariIni) {
                $salah[] = 'Tanggal pengumpulan ' . tgl_id($tgl) . ' melewati hari ini';
            }
            $ket = trim((string) $ambil('ket'));

            if ($salah !== []) {
                $hasil['galat'][] = "Baris {$no}: " . implode('; ', $salah) . '.';
                if (count($hasil['galat']) >= 30) {
                    $hasil['galat'][] = 'Pemeriksaan dihentikan setelah 30 kesalahan.';
                    break;
                }
                continue;
            }

            $dipakai[$pd['id']] = $no;
            $siap[] = [
                'periode' => $periode, 'tgl' => $tgl, 'id_perangkat_daerah' => $pd['id'],
                'zakat' => $nominal['zakat'], 'infaq' => $nominal['infaq'], 'dskl' => $nominal['dskl'],
                'keterangan' => $ket !== '' ? mb_substr($ket, 0, 2000) : null, 'petugas' => $petugas,
            ];
        }

        if ($hasil['galat'] !== []) {
            return $hasil;
        }
        if ($siap === []) {
            $hasil['galat'][] = 'Tidak ada baris bernominal di file. Isi kolom Zakat, Infaq/Sedekah, atau DSKL untuk PD yang menyetor.';

            return $hasil;
        }

        Database::transaction(static function () use ($siap, $periode, $mode, &$hasil): void {
            $ada = Pengumpulan::jumlahPerPd($periode);
            foreach ($siap as $d) {
                if (isset($ada[$d['id_perangkat_daerah']])) {
                    if ($mode === 'lewati') {
                        $hasil['lewati']++;
                        continue;
                    }
                    if ($mode === 'ganti') {
                        Pengumpulan::hapusPdPeriode($d['id_perangkat_daerah'], $periode);
                        $hasil['ganti']++;
                    }
                }
                Pengumpulan::tambah($d);
                $hasil['simpan']++;
                $hasil['total'] += (int) $d['zakat'] + (int) $d['infaq'] + (int) $d['dskl'];
            }
        });

        return $hasil;
    }

    /**
     * Baris judul = baris pertama yang memuat "Perangkat Daerah" (atau "ID") dan minimal satu kolom jenis.
     *
     * @param list<list<mixed>> $sel
     * @return array{0: ?int, 1: array<string, int>}
     */
    private static function cariJudul(array $sel): array
    {
        foreach (array_slice($sel, 0, 30, true) as $r => $b) {
            $kolom = [];
            foreach ($b as $c => $isi) {
                $h = self::kunci((string) $isi);
                $k = match (true) {
                    $h === 'id'                                                   => 'id',
                    str_contains($h, 'perangkat') || $h === 'opd' || $h === 'pd'  => 'nama',
                    str_contains($h, 'tanggal')                                   => 'tgl',
                    str_contains($h, 'dskl')                                      => 'dskl',
                    str_contains($h, 'infa') || str_contains($h, 'sedekah')       => 'infaq',
                    str_contains($h, 'zakat')                                     => 'zakat',
                    str_contains($h, 'keterangan')                                => 'ket',
                    default                                                       => null,
                };
                if ($k !== null && !isset($kolom[$k])) {
                    $kolom[$k] = $c;
                }
            }
            if ((isset($kolom['nama']) || isset($kolom['id'])) && array_intersect_key($kolom, Pengumpulan::JENIS) !== []) {
                return [$r, $kolom];
            }
        }

        return [null, []];
    }

    /** @return array{0: ?int, 1: ?string} [nominal atau null bila kosong/0, pesan kesalahan] */
    private static function nominal(mixed $v): array
    {
        if ($v === null || $v === '' || $v === '-' || $v === '—') {
            return [null, null];
        }
        if (is_int($v) || is_float($v)) {
            $n = (int) round($v);
        } else {
            $s = preg_replace('/,\d{1,2}$/', '', str_ireplace(['rp', ' ', "\u{a0}"], '', (string) $v)); // "Rp1.500.000,00" → "1.500.000"
            if (!preg_match('/^\d[\d.]*$/', (string) $s)) {
                return [null, 'bukan angka ("' . mb_substr((string) $v, 0, 20) . '")'];
            }
            $n = angka($s);
        }
        if ($n < 0 || $n > self::MAKS_NOMINAL) {
            return [null, 'di luar batas'];
        }

        return [$n === 0 ? null : $n, null];
    }

    /** @return array{0: string, 1: ?string} [Y-m-d, pesan kesalahan] */
    private static function tanggal(mixed $v, string $bawaan): array
    {
        if ($v === null || $v === '') {
            return [$bawaan, null];
        }
        if (is_int($v) || is_float($v)) {
            return $v > 20000 && $v < 80000 ? [Xlsx::dariSerial((float) $v)->format('Y-m-d'), null] : [$bawaan, 'Tanggal pengumpulan tidak valid'];
        }
        foreach (['!d/m/Y', '!d-m-Y', '!Y-m-d', '!d.m.Y'] as $f) {
            $t = DateTimeImmutable::createFromFormat($f, (string) $v);
            if ($t !== false && DateTimeImmutable::getLastErrors() === false && $t->format('Y') >= '2000' && $t->format('Y') <= '2100') {
                return [$t->format('Y-m-d'), null];
            }
        }

        return [$bawaan, 'Tanggal pengumpulan "' . mb_substr((string) $v, 0, 20) . '" tidak dikenali (pakai format 05/11/2026)'];
    }

    private static function kunci(string $s): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $s)));
    }
}
