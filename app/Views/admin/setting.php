<?php
/**
 * @var App\Core\View $this
 * @var array<string, ?string> $nilai
 */

use App\Controllers\Admin\SettingController;
use App\Core\Session;

$this->layout('admin', ['title' => 'Pengaturan']);
$lama = Session::getFlash('_old');
$isi = static function (string $kunci, string $tipe) use ($nilai, $lama): string {
    if (is_array($lama)) {
        return (string) ($lama[$kunci] ?? '');
    }
    $v = (string) ($nilai[$kunci] ?? '');
    return match (true) {
        $tipe === 'rupiah' && $v !== '' => number_format(angka($v), 0, ',', '.'),
        $tipe === 'wa' && $v !== ''     => '0' . substr($v, 2),
        default                          => $v,
    };
};
?>
<h1 class="text-2xl font-bold">Pengaturan</h1>
<p class="mt-1 text-muted">Data ini tampil di header, footer, halaman kontak, kalkulator, dan form bayar.</p>

<form method="post" action="<?= e(url('admin/setting')) ?>" class="mt-6 max-w-3xl space-y-8">
    <?= csrf_field() ?>
    <?php foreach (SettingController::ISIAN as $grup => $isian): ?>
    <fieldset class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
        <legend class="px-2 text-lg font-bold"><?= e($grup) ?></legend>
        <?php foreach ($isian as $kunci => [$label, $tipe, $hint]): ?>
        <?= $this->insert('components/form-field', [
            'name'  => $kunci,
            'label' => $label,
            'type'  => match ($tipe) { 'panjang' => 'textarea', 'email' => 'email', 'url' => 'url', 'wa' => 'tel', default => 'text' },
            'value' => $isi($kunci, $tipe),
            'hint'  => $hint,
            'required' => $kunci === 'site_name',
            'atribut' => $tipe === 'rupiah' ? ['data-rupiah' => '', 'inputmode' => 'numeric', 'placeholder' => 'Rp'] : [],
        ]) ?>
        <?php endforeach ?>
    </fieldset>
    <?php endforeach ?>
    <button type="submit" class="inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Simpan pengaturan</button>
</form>
