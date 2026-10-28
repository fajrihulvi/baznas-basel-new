<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PembayaranService;
use App\Services\ZakatCalculator;

/** /kalkulator-zakat — dihitung di browser; ?jenis=…&… menghitung di server bila JS tidak aktif. */
final class KalkulatorController extends Controller
{
    public function index(Request $request): Response
    {
        $aktif = (string) $request->query('jenis', 'penghasilan');
        $aktif = array_key_exists($aktif, ZakatCalculator::JENIS) ? $aktif : 'penghasilan';

        // id jenis_zakat tujuan tombol "Tunaikan" per kalkulator
        $idJenis = [];
        foreach (PembayaranService::jenis() as $j) {
            $kunci = $j['kalkulator'] ?? ($j['mode'] !== 'nominal' ? $j['mode'] : null);
            if ($kunci !== null) {
                $idJenis[$kunci] = $j['id'];
            }
        }

        return $this->view('site/kalkulator', [
            'aktif'   => $aktif,
            'param'   => ZakatCalculator::parameter(),
            'idJenis' => $idJenis,
            'input'   => $request->queryAll(),
            'hasil'   => $request->query('hitung') !== null ? ZakatCalculator::hitung($aktif, $request->queryAll()) : null,
        ]);
    }
}
