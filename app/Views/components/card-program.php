<?php
/**
 * Kartu pilar program (tanpa efek flip): ikon + label + deskripsi singkat.
 *
 * @var array{label: string, nama: string, slug: string, deskripsi: ?string, ikon: ?string, warna_latar?: ?string, opasitas_latar?: ?int} $pilar
 * @var string|null $heading tag judul (default h3)
 * @var bool|null   $besar   ikon 1,3× (beranda): kotak 72px, ikon bawaan 36px, ikon unggahan 48px
 */
$besar = !empty($besar);
[$kotak, $ukuranIkon, $ukuranUnggahan] = $besar ? ['size-18', 'size-9', 'size-12'] : ['size-14', 'size-7', 'size-9'];
$ikonPilar = [
    'basel-peduli' => 'hand-heart',
    'basel-cerdas' => 'graduation',
    'basel-sehat'  => 'stethoscope',
    'basel-taqwa'  => 'mosque',
    'basel-makmur' => 'store',
];
$h = $heading ?? 'h3';

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
<article class="group relative flex h-full flex-col items-center rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
    <span class="grid <?= $kotak ?> place-items-center rounded-2xl <?= $kelasKotak ?>"<?= $gayaKotak !== '' ? ' style="' . e($gayaKotak) . '"' : '' ?>>
        <?php if (!empty($pilar['ikon'])): ?>
        <?= img($pilar['ikon'], 'pilar', '', ['sizes' => $besar ? '48px' : '36px', 'class' => $ukuranUnggahan . ' object-contain']) ?>
        <?php else: ?>
        <?= icon($ikonPilar[$pilar['slug']] ?? 'grid', $ukuranIkon) ?>
        <?php endif ?>
    </span>
    <<?= $h ?> class="mt-3 font-bold text-ink group-hover:text-primary">
        <a href="<?= e(url('program/' . $pilar['slug'])) ?>" class="after:absolute after:inset-0"><?= e($pilar['label']) ?></a>
    </<?= $h ?>>
    <p class="text-xs font-semibold uppercase tracking-wide text-primary"><?= e($pilar['nama']) ?></p>
    <?php if (!empty($pilar['deskripsi'])): ?>
    <p class="mt-2 text-sm text-muted"><?= e($pilar['deskripsi']) ?></p>
    <?php endif ?>
</article>
