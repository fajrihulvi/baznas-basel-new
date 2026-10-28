<?php
/**
 * @var string      $label
 * @var string|null $variant primary (default) | accent | muted | putih
 */
$variants = [
    'primary' => 'bg-primary/10 text-primary',
    'accent'  => 'bg-accent text-ink',
    'muted'   => 'bg-gray-100 text-gray-700',
    'putih'   => 'bg-white/95 text-primary shadow-sm',
];
?>
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $variants[$variant ?? 'primary'] ?? $variants['primary'] ?>"><?= e($label) ?></span>
