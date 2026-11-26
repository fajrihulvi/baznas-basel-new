<?php
/**
 * /donasi — semua program donasi terbit (berjalan → segera dibuka → selesai).
 *
 * @var App\Core\View $this
 * @var list<array>   $program DonasiService::program()
 */
$this->layout('site', [
    'title'       => 'Program Donasi',
    'description' => 'Program donasi BAZNAS Kabupaten Bangka Selatan: salurkan bantuan Anda melalui rekening resmi.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Program Donasi',
    'subjudul'   => 'Salurkan kepedulian Anda untuk program bantuan BAZNAS Kabupaten Bangka Selatan.',
    'breadcrumb' => [['label' => 'Program Donasi']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php if ($program === []): ?>
    <?= $this->insert('components/empty-state', ['judul' => 'Belum ada program donasi', 'pesan' => 'Program donasi akan tampil di sini saat dibuka.', 'ikon' => 'hand-heart',
        'aksi' => ['label' => 'Bayar zakat', 'href' => '/bayar-zakat']]) ?>
    <?php else: ?>
    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <?php foreach ($program as $p): ?>
        <li><?= $this->insert('components/card-donasi', ['program' => $p, 'heading' => 'h2']) ?></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
