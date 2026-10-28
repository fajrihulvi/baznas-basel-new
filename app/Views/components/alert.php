<?php
/**
 * @var string      $pesan
 * @var string|null $jenis sukses | error | peringatan | info (default)
 * @var string|null $judul
 */
$gaya = [
    'sukses'     => ['border-green-200 bg-green-50 text-green-900', 'check-circle', 'text-green-700'],
    'error'      => ['border-red-200 bg-red-50 text-red-900', 'alert', 'text-red-700'],
    'peringatan' => ['border-amber-200 bg-amber-50 text-amber-900', 'alert', 'text-amber-700'],
    'info'       => ['border-sky-200 bg-sky-50 text-sky-900', 'info', 'text-sky-700'],
];
[$kelas, $ikon, $warnaIkon] = $gaya[$jenis ?? 'info'] ?? $gaya['info'];
$penting = in_array($jenis ?? 'info', ['error', 'peringatan'], true);
?>
<div class="flex gap-3 rounded-2xl border p-4 text-sm <?= $kelas ?>" role="<?= $penting ? 'alert' : 'status' ?>">
    <?= icon($ikon, 'size-5 shrink-0 ' . $warnaIkon) ?>
    <div>
        <?php if (!empty($judul)): ?><p class="font-bold"><?= e($judul) ?></p><?php endif ?>
        <p><?= e($pesan) ?></p>
    </div>
</div>
