<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Donasi;
use App\Services\DonasiService;
use App\Services\SiteService;

/** Program donasi: daftar → detail (pilih nominal) → formulir donatur → instruksi transfer & konfirmasi WhatsApp. */
final class DonasiController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('site/donasi-list', ['program' => DonasiService::program()]);
    }

    public function show(Request $request, string $slug): Response
    {
        $p = $this->program($slug);

        return $this->view('site/donasi-detail', [
            'p'   => $p,
            'doa' => Donasi::doa($p['id'], 10),
        ]);
    }

    public function formulir(Request $request, string $slug): Response
    {
        $p = $this->program($slug);
        if ($p['status'] !== 'berjalan') {
            return Response::redirect(url('donasi/' . $p['slug']), 303);
        }
        $nominal = angka(old('nominal', (string) $request->query('nominal', '')));

        return $this->view('site/donasi-bayar', [
            'p'         => $p,
            'nominal'   => $nominal > 0 ? $nominal : $p['minimal'],
            'rekening'  => DonasiService::rekening(),
            'ketentuan' => SiteService::halaman('ketentuan'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function simpan(Request $request, string $slug): Response
    {
        $p = $this->program($slug);
        $hasil = DonasiService::buat($p, $request->all(), $request->ip());

        if ($hasil['errors'] !== []) {
            Session::flash('errors', $hasil['errors']);
            Session::flash('_old', $request->only(DonasiService::FIELD_FORM));
            Session::flash('alert', ['jenis' => 'error', 'pesan' => $hasil['errors']['_form'] ?? 'Data belum lengkap atau tidak valid. Periksa isian yang ditandai.']);

            return Response::redirect(url('donasi/' . $p['slug'] . '/bayar'), 303);
        }

        return Response::redirect(url('donasi/transfer/' . $hasil['kode']), 303);
    }

    public function instruksi(Request $request, string $kode): Response
    {
        if (!preg_match('/^[A-Z0-9]{8}$/', $kode)) {
            throw new HttpException(404);
        }
        $d = Donasi::byKode($kode) ?? throw new HttpException(404);
        $rekening = null;
        foreach (SiteService::rekening() as $r) {
            if ((int) $r['id'] === (int) $d['id_rekening']) {
                $rekening = $r;
            }
        }

        return $this->view('site/donasi-instruksi', ['d' => $d, 'rekening' => $rekening])
            ->header('Cache-Control', 'private, no-store'); // berisi nama donatur
    }

    private function program(string $slug): array
    {
        if (!preg_match('/^[a-z0-9-]{1,170}$/', $slug)) {
            throw new HttpException(404);
        }

        return DonasiService::bySlug($slug) ?? throw new HttpException(404);
    }
}
