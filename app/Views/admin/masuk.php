<?php
/**
 * @var App\Core\View $this
 * @var string        $ke tujuan setelah masuk
 */
$this->layout('admin', ['title' => 'Masuk', 'polos' => true]);
?>
<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="text-center">
        <?= $this->insert('partials/logo', ['class' => 'mx-auto h-24 w-auto', 'sizes' => '139px']) ?>
        <h1 class="mt-4 text-xl font-bold text-ink">Masuk Admin</h1>
        <p class="text-sm text-muted">Masuk untuk mengelola situs.</p>
    </div>
    <form method="post" action="<?= e(url('admin/masuk')) ?>" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="ke" value="<?= e($ke) ?>">
        <?= $this->insert('components/form-field', ['name' => 'username', 'label' => 'Username', 'required' => true, 'autocomplete' => 'username', 'atribut' => ['autocapitalize' => 'off', 'spellcheck' => 'false']]) ?>
        <?= $this->insert('components/form-field', ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'value' => '']) ?>
        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Masuk</button>
    </form>
</div>
<p class="mt-4 text-center text-sm"><a href="<?= e(url()) ?>" class="text-muted hover:text-primary">← Kembali ke situs</a></p>
