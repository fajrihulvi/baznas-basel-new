<?php
/**
 * Kartu video dengan facade: hanya thumbnail + tombol play. Tanpa JS = link ke YouTube;
 * dengan JS (Fase 4, lite-yt.js) iframe youtube-nocookie dimuat saat diklik.
 *
 * @var array{judul: string, youtube_id: ?string, tgl?: ?string} $video
 * @var string|null $heading tag judul (default h3)
 */
$id = (string) ($video['youtube_id'] ?? '');
$h = $heading ?? 'h3';
?>
<article class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <a href="https://www.youtube.com/watch?v=<?= e(rawurlencode($id)) ?>" target="_blank" rel="noopener"
       data-yt="<?= e($id) ?>" data-yt-title="<?= e($video['judul']) ?>"
       class="group relative block aspect-video overflow-hidden bg-ink">
        <img src="https://i.ytimg.com/vi/<?= e(rawurlencode($id)) ?>/hqdefault.jpg" alt="" width="480" height="360"
             loading="lazy" decoding="async" class="size-full object-cover opacity-90 transition-opacity group-hover:opacity-100">
        <span class="absolute inset-0 grid place-items-center">
            <span class="grid size-16 place-items-center rounded-full bg-white/95 text-primary shadow-lg transition-transform group-hover:scale-110">
                <?= icon('play', 'size-7 translate-x-0.5') ?>
            </span>
        </span>
        <span class="sr-only">Putar video: <?= e($video['judul']) ?></span>
    </a>
    <div class="p-4">
        <<?= $h ?> class="line-clamp-2 font-bold leading-snug text-ink"><?= e($video['judul']) ?></<?= $h ?>>
        <?php if (!empty($video['tgl'])): ?>
        <p class="mt-1 text-xs text-muted"><time datetime="<?= e($video['tgl']) ?>"><?= e(tgl_id($video['tgl'])) ?></time></p>
        <?php endif ?>
    </div>
</article>
