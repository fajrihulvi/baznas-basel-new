<?php
/**
 * /zakat — penjelasan jenis zakat.
 *
 * @var App\Core\View $this
 * @var list<array> $zakat  id, judul, slug, ringkasan, foto, id_jenis_zakat
 */
$this->layout('site', [
    'title'       => 'Jenis Zakat',
    'description' => 'Penjelasan zakat penghasilan, zakat fitrah, zakat emas, perdagangan, fidyah, infaq, dan sedekah beserta nishab dan cara menghitungnya.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Kenali Zakat',
    'subjudul'   => 'Pahami jenis zakat, nishab, dan kadarnya sebelum menunaikan.',
    'breadcrumb' => [['label' => 'Zakat']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?php if ($zakat === []): ?>
    <?= $this->insert('components/empty-state', ['judul' => 'Belum ada penjelasan zakat', 'ikon' => 'book']) ?>
    <?php else: ?>
    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        <?php foreach ($zakat as $i => $z): ?>
        <li>
            <article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
                <div class="aspect-video overflow-hidden bg-surface">
                    <?= img($z['foto'], 'zakat', '', ['sizes' => '(min-width: 1024px) 368px, (min-width: 640px) 45vw, calc(100vw - 2rem)', 'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105', 'eager' => $i === 0]) ?>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <h2 class="text-lg font-bold text-ink group-hover:text-primary">
                        <a href="<?= e(url('zakat/' . $z['slug'])) ?>" class="after:absolute after:inset-0"><?= e($z['judul']) ?></a>
                    </h2>
                    <p class="mt-2 line-clamp-3 flex-1 text-sm text-muted"><?= e($z['ringkasan']) ?></p>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary">Pelajari <?= icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-0.5') ?></span>
                </div>
            </article>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>

    <div class="mt-10 flex flex-col items-start gap-4 rounded-2xl bg-primary p-6 text-white sm:flex-row sm:items-center sm:justify-between lg:p-8">
        <div>
            <p class="text-lg font-bold lg:text-xl">Sudah tahu berapa zakat Anda?</p>
            <p class="mt-1 text-white/85">Hitung dengan kalkulator, lalu tunaikan langsung secara online.</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <?= $this->insert('components/button', ['label' => 'Kalkulator', 'href' => '/kalkulator-zakat', 'variant' => 'light', 'icon' => 'calculator']) ?>
            <?= $this->insert('components/button', ['label' => 'Bayar Zakat', 'href' => '/bayar-zakat', 'variant' => 'light', 'icon' => 'heart']) ?>
        </div>
    </div>
</div>
