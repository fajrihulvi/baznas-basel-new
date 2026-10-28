<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Middleware\AuthAdmin;

/** /admin/masuk & /admin/keluar */
final class AuthController extends Controller
{
    public function form(Request $request): Response
    {
        if (Auth::aktif()) {
            return Response::redirect(url('admin'), 303);
        }

        return AuthAdmin::tanpaCache($this->view('admin/masuk', ['ke' => self::tujuan($request->query('ke'))]));
    }

    public function masuk(Request $request): Response
    {
        $username = mb_substr((string) $request->input('username', ''), 0, 45);
        $ke = self::tujuan($request->input('ke'));
        $pesan = Auth::masuk($username, (string) $request->input('password', ''), $request->ip());

        if ($pesan !== null) {
            Session::flash('alert', ['jenis' => 'error', 'pesan' => $pesan]);
            Session::flash('_old', ['username' => $username]);

            return Response::redirect(url('admin/masuk', ['ke' => $ke]), 303);
        }

        return Response::redirect(url($ke), 303);
    }

    public function keluar(Request $request): Response
    {
        Auth::keluar();
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Anda telah keluar.']);

        return Response::redirect(url('admin/masuk'), 303);
    }

    /** Tujuan setelah masuk: hanya path di dalam /admin (cegah open redirect). */
    private static function tujuan(mixed $ke): string
    {
        $ke = is_string($ke) ? $ke : '';

        return preg_match('#^/admin(/[A-Za-z0-9/_-]*)?$#', $ke) && $ke !== '/admin/masuk' ? $ke : '/admin';
    }
}
