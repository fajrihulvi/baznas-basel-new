<?php
/**
 * /tentang/{slug} dan /halaman/{slug} — halaman statis dari tabel `halaman` (konten tersanitasi).
 *
 * @var App\Core\View $this
 * @var array       $halaman  id, grup, judul, slug, konten, updated_at, gambar, gambar_w, gambar_h (gambar tampil di atas isi)
 * @var list<array> $saudara  halaman terbit lain di grup yang sama (judul, slug)
 * @var array|null  $penerima laporan penerima manfaat (hanya PenerimaManfaat::SLUG_HALAMAN), di bawah isi
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

    <div class="min-w-0 max-w-3xl<?= count($navigasi) > 1 ? '' : ' lg:col-span-2' ?>">
        <?php if (!empty($halaman['gambar'])): ?>
        <?php /* Gambar halaman (admin → Halaman → Gambar kartu) di atas isi, rasio asli, maks. ±512px di layar lebar */ ?>
        <figure class="mb-6 max-w-lg overflow-hidden rounded-2xl bg-surface lg:mb-8">
            <?= img($halaman['gambar'], 'halaman', $halaman['judul'], ['width' => $halaman['gambar_w'], 'height' => $halaman['gambar_h'],
                'sizes' => '(min-width: 1024px) 512px, calc(100vw - 2rem)', 'eager' => true, 'class' => 'h-auto w-full']) ?>
        </figure>
        <?php endif ?>
        <div class="konten text-ink">
            <?= $halaman['konten'] /* HTML dari admin — wajib disanitasi saat disimpan (Fase 7) */ ?>
        </div>
    </div>
</div>

<?php if (!empty($penerima) && $penerima['total'] > 0): ?>
<section id="penerima-manfaat" class="mx-auto max-w-6xl scroll-mt-16 px-4 pb-10 lg:pb-14" aria-labelledby="judul-penerima">
    <div class="border-t border-gray-200 pt-8">
        <h2 id="judul-penerima" class="text-2xl font-bold text-ink lg:text-3xl">Laporan Penerima Manfaat</h2>
        <p class="mt-1 text-muted">Akumulasi jumlah penerima manfaat program BAZNAS Kabupaten Bangka Selatan.</p>
        <?= $this->insert('components/filter-penerima', ['m' => $penerima, 'aksi' => current_path(), 'jangkar' => 'penerima-manfaat']) ?>
        <div class="mt-5"><?= $this->insert('components/laporan-penerima', ['m' => $penerima, 'id' => 'penerima']) ?></div>
    </div>
</section>
<?php $this->start('scripts') ?>
<script type="module" src="<?= e(asset('js/grafik.js')) ?>"></script>
<?php $this->stop() ?>
<?php endif ?>
