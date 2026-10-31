<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Distribusi;
use App\Models\Pembayaran;
use App\Services\StatistikZakatService as Zakat;
use DateTimeImmutable;

/**
 * Statistik distribusi (penyaluran) untuk dasbor — memakai periode & potongan waktu yang sama dengan statistik zakat masuk.
 */
final class StatistikDistribusiService
{
    /** Kategori efektivitas ACR (Allocation to Collection Ratio) menurut Puskas BAZNAS: [batas bawah %, label] */
    public const KATEGORI_ACR = [[90, 'Sangat efektif'], [70, 'Efektif'], [50, 'Cukup efektif'], [20, 'Di bawah harapan'], [0, 'Tidak efektif']];

    /** @param array $periode hasil StatistikZakatService::periode() */
    public static function untuk(array $periode, int $zakatMasuk): array
    {
        [$dari, $sampai] = [$periode['dari'], $periode['sampai']];
        $ringkasan = self::ringkasan($dari, $sampai, $periode['banding']);
        $acr = $zakatMasuk > 0 ? $ringkasan['total'] / $zakatMasuk * 100 : null;

        return [
            'ringkasan' => $ringkasan + ['acr' => $acr, 'kategori_acr' => $acr === null ? null : self::kategoriAcr($acr)],
            'tren'      => self::tren($dari, $sampai, $periode['satuan']),
            'perPilar'  => self::perPilar($dari, $sampai),
            'perAsnaf'  => self::perKolom($dari, $sampai, 'asnaf', Distribusi::ASNAF, 'zakat'), // asnaf hanya bermakna untuk dana zakat
            'perWilayah' => self::perKolom($dari, $sampai, 'kecamatan', Distribusi::KECAMATAN),
            'perSumber' => self::perKolom($dari, $sampai, 'sumber_dana', Distribusi::SUMBER),
            'terbaru'   => self::terbaru($dari, $sampai, 6),
        ];
    }

    /** @return array{total: int, jumlah: int, penerima: int, total_lalu: ?int, produktif: int} */
    public static function ringkasan(DateTimeImmutable $dari, DateTimeImmutable $sampai, ?array $banding): array
    {
        $r = Database::fetch(
            "SELECT COALESCE(SUM(nominal), 0) AS total, COUNT(*) AS jumlah, COALESCE(SUM(penerima), 0) AS penerima,
                    COALESCE(SUM(CASE WHEN sifat = 'produktif' THEN nominal END), 0) AS produktif
               FROM distribusi WHERE tgl >= ? AND tgl < ?",
            Zakat::batas($dari, $sampai)
        ) ?? [];
        $lalu = $banding === null ? null
            : (int) Database::fetchColumn('SELECT COALESCE(SUM(nominal), 0) FROM distribusi WHERE tgl >= ? AND tgl < ?', Zakat::batas($banding[0], $banding[1]));

        return [
            'total' => (int) ($r['total'] ?? 0), 'jumlah' => (int) ($r['jumlah'] ?? 0), 'penerima' => (int) ($r['penerima'] ?? 0),
            'produktif' => (int) ($r['produktif'] ?? 0), 'total_lalu' => $lalu,
        ];
    }

    /**
     * Zakat masuk (Selesai) vs disalurkan per periode.
     *
     * @return list<array{kunci: string, label: string, judul: string, masuk: int, salur: int}>
     */
    public static function tren(DateTimeImmutable $dari, DateTimeImmutable $sampai, string $satuan): array
    {
        $format = Zakat::formatSql($satuan);
        $batas = Zakat::batas($dari, $sampai);
        $masuk = array_column(Database::fetchAll(
            "SELECT DATE_FORMAT(waktu, '{$format}') AS kunci, SUM(nominal) AS total FROM pembayaran WHERE status = ? AND waktu >= ? AND waktu < ? GROUP BY kunci",
            [Pembayaran::SELESAI, ...$batas]
        ), 'total', 'kunci');
        $salur = array_column(Database::fetchAll(
            "SELECT DATE_FORMAT(tgl, '{$format}') AS kunci, SUM(nominal) AS total FROM distribusi WHERE tgl >= ? AND tgl < ? GROUP BY kunci",
            $batas
        ), 'total', 'kunci');

        return array_map(static fn (array $e): array => $e + [
            'masuk' => (int) ($masuk[$e['kunci']] ?? 0),
            'salur' => (int) ($salur[$e['kunci']] ?? 0),
        ], Zakat::ember($dari, $sampai, $satuan));
    }

