<?php
/**
 * Kolom berpasangan per periode: zakat masuk (abu-abu, pembanding) vs disalurkan (hijau, fokus). Satu sumbu Rupiah.
 *
 * @var App\Core\View $this
 * @var list<array{kunci: string, label: string, judul: string, masuk: int, salur: int}> $tren
 * @var string $idJudul
 */

use App\Services\StatistikZakatService as Statistik;

$W = 720;
$kiri = 64;
$kanan = 8;
$atas = 12;
$tinggi = 220;
$H = $atas + $tinggi + 30;
$lebarPlot = $W - $kiri - $kanan;
$n = max(1, count($tren));
$slot = $lebarPlot / $n;
$batang = min(20, max(2, ($slot * 0.7 - 2) / 2)); // dua batang + celah 2px
$ticks = Statistik::skala(max([...array_column($tren, 'masuk'), ...array_column($tren, 'salur'), 0]));
$maks = max(1, end($ticks));
$y = static fn (float $v): float => $atas + $tinggi - $v / $maks * $tinggi;
$setiap = (int) ceil($n / 12);
$bulat = static fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
$warna = ['masuk' => '#9ca3af', 'salur' => '#0b6b3a'];
?>
<div class="relative overflow-x-auto" data-grafik-wadah>
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full min-w-[600px]" role="img" aria-labelledby="<?= e($idJudul) ?>" data-grafik tabindex="0">
        <?php foreach ($ticks as $t): ?>
        <line x1="<?= $kiri ?>" x2="<?= $W - $kanan ?>" y1="<?= $bulat($y($t)) ?>" y2="<?= $bulat($y($t)) ?>" stroke="<?= $t === 0 ? '#c3c2b7' : '#e5e7eb' ?>" stroke-width="1" shape-rendering="crispEdges"/>
        <text x="<?= $kiri - 8 ?>" y="<?= $bulat($y($t) + 4) ?>" text-anchor="end" font-size="12" fill="#6b7280" style="font-variant-numeric: tabular-nums"><?= e(rupiah_ringkas($t)) ?></text>
        <?php endforeach ?>

        <?php foreach ($tren as $i => $b): ?>
        <?php $x0 = $kiri + $i * $slot + ($slot - (2 * $batang + 2)) / 2; ?>
        <?php foreach (['masuk' => $x0, 'salur' => $x0 + $batang + 2] as $seri => $x): ?>
        <?php
        $h = $b[$seri] / $maks * $tinggi;
        if ($h < 0.5) {
            continue;
        }
        $alas = $atas + $tinggi;
        $top = $alas - $h;
        $r = min(4, $batang / 2, $h);
        ?>
        <path d="M<?= $bulat($x) ?> <?= $bulat($alas) ?>V<?= $bulat($top + $r) ?>Q<?= $bulat($x) ?> <?= $bulat($top) ?> <?= $bulat($x + $r) ?> <?= $bulat($top) ?>H<?= $bulat($x + $batang - $r) ?>Q<?= $bulat($x + $batang) ?> <?= $bulat($top) ?> <?= $bulat($x + $batang) ?> <?= $bulat($top + $r) ?>V<?= $bulat($alas) ?>Z" fill="<?= $warna[$seri] ?>"/>
        <?php endforeach ?>
        <?php if ($i % $setiap === 0): ?>
        <text x="<?= $bulat($kiri + ($i + 0.5) * $slot) ?>" y="<?= $atas + $tinggi + 20 ?>" text-anchor="middle" font-size="12" fill="#6b7280"><?= e($b['label']) ?></text>
        <?php endif ?>
        <?php endforeach ?>

        <?php foreach ($tren as $i => $b): ?>
        <?php
        $baris = [['Disalurkan', $warna['salur'], format_rupiah($b['salur'])], ['Zakat masuk', $warna['masuk'], format_rupiah($b['masuk'])]];
        $rasio = $b['masuk'] > 0 ? number_format($b['salur'] / $b['masuk'] * 100, 0, ',', '.') . '% dari zakat masuk' : null;
        ?>
        <rect x="<?= $bulat($kiri + $i * $slot) ?>" y="<?= $atas ?>" width="<?= $bulat($slot) ?>" height="<?= $tinggi ?>" fill="#0b6b3a" fill-opacity="0" class="grafik-sentuh"
              data-judul="<?= e($b['judul']) ?>" data-baris="<?= e((string) json_encode($baris, JSON_UNESCAPED_UNICODE)) ?>"<?= $rasio ? ' data-catatan="' . e($rasio) . '"' : '' ?>/>
        <?php endforeach ?>
    </svg>
</div>
