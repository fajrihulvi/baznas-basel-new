<?php
/**
 * Kotak ikon satu pilar program: ikon unggahan admin (atau ikon bawaan per slug) di atas kotak bersudut bulat
 * berwarna sesuai admin → 5 Pilar (warna_latar + opasitas_latar). Dipakai card-program & deret ikon pilar di beranda.
 *
 * @var array{slug: string, ikon: ?string, warna_latar?: ?string, opasitas_latar?: ?int} $pilar
 * @var string $kotak          kelas ukuran kotak, mis. 'size-14'
 * @var string $ukuranIkon     kelas ukuran ikon bawaan (SVG), mis. 'size-7'
 * @var string $ukuranUnggahan kelas ukuran ikon unggahan, mis. 'size-9'
 * @var string $sizes          atribut sizes ikon unggahan, mis. '36px'
 */
$ikonPilar = [
    'basel-peduli' => 'hand-heart',
    'basel-cerdas' => 'graduation',
    'basel-sehat'  => 'stethoscope',
    'basel-taqwa'  => 'mosque',
    'basel-makmur' => 'store',
];
// Warna kotak dari admin (5 Pilar): #rrggbb + kepekatan 0–100%. Tanpa warna → bawaan:
// ikon unggahan (biasanya berwarna) di atas hijau muda, ikon bawaan putih di atas hijau BAZNAS.
$gayaKotak = '';
$kelasKotak = !empty($pilar['ikon']) ? 'bg-primary/10' : 'bg-primary text-white';
if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i', (string) ($pilar['warna_latar'] ?? ''), $w)) {
    [$r, $g, $b] = array_map('hexdec', array_slice($w, 1));
    $persen = max(0, min(100, $pilar['opasitas_latar'] ?? 100));
    $gayaKotak = sprintf('background-color: rgb(%d %d %d / %d%%)', $r, $g, $b, $persen);
    // Ikon bawaan (garis currentColor): putih bila latar cukup pekat & gelap, selain itu hijau
    $gelap = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255 < 0.55 && $persen >= 50;
    $kelasKotak = $gelap ? 'text-white' : 'text-primary';
}
?>
<span class="grid <?= e($kotak) ?> shrink-0 place-items-center rounded-2xl <?= $kelasKotak ?>"<?= $gayaKotak !== '' ? ' style="' . e($gayaKotak) . '"' : '' ?>>
    <?php if (!empty($pilar['ikon'])): ?>
    <?= img($pilar['ikon'], 'pilar', '', ['sizes' => $sizes, 'class' => $ukuranUnggahan . ' object-contain']) ?>
    <?php else: ?>
    <?= icon($ikonPilar[$pilar['slug']] ?? 'grid', $ukuranIkon) ?>
    <?php endif ?>
</span>
