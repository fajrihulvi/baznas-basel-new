<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\UploadException;
use App\Core\Validator;
use App\Models\Pengumpulan;
use App\Services\ImporPengumpulanService as Impor;

/**
 * /admin/pengumpulan/impor — impor setoran Perangkat Daerah dari Excel + unduh format Excel (semua PD aktif).
 * Peran Kepala ditolak middleware (bukan menu akses_kepala).
 */
final class PengumpulanImporController extends Controller
{
    public const MAKS_FILE = 2 * 1048576;

    public function format(Request $request): Response
    {
        return new Response(Impor::format(), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="format-impor-pengumpulan.xlsx"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    public function form(Request $request): Response
    {
        return $this->view('admin/pengumpulan-impor', ['galat' => Session::getFlash('impor_galat', [])]);
    }

    public function impor(Request $request): Response
    {
        $in = $request->all();
        $bl = (int) ($in['periode_bulan'] ?? 0);
        $th = (int) ($in['periode_tahun'] ?? 0);
        $data = [
            'periode' => $bl >= 1 && $bl <= 12 && $th >= 2000 && $th <= 2100 ? sprintf('%04d-%02d-01', $th, $bl) : '',
            'tgl'     => trim((string) ($in['tgl'] ?? '')),
            'mode'    => (string) ($in['mode'] ?? ''),
        ];
        $v = Validator::make($data, [
            'periode' => 'required|date',
            'tgl'     => 'required|date|before_or_equal:today',
            'mode'    => 'required|in:' . implode(',', array_keys(Impor::MODE)),
        ], ['periode' => 'Bulan dan tahun', 'tgl' => 'Tanggal pengumpulan', 'mode' => 'Pilihan data yang sudah ada']);

        $errors = $v->errors();
        $path = null;
        if ($errors === []) {
            try {
                $path = Upload::excel($request->file('berkas'), self::MAKS_FILE);
            } catch (UploadException $e) {
                $errors['berkas'] = $e->getMessage();
            }
        }
        if ($errors !== []) {
            return $this->kembali($in, $errors, []);
        }

        $hasil = Impor::impor($path, $data['periode'], $data['tgl'], $data['mode'], ResourceController::namaAdmin() ?: null);
        if ($hasil['galat'] !== []) {
            return $this->kembali($in, [], $hasil['galat']);
        }

        $bulan = Pengumpulan::namaPeriode($data['periode']);
        $pesan = "Impor {$bulan} selesai: {$hasil['simpan']} setoran tersimpan (" . format_rupiah($hasil['total']) . ')';
        if ($hasil['ganti'] > 0) {
            $pesan .= ", {$hasil['ganti']} PD menggantikan data lama";
        }
        if ($hasil['lewati'] > 0) {
            $pesan .= ", {$hasil['lewati']} PD dilewati karena sudah punya data bulan ini";
        }
        if ($hasil['kosong'] > 0) {
            $pesan .= ", {$hasil['kosong']} PD tanpa nominal tidak dicatat";
        }
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => $pesan . '.']);

        return Response::redirect(url('admin/pengumpulan'), 303);
    }

    /** PRG: kembali ke form dengan isian lama & daftar kesalahan. */
    private function kembali(array $in, array $errors, array $galat): Response
    {
        unset($in['_token']);
        Session::flash('errors', $errors);
        Session::flash('_old', array_filter($in, 'is_scalar'));
        Session::flash('impor_galat', $galat);
        Session::flash('alert', ['jenis' => 'error', 'pesan' => $galat !== []
            ? 'File belum diimpor — perbaiki baris yang disebutkan di bawah, lalu unggah ulang. Tidak ada data yang tersimpan.'
            : 'Periksa kembali isian yang ditandai. File perlu dipilih ulang.']);

        return Response::redirect(url('admin/pengumpulan/impor'), 303);
    }
}
