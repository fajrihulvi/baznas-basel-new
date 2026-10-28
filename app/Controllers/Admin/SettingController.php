<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\SiteService;

/** Pengaturan situs (tabel `setting`). */
final class SettingController extends Controller
{
    /** kunci => [label, tipe (teks|rupiah|url|email|wa|panjang), petunjuk] per grup */
    public const ISIAN = [
        'Umum' => [
            'site_name' => ['Nama situs', 'teks', null],
            'tagline'   => ['Tagline', 'teks', 'Dipakai sebagai deskripsi bawaan halaman.'],
        ],
        'Kontak' => [
            'alamat'      => ['Alamat kantor', 'panjang', null],
            'telepon'     => ['Telepon', 'teks', null],
            'whatsapp'    => ['WhatsApp', 'wa', 'Nomor tujuan tombol WhatsApp, mis. 0821-8021-8241.'],
            'email'       => ['Email', 'email', null],
            'jam_layanan' => ['Jam layanan', 'teks', 'Mis. Senin–Jumat, 08.00–15.30 WIB.'],
            'maps_url'    => ['Tautan Google Maps', 'url', null],
            'maps_lat'    => ['Lintang (latitude)', 'teks', null],
            'maps_lng'    => ['Bujur (longitude)', 'teks', null],
        ],
        'Media sosial' => [
            'instagram' => ['Instagram', 'url', null],
            'facebook'  => ['Facebook', 'url', null],
            'youtube'   => ['YouTube', 'url', null],
            'tiktok'    => ['TikTok', 'url', null],
            'twitter'   => ['X (Twitter)', 'url', null],
        ],
        'Zakat' => [
            'harga_emas_per_gram' => ['Harga emas per gram', 'rupiah', 'Dasar nishab zakat maal & penghasilan (85 gram). Perbarui berkala.'],
            'nishab_bulanan'      => ['Nishab penghasilan per bulan', 'rupiah', 'Opsional — kosongkan agar dihitung otomatis: 85 × harga emas ÷ 12. Isi bila mengikuti SK BAZNAS.'],
            'nilai_fitrah'        => ['Zakat fitrah per jiwa', 'rupiah', 'Sesuai SK tahun berjalan. Bila diisi, nominal fitrah dihitung otomatis dari jumlah jiwa.'],
            'nilai_fidyah'        => ['Fidyah per hari', 'rupiah', null],
        ],
        'Integrasi' => [
            'ga4_id' => ['ID Google Analytics 4', 'teks', 'Mis. G-XXXXXXX. Kosongkan bila tidak dipakai.'],
        ],
    ];

    public function edit(Request $request): Response
    {
        $nilai = array_column(Database::fetchAll('SELECT kunci, nilai FROM setting'), 'nilai', 'kunci');

        return $this->view('admin/setting', ['nilai' => $nilai]);
    }

    public function update(Request $request): Response
    {
        $data = [];
        $aturan = [];
        $label = [];
        foreach (self::ISIAN as $grup => $isian) {
            foreach ($isian as $kunci => [$lbl, $tipe]) {
                $nilai = trim((string) $request->input($kunci, ''));
                $label[$kunci] = $lbl;
                $data[$kunci] = match ($tipe) {
                    'rupiah' => angka($nilai) > 0 ? (string) angka($nilai) : '',
                    'wa'     => self::wa($nilai),
                    default  => $nilai,
                };
                $aturan[$kunci] = match ($tipe) {
                    'email'  => 'nullable|email|max:100',
                    'url'    => ['nullable', 'max:255', 'regex:#^https?://#i'],
                    'wa'     => ['nullable', 'regex:/^62\d{8,13}$/'],
                    'rupiah' => 'nullable|max:12',
                    default  => 'nullable|max:500',
                };
            }
        }
        $aturan['site_name'] = 'required|max:150';

        $v = Validator::make($data, $aturan, $label);
        if ($v->fails()) {
            Session::flash('errors', $v->errors());
            Session::flash('_old', $request->only(array_keys($data)));
            Session::flash('alert', ['jenis' => 'error', 'pesan' => 'Periksa kembali isian yang ditandai.']);

            return Response::redirect(url('admin/setting'), 303);
        }

        Database::transaction(static function () use ($data): void {
            foreach ($data as $kunci => $nilai) {
                Database::execute(
                    'INSERT INTO setting (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)',
                    [$kunci, $nilai === '' ? null : $nilai]
                );
            }
        });
        SiteService::forget();
        Cache::forget('zakat');
        Session::flash('alert', ['jenis' => 'sukses', 'pesan' => 'Pengaturan disimpan.']);

        return Response::redirect(url('admin/setting'), 303);
    }

    /** "0821-8021-8241" / "+62 821…" → "6282180218241" (format wa.me) */
    private static function wa(string $nomor): string
    {
        $d = (string) preg_replace('/\D/', '', $nomor);

        return match (true) {
            $d === ''               => '',
            str_starts_with($d, '0') => '62' . substr($d, 1),
            str_starts_with($d, '8') => '62' . $d,
            default                 => $d,
        };
    }
}
