<?php
/** @var App\Core\View $this */

use App\Services\Navigation;
?>
<?php /* Semua ikon selalu berwarna asli (bukan keabu-abuan saat tidak aktif); halaman aktif ditandai garis atas & label tebal */ ?>
<nav aria-label="Navigasi bawah" class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white pb-[calc(0.75rem+env(safe-area-inset-bottom))] lg:hidden">
    <?php /* Selebar layar (container-fluid): item lebar tetap + justify-between → ikon pertama & terakhir mepet ke tepi,
             tombol tengah tetap persis di tengah layar (susunan simetris) */ ?>
    <ul class="flex h-20 w-full justify-between px-1">
        <?php foreach (Navigation::bawah() as $item): ?>
        <li class="flex w-14 shrink-0">
            <?php if ($item['url'] === null): ?>
            <button type="button" data-sheet-open aria-controls="menu-sheet" aria-haspopup="dialog" aria-expanded="false"
                    class="group flex w-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-muted hover:text-primary">
                <?= $item['svg'] !== null ? $this->insert('partials/ikon-nav', ['nama' => $item['svg'], 'aktif' => false, 'berwarna' => true, 'ukuran' => 'size-12', 'gambar' => $item['gambar']]) : icon($item['ikon']) ?>
                <?= e($item['label']) ?>
            </button>
            <?php elseif ($item['tengah']): ?>
            <?php
            /* Tombol tengah: lingkaran hijau bergradasi (gelap → terang) menyembul di atas bilah; ikon & label putih di dalamnya.
               SVG bawaan tampil putih (bagian hijaunya tak terlihat di atas hijau), koin tetap kuning; gambar unggahan admin tetap berwarna asli. */
            ?>
            <a href="<?= e(url($item['url'])) ?>" <?= $item['aktif'] ? 'aria-current="page"' : '' ?>
               class="group relative flex w-full justify-center">
                <span class="absolute bottom-1 flex size-[92px] flex-col items-center justify-center gap-0.5 rounded-full bg-[linear-gradient(180deg,#0b3a12_0%,#14521a_55%,#1f7a22_100%)] pt-1 text-center text-[11px] leading-tight font-semibold text-white shadow-[0_-4px_12px_rgb(0_0_0/0.18)] transition group-hover:brightness-110 group-active:scale-95 motion-reduce:transition-none<?= $item['aktif'] ? ' ring-4 ring-accent/70' : '' ?>">
                    <?php if ($item['svg'] !== null): ?>
                    <?= $this->insert('partials/ikon-nav', ['nama' => $item['svg'], 'aktif' => false, 'berwarna' => $item['gambar'] !== null, 'ukuran' => 'size-11', 'gambar' => $item['gambar']]) ?>
                    <?php else: ?>
                    <?= icon($item['ikon'], 'size-9') ?>
                    <?php endif ?>
                    <span><?= implode('<br>', array_map('e', explode(' ', $item['label'], 2))) ?></span>
                </span>
            </a>
            <?php else: ?>
            <a href="<?= e(url($item['url'])) ?>" <?= $item['aktif'] ? 'aria-current="page"' : '' ?>
               class="group relative flex w-full flex-col items-center justify-center gap-0.5 text-[11px] <?= $item['aktif'] ? 'font-bold text-primary' : 'font-medium text-muted hover:text-primary' ?>">
                <?php if ($item['aktif']): ?><span class="absolute top-0 h-1 w-8 rounded-b-full bg-primary" aria-hidden="true"></span><?php endif ?>
                <?= $item['svg'] !== null ? $this->insert('partials/ikon-nav', ['nama' => $item['svg'], 'aktif' => $item['aktif'], 'berwarna' => true, 'ukuran' => 'size-12', 'gambar' => $item['gambar']]) : icon($item['ikon']) ?>
                <?= e($item['label']) ?>
            </a>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
</nav>
