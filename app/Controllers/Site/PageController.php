<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Amil;
use App\Models\Halaman;
use App\Models\Testimoni;
use App\Services\SiteService;

/** Halaman statis (/tentang/{slug}, /halaman/{slug}), Amil, Testimoni, Rekening. */
final class PageController extends Controller
{
    /** /tentang → halaman "tentang" pertama yang terbit */
    public function tentangIndex(Request $request): Response
    {
        $pertama = SiteService::halaman('tentang')[0] ?? throw new HttpException(404);

        return Response::redirect(url('tentang/' . $pertama['slug']), 302);
    }

    public function tentang(Request $request, string $slug): Response
    {
        return $this->halamanGrup($slug, true);
    }

    public function halaman(Request $request, string $slug): Response
    {
        return $this->halamanGrup($slug, false);
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

    public function rekening(Request $request): Response
    {
        return $this->view('site/rekening', ['rekening' => SiteService::rekening()]);
    }

    /** Halaman grup "tentang" hanya di /tentang/…, grup lain hanya di /halaman/… (satu URL kanonik). */
    private function halamanGrup(string $slug, bool $tentang): Response
    {
        $halaman = Halaman::terbit($slug) ?? throw new HttpException(404);
        if (($halaman['grup'] === 'tentang') !== $tentang) {
            return Response::redirect(url(($tentang ? 'halaman/' : 'tentang/') . $slug), 301);
        }

        return $this->view('site/halaman', [
            'halaman' => $halaman,
            'saudara' => SiteService::halaman($halaman['grup']),
        ]);
    }
}
