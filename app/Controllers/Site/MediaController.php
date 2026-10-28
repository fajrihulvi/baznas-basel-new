<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Galeri;
use App\Models\Video;

/** /video dan /galeri (filter ?jenis=). */
final class MediaController extends Controller
{
    public function video(Request $request): Response
    {
        $paginator = Paginator::fromRequest($request, Video::hitung(), 12);

        return $this->view('site/video', [
            'video'     => Video::terbaru(12, $paginator->offset),
            'paginator' => $paginator,
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
