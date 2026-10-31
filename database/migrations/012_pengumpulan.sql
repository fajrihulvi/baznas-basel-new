-- 012 — Pengumpulan ZIS dari Perangkat Daerah (UPZ) Pemkab Bangka Selatan.
-- Tabel baru saja; aman untuk admin lama. Data internal — tidak pernah ditampilkan di halaman publik.

-- Daftar Perangkat Daerah (dikelola di admin → Pengumpulan → Perangkat Daerah).
-- Data awal: OPD dari bangkaselatankab.go.id (Perda SOTK 2016 + perubahannya) dan 8 kecamatan.
-- Periksa kembali dengan Bagian Organisasi Setda; nama bisa diubah, PD yang dilebur dinonaktifkan (aktif = 0).
CREATE TABLE IF NOT EXISTS perangkat_daerah (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(150) NOT NULL,
  singkatan VARCHAR(30) NULL,                  -- label pendek di grafik dasbor
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1,         -- 0 = tidak muncul di pilihan & daftar "belum menyetor"
  PRIMARY KEY (id),
  UNIQUE KEY uniq_perangkat_daerah_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO perangkat_daerah (nama, singkatan, urutan) VALUES
  ('Sekretariat Daerah', 'Setda', 1),
  ('Sekretariat DPRD', 'Setwan', 2),
  ('Inspektorat Daerah', 'Inspektorat', 3),
  ('Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah', 'Bappelitbangda', 4),
  ('Badan Keuangan Daerah', 'Bakuda', 5),
  ('Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Daerah', 'BKPSDMD', 6),
  ('Dinas Pendidikan dan Kebudayaan', 'Dindikbud', 7),
  ('Dinas Kesehatan, Pengendalian Penduduk dan Keluarga Berencana', 'DKPPKB', 8),
  ('Dinas Pekerjaan Umum dan Penataan Ruang', 'DPUPR', 9),
  ('Dinas Perumahan dan Kawasan Permukiman', 'Disperkim', 10),
  ('Satuan Polisi Pamong Praja', 'Satpol PP', 11),
  ('Dinas Sosial, Pemberdayaan Perempuan dan Perlindungan Anak', 'DSPPPA', 12),
  ('Dinas Tenaga Kerja dan Transmigrasi', 'Disnakertrans', 13),
  ('Dinas Pertanian, Pangan dan Perikanan', 'DPPP', 14),
  ('Dinas Lingkungan Hidup', 'DLH', 15),
  ('Dinas Kependudukan dan Pencatatan Sipil', 'Disdukcapil', 16),
  ('Dinas Pemberdayaan Masyarakat dan Desa', 'DPMD', 17),
  ('Dinas Perhubungan', 'Dishub', 18),
  ('Dinas Komunikasi dan Informatika', 'Diskominfo', 19),
  ('Dinas Koperasi, Usaha Kecil Menengah, Perindustrian dan Perdagangan', 'DKUKMINDAG', 20),
  ('Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'DPMPTSP', 21),
  ('Dinas Pariwisata, Kepemudaan dan Olahraga', 'DPKO', 22),
  ('Dinas Perpustakaan dan Kearsipan', 'DPKAR', 23),
  ('Kantor Kesatuan Bangsa dan Politik', 'Kesbangpol', 24),
  ('RSUD Kabupaten Bangka Selatan', 'RSUD Basel', 25),
  ('RSUD Kriopanting', 'RSUD Kriopanting', 26),
  ('Kecamatan Toboali', 'Kec. Toboali', 27),
  ('Kecamatan Air Gegas', 'Kec. Air Gegas', 28),
  ('Kecamatan Payung', 'Kec. Payung', 29),
  ('Kecamatan Simpang Rimba', 'Kec. Simpang Rimba', 30),
  ('Kecamatan Tukak Sadai', 'Kec. Tukak Sadai', 31),
  ('Kecamatan Pulau Besar', 'Kec. Pulau Besar', 32),
  ('Kecamatan Lepar Pongok', 'Kec. Lepar Pongok', 33),
  ('Kecamatan Kepulauan Pongok', 'Kec. Kep. Pongok', 34);

-- Satu baris = satu setoran satu PD untuk satu bulan; boleh berisi 1, 2, atau 3 jenis sekaligus.
CREATE TABLE IF NOT EXISTS pengumpulan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  periode DATE NOT NULL,                       -- bulan & tahun setoran, selalu tanggal 1 (mis. 2026-10-01)
  tgl DATE NOT NULL,                           -- tanggal dana diterima BAZNAS
  id_perangkat_daerah INT UNSIGNED NOT NULL,
  zakat BIGINT UNSIGNED NULL,                  -- NULL = jenis ini tidak ada pada setoran ini
  infaq BIGINT UNSIGNED NULL,                  -- infaq/sedekah
  dskl BIGINT UNSIGNED NULL,                   -- dana sosial keagamaan lainnya
  total BIGINT UNSIGNED NOT NULL DEFAULT 0,    -- zakat + infaq + dskl (dihitung PengumpulanHook)
  keterangan TEXT NULL,
  petugas VARCHAR(100) NULL,
  dibuat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pengumpulan_periode (periode),
  KEY idx_pengumpulan_pd (id_perangkat_daerah, periode),
  CONSTRAINT fk_pengumpulan_pd FOREIGN KEY (id_perangkat_daerah) REFERENCES perangkat_daerah (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
