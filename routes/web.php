<?php

declare(strict_types=1);

use App\Controllers\Site\CariController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\KalkulatorController;
use App\Controllers\Site\PembayaranController;
use App\Controllers\Site\KomponenController;
use App\Controllers\Site\MediaController;
use App\Controllers\Site\PageController;
use App\Controllers\Site\PostController;
use App\Controllers\Site\ProgramController;
use App\Controllers\Site\RegulasiController;
use App\Controllers\Site\SeoController;
use App\Controllers\Site\ZakatController;
use App\Services\RedirectService;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);

// Kabar & program
$router->get('/berita', [PostController::class, 'index']);
$router->get('/berita/{slug}', [PostController::class, 'show']);
$router->get('/program', [ProgramController::class, 'index']);
$router->get('/program/{slug}', [ProgramController::class, 'pilar']);

// Zakat
$router->get('/zakat', [ZakatController::class, 'index']);
$router->get('/zakat/{slug}', [ZakatController::class, 'show']);
$router->get('/rekening', [PageController::class, 'rekening']);
$router->get('/kalkulator-zakat', [KalkulatorController::class, 'index']);
$router->get('/bayar-zakat', [PembayaranController::class, 'form']);
$router->post('/bayar-zakat', [PembayaranController::class, 'simpan']);
$router->get('/bayar-zakat/{kode}', [PembayaranController::class, 'instruksi']);
$router->get('/konfirmasi', [PembayaranController::class, 'konfirmasiForm']);
$router->post('/konfirmasi', [PembayaranController::class, 'konfirmasiSimpan']);

// Informasi
$router->get('/regulasi', [RegulasiController::class, 'index']);
$router->get('/laporan', [RegulasiController::class, 'laporan']);
$router->get('/video', [MediaController::class, 'video']);
$router->get('/galeri', [MediaController::class, 'galeri']);

// Tentang & halaman statis
$router->get('/tentang', [PageController::class, 'tentangIndex']);
$router->get('/tentang/{slug}', [PageController::class, 'tentang']);
$router->get('/halaman/{slug}', [PageController::class, 'halaman']);
$router->get('/amil', [PageController::class, 'amil']);
$router->get('/testimoni', [PageController::class, 'testimoni']);

$router->get('/cari', [CariController::class, 'index']);

// SEO
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);

// URL situs lama → 301 (dijalankan hanya sebelum 404)
$router->fallback(static fn ($request) => RedirectService::untuk($request));

// Katalog komponen — hanya untuk pengembangan
if (config('app.debug')) {
    $router->get('/_komponen', [KomponenController::class, 'index']);
}
