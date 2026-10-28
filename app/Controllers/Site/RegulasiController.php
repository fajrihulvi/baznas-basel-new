<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\LaporanKeuangan;
use App\Models\Perundangan;

/** /regulasi (filter ?kategori=) dan /laporan. */
final class RegulasiController extends Controller
{
    public function index(Request $request): Response
    {
        $kategori = Perundangan::kategori();
        $pilih = $request->query('kategori');
        $aktif = null;
        foreach ($kategori as $k) {
            if ((string) $k['id'] === $pilih) {
                $aktif = $k;
            }
        }

        return $this->view('site/regulasi', [
            'kategori'  => $kategori,
            'aktif'     => $aktif,
            'dokumen'   => Perundangan::terbit($aktif !== null ? (int) $aktif['id'] : null),
        ]);
    }

    public function laporan(Request $request): Response
    {
        return $this->view('site/laporan', ['laporan' => LaporanKeuangan::terbit()]);
    }
}
