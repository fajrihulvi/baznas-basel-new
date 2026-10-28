<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public const FIELD = '_token';

    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }

        return $token;
    }

    public static function verify(mixed $token): bool
    {
        $expected = Session::get('_csrf');

        return is_string($expected) && $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    /** Dipanggil Router untuk setiap request selain GET/HEAD. */
    public static function check(Request $request): void
    {
        if (!self::verify($request->input(self::FIELD) ?? $request->header('X-CSRF-Token'))) {
            throw new HttpException(419);
        }
    }
}
