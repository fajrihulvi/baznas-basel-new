-- 009 — Distribusi/penyaluran dana ke sub-program (pilar diambil lewat sub_program.id_kategori_program).
-- Tabel baru saja; aman untuk admin lama. Data internal — tidak pernah ditampilkan di halaman publik.

CREATE TABLE IF NOT EXISTS distribusi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  tgl DATE NOT NULL,
  id_sub_program INT NOT NULL,
  nama_program VARCHAR(200) NOT NULL,            -- nama kegiatan penyaluran
  nominal BIGINT UNSIGNED NOT NULL,              -- rupiah yang disalurkan
  sumber_dana VARCHAR(10) NOT NULL DEFAULT 'zakat', -- zakat | infaq | dskl
  asnaf VARCHAR(15) NULL,                        -- fakir … ibnu_sabil; wajib bila sumber_dana = zakat
  sifat VARCHAR(10) NOT NULL DEFAULT 'konsumtif',-- konsumtif (pendistribusian) | produktif (pendayagunaan)
  penerima INT UNSIGNED NULL,                    -- jumlah penerima manfaat (jiwa)
  kecamatan VARCHAR(40) NULL,
  lokasi VARCHAR(150) NULL,                      -- desa/alamat rinci
  keterangan TEXT NULL,
  petugas VARCHAR(100) NULL,
  dibuat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_distribusi_tgl (tgl),
  KEY idx_distribusi_sub (id_sub_program, tgl),
  CONSTRAINT fk_distribusi_sub_program FOREIGN KEY (id_sub_program) REFERENCES sub_program (id_sub_program)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
