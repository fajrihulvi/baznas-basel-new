<?php
/**
 * /testimoni — semua testimoni terbit.
 *
 * @var App\Core\View $this
 * @var list<array>   $testimoni
 */
$this->layout('site', [
    'title'       => 'Testimoni',
    'description' => 'Pengalaman muzaki dan mustahik bersama BAZNAS Kabupaten Bangka Selatan.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Kata Mereka',
    'subjudul'   => 'Pengalaman muzaki dan mustahik bersama BAZNAS Bangka Selatan.',
    'breadcrumb' => [['label' => 'Tentang Kami', 'url' => '/tentang'], ['label' => 'Testimoni']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php if ($testimoni === []): ?>
    <?= $this->insert('components/empty-state', ['judul' => 'Belum ada testimoni', 'pesan' => 'Cerita dari muzaki dan mustahik akan tampil di sini.', 'ikon' => 'star', 'aksi' => ['label' => 'Baca kisah penyaluran', 'href' => '/berita?jenis=berkah_zakat']]) ?>
    <?php else: ?>
    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <?php foreach ($testimoni as $t): ?>
        <li><?= $this->insert('components/card-testimoni', ['testimoni' => $t]) ?></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
