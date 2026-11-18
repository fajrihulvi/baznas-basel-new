<?php
/**
 * Header dua tingkat (pola BAZNAS kabupaten):
 *  1. Baris putih — logo, nama situs & tagline, "Ikuti" + media sosial, tombol Cari. Ikut tergulir.
 *  2. Bilah gelap (hitam → hijau tua) — menu utama + Bayar Zakat & Konfirmasi (kuning). Lengket di atas.
 * Isi menu tetap dari config/menu.php (Navigation); di HP menu dibuka lewat panel kiri (partials/menu-sheet).
 *
 * @var App\Core\View $this
 */

use App\Services\Navigation;

$menu = Navigation::utama();
$wa = preg_replace('/\D/', '', (string) setting('whatsapp'));
// Urutan & warna merek; warna dipilih agar ikon putih tetap ≥ 3:1 (WhatsApp/Twitter memakai varian lebih gelap)
$sosial = array_filter([
    'facebook'  => [setting('facebook'), 'Facebook', 'bg-[#1877f2]'],
    'twitter'   => [setting('twitter'), 'X (Twitter)', 'bg-[#111827]'],
    'youtube'   => [setting('youtube'), 'YouTube', 'bg-[#cc0000]'],
    'instagram' => [setting('instagram'), 'Instagram', 'bg-[#c13584]'],
    'tiktok'    => [setting('tiktok'), 'TikTok', 'bg-[#111827]'],
    'whatsapp'  => [$wa !== '' ? 'https://wa.me/' . $wa : null, 'WhatsApp', 'bg-[#128c7e]'],
], static fn (array $s): bool => !empty($s[0]));
// Ukuran xs: tinggi 32px (target sentuh tetap ≥ 24px, WCAG 2.2 AA)
$tombolKuning = 'inline-flex h-8 items-center whitespace-nowrap rounded-md bg-accent px-2.5 text-xs font-semibold text-ink shadow-sm hover:bg-[#ffc933] sm:px-3';
?>
<header class="bg-white">
    <?php /* Selebar layar (container-fluid): hanya jarak tepi, tanpa batas lebar */ ?>
    <div class="flex w-full items-center gap-3 px-4 py-2.5 lg:px-8 lg:py-3 2xl:px-12">
        <a href="<?= e(url()) ?>" class="flex min-w-0 shrink-0 items-center gap-4 rounded-lg lg:gap-5">
            <?php /* alt kosong: nama tautan dari teks di sebelahnya (di HP teksnya hanya untuk pembaca layar — logo sudah memuat nama) */ ?>
            <?= $this->insert('partials/logo', ['class' => 'h-14 w-auto shrink-0 sm:h-16 lg:h-20', 'sizes' => '(min-width: 1024px) 116px, (min-width: 640px) 93px, 81px', 'alt' => '']) ?>
            <span class="sr-only min-w-0 font-roboto md:not-sr-only md:block">
                <span class="block text-2xl font-extrabold leading-tight tracking-tight text-primary lg:text-3xl">Baznas Kab. Bangka Selatan</span>
                <span class="mt-1 block text-base leading-tight text-ink lg:text-lg">Amanah, Professional, Transparan</span>
            </span>
        </a>

        <div class="ml-auto flex flex-col items-end gap-2">
            <?php if ($sosial !== []): ?>
            <div class="flex items-center gap-1.5 sm:gap-2">
                <span class="hidden text-base text-muted min-[400px]:inline lg:text-lg" id="ikuti-kami">Ikuti</span>
                <ul class="flex items-center gap-1.5" aria-labelledby="ikuti-kami">
                    <?php foreach ($sosial as $kunci => [$link, $nama, $warna]): ?>
                    <li>
                        <a href="<?= e(url($link)) ?>" target="_blank" rel="noopener" aria-label="<?= e($nama) ?>"
                           class="grid size-[30px] place-items-center rounded-full text-white transition hover:-translate-y-0.5 hover:shadow-md <?= $warna ?>"><?= icon($kunci, 'size-4') ?></a>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>
            <a href="<?= e(url('cari')) ?>" class="hidden h-8 items-center gap-1.5 rounded-md border-2 border-primary px-2.5 text-xs font-semibold text-primary hover:bg-primary hover:text-white lg:inline-flex">
                <?= icon('search', 'size-3.5') ?>Cari
            </a>
        </div>
    </div>
</header>

<?php /* Bilah menu: lengket di atas saat digulir (baris putih di atas ikut tergulir) */ ?>
<div class="on-dark sticky top-0 z-40 bg-[#0b3a12] font-montserrat text-white shadow-md lg:bg-[linear-gradient(90deg,#000_0%,#06180a_45%,#163f12_100%)]">
    <div class="flex h-14 w-full items-center gap-2 px-4 sm:gap-3 lg:px-8 2xl:px-12">
        <?php /* HP: tombol Menu membuka panel kiri (isi sama dengan menu Bottom Navigation) */ ?>
        <button type="button" data-sheet-open aria-controls="menu-sheet" aria-haspopup="dialog" aria-expanded="false"
                class="-ml-2 inline-flex h-11 shrink-0 items-center gap-2 rounded-md px-2 text-base font-bold hover:bg-white/10 lg:hidden">
            <?= icon('menu', 'size-6') ?><span class="sr-only min-[400px]:not-sr-only">Menu</span>
        </button>

        <nav aria-label="Menu utama" class="hidden lg:block">
            <ul class="flex items-center">
                <?php foreach ($menu as $item): ?>
                <li>
                    <details class="group relative" data-dropdown>
                        <summary class="flex h-14 cursor-pointer items-center gap-2 px-4 font-semibold transition-colors hover:text-white <?= $item['aktif'] ? 'text-white' : 'text-[#9be27a]' ?>">
                            <?= e($item['label']) ?>
                            <?= icon('caret', 'size-3.5 transition-transform group-open:rotate-180') ?>
                        </summary>
                        <ul class="absolute left-0 top-full z-50 w-60 rounded-b-xl border border-gray-200 bg-white p-2 text-ink shadow-lg">
                            <?php foreach ($item['anak'] as $anak): ?>
                            <li>
                                <a href="<?= e(url($anak['url'])) ?>"
                                   class="block rounded-lg px-3 py-2 text-sm hover:bg-surface hover:text-primary<?= current_path() === $anak['url'] ? ' bg-surface font-semibold text-primary' : '' ?>"
                                   <?= current_path() === $anak['url'] ? 'aria-current="page"' : '' ?>><?= e($anak['label']) ?></a>
                            </li>
                            <?php endforeach ?>
                        </ul>
                    </details>
                </li>
                <?php endforeach ?>
            </ul>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
            <a href="<?= e(url('bayar-zakat')) ?>" class="<?= $tombolKuning ?>">Bayar Zakat</a>
            <a href="<?= e(url('konfirmasi')) ?>" class="<?= $tombolKuning ?>">Konfirmasi</a>
            <a href="<?= e(url('cari')) ?>" class="-mr-2 grid size-11 shrink-0 place-items-center rounded-md hover:bg-white/10 lg:hidden" aria-label="Cari"><?= icon('search') ?></a>
        </div>
    </div>
</div>
