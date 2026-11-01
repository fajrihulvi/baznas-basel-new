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
use App\Services\StatistikPengumpulanService;
use App\Services\StatistikZakatService;
use App\Services\ZakatCalculator;
use PDOException;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $statistik = [
            'statistik'   => StatistikZakatService::untuk($request),
            'pengumpulan' => self::pengumpulan($request),
            // Dua filter terpisah di satu halaman: masing-masing mengirim ulang parameter filter lainnya
            'filterZakat'       => self::query($request, ['periode', 'dari', 'sampai']),
            'filterPengumpulan' => self::query($request, ['pg_tahun', 'pg_bulan']),
        ];

        // Kepala: hanya statistik (tanpa antrean, daftar pembayaran, pintasan yang mengarah ke halaman kelola)
        if (Auth::kepala()) {
            return $this->view('admin/dashboard', ['kepala' => true] + $statistik);
        }

        $param = ZakatCalculator::parameter();

        return $this->view('admin/dashboard', $statistik + [
            'kepala'         => false,
            'bayar'          => Pembayaran::ringkasan(),
            'konfirmasiBaru' => KonfirmasiPembayaran::hitungBaru(),
            'antrean'        => KonfirmasiPembayaran::antrean(5),
            'terbaru'        => Pembayaran::daftarAdmin(null, false, '', 5, 0),
            'testimoniBaru'  => Testimoni::menunggu(),
            'jumlahKabar'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM post WHERE publish = 'Y'"),
            'settingKurang'  => array_keys(array_filter([
                'Harga emas per gram (nishab zakat maal & penghasilan)' => $param['harga_emas'] === null && $param['nishab_bulanan'] === null,
                'Nilai zakat fitrah per jiwa'                           => $param['nilai_fitrah'] === null,
            ])),
        ]);
    }

    /** null bila tabel pengumpulan belum dimigrasi (kode terunggah lebih dulu) — dasbor lainnya tetap tampil. */
    private static function pengumpulan(Request $request): ?array
    {
        try {
            return StatistikPengumpulanService::untuk($request);
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') {
                return null;
            }
            throw $e;
        }
    }

    /** @param list<string> $kunci @return array<string, string> parameter query yang terisi */
    private static function query(Request $request, array $kunci): array
    {
        $hasil = [];
        foreach ($kunci as $k) {
            $v = $request->query($k);
            if (is_string($v) && $v !== '') {
                $hasil[$k] = mb_substr($v, 0, 20);
            }
        }

        return $hasil;
    }
}
