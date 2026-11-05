<?php
/**
 * Kartu layanan "Tunaikan Zakat": gambar (opsional) di atas judul, deskripsi singkat, tautan selengkapnya.
 * Tautan WhatsApp (wa.me) selalu memakai nomor WhatsApp di Pengaturan, dan nomornya ditampilkan di kartu (mis. Jemput Zakat).
 *
 * @var array{judul: string, deskripsi: ?string, link: string, gambar?: ?string, gambar_w?: ?int, gambar_h?: ?int} $layanan
 * @var string|null $heading tag judul (default h3)
 */
$link = $layanan['link'];
$wa = null;
if (preg_match('#^https?://(?:api\.)?wa\.me/(\d*)(.*)$#i', $link, $m)) {
    $nomor = preg_replace('/\D/', '', (string) setting('whatsapp')) ?: $m[1];
    $link = 'https://wa.me/' . $nomor . $m[2];
    $wa = $nomor !== '' ? $nomor : null;
}
$eksternal = (bool) preg_match('#^https?://#i', $link);
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-primary/40 hover:shadow-md">
    <?php if (!empty($layanan['gambar'])): ?>
    <div class="aspect-square overflow-hidden bg-surface">
        <?= img($layanan['gambar'], 'layanan', '', ['sizes' => '(min-width: 1024px) 270px, 50vw', 'class' => 'size-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:transform-none',
            'width' => $layanan['gambar_w'] ?? null, 'height' => $layanan['gambar_h'] ?? null]) ?>
    </div>
    <?php else: ?>
    <?php /* Tanpa gambar: bidang kosong setinggi gambar agar judul keempat kartu tetap sejajar */ ?>
    <div class="aspect-square bg-primary/5" aria-hidden="true"></div>
    <?php endif ?>
    <div class="flex flex-1 flex-col p-4 lg:p-5">
        <<?= $h ?> class="font-bold leading-snug text-ink group-hover:text-primary">
            <a href="<?= e(url($link)) ?>" class="after:absolute after:inset-0"<?= $eksternal ? ' target="_blank" rel="noopener"' : '' ?>><?= e($layanan['judul']) ?></a>
        </<?= $h ?>>
        <?php if (!empty($layanan['deskripsi'])): ?>
        <p class="mt-1 line-clamp-3 text-sm text-muted"><?= e($layanan['deskripsi']) ?></p>
        <?php endif ?>
        <?php if ($wa !== null): ?>
        <p class="mt-2 text-sm font-semibold text-ink" style="font-variant-numeric: tabular-nums">WhatsApp <span class="whitespace-nowrap"><?= e(nomor_hp($wa)) ?></span></p>
        <?php endif ?>
        <span class="mt-auto inline-flex items-center gap-1 pt-3 text-sm font-semibold text-primary">
            <?= $eksternal ? 'Hubungi' : 'Selengkapnya' ?> <?= icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-0.5') ?>
        </span>
    </div>
</article>
