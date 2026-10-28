<?php
/**
 * /program — 5 pilar + kegiatan terbaru.
 *
 * @var App\Core\View $this
 * @var list<array> $pilar
 * @var list<array> $kegiatan
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
    <ul class="grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-4">
        <?php foreach ($pilar as $i => $p): ?>
        <li class="<?= $i === count($pilar) - 1 && count($pilar) % 2 === 1 ? 'col-span-2 lg:col-span-1' : '' ?>">
            <?= $this->insert('components/card-program', ['pilar' => $p, 'heading' => 'h2']) ?>
        </li>
        <?php endforeach ?>
    </ul>
</div>

<?php if ($kegiatan !== []): ?>
<section class="cv-auto border-t border-gray-200 bg-surface" aria-labelledby="judul-kegiatan">
    <div class="mx-auto max-w-6xl px-4 py-10 lg:py-14">
        <?= $this->insert('components/section-title', ['judul' => 'Kegiatan terbaru', 'id' => 'judul-kegiatan', 'subjudul' => 'Penyaluran dari kelima pilar program.']) ?>
        <?= $this->insert('components/grid-post', ['posts' => $kegiatan, 'heading' => 'h3']) ?>
    </div>
</section>
<?php endif ?>
