<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Baca/tulis Excel .xlsx minimal (tanpa library; ext-zip + SimpleXML).
 * Tulis: satu sheet, string inline, angka, tanggal, lebar kolom, baris judul tebal, panel beku.
 * Baca: sheet pertama → array baris (indeks kolom 0-based); angka tetap angka, teks tetap teks.
 */
final class Xlsx
{
    /** Batas ukuran isi arsip agar file "zip bomb" tidak menghabiskan memori. */
    private const MAKS_ISI = 20 * 1048576;

    /** Gaya sel (indeks cellXfs di styles.xml). */
    public const GAYA_BIASA  = 0;
    public const GAYA_JUDUL  = 1; // tebal, latar hijau muda, berbingkai
    public const GAYA_ANGKA  = 2; // #,##0 berbingkai (bisa diisi)
    public const GAYA_TGL    = 3; // dd/mm/yyyy berbingkai
    public const GAYA_TEKS   = 4; // teks berbingkai
    public const GAYA_TEBAL  = 5; // tebal tanpa bingkai (judul lembar)
    public const GAYA_CATAT  = 6; // abu-abu miring (petunjuk)

    /**
     * Buat isi file .xlsx.
     *
     * @param list<list<array{0: string|int|float|DateTimeImmutable|null, 1?: int}>> $baris sel = [nilai, gaya]
     * @param list<float> $lebar lebar kolom (satuan karakter)
     * @param int $bekuBaris jumlah baris teratas yang dibekukan (0 = tidak)
     */
    public static function tulis(string $namaSheet, array $baris, array $lebar = [], int $bekuBaris = 0): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($bekuBaris > 0) {
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $bekuBaris . '" topLeftCell="A' . ($bekuBaris + 1)
                . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        }
        if ($lebar !== []) {
            $xml .= '<cols>';
            foreach ($lebar as $i => $w) {
                $xml .= sprintf('<col min="%d" max="%d" width="%s" customWidth="1"/>', $i + 1, $i + 1, rtrim(rtrim(number_format($w, 2, '.', ''), '0'), '.'));
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($baris as $r => $sel) {
            $xml .= '<row r="' . ($r + 1) . '">';
            foreach ($sel as $c => $isi) {
                [$nilai, $gaya] = [$isi[0] ?? null, $isi[1] ?? self::GAYA_BIASA];
                $ref = self::kolom($c) . ($r + 1);
                $s = $gaya !== self::GAYA_BIASA ? ' s="' . $gaya . '"' : '';
                if ($nilai === null || $nilai === '') {
                    $xml .= '<c r="' . $ref . '"' . $s . '/>';
                } elseif ($nilai instanceof DateTimeImmutable) {
                    $xml .= '<c r="' . $ref . '"' . $s . '><v>' . self::serial($nilai) . '</v></c>';
                } elseif (is_int($nilai) || is_float($nilai)) {
                    $xml .= '<c r="' . $ref . '"' . $s . '><v>' . $nilai . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc($nilai) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file Excel.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc(mb_substr($namaSheet, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
        $isi = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $isi;
    }

    /**
     * Baca sheet pertama file .xlsx.
     *
     * @return list<list<string|int|float|null>> baris → kolom (0-based); sel kosong = null; baris kosong tetap ada (nomor baris = indeks + 1)
     * @throws RuntimeException bila bukan file .xlsx yang valid
     */
    public static function baca(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('File bukan Excel .xlsx yang valid.');
        }
        try {
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $total += (int) ($zip->statIndex($i)['size'] ?? 0);
            }
            if ($total > self::MAKS_ISI) {
                throw new RuntimeException('Isi file Excel terlalu besar.');
            }

            $workbook = self::xml($zip, 'xl/workbook.xml') ?? throw new RuntimeException('File bukan Excel .xlsx yang valid.');
            $relasi = self::xml($zip, 'xl/_rels/workbook.xml.rels');
            $sheetPath = 'xl/worksheets/sheet1.xml';
            $pertama = $workbook->sheets->sheet[0] ?? null;
            if ($pertama !== null && $relasi !== null) {
                $rid = (string) $pertama->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                foreach ($relasi->Relationship as $rel) {
                    if ((string) $rel['Id'] === $rid) {
                        $target = ltrim((string) $rel['Target'], '/');
                        $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                    }
                }
            }
            $sheet = self::xml($zip, $sheetPath) ?? throw new RuntimeException('Lembar kerja Excel tidak ditemukan.');

            $teks = [];
            if (($ss = self::xml($zip, 'xl/sharedStrings.xml')) !== null) {
                foreach ($ss->si as $si) {
                    $teks[] = self::teksSi($si);
                }
            }

            $hasil = [];
            foreach ($sheet->sheetData->row ?? [] as $row) {
                $r = (int) $row['r'] - 1;
                if ($r < 0 || $r > 5000) {
                    continue;
                }
                $sel = [];
                $c = 0;
                foreach ($row->c as $cell) {
                    $c = isset($cell['r']) ? self::indeksKolom((string) $cell['r']) : $c;
                    if ($c > 50) {
                        continue;
                    }
                    $tipe = (string) $cell['t'];
                    $v = isset($cell->v) ? (string) $cell->v : null;
                    $sel[$c] = match ($tipe) {
                        's'         => $teks[(int) $v] ?? null,
                        'inlineStr' => isset($cell->is) ? self::teksSi($cell->is) : null,
                        'str', 'e'  => $v,
                        'b'         => $v,
                        default     => $v === null || $v === '' ? null : (is_numeric($v) ? $v + 0 : $v),
                    };
                    $c++;
                }
                if ($sel !== []) {
                    $baris = array_fill(0, max(array_keys($sel)) + 1, null);
                    foreach ($sel as $k => $isi) {
                        $baris[$k] = is_string($isi) ? trim($isi) : $isi;
                    }
                    $hasil[$r] = $baris;
                }
            }
            if ($hasil === []) {
                return [];
            }
            $padat = array_fill(0, max(array_keys($hasil)) + 1, []);

            return array_replace($padat, $hasil);
        } finally {
            $zip->close();
        }
    }

    /** Nomor seri tanggal Excel (sistem 1900) → tanggal, mis. 46327 → 2026-11-01. */
    public static function dariSerial(float $serial): DateTimeImmutable
    {
        return (new DateTimeImmutable('1899-12-30'))->modify('+' . (int) floor($serial) . ' days');
    }

    public static function serial(DateTimeImmutable $t): int
    {
        return (int) (new DateTimeImmutable('1899-12-30'))->diff($t->setTime(0, 0))->days;
    }

    /** 0 → A, 25 → Z, 26 → AA */
    public static function kolom(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    private static function indeksKolom(string $ref): int
    {
        preg_match('/^[A-Z]+/', strtoupper($ref), $m);
        $n = 0;
        foreach (str_split($m[0] ?? 'A') as $h) {
            $n = $n * 26 + (ord($h) - 64);
        }

        return $n - 1;
    }

    private static function xml(ZipArchive $zip, string $nama): ?SimpleXMLElement
    {
        $isi = $zip->getFromName($nama);
        if ($isi === false) {
            return null;
        }
        $xml = simplexml_load_string($isi, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

        return $xml === false ? null : $xml;
    }

    private static function teksSi(SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }
        $s = '';
        foreach ($si->r as $run) {
            $s .= (string) $run->t;
        }

        return $s;
    }

    private static function esc(string $s): string
    {
        // Buang karakter kontrol yang tidak sah di XML
        return htmlspecialchars((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function styles(): string
    {
        $bingkai = 'applyBorder="1" borderId="1"';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF0B6B3A"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE7F3EC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFC3C2B7"/></left><right style="thin"><color rgb="FFC3C2B7"/></right>'
            . '<top style="thin"><color rgb="FFC3C2B7"/></top><bottom style="thin"><color rgb="FFC3C2B7"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" xfId="0" applyFont="1" applyFill="1" ' . $bingkai . ' applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf>'
            . '<xf numFmtId="3" fontId="0" fillId="0" xfId="0" applyNumberFormat="1" ' . $bingkai . '/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" xfId="0" applyNumberFormat="1" ' . $bingkai . '/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" xfId="0" applyNumberFormat="1" ' . $bingkai . '/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment wrapText="0"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
