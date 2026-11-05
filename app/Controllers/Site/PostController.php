<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Models\Post;
use App\Services\DilihatService;

/** Kabar: /berita (filter ?jenis=, ?hal=) dan /berita/{slug}. */
final class PostController extends Controller
{
    public const PER_HALAMAN = 12;

    /** Filter chip: jenis => label ('' = semua jenis) */
    public const FILTER = [
        ''             => 'Semua',
        'berita'       => 'Berita',
        'artikel'      => 'Artikel',
        'berkah_zakat' => 'Berkah Zakat',
        'event'        => 'Event',
    ];

    public function index(Request $request): Response
    {
        $jenis = $request->query('jenis');
        $jenis = is_string($jenis) && in_array($jenis, Post::JENIS, true) ? $jenis : null;

        $paginator = Paginator::fromRequest($request, Post::hitung($jenis), self::PER_HALAMAN);

        return $this->view('site/post-list', [
            'jenis'     => $jenis,
            'posts'     => Post::daftar($jenis, self::PER_HALAMAN, $paginator->offset),
            'paginator' => $paginator,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $post = Post::bySlug($slug) ?? throw new HttpException(404);

        return $this->view('site/post-detail', [
            'post'    => $post,
            'terkait' => Post::terkait($post, 3),
        ]);
    }

    /** POST /berita/{slug}/dilihat — sinyal dari assets/js/dilihat.js (tanpa sesi, lihat DilihatService). */
    public function dilihat(Request $request, string $slug): Response
    {
        if (!DilihatService::dariSitusIni($request)) {
            throw new HttpException(403);
        }
        $id = Post::idTerbit($slug) ?? throw new HttpException(404);
        if (DilihatService::bot($request->header('User-Agent'))) {
            return new Response('', 204);
        }
        if (RateLimiter::terlalu('dilihat:' . $request->ip(), 120, 3600)) {
            throw new HttpException(429);
        }
        DilihatService::catat($id, $request);

        return Response::json(['dilihat' => Post::jumlahDilihat($id)]);
    }
}
