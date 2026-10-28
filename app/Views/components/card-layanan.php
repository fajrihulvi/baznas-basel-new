<?php
/**
 * Kartu layanan "Tunaikan Zakat".
 *
 * @var array{judul: string, deskripsi: ?string, ikon: ?string, link: string} $layanan
 * @var string|null $heading tag judul (default h3)
 */
$link = $layanan['link'];
$eksternal = (bool) preg_match('#^https?://#i', $link);

// Ikon default ditebak dari tujuan link bila kolom ikon kosong
$ikon = match (true) {
    str_contains($link, 'wa.me')    => 'whatsapp',
    str_contains($link, 'rekening') => 'bank',
    str_contains($link, 'fitrah')   => 'heart',
    default                         => 'hand-heart',
};
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-accent/20 text-primary-dark">
        <?php if (!empty($layanan['ikon'])): ?>
        <img src="<?= e(url($layanan['ikon'])) ?>" alt="" width="24" height="24" class="size-6" loading="lazy">
        <?php else: ?>
        <?= icon($ikon) ?>
        <?php endif ?>
    </span>
    <div class="min-w-0">
        <<?= $h ?> class="font-bold text-ink group-hover:text-primary">
            <a href="<?= e(url($link)) ?>" class="after:absolute after:inset-0"<?= $eksternal ? ' target="_blank" rel="noopener"' : '' ?>><?= e($layanan['judul']) ?></a>
        </<?= $h ?>>
        <?php if (!empty($layanan['deskripsi'])): ?>
        <p class="mt-1 text-sm text-muted"><?= e($layanan['deskripsi']) ?></p>
        <?php endif ?>
        <span class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary">
            <?= $eksternal ? 'Hubungi' : 'Selengkapnya' ?> <?= icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-0.5') ?>
        </span>
    </div>
</article>
