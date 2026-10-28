<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Post;
use App\Services\ZakatService;

/** /cari?q= — pencarian judul & ringkasan post, plus jenis zakat yang judulnya cocok. */
final class CariController extends Controller
{
    public const MIN_KARAKTER = 3;

    public function index(Request $request): Response
    {
        $q = $request->query('q', '');
        $q = is_string($q) ? mb_substr(trim($q), 0, 100) : '';

        $valid = mb_strlen($q) >= self::MIN_KARAKTER;
        $paginator = Paginator::fromRequest($request, $valid ? Post::hitungCari($q) : 0, PostController::PER_HALAMAN);

        $zakat = $valid
            ? array_values(array_filter(ZakatService::daftar(), static fn (array $z): bool => mb_stripos($z['judul'], $q) !== false))
            : [];

        return $this->view('site/cari', [
            'q'         => $q,
            'valid'     => $valid,
            'posts'     => $valid ? Post::cari($q, PostController::PER_HALAMAN, $paginator->offset) : [],
            'zakat'     => $zakat,
            'paginator' => $paginator,
        ]);
    }
}
