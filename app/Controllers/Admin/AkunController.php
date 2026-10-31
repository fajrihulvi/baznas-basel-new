<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/** /admin/akun — ganti password akun yang sedang login. */
final class AkunController extends Controller
{
    public function edit(Request $request): Response
    {
        return $this->view('admin/akun', ['user' => Auth::user()]);
    }

    public function update(Request $request): Response
    {
        $user = Auth::user();
        $in = $request->only(['password_lama', 'password_baru', 'password_ulang']);
        $v = Validator::make($in, [
            'password_lama'  => 'required',
            'password_baru'  => 'required|min:8|max:200',
            'password_ulang' => 'required',
        ], ['password_lama' => 'Password saat ini', 'password_baru' => 'Password baru', 'password_ulang' => 'Ulangi password baru']);

        $hash = (string) Database::fetchColumn('SELECT password FROM user WHERE id_user = ?', [$user['id']]);
        if (!isset($v->errors()['password_lama']) && !password_verify((string) $in['password_lama'], $hash)) {
            $v->tambah('password_lama', 'Password saat ini salah.');
        }
        if (($in['password_baru'] ?? '') !== ($in['password_ulang'] ?? '')) {
            $v->tambah('password_ulang', 'Ulangan password tidak sama.');
        }
        if (!isset($v->errors()['password_baru']) && strcasecmp((string) $in['password_baru'], $user['username']) === 0) {
            $v->tambah('password_baru', 'Password tidak boleh sama dengan username.');
        }

        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Password belum diganti. Periksa isian yang ditandai.']);

            return Response::redirect(url('admin/akun'), 303);
        }

        $hashBaru = password_hash((string) $in['password_baru'], PASSWORD_BCRYPT, ['cost' => 12]);
        Database::execute('UPDATE user SET password = ?, token = NULL, token_expired = NULL WHERE id_user = ?', [$hashBaru, $user['id']]);
        Session::regenerate();
        Auth::perbaruiSidik($hashBaru); // sesi ini tetap masuk; sesi di perangkat lain berakhir
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Password berhasil diganti.']);

        return Response::redirect(url('admin/akun'), 303);
    }
}
