<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Admin\Resource;
use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Slug;
use App\Core\Upload;
use App\Core\UploadException;
use App\Core\Validator;
use App\Services\HtmlSanitizer;
use PDOException;
use Throwable;

/**
 * CRUD generik untuk semua konten di config/admin.php:
 *   GET  /admin/{res}             daftar (?q=, ?hal=)
 *   GET  /admin/{res}/baru        form tambah
 *   POST /admin/{res}             simpan baru
 *   GET  /admin/{res}/{id}/ubah   form ubah
 *   POST /admin/{res}/{id}        simpan perubahan
 *   POST /admin/{res}/{id}/hapus  hapus
 */
final class ResourceController extends Controller
{
    public const MAKS_GAMBAR = 5 * 1048576;
    public const MAKS_PDF    = 10 * 1048576;
    /** Tipe field berupa unggahan file ke public/uploads/{folder} ('dokumen' = PDF atau Excel) */
    public const TIPE_FILE = ['gambar', 'pdf', 'dokumen'];

    public function index(Request $request, string $res): Response
    {
        $r = $this->resource($res);
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $paginator = Paginator::fromRequest($request, $r->hitung($q), 20);

        return $this->view('admin/resource-index', [
            'r'         => $r,
            'q'         => $q,
            'baris'     => $r->daftar($q, 20, $paginator->offset),
            'paginator' => $paginator,
        ]);
    }

    public function create(Request $request, string $res): Response
    {
        $r = $this->resource($res, 'tambah');

        // Field bertanda 'dari_query' boleh diisi awal dari URL, mis. /admin/redirect/baru?dari=/home/x
        $awal = [];
        foreach ($r->field() as $nama => $f) {
            $nilai = $request->query($nama);
            if (!empty($f['dari_query']) && is_string($nilai)) {
                $awal[$nama] = mb_substr($nilai, 0, 255);
            }
        }

        return $this->view('admin/resource-form', ['r' => $r, 'baris' => null, 'awal' => $awal]);
    }

    public function store(Request $request, string $res): Response
    {
        return $this->simpan($request, $this->resource($res, 'tambah'), null);
    }

    public function edit(Request $request, string $res, string $id): Response
    {
        $r = $this->resource($res, 'ubah');
        $baris = $r->satu($this->id($id)) ?? throw new HttpException(404);

        return $this->view('admin/resource-form', ['r' => $r, 'baris' => $baris]);
    }

    public function update(Request $request, string $res, string $id): Response
    {
        $r = $this->resource($res, 'ubah');
        $lama = $r->satu($this->id($id)) ?? throw new HttpException(404);

        return $this->simpan($request, $r, $lama);
    }

