<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\KonfirmasiPembayaran;
use App\Models\Pembayaran;
use App\Services\PembayaranService;
use App\Services\SiteService;
use App\Services\ZakatCalculator;

/** Alur bayar zakat (§12): form → instruksi transfer → konfirmasi. */
final class PembayaranController extends Controller
{
    public function form(Request $request): Response
    {
        $jenisDipilih = old('jenis', (string) $request->query('jenis', ''));

        return $this->view('site/bayar', [
            'jenis'        => PembayaranService::jenis(),
            'param'        => ZakatCalculator::parameter(),
            'jenisDipilih' => $jenisDipilih,
            'nominalAwal'  => old('nominal', (string) (angka($request->query('nominal', '')) ?: '')),
            'jumlahAwal'   => (string) (angka($request->query('jumlah', '')) ?: ''),
        ]);
    }

    public function simpan(Request $request): Response
    {
        $hasil = PembayaranService::buat($request->all(), $request->ip());

        if ($hasil['errors'] !== []) {
            Session::flash('errors', $hasil['errors']);
            Session::flash('_old', $request->only(PembayaranService::FIELD_BAYAR));
            Session::flash('alert', ['jenis' => 'error', 'pesan' => $hasil['errors']['_form'] ?? 'Periksa kembali isian yang ditandai.']);

            return Response::redirect(url('bayar-zakat'), 303);
        }

        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Data pembayaran tersimpan. Silakan transfer sesuai instruksi di bawah ini.']);

        return Response::redirect(url('bayar-zakat/' . $hasil['kode']), 303);
    }

    public function instruksi(Request $request, string $kode): Response
    {
        if (!preg_match('/^[A-Za-z0-9]{4,12}$/', $kode)) {
            throw new HttpException(404);
        }
        $p = Pembayaran::byKode($kode) ?? throw new HttpException(404);

        return $this->view('site/bayar-instruksi', [
            'p'          => $p,
            'rekening'   => PembayaranService::rekeningUntuk($p['jenis']),
            'konfirmasi' => KonfirmasiPembayaran::perPembayaran((int) $p['id']),
        ])->header('Cache-Control', 'private, no-store'); // berisi nama muzaki
    }

    public function konfirmasiForm(Request $request): Response
    {
        $kode = old('kode', (string) $request->query('kode', ''));
        $p = preg_match('/^[A-Za-z0-9]{4,12}$/', $kode) ? Pembayaran::byKode($kode) : null;

        return $this->view('site/konfirmasi', [
            'kode'       => $kode,
            'pembayaran' => $p,
            'rekening'   => SiteService::rekening(),
            'rekeningSaran' => $p ? array_column(PembayaranService::rekeningUntuk($p['jenis']), 'id') : [],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function konfirmasiSimpan(Request $request): Response
    {
        $hasil = PembayaranService::konfirmasi($request->all(), $request->file('bukti'), $request->ip());
        $kode = (string) $request->input('kode', '');

        if ($hasil['errors'] !== []) {
            Session::flash('errors', $hasil['errors']);
            Session::flash('_old', $request->only(PembayaranService::FIELD_KONFIRMASI));
            Session::flash('alert', ['jenis' => 'error', 'pesan' => $hasil['errors']['_form'] ?? 'Periksa kembali isian yang ditandai. File bukti perlu dipilih ulang.']);

            return Response::redirect(url('konfirmasi', preg_match('/^[A-Za-z0-9]{4,12}$/', $kode) ? ['kode' => $kode] : []), 303);
        }

        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Konfirmasi diterima. Tim kami akan memverifikasi pembayaran Anda maksimal 1×24 jam kerja.']);

        return Response::redirect(url('bayar-zakat/' . $hasil['kode']), 303);
    }
}
