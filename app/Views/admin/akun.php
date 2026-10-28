<?php
/**
 * @var App\Core\View $this
 * @var array{id: int, username: string, nama: string} $user
 */
$this->layout('admin', ['title' => 'Akun Saya']);
?>
<h1 class="text-2xl font-bold">Akun Saya</h1>
<p class="mt-1 text-muted">Masuk sebagai <strong class="text-ink"><?= e($user['username']) ?></strong> (<?= e($user['nama']) ?>).</p>

<form method="post" action="<?= e(url('admin/akun')) ?>" class="mt-6 max-w-md space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
    <?= csrf_field() ?>
    <h2 class="text-lg font-bold">Ganti password</h2>
    <?= $this->insert('components/form-field', ['name' => 'password_lama', 'label' => 'Password saat ini', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'value' => '']) ?>
    <?= $this->insert('components/form-field', ['name' => 'password_baru', 'label' => 'Password baru', 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'value' => '',
        'hint' => 'Minimal 8 karakter. Gunakan kombinasi huruf, angka, dan simbol, mis. "Zakat-Basel26".', 'atribut' => ['minlength' => 8]]) ?>
    <?= $this->insert('components/form-field', ['name' => 'password_ulang', 'label' => 'Ulangi password baru', 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'value' => '']) ?>
    <button type="submit" class="inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Ganti password</button>
</form>
