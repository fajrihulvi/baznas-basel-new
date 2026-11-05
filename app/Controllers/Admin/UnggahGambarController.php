<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Image;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Core\UploadException;

/**
 * POST /admin/unggah-gambar — gambar untuk isi editor HTML (Halaman, Kabar, Penjelasan Zakat).
 * Disimpan di public/uploads/konten (+ varian WebP); yang disisipkan ke isi = varian 1200 px (WebP, ringan).
 * Dipanggil fetch() dari assets/js/admin.js dengan header X-CSRF-Token (dicek Router).
 */
final class UnggahGambarController extends Controller
{
    public const FOLDER = 'konten';

    public function simpan(Request $request): Response
    {
        try {
            $hasil = Upload::gambarPublik($request->file('gambar'), self::FOLDER, ResourceController::MAKS_GAMBAR);
        } catch (UploadException $e) {
            return Response::json(['galat' => $e->getMessage()], 422);
        }

        // Varian 1200 px selalu dibuat (tidak pernah diperbesar) — ukurannya mengikuti rasio asli
        $lebar = min(1200, $hasil['width']);
        $tinggi = (int) round($hasil['height'] * $lebar / max(1, $hasil['width']));
        // Path tanpa domain agar isi tetap benar setelah pindah dari localhost ke hosting
        $src = (string) parse_url(url('uploads/' . self::FOLDER . '/' . Image::variant($hasil['file'], 1200)), PHP_URL_PATH);

        return Response::json(['src' => $src, 'width' => $lebar, 'height' => $tinggi]);
    }
}
