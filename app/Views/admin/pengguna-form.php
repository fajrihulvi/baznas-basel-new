<?php
/**
 * Form tambah/ubah pengguna admin.
 *
 * @var App\Core\View $this
 * @var array|null    $baris        null = tambah
 * @var bool          $diriSendiri  mengubah akun sendiri → peran & status dikunci
 */

use App\Controllers\Admin\PenggunaController;
use App\Core\Auth;
use App\Core\Session;

$baru = $baris === null;
$judul = $baru ? 'Tambah pengguna' : 'Ubah pengguna';
$this->layout('admin', ['title' => $judul]);
$aksi = $baru ? 'admin/pengguna' : 'admin/pengguna/' . (int) $baris['id'];
$adaLama = is_array(Session::getFlash('_old'));
$nilai = static fn (string $k, string $bawaan = ''): string => old($k, $baris !== null ? (string) ($baris[$k] ?? '') : $bawaan);
$aktif = $adaLama ? old('aktif') !== '' : ($baris === null || $baris['status'] !== '0');
?>
<nav aria-label="Breadcrumb" class="text-sm text-muted">
    <a href="<?= e(url('admin/pengguna')) ?>" class="hover:text-primary hover:underline">Pengguna</a> › <span aria-current="page"><?= e($judul) ?></span>
</nav>
<h1 class="mt-2 text-2xl font-bold"><?= e($judul) ?></h1>
<?php if (!$baru): ?>
<p class="mt-1 text-sm text-muted">
    <?php if ($baris['created_user']): ?>Dibuat <?= e(tgl_id(substr((string) $baris['created_user'], 0, 10))) ?> · <?php endif ?>
    Terakhir masuk <?= $baris['last_login'] ? e(tgl_id(substr((string) $baris['last_login'], 0, 10)) . ', ' . substr((string) $baris['last_login'], 11, 5)) : 'belum pernah' ?>
</p>
<?php endif ?>

<form method="post" action="<?= e(url($aksi)) ?>" class="mt-6 max-w-3xl space-y-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8" autocomplete="off">
    <?= csrf_field() ?>

    <fieldset class="space-y-5">
        <legend class="text-lg font-bold">Identitas</legend>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $this->insert('components/form-field', ['name' => 'f_name', 'label' => 'Nama depan', 'required' => true, 'value' => $nilai('f_name'), 'autocomplete' => 'off', 'atribut' => ['maxlength' => 50]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'l_name', 'label' => 'Nama belakang', 'value' => $nilai('l_name'), 'autocomplete' => 'off', 'atribut' => ['maxlength' => 50]]) ?>
        </div>
        <?= $this->insert('components/form-field', ['name' => 'username', 'label' => 'Username', 'required' => true, 'value' => $nilai('username'), 'autocomplete' => 'off',
            'hint' => 'Untuk masuk. Huruf kecil, angka, titik, garis bawah, atau tanda hubung; 3–45 karakter.', 'atribut' => ['maxlength' => 45, 'autocapitalize' => 'none', 'spellcheck' => 'false']]) ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $this->insert('components/form-field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $nilai('email'), 'autocomplete' => 'off', 'atribut' => ['maxlength' => 45]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'no_hp', 'label' => 'Nomor HP', 'type' => 'tel', 'value' => $nilai('no_hp'), 'inputmode' => 'tel', 'autocomplete' => 'off', 'atribut' => ['maxlength' => 20]]) ?>
        </div>
    </fieldset>

    <div class="border-t border-gray-200 pt-6">
    <fieldset class="space-y-5">
        <legend class="text-lg font-bold">Akses</legend>
        <?php if ($diriSendiri): ?>
        <p class="rounded-xl bg-surface p-4 text-sm text-muted">
            Ini akun Anda sendiri: peran <strong class="text-ink"><?= e(PenggunaController::PERAN[(int) $baris['level']] ?? 'Operator') ?></strong> dan status <strong class="text-ink">aktif</strong>
            tidak bisa diubah sendiri, agar selalu ada Administrator yang bisa masuk.
        </p>
        <?php else: ?>
        <?= $this->insert('components/form-field', ['name' => 'level', 'label' => 'Peran', 'type' => 'select', 'required' => true,
            'options' => PenggunaController::PERAN, 'value' => $nilai('level', (string) Auth::OPERATOR),
            'hint' => 'Administrator: semua menu termasuk Pengguna. Operator: semua menu kecuali Pengguna. Kepala: hanya melihat Dasbor (statistik zakat), tidak bisa mengubah apa pun.']) ?>
        <label class="inline-flex cursor-pointer items-center gap-3 font-semibold">
            <input type="checkbox" name="aktif" value="1" class="size-5 accent-primary"<?= $aktif ? ' checked' : '' ?>>
            Akun aktif (boleh masuk)
        </label>
        <?php endif ?>
    </fieldset>
    </div>

    <div class="border-t border-gray-200 pt-6">
    <fieldset class="space-y-5">
        <legend class="text-lg font-bold"><?= $baru ? 'Password awal' : 'Ganti password' ?></legend>
        <?php if (!$baru): ?>
        <p class="text-sm text-muted">Kosongkan bila password tidak diganti. Bila diganti, pengguna ini otomatis keluar dari perangkat lain.</p>
        <?php endif ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $this->insert('components/form-field', ['name' => 'password', 'label' => $baru ? 'Password' : 'Password baru', 'type' => 'password', 'required' => $baru, 'value' => '',
                'autocomplete' => 'new-password', 'hint' => 'Minimal 8 karakter, tidak sama dengan username.', 'atribut' => ['minlength' => 8, 'maxlength' => 200]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'password_ulang', 'label' => 'Ulangi password', 'type' => 'password', 'required' => $baru, 'value' => '', 'autocomplete' => 'new-password']) ?>
        </div>
    </fieldset>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-6">
        <button type="submit" class="inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Simpan</button>
        <a href="<?= e(url('admin/pengguna')) ?>" class="inline-flex min-h-12 items-center rounded-full px-5 font-semibold text-muted hover:text-primary">Batal</a>
    </div>
</form>
