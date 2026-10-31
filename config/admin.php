<?php

declare(strict_types=1);

/*
 * Definisi konten yang dikelola panel admin (CRUD generik — App\Controllers\Admin\ResourceController).
 * Nama tabel/kolom & SQL di sini adalah konfigurasi tepercaya (bukan input pengguna).
 *
 * Tipe field: teks, panjang (textarea), html (editor + HtmlSanitizer), angka, rupiah (diketik 1.500.000), tanggal, pilihan,
 *             publish (Y/N), centang (1/0), slug (otomatis dari 'dari'), gambar (+ varian WebP), pdf.
 * 'pilihan'  : array nilai => label, atau 'sumber' = SQL "SELECT nilai, label …".
 * 'boleh_kosong' (angka): kosong disimpan NULL, bukan 0. 'geser' (angka): [min, maks, langkah, awal] → penggeser %.
 * Tipe 'warna': pemilih warna #rrggbb + centang "pakai warna bawaan" (NULL); 'pratinjau' => ['opasitas' => field, 'ikon' => [field, folder]].
 * 'kolom'    : kolom di daftar => tipe tampilan (teks|gambar|publish|tanggal|pilihan|centang|rupiah).
 * 'cache'    : kunci cache yang dihapus setelah simpan/hapus (home, site, zakat, sitemap).
 * 'aksi'     : default ['tambah', 'ubah', 'hapus'].
 * 'hook'     : kelas App\Admin\Hook untuk aturan khusus.
 */

$publish  = ['tipe' => 'publish', 'label' => 'Tampilkan di situs'];
$urutan   = ['tipe' => 'angka', 'label' => 'Urutan', 'aturan' => 'nullable|between:0,999', 'hint' => 'Angka kecil tampil lebih dulu.'];
$tanggal  = ['tipe' => 'tanggal', 'label' => 'Tanggal', 'aturan' => 'required|date'];
// Tautan isian admin: hanya path internal, http(s), WhatsApp/telepon/email (bukan javascript: dsb.)
$polaTautan = 'regex:#^(/(?!/)|https?://|mailto:|tel:)#i';

