<?php
/**
 * /program/{pilar} — kegiatan satu pilar, filter sub-program (?sub=).
 *
 * @var App\Core\View $this
 * @var array       $pilar       id, nama, label, slug, deskripsi, ikon
 * @var list<array> $subProgram  id, nama, slug
 * @var array|null  $subAktif
 * @var list<array> $posts
 * @var App\Core\Paginator $paginator
 */

use App\Services\SiteService;

$this->layout('site', [
    'title'       => $pilar['label'] . ($subAktif ? ' — ' . $subAktif['nama'] : '') . ($paginator->page > 1 ? ' — Halaman ' . $paginator->page : ''),
    'description' => $pilar['label'] . ' (' . $pilar['nama'] . '): ' . ($pilar['deskripsi'] ?? 'program BAZNAS Kabupaten Bangka Selatan.'),
    'canonical'   => $paginator->url($paginator->page),
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => $pilar['label'],
    'subjudul'   => trim(($pilar['nama'] ? 'Program ' . mb_strtolower($pilar['nama']) . '. ' : '') . ($pilar['deskripsi'] ?? '')),
    'breadcrumb' => [['label' => 'Program', 'url' => '/program'], ['label' => $pilar['label']]],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php if (count($subProgram) > 1): ?>
    <?= $this->insert('components/chip-tabs', [
        'label' => 'Sub-program',
        'items' => [
            ['label' => 'Semua', 'url' => '/program/' . $pilar['slug'], 'aktif' => $subAktif === null],
            ...array_map(static fn (array $s): array => [
                'label' => $s['nama'],
                'url'   => '/program/' . $pilar['slug'] . '?sub=' . $s['slug'],
                'aktif' => $subAktif !== null && $subAktif['id'] === $s['id'],
            ], $subProgram),
        ],
    ]) ?>
    <?php endif ?>

    <div class="mt-6">
        <?php if ($posts === []): ?>
        <?= $this->insert('components/empty-state', ['judul' => 'Belum ada kegiatan', 'pesan' => 'Kegiatan program ini akan tampil di sini setelah dipublikasikan.', 'ikon' => 'grid', 'aksi' => ['label' => 'Lihat semua program', 'href' => '/program']]) ?>
        <?php else: ?>
        <?= $this->insert('components/grid-post', ['posts' => $posts, 'eagerPertama' => true]) ?>
        <?php endif ?>
    </div>

    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
</div>

<nav aria-labelledby="judul-pilar-lain" class="border-t border-gray-200 bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h2 id="judul-pilar-lain" class="text-sm font-bold uppercase tracking-wide text-muted">Pilar lainnya</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            <?php foreach (SiteService::pilar() as $p): ?>
            <?php if ($p['slug'] !== $pilar['slug']): ?>
            <li><a href="<?= e(url('program/' . $p['slug'])) ?>" class="inline-flex h-10 items-center rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold hover:border-primary hover:text-primary"><?= e($p['label']) ?></a></li>
            <?php endif ?>
            <?php endforeach ?>
        </ul>
    </div>
</nav>
