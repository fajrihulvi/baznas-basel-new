<?php
/**
 * /video — galeri video (facade YouTube).
 *
 * @var App\Core\View $this
 * @var list<array>   $video
 * @var App\Core\Paginator $paginator
 */
$this->layout('site', [
    'title'       => 'Video' . ($paginator->page > 1 ? ' — Halaman ' . $paginator->page : ''),
    'description' => 'Video kisah penyaluran zakat, infaq, dan sedekah BAZNAS Kabupaten Bangka Selatan.',
    'canonical'   => $paginator->url($paginator->page),
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Video',
    'subjudul'   => 'Kisah penyaluran zakat dari lapangan.',
    'breadcrumb' => [['label' => 'Kabar', 'url' => '/berita'], ['label' => 'Video']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php if ($video === []): ?>
    <?= $this->insert('components/empty-state', ['judul' => 'Belum ada video', 'ikon' => 'video']) ?>
    <?php else: ?>
    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <?php foreach ($video as $v): ?>
        <li><?= $this->insert('components/card-video', ['video' => $v, 'heading' => 'h2']) ?></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>

    <?php if ($yt = setting('youtube')): ?>
    <p class="mt-10 text-center">
        <a href="<?= e($yt) ?>" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center gap-2 rounded-full border-2 border-primary px-5 font-bold text-primary hover:bg-primary hover:text-white">
            <?= icon('youtube', 'size-5') ?>Kunjungi kanal YouTube kami
        </a>
    </p>
    <?php endif ?>
</div>

<?php $this->start('scripts') ?>
    <script type="module" src="<?= e(asset('js/lite-yt.js')) ?>"></script>
<?php $this->stop() ?>
