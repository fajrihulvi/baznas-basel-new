<?php
declare(strict_types=1);
$argv = [];
require dirname(__DIR__) . '/database/scripts/bootstrap.php';

use App\Core\{Image, Slug};
use App\Services\HtmlSanitizer;

$fail = 0;
function ok(string $l, bool $c, string $info = ''): void { global $fail; echo ($c ? 'OK   ' : 'FAIL ') . $l . ($c || $info === '' ? '' : "\n     → $info") . PHP_EOL; if (!$c) $fail++; }

$s = new HtmlSanitizer();
$t = fn (string $in) => $s->clean($in);

ok('script dibuang', ($r = $t('<p>a</p><script>alert(1)</script>')) === '<p>a</p>', $r);
ok('onclick/style/class dibuang', ($r = $t('<p onclick="x()" style="color:red" class="c">a</p>')) === '<p>a</p>', $r);
ok('javascript: href dibuang', ($r = $t('<a href="javascript:alert(1)">x</a>')) === '<a>x</a>', $r);
ok('JaVaScRiPt dengan spasi', ($r = $t('<a href=" java script:alert(1)">x</a>')) === '<a>x</a>', $r);
ok('target _blank + rel', ($r = $t('<a href="https://www.youtube.com/watch?v=abc" target="_blank">v</a>')) === '<a href="https://www.youtube.com/watch?v=abc" target="_blank" rel="noopener noreferrer">v</a>', $r);
ok('UTF-8 & entitas tetap', ($r = $t('<p>“Zakat” &ndash; é</p>')) === '<p>“Zakat” – é</p>', $r);
ok('&nbsp; paragraf kosong dibuang', ($r = $t('<p>&nbsp;</p><p>isi</p>')) === '<p>isi</p>', $r);
ok('tag tak dikenal dibuka', ($r = $t('<div><span>teks</span></div>')) === 'teks', $r);
ok('h1 → h2', ($r = $t('<h1>Judul</h1>')) === '<h2>Judul</h2>', $r);
ok('img data: ditolak', ($r = $t('<p><img src="data:image/png;base64,xx">a</p>')) === '<p>a</p>', $r);
ok('img valid + lazy', str_contains($r = $t('<img src="https://x.id/a.jpg" alt="A" onerror="x">'), 'loading="lazy"') && !str_contains($r, 'onerror'), $r);
ok('iframe youtube → nocookie', str_contains($r = $t('<iframe src="https://www.youtube.com/embed/OS3E6EbdywY" width="560"></iframe>'), 'youtube-nocookie.com/embed/OS3E6EbdywY') && !str_contains($r, 'width'), $r);
ok('iframe lain dibuang', ($r = $t('<iframe src="https://evil.com/x"></iframe><p>a</p>')) === '<p>a</p>', $r);
ok('tabel border/align dibuang', ($r = $t('<table border="1" align="center"><tbody><tr><td colspan="2">x</td></tr></tbody></table>')) === '<table><tbody><tr><td colspan="2">x</td></tr></tbody></table>', $r);
ok('komentar dibuang', ($r = $t('<p>a<!-- x --></p>')) === '<p>a</p>', $r);
ok('svg onload dibuang', ($r = $t('<svg onload="x"><circle/></svg><p>a</p>')) === '<p>a</p>', $r);

ok('youtubeId watch&t', HtmlSanitizer::youtubeId('https://www.youtube.com/watch?v=UV7jCSQlU_I&t=12s') === 'UV7jCSQlU_I');
ok('youtubeId youtu.be', HtmlSanitizer::youtubeId('https://youtu.be/rg6aeHegGSA') === 'rg6aeHegGSA');
ok('youtubeId shorts', HtmlSanitizer::youtubeId('https://youtube.com/shorts/rg6aeHegGSA?si=x') === 'rg6aeHegGSA');
ok('youtubeId bukan yt', HtmlSanitizer::youtubeId('https://vimeo.com/123') === null);

ok('Slug::make', ($r = Slug::make('Zakat & Infaq: Ramadhan 1447 H!')) === 'zakat-dan-infaq-ramadhan-1447-h', $r);
ok('Slug::make aksen', ($r = Slug::make('Café Ümmat')) === 'cafe-ummat', $r);
ok('Slug::make kosong', Slug::make('!!!') === 'tanpa-judul');
ok('Slug::make potong di batas kata', ($r = Slug::make(str_repeat('kata ', 60), 30)) === 'kata-kata-kata-kata-kata-kata', $r);
ok('Slug::unique bentrok', ($r = Slug::unique('Profil', 'halaman')) === 'profil-2', $r);
ok('Slug::unique abaikan diri sendiri', ($r = Slug::unique('Profil', 'halaman', 'slug', 1)) === 'profil', $r);

// Image
$dir = sys_get_temp_dir() . '/uji-img-' . bin2hex(random_bytes(3));
$src = $dir . '-src.png';
$im = imagecreatetruecolor(2000, 500); imagesavealpha($im, true); imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 128, 0, 64)); imagepng($im, $src);
$dim = Image::optimize($src, $dir, 'uji.png');
ok('Image maks 1600', $dim === ['width' => 1600, 'height' => 400], json_encode($dim));
$v = getimagesize("$dir/uji-480.webp");
ok('Varian 480 webp', $v !== false && $v[0] === 480 && $v[2] === IMAGETYPE_WEBP);
ok('Varian 1200', getimagesize("$dir/uji-1200.webp")[0] === 1200);
$small = $dir . '-kecil.jpg'; imagejpeg(imagecreatetruecolor(300, 200), $small);
$dim = Image::optimize($small, $dir, 'kecil.jpg');
ok('Gambar kecil tidak diperbesar', $dim['width'] === 300 && getimagesize("$dir/kecil-1200.webp")[0] === 300);
$a = imagecreatefromwebp("$dir/uji-480.webp"); $px = imagecolorsforindex($a, imagecolorat($a, 5, 5));
ok('Transparansi PNG terjaga di WebP', $px['alpha'] > 0, json_encode($px));
array_map('unlink', glob("$dir/*")); rmdir($dir); unlink($src); unlink($small);

echo $fail === 0 ? "\nSEMUA LULUS\n" : "\n$fail GAGAL\n";
