<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\{Cache, Config, Csrf, Database, Env, ErrorHandler, HttpException, Middleware, Request, Response, Router, Session};

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

$fail = 0;
function ok(string $label, bool $cond): void { global $fail; echo ($cond ? 'OK   ' : 'FAIL ') . $label . PHP_EOL; if (!$cond) $fail++; }

// Database
ok('DB berita = 26', (int) Database::fetchColumn('SELECT COUNT(*) FROM berita') === 26);
$row = Database::fetch('SELECT judul FROM berita WHERE id_berita = ?', [49]);
ok('DB fetch param int', is_array($row) && $row['judul'] !== '');
ok('DB named param + LIMIT', count(Database::fetchAll('SELECT id_berita FROM berita WHERE publish = :p LIMIT :n', ['p' => 'Y', 'n' => 3])) === 3);
ok('DB fetch kosong → null', Database::fetch('SELECT 1 FROM berita WHERE id_berita = -1') === null);
ok('DB time_zone +07:00', Database::fetchColumn('SELECT @@session.time_zone') === '+07:00');
try { Database::transaction(function () { Database::execute('UPDATE berita SET judul = judul WHERE id_berita = 49'); throw new RuntimeException('x'); }); } catch (RuntimeException) {}
ok('DB transaksi rollback tidak meninggalkan transaksi', !Database::connection()->inTransaction());

// Cache
Cache::forget('uji');
$calls = 0;
$v1 = Cache::remember('uji', 60, function () use (&$calls) { $calls++; return ['a' => 1]; });
$v2 = Cache::remember('uji', 60, function () use (&$calls) { $calls++; return ['a' => 2]; });
ok('Cache remember sekali hitung', $calls === 1 && $v2 === ['a' => 1]);
Cache::set('uji-null', null, 60);
ok('Cache simpan null', Cache::remember('uji-null', 60, fn () => 'baru') === null);
Cache::forget('uji'); Cache::forget('uji-null');
ok('Cache forget', Cache::get('uji', 'kosong') === 'kosong');

// Helpers
ok('tgl_id', tgl_id('2026-09-07') === '7 September 2026');
ok('tgl_id 0000', tgl_id('0000-00-00') === '');
ok('format_rupiah', format_rupiah(1500000) === 'Rp1.500.000');
ok('e()', e('<a href="x">\'') === '&lt;a href=&quot;x&quot;&gt;&#039;');
ok('url query', url('berita', ['hal' => 2]) === 'http://localhost:8000/berita?hal=2');
ok('url root', url() === 'http://localhost:8000/');

// Router: params, grup, middleware, CSRF
final class TandaMw implements Middleware {
    public function handle(Request $r, Closure $next): Response { return $next($r)->header('X-Mw', 'ya'); }
}
function req(string $method, string $uri, array $post = []): Request {
    $_SERVER['REQUEST_METHOD'] = $method; $_SERVER['REQUEST_URI'] = $uri; $_POST = $post; $_GET = [];
    return Request::capture('');
}
$router = new Router();
$router->get('/berita/{slug}', fn (Request $r, string $slug) => "slug=$slug");
$router->get('/program/{pilar}/{hal}', fn (Request $r, string $pilar, string $hal) => "$pilar|$hal");
$router->group('admin', [TandaMw::class], function (Router $r) {
    $r->get('/', fn () => 'dashboard');
    $r->post('/simpan', fn () => 'tersimpan');
});
$router->fallback(fn (Request $r) => $r->path() === '/lama' ? Response::redirect('/baru', 301) : null);

ok('Route param', $router->dispatch(req('GET', '/berita/zakat-fitrah-2026'))->body() === 'slug=zakat-fitrah-2026');
ok('Route 2 param', $router->dispatch(req('GET', '/program/basel-sehat/2'))->body() === 'basel-sehat|2');
ok('Route titik tidak jadi wildcard', (function () use ($router) { try { $router->dispatch(req('GET', '/beritaX/a')); return false; } catch (HttpException $e) { return $e->status() === 404; } })());
$res = $router->dispatch(req('GET', '/admin'));
ok('Grup + middleware', $res->body() === 'dashboard' && $res->getHeader('X-Mw') === 'ya');
ok('Fallback redirect', $router->dispatch(req('GET', '/lama'))->status() === 301);
try { $router->dispatch(req('POST', '/admin/simpan')); ok('POST tanpa token → 419', false); }
catch (HttpException $e) { ok('POST tanpa token → 419', $e->status() === 419); }
ok('Sesi belum dimulai sebelum butuh token', !Session::isStarted());
$token = Csrf::token();
ok('Sesi dimulai saat token dibuat', Session::isStarted());
ok('POST dengan token valid', $router->dispatch(req('POST', '/admin/simpan', ['_token' => $token]))->body() === 'tersimpan');
try { $router->dispatch(req('POST', '/admin/simpan', ['_token' => 'salah'])); ok('POST token salah → 419', false); }
catch (HttpException $e) { ok('POST token salah → 419', $e->status() === 419); }
try { $router->dispatch(req('GET', '/admin/simpan')); ok('GET ke route POST → 405', false); }
catch (HttpException $e) { ok('GET ke route POST → 405', $e->status() === 405 && $e->headers()['Allow'] === 'POST'); }

// Request base path & input trim
$_SERVER['REQUEST_URI'] = '/baznas-basel/berita/abc?x=1'; $_SERVER['REQUEST_METHOD'] = 'GET';
ok('Base path subfolder', Request::capture('/baznas-basel')->path() === '/berita/abc');
$_SERVER['REQUEST_URI'] = '/baznas-baselX/abc';
ok('Base path tidak salah potong', Request::capture('/baznas-basel')->path() === '/baznas-baselX/abc');
$_SERVER['REQUEST_URI'] = '//evil.com/x';
ok('URI // aman', Request::capture('')->path() === '/evil.com/x');
ok('Input di-trim', req('POST', '/', ['nama' => "  Ali\0 "])->input('nama') === 'Ali');

// Flash & old()
Session::flash('_old', ['nama' => 'Budi']);
ok('Flash belum terbaca di request yang sama', old('nama') === '');

// Error 500 + log
$logFile = BASE_PATH . '/storage/logs/app-' . date('Y-m-d') . '.log';
$before = is_file($logFile) ? filesize($logFile) : 0;
$res = ErrorHandler::toResponse(new RuntimeException('uji error 500'));
clearstatcache();
ok('Error 500 status', $res->status() === 500);
ok('Error 500 tercatat di log', is_file($logFile) && filesize($logFile) > $before);
ok('Debug: pesan tampil', str_contains($res->body(), 'uji error 500'));
$res404 = ErrorHandler::toResponse(new HttpException(404));
ok('404 pakai template', str_contains($res404->body(), 'Halaman tidak ditemukan'));

echo $fail === 0 ? "\nSEMUA LULUS\n" : "\n$fail GAGAL\n";
