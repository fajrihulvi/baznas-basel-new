<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\TautanRusakService;
use Throwable;

final class App
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function run(): void
    {
        Env::load($this->basePath . '/.env');
        Config::load($this->basePath . '/config');
        date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Jakarta'));
        ErrorHandler::register();

        $request = Request::capture(rtrim((string) parse_url((string) Config::get('app.url'), PHP_URL_PATH), '/'));

        try {
            $response = $this->handle($request);
        } catch (Throwable $e) {
            $response = ErrorHandler::toResponse($e);
        }

        if ($response->status() === 404) {
            TautanRusakService::catat($request);
        }

        $this->finalize($request, $response)->send($request->method() !== 'HEAD');
    }

    private function handle(Request $request): Response
    {
        if ($kanonik = $this->kanonik($request)) {
            return $kanonik;
        }

        $path = $request->path();
        if ($path !== '/' && str_ends_with($path, '/')) {
            $query = $request->queryString();

            return Response::redirect(url(rtrim($path, '/')) . ($query !== '' ? '?' . $query : ''), 301);
        }

        $router = new Router();
        require $this->basePath . '/routes/web.php';
        require $this->basePath . '/routes/admin.php';

        return $router->dispatch($request);
    }

    /**
     * APP_FORCE_HTTPS: request http:// atau ke host lain (mis. www.) → 301 ke alamat APP_URL.
     * Hanya untuk GET/HEAD; POST ke http ditolak agar isian tidak terkirim tanpa enkripsi.
     */
    private function kanonik(Request $request): ?Response
    {
        $url = (string) Config::get('app.url');
        if (!Config::get('app.force_https') || !str_starts_with($url, 'https://')) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($request->secure() && $request->host() === $host) {
            return null;
        }
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            throw new HttpException(403);
        }
        $query = $request->queryString();

        return Response::redirect(url($request->path()) . ($query !== '' ? '?' . $query : ''), 301);
    }

    /** Header cache HTML + ETag sederhana (304 bila konten tidak berubah). */
    private function finalize(Request $request, Response $response): Response
    {
        if ($response->getHeader('Cache-Control') === null) {
            $response->header('Cache-Control', Session::isStarted() ? 'private, no-cache' : 'no-cache');
        }

        $hsts = (int) Config::get('app.hsts', 0);
        if ($hsts > 0 && $request->secure()) {
            $response->header('Strict-Transport-Security', 'max-age=' . $hsts);
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true) || $response->status() !== 200) {
            return $response;
        }

        $etag = '"' . hash('xxh3', $response->body()) . '"';
        $response->header('ETag', $etag);

        // Apache mod_deflate menambah akhiran "-gzip" pada ETag yang dikirim balik browser
        $ifNoneMatch = str_replace('-gzip"', '"', (string) $request->header('If-None-Match'));
        if (in_array($etag, array_map('trim', explode(',', $ifNoneMatch)), true)) {
            $response->setStatus(304)->setBody('');
        }

        return $response;
    }
}
