<?php
/**
 * Kartu rekening resmi + tombol salin nomor (angka saja, siap ditempel di aplikasi bank).
 *
 * @var array{id: int, nama_bank: string, nomor_rekening: string, atas_nama: string, logo: ?string} $rekening
 * @var string|null $heading tag nama bank (default h2)
 */
$r = $rekening;
$h = $heading ?? 'h2';
?>
<div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-3">
        <?php if ($r['logo']): ?>
        <span class="grid h-12 w-20 shrink-0 place-items-center rounded-lg border border-gray-200 bg-white p-1.5">
            <?= img($r['logo'], 'rekening', '', ['sizes' => '80px', 'class' => 'max-h-9 w-auto object-contain']) ?>
        </span>
        <?php endif ?>
        <<?= $h ?> class="font-bold leading-snug text-ink"><?= e(trim($r['nama_bank'])) ?></<?= $h ?>>
    </div>
    <p class="mt-4 font-mono text-2xl font-bold tracking-wide text-primary"><?= e($r['nomor_rekening']) ?></p>
    <p class="mt-1 text-sm text-muted">a.n. <?= e(trim($r['atas_nama'])) ?></p>
    <button type="button" hidden data-salin="<?= e(preg_replace('/\D/', '', $r['nomor_rekening'])) ?>" data-salin-pesan="Nomor rekening <?= e(trim($r['nama_bank'])) ?> disalin"
            class="mt-4 inline-flex h-11 items-center justify-center gap-2 self-start rounded-full border-2 border-primary px-4 text-sm font-bold text-primary hover:bg-primary hover:text-white">
        <?= icon('copy', 'size-4') ?>Salin nomor
    </button>
</div>
