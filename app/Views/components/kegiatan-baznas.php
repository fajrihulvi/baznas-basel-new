<?php
/**
 * Bagian kartu Kegiatan BAZNAS (tabel sub_program, admin → Program → Kegiatan BAZNAS) 2 kolom:
 * foto persegi (atau kotak ikon pilar), nama, deskripsi maks. 2 baris → /program/{pilar}?sub={slug}.
 * Dipakai /tentang ("Kegiatan Kami"), /layanan, dan /program. Data: SiteService::kegiatan().
 *
 * @var App\Core\View $this
 * @var list<array{nama: string, slug: string, deskripsi: ?string, foto: ?string, foto_w: ?int, foto_h: ?int, pilar: array}> $kegiatan
 * @var string $judul  judul bagian, mis. "Kegiatan Kami"
 * @var string $id     id judul (aria-labelledby)
 */
if ($kegiatan === []) {
    return;
}
?>
<section class="cv-auto mx-auto max-w-4xl px-4 pb-10 lg:pb-14" aria-labelledby="<?= e($id) ?>">
    <h2 id="<?= e($id) ?>" class="text-2xl font-bold tracking-wide text-primary uppercase lg:text-3xl"><?= e($judul) ?></h2>
    <span class="mt-2 block h-1 w-40 bg-[#ef8a14] lg:w-48" aria-hidden="true"></span>

    <ul class="mt-6 grid grid-cols-2 gap-x-3 gap-y-6 sm:gap-x-8 sm:gap-y-8">
        <?php foreach ($kegiatan as $k): ?>
        <li>
            <a href="<?= e(url('program/' . $k['pilar']['slug']) . '?sub=' . rawurlencode($k['slug'])) ?>" class="group block">
                <?php if (!empty($k['foto'])): ?>
                <span class="block aspect-square overflow-hidden rounded-xl bg-surface">
                    <?= img($k['foto'], 'kegiatan', '', ['width' => $k['foto_w'], 'height' => $k['foto_h'], 'sizes' => '(min-width: 896px) 420px, calc(50vw - 1.5rem)',
                        'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
                </span>
                <?php else: ?>
                <?= $this->insert('components/ikon-pilar', ['pilar' => $k['pilar'], 'kotak' => 'aspect-square w-full rounded-xl!',
                    'ukuranIkon' => 'size-14 sm:size-24', 'ukuranUnggahan' => 'size-20 sm:size-36', 'sizes' => '(min-width: 640px) 144px, 80px']) ?>
                <?php endif ?>
                <span class="mt-2 block text-base font-medium text-ink group-hover:text-primary group-hover:underline sm:text-lg"><?= e($k['nama']) ?></span>
                <?php if ($k['deskripsi'] !== null): ?>
                <span class="mt-0.5 line-clamp-2 text-sm text-muted"><?= e($k['deskripsi']) ?></span>
                <?php endif ?>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</section>
