<?php
/**
 * /galeri — foto, poster, infografis (?jenis=).
 *
 * @var App\Core\View $this
 * @var string|null   $jenis
 * @var list<array>   $galeri  id, jenis, judul, foto, foto_w, foto_h, tgl
 * @var App\Core\Paginator $paginator
 */
$label = ['foto' => 'Foto', 'poster' => 'Poster', 'infografis' => 'Infografis'];
$this->layout('site', [
    'title'       => 'Galeri' . ($jenis ? ' ' . $label[$jenis] : '') . ($paginator->page > 1 ? ' — Halaman ' . $paginator->page : ''),
    'description' => 'Galeri foto kegiatan, poster, dan infografis BAZNAS Kabupaten Bangka Selatan.',
    'canonical'   => $paginator->url($paginator->page),
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Galeri',
    'subjudul'   => 'Dokumentasi kegiatan, poster, dan infografis.',
    'breadcrumb' => [['label' => 'Kabar', 'url' => '/berita'], ['label' => 'Galeri']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?= $this->insert('components/chip-tabs', [
        'label' => 'Jenis galeri',
        'items' => [
            ['label' => 'Semua', 'url' => '/galeri', 'aktif' => $jenis === null],
            ...array_map(static fn (string $k, string $l): array => ['label' => $l, 'url' => '/galeri?jenis=' . $k, 'aktif' => $jenis === $k], array_keys($label), $label),
        ],
    ]) ?>

    <div class="mt-6">
        <?php if ($galeri === []): ?>
        <?= $this->insert('components/empty-state', ['judul' => 'Galeri masih kosong', 'pesan' => 'Foto kegiatan akan tampil di sini setelah diunggah.', 'ikon' => 'image', 'aksi' => ['label' => 'Lihat kabar terbaru', 'href' => '/berita']]) ?>
        <?php else: ?>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 lg:gap-4">
            <?php foreach ($galeri as $i => $g): ?>
            <li>
                <figure class="group overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <a href="<?= e(url('uploads/galeri/' . rawurlencode($g['foto']))) ?>" target="_blank" rel="noopener" class="block aspect-square overflow-hidden bg-surface">
                        <?= img($g['foto'], 'galeri', $g['judul'], [
                            'width' => $g['foto_w'], 'height' => $g['foto_h'], 'eager' => $i < 2,
                            'sizes' => '(min-width: 1024px) 276px, (min-width: 640px) 31vw, 46vw',
                            'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105',
                        ]) ?>
                    </a>
                    <figcaption class="p-3">
                        <span class="line-clamp-2 text-sm font-semibold text-ink"><?= e($g['judul']) ?></span>
                        <time datetime="<?= e($g['tgl']) ?>" class="text-xs text-muted"><?= e(tgl_id($g['tgl'])) ?></time>
                    </figcaption>
                </figure>
            </li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
    </div>
    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
</div>
