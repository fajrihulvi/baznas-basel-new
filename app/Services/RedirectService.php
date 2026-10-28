<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Response;
use App\Models\Redirect;

/**
 * Redirect 301 dari URL situs lama (baznasbasel.id/home/...). Dipasang sebagai Router::fallback,
 * sehingga hanya dijalankan untuk request yang akan berakhir 404.
 */
final class RedirectService
{
    /** Folder upload lama /file/{tabel}/ → folder baru /uploads/{folder}/ */
    private const FOLDER_LAMA = [
        'berita' => 'post', 'artikel' => 'post', 'berkah_zakat' => 'post', 'program' => 'post', 'event' => 'post',
        'banner' => 'banner', 'deskripsi_zakat' => 'zakat', 'mitra' => 'mitra', 'rekening' => 'rekening',
        'perundangan' => 'perundangan', 'laporan_keuangan' => 'laporan',
    ];

    public static function untuk(Request $request): ?Response
    {
        $path = $request->path();

        // Keterangan pembayaran lama: ?kode=X → /bayar-zakat/X
        if ($path === '/home/pembayaran/keterangan') {
            $kode = (string) $request->query('kode', '');

            return self::ke(preg_match('/^[A-Za-z0-9]{4,12}$/', $kode) ? '/bayar-zakat/' . $kode : '/bayar-zakat');
        }

        // File upload lama yang ditautkan dari luar (media sosial, mesin pencari gambar)
        if (preg_match('#^/file/([a-z_]+)/([^/]+)$#', $path, $m) && isset(self::FOLDER_LAMA[$m[1]])) {
            $baru = 'uploads/' . self::FOLDER_LAMA[$m[1]] . '/' . basename($m[2]);

            return is_file(base_path('public/' . $baru)) ? self::ke('/' . $baru) : null;
        }

        $baris = Redirect::cari($path);
        if ($baris === null) {
            return null;
        }
        Redirect::catatHit((int) $baris['id']);

        // Query lama ikut dibawa bila tujuan belum punya query (mis. ?nominal=, ?kode=)
        $tujuan = $baris['ke'];
        $query  = $request->queryString();
        if ($query !== '' && !str_contains($tujuan, '?') && !str_contains($tujuan, '#')) {
            $tujuan .= '?' . $query;
        }

        return self::ke($tujuan);
    }

    private static function ke(string $path): Response
    {
        return Response::redirect(url($path), 301)->header('Cache-Control', 'public, max-age=86400');
    }
}
