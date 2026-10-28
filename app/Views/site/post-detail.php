<?php
/**
 * /berita/{slug} — detail kabar. Konten sudah disanitasi saat disimpan (HtmlSanitizer).
 *
 * @var App\Core\View $this
 * @var array $post
 * @var list<array> $terkait
 */

use App\Controllers\Site\PostController;

$label   = PostController::FILTER[$post['jenis']] ?? ($post['jenis'] === 'program' ? 'Program' : 'Kabar');
$tautan  = url('berita/' . $post['slug']);
$deskripsi = $post['ringkasan'] ?: ringkas($post['konten']);
$fotoUrl = $post['foto'] ? url('uploads/post/' . rawurlencode($post['foto'])) : null;
$namaSitus = setting('site_name', (string) config('app.name'));

$this->layout('site', [
    'title'       => $post['judul'],
    'description' => $deskripsi,
    'image'       => $fotoUrl,
    'type'        => 'article',
    'canonical'   => $tautan,
    'jsonLd'      => [array_filter([
        '@context'         => 'https://schema.org',
        '@type'            => 'NewsArticle',
        'headline'         => mb_substr($post['judul'], 0, 110),
        'description'      => $deskripsi,
        'image'            => $fotoUrl ? [$fotoUrl] : null,
        'datePublished'    => $post['tgl'],
        'dateModified'     => $post['updated_at'] ? substr($post['updated_at'], 0, 10) : $post['tgl'],
        'author'           => ['@type' => 'Organization', 'name' => $namaSitus, 'url' => url()],
        'publisher'        => ['@type' => 'Organization', 'name' => $namaSitus, 'logo' => ['@type' => 'ImageObject', 'url' => url('assets/img/icon-512.png')]],
        'mainEntityOfPage' => $tautan,
    ])],
]);

$bagikan = rawurlencode($post['judul'] . ' — ' . $tautan);
?>
<article class="mx-auto max-w-3xl px-4 py-6 lg:py-10">
    <?= $this->insert('components/breadcrumb', ['items' => [
        ['label' => 'Kabar', 'url' => '/berita'],
        ['label' => $label, 'url' => in_array($post['jenis'], ['berita', 'artikel', 'berkah_zakat', 'event'], true) ? '/berita?jenis=' . $post['jenis'] : null],
        ['label' => $post['judul']],
    ]]) ?>

    <header class="mt-4">
        <div class="flex flex-wrap items-center gap-2">
            <?= $this->insert('components/badge', ['label' => $label]) ?>
            <?php if (!empty($post['pilar'])): ?>
            <a href="<?= e(url('program/' . $post['pilar_slug'], $post['sub_slug'] ? ['sub' => $post['sub_slug']] : [])) ?>"
               class="inline-flex items-center rounded-full bg-accent/20 px-2.5 py-0.5 text-xs font-semibold text-primary-dark hover:underline"><?= e($post['pilar']) ?><?= $post['sub_program'] ? ' · ' . e($post['sub_program']) : '' ?></a>
            <?php endif ?>
        </div>
        <h1 class="mt-3 text-2xl font-bold leading-tight text-ink lg:text-4xl"><?= e($post['judul']) ?></h1>
        <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
            <span class="inline-flex items-center gap-1.5"><?= icon('calendar', 'size-4') ?><time datetime="<?= e($post['tgl']) ?>"><?= e(tgl_id($post['tgl'])) ?></time></span>
            <?php if (!empty($post['penulis'])): ?>
            <span class="inline-flex items-center gap-1.5"><?= icon('users', 'size-4') ?><?= e($post['penulis']) ?></span>
            <?php endif ?>
        </p>
    </header>

    <?php if ($post['foto']): ?>
    <figure class="mt-6 overflow-hidden rounded-2xl bg-surface">
        <?= img($post['foto'], 'post', $post['judul'], [
            'width' => $post['foto_w'], 'height' => $post['foto_h'], 'eager' => true,
            'sizes' => '(min-width: 800px) 736px, calc(100vw - 2rem)',
            'class' => 'h-auto w-full',
        ]) ?>
    </figure>
    <?php endif ?>

    <div class="konten mt-6 text-ink">
        <?= $post['konten'] /* HTML tersanitasi (HtmlSanitizer) */ ?>
    </div>

    <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-gray-200 pt-6">
        <span class="mr-1 text-sm font-semibold text-ink">Bagikan:</span>
        <a href="https://wa.me/?text=<?= e($bagikan) ?>" target="_blank" rel="noopener" class="grid size-11 place-items-center rounded-full bg-surface text-primary hover:bg-primary hover:text-white" aria-label="Bagikan ke WhatsApp"><?= icon('whatsapp', 'size-5') ?></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($tautan)) ?>" target="_blank" rel="noopener" class="grid size-11 place-items-center rounded-full bg-surface text-primary hover:bg-primary hover:text-white" aria-label="Bagikan ke Facebook"><?= icon('facebook', 'size-5') ?></a>
        <a href="https://twitter.com/intent/tweet?url=<?= e(rawurlencode($tautan)) ?>&amp;text=<?= e(rawurlencode($post['judul'])) ?>" target="_blank" rel="noopener" class="grid size-11 place-items-center rounded-full bg-surface text-primary hover:bg-primary hover:text-white" aria-label="Bagikan ke X"><?= icon('twitter', 'size-5') ?></a>
        <button type="button" data-salin="<?= e($tautan) ?>" data-salin-pesan="Tautan disalin" hidden
                class="inline-flex h-11 items-center gap-2 rounded-full bg-surface px-4 text-sm font-semibold text-primary hover:bg-primary hover:text-white"><?= icon('copy', 'size-5') ?>Salin tautan</button>
    </div>
</article>

<div class="border-t border-gray-200 bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-10 lg:py-14">
        <?php if ($terkait !== []): ?>
        <section aria-labelledby="judul-terkait">
            <?= $this->insert('components/section-title', ['judul' => 'Kabar terkait', 'id' => 'judul-terkait', 'link' => '/berita']) ?>
            <?= $this->insert('components/grid-post', ['posts' => $terkait, 'heading' => 'h3']) ?>
        </section>
        <?php endif ?>
        <div class="mt-10 flex flex-col items-start gap-4 rounded-2xl bg-primary p-6 text-white sm:flex-row sm:items-center sm:justify-between lg:p-8">
            <div>
                <p class="text-lg font-bold lg:text-xl">Ikut menebar manfaat seperti kisah ini</p>
                <p class="mt-1 text-white/85">Zakat, infaq, dan sedekah Anda disalurkan untuk mustahik di Bangka Selatan.</p>
            </div>
            <?= $this->insert('components/button', ['label' => 'Bayar Zakat', 'href' => '/bayar-zakat', 'variant' => 'light', 'icon' => 'heart', 'class' => 'shrink-0']) ?>
        </div>
    </div>
</div>
