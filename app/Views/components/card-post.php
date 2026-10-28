<?php
/**
 * Kartu kabar: gambar 16:9, badge jenis, tanggal, judul 2 baris, ringkasan opsional.
 *
 * @var App\Core\View $this
 * @var array{judul: string, slug: string, jenis: string, tgl: string, foto: ?string, foto_w: ?int, foto_h: ?int, ringkasan?: ?string} $post
 * @var string|null $sizes     atribut sizes gambar (default untuk kartu carousel/grid)
 * @var bool|null   $ringkasan tampilkan ringkasan
 * @var string|null $heading   tag judul (default h3)
 * @var bool|null   $eager     true untuk kartu pertama di daftar (kandidat LCP)
 */
$jenisLabel = ['berita' => 'Berita', 'artikel' => 'Artikel', 'berkah_zakat' => 'Berkah Zakat', 'program' => 'Program', 'event' => 'Event'];
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
    <div class="relative aspect-video overflow-hidden bg-surface">
        <?= img($post['foto'], 'post', $post['judul'], [
            'width'  => $post['foto_w'] ?? null,
            'height' => $post['foto_h'] ?? null,
            'sizes'  => $sizes ?? '(min-width: 1024px) 280px, (min-width: 640px) 45vw, 80vw',
            'class'  => 'size-full object-cover transition-transform duration-300 group-hover:scale-105',
            'eager'  => !empty($eager),
        ]) ?>
        <span class="absolute top-3 left-3"><?= $this->insert('components/badge', ['label' => $jenisLabel[$post['jenis']] ?? 'Kabar', 'variant' => 'putih']) ?></span>
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="flex items-center gap-1.5 text-xs text-muted">
            <?= icon('calendar', 'size-4') ?><time datetime="<?= e($post['tgl']) ?>"><?= e(tgl_id($post['tgl'])) ?></time>
        </p>
        <<?= $h ?> class="mt-1.5 line-clamp-2 font-bold leading-snug text-ink group-hover:text-primary">
            <a href="<?= e(url('berita/' . $post['slug'])) ?>" class="after:absolute after:inset-0"><?= e($post['judul']) ?></a>
        </<?= $h ?>>
        <?php if (!empty($ringkasan) && !empty($post['ringkasan'])): ?>
        <p class="mt-2 line-clamp-3 text-sm text-muted"><?= e($post['ringkasan']) ?></p>
        <?php endif ?>
    </div>
</article>
