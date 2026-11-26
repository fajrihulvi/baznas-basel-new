<?php
/**
 * Kartu pilar program (/program): foto pilar 3:2 di atas (admin → 5 Pilar → Foto program; tanpa foto = kotak
 * warna + ikon pilar seukuran gambar), lalu nama, bidang, dan deskripsi maks. 3 baris.
 *
 * @var App\Core\View $this
 * @var array{label: string, nama: string, slug: string, deskripsi: ?string, ikon: ?string, foto?: ?string, foto_w?: ?int, foto_h?: ?int, warna_latar?: ?string, opasitas_latar?: ?int} $pilar
 * @var string|null $heading tag judul (default h3)
 */
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
    <?php if (!empty($pilar['foto'])): ?>
    <span class="block aspect-[3/2] overflow-hidden bg-surface">
        <?= img($pilar['foto'], 'pilar', '', ['width' => $pilar['foto_w'] ?? null, 'height' => $pilar['foto_h'] ?? null,
            'sizes' => '(min-width: 1024px) 370px, (min-width: 640px) 50vw, 100vw',
            'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
    </span>
    <?php else: ?>
    <?= $this->insert('components/ikon-pilar', ['pilar' => $pilar, 'kotak' => 'aspect-[3/2] w-full rounded-none!',
        'ukuranIkon' => 'size-16', 'ukuranUnggahan' => 'size-24', 'sizes' => '96px']) ?>
    <?php endif ?>
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary"><?= e($pilar['nama']) ?></p>
        <<?= $h ?> class="mt-1 text-lg font-bold text-ink group-hover:text-primary">
            <a href="<?= e(url('program/' . $pilar['slug'])) ?>" class="after:absolute after:inset-0"><?= e($pilar['label']) ?></a>
        </<?= $h ?>>
        <?php if (!empty($pilar['deskripsi'])): ?>
        <p class="mt-2 line-clamp-3 text-sm text-muted"><?= e($pilar['deskripsi']) ?></p>
        <?php endif ?>
    </div>
</article>
