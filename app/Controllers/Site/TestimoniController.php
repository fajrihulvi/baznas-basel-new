<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\TestimoniService;

/**
 * Testimoni dari pengunjung (muzaki/mustahik):
 *   GET  /testimoni/token  token CSRF untuk form di beranda (diambil JS saat mulai mengisi → beranda tetap tanpa sesi)
 *   GET  /testimoni/kirim  halaman form (cadangan tanpa JS, bisa dibagikan sebagai tautan)
 *   POST /testimoni/kirim  simpan sebagai draf menunggu moderasi
 */
final class TestimoniController extends Controller
{
    public function token(Request $request): Response
    {
        return Response::json(['token' => csrf_token()])->header('Cache-Control', 'private, no-store');
    }

    public function formulir(Request $request): Response
    {
        return $this->view('site/testimoni-kirim')->header('Cache-Control', 'private, no-store');
    }

    public function kirim(Request $request): Response
    {
        $hasil = TestimoniService::kirim($request->all(), $request->ip());
        // Kembali ke tempat form diisi: beranda (bagian #kirim-testimoni) atau halaman form
        $kembali = $request->input('asal') === 'beranda' ? url('/') . '#kirim-testimoni' : url('testimoni/kirim');

        if ($hasil['errors'] !== []) {
            Session::flash('errors', $hasil['errors']);
            Session::flash('_old', $request->only(TestimoniService::FIELD));
        } else {
            Session::flash('testimoni_terkirim', true);
        }

        return Response::redirect($kembali, 303);
    }
}
