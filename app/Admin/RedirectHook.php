<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Models\Redirect;
use App\Models\TautanRusak;

/** Pengalihan 301: alamat lama dinormalkan; setelah disimpan, catatan tautan rusaknya dihapus. */
final class RedirectHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (!isset($data['dari'])) {
            return $data;
        }

        $dari = self::normalkan((string) $data['dari']);
        $data['dari'] = $dari;

        if ($dari === '/' || str_starts_with($dari . '/', '/admin/')) {
            $v->tambah('dari', 'Beranda dan halaman admin tidak bisa dialihkan.');
        } elseif ($dari === rtrim((string) ($data['ke'] ?? ''), '/')) {
            $v->tambah('ke', 'Tujuan tidak boleh sama dengan alamat lama.');
        } elseif (($ada = Redirect::cari($dari)) !== null && (int) $ada['id'] !== (int) ($lama['id'] ?? 0)) {
            $v->tambah('dari', 'Alamat lama ini sudah punya pengalihan.');
        }

        return $data;
    }

    public function setelahSimpan(int $id, array $data, ?array $lama): void
    {
        TautanRusak::hapusPath((string) $data['dari']);
    }

    /** "https://baznasbasel.id/home/x/?a=1" → "/home/x" (sama dengan Request::path()). */
    private static function normalkan(string $dari): string
    {
        $path = preg_match('#^https?://#i', $dari) ? (string) parse_url($dari, PHP_URL_PATH) : (string) strtok($dari, '?#');
        $path = '/' . ltrim((string) preg_replace('#/+#', '/', rawurldecode($path)), '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }
}
