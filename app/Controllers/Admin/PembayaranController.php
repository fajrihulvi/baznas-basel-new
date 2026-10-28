<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\KonfirmasiPembayaran;
use App\Models\Pembayaran;

/** Pembayaran muzaki: daftar, detail + konfirmasi, verifikasi, dan bukti transfer (hanya admin). */
final class PembayaranController extends Controller
{
    public const STATUS = ['0' => 'Menunggu', '1' => 'Selesai', '2' => 'Dibatalkan'];

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $status = is_string($status) && isset(self::STATUS[$status]) ? $status : null;
        $konfirmasiBaru = $request->query('konfirmasi') === 'baru';
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 50);

        $paginator = Paginator::fromRequest($request, Pembayaran::hitungAdmin($status, $konfirmasiBaru, $q), 25);

        return $this->view('admin/pembayaran-index', [
            'status'         => $status,
            'konfirmasiBaru' => $konfirmasiBaru,
            'q'              => $q,
            'baris'          => Pembayaran::daftarAdmin($status, $konfirmasiBaru, $q, 25, $paginator->offset),
            'paginator'      => $paginator,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $p = Pembayaran::byId($this->id($id)) ?? throw new HttpException(404);

        return $this->view('admin/pembayaran-detail', [
            'p'          => $p,
            'konfirmasi' => KonfirmasiPembayaran::untukAdmin((int) $p['id']),
        ]);
    }

    public function status(Request $request, string $id): Response
    {
        $p = Pembayaran::byId($this->id($id)) ?? throw new HttpException(404);
        $status = (string) $request->input('status', '');
        if (!isset(self::STATUS[$status])) {
            throw new HttpException(422, 'Status tidak valid.');
        }
        Pembayaran::ubahStatus((int) $p['id'], $status);
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => "Status pembayaran {$p['kode']} diubah menjadi " . self::STATUS[$status] . '.']);

        return Response::redirect(url('admin/pembayaran/' . $p['id']), 303);
    }

    /** Verifikasi konfirmasi → pembayaran selesai; tolak → konfirmasi ditolak (pembayaran tetap menunggu). */
    public function konfirmasi(Request $request, string $id): Response
    {
        $k = KonfirmasiPembayaran::byId($this->id($id)) ?? throw new HttpException(404);
        $aksi = (string) $request->input('status', '');
        if (!in_array($aksi, ['diverifikasi', 'ditolak'], true)) {
            throw new HttpException(422, 'Status tidak valid.');
        }

        Database::transaction(static function () use ($k, $aksi): void {
            KonfirmasiPembayaran::ubahStatus((int) $k['id_konfirmasi_pembayaran'], $aksi);
            if ($aksi === 'diverifikasi' && $k['id_pembayaran']) {
                Pembayaran::ubahStatus((int) $k['id_pembayaran'], Pembayaran::SELESAI);
            }
        });
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => $aksi === 'diverifikasi'
            ? 'Konfirmasi diverifikasi — pembayaran ditandai selesai.'
            : 'Konfirmasi ditolak. Pembayaran tetap menunggu.']);

        return Response::redirect(url($k['id_pembayaran'] ? 'admin/pembayaran/' . $k['id_pembayaran'] : 'admin/pembayaran'), 303);
    }

    /** File bukti dari storage/bukti — hanya lewat sesi admin, tidak pernah dari folder publik. */
    public function bukti(Request $request, string $id): Response
    {
        $k = KonfirmasiPembayaran::byId($this->id($id)) ?? throw new HttpException(404);
        $folder = realpath(base_path('storage/bukti'));
        $file = $folder !== false ? realpath($folder . '/' . $k['bukti_pembayaran']) : false;
        if ($file === false || !str_starts_with($file, $folder . DIRECTORY_SEPARATOR) || !is_file($file)) {
            throw new HttpException(404, 'File bukti tidak tersedia (konfirmasi dari situs lama belum dipindahkan).');
        }

        $mime = str_ends_with($file, '.pdf') ? 'application/pdf' : 'image/webp';

        return new Response((string) file_get_contents($file), 200, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="bukti-' . $k['kode_pembayaran'] . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function id(string $id): int
    {
        return ctype_digit($id) ? (int) $id : throw new HttpException(404);
    }
}
