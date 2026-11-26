<?php

declare(strict_types=1);

/*
 * Definisi konten yang dikelola panel admin (CRUD generik — App\Controllers\Admin\ResourceController).
 * Nama tabel/kolom & SQL di sini adalah konfigurasi tepercaya (bukan input pengguna).
 *
 * Tipe field: teks, panjang (textarea), html (editor + HtmlSanitizer), angka, rupiah (diketik 1.500.000), tanggal, pilihan,
 *             publish (Y/N), centang (1/0), slug (otomatis dari 'dari'), gambar (+ varian WebP), pdf, dokumen (PDF atau Excel .xlsx/.xls tanpa makro).
 * 'pilihan'  : array nilai => label, atau 'sumber' = SQL "SELECT nilai, label …".
 * 'persen_dari' (angka): daftar field → persentase otomatis di bawah isian; 'satuan' (jumlah): mis. 'jiwa' (default rupiah).
 * 'baris' (panjang): tinggi textarea (default 3). 'boleh_kosong' (angka): kosong disimpan NULL, bukan 0. 'geser' (angka): [min, maks, langkah, awal] → penggeser %.
 * Tipe 'warna': pemilih warna #rrggbb + centang "pakai warna bawaan" (NULL); 'pratinjau' => ['opasitas' => field, 'ikon' => [field, folder]].
 * 'kolom'    : kolom di daftar => tipe tampilan (teks|gambar|publish|tanggal|pilihan|centang|rupiah|bulan|format|angka|angka_persen = angka + % dari kolom 'total').
 * 'cache'    : kunci cache yang dihapus setelah simpan/hapus (home, site, zakat, sitemap).
 * 'aksi'     : default ['tambah', 'ubah', 'hapus'].
 * 'hook'     : kelas App\Admin\Hook untuk aturan khusus.
 */

$publish  = ['tipe' => 'publish', 'label' => 'Tampilkan di situs'];
$urutan   = ['tipe' => 'angka', 'label' => 'Urutan', 'aturan' => 'nullable|between:0,999', 'hint' => 'Angka kecil tampil lebih dulu.'];
$tanggal  = ['tipe' => 'tanggal', 'label' => 'Tanggal', 'aturan' => 'required|date'];
$pilarManfaat = array_keys(App\Models\PenerimaManfaat::PROGRAM); // kolom jumlah jiwa per pilar
// Tautan isian admin: hanya path internal, http(s), WhatsApp/telepon/email (bukan javascript: dsb.)
$polaTautan = 'regex:#^(/(?!/)|https?://|mailto:|tel:)#i';