    /**
     * Per pilar (urut terbesar) beserta rincian sub-programnya. Pilar tanpa distribusi tetap muncul (Rp0) agar terlihat.
     *
     * @return list<array{pilar: string, total: int, jumlah: int, sub: list<array{nama: string, total: int, jumlah: int}>}>
     */
    public static function perPilar(DateTimeImmutable $dari, DateTimeImmutable $sampai): array
    {
        $baris = Database::fetchAll(
            'SELECT k.id_kategori_program AS id_pilar, k.label AS pilar, k.urutan, s.sub_program AS sub,
                    COALESCE(SUM(d.nominal), 0) AS total, COUNT(d.id) AS jumlah
               FROM kategori_program k
               LEFT JOIN sub_program s ON s.id_kategori_program = k.id_kategori_program
               LEFT JOIN distribusi d ON d.id_sub_program = s.id_sub_program AND d.tgl >= ? AND d.tgl < ?
              GROUP BY k.id_kategori_program, k.label, k.urutan, s.id_sub_program, s.sub_program
              ORDER BY k.urutan, total DESC',
            Zakat::batas($dari, $sampai)
        );
        $pilar = [];
        foreach ($baris as $b) {
            $p = &$pilar[(int) $b['id_pilar']];
            $p ??= ['pilar' => (string) $b['pilar'], 'total' => 0, 'jumlah' => 0, 'sub' => []];
            $p['total'] += (int) $b['total'];
            $p['jumlah'] += (int) $b['jumlah'];
            if ($b['sub'] !== null && (int) $b['total'] > 0) {
                $p['sub'][] = ['nama' => (string) $b['sub'], 'total' => (int) $b['total'], 'jumlah' => (int) $b['jumlah']];
            }
            unset($p);
        }
        $pilar = array_values($pilar);
        usort($pilar, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $pilar;
    }

    /**
     * Total per nilai satu kolom (asnaf, kecamatan, sumber_dana); urut terbesar, yang kosong jadi "Tidak diisi".
     *
     * @param 'asnaf'|'kecamatan'|'sumber_dana' $kolom
     * @param array<string, string> $label
     * @param string|null $sumber hanya sumber dana ini (mis. 'zakat' untuk asnaf)
     * @return list<array{label: string, total: int, jumlah: int}>
     */
    public static function perKolom(DateTimeImmutable $dari, DateTimeImmutable $sampai, string $kolom, array $label, ?string $sumber = null): array
    {
        $kolom = ['asnaf' => 'asnaf', 'kecamatan' => 'kecamatan', 'sumber_dana' => 'sumber_dana'][$kolom];
        $baris = Database::fetchAll(
            "SELECT {$kolom} AS nilai, SUM(nominal) AS total, COUNT(*) AS jumlah FROM distribusi
              WHERE tgl >= ? AND tgl < ?" . ($sumber !== null ? ' AND sumber_dana = ?' : '') . "
              GROUP BY {$kolom} ORDER BY total DESC",
            [...Zakat::batas($dari, $sampai), ...($sumber !== null ? [$sumber] : [])]
        );

        return array_map(static fn (array $b): array => [
            'label' => $b['nilai'] === null || $b['nilai'] === '' ? 'Tidak diisi' : ($label[$b['nilai']] ?? (string) $b['nilai']),
            'total' => (int) $b['total'], 'jumlah' => (int) $b['jumlah'],
        ], $baris);
    }

    /** @return list<array{id: int, tgl: string, nama_program: string, nominal: int, pilar: string, sub: string, penerima: ?int}> */
    public static function terbaru(DateTimeImmutable $dari, DateTimeImmutable $sampai, int $limit): array
    {
        return Database::fetchAll(
            'SELECT d.id, d.tgl, d.nama_program, d.nominal, d.penerima, k.label AS pilar, s.sub_program AS sub
               FROM distribusi d JOIN sub_program s ON s.id_sub_program = d.id_sub_program
               JOIN kategori_program k ON k.id_kategori_program = s.id_kategori_program
              WHERE d.tgl >= ? AND d.tgl < ? ORDER BY d.tgl DESC, d.id DESC LIMIT ' . max(1, $limit),
            Zakat::batas($dari, $sampai)
        );
    }

    public static function kategoriAcr(float $persen): string
    {
        foreach (self::KATEGORI_ACR as [$batas, $label]) {
            if ($persen >= $batas) {
                return $label;
            }
        }

        return 'Tidak efektif';
    }
}
