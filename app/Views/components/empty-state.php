<?php
/**
 * @var App\Core\View $this
 * @var string      $judul
 * @var string|null $pesan
 * @var string|null $ikon  default "info"
 * @var array{label: string, href: string}|null $aksi
 */
?>
<div class="rounded-2xl border border-dashed border-gray-300 bg-surface px-6 py-12 text-center">
    <span class="mx-auto grid size-14 place-items-center rounded-full bg-white text-primary shadow-sm"><?= icon($ikon ?? 'info', 'size-7') ?></span>
    <p class="mt-4 font-bold text-ink"><?= e($judul) ?></p>
    <?php if (!empty($pesan)): ?>
    <p class="mx-auto mt-1 max-w-md text-sm text-muted"><?= e($pesan) ?></p>
    <?php endif ?>
    <?php if (!empty($aksi)): ?>
    <div class="mt-5"><?= $this->insert('components/button', ['label' => $aksi['label'], 'href' => $aksi['href'], 'variant' => 'outline', 'size' => 'sm']) ?></div>
    <?php endif ?>
</div>
