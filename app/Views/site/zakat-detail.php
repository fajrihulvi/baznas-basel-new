<?php
/**
 * /zakat/{slug} — penjelasan satu jenis zakat + CTA bayar. Konten disanitasi di ZakatService.
 *
 * @var App\Core\View $this
 * @var array       $zakat  id, judul, slug, konten, ringkasan, foto, id_jenis_zakat, tgl
 * @var list<array> $lain
 */
$tautanBayar = $zakat['id_jenis_zakat'] ? '/bayar-zakat?jenis=' . $zakat['id_jenis_zakat'] : '/bayar-zakat';
$this->layout('site', [
    'title'       => $zakat['judul'],
    'description' => ringkas($zakat['konten']),
    'image'       => $zakat['foto'] ? url('uploads/zakat/' . rawurlencode($zakat['foto'])) : null,
    'type'        => 'article',
]);
?>
<div class="mx-auto max-w-6xl px-4 py-6 lg:py-10">
    <?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Zakat', 'url' => '/zakat'], ['label' => $zakat['judul']]]]) ?>

    <div class="mt-4 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-12">
        <article>
            <h1 class="text-2xl font-bold leading-tight text-ink lg:text-4xl"><?= e($zakat['judul']) ?></h1>
            <?php if ($zakat['foto']): ?>
            <figure class="mt-6 overflow-hidden rounded-2xl bg-surface">
                <?= img($zakat['foto'], 'zakat', '', ['eager' => true, 'sizes' => '(min-width: 1024px) 768px, calc(100vw - 2rem)', 'class' => 'h-auto w-full']) ?>
            </figure>
            <?php endif ?>
            <div class="konten mt-6 text-ink">
                <?= $zakat['konten'] /* HTML tersanitasi (ZakatService) */ ?>
            </div>
        </article>

        <aside class="lg:sticky lg:top-24 lg:self-start" aria-label="Tunaikan zakat">
            <div class="rounded-2xl border border-gray-200 bg-surface p-6">
                <p class="text-lg font-bold text-ink">Tunaikan <?= e($zakat['judul']) ?></p>
                <p class="mt-1 text-sm text-muted">Disalurkan untuk mustahik di Bangka Selatan melalui lima pilar program.</p>
                <div class="mt-5 flex flex-col gap-2">
                    <?= $this->insert('components/button', ['label' => 'Bayar Sekarang', 'href' => $tautanBayar, 'icon' => 'heart', 'class' => 'w-full']) ?>
                    <?= $this->insert('components/button', ['label' => 'Hitung di Kalkulator', 'href' => '/kalkulator-zakat', 'variant' => 'outline', 'icon' => 'calculator', 'class' => 'w-full']) ?>
                    <?= $this->insert('components/button', ['label' => 'Rekening Resmi', 'href' => '/rekening', 'variant' => 'ghost', 'icon' => 'bank', 'class' => 'w-full']) ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php if ($lain !== []): ?>
<nav aria-labelledby="judul-zakat-lain" class="border-t border-gray-200 bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h2 id="judul-zakat-lain" class="text-sm font-bold uppercase tracking-wide text-muted">Jenis zakat lainnya</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($lain as $z): ?>
            <li><a href="<?= e(url('zakat/' . $z['slug'])) ?>" class="inline-flex h-10 items-center rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold hover:border-primary hover:text-primary"><?= e($z['judul']) ?></a></li>
            <?php endforeach ?>
        </ul>
    </div>
</nav>
<?php endif ?>
