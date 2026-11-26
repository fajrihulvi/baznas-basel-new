<?php
/**
 * /program — 5 pilar (2 kolom di HP), kartu Kegiatan BAZNAS, lalu kabar kegiatan terbaru.
 * Tujuan menu "Program" di bilah navigasi bawah & panel menu HP.
 *
 * @var App\Core\View $this
 * @var list<array> $pilar
 * @var list<array> $kegiatanBaznas  SiteService::kegiatan()
 * @var list<array> $kegiatan        kabar terbaru dari kelima pilar
 */
$this->layout('site', [
    'title'       => 'Program Kami',
    'description' => 'Lima pilar program BAZNAS Kabupaten Bangka Selatan: Basel Peduli, Basel Cerdas, Basel Sehat, Basel Taqwa, dan Basel Makmur.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Program Kami',
    'subjudul'   => 'Zakat, infaq, dan sedekah Anda disalurkan melalui lima pilar program untuk masyarakat Bangka Selatan.',
    'breadcrumb' => [['label' => 'Program']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php /* 1 kolom di HP, 2 di tablet, 3 di laptop; kartu bergambar foto pilar */ ?>
    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <?php foreach ($pilar as $p): ?>
        <li><?= $this->insert('components/card-program', ['pilar' => $p, 'heading' => 'h2']) ?></li>
        <?php endforeach ?>
    </ul>
</div>

<?= $this->insert('components/kegiatan-baznas', ['kegiatan' => $kegiatanBaznas, 'judul' => 'Kegiatan BAZNAS', 'id' => 'judul-kegiatan-baznas']) ?>

<?php if ($kegiatan !== []): ?>
<section class="cv-auto border-t border-gray-200 bg-surface" aria-labelledby="judul-kegiatan">
    <div class="mx-auto max-w-6xl px-4 py-10 lg:py-14">
        <?= $this->insert('components/section-title', ['judul' => 'Kegiatan terbaru', 'id' => 'judul-kegiatan', 'subjudul' => 'Penyaluran dari kelima pilar program.']) ?>
        <?= $this->insert('components/grid-post', ['posts' => $kegiatan, 'heading' => 'h3']) ?>
    </div>
</section>
<?php endif ?>
