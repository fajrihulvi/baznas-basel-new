<?php
/**
 * Hasil kalkulator (versi server; zakat.js membuat markup yang sama di browser).
 *
 * @var array{rincian: list<array{0: string, 1: string}>, wajib: ?bool, zakat: int, pesan: string} $hasil
 * @var int    $idJenis  jenis_zakat tujuan tombol "Tunaikan" (0 = tanpa tombol)
 * @var string $kunci    penghasilan | emas | perdagangan | fitrah | fidyah
 * @var array  $input
 */
$query = ['jenis' => $idJenis];
if ($kunci === 'fitrah' || $kunci === 'fidyah') {
    $query['jumlah'] = angka($input[$kunci === 'fitrah' ? 'jiwa' : 'hari'] ?? '');
}
$query['nominal'] = $hasil['zakat'];
?>
<p class="text-sm font-semibold uppercase tracking-wide text-muted">Hasil perhitungan</p>
<dl class="mt-3 space-y-2 text-sm">
    <?php foreach ($hasil['rincian'] as [$label, $nilaiRincian]): ?>
    <div class="flex justify-between gap-4"><dt class="text-muted"><?= e($label) ?></dt><dd class="text-right font-semibold text-ink"><?= e($nilaiRincian) ?></dd></div>
    <?php endforeach ?>
</dl>
<p class="mt-4 text-sm font-semibold <?= $hasil['wajib'] === false ? 'text-amber-800' : 'text-primary' ?>"><?= e($hasil['pesan']) ?></p>
<p class="mt-1 text-3xl font-bold text-ink"><?= e(format_rupiah($hasil['zakat'])) ?></p>
<?php if ($hasil['zakat'] > 0 && $idJenis > 0): ?>
<a href="<?= e(url('bayar-zakat', $query)) ?>" class="mt-5 inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-5 font-bold text-white hover:bg-primary-dark"><?= icon('heart', 'size-5') ?>Tunaikan sekarang</a>
<?php elseif ($hasil['wajib'] === false): ?>
<a href="<?= e(url('bayar-zakat')) ?>" class="mt-5 inline-flex min-h-12 items-center gap-2 rounded-full border-2 border-primary px-5 font-bold text-primary hover:bg-primary hover:text-white">Berinfaq / bersedekah</a>
<?php endif ?>
