<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Post;
use App\Models\SubProgram;
use App\Services\SiteService;

/** /program (5 pilar) dan /program/{pilar} (kegiatan per pilar, filter ?sub=). */
final class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $pilar = SiteService::pilar();
        $idSub = [];
        foreach ($pilar as $p) {
            $idSub = [...$idSub, ...array_column(SubProgram::perPilar((int) $p['id']), 'id')];
        }

        return $this->view('site/program', [
            'pilar'    => $pilar,
            'kegiatan' => Post::perSubProgram($idSub, 6, 0),
        ]);
    }

    public function pilar(Request $request, string $slug): Response
    {
        $pilar = null;
        foreach (SiteService::pilar() as $p) {
            if ($p['slug'] === $slug) {
                $pilar = $p;
            }
        }
        if ($pilar === null) {
            throw new HttpException(404);
        }

        $subProgram = SubProgram::perPilar((int) $pilar['id']);
        $subAktif = null;
        foreach ($subProgram as $s) {
            if ($s['slug'] === $request->query('sub')) {
                $subAktif = $s;
            }
        }
        $idSub = $subAktif !== null ? [(int) $subAktif['id']] : array_map('intval', array_column($subProgram, 'id'));

        $paginator = Paginator::fromRequest($request, Post::hitungPerSubProgram($idSub), PostController::PER_HALAMAN);

        return $this->view('site/program-pilar', [
            'pilar'      => $pilar,
            'subProgram' => $subProgram,
            'subAktif'   => $subAktif,
            'posts'      => Post::perSubProgram($idSub, PostController::PER_HALAMAN, $paginator->offset),
            'paginator'  => $paginator,
        ]);
    }
}