    public function destroy(Request $request, string $res, string $id): Response
    {
        $r = $this->resource($res, 'hapus');
        $baris = $r->satu($this->id($id)) ?? throw new HttpException(404);

        try {
            $r->hapus((int) $baris[$r->pk()]);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Data tidak dapat dihapus karena masih dipakai data lain. Nonaktifkan saja bila tidak ingin ditampilkan.']);

            return Response::redirect(url('admin/' . $r->kunci), 303);
        }

        foreach ($r->field() as $nama => $f) {
            if (in_array($f['tipe'], self::TIPE_FILE, true)) {
                Upload::hapusPublik($f['folder'], $baris[$nama] ?? null);
            }
        }
        $r->lupakanCache();
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => ucfirst($r->tunggal()) . ' dihapus.']);

        return Response::redirect(url('admin/' . $r->kunci), 303);
    }

    private function simpan(Request $request, Resource $r, ?array $lama): Response
    {
        $id = $lama !== null ? (int) $lama[$r->pk()] : null;
        $in = $request->all();
        [$data, $v] = $this->olahIsian($r, $in, $id);
        $data = $r->hook()->sebelumSimpan($data, $lama, $v);

        $kembali = url('admin/' . $r->kunci . ($id === null ? '/baru' : "/{$id}/ubah"));
        if ($v->fails()) {
            return $this->gagal($v->errors(), $in, $kembali);
        }

        // File diproses setelah isian lain valid → tidak ada file yatim bila ada kesalahan
        $fileBaru = [];
        $fileLama = [];
        $errors = [];
        foreach ($r->field() as $nama => $f) {
            if (!in_array($f['tipe'], self::TIPE_FILE, true)) {
                continue;
            }
            $file = $request->file($nama);
            $adaFile = $file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE;
            try {
                if ($adaFile) {
                    if ($f['tipe'] === 'gambar') {
                        $hasil = Upload::gambarPublik($file, $f['folder'], self::MAKS_GAMBAR);
                        $data[$nama] = $hasil['file'];
                        if (isset($f['kolom_w'], $f['kolom_h'])) {
                            $data[$f['kolom_w']] = $hasil['width'];
                            $data[$f['kolom_h']] = $hasil['height'];
                        }
                    } elseif ($f['tipe'] === 'dokumen') {
                        $data[$nama] = Upload::dokumenPublik($file, $f['folder'], self::MAKS_PDF);
                    } else {
                        $data[$nama] = Upload::pdfPublik($file, $f['folder'], self::MAKS_PDF);
                    }
                    $fileBaru[] = [$f['folder'], $data[$nama]];
                    if (!empty($lama[$nama])) {
                        $fileLama[] = [$f['folder'], $lama[$nama]];
                    }
                } elseif (!empty($in['hapus_' . $nama]) && empty($f['wajib']) && !empty($lama[$nama])) {
                    $data[$nama] = null;
                    if (isset($f['kolom_w'], $f['kolom_h'])) {
                        $data[$f['kolom_w']] = $data[$f['kolom_h']] = null;
                    }
                    $fileLama[] = [$f['folder'], $lama[$nama]];
                } elseif (!empty($f['wajib']) && empty($lama[$nama])) {
                    $errors[$nama] = $f['label'] . ' wajib diunggah.';
                }
            } catch (UploadException $e) {
                $errors[$nama] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            foreach ($fileBaru as [$folder, $file]) {
                Upload::hapusPublik($folder, $file);
            }
            return $this->gagal($errors, $in, $kembali);
        }

        try {
            $id = $r->simpan($data, $id);
            $r->hook()->setelahSimpan($id, $data, $lama);
        } catch (Throwable $e) {
            foreach ($fileBaru as [$folder, $file]) {
                Upload::hapusPublik($folder, $file);
            }
            throw $e;
        }

        foreach ($fileLama as [$folder, $file]) {
            Upload::hapusPublik($folder, $file);
        }
        $r->lupakanCache();
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => ucfirst($r->tunggal()) . ($lama === null ? ' ditambahkan.' : ' disimpan.')]);

        return Response::redirect(url('admin/' . $r->kunci), 303);
    }

    /**
     * Ubah input form menjadi data kolom + validasi.
     *
     * @return array{0: array<string, mixed>, 1: Validator}
     */
    private function olahIsian(Resource $r, array $in, ?int $id): array
    {
        $data = [];
        $aturan = [];
        $label = [];
        $sanitizer = new HtmlSanitizer();

        foreach ($r->field() as $nama => $f) {
            $label[$nama] = $f['label'];
            $nilai = $in[$nama] ?? null;
            $nilai = is_scalar($nilai) ? trim((string) $nilai) : null;

            switch ($f['tipe']) {
                case 'teks':
                case 'panjang':
                case 'tanggal':
                    $data[$nama] = $nilai === '' ? null : $nilai;
                    break;
                case 'html':
                    $bersih = $sanitizer->clean((string) $nilai);
                    $data[$nama] = trim(strip_tags($bersih, '<img><iframe>')) === '' ? null : $bersih;
                    break;
                case 'angka':
                    $wajib = in_array('required', (array) (is_string($f['aturan'] ?? null) ? explode('|', $f['aturan']) : ($f['aturan'] ?? [])), true);
                    $data[$nama] = $nilai === '' || $nilai === null ? ($wajib || !empty($f['boleh_kosong']) ? null : 0) : $nilai;
                    break;
                case 'warna':
                    // Centang "Pakai warna bawaan" → NULL (input color selalu mengirim nilai)
                    $data[$nama] = !empty($in['bawaan_' . $nama]) || $nilai === '' || $nilai === null ? null : strtolower($nilai);
                    break;
                case 'rupiah':
                    // "Rp1.500.000" / "1.500.000" → "1500000" (divalidasi sebagai teks angka, lalu jadi int)
                    $data[$nama] = $nilai === '' || $nilai === null ? null : (string) angka($nilai);
                    break;
                case 'bulan':
                    // Dua pilihan {nama}_bulan + {nama}_tahun (atau "YYYY-MM") → "YYYY-MM-01"; salah format tetap teks → gagal aturan 'date'
                    $bl = is_scalar($in[$nama . '_bulan'] ?? null) ? (string) $in[$nama . '_bulan'] : '';
                    $th = is_scalar($in[$nama . '_tahun'] ?? null) ? (string) $in[$nama . '_tahun'] : '';
                    if ($bl !== '' || $th !== '') {
                        $nilai = ctype_digit($bl) && ctype_digit($th) ? sprintf('%04d-%02d', (int) $th, (int) $bl) : $th . '-' . $bl;
                    }
                    $data[$nama] = $nilai === '' || $nilai === null ? null : (preg_match('/^\d{4}-\d{2}$/', $nilai) ? $nilai . '-01' : $nilai);
                    break;
                case 'jumlah':
                    // Hanya tampilan (total otomatis); nilainya dihitung hook, tidak diambil dari form
                    break;
                case 'pilihan':
                    $data[$nama] = $nilai === '' ? null : $nilai;
                    break;
                case 'publish':
                    $data[$nama] = !empty($in[$nama]) ? 'Y' : 'N';
                    break;
                case 'centang':
                    $data[$nama] = !empty($in[$nama]) ? 1 : 0;
                    break;
            }
            if (isset($f['aturan'])) {
                $aturan[$nama] = $f['aturan'];
            }
        }

        $v = Validator::make(array_map(static fn ($x) => $x === null ? null : (string) $x, $data), $aturan, $label);

        foreach ($r->field() as $nama => $f) {
            if ($f['tipe'] === 'bulan' && $data[$nama] !== null && !preg_match('/^\d{4}-\d{2}-01$/', (string) $data[$nama])) {
                $v->tambah($nama, $f['label'] . ' tidak valid.');
            }
            if ($f['tipe'] === 'pilihan' && $data[$nama] !== null &&!array_key_exists((string) $data[$nama], $r->pilihan($nama))) {
                $v->tambah($nama, 'Pilihan ' . $f['label'] . ' tidak valid.');
            }
            if (in_array($f['tipe'], ['angka', 'rupiah'], true) && $data[$nama] !== null) {
                $data[$nama] = (int) $data[$nama];
            }
        }

        // Slug dihitung terakhir (butuh field sumbernya)
        foreach ($r->field() as $nama => $f) {
            if ($f['tipe'] !== 'slug') {
                continue;
            }
            $sumber = trim((string) ($in[$nama] ?? '')) ?: (string) ($data[$f['dari']] ?? '');
            if ($sumber === '') {
                continue; // field sumber kosong sudah dilaporkan validasi
            }
            $data[$nama] = Slug::unique($sumber, $this->tabel($r), $nama, $id, $r->pk(), (int) ($f['maks'] ?? 200));
        }

        return [$data, $v];
    }

    private function gagal(array $errors, array $in, string $kembali): Response
    {
        unset($in['_token']);
        Session::flash('errors', $errors);
        Session::flash('_old', array_filter($in, 'is_scalar'));
        Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Periksa kembali isian yang ditandai. File yang dipilih perlu diunggah ulang.']);

        return Response::redirect($kembali, 303);
    }

    private function resource(string $kunci, ?string $aksi = null): Resource
    {
        $r = Resource::cari($kunci) ?? throw new HttpException(404);
        if ($aksi !== null && !$r->boleh($aksi)) {
            throw new HttpException(404);
        }

        return $r;
    }

    private function tabel(Resource $r): string
    {
        return (string) config('admin.resource.' . $r->kunci . '.tabel');
    }

    private function id(string $id): int
    {
        return ctype_digit($id) ? (int) $id : throw new HttpException(404);
    }

    /** Nama admin yang sedang login (nilai bawaan field 'nama_admin'). */
    public static function namaAdmin(): string
    {
        return Auth::user()['nama'] ?? '';
    }
}
