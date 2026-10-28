<?php
/**
 * /cari?q= — hasil pencarian (tidak diindeks mesin pencari).
 *
 * @var App\Core\View $this
 * @var string        $q
 * @var bool          $valid     q ≥ 3 karakter
 * @var list<array>   $posts
 * @var list<array>   $zakat
 * @var App\Core\Paginator $paginator
 */

use App\Controllers\Site\CariController;

$this->layout('site', [
    'title'   => $q !== '' ? 'Pencarian “' . mb_substr($q, 0, 40) . '”' : 'Pencarian',
    'noindex' => true,
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Pencarian',
    'breadcrumb' => [['label' => 'Pencarian']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <form action="<?= e(url('cari')) ?>" method="get" role="search" class="flex max-w-2xl gap-2">
        <label for="q" class="sr-only">Kata kunci</label>
        <input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari kabar, program, atau jenis zakat…"
               minlength="<?= CariController::MIN_KARAKTER ?>" required
               class="h-12 min-w-0 flex-1 rounded-full border border-gray-300 bg-white px-5 text-base focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
        <button type="submit" class="inline-flex h-12 shrink-0 items-center gap-2 rounded-full bg-primary px-5 font-bold text-white hover:bg-primary-dark"><?= icon('search', 'size-5') ?><span class="hidden sm:inline">Cari</span><span class="sr-only sm:hidden">Cari</span></button>
    </form>

    <div class="mt-8" role="status">
        <?php if ($q === ''): ?>
        <p class="text-muted">Ketik kata kunci untuk mencari kabar dan penjelasan zakat.</p>
        <?php elseif (!$valid): ?>
        <p class="text-muted">Kata kunci minimal <?= CariController::MIN_KARAKTER ?> karakter.</p>
        <?php else: ?>
        <p class="text-muted"><strong class="text-ink"><?= $paginator->total + count($zakat) ?></strong> hasil untuk “<?= e($q) ?>”</p>
        <?php endif ?>
    </div>

    <?php if ($zakat !== []): ?>
    <section class="mt-6" aria-labelledby="hasil-zakat">
        <h2 id="hasil-zakat" class="text-sm font-bold uppercase tracking-wide text-muted">Jenis zakat</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($zakat as $z): ?>
            <li><a href="<?= e(url('zakat/' . $z['slug'])) ?>" class="inline-flex h-10 items-center gap-2 rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold hover:border-primary hover:text-primary"><?= icon('book', 'size-4 text-primary') ?><?= e($z['judul']) ?></a></li>
            <?php endforeach ?>
        </ul>
    </section>
    <?php endif ?>

    <?php if ($posts !== []): ?>
    <section class="mt-8" aria-labelledby="hasil-kabar">
        <h2 id="hasil-kabar" class="mb-3 text-sm font-bold uppercase tracking-wide text-muted">Kabar</h2>
        <?= $this->insert('components/grid-post', ['posts' => $posts, 'heading' => 'h3']) ?>
    </section>
    <?php elseif ($valid && $zakat === []): ?>
    <div class="mt-6">
        <?= $this->insert('components/empty-state', ['judul' => 'Tidak ditemukan', 'pesan' => 'Coba kata kunci lain, misalnya “fitrah”, “beasiswa”, atau “kursi roda”.', 'ikon' => 'search', 'aksi' => ['label' => 'Lihat semua kabar', 'href' => '/berita']]) ?>
    </div>
    <?php endif ?>

    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
</div>
