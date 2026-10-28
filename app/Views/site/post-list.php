<?php
/**
 * /berita — daftar kabar dengan filter jenis & pagination.
 *
 * @var App\Core\View $this
 * @var string|null   $jenis
 * @var list<array>   $posts
 * @var App\Core\Paginator $paginator
 */

use App\Controllers\Site\PostController;

$labelJenis = PostController::FILTER[$jenis ?? ''] ?? 'Semua';
$judul = $jenis === null ? 'Kabar BAZNAS' : $labelJenis;
$this->layout('site', [
    'title'       => $judul . ($paginator->page > 1 ? ' — Halaman ' . $paginator->page : ''),
    'description' => 'Berita, artikel, dan kisah penyaluran zakat (Berkah Zakat) dari BAZNAS Kabupaten Bangka Selatan.',
    'canonical'   => $paginator->url($paginator->page),
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => $judul,
    'subjudul'   => 'Kabar terbaru penghimpunan dan penyaluran zakat di Bangka Selatan.',
    'breadcrumb' => $jenis === null ? [['label' => 'Kabar']] : [['label' => 'Kabar', 'url' => '/berita'], ['label' => $labelJenis]],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <?= $this->insert('components/chip-tabs', [
            'label' => 'Jenis kabar',
            'items' => array_map(static fn (string $kunci, string $label): array => [
                'label' => $label,
                'url'   => $kunci === '' ? '/berita' : '/berita?jenis=' . $kunci,
                'aktif' => $kunci === ($jenis ?? ''),
            ], array_keys(PostController::FILTER), PostController::FILTER),
        ]) ?>
        <form action="<?= e(url('cari')) ?>" method="get" role="search" class="flex w-full gap-2 lg:w-80">
            <label for="cari-kabar" class="sr-only">Cari kabar</label>
            <input id="cari-kabar" type="search" name="q" placeholder="Cari kabar…" minlength="3" required
                   class="h-11 min-w-0 flex-1 rounded-full border border-gray-300 bg-white px-4 text-base focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
            <button type="submit" class="grid size-11 shrink-0 place-items-center rounded-full bg-primary text-white hover:bg-primary-dark" aria-label="Cari"><?= icon('search', 'size-5') ?></button>
        </form>
    </div>

    <div class="mt-6">
        <?php if ($posts === []): ?>
        <?= $this->insert('components/empty-state', ['judul' => 'Belum ada ' . mb_strtolower($labelJenis === 'Semua' ? 'kabar' : $labelJenis), 'ikon' => 'newspaper', 'aksi' => $jenis !== null ? ['label' => 'Lihat semua kabar', 'href' => '/berita'] : null]) ?>
        <?php else: ?>
        <?= $this->insert('components/grid-post', ['posts' => $posts, 'eagerPertama' => true]) ?>
        <?php endif ?>
    </div>

    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
</div>
