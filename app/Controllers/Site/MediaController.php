<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Galeri;
use App\Models\Video;
use App\Services\HomeService;

/** /video, /galeri (filter ?jenis=), dan /arsip (galeri berbentuk daftar + cari judul). */
final class MediaController extends Controller
{
    public const PER_ARSIP = 10;

    public function video(Request $request): Response
    {
        $paginator = Paginator::fromRequest($request, Video::hitung(), 12);

        return $this->view('site/video', [
            'video'     => Video::terbaru(12, $paginator->offset),
            'paginator' => $paginator,
        ]);
    }

    /**
     * /arsip — Arsip BAZNAS (tujuan menu "Arsip" di panel menu HP): daftar galeri + pagination,
     * form cari judul (?q=), lalu kartu "Ayo Tunaikan Zakat".
     */
    public function arsip(Request $request): Response
    {
        $q = $request->query('q');
        $q = is_string($q) ? mb_substr(trim($q), 0, 100) : '';
        $paginator = Paginator::fromRequest($request, Galeri::hitung(null, $q), self::PER_ARSIP);

        return $this->view('site/arsip', [
            'q'         => $q,
            'galeri'    => Galeri::terbit(null, self::PER_ARSIP, $paginator->offset, $q),
            'paginator' => $paginator,
            'tunaikan'  => HomeService::get()['layanan'] ?? [],
        ]);
    }

    public function galeri(Request $request): Response
    {
        $jenis = $request->query('jenis');
        $jenis = is_string($jenis) && in_array($jenis, Galeri::JENIS, true) ? $jenis : null;
        $paginator = Paginator::fromRequest($request, Galeri::hitung($jenis), 24);

        return $this->view('site/galeri', [
            'jenis'     => $jenis,
            'galeri'    => Galeri::terbit($jenis, 24, $paginator->offset),
            'paginator' => $paginator,
        ]);
    }
}
