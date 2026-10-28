<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/** Wajib login untuk /admin/*; halaman admin tidak pernah di-cache & tidak diindeks. */
final class AuthAdmin implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::aktif()) {
            $ke = $request->method() === 'GET' ? ['ke' => $request->path()] : [];

            return Response::redirect(url('admin/masuk', $ke), 303);
        }

        return self::tanpaCache($next($request));
    }

    public static function tanpaCache(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
