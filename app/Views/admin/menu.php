<?php
/**
 * @var array<string, array<string, string>> $menu  grup => [kunci => label]
 * @var Closure(string): bool $aktif
 * @var int    $antrean konfirmasi baru
 * @var string $path
 */
$kelas = static fn (bool $on): string => 'flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm '
    . ($on ? 'bg-primary/10 font-bold text-primary' : 'font-medium text-ink hover:bg-surface hover:text-primary');
?>
<ul class="space-y-5 px-3 pb-6 lg:pt-6">
    <li>
        <a href="<?= e(url('admin')) ?>" class="<?= $kelas($path === '/admin') ?>"<?= $path === '/admin' ? ' aria-current="page"' : '' ?>><?= icon('home', 'size-5') ?>Dasbor</a>
    </li>
    <?php foreach ($menu as $grup => $item): ?>
    <li>
        <p class="mb-1 px-3 text-xs font-bold uppercase tracking-wide text-muted"><?= e($grup) ?></p>
        <ul class="space-y-0.5">
            <?php foreach ($item as $kunci => $label): ?>
            <li>
                <a href="<?= e(url('admin/' . $kunci)) ?>" class="<?= $kelas($aktif($kunci)) ?>"<?= $aktif($kunci) ? ' aria-current="page"' : '' ?>>
                    <?= e($label) ?>
                    <?php if ($kunci === 'pembayaran' && $antrean > 0): ?>
                    <span class="ml-auto rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-ink"><?= $antrean ?><span class="sr-only"> konfirmasi baru</span></span>
                    <?php endif ?>
                </a>
            </li>
            <?php endforeach ?>
        </ul>
    </li>
    <?php endforeach ?>
</ul>