return [
    'menu' => [
        'Donasi'    => ['donasi' => 'Donasi Bantuan'],
        'Zakat'     => ['pembayaran' => 'Pembayaran', 'rekening' => 'Rekening', 'zakat' => 'Penjelasan Zakat', 'layanan' => 'Tunaikan Zakat (Beranda)'],
        'Pengumpulan' => ['pengumpulan' => 'Pengumpulan', 'perangkat-daerah' => 'Perangkat Daerah'],
        'Penyaluran' => ['distribusi' => 'Distribusi', 'penerima-manfaat' => 'Penerima Manfaat'],
        'Konten'    => ['post' => 'Kabar & Kegiatan', 'banner' => 'Banner', 'video' => 'Video', 'galeri' => 'Galeri', 'testimoni' => 'Testimoni', 'mitra' => 'Mitra'],
        'Program'   => ['pilar' => '5 Pilar', 'sub-program' => 'Kegiatan BAZNAS'],
        'Informasi' => ['halaman' => 'Halaman', 'regulasi' => 'Regulasi', 'kategori-regulasi' => 'Kategori Regulasi', 'laporan' => 'Laporan Keuangan', 'amil' => 'Amil'],
        // Tautan Rusak (/admin/tautan-rusak) & Status Sistem (/admin/status) sengaja tidak di menu — buka lewat alamatnya
        'Tampilan'  => ['ikon-navigasi' => 'Ikon Navigasi'],
        'Sistem'    => ['pengguna' => 'Pengguna', 'redirect' => 'Pengalihan URL', 'setting' => 'Pengaturan', 'akun' => 'Akun Saya'],
    ],
    // Menu yang hanya tampil (dan hanya bisa dibuka) oleh Administrator (user.level = 1)
    'khusus_administrator' => ['pengguna'],
    // Peran Kepala (user.level = 3): hanya Dasbor + menu berikut; alamat /admin lainnya ditolak (AuthAdmin)
    'akses_kepala' => ['akun'],

    'resource' => [
        'post' => [
            'judul' => 'Kabar & Kegiatan', 'tunggal' => 'kabar', 'tabel' => 'post', 'pk' => 'id',
            'urut' => 'tgl DESC, id DESC', 'cari' => ['judul'],
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'jenis' => 'pilihan', 'tgl' => 'tanggal', 'views' => 'angka', 'publish' => 'publish'],
            'label_kolom' => ['views' => 'Dilihat'],
            'tautan' => '/berita/{slug}',
            'field' => [
                'judul'          => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|min:5|max:200'],
                'jenis'          => ['tipe' => 'pilihan', 'label' => 'Jenis', 'aturan' => 'required',
                                     'pilihan' => ['berita' => 'Berita', 'artikel' => 'Artikel', 'berkah_zakat' => 'Berkah Zakat', 'program' => 'Program', 'event' => 'Event']],
                'id_sub_program' => ['tipe' => 'pilihan', 'label' => 'Termasuk program pilar', 'kosong' => '— Bukan kegiatan program —',
                                     'sumber' => "SELECT s.id_sub_program, CONCAT(k.label, ' — ', s.sub_program) FROM sub_program s JOIN kategori_program k ON k.id_kategori_program = s.id_kategori_program ORDER BY k.urutan, s.id_sub_program",
                                     'hint' => 'Kegiatan akan tampil juga di halaman pilar program.'],
                'konten'         => ['tipe' => 'html', 'label' => 'Isi', 'aturan' => 'required'],
                'foto'           => ['tipe' => 'gambar', 'label' => 'Foto utama', 'folder' => 'post', 'kolom_w' => 'foto_w', 'kolom_h' => 'foto_h', 'hint' => 'Disarankan mendatar (16:9), minimal lebar 1200 px.'],
                'ringkasan'      => ['tipe' => 'panjang', 'label' => 'Ringkasan', 'aturan' => 'nullable|max:300', 'hint' => 'Kosongkan untuk dibuat otomatis dari isi (dipakai di kartu & mesin pencari).'],
                'slug'           => ['tipe' => 'slug', 'label' => 'Alamat (slug)', 'dari' => 'judul', 'hint' => 'Kosongkan untuk dibuat otomatis dari judul. Bila diubah, alamat lama otomatis dialihkan.'],
                'penulis'        => ['tipe' => 'teks', 'label' => 'Penulis', 'aturan' => 'nullable|max:100'],
                'tgl'            => $tanggal,
                'publish'        => $publish,
            ],
            'cache' => ['home', 'sitemap'],
            'hook'  => App\Admin\PostHook::class,
        ],

        'banner' => [
            'judul' => 'Banner Beranda', 'tunggal' => 'banner', 'tabel' => 'banner', 'pk' => 'id_banner',
            'urut' => 'urutan, tgl DESC, id_banner DESC', 'cari' => ['judul'],
            'keterangan' => 'Banner bergeser di atas beranda. Centang "Banner program donasi" untuk menjadikannya program donasi: banner dapat diklik menuju halaman donasi, programnya tampil di beranda (di bawah Kabar BAZNAS), dan donatur mengisi formulir lalu transfer ke rekening resmi. Data donatur ada di menu Donasi → Donasi Bantuan.',
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'donasi' => 'centang', 'urutan' => 'teks', 'publish' => 'publish'],
            'label_kolom' => ['donasi' => 'Donasi'],
            'tautan' => '/donasi/{donasi_slug}', 'tautan_jika' => 'donasi',
            'grup' => [
                'donasi' => ['judul' => 'Detail program donasi', 'tampil_jika' => 'donasi', 'kelas' => 'grid gap-5 sm:grid-cols-2',
                             'hint' => 'Wajib diisi bila banner ini banner donasi. Donasi hanya bisa diisi antara tanggal dibuka dan ditutup.'],
            ],
            'field' => [
                'judul'       => ['tipe' => 'teks', 'label' => 'Judul / teks alternatif', 'aturan' => 'required|max:255', 'hint' => 'Dibacakan pembaca layar & mesin pencari.'],
                'foto'        => ['tipe' => 'gambar', 'label' => 'Gambar desktop', 'folder' => 'banner', 'wajib' => true, 'hint' => 'Rasio ±16:7, mis. 1600 × 700 px. Hindari teks kecil.'],
                'foto_mobile' => ['tipe' => 'gambar', 'label' => 'Gambar HP (opsional)', 'folder' => 'banner', 'hint' => 'Rasio 4:3, mis. 1200 × 900 px. Bila semua banner punya gambar HP, beranda di HP memakai rasio 4:3.'],
                'link'        => ['tipe' => 'teks', 'label' => 'Tautan (opsional)', 'aturan' => ['nullable', 'max:255', $polaTautan], 'hint' => 'Mis. /bayar-zakat atau https://… Diabaikan bila banner donasi (otomatis menuju halaman donasinya).'],
                'urutan'      => $urutan,
                'tgl'         => $tanggal,
                'publish'     => $publish,
                'donasi'      => ['tipe' => 'centang', 'label' => 'Banner program donasi (banner dapat diklik menuju halaman donasi)'],
                'donasi_nama'    => ['tipe' => 'teks', 'label' => 'Nama program donasi', 'aturan' => 'nullable|max:150', 'grup' => 'donasi', 'kelas' => 'sm:col-span-2', 'hint' => 'Mis. Bantuan Korban Banjir Toboali.'],
                'donasi_mulai'   => ['tipe' => 'tanggal', 'label' => 'Tanggal dibuka', 'aturan' => 'nullable|date', 'grup' => 'donasi', 'bawaan' => 'kosong'],
                'donasi_selesai' => ['tipe' => 'tanggal', 'label' => 'Tanggal ditutup', 'aturan' => 'nullable|date', 'grup' => 'donasi', 'bawaan' => 'kosong'],
                'donasi_target'  => ['tipe' => 'rupiah', 'label' => 'Target terkumpul', 'aturan' => 'nullable|integer|between:1000,999999999999', 'grup' => 'donasi'],
                'donasi_minimal' => ['tipe' => 'rupiah', 'label' => 'Nominal donasi minimal', 'aturan' => 'nullable|integer|between:1000,100000000', 'grup' => 'donasi',
                                     'hint' => 'Kosong = Rp10.000. Pilihan nominal cepat = 2×, 4×, 6×, 8× nominal ini.'],
                'donasi_terkumpul_luar' => ['tipe' => 'rupiah', 'label' => 'Dana terkumpul di luar formulir (opsional)', 'aturan' => 'nullable|integer|between:0,999999999999', 'grup' => 'donasi', 'kelas' => 'sm:col-span-2',
                                            'hint' => 'Mis. donasi tunai atau transfer yang tidak lewat formulir situs. Ditambahkan ke total terkumpul.'],
                'donasi_deskripsi' => ['tipe' => 'html', 'label' => 'Deskripsi program', 'grup' => 'donasi', 'kelas' => 'sm:col-span-2', 'hint' => 'Latar belakang, rencana penyaluran, dan penerima manfaat.'],
                'donasi_slug'    => ['tipe' => 'slug', 'label' => 'Alamat halaman donasi (slug)', 'dari' => 'donasi_nama', 'maks' => 170, 'grup' => 'donasi', 'kelas' => 'sm:col-span-2',
                                     'hint' => 'Kosongkan untuk dibuat otomatis dari nama program. Halaman: /donasi/{slug}.'],
            ],
            'hook'  => App\Admin\BannerHook::class,
            'cache' => ['home', 'sitemap'],
        ],

        // Donatur program donasi (formulir /donasi/{slug}/bayar). Data pribadi — tidak pernah tampil di situs.
        'donasi' => [
            'judul' => 'Donasi Bantuan', 'tunggal' => 'donasi', 'tabel' => 'donasi', 'pk' => 'id',
            'urut' => "status = 'menunggu' DESC, waktu DESC, id DESC", 'cari' => ['kode', 'nama', 'no_hp', 'email'],
            'cari_sql' => ['id_banner IN (SELECT id_banner FROM banner WHERE donasi_nama LIKE ?)', 'status LIKE ?'],
            'keterangan' => 'Donatur yang mengisi formulir donasi di situs. Setelah transfer, donatur mengonfirmasi lewat WhatsApp (nomor di Pengaturan). Cocokkan transfer di rekening dengan kode donasi, lalu Ubah → Status "Terverifikasi": nominalnya masuk ke total terkumpul program. Doa yang dicentang "Tampilkan doa" muncul di halaman program (nama atau "Hamba Allah", tanpa nominal & kontak). Cari menurut kode, nama, nomor, nama program, atau status.',
            'kolom' => ['waktu' => 'tanggal', 'kode' => 'teks', 'id_banner' => 'pilihan', 'nama' => 'teks', 'no_hp' => 'teks', 'nominal' => 'rupiah', 'status' => 'pilihan'],
            'label_kolom' => ['waktu' => 'Tanggal', 'kode' => 'Kode', 'id_banner' => 'Program', 'nama' => 'Donatur', 'no_hp' => 'WhatsApp'],
            'field' => [
                'status'      => ['tipe' => 'pilihan', 'label' => 'Status', 'aturan' => 'required', 'pilihan' => App\Models\Donasi::STATUS, 'bawaan' => 'menunggu',
                                  'hint' => 'Terverifikasi = transfer sudah diterima; nominal masuk ke total terkumpul program.'],
                'id_banner'   => ['tipe' => 'pilihan', 'label' => 'Program donasi', 'aturan' => 'required', 'kosong' => 'Pilih program…',
                                  'sumber' => "SELECT id_banner, donasi_nama FROM banner WHERE donasi = 1 AND donasi_nama IS NOT NULL ORDER BY donasi_mulai DESC, id_banner DESC"],
                'nominal'     => ['tipe' => 'rupiah', 'label' => 'Nominal', 'aturan' => 'required|integer|between:1000,10000000000', 'hint' => 'Sesuaikan dengan nominal yang benar-benar diterima.'],
                'nama'        => ['tipe' => 'teks', 'label' => 'Nama donatur', 'aturan' => 'required|max:100'],
                'anonim'      => ['tipe' => 'centang', 'label' => 'Tampilkan sebagai Hamba Allah'],
                'no_hp'       => ['tipe' => 'teks', 'label' => 'Nomor WhatsApp', 'aturan' => 'required|max:20'],
                'email'       => ['tipe' => 'teks', 'label' => 'Email', 'aturan' => 'nullable|email|max:100'],
                'id_rekening' => ['tipe' => 'pilihan', 'label' => 'Rekening tujuan', 'kosong' => '— Tidak tercatat —',
                                  'sumber' => "SELECT id_rekening, CONCAT(TRIM(nama_bank), ' — ', nomor_rekening) FROM rekening ORDER BY id_rekening"],
                'doa'         => ['tipe' => 'panjang', 'label' => 'Doa', 'aturan' => 'nullable|max:500'],
                'doa_tampil'  => ['tipe' => 'centang', 'label' => 'Tampilkan doa di halaman program (setelah terverifikasi)', 'bawaan' => '1'],
                'catatan'     => ['tipe' => 'panjang', 'label' => 'Catatan internal', 'aturan' => 'nullable|max:2000'],
            ],
            'hook'  => App\Admin\DonasiHook::class,
            'cache' => ['home'],
        ],

        // Kartu "Tunaikan Zakat" di beranda: hanya 4 teratas (urutan) yang tampil (App\Models\Layanan::MAKS_BERANDA)
        'layanan' => [
            'judul' => 'Tunaikan Zakat (Beranda)', 'tunggal' => 'kartu', 'tabel' => 'layanan', 'pk' => 'id',
            'urut' => "publish = 'N', urutan, id", 'cari' => ['judul'],
            'keterangan' => 'Kartu di bagian "Tunaikan Zakat" beranda. Hanya 4 kartu tampil (urutan terkecil yang ditampilkan). Tautan WhatsApp (https://wa.me/…) otomatis memakai nomor WhatsApp di Pengaturan, dan nomornya ditampilkan di kartu.',
            'kolom' => ['gambar' => 'gambar', 'judul' => 'teks', 'link' => 'teks', 'urutan' => 'teks', 'publish' => 'publish'],
            'field' => [
                'gambar'    => ['tipe' => 'gambar', 'label' => 'Gambar', 'folder' => 'layanan', 'kolom_w' => 'gambar_w', 'kolom_h' => 'gambar_h',
                                'hint' => 'Tampil di atas judul. Persegi (1:1), 800 × 800 px. Gambar yang tidak persegi dipotong bagian tengahnya. Kosongkan bila tanpa gambar.'],
                'judul'     => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:100'],
                'deskripsi' => ['tipe' => 'panjang', 'label' => 'Deskripsi singkat', 'aturan' => 'nullable|max:200', 'hint' => 'Maksimal ±3 baris di kartu.'],
                'link'      => ['tipe' => 'teks', 'label' => 'Tautan "Selengkapnya"', 'aturan' => ['required', 'max:255', $polaTautan],
                                'hint' => 'Mis. /bayar-zakat, /rekening, atau https://wa.me/?text=… (nomor diambil dari Pengaturan → WhatsApp).'],
                'urutan'    => $urutan,
                'publish'   => $publish,
            ],
            'cache' => ['home'],
        ],

        'pilar' => [
            'judul' => '5 Pilar Program', 'tunggal' => 'pilar', 'tabel' => 'kategori_program', 'pk' => 'id_kategori_program',
            'urut' => 'urutan, id_kategori_program', 'cari' => ['label'], 'aksi' => ['ubah'],
            'kolom' => ['ikon' => 'gambar', 'foto' => 'gambar', 'label' => 'teks', 'kategori_program' => 'teks', 'slug' => 'teks', 'urutan' => 'teks'],
            'tautan' => '/program/{slug}',
            'field' => [
                'label'            => ['tipe' => 'teks', 'label' => 'Nama pilar', 'aturan' => 'required|max:100', 'hint' => 'Mis. Basel Sehat.'],
                'kategori_program' => ['tipe' => 'teks', 'label' => 'Bidang', 'aturan' => 'required|max:100', 'hint' => 'Mis. Kesehatan.'],
                'deskripsi'        => ['tipe' => 'panjang', 'label' => 'Deskripsi', 'aturan' => 'nullable|max:5000', 'baris' => 10,
                                       'hint' => 'Boleh panjang dan beberapa paragraf (tekan Enter untuk paragraf baru), maks. 5.000 huruf. Ditampilkan utuh di halaman pilar (/program/…); di kartu & daftar hanya beberapa baris awal.'],
                'ikon'             => ['tipe' => 'gambar', 'label' => 'Ikon (opsional)', 'folder' => 'pilar',
                                       'hint' => 'PNG atau WebP berlatar transparan, persegi, minimal 256 × 256 px. Kosongkan untuk memakai ikon bawaan.'],
                'foto'             => ['tipe' => 'gambar', 'label' => 'Foto program (opsional)', 'folder' => 'pilar', 'kolom_w' => 'foto_w', 'kolom_h' => 'foto_h',
                                       'hint' => 'Tampil selebar halaman di atas deskripsi pada halaman pilar (/program/…), dan sebagai gambar kecil di daftar "Program Kami" halaman Tentang Kami. Mendatar 3:2, mis. 1536 × 1024 px; minimal lebar 1200 px agar tajam. Kosong = tanpa foto (kotak ikon di daftar).'],
                'warna_latar'      => ['tipe' => 'warna', 'label' => 'Warna kotak ikon', 'aturan' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
                                       'pratinjau' => ['opasitas' => 'opasitas_latar', 'ikon' => ['ikon', 'pilar']],
                                       'hint' => 'Latar kotak bersudut bulat di belakang ikon (beranda & halaman Program). Bawaan: hijau muda (ikon unggahan) / hijau BAZNAS (ikon bawaan).'],
                'opasitas_latar'   => ['tipe' => 'angka', 'label' => 'Kepekatan warna kotak', 'aturan' => 'nullable|integer|between:0,100', 'boleh_kosong' => true,
                                       'geser' => [0, 100, 5, 100], 'hint' => '0% = transparan penuh, 100% = warna penuh. Mis. 15% untuk latar lembut di belakang ikon berwarna.'],
                'urutan'           => $urutan,
            ],
            'cache' => ['site', 'home', 'sitemap'],
        ],

        // Setoran ZIS Perangkat Daerah per bulan; 1–3 jenis sekaligus. Statistiknya di dasbor (StatistikPengumpulanService).
        'pengumpulan' => [
            'judul' => 'Pengumpulan', 'tunggal' => 'pengumpulan', 'tabel' => 'pengumpulan', 'pk' => 'id',
            'urut' => 'periode DESC, tgl DESC, id DESC', 'cari' => ['keterangan', 'petugas'],
            'tombol' => ['Unduh format Excel' => '/admin/pengumpulan/format-impor', 'Impor Excel' => '/admin/pengumpulan/impor'],
            'cari_sql' => ['id_perangkat_daerah IN (SELECT id FROM perangkat_daerah WHERE nama LIKE ? OR singkatan LIKE ?)'],
            'keterangan' => 'Setoran zakat, infaq/sedekah, dan DSKL dari Perangkat Daerah per bulan. Satu setoran boleh berisi satu, dua, atau ketiga jenis sekaligus. Total & grafiknya tampil di Dasbor (juga untuk akun Kepala). Cari menurut nama atau singkatan PD. Setoran banyak PD sekaligus: Unduh format Excel → isi → Impor Excel.',
            'kolom' => ['id' => 'teks', 'periode' => 'bulan', 'id_perangkat_daerah' => 'pilihan', 'zakat' => 'rupiah', 'infaq' => 'rupiah', 'dskl' => 'rupiah', 'total' => 'rupiah'],
            // Di daftar cukup singkatan PD agar kolom nominal & tombol aksi tidak terdesak
            'sumber_kolom' => ['id_perangkat_daerah' => "SELECT id, COALESCE(NULLIF(TRIM(singkatan), ''), nama) FROM perangkat_daerah"],
            'label_kolom' => ['id' => 'ID', 'periode' => 'Bulan', 'tgl' => 'Tanggal', 'id_perangkat_daerah' => 'Perangkat Daerah', 'zakat' => 'Zakat', 'infaq' => 'Infaq/Sedekah', 'dskl' => 'DSKL', 'total' => 'Total'],
            'grup' => [
                'jenis' => ['judul' => 'Jenis pengumpulan & nominal', 'kelas' => 'grid gap-4 sm:grid-cols-3',
                            'hint' => 'Isi satu, dua, atau ketiga jenis sesuai setoran. Kosongkan jenis yang tidak ada.'],
            ],
            'field' => [
                'periode'             => ['tipe' => 'bulan', 'label' => 'Bulan dan tahun', 'aturan' => 'required|date',
                                          'hint' => 'Bulan setoran, mis. potongan gaji Oktober 2026. Grafik dasbor memakai bulan ini.'],
                'tgl'                 => ['tipe' => 'tanggal', 'label' => 'Tanggal pengumpulan', 'aturan' => 'required|date|before_or_equal:today',
                                          'hint' => 'Tanggal dana diterima BAZNAS.'],
                'id_perangkat_daerah' => ['tipe' => 'pilihan', 'label' => 'Perangkat Daerah', 'aturan' => 'required', 'kosong' => 'Pilih perangkat daerah…',
                                          'sumber' => "SELECT id, IF(aktif = 1, nama, CONCAT(nama, ' (nonaktif)')) FROM perangkat_daerah ORDER BY aktif DESC, urutan, nama",
                                          'hint' => 'Daftar dikelola di menu Pengumpulan → Perangkat Daerah.'],
                'zakat'               => ['tipe' => 'rupiah', 'label' => 'Zakat', 'aturan' => 'nullable|integer|between:1,999999999999', 'grup' => 'jenis'],
                'infaq'               => ['tipe' => 'rupiah', 'label' => 'Infaq/Sedekah', 'aturan' => 'nullable|integer|between:1,999999999999', 'grup' => 'jenis'],
                'dskl'                => ['tipe' => 'rupiah', 'label' => 'DSKL', 'aturan' => 'nullable|integer|between:1,999999999999', 'grup' => 'jenis',
                                          'hint' => 'Dana sosial keagamaan lainnya.'],
                'total'               => ['tipe' => 'jumlah', 'label' => 'Total setoran', 'dari' => ['zakat', 'infaq', 'dskl'], 'grup' => 'jenis',
                                          'kelas' => 'border-t border-gray-200 pt-4 sm:col-span-3'],
                'keterangan'          => ['tipe' => 'panjang', 'label' => 'Keterangan', 'aturan' => 'nullable|max:2000', 'hint' => 'Mis. nomor bukti setor atau catatan rekonsiliasi.'],
                'petugas'             => ['tipe' => 'teks', 'label' => 'Petugas pencatat', 'aturan' => 'nullable|max:100', 'bawaan' => 'nama_admin'],
            ],
            'hook'  => App\Admin\PengumpulanHook::class,
            'cache' => [],
        ],

        'perangkat-daerah' => [
            'judul' => 'Perangkat Daerah', 'tunggal' => 'perangkat daerah', 'tabel' => 'perangkat_daerah', 'pk' => 'id',
            'urut' => 'aktif DESC, urutan, nama', 'cari' => ['nama', 'singkatan'],
            'keterangan' => 'Daftar Perangkat Daerah (OPD & kecamatan) Pemkab Bangka Selatan untuk menu Pengumpulan. PD yang sudah punya data pengumpulan tidak bisa dihapus — hapus centang "Aktif" bila dilebur/berganti nama.',
            'kolom' => ['nama' => 'teks', 'singkatan' => 'teks', 'urutan' => 'teks', 'aktif' => 'centang'],
            'field' => [
                'nama'      => ['tipe' => 'teks', 'label' => 'Nama perangkat daerah', 'aturan' => 'required|max:150', 'hint' => 'Nama resmi, mis. Dinas Komunikasi dan Informatika.'],
                'singkatan' => ['tipe' => 'teks', 'label' => 'Singkatan', 'aturan' => 'nullable|max:30', 'hint' => 'Dipakai sebagai label pendek di grafik dasbor, mis. Diskominfo.'],
                'urutan'    => $urutan,
                'aktif'     => ['tipe' => 'centang', 'label' => 'Aktif (muncul di pilihan & dihitung di daftar "belum menyetor")', 'bawaan' => '1'],
            ],
            'hook'  => App\Admin\PerangkatDaerahHook::class,
            'cache' => [],
        ],

        // Penyaluran dana ke sub-program; pilar ikut dari sub-program. Statistiknya di dasbor (StatistikDistribusiService).
        'distribusi' => [
            'judul' => 'Distribusi', 'tunggal' => 'distribusi', 'tabel' => 'distribusi', 'pk' => 'id',
            'urut' => 'tgl DESC, id DESC', 'cari' => ['nama_program', 'lokasi', 'kecamatan'],
            'keterangan' => 'Catatan penyaluran dana ke sub-program pilar BAZNAS. Total & grafiknya tampil di Dasbor (juga untuk akun Kepala). Data ini tidak tampil di situs publik.',
            'kolom' => ['tgl' => 'tanggal', 'nama_program' => 'teks', 'id_sub_program' => 'pilihan', 'nominal' => 'rupiah', 'sumber_dana' => 'pilihan', 'penerima' => 'teks'],
            'field' => [
                'tgl'            => ['tipe' => 'tanggal', 'label' => 'Tanggal penyaluran', 'aturan' => 'required|date|before_or_equal:today'],
                'id_sub_program' => ['tipe' => 'pilihan', 'label' => 'Pilar — sub-program', 'aturan' => 'required', 'kosong' => 'Pilih sub-program…',
                                     'sumber' => "SELECT s.id_sub_program, CONCAT(k.label, ' — ', s.sub_program) FROM sub_program s JOIN kategori_program k ON k.id_kategori_program = s.id_kategori_program ORDER BY k.urutan, s.id_sub_program",
                                     'hint' => 'Pilar otomatis mengikuti sub-program. Sub-program dikelola di menu Program → Kegiatan BAZNAS.'],
                'nama_program'   => ['tipe' => 'teks', 'label' => 'Nama program / kegiatan', 'aturan' => 'required|min:5|max:200', 'hint' => 'Mis. Bantuan biaya berobat warga Kec. Toboali.'],
                'nominal'        => ['tipe' => 'rupiah', 'label' => 'Nominal disalurkan', 'aturan' => 'required|integer|between:1,999999999999'],
                'sumber_dana'    => ['tipe' => 'pilihan', 'label' => 'Sumber dana', 'aturan' => 'required', 'pilihan' => App\Models\Distribusi::SUMBER, 'bawaan' => 'zakat'],
                'asnaf'          => ['tipe' => 'pilihan', 'label' => 'Asnaf penerima', 'pilihan' => App\Models\Distribusi::ASNAF, 'kosong' => '— Tidak ada / bukan dana zakat —',
                                     'hint' => 'Wajib bila sumber dana zakat.'],
                'sifat'          => ['tipe' => 'pilihan', 'label' => 'Sifat penyaluran', 'aturan' => 'required', 'pilihan' => App\Models\Distribusi::SIFAT, 'bawaan' => 'konsumtif',
                                     'hint' => 'Konsumtif = bantuan langsung habis pakai; produktif = modal usaha/pemberdayaan.'],
                'penerima'       => ['tipe' => 'angka', 'label' => 'Jumlah penerima manfaat (jiwa)', 'aturan' => 'nullable|integer|between:0,1000000', 'boleh_kosong' => true],
                'kecamatan'      => ['tipe' => 'pilihan', 'label' => 'Kecamatan', 'pilihan' => App\Models\Distribusi::KECAMATAN, 'kosong' => '— Pilih kecamatan —'],
                'lokasi'         => ['tipe' => 'teks', 'label' => 'Desa / lokasi rinci', 'aturan' => 'nullable|max:150'],
                'keterangan'     => ['tipe' => 'panjang', 'label' => 'Keterangan', 'aturan' => 'nullable|max:2000'],
                'petugas'        => ['tipe' => 'teks', 'label' => 'Petugas pencatat', 'aturan' => 'nullable|max:100', 'bawaan' => 'nama_admin'],
            ],
            'hook'  => App\Admin\DistribusiHook::class,
            'cache' => [],
        ],

        // Jumlah penerima manfaat per bulan untuk 5 pilar (agregat, tanpa data pribadi). Laporan: dasbor & /tentang/sistem-pelaporan-zakat
        'penerima-manfaat' => [
            'judul' => 'Penerima Manfaat', 'tunggal' => 'penerima manfaat', 'tabel' => 'penerima_manfaat', 'pk' => 'id',
            'urut' => 'periode DESC', 'cari' => ['keterangan', 'petugas'],
            'cari_sql' => ["DATE_FORMAT(periode, '%Y-%m') LIKE ?"],
            'keterangan' => 'Jumlah penerima manfaat per bulan untuk 5 pilar program (satu data per bulan). Persentase dihitung otomatis. Tabel & grafik akumulasinya tampil di Dasbor (juga untuk akun Kepala) dan di situs, di bawah isi halaman Tentang → Sistem Pelaporan Zakat. Cari menurut tahun, mis. 2026 atau 2026-10.',
            'kolom' => ['periode' => 'bulan', 'peduli' => 'angka_persen', 'sehat' => 'angka_persen', 'makmur' => 'angka_persen', 'cerdas' => 'angka_persen', 'taqwa' => 'angka_persen', 'total' => 'angka'],
            'label_kolom' => ['periode' => 'Bulan', 'peduli' => 'Kemanusiaan', 'sehat' => 'Kesehatan', 'makmur' => 'Ekonomi', 'cerdas' => 'Pendidikan', 'taqwa' => 'Dakwah', 'total' => 'Total jiwa'],
            'grup' => [
                'pilar' => ['judul' => 'Jumlah penerima manfaat (jiwa)', 'kelas' => 'grid gap-4 sm:grid-cols-2',
                            'hint' => 'Isi kelima pilar sekaligus; kosongkan pilar yang tidak ada penerimanya. Persentase & total dihitung otomatis.'],
            ],
            'field' => [
                'periode'      => ['tipe' => 'bulan', 'label' => 'Bulan dan tahun', 'aturan' => 'required|date', 'hint' => 'Satu data untuk setiap bulan.'],
                'peduli'       => ['tipe' => 'angka', 'label' => 'Basel Peduli — Kemanusiaan', 'aturan' => 'nullable|integer|between:0,10000000', 'grup' => 'pilar', 'persen_dari' => $pilarManfaat],
                'sehat'        => ['tipe' => 'angka', 'label' => 'Basel Sehat — Kesehatan', 'aturan' => 'nullable|integer|between:0,10000000', 'grup' => 'pilar', 'persen_dari' => $pilarManfaat],
                'makmur'       => ['tipe' => 'angka', 'label' => 'Basel Makmur — Ekonomi', 'aturan' => 'nullable|integer|between:0,10000000', 'grup' => 'pilar', 'persen_dari' => $pilarManfaat],
                'cerdas'       => ['tipe' => 'angka', 'label' => 'Basel Cerdas — Pendidikan', 'aturan' => 'nullable|integer|between:0,10000000', 'grup' => 'pilar', 'persen_dari' => $pilarManfaat],
                'taqwa'        => ['tipe' => 'angka', 'label' => 'Basel Taqwa — Dakwah', 'aturan' => 'nullable|integer|between:0,10000000', 'grup' => 'pilar', 'persen_dari' => $pilarManfaat],
                'total'        => ['tipe' => 'jumlah', 'label' => 'Total penerima manfaat', 'dari' => $pilarManfaat, 'satuan' => 'jiwa', 'grup' => 'pilar'],
                'penghimpunan' => ['tipe' => 'rupiah', 'label' => 'Total penghimpunan ZIS bulan ini (opsional)', 'aturan' => 'nullable|integer|between:0,999999999999',
                                   'hint' => 'Tampil di situs sebagai "Total Penghimpunan" bila semua bulan pada laporan sudah diisi. Kosongkan bila tidak ingin diumumkan.'],
                'keterangan'   => ['tipe' => 'panjang', 'label' => 'Keterangan (internal)', 'aturan' => 'nullable|max:2000'],
                'petugas'      => ['tipe' => 'teks', 'label' => 'Petugas pencatat', 'aturan' => 'nullable|max:100', 'bawaan' => 'nama_admin'],
            ],
            'hook'  => App\Admin\PenerimaManfaatHook::class,
            'cache' => [],
        ],

        // 5 item bottom navigation (HP): kunci & urutan tetap (config/menu.php), admin hanya mengganti label & gambar ikon
        'ikon-navigasi' => [
            'judul' => 'Ikon Navigasi Bawah', 'tunggal' => 'ikon navigasi', 'tabel' => 'ikon_nav', 'pk' => 'id',
            'urut' => 'urutan, id', 'aksi' => ['ubah'],
            'keterangan' => 'Lima tombol di bilah bawah layar HP. Unggah gambar untuk mengganti ikon; kosongkan (centang "Hapus file ini") untuk kembali ke ikon bawaan. Semua ikon tampil berwarna asli; menu halaman yang sedang dibuka ditandai garis di atas ikon & label tebal. Kolom Kunci = nama ikon bawaan per posisi: "program" (posisi 2) kini menu Layanan, "kabar" (posisi 4) kini menu Program.',
            'kolom' => ['ikon' => 'gambar', 'label' => 'teks', 'kunci' => 'teks'],
            'label_kolom' => ['kunci' => 'Kunci (ikon bawaan)'],
            'field' => [
                'label' => ['tipe' => 'teks', 'label' => 'Label', 'aturan' => 'required|max:14', 'hint' => 'Maksimal 14 huruf agar muat di layar HP, mis. "Bayar Zakat".'],
                'ikon'  => ['tipe' => 'gambar', 'label' => 'Gambar ikon (opsional)', 'folder' => 'ikon-nav',
                            'hint' => 'PNG atau WebP berlatar transparan, persegi, minimal 120 × 120 px. Kosong = ikon bawaan.'],
            ],
            'cache' => ['site'],
        ],

        // Kegiatan BAZNAS = tabel sub_program (sub-program pilar). Foto & deskripsi (migrasi 018) tampil di "Kegiatan Kami" /tentang.
        'sub-program' => [
            'judul' => 'Kegiatan BAZNAS', 'tunggal' => 'kegiatan', 'tabel' => 'sub_program', 'pk' => 'id_sub_program',
            'urut' => 'id_kategori_program, id_sub_program', 'cari' => ['sub_program', 'deskripsi'],
            'keterangan' => 'Kegiatan (sub-program) tiap pilar. Tampil sebagai kartu di bagian "Kegiatan Kami" halaman Tentang Kami (/tentang) dan sebagai penyaring kegiatan di halaman pilar program. Kabar & Kegiatan serta Distribusi juga memilih dari daftar ini.',
            'kolom' => ['foto' => 'gambar', 'sub_program' => 'teks', 'id_kategori_program' => 'pilihan', 'slug' => 'teks'],
            'label_kolom' => ['sub_program' => 'Nama kegiatan', 'id_kategori_program' => 'Pilar'],
            'field' => [
                'sub_program'         => ['tipe' => 'teks', 'label' => 'Nama kegiatan', 'aturan' => 'required|max:100', 'hint' => 'Mis. Bantuan Modal Usaha Produktif.'],
                'id_kategori_program' => ['tipe' => 'pilihan', 'label' => 'Pilar', 'aturan' => 'required',
                                          'sumber' => 'SELECT id_kategori_program, label FROM kategori_program ORDER BY urutan'],
                'deskripsi'           => ['tipe' => 'panjang', 'label' => 'Deskripsi singkat', 'aturan' => 'nullable|max:300', 'hint' => 'Tampil di bawah nama kegiatan (±2 baris).'],
                'foto'                => ['tipe' => 'gambar', 'label' => 'Foto (opsional)', 'folder' => 'kegiatan', 'kolom_w' => 'foto_w', 'kolom_h' => 'foto_h',
                                          'hint' => 'Persegi (1:1), mis. 1080 × 1080 px (ukuran unggahan Instagram) atau 800 × 800 px; rasio lain dipotong bagian tengahnya. Kosong = kotak ikon pilar.'],
                'slug'                => ['tipe' => 'slug', 'label' => 'Slug', 'dari' => 'sub_program', 'maks' => 100],
            ],
            'cache' => ['site'],
        ],

        'zakat' => [
            'judul' => 'Penjelasan Zakat', 'tunggal' => 'penjelasan zakat', 'tabel' => 'deskripsi_zakat', 'pk' => 'id_deskripsi_zakat',
            'urut' => 'id_deskripsi_zakat', 'cari' => ['judul'],
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'jenis_zakat' => 'pilihan', 'tgl' => 'tanggal'],
            'tautan' => '/zakat/{slug}',
            'field' => [
                'judul'       => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:100'],
                'jenis_zakat' => ['tipe' => 'pilihan', 'label' => 'Jenis zakat (tujuan tombol bayar)', 'aturan' => 'required',
                                  'sumber' => 'SELECT id_jenis_zakat, TRIM(jenis_zakat) FROM jenis_zakat ORDER BY id_jenis_zakat'],
                'deskripsi'   => ['tipe' => 'html', 'label' => 'Penjelasan', 'aturan' => 'required', 'hint' => 'Hindari menulis angka nishab tahun tertentu — nilai terbaru diatur di Pengaturan.'],
                'foto'        => ['tipe' => 'gambar', 'label' => 'Gambar', 'folder' => 'zakat'],
                'slug'        => ['tipe' => 'slug', 'label' => 'Alamat (slug)', 'dari' => 'judul'],
                'tgl'         => $tanggal,
                'diposting'   => ['tipe' => 'teks', 'label' => 'Ditulis oleh', 'aturan' => 'required|max:255', 'bawaan' => 'nama_admin'],
            ],
            'cache' => ['zakat', 'home', 'sitemap'],
        ],

        'video' => [
            'judul' => 'Video', 'tunggal' => 'video', 'tabel' => 'video', 'pk' => 'id_video',
            'urut' => 'tgl DESC, id_video DESC', 'cari' => ['judul'],
            'kolom' => ['judul' => 'teks', 'youtube_id' => 'teks', 'tgl' => 'tanggal'],
            'field' => [
                'judul' => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:100'],
                'link'  => ['tipe' => 'teks', 'label' => 'Tautan YouTube', 'aturan' => 'required|max:100', 'hint' => 'Mis. https://www.youtube.com/watch?v=… atau https://youtu.be/…'],
                'tgl'   => $tanggal,
            ],
            'cache' => ['home'],
            'hook'  => App\Admin\VideoHook::class,
        ],

        'galeri' => [
            'judul' => 'Galeri', 'tunggal' => 'foto galeri', 'tabel' => 'galeri', 'pk' => 'id',
            'urut' => 'tgl DESC, id DESC', 'cari' => ['judul'],
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'jenis' => 'pilihan', 'tgl' => 'tanggal', 'publish' => 'publish'],
            'field' => [
                'judul'   => ['tipe' => 'teks', 'label' => 'Judul / keterangan', 'aturan' => 'required|max:150'],
                'jenis'   => ['tipe' => 'pilihan', 'label' => 'Jenis', 'aturan' => 'required', 'pilihan' => ['foto' => 'Foto', 'poster' => 'Poster', 'infografis' => 'Infografis']],
                'foto'    => ['tipe' => 'gambar', 'label' => 'Gambar', 'folder' => 'galeri', 'wajib' => true, 'kolom_w' => 'foto_w', 'kolom_h' => 'foto_h'],
                'tgl'     => $tanggal,
                'publish' => $publish,
            ],
            'cache' => [],
        ],

        'testimoni' => [
            'judul' => 'Testimoni', 'tunggal' => 'testimoni', 'tabel' => 'testimoni', 'pk' => 'id',
            // Draf (kiriman pengunjung yang belum dimoderasi) di atas
            'urut' => "publish = 'Y', urutan, created_at DESC", 'cari' => ['nama', 'isi', 'profesi'],
            'keterangan' => 'Testimoni kiriman pengunjung (form "Bagikan pengalaman Anda" di beranda) masuk sebagai Draf dan berada paling atas. Periksa isinya, lalu Ubah → centang "Tampilkan di situs". Nomor WhatsApp pengirim hanya untuk verifikasi — tidak ditampilkan.',
            'kolom' => ['foto' => 'gambar', 'nama' => 'teks', 'peran' => 'pilihan', 'profesi' => 'teks', 'rating' => 'teks', 'sumber' => 'teks', 'publish' => 'publish'],
            'field' => [
                'nama'    => ['tipe' => 'teks', 'label' => 'Nama', 'aturan' => 'required|max:100'],
                'peran'   => ['tipe' => 'pilihan', 'label' => 'Sebagai', 'pilihan' => App\Models\Testimoni::PERAN, 'kosong' => '— Tidak disebut —'],
                'profesi' => ['tipe' => 'teks', 'label' => 'Keterangan', 'aturan' => 'nullable|max:100', 'hint' => 'Mis. ASN, Toboali · Penerima modal usaha.'],
                'no_hp'   => ['tipe' => 'teks', 'label' => 'WhatsApp pengirim', 'aturan' => 'nullable|max:20', 'hint' => 'Hanya untuk verifikasi admin, tidak ditampilkan di situs.'],
                'isi'     => ['tipe' => 'panjang', 'label' => 'Testimoni', 'aturan' => 'required|max:1000'],
                'foto'    => ['tipe' => 'gambar', 'label' => 'Foto (opsional)', 'folder' => 'testimoni', 'hint' => 'Pastikan ada izin dari yang bersangkutan.'],
                'rating'  => ['tipe' => 'pilihan', 'label' => 'Rating', 'aturan' => 'required', 'pilihan' => ['5' => '5 bintang', '4' => '4 bintang', '3' => '3 bintang', '2' => '2 bintang', '1' => '1 bintang']],
                'urutan'  => $urutan,
                'publish' => $publish,
            ],
            'cache' => ['home'],
        ],

        'mitra' => [
            'judul' => 'Mitra', 'tunggal' => 'mitra', 'tabel' => 'mitra', 'pk' => 'id_mitra',
            'urut' => 'id_mitra', 'cari' => ['nama_mitra'],
            'kolom' => ['logo' => 'gambar', 'nama_mitra' => 'teks'],
            'field' => [
                'nama_mitra' => ['tipe' => 'teks', 'label' => 'Nama mitra', 'aturan' => 'required|max:255', 'hint' => 'Juga dipakai sebagai teks alternatif logo.'],
                'logo'       => ['tipe' => 'gambar', 'label' => 'Logo', 'folder' => 'mitra', 'wajib' => true, 'hint' => 'PNG berlatar transparan lebih baik.'],
            ],
            'cache' => ['home'],
        ],

        'rekening' => [
            'judul' => 'Rekening Resmi', 'tunggal' => 'rekening', 'tabel' => 'rekening', 'pk' => 'id_rekening',
            'urut' => 'id_rekening', 'cari' => ['nama_bank', 'nomor_rekening'],
            'kolom' => ['logo' => 'gambar', 'nama_bank' => 'teks', 'nomor_rekening' => 'teks', 'atas_nama' => 'teks'],
            'field' => [
                'nama_bank'      => ['tipe' => 'teks', 'label' => 'Nama bank', 'aturan' => 'required|max:255', 'hint' => 'Sertakan peruntukan, mis. "BSI (ZAKAT)" atau "BSI (INFAQ)" — dipakai untuk menyarankan rekening.'],
                'nomor_rekening' => ['tipe' => 'teks', 'label' => 'Nomor rekening', 'aturan' => 'required|max:50'],
                'atas_nama'      => ['tipe' => 'teks', 'label' => 'Atas nama', 'aturan' => 'required|max:100'],
                'logo'           => ['tipe' => 'gambar', 'label' => 'Logo bank', 'folder' => 'rekening'],
            ],
            'cache' => ['site'],
        ],

        'halaman' => [
            'judul' => 'Halaman', 'tunggal' => 'halaman', 'tabel' => 'halaman', 'pk' => 'id',
            'urut' => 'grup, urutan, id', 'cari' => ['judul'],
            'kolom' => ['gambar' => 'gambar', 'judul' => 'teks', 'grup' => 'pilihan', 'slug' => 'teks', 'publish' => 'publish'],
            'tautan' => '/halaman/{slug}',
            'field' => [
                'judul'   => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:150'],
                'gambar'  => ['tipe' => 'gambar', 'label' => 'Gambar halaman (opsional)', 'folder' => 'halaman', 'kolom_w' => 'gambar_w', 'kolom_h' => 'gambar_h',
                              'hint' => 'Tampil di atas isi halaman ini. Untuk kelompok Tentang Kami juga menjadi kartu di halaman Profil Kami (/tentang). Potret ±11:12, mis. 1200 × 1310 px (atau 1100 × 1200 px). Kosong = tanpa gambar (kartu berikon).'],
                'grup'    => ['tipe' => 'pilihan', 'label' => 'Kelompok', 'aturan' => 'required',
                              'pilihan' => ['tentang' => 'Tentang Kami (menu Tentang)', 'ketentuan' => 'Ketentuan (footer)', 'layanan' => 'Layanan (footer)', 'lainnya' => 'Lainnya']],
                'konten'  => ['tipe' => 'html', 'label' => 'Isi', 'aturan' => 'required'],
                'slug'    => ['tipe' => 'slug', 'label' => 'Alamat (slug)', 'dari' => 'judul', 'maks' => 170],
                'urutan'  => $urutan,
                'publish' => $publish,
            ],
            'cache' => ['site', 'sitemap'],
        ],

        'regulasi' => [
            'judul' => 'Regulasi', 'tunggal' => 'regulasi', 'tabel' => 'perundangan', 'pk' => 'id_perundangan',
            'urut' => 'id_kategori_perundangan, id_perundangan', 'cari' => ['judul'],
            'kolom' => ['judul' => 'teks', 'id_kategori_perundangan' => 'pilihan', 'publish' => 'publish'],
            'field' => [
                'judul'                   => ['tipe' => 'teks', 'label' => 'Judul dokumen', 'aturan' => 'required|max:255'],
                'id_kategori_perundangan' => ['tipe' => 'pilihan', 'label' => 'Kategori', 'aturan' => 'required',
                                              'sumber' => 'SELECT id_kategori_perundangan, TRIM(kategori_perundangan) FROM kategori_perundangan ORDER BY id_kategori_perundangan'],
                'file'                    => ['tipe' => 'pdf', 'label' => 'File PDF', 'folder' => 'perundangan', 'wajib' => true],
                'publish'                 => $publish,
            ],
            'cache' => [],
        ],

        'kategori-regulasi' => [
            'judul' => 'Kategori Regulasi', 'tunggal' => 'kategori', 'tabel' => 'kategori_perundangan', 'pk' => 'id_kategori_perundangan',
            'urut' => 'id_kategori_perundangan', 'cari' => ['kategori_perundangan'],
            'kolom' => ['kategori_perundangan' => 'teks'],
            'field' => [
                'kategori_perundangan' => ['tipe' => 'teks', 'label' => 'Nama kategori', 'aturan' => 'required|max:100'],
            ],
            'cache' => [],
        ],

        'laporan' => [
            'judul' => 'Laporan Keuangan', 'tunggal' => 'laporan', 'tabel' => 'laporan_keuangan', 'pk' => 'id_laporan_keuangan',
            'urut' => 'tahun DESC, tgl DESC, id_laporan_keuangan DESC', 'cari' => ['judul'],
            'keterangan' => 'Laporan tampil di halaman /laporan untuk diunduh publik. File boleh PDF atau Excel.',
            'kolom' => ['judul' => 'teks', 'tahun' => 'teks', 'tgl' => 'tanggal', 'file' => 'format', 'publish' => 'publish'],
            'label_kolom' => ['file' => 'Format'],
            'field' => [
                'judul'   => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:255', 'hint' => 'Mis. Laporan Keuangan Tahun 2025 (Audited).'],
                'tahun'   => ['tipe' => 'angka', 'label' => 'Tahun', 'aturan' => 'required|between:2000,2100'],
                'tgl'     => ['tipe' => 'tanggal', 'label' => 'Tanggal terbit', 'aturan' => 'nullable|date'],
                'file'    => ['tipe' => 'dokumen', 'label' => 'File laporan (PDF atau Excel)', 'folder' => 'laporan', 'wajib' => true],
                'publish' => $publish,
            ],
            'cache' => [],
        ],

        'amil' => [
            'judul' => 'Amil', 'tunggal' => 'amil', 'tabel' => 'amil', 'pk' => 'id',
            'urut' => "FIELD(bidang, 'pimpinan', 'pelaksana'), urutan, id", 'cari' => ['nama', 'jabatan'],
            'kolom' => ['foto' => 'gambar', 'nama' => 'teks', 'jabatan' => 'teks', 'bidang' => 'pilihan', 'publish' => 'publish'],
            'field' => [
                'nama'    => ['tipe' => 'teks', 'label' => 'Nama', 'aturan' => 'required|max:100'],
                'jabatan' => ['tipe' => 'teks', 'label' => 'Jabatan', 'aturan' => 'required|max:100'],
                'bidang'  => ['tipe' => 'pilihan', 'label' => 'Kelompok', 'aturan' => 'required', 'pilihan' => ['pimpinan' => 'Pimpinan', 'pelaksana' => 'Pelaksana']],
                'foto'    => ['tipe' => 'gambar', 'label' => 'Foto', 'folder' => 'amil', 'hint' => 'Potret, wajah di tengah (ditampilkan bulat).'],
                'urutan'  => $urutan,
                'publish' => $publish,
            ],
            'cache' => [],
        ],

        // Diisi otomatis oleh App\Services\TautanRusakService setiap ada 404
        'tautan-rusak' => [
            'judul' => 'Tautan Rusak (404)', 'tunggal' => 'catatan', 'tabel' => 'tautan_rusak', 'pk' => 'id',
            'urut' => 'hit DESC, terakhir DESC', 'cari' => ['path', 'referer'], 'aksi' => ['hapus'],
            'keterangan' => 'Alamat di situs ini yang dibuka pengunjung tetapi tidak ditemukan, mis. tautan lama dari media sosial atau mesin pencari. Buat pengalihan ke halaman yang sesuai (catatan otomatis hilang), atau hapus catatan yang tidak penting.',
            'kolom' => ['path' => 'teks', 'hit' => 'teks', 'terakhir' => 'tanggal', 'referer' => 'teks'],
            'aksi_baris' => ['Buat pengalihan' => '/admin/redirect/baru?dari={path}'],
            'field' => [
                'path'     => ['tipe' => 'teks', 'label' => 'Alamat'],
                'hit'      => ['tipe' => 'angka', 'label' => 'Dibuka'],
                'terakhir' => ['tipe' => 'tanggal', 'label' => 'Terakhir'],
                'referer'  => ['tipe' => 'teks', 'label' => 'Asal tautan'],
            ],
            'cache' => [],
        ],

        'redirect' => [
            'judul' => 'Pengalihan URL', 'tunggal' => 'pengalihan', 'tabel' => 'redirect', 'pk' => 'id',
            'urut' => 'id DESC', 'cari' => ['dari', 'ke'],
            'keterangan' => 'Alamat lama yang dialihkan permanen (301) ke alamat baru, agar tautan lama dan peringkat di mesin pencari tidak hilang.',
            'kolom' => ['dari' => 'teks', 'ke' => 'teks', 'hit' => 'teks'],
            'field' => [
                'dari' => ['tipe' => 'teks', 'label' => 'Alamat lama', 'aturan' => 'required|max:255', 'dari_query' => true,
                           'hint' => 'Mis. /home/berita/judul-lama (boleh ditempel lengkap dengan https://baznasbasel.id).'],
                'ke'   => ['tipe' => 'teks', 'label' => 'Dialihkan ke', 'aturan' => ['required', 'max:255', $polaTautan],
                           'hint' => 'Mis. /berita/judul-baru, /bayar-zakat, atau https://… untuk situs lain.'],
            ],
            'hook'  => App\Admin\RedirectHook::class,
            'cache' => [],
        ],
    ],
];
