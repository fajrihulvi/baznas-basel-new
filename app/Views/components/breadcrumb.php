<?php
/**
 * Breadcrumb + JSON-LD BreadcrumbList. "Beranda" ditambahkan otomatis di depan.
 *
 * @var list<array{label: string, url?: ?string}> $items item terakhir = halaman aktif (tanpa url)
 */
$items = [['label' => 'Beranda', 'url' => '/'], ...$items];
$schema = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => array_map(static fn (array $item, int $i): array => array_filter([
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $item['label'],
        'item'     => !empty($item['url']) ? url($item['url']) : url(current_path()),
    ]), $items, array_keys($items)),
];
?>
<nav aria-label="Breadcrumb" class="text-sm">
    <ol class="flex flex-wrap items-center gap-1 text-muted">
        <?php foreach ($items as $i => $item): ?>
        <li class="flex min-w-0 items-center gap-1">
            <?php if (!empty($item['url']) && $i < count($items) - 1): ?>
            <a href="<?= e(url($item['url'])) ?>" class="py-1 hover:text-primary hover:underline"><?= e($item['label']) ?></a>
            <?= icon('chevron-right', 'size-4 shrink-0') ?>
            <?php elseif ($i < count($items) - 1): ?>
            <span><?= e($item['label']) ?></span>
            <?= icon('chevron-right', 'size-4 shrink-0') ?>
            <?php else: ?>
            <span aria-current="page" class="line-clamp-1 font-medium text-ink"><?= e($item['label']) ?></span>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ol>
</nav>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
