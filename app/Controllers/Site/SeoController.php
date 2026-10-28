<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\SitemapService;

final class SeoController extends Controller
{
    public function sitemap(Request $request): Response
    {
        return new Response(SitemapService::xml(), 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** Selain produksi seluruh situs ditutup dari mesin pencari (mis. staging). */
    public function robots(Request $request): Response
    {
        $isi = config('app.env') === 'production'
            ? "User-agent: *\nDisallow: /admin\nDisallow: /cari\n\nSitemap: " . url('sitemap.xml') . "\n"
            : "User-agent: *\nDisallow: /\n";

        return new Response($isi, 200, [
            'Content-Type'  => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
