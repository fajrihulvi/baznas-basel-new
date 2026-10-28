-- 004 — Data awal: setting, 5 pilar program, layanan, halaman.
-- INSERT IGNORE: tidak menimpa data yang sudah diubah admin bila dijalankan ulang.
-- Sumber kontak & koordinat: baznasbasel.id/home/hubungi_kami (28-10-2026).
-- Angka zakat (harga emas, nishab, fitrah) SENGAJA kosong — wajib diisi admin dari SK terbaru.

INSERT IGNORE INTO setting (kunci, nilai, grup) VALUES
  ('site_name', 'BAZNAS Kabupaten Bangka Selatan', 'umum'),
  ('tagline', 'Badan Amil Zakat Nasional Kabupaten Bangka Selatan', 'umum'),
  ('alamat', 'Komplek Perkantoran Terpadu Pemkab. Bangka Selatan, Gunung Namak, Toboali, Bangka Selatan', 'kontak'),
  ('email', 'baznaskab.bangkaselatan@baznas.go.id', 'kontak'),
  ('telepon', '0878-9519-9122', 'kontak'),
  ('whatsapp', '6282180218241', 'kontak'),
  ('jam_layanan', NULL, 'kontak'),
  ('maps_lat', '-3.0241497', 'kontak'),
  ('maps_lng', '106.5050903', 'kontak'),
  ('maps_url', 'https://www.google.com/maps?q=-3.0241497,106.5050903', 'kontak'),
  ('instagram', 'https://www.instagram.com/baznas.bangkaselatan/', 'sosial'),
  ('facebook', 'https://www.facebook.com/profile.php?id=100060080163628', 'sosial'),
  ('youtube', 'https://www.youtube.com/channel/UCo3pi3soROMZUhQ_LxdYx5A', 'sosial'),
  ('tiktok', NULL, 'sosial'),
  ('twitter', 'https://twitter.com/baznaskab_basel', 'sosial'),
  ('harga_emas_per_gram', NULL, 'zakat'),
  ('nishab_bulanan', NULL, 'zakat'),
  ('nilai_fitrah', NULL, 'zakat'),
  ('nilai_fidyah', '20000', 'zakat'),
  ('ga4_id', NULL, 'integrasi');

-- 5 pilar program
UPDATE kategori_program SET label = 'Basel Peduli', slug = 'basel-peduli', urutan = 1,
  deskripsi = 'Bantuan kemanusiaan, tanggap bencana, dan layanan mustahik.' WHERE id_kategori_program = 1;
UPDATE kategori_program SET label = 'Basel Cerdas', slug = 'basel-cerdas', urutan = 2,
  deskripsi = 'Beasiswa dan dukungan pendidikan bagi pelajar dan mahasiswa.' WHERE id_kategori_program = 2;
UPDATE kategori_program SET label = 'Basel Sehat', slug = 'basel-sehat', urutan = 3,
  deskripsi = 'Bantuan biaya berobat dan layanan kesehatan mustahik.' WHERE id_kategori_program = 3;
UPDATE kategori_program SET label = 'Basel Taqwa', slug = 'basel-taqwa', urutan = 4,
  deskripsi = 'Pembinaan mualaf, dakwah, dan syiar keagamaan.' WHERE id_kategori_program = 4;
UPDATE kategori_program SET label = 'Basel Makmur', slug = 'basel-makmur', urutan = 5,
  deskripsi = 'Pemberdayaan ekonomi dan modal usaha mustahik.' WHERE id_kategori_program = 5;

-- Kartu "Tunaikan Zakat"
INSERT IGNORE INTO layanan (id, judul, deskripsi, ikon, link, urutan, publish) VALUES
  (1, 'ZIS Online', 'Bayar zakat, infaq, dan sedekah dari mana saja.', NULL, '/bayar-zakat', 1, 'Y'),
  (2, 'Transfer Zakat', 'Transfer langsung ke rekening resmi BAZNAS Bangka Selatan.', NULL, '/rekening', 2, 'Y'),
  (3, 'Jemput Zakat', 'Petugas kami datang menjemput zakat Anda.', NULL, 'https://wa.me/6282180218241?text=Assalamualaikum%2C%20saya%20ingin%20menggunakan%20layanan%20jemput%20zakat.', 3, 'Y'),
  (4, 'Zakat Fitrah', 'Tunaikan zakat fitrah untuk Anda dan keluarga.', NULL, '/zakat/zakat-fitrah', 4, 'Y');

