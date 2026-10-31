<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Pengguna;

/**
 * /admin/pengguna — kelola akun panel admin (khusus Administrator).
 * Pengaman: tidak bisa menonaktifkan, menurunkan peran, atau menghapus akun sendiri,
 * sehingga selalu ada minimal satu Administrator aktif.
 */
final class PenggunaController extends Controller
{
    public const PERAN = [Auth::ADMINISTRATOR => 'Administrator', Auth::OPERATOR => 'Operator', Auth::KEPALA => 'Kepala'];

    public function index(Request $request): Response
    {
        $this->wajibAdministrator();
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        return $this->view('admin/pengguna-index', ['pengguna' => Pengguna::daftar($q), 'q' => $q, 'saya' => (int) Auth::user()['id']]);
    }

    public function create(Request $request): Response
    {
        $this->wajibAdministrator();

        return $this->view('admin/pengguna-form', ['baris' => null, 'diriSendiri' => false]);
    }

    public function store(Request $request): Response
    {
        $this->wajibAdministrator();
        [$data, $v] = $this->olah($request, null);
        if ($v->fails()) {
            return $this->gagal($request, $v->errors(), 'admin/pengguna/baru');
        }

        Pengguna::tambah($data, $this->hash($request), $request->ip());
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Pengguna ' . $data['username'] . ' ditambahkan. Sampaikan password awalnya secara langsung, dan minta segera diganti lewat Akun Saya.']);

        return Response::redirect(url('admin/pengguna'), 303);
    }

    public function edit(Request $request, string $id): Response
    {
        $this->wajibAdministrator();
        $baris = $this->cari($id);

        return $this->view('admin/pengguna-form', ['baris' => $baris, 'diriSendiri' => (int) $baris['id'] === (int) Auth::user()['id']]);
    }

    public function update(Request $request, string $id): Response
    {
        $this->wajibAdministrator();
        $baris = $this->cari($id);
        [$data, $v] = $this->olah($request, $baris);
        if ($v->fails()) {
            return $this->gagal($request, $v->errors(), 'admin/pengguna/' . $baris['id'] . '/ubah');
        }

        $passwordBaru = trim((string) $request->input('password', '')) !== '' ? $this->hash($request) : null;
        Pengguna::ubah((int) $baris['id'], $data, $passwordBaru);
        if ($passwordBaru !== null && (int) $baris['id'] === (int) Auth::user()['id']) {
            Auth::perbaruiSidik($passwordBaru); // mengganti password sendiri lewat menu ini tidak mengeluarkan diri sendiri
        }
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Pengguna ' . $data['username'] . ' disimpan.'
            . ($passwordBaru !== null && (int) $baris['id'] !== (int) Auth::user()['id'] ? ' Password diganti — sesi pengguna tersebut di perangkat lain otomatis berakhir.' : '')]);

        return Response::redirect(url('admin/pengguna'), 303);
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->wajibAdministrator();
        $baris = $this->cari($id);
        if ((int) $baris['id'] === (int) Auth::user()['id']) {
            Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Anda tidak dapat menghapus akun sendiri.']);

            return Response::redirect(url('admin/pengguna'), 303);
        }

        Pengguna::hapus((int) $baris['id']);
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Pengguna ' . $baris['username'] . ' dihapus.']);

        return Response::redirect(url('admin/pengguna'), 303);
    }

    /**
     * Isian form → data kolom + validasi. Peran & status akun sendiri tidak bisa diubah (selalu dipertahankan).
     *
     * @return array{0: array, 1: Validator}
     */
    private function olah(Request $request, ?array $lama): array
    {
        $in = $request->only(['username', 'f_name', 'l_name', 'email', 'no_hp', 'level', 'password', 'password_ulang']);
        $kosongNull = static fn (mixed $x): ?string => is_string($x) && trim($x) !== '' ? trim($x) : null;
        $diriSendiri = $lama !== null && (int) $lama['id'] === (int) Auth::user()['id'];

        $data = [
            'username' => mb_strtolower(trim((string) ($in['username'] ?? ''))),
            'f_name'   => trim((string) ($in['f_name'] ?? '')),
            'l_name'   => $kosongNull($in['l_name'] ?? null),
            'email'    => $kosongNull($in['email'] ?? null),
            'no_hp'    => $kosongNull($in['no_hp'] ?? null),
            'level'    => $diriSendiri ? (int) $lama['level'] : (int) ($in['level'] ?? 0),
            'status'   => $diriSendiri ? (string) $lama['status'] : ($request->input('aktif') ? '1' : '0'),
        ];
        $password = (string) ($in['password'] ?? '');

        $v = Validator::make([...$data, 'level' => (string) $data['level'], 'password' => $password], [
            'username' => ['required', 'min:3', 'max:45', 'regex:/^[a-z0-9._-]+$/'],
            'f_name'   => 'required|max:50',
            'l_name'   => 'nullable|max:50',
            'email'    => 'nullable|email|max:45',
            'no_hp'    => ['nullable', 'max:20', 'regex:/^\+?[0-9 -]{8,20}$/'],
            'level'    => 'required|in:' . implode(',', array_keys(self::PERAN)),
            'password' => $lama === null ? 'required|min:8|max:200' : 'nullable|min:8|max:200',
        ], [
            'username' => 'Username', 'f_name' => 'Nama depan', 'l_name' => 'Nama belakang', 'email' => 'Email',
            'no_hp' => 'Nomor HP', 'level' => 'Peran', 'password' => 'Password',
        ]);

        if (!isset($v->errors()['username']) && Pengguna::usernameDipakai($data['username'], $lama !== null ? (int) $lama['id'] : null)) {
            $v->tambah('username', 'Username sudah dipakai pengguna lain.');
        }
        if ($password !== '' && $password !== (string) ($in['password_ulang'] ?? '')) {
            $v->tambah('password_ulang', 'Ulangan password tidak sama.');
        }
        if ($password !== '' && strcasecmp($password, $data['username']) === 0) {
            $v->tambah('password', 'Password tidak boleh sama dengan username.');
        }

        return [$data, $v];
    }

    private function hash(Request $request): string
    {
        return password_hash((string) $request->input('password', ''), PASSWORD_BCRYPT, ['cost' => 12]);
    }

    private function gagal(Request $request, array $errors, string $kembali): Response
    {
        Session::flash('errors', $errors);
        Session::flash('_old', array_filter($request->only(['username', 'f_name', 'l_name', 'email', 'no_hp', 'level', 'aktif']), 'is_scalar'));
        Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Periksa kembali isian yang ditandai. Password perlu diketik ulang.']);

        return Response::redirect(url($kembali), 303);
    }

    private function cari(string $id): array
    {
        return ctype_digit($id) ? (Pengguna::byId((int) $id) ?? throw new HttpException(404)) : throw new HttpException(404);
    }

    /** Operator tidak boleh mengelola pengguna (halaman tidak ada baginya). */
    private function wajibAdministrator(): void
    {
        if (!Auth::administrator()) {
            throw new HttpException(403);
        }
    }
}