return [
    'menu' => [
        'Zakat'     => ['pembayaran' => 'Pembayaran', 'rekening' => 'Rekening', 'zakat' => 'Penjelasan Zakat', 'layanan' => 'Layanan Beranda'],
        'Penyaluran' => ['distribusi' => 'Distribusi'],
        'Konten'    => ['post' => 'Kabar & Kegiatan', 'banner' => 'Banner', 'video' => 'Video', 'galeri' => 'Galeri', 'testimoni' => 'Testimoni', 'mitra' => 'Mitra'],
        'Program'   => ['pilar' => '5 Pilar', 'sub-program' => 'Sub-program'],
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
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'jenis' => 'pilihan', 'tgl' => 'tanggal', 'is_featured' => 'centang', 'publish' => 'publish'],
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
                'is_featured'    => ['tipe' => 'centang', 'label' => 'Sorot di "Info BAZNAS" beranda'],
                'publish'        => $publish,
            ],
            'cache' => ['home', 'sitemap'],
            'hook'  => App\Admin\PostHook::class,
        ],

        'banner' => [
            'judul' => 'Banner Beranda', 'tunggal' => 'banner', 'tabel' => 'banner', 'pk' => 'id_banner',
            'urut' => 'urutan, tgl DESC, id_banner DESC', 'cari' => ['judul'],
            'kolom' => ['foto' => 'gambar', 'judul' => 'teks', 'urutan' => 'teks', 'publish' => 'publish'],
            'field' => [
                'judul'       => ['tipe' => 'teks', 'label' => 'Judul / teks alternatif', 'aturan' => 'required|max:255', 'hint' => 'Dibacakan pembaca layar & mesin pencari.'],
                'foto'        => ['tipe' => 'gambar', 'label' => 'Gambar desktop', 'folder' => 'banner', 'wajib' => true, 'hint' => 'Rasio ±16:7, mis. 1600 × 700 px. Hindari teks kecil.'],
                'foto_mobile' => ['tipe' => 'gambar', 'label' => 'Gambar HP (opsional)', 'folder' => 'banner', 'hint' => 'Rasio 4:3, mis. 1200 × 900 px. Bila semua banner punya gambar HP, beranda di HP memakai rasio 4:3.'],
                'link'        => ['tipe' => 'teks', 'label' => 'Tautan (opsional)', 'aturan' => ['nullable', 'max:255', $polaTautan], 'hint' => 'Mis. /bayar-zakat atau https://…'],
                'urutan'      => $urutan,
                'tgl'         => $tanggal,
                'publish'     => $publish,
            ],
            'cache' => ['home'],
        ],

        'layanan' => [
            'judul' => 'Layanan Beranda', 'tunggal' => 'layanan', 'tabel' => 'layanan', 'pk' => 'id',
            'urut' => 'urutan, id', 'cari' => ['judul'],
            'kolom' => ['judul' => 'teks', 'link' => 'teks', 'urutan' => 'teks', 'publish' => 'publish'],
            'field' => [
                'judul'     => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:100'],
                'deskripsi' => ['tipe' => 'panjang', 'label' => 'Deskripsi singkat', 'aturan' => 'nullable|max:200'],
                'link'      => ['tipe' => 'teks', 'label' => 'Tautan', 'aturan' => ['required', 'max:255', $polaTautan], 'hint' => 'Mis. /bayar-zakat, /rekening, atau https://wa.me/62…'],
                'urutan'    => $urutan,
                'publish'   => $publish,
            ],
            'cache' => ['home'],
        ],

        'pilar' => [
            'judul' => '5 Pilar Program', 'tunggal' => 'pilar', 'tabel' => 'kategori_program', 'pk' => 'id_kategori_program',
            'urut' => 'urutan, id_kategori_program', 'cari' => ['label'], 'aksi' => ['ubah'],
            'kolom' => ['ikon' => 'gambar', 'label' => 'teks', 'kategori_program' => 'teks', 'slug' => 'teks', 'urutan' => 'teks'],
            'tautan' => '/program/{slug}',
            'field' => [
                'label'            => ['tipe' => 'teks', 'label' => 'Nama pilar', 'aturan' => 'required|max:100', 'hint' => 'Mis. Basel Sehat.'],
                'kategori_program' => ['tipe' => 'teks', 'label' => 'Bidang', 'aturan' => 'required|max:100', 'hint' => 'Mis. Kesehatan.'],
                'deskripsi'        => ['tipe' => 'panjang', 'label' => 'Deskripsi singkat', 'aturan' => 'nullable|max:255'],
                'ikon'             => ['tipe' => 'gambar', 'label' => 'Ikon (opsional)', 'folder' => 'pilar',
                                       'hint' => 'PNG atau WebP berlatar transparan, persegi, minimal 256 × 256 px. Kosongkan untuk memakai ikon bawaan.'],
                'warna_latar'      => ['tipe' => 'warna', 'label' => 'Warna kotak ikon', 'aturan' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
                                       'pratinjau' => ['opasitas' => 'opasitas_latar', 'ikon' => ['ikon', 'pilar']],
                                       'hint' => 'Latar kotak bersudut bulat di belakang ikon (beranda & halaman Program). Bawaan: hijau muda (ikon unggahan) / hijau BAZNAS (ikon bawaan).'],
                'opasitas_latar'   => ['tipe' => 'angka', 'label' => 'Kepekatan warna kotak', 'aturan' => 'nullable|integer|between:0,100', 'boleh_kosong' => true,
                                       'geser' => [0, 100, 5, 100], 'hint' => '0% = transparan penuh, 100% = warna penuh. Mis. 15% untuk latar lembut di belakang ikon berwarna.'],
                'urutan'           => $urutan,
            ],
            'cache' => ['site', 'home', 'sitemap'],
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
                                     'hint' => 'Pilar otomatis mengikuti sub-program. Sub-program dikelola di menu Program → Sub-program.'],
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

        // 5 item bottom navigation (HP): kunci & urutan tetap (config/menu.php), admin hanya mengganti label & gambar ikon
        'ikon-navigasi' => [
            'judul' => 'Ikon Navigasi Bawah', 'tunggal' => 'ikon navigasi', 'tabel' => 'ikon_nav', 'pk' => 'id',
            'urut' => 'urutan, id', 'aksi' => ['ubah'],
            'keterangan' => 'Lima tombol di bilah bawah layar HP. Unggah gambar untuk mengganti ikon; kosongkan (centang "Hapus file ini") untuk kembali ke ikon bawaan. Ikon yang tidak aktif otomatis tampil keabu-abuan, berwarna saat halamannya dibuka.',
            'kolom' => ['ikon' => 'gambar', 'label' => 'teks', 'kunci' => 'teks'],
            'field' => [
                'label' => ['tipe' => 'teks', 'label' => 'Label', 'aturan' => 'required|max:14', 'hint' => 'Maksimal 14 huruf agar muat di layar HP, mis. "Bayar Zakat".'],
                'ikon'  => ['tipe' => 'gambar', 'label' => 'Gambar ikon (opsional)', 'folder' => 'ikon-nav',
                            'hint' => 'PNG atau WebP berlatar transparan, persegi, minimal 120 × 120 px. Kosong = ikon bawaan.'],
            ],
            'cache' => ['site'],
        ],

        'sub-program' => [
            'judul' => 'Sub-program', 'tunggal' => 'sub-program', 'tabel' => 'sub_program', 'pk' => 'id_sub_program',
            'urut' => 'id_kategori_program, id_sub_program', 'cari' => ['sub_program'],
            'kolom' => ['sub_program' => 'teks', 'id_kategori_program' => 'pilihan', 'slug' => 'teks'],
            'field' => [
                'sub_program'         => ['tipe' => 'teks', 'label' => 'Nama sub-program', 'aturan' => 'required|max:100'],
                'id_kategori_program' => ['tipe' => 'pilihan', 'label' => 'Pilar', 'aturan' => 'required',
                                          'sumber' => 'SELECT id_kategori_program, label FROM kategori_program ORDER BY urutan'],
                'slug'                => ['tipe' => 'slug', 'label' => 'Slug', 'dari' => 'sub_program', 'maks' => 100],
            ],
            'cache' => [],
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
            'kolom' => ['judul' => 'teks', 'grup' => 'pilihan', 'slug' => 'teks', 'publish' => 'publish'],
            'tautan' => '/halaman/{slug}',
            'field' => [
                'judul'   => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:150'],
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
            'kolom' => ['judul' => 'teks', 'tahun' => 'teks', 'tgl' => 'tanggal', 'publish' => 'publish'],
            'field' => [
                'judul'   => ['tipe' => 'teks', 'label' => 'Judul', 'aturan' => 'required|max:255', 'hint' => 'Mis. Laporan Keuangan Tahun 2025 (Audited).'],
                'tahun'   => ['tipe' => 'angka', 'label' => 'Tahun', 'aturan' => 'required|between:2000,2100'],
                'tgl'     => ['tipe' => 'tanggal', 'label' => 'Tanggal terbit', 'aturan' => 'nullable|date'],
                'file'    => ['tipe' => 'pdf', 'label' => 'File PDF', 'folder' => 'laporan', 'wajib' => true],
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
