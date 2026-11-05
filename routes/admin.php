<?php

declare(strict_types=1);

use App\Controllers\Admin\AkunController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PembayaranController;
use App\Controllers\Admin\PenggunaController;
use App\Controllers\Admin\PengumpulanImporController;
use App\Controllers\Admin\ResourceController;
use App\Controllers\Admin\SettingController;
use App\Controllers\Admin\StatusController;
use App\Controllers\Admin\UnggahGambarController;
use App\Core\Router;
use App\Middleware\AuthAdmin;

/** @var App\Core\Router $router */

// Panel admin — tidak ditautkan dari halaman publik (§10)
$router->get('/admin/masuk', [AuthController::class, 'form']);
$router->post('/admin/masuk', [AuthController::class, 'masuk']);

$router->group('admin', [AuthAdmin::class], static function (Router $r): void {
    $r->get('/', [DashboardController::class, 'index']);
    $r->post('/keluar', [AuthController::class, 'keluar']);

    $r->get('/pembayaran', [PembayaranController::class, 'index']);
    $r->get('/pembayaran/{id}', [PembayaranController::class, 'show']);
    $r->post('/pembayaran/{id}/status', [PembayaranController::class, 'status']);
    $r->post('/konfirmasi/{id}/status', [PembayaranController::class, 'konfirmasi']);
    $r->get('/bukti/{id}', [PembayaranController::class, 'bukti']);

    $r->get('/setting', [SettingController::class, 'edit']);
    $r->post('/setting', [SettingController::class, 'update']);
    $r->get('/status', [StatusController::class, 'index']);

    // Kelola pengguna (khusus Administrator — dicek di controller)
    $r->get('/pengguna', [PenggunaController::class, 'index']);
    $r->get('/pengguna/baru', [PenggunaController::class, 'create']);
    $r->post('/pengguna', [PenggunaController::class, 'store']);
    $r->get('/pengguna/{id}/ubah', [PenggunaController::class, 'edit']);
    $r->post('/pengguna/{id}', [PenggunaController::class, 'update']);
    $r->post('/pengguna/{id}/hapus', [PenggunaController::class, 'destroy']);
    $r->get('/akun', [AkunController::class, 'edit']);
    $r->post('/akun', [AkunController::class, 'update']);

    // Gambar di isi editor HTML (fetch dari admin.js)
    $r->post('/unggah-gambar', [UnggahGambarController::class, 'simpan']);

    // Impor Excel pengumpulan (Kepala ditolak middleware: bukan akses_kepala)
    $r->get('/pengumpulan/impor', [PengumpulanImporController::class, 'form']);
    $r->post('/pengumpulan/impor', [PengumpulanImporController::class, 'impor']);
    $r->get('/pengumpulan/format-impor', [PengumpulanImporController::class, 'format']);

    // CRUD generik (config/admin.php) — paling akhir agar route khusus di atas didahulukan
    $r->get('/{res}', [ResourceController::class, 'index']);
    $r->get('/{res}/baru', [ResourceController::class, 'create']);
    $r->post('/{res}', [ResourceController::class, 'store']);
    $r->get('/{res}/{id}/ubah', [ResourceController::class, 'edit']);
    $r->post('/{res}/{id}', [ResourceController::class, 'update']);
    $r->post('/{res}/{id}/hapus', [ResourceController::class, 'destroy']);
});
