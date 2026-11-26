<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Amil;
use App\Models\Halaman;
use App\Models\PenerimaManfaat;
use App\Models\Testimoni;
use App\Services\HomeService;
use App\Services\Navigation;
use App\Services\SiteService;
use App\Services\StatistikPenerimaManfaatService;

/** Halaman statis (/tentang, /tentang/{slug}, /halaman/{slug}), Amil, Testimoni, Layanan, Rekening. */
final class PageController extends Controller
{
    /** /tentang — Profil Kami: kartu Halaman kelompok Tentang Kami + daftar Program Kami (5 pilar) + Kegiatan Kami. */
    public function tentangIndex(Request $request): Response
    {
        $halaman = [];
        foreach (SiteService::halaman('tentang') as $h) {
            $halaman['/tentang/' . $h['slug']] = $h;
        }
        // Hanya Halaman kelompok Tentang Kami (Amil, Testimoni, Kontak tidak ditampilkan); urutan ikut menu Tentang
        $kartu = [];
        foreach (Navigation::anakUtama('Tentang') as $a) {
            $h = $halaman[$a['url']] ?? null;
            if ($h === null) {
                continue;
            }
            $kartu[] = [
                'label'    => $a['label'],
                'url'      => $a['url'],
                'gambar'   => $h['gambar'] ?? null,
                'gambar_w' => $h['gambar_w'] ?? null,
                'gambar_h' => $h['gambar_h'] ?? null,
                'ikon'     => 'info', // pengganti bila belum ada gambar
            ];
        }
        if ($kartu === []) {
            throw new HttpException(404);
        }

        return $this->view('site/tentang', ['kartu' => $kartu, 'pilar' => SiteService::pilar(), 'kegiatan' => SiteService::kegiatan()]);
    }

    public function tentang(Request $request, string $slug): Response
    {
        return $this->halamanGrup($request, $slug, true);
    }

    public function halaman(Request $request, string $slug): Response
    {
        return $this->halamanGrup($request, $slug, false);
    }

    public function amil(Request $request): Response
    {
        $amil = Amil::terbit();

        return $this->view('site/amil', [
            'pimpinan'  => array_values(array_filter($amil, static fn (array $a): bool => $a['bidang'] === 'pimpinan')),
            'pelaksana' => array_values(array_filter($amil, static fn (array $a): bool => $a['bidang'] === 'pelaksana')),
        ]);
    }

    public function testimoni(Request $request): Response
    {
        return $this->view('site/testimoni', ['testimoni' => Testimoni::terbit(100)]);
    }

    /**
     * /layanan — tujuan menu "Layanan" (bilah navigasi bawah & panel menu HP): ikon layanan zakat,
     * kartu Tunaikan Zakat (sama dengan beranda), lalu kartu Kegiatan BAZNAS.
     */
    public function layanan(Request $request): Response
    {
        return $this->view('site/layanan', [
            'tunaikan' => HomeService::get()['layanan'] ?? [],
            'kegiatan' => SiteService::kegiatan(),
        ]);
    }

    public function rekening(Request $request): Response
    {
        return $this->view('site/rekening', ['rekening' => SiteService::rekening()]);
    }

    /** Halaman grup "tentang" hanya di /tentang/…, grup lain hanya di /halaman/… (satu URL kanonik). */
    private function halamanGrup(Request $request, string $slug, bool $tentang): Response
    {
        $halaman = Halaman::terbit($slug) ?? throw new HttpException(404);
        if (($halaman['grup'] === 'tentang') !== $tentang) {
            return Response::redirect(url(($tentang ? 'halaman/' : 'tentang/') . $slug), 301);
        }

        return $this->view('site/halaman', [
            'halaman' => $halaman,
            'saudara' => SiteService::halaman($halaman['grup']),
            // Laporan penerima manfaat di bawah isi halaman Sistem Pelaporan Zakat (agregat bulanan)
            'penerima' => $tentang && $slug === PenerimaManfaat::SLUG_HALAMAN ? StatistikPenerimaManfaatService::untuk($request) : null,
        ]);
    }
}
