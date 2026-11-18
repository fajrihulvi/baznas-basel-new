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
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col items-center rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
    <?= $this->insert('components/ikon-pilar', ['pilar' => $pilar, 'kotak' => $kotak, 'ukuranIkon' => $ukuranIkon, 'ukuranUnggahan' => $ukuranUnggahan, 'sizes' => $besar ? '48px' : '36px']) ?>
    <<?= $h ?> class="mt-3 font-bold text-ink group-hover:text-primary">
        <a href="<?= e(url('program/' . $pilar['slug'])) ?>" class="after:absolute after:inset-0"><?= e($pilar['label']) ?></a>
    </<?= $h ?>>
    <p class="text-xs font-semibold uppercase tracking-wide text-primary"><?= e($pilar['nama']) ?></p>
    <?php if (!empty($pilar['deskripsi'])): ?>
    <p class="mt-2 text-sm text-muted"><?= e($pilar['deskripsi']) ?></p>
    <?php endif ?>
</article>
