<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\ZakatService;

/** Penjelasan zakat: /zakat dan /zakat/{slug}. */
final class ZakatController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('site/zakat-list', ['zakat' => ZakatService::daftar()]);
    }

    public function show(Request $request, string $slug): Response
    {
        $zakat = ZakatService::bySlug($slug) ?? throw new HttpException(404);

        return $this->view('site/zakat-detail', [
            'zakat' => $zakat,
            'lain'  => array_values(array_filter(ZakatService::daftar(), static fn (array $z): bool => $z['slug'] !== $slug)),
        ]);
    }
}