-- Halaman statis. Profil & Visi Misi dari situs lama (typo dibakukan); sisanya draf menunggu konten.
INSERT IGNORE INTO halaman (grup, judul, slug, konten, urutan, publish) VALUES
  ('tentang', 'Profil', 'profil',
   '<h2>Tentang BAZNAS</h2>
<p>Badan Amil Zakat Nasional (BAZNAS) merupakan badan resmi dan satu-satunya yang dibentuk oleh pemerintah berdasarkan Keputusan Presiden RI No. 8 Tahun 2001 yang memiliki tugas dan fungsi menghimpun dan menyalurkan zakat, infaq, dan sedekah (ZIS) pada tingkat nasional. Lahirnya Undang-Undang Nomor 23 Tahun 2011 tentang Pengelolaan Zakat semakin mengukuhkan peran BAZNAS sebagai lembaga pemerintah nonstruktural yang bersifat mandiri dan bertanggung jawab kepada Presiden melalui Menteri Agama.</p>
<p>Dengan demikian, BAZNAS bersama Pemerintah bertanggung jawab untuk mengawal pengelolaan zakat yang berasaskan: syariat Islam, amanah, kemanfaatan, keadilan, kepastian hukum, terintegrasi, dan akuntabilitas.</p>
<h2>Logo BAZNAS</h2>
<p>Logo BAZNAS terdiri dari lambang Burung Garuda Pancasila dengan tulisan BAZNAS di bawahnya, Badan Amil Zakat Nasional, dan Kabupaten Bangka Selatan.</p>',
   1, 'Y'),
  ('tentang', 'Visi dan Misi', 'visi-misi',
   '<h2>Visi</h2>
<p>“Menjadikan BAZNAS Kabupaten Bangka Selatan sebagai garda terdepan dalam menyejahterakan umat.”</p>
<h2>Misi</h2>
<ol>
<li>Memaksimalkan literasi zakat di Kabupaten Bangka Selatan serta memaksimalkan pengumpulan ZIS-DSKL secara masif dan terukur.</li>
<li>Memaksimalkan pendistribusian dan pendayagunaan ZIS-DSKL untuk mengentaskan kemiskinan, meningkatkan kesejahteraan umat, serta mengurangi kesenjangan sosial.</li>
<li>Memperkuat kompetensi, profesionalisme, integritas, dan kesejahteraan amil zakat secara berkelanjutan.</li>
<li>Modernisasi dan digitalisasi pengelolaan zakat di Kabupaten Bangka Selatan dengan sistem manajemen berbasis data.</li>
<li>Menjadikan BAZNAS Kabupaten Bangka Selatan sebagai lembaga yang unggul dan terpercaya dalam pengelolaan zakat.</li>
<li>Memperkuat sistem perencanaan, pengendalian, pelaporan, pertanggungjawaban, dan koordinasi pengelolaan zakat di Kabupaten Bangka Selatan.</li>
<li>Membangun kemitraan antara muzaki dan mustahik.</li>
<li>Meningkatkan sinergi dan kolaborasi seluruh pemangku kepentingan untuk pembangunan zakat Kabupaten Bangka Selatan.</li>
</ol>
<h2>Tujuan</h2>
<ol>
<li>Terwujudnya pengumpulan zakat nasional yang optimal.</li>
<li>Terwujudnya penyaluran ZIS-DSKL yang efektif dalam pengentasan kemiskinan, peningkatan kesejahteraan umat, dan pengurangan kesenjangan nasional.</li>
<li>Terwujudnya profesi amil zakat nasional yang kompeten, berintegritas, dan sejahtera.</li>
<li>Terwujudnya sistem manajemen dan basis data pengelolaan zakat nasional yang mengadopsi teknologi mutakhir.</li>
<li>Terwujudnya perencanaan, pengendalian, pelaporan, dan pertanggungjawaban pengelolaan zakat dengan tata kelola yang baik dan terstandar.</li>
<li>Terwujudnya hubungan saling tolong-menolong dalam kebaikan dan ketakwaan antara muzaki dan mustahik.</li>
<li>Terwujudnya sinergi dan kolaborasi seluruh pemangku kepentingan terkait dalam pembangunan zakat nasional.</li>
<li>Terwujudnya Indonesia sebagai pusat keunggulan (center of excellence) pengelolaan zakat dunia.</li>
</ol>',
   2, 'Y'),
  ('tentang', 'Struktur Organisasi', 'struktur-organisasi', '<p>Konten sedang disiapkan.</p>', 3, 'N'),
  ('ketentuan', 'Kebijakan Privasi', 'kebijakan-privasi', '<p>Konten sedang disiapkan.</p>', 1, 'N'),
  ('ketentuan', 'Syarat dan Ketentuan', 'syarat-ketentuan', '<p>Konten sedang disiapkan.</p>', 2, 'N'),
  ('layanan', 'Pertanyaan Umum (FAQ)', 'faq', '<p>Konten sedang disiapkan.</p>', 1, 'N');
