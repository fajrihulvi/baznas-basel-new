<?php
/**
 * Tombol / link bergaya tombol.
 *
 * @var string      $label
 * @var string|null $href     ada = <a>, null = <button>
 * @var string|null $variant  primary (default) | outline | ghost | light
 * @var string|null $size     sm | md (default) | lg
 * @var string|null $icon     nama ikon di kanan label
 * @var string|null $type     untuk <button>: submit (default) | button
 * @var string|null $class    kelas tambahan (mis. w-full)
 * @var bool|null   $external buka di tab baru
 */
$variants = [
    'primary' => 'bg-primary text-white hover:bg-primary-dark',
    'outline' => 'border-2 border-primary text-primary hover:bg-primary hover:text-white',
    'ghost'   => 'text-primary hover:bg-primary/10',
    'light'   => 'bg-white text-primary hover:bg-surface',
];
$sizes = [
    'sm' => 'min-h-10 px-4 text-sm',
    'md' => 'min-h-12 px-5',
    'lg' => 'min-h-14 px-7 text-lg',
];
$kelas = 'inline-flex items-center justify-center gap-2 rounded-full font-bold transition-colors disabled:opacity-60 '
    . ($variants[$variant ?? 'primary'] ?? $variants['primary']) . ' '
    . ($sizes[$size ?? 'md'] ?? $sizes['md']) . ' ' . ($class ?? '');
$isi = e($label) . (!empty($icon) ? icon($icon, ($size ?? 'md') === 'sm' ? 'size-4' : 'size-5') : '');
?>
<?php if (isset($href)): ?>
<a href="<?= e(url($href)) ?>" class="<?= e(trim($kelas)) ?>"<?= !empty($external) ? ' target="_blank" rel="noopener"' : '' ?>><?= $isi ?></a>
<?php else: ?>
<button type="<?= e($type ?? 'submit') ?>" class="<?= e(trim($kelas)) ?>"><?= $isi ?></button>
<?php endif ?>
