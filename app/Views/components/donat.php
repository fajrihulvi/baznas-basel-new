<?php
/**
 * Grafik donat bagian-dari-keseluruhan (SVG dari server, tanpa library). Tooltip & panah kiri/kanan oleh assets/js/grafik.js.
 * Selalu dampingi dengan tabel berisi nilai yang sama. Persentase irisan besar di dalam, irisan kecil di luar dengan garis penunjuk.
 *
 * @var App\Core\View $this
 * @var list<array{label: string, nilai: int, persen: string, warna: string, teks: string}> $item
 * @var int    $total
 * @var string $satuan  mis. "jiwa"
 * @var string $idJudul id judul grafik (aria-labelledby)
 */
$pusat = 200;
$luar = 140;
$dalam = 78;
$ambang = 0.06; // irisan ≥ 6% cukup lebar untuk teks di dalamnya
$f = static fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
// Sudut 0 = jam 12, searah jarum jam
$titik = static fn (float $r, float $sudut): array => [$pusat + $r * sin($sudut), $pusat - $r * cos($sudut)];

$iris = [];
$mulai = 0.0;
foreach (array_values(array_filter($item, static fn (array $i): bool => $i['nilai'] > 0)) as $i) {
    $bagian = $total > 0 ? $i['nilai'] / $total : 0;
    $akhir = $mulai + $bagian * 2 * M_PI;
    if ($bagian >= 0.9999) {
        // Satu irisan penuh: dua setengah lingkaran (satu busur 360° tidak tergambar)
        $d = sprintf('M200 %1$sA%2$s %2$s 0 1 1 200 %3$sA%2$s %2$s 0 1 1 200 %1$sZM200 %4$sA%5$s %5$s 0 1 0 200 %6$sA%5$s %5$s 0 1 0 200 %4$sZ',
            $f($pusat - $luar), $luar, $f($pusat + $luar), $f($pusat - $dalam), $dalam, $f($pusat + $dalam));
    } else {
        [$x1, $y1] = $titik($luar, $mulai);
        [$x2, $y2] = $titik($luar, $akhir);
        [$x3, $y3] = $titik($dalam, $akhir);
        [$x4, $y4] = $titik($dalam, $mulai);
        $besar = $bagian > 0.5 ? 1 : 0;
        $d = sprintf('M%s %sA%s %s 0 %d 1 %s %sL%s %sA%s %s 0 %d 0 %s %sZ', $f($x1), $f($y1), $luar, $luar, $besar, $f($x2), $f($y2), $f($x3), $f($y3), $dalam, $dalam, $besar, $f($x4), $f($y4));
    }
    $iris[] = $i + ['d' => $d, 'tengah' => ($mulai + $akhir) / 2, 'bagian' => $bagian];
    $mulai = $akhir;
}

// Label luar (irisan kecil): beri jarak sudut minimal agar tidak bertabrakan, lalu geser balik supaya tetap dekat irisannya
$celah = deg2rad(20);
$luarLabel = array_keys(array_filter($iris, static fn (array $i): bool => $i['bagian'] < $ambang));
$sudutLabel = [];
$sebelum = null;
foreach ($luarLabel as $k) {
    $sudutLabel[$k] = $sebelum !== null ? max($iris[$k]['tengah'], $sebelum + $celah) : $iris[$k]['tengah'];
    $sebelum = $sudutLabel[$k];
}
if ($sudutLabel !== []) {
    $geser = max(array_map(static fn (int $k): float => $sudutLabel[$k] - $iris[$k]['tengah'], $luarLabel)) / 2;
    $sudutLabel = array_map(static fn (float $s): float => $s - $geser, $sudutLabel);
}
?>
<div class="relative mx-auto max-w-sm" data-grafik-wadah>
    <svg viewBox="0 0 400 400" class="w-full overflow-visible" role="img" aria-labelledby="<?= e($idJudul) ?>" data-grafik data-grafik-awal="0" tabindex="0">
        <?php foreach ($iris as $i): ?>
        <path d="<?= $i['d'] ?>" fill="<?= e($i['warna']) ?>" fill-rule="evenodd" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
        <?php endforeach ?>

        <?php foreach ($iris as $k => $i): ?>
        <?php if (isset($sudutLabel[$k])): ?>
        <?php [$ax, $ay] = $titik($luar + 2, $i['tengah']); [$bx, $by] = $titik($luar + 14, $sudutLabel[$k]); [$tx, $ty] = $titik($luar + 28 + 14 * abs(sin($sudutLabel[$k])), $sudutLabel[$k]); ?>
        <path d="M<?= $f($ax) ?> <?= $f($ay) ?>L<?= $f($bx) ?> <?= $f($by) ?>" stroke="#6b7280" stroke-width="1.5" fill="none"/>
        <text x="<?= $f($tx) ?>" y="<?= $f($ty + 5) ?>" text-anchor="middle" font-size="15" font-weight="700" fill="#1f2937"><?= e($i['persen']) ?></text>
        <?php else: ?>
        <?php [$tx, $ty] = $i['bagian'] >= 0.9999 ? [$pusat, $pusat + ($luar + $dalam) / 2] : $titik(($luar + $dalam) / 2, $i['tengah']); ?>
        <text x="<?= $f($tx) ?>" y="<?= $f($ty + 5) ?>" text-anchor="middle" font-size="15" font-weight="700" fill="<?= e($i['teks']) ?>"><?= e($i['persen']) ?></text>
        <?php endif ?>
        <?php endforeach ?>

        <text x="200" y="178" text-anchor="middle" font-size="18" font-weight="600" fill="#1f2937">Total</text>
        <text x="200" y="212" text-anchor="middle" font-size="<?= strlen((string) $total) > 6 ? 26 : 34 ?>" font-weight="800" fill="#1f2937"><?= e(number_format($total, 0, ',', '.')) ?></text>
        <text x="200" y="236" text-anchor="middle" font-size="18" font-weight="600" fill="#1f2937"><?= e(ucfirst($satuan)) ?></text>

        <?php /* Area sentuh transparan di atas irisan — membawa isi tooltip */ ?>
        <?php foreach ($iris as $i): ?>
        <path d="<?= $i['d'] ?>" fill="#111827" fill-opacity="0" fill-rule="evenodd" class="grafik-sentuh" data-judul="<?= e($i['label']) ?>"
              data-baris="<?= e((string) json_encode([[$i['persen'], $i['warna'], number_format($i['nilai'], 0, ',', '.') . ' ' . $satuan]], JSON_UNESCAPED_UNICODE)) ?>"/>
        <?php endforeach ?>
    </svg>
</div>
