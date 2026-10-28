<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Models\Halaman;
use App\Models\Post;

/** sitemap.xml dinamis (cache 1 jam). */
final class SitemapService
{
    public const CACHE_KEY = 'sitemap';

    /** Halaman statis: path => prioritas */
    private const STATIS = [
        '/'                 => '1.0',
        '/bayar-zakat'      => '0.9',
        '/kalkulator-zakat' => '0.8',
        '/zakat'            => '0.8',
        '/program'          => '0.8',
        '/berita'           => '0.8',
        '/rekening'         => '0.7',
        '/konfirmasi'       => '0.5',
        '/regulasi'         => '0.6',
        '/laporan'          => '0.6',
        '/video'            => '0.5',
        '/galeri'           => '0.5',
        '/amil'             => '0.5',
        '/testimoni'        => '0.4',
    ];

    public static function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, 3600, static function (): string {
            $url = [];
            foreach (self::STATIS as $path => $prioritas) {
                $url[] = [$path, null, $prioritas];
            }
            foreach (SiteService::pilar() as $p) {
                $url[] = ['/program/' . $p['slug'], null, '0.7'];
            }
            foreach (ZakatService::daftar() as $z) {
                $url[] = ['/zakat/' . $z['slug'], null, '0.7'];
            }
            foreach (Halaman::untukSitemap() as $h) {
                $url[] = [($h['grup'] === 'tentang' ? '/tentang/' : '/halaman/') . $h['slug'], $h['updated_at'], '0.5'];
            }
            foreach (Post::untukSitemap() as $p) {
                $url[] = ['/berita/' . $p['slug'], $p['updated_at'] ?? $p['tgl'], '0.6'];
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($url as [$path, $ubah, $prioritas]) {
                $xml .= '  <url><loc>' . htmlspecialchars(url($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
                    . ($ubah ? '<lastmod>' . substr((string) $ubah, 0, 10) . '</lastmod>' : '')
                    . '<priority>' . $prioritas . '</priority></url>' . "\n";
            }

            return $xml . '</urlset>' . "\n";
        });
    }
}
