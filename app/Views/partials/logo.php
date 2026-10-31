<?php
/**
 * Logo resmi BAZNAS Kabupaten Bangka Selatan (Garuda + tulisan), dari public/logo-baznasbasel.png
 * → assets/img/logo-baznasbasel-{120,180,240,360,480}.webp (pinggiran transparan dipangkas, rasio 1,443 : 1).
 * Favicon & ikon aplikasi tetap Garuda saja (tulisan tidak terbaca di 16–48 px).
 *
 * @var string      $class  ukuran tampil, mis. "h-11 w-auto lg:h-14" (tinggi menentukan lebar)
 * @var string      $sizes  lebar tampil untuk srcset, mis. "(min-width: 1024px) 81px, 64px"
 * @var string|null $alt    default nama situs; isi '' bila nama sudah tertulis di sebelahnya
 * @var bool|null   $lazy   true untuk logo di bawah layar (footer)
 */
$alt ??= setting('site_name', (string) config('app.name'));
$srcset = implode(', ', array_map(static fn (int $w): string => asset("img/logo-baznasbasel-{$w}.webp") . " {$w}w", [120, 180, 240, 360, 480]));
?>
<picture>
    <source type="image/webp" srcset="<?= e($srcset) ?>" sizes="<?= e($sizes) ?>">
    <img src="<?= e(asset('img/logo-baznasbasel-480.png')) ?>" alt="<?= e($alt) ?>" width="480" height="333" class="<?= e($class) ?>"
         decoding="async"<?= !empty($lazy) ? ' loading="lazy"' : '' ?>>
</picture>
