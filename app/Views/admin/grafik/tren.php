<?php
/**
 * Batang bertumpuk per periode (hari/bulan/tahun), ditumpuk per seri — dipakai zakat masuk (per kelompok) & pengumpulan (per jenis).
 * SVG dari server (tanpa library); tooltip & navigasi panah oleh assets/js/grafik.js.
 *
 * @var App\Core\View $this
 * @var list<array{label: string, judul: string, nilai: array<string, int>, total: int}> $tren
 * @var string $idJudul id judul grafik (aria-labelledby)
 * @var array<string, array{label: string, warna: string}>|null $seri  bawaan: StatistikZakatService::KELOMPOK
 * @var int|null $sorot indeks batang yang disorot (lainnya dipudarkan), mis. bulan yang dipilih di filter
 */

use App\Services\StatistikZakatService as Statistik;

$seri ??= Statistik::KELOMPOK;
$sorot ??= null;
$W = 720;
$kiri = 64;
$kanan = 8;
$atas = 12;
$tinggi = 220;
$H = $atas + $tinggi + 30;
$lebarPlot = $W - $kiri - $kanan;
$n = max(1, count($tren));
$slot = $lebarPlot / $n;
$batang = min(24, max(2, $slot * 0.62));
$ticks = Statistik::skala(max(array_column($tren, 'total') ?: [0]));
$maks = max(1, end($ticks));
$y = static fn (float $v): float => $atas + $tinggi - $v / $maks * $tinggi;
// Label sumbu X: maks ±12 agar tidak bertabrakan
$setiap = (int) ceil($n / 12);
$bulat = static fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
?>
<div class="relative overflow-x-auto" data-grafik-wadah>
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full min-w-[600px]" role="img" aria-labelledby="<?= e($idJudul) ?>" data-grafik tabindex="0"<?= $sorot !== null ? ' data-grafik-awal="' . (int) $sorot . '"' : '' ?>>
        <?php foreach ($ticks as $t): ?>
        <line x1="<?= $kiri ?>" x2="<?= $W - $kanan ?>" y1="<?= $bulat($y($t)) ?>" y2="<?= $bulat($y($t)) ?>" stroke="<?= $t === 0 ? '#c3c2b7' : '#e5e7eb' ?>" stroke-width="1" shape-rendering="crispEdges"/>
        <text x="<?= $kiri - 8 ?>" y="<?= $bulat($y($t) + 4) ?>" text-anchor="end" font-size="12" fill="#6b7280" style="font-variant-numeric: tabular-nums"><?= e(rupiah_ringkas($t)) ?></text>
        <?php endforeach ?>

        <?php foreach ($tren as $i => $b): ?>
        <?php
        $x = $kiri + $i * $slot + ($slot - $batang) / 2;
        $alas = $atas + $tinggi;
        $isi = array_filter(array_intersect_key($b['nilai'], $seri));
        $terakhir = array_key_last($isi);
        $pudar = $sorot !== null && $i !== $sorot;
        ?>
        <g<?= $pudar ? ' opacity="0.35"' : '' ?>>
        <?php
        foreach ($isi as $k => $v):
            $h = $v / $maks * $tinggi;
            $top = $alas - $h;
            $hGambar = $k === $terakhir ? $h : max(0, $h - 2); // celah 2px antarsegmen
            if ($hGambar >= 0.5):
                if ($k === $terakhir):
                    $r = min(4, $batang / 2, $hGambar); // ujung data membulat 4px, alas tetap persegi
                    $d = sprintf('M%s %sV%sQ%s %s %s %sH%sQ%s %s %s %sV%sZ', $bulat($x), $bulat($alas), $bulat($top + $r), $bulat($x), $bulat($top), $bulat($x + $r), $bulat($top),
                        $bulat($x + $batang - $r), $bulat($x + $batang), $bulat($top), $bulat($x + $batang), $bulat($top + $r), $bulat($alas));
        ?>
        <path d="<?= $d ?>" fill="<?= e($seri[$k]['warna']) ?>"/>
                <?php else: ?>
        <rect x="<?= $bulat($x) ?>" y="<?= $bulat($top + 2) ?>" width="<?= $bulat($batang) ?>" height="<?= $bulat($hGambar) ?>" fill="<?= e($seri[$k]['warna']) ?>"/>
                <?php endif ?>
            <?php endif ?>
        <?php $alas = $top; endforeach ?>
        </g>

        <?php if ($i % $setiap === 0): ?>
        <text x="<?= $bulat($kiri + ($i + 0.5) * $slot) ?>" y="<?= $atas + $tinggi + 20 ?>" text-anchor="middle" font-size="12"
              fill="<?= $sorot === $i ? '#111827' : '#6b7280' ?>"<?= $sorot === $i ? ' font-weight="700"' : '' ?>><?= e($b['label']) ?></text>
        <?php endif ?>
        <?php endforeach ?>

        <?php /* Area sentuh per periode (lebih besar dari batang) — membawa isi tooltip */ ?>
        <?php foreach ($tren as $i => $b): ?>
        <?php
        $baris = [];
        foreach ($seri as $k => $s) {
            $baris[] = [$s['label'], $s['warna'], format_rupiah($b['nilai'][$k] ?? 0)];
        }
        ?>
        <rect x="<?= $bulat($kiri + $i * $slot) ?>" y="<?= $atas ?>" width="<?= $bulat($slot) ?>" height="<?= $tinggi ?>" fill="#0b6b3a" fill-opacity="0" class="grafik-sentuh"
              data-judul="<?= e($b['judul']) ?>" data-total="<?= e(format_rupiah($b['total'])) ?>" data-baris="<?= e((string) json_encode($baris, JSON_UNESCAPED_UNICODE)) ?>"/>
        <?php endforeach ?>
    </svg>
</div>
