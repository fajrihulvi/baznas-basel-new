<?php
/**
 * /tentang/{slug} dan /halaman/{slug} — halaman statis dari tabel `halaman` (konten tersanitasi).
 *
 * @var App\Core\View $this
 * @var array       $halaman  id, grup, judul, slug, konten, updated_at
 * @var list<array> $saudara  halaman terbit lain di grup yang sama (judul, slug)
 */
$tentang = $halaman['grup'] === 'tentang';
$awalan  = $tentang ? '/tentang/' : '/halaman/';
$this->layout('site', [
    'title'       => $halaman['judul'],
    'description' => ringkas($halaman['konten']),
]);

$navigasi = array_map(static fn (array $h): array => ['label' => $h['judul'], 'url' => $awalan . $h['slug']], $saudara);
if ($tentang) {
    $navigasi[] = ['label' => 'Amil BAZNAS', 'url' => '/amil'];
    $navigasi[] = ['label' => 'Testimoni', 'url' => '/testimoni'];
}
?>
<?= $this->insert('components/page-header', [
    'judul'      => $halaman['judul'],
    'breadcrumb' => [['label' => $tentang ? 'Tentang Kami' : 'Informasi'], ['label' => $halaman['judul']]],
]) ?>

<div class="mx-auto grid max-w-6xl gap-8 px-4 py-8 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-12 lg:py-12">
    <?php if (count($navigasi) > 1): ?>
    <nav aria-label="<?= $tentang ? 'Tentang kami' : 'Informasi' ?>" class="min-w-0 lg:sticky lg:top-24 lg:self-start">
        <ul class="-mx-4 flex gap-2 overflow-x-auto px-4 scrollbar-none lg:mx-0 lg:flex-col lg:gap-1 lg:px-0">
            <?php foreach ($navigasi as $n): ?>
            <?php $aktif = $n['url'] === current_path(); ?>
            <li class="shrink-0">
                <a href="<?= e(url($n['url'])) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>
                   class="flex min-h-10 items-center rounded-full px-4 text-sm font-semibold lg:rounded-xl <?= $aktif ? 'bg-primary text-white' : 'border border-gray-300 text-ink hover:border-primary hover:text-primary lg:border-transparent lg:hover:bg-surface' ?>"><?= e($n['label']) ?></a>
            </li>
            <?php endforeach ?>
        </ul>
    </nav>
    <?php endif ?>

    <div class="konten max-w-3xl text-ink<?= count($navigasi) > 1 ? '' : ' lg:col-span-2' ?>">
        <?= $halaman['konten'] /* HTML dari admin — wajib disanitasi saat disimpan (Fase 7) */ ?>
    </div>
</div>
