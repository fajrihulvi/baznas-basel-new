<?php
/**
 * Grid kartu kabar (1 kolom HP, 2 tablet, 3 desktop).
 * Kartu ke-4 dst. memakai content-visibility (cv-kartu): gambarnya tidak berebut bandwidth
 * dengan gambar LCP dan baru dimuat saat mendekati layar.
 *
 * @var App\Core\View $this
 * @var list<array>   $posts
 * @var string|null   $heading     tag judul kartu (default h2 — dipakai langsung di bawah h1 halaman)
 * @var bool|null     $eagerPertama gambar kartu pertama dimuat segera (kandidat LCP di halaman daftar)
 */
?>
<ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
    <?php foreach ($posts as $i => $p): ?>
    <li<?= $i >= 3 ? ' class="cv-kartu"' : '' ?>>
        <?= $this->insert('components/card-post', [
            'post'      => $p,
            'heading'   => $heading ?? 'h2',
            'ringkasan' => true,
            'sizes'     => '(min-width: 1024px) 368px, (min-width: 640px) 45vw, calc(100vw - 2rem)',
            'eager'     => $i === 0 && !empty($eagerPertama),
        ]) ?>
    </li>
    <?php endforeach ?>
</ul>
