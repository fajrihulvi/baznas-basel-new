<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Wajib login untuk /admin/*; halaman admin tidak pernah di-cache & tidak diindeks.
 * Peran Kepala hanya boleh membuka dasbor, keluar, dan halaman di config admin.akses_kepala — selain itu 403.
 */
final class AuthAdmin implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::aktif()) {
            $ke = $request->method() === 'GET' ? ['ke' => $request->path()] : [];

            return Response::redirect(url('admin/masuk', $ke), 303);
        }
        if (Auth::kepala() && !self::bolehUntukKepala($request->path())) {
            throw new HttpException(403);
        }

        return self::tanpaCache($next($request));
    }

    private static function bolehUntukKepala(string $path): bool
    {
        if ($path === '/admin' || $path === '/admin/keluar') {
            return true;
        }
        foreach ((array) config('admin.akses_kepala', []) as $kunci) {
            if ($path === '/admin/' . $kunci || str_starts_with($path, '/admin/' . $kunci . '/')) {
                return true;
            }
        }

        return false;
    }

    public static function tanpaCache(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
