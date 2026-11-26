<?php
/**
 * /tentang — Profil Kami (tujuan menu "Tentang Kami" di panel menu HP): kartu bergambar tiap Halaman kelompok
 * Tentang Kami (tanpa Amil, Testimoni, Kontak). Gambar kartu diatur di admin → Halaman (isian Gambar kartu); kosong = ikon.
 *
 * @var App\Core\View $this
 * @var list<array{label: string, url: string, gambar: ?string, gambar_w: ?int, gambar_h: ?int, ikon: string}> $kartu
 * @var list<array{slug: string, label: string, nama: string, deskripsi: ?string, ikon: ?string, foto: ?string, foto_w: ?int, foto_h: ?int}> $pilar
 *      daftar "Program Kami" (5 pilar): foto dari admin → 5 Pilar (migrasi 017), kosong = kotak ikon pilar
 * @var list<array{nama: string, slug: string, deskripsi: ?string, foto: ?string, foto_w: ?int, foto_h: ?int, pilar: array}> $kegiatan
 *      "Kegiatan Kami" 2 kolom: admin → Kegiatan BAZNAS (tabel sub_program, migrasi 018), tautan ke /program/{pilar}?sub={slug}
 */

use App\Services\Navigation;

$namaSitus = setting('site_name', 'BAZNAS Kabupaten Bangka Selatan');
$this->layout('site', [
    'title'       => 'Profil Kami',
    'description' => 'Profil, kelembagaan, dan tata kelola ' . $namaSitus . '.',
]);

// Ikon hiasan: ikon slot terakhir bilah navigasi bawah (Menu), ikut gambar admin → Ikon Navigasi
$navMenu = array_values(array_filter(Navigation::bawah(), static fn (array $n): bool => $n['url'] === null && $n['svg'] !== null))[0] ?? null;

?>
<section class="mx-auto max-w-4xl px-4 py-8 text-center lg:py-12" aria-labelledby="judul-profil">
    <?php if ($navMenu !== null): ?>
    <div class="flex justify-center" aria-hidden="true">
        <?= $this->insert('partials/ikon-nav', ['nama' => $navMenu['svg'], 'aktif' => false, 'berwarna' => true, 'ukuran' => 'size-20 lg:size-24', 'gambar' => $navMenu['gambar']]) ?>
    </div>
    <?php endif ?>
    <h1 id="judul-profil" class="mt-4 text-3xl font-extrabold text-primary lg:text-4xl">Profil Kami</h1>
    <p class="mt-2 text-base text-ink lg:text-lg">Profil, kelembagaan, dan tata kelola <?= e($namaSitus) ?>.</p>
    <span class="mx-auto mt-5 block h-1 w-24 rounded-full bg-[#ef8a14]" aria-hidden="true"></span>

    <?= $this->insert('components/bagikan', ['tautan' => url('tentang'), 'teks' => 'Profil Kami — ' . $namaSitus, 'id' => 'bagikan-profil']) ?>

    <ul class="mt-8 grid grid-cols-2 gap-3 text-left sm:gap-8">
        <?php foreach ($kartu as $k): ?>
        <li>
            <a href="<?= e(url($k['url'])) ?>" class="group block h-full rounded-xl border border-gray-200 bg-white p-1.5 shadow-md transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg sm:p-2">
                <span class="block aspect-[11/12] overflow-hidden rounded-lg bg-surface">
                    <?php if ($k['gambar']): ?>
                    <?= img($k['gambar'], 'halaman', '', ['width' => $k['gambar_w'], 'height' => $k['gambar_h'], 'sizes' => '(min-width: 640px) 420px, calc(50vw - 2rem)',
                        'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
                    <?php else: ?>
                    <span class="grid size-full place-items-center bg-[linear-gradient(135deg,#0b3a12_0%,#14521a_55%,#1f7a22_100%)] text-white">
                        <?= icon($k['ikon'], 'size-12 stroke-[1.5] sm:size-20') ?>
                    </span>
                    <?php endif ?>
                </span>
                <span class="block px-1 py-3 text-center text-sm font-semibold text-ink group-hover:text-primary sm:px-2 sm:py-4 sm:text-base lg:text-lg"><?= e($k['label']) ?></span>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</section>

<?php if ($pilar !== []): ?>
<section class="cv-auto mx-auto max-w-4xl px-4 pb-10 lg:pb-14" aria-labelledby="judul-program-kami">
    <h2 id="judul-program-kami" class="text-2xl font-bold tracking-wide text-primary uppercase lg:text-3xl">Program Kami</h2>
    <span class="mt-2 block h-1 w-40 bg-[#ef8a14] lg:w-48" aria-hidden="true"></span>

    <ul class="mt-6">
        <?php foreach ($pilar as $p): ?>
        <li class="border-b border-gray-300">
            <a href="<?= e(url('program/' . $p['slug'])) ?>" class="group flex items-start gap-3 py-3 sm:gap-4">
                <?php if (!empty($p['foto'])): ?>
                <span class="block aspect-[3/2] w-36 shrink-0 overflow-hidden rounded-md bg-surface sm:w-48 lg:w-60">
                    <?= img($p['foto'], 'pilar', '', ['width' => $p['foto_w'], 'height' => $p['foto_h'], 'sizes' => '(min-width: 1024px) 240px, (min-width: 640px) 192px, 144px',
                        'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
                </span>
                <?php else: ?>
                <?= $this->insert('components/ikon-pilar', ['pilar' => $p, 'kotak' => 'aspect-[3/2] w-36 rounded-md! sm:w-48 lg:w-60',
                    'ukuranIkon' => 'size-10 sm:size-14 lg:size-16', 'ukuranUnggahan' => 'size-14 sm:size-20 lg:size-24', 'sizes' => '(min-width: 1024px) 96px, (min-width: 640px) 80px, 64px']) ?>
                <?php endif ?>
                <span class="min-w-0">
                    <span class="block text-lg font-medium text-ink group-hover:text-primary group-hover:underline lg:text-xl"><?= e($p['label']) ?></span>
                    <span class="mt-1 line-clamp-3 text-sm text-muted lg:text-base"><?= e($p['deskripsi'] ?: 'Bidang ' . $p['nama']) ?></span>
                </span>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<?= $this->insert('components/kegiatan-baznas', ['kegiatan' => $kegiatan, 'judul' => 'Kegiatan Kami', 'id' => 'judul-kegiatan-kami']) ?>
