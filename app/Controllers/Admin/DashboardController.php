<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\KonfirmasiPembayaran;
use App\Models\Pembayaran;
use App\Models\Testimoni;
use App\Services\StatistikZakatService;
use App\Services\ZakatCalculator;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Kepala: hanya statistik (tanpa antrean, daftar pembayaran, pintasan yang mengarah ke halaman kelola)
        if (Auth::kepala()) {
            return $this->view('admin/dashboard', ['kepala' => true, 'statistik' => StatistikZakatService::untuk($request)]);
        }

        $param = ZakatCalculator::parameter();

        return $this->view('admin/dashboard', [
            'kepala'         => false,
            'bayar'          => Pembayaran::ringkasan(),
            'konfirmasiBaru' => KonfirmasiPembayaran::hitungBaru(),
            'antrean'        => KonfirmasiPembayaran::antrean(5),
            'terbaru'        => Pembayaran::daftarAdmin(null, false, '', 5, 0),
            'statistik'      => StatistikZakatService::untuk($request),
            'testimoniBaru'  => Testimoni::menunggu(),
            'jumlahKabar'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM post WHERE publish = 'Y'"),
            'settingKurang'  => array_keys(array_filter([
                'Harga emas per gram (nishab zakat maal & penghasilan)' => $param['harga_emas'] === null && $param['nishab_bulanan'] === null,
                'Nilai zakat fitrah per jiwa'                           => $param['nilai_fitrah'] === null,
            ])),
        ]);
    }
}
