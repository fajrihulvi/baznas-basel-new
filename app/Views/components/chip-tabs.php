<?php
/**
 * Filter berbentuk chip. Tanpa JS = link biasa (filter dikerjakan server).
 * Bila item punya 'tab' dan induknya [data-tabs], tabs.js mengubahnya menjadi tab ARIA
 * yang menampilkan [data-tab-panel="..."] tanpa memuat ulang halaman.
 *
 * @var string $label  aria-label, mis. "Filter kabar"
 * @var list<array{label: string, url: string, aktif: bool, tab?: string}> $items
 */
?>
<nav aria-label="<?= e($label) ?>">
    <ul class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-none lg:mx-0 lg:px-0">
        <?php foreach ($items as $item): ?>
        <li class="shrink-0">
            <a href="<?= e(url($item['url'])) ?>"<?= $item['aktif'] ? ' aria-current="page"' : '' ?><?= isset($item['tab']) ? ' data-tab="' . e($item['tab']) . '"' : '' ?>
               class="inline-flex h-10 items-center rounded-full border px-4 text-sm font-semibold transition-colors <?= $item['aktif']
                   ? 'border-primary bg-primary text-white'
                   : 'border-gray-300 bg-white text-ink hover:border-primary hover:text-primary' ?>"><?= e($item['label']) ?></a>
        </li>
        <?php endforeach ?>
    </ul>
</nav>
