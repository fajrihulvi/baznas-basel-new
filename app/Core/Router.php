<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Router GET/POST dengan parameter {nama}, grup prefix + middleware.
 * Semua request selain GET/HEAD wajib lolos verifikasi CSRF.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: array|Closure, middleware: list<class-string<Middleware>>}> */
    private array $routes = [];

    private string $prefix = '';

    /** @var list<class-string<Middleware>> */
    private array $middleware = [];

    /** @var (Closure(Request): ?Response)|null */
    private ?Closure $fallback = null;

    public function get(string $path, array|Closure $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array|Closure $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param list<class-string<Middleware>> $middleware
     * @param Closure(self): void $routes
     */
    public function group(string $prefix, array $middleware, Closure $routes): void
    {
        [$prevPrefix, $prevMiddleware] = [$this->prefix, $this->middleware];
        $this->prefix .= '/' . trim($prefix, '/');
        $this->middleware = [...$this->middleware, ...$middleware];

        $routes($this);

        [$this->prefix, $this->middleware] = [$prevPrefix, $prevMiddleware];
    }

    /**
     * Dipanggil sebelum 404 — baik saat tidak ada route yang cocok maupun saat handler melempar
     * HttpException(404) (mis. slug tidak ditemukan) — untuk redirect 301 URL lama.
     * Kembalikan null bila tidak menangani.
     */
    public function fallback(Closure $handler): void
    {
        $this->fallback = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method  = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            if ($method !== 'GET') {
                if ($request->melebihiBatasPost()) {
                    throw new HttpException(413);
                }
                Csrf::check($request);
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            try {
                return $this->run($route, $request, $params);
            } catch (HttpException $e) {
                if ($e->status() === 404 && $method === 'GET' && ($response = $this->cobaFallback($request)) !== null) {
                    return $response;
                }
                throw $e;
            }
        }

        if ($allowed !== []) {
            throw new HttpException(405, '', ['Allow' => implode(', ', array_unique($allowed))]);
        }

        return $this->cobaFallback($request) ?? throw new HttpException(404);
    }

    private function cobaFallback(Request $request): ?Response
    {
        $response = $this->fallback !== null ? ($this->fallback)($request) : null;

        return $response instanceof Response ? $response : null;
    }

    private function add(string $method, string $path, array|Closure $handler): void
    {
        $path  = '/' . trim($this->prefix . '/' . trim($path, '/'), '/');
        $regex = '';
        foreach (preg_split('/(\{[a-z_][a-z0-9_]*\})/i', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            $regex .= preg_match('/^\{([a-z_][a-z0-9_]*)\}$/i', $part, $m)
                ? '(?P<' . $m[1] . '>[^/]+)'
                : preg_quote($part, '#');
        }

        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#u',
            'handler'    => $handler,
            'middleware' => $this->middleware,
        ];
    }

    /** @param array<string, string> $params */
    private function run(array $route, Request $request, array $params): Response
    {
        $core = function (Request $request) use ($route, $params): Response {
            $handler = $route['handler'];
            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }
            $result = $handler($request, ...$params);

            return $result instanceof Response ? $result : Response::html((string) $result);
        };

        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            static fn (Closure $next, string $class): Closure => static fn (Request $r): Response => (new $class())->handle($r, $next),
            $core
        );

        return $pipeline($request);
    }
}
