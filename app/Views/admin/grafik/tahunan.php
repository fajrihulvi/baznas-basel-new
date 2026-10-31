<?php
/**
 * Garis kumulatif: zakat masuk tahun ini vs tahun lalu (penekanan: tahun ini hijau, tahun lalu abu-abu).
 *
 * @var App\Core\View $this
 * @var array{tahun: int, bulan: list<string>, ini: list<?int>, lalu: list<int>} $tahunan
 * @var string $idJudul
 */

use App\Services\StatistikZakatService as Statistik;

$W = 720;
$kiri = 64;
$kanan = 16;
$atas = 16;
$tinggi = 220;
$H = $atas + $tinggi + 30;
$lebarPlot = $W - $kiri - $kanan;
$slot = $lebarPlot / 12;
$ticks = Statistik::skala(max([...array_filter($tahunan['ini'], 'is_int'), ...$tahunan['lalu'], 0]));
$maks = max(1, end($ticks));
$bulat = static fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
$x = static fn (int $i): float => $kiri + ($i + 0.5) * $slot;
$y = static fn (float $v): float => $atas + $tinggi - $v / $maks * $tinggi;
$titik = static function (array $nilai) use ($x, $y, $bulat): string {
    $p = [];
    foreach ($nilai as $i => $v) {
        if ($v !== null) {
            $p[] = $bulat($x($i)) . ',' . $bulat($y($v));
        }
    }

    return implode(' ', $p);
};
$warnaIni = '#0b6b3a';
$warnaLalu = '#9ca3af';
$akhirIni = array_key_last(array_filter($tahunan['ini'], 'is_int'));
?>
<div class="relative overflow-x-auto" data-grafik-wadah>
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full min-w-[600px]" role="img" aria-labelledby="<?= e($idJudul) ?>" data-grafik tabindex="0">
        <?php foreach ($ticks as $t): ?>
        <line x1="<?= $kiri ?>" x2="<?= $W - $kanan ?>" y1="<?= $bulat($y($t)) ?>" y2="<?= $bulat($y($t)) ?>" stroke="<?= $t === 0 ? '#c3c2b7' : '#e5e7eb' ?>" stroke-width="1" shape-rendering="crispEdges"/>
        <text x="<?= $kiri - 8 ?>" y="<?= $bulat($y($t) + 4) ?>" text-anchor="end" font-size="12" fill="#6b7280" style="font-variant-numeric: tabular-nums"><?= e(rupiah_ringkas($t)) ?></text>
        <?php endforeach ?>
        <?php foreach ($tahunan['bulan'] as $i => $b): ?>
        <text x="<?= $bulat($x($i)) ?>" y="<?= $atas + $tinggi + 20 ?>" text-anchor="middle" font-size="12" fill="#6b7280"><?= e($b) ?></text>
        <?php endforeach ?>

        <line class="grafik-silang" x1="0" x2="0" y1="<?= $atas ?>" y2="<?= $atas + $tinggi ?>" stroke="#9ca3af" stroke-width="1" visibility="hidden"/>
        <polyline points="<?= $titik($tahunan['lalu']) ?>" fill="none" stroke="<?= $warnaLalu ?>" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
        <?php if ($akhirIni !== null): ?>
        <polyline points="<?= $titik($tahunan['ini']) ?>" fill="none" stroke="<?= $warnaIni ?>" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
        <?php /* Titik akhir + label nilai (satu-satunya label langsung: angka yang jadi cerita) */ ?>
        <circle cx="<?= $bulat($x($akhirIni)) ?>" cy="<?= $bulat($y($tahunan['ini'][$akhirIni])) ?>" r="4" fill="<?= $warnaIni ?>" stroke="#ffffff" stroke-width="2"/>
        <text x="<?= $bulat($x($akhirIni) + ($akhirIni > 8 ? -8 : 8)) ?>" y="<?= $bulat($y($tahunan['ini'][$akhirIni]) - 10) ?>" text-anchor="<?= $akhirIni > 8 ? 'end' : 'start' ?>" font-size="12" font-weight="700" fill="#1f2937"><?= e(rupiah_ringkas($tahunan['ini'][$akhirIni])) ?></text>
        <?php endif ?>

        <?php foreach ($tahunan['bulan'] as $i => $b): ?>
        <?php
        $baris = [[(string) $tahunan['tahun'], $warnaIni, $tahunan['ini'][$i] === null ? 'belum berjalan' : format_rupiah($tahunan['ini'][$i])],
                  [(string) ($tahunan['tahun'] - 1), $warnaLalu, format_rupiah($tahunan['lalu'][$i])]];
        ?>
        <rect x="<?= $bulat($kiri + $i * $slot) ?>" y="<?= $atas ?>" width="<?= $bulat($slot) ?>" height="<?= $tinggi ?>" fill="#0b6b3a" fill-opacity="0" class="grafik-sentuh"
              data-silang="<?= $bulat($x($i)) ?>" data-judul="Akumulasi s.d. <?= e($b) ?>" data-garis data-baris="<?= e((string) json_encode($baris, JSON_UNESCAPED_UNICODE)) ?>"/>
        <?php endforeach ?>
    </svg>
</div>
