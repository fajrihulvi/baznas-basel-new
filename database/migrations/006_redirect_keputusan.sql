-- 006 — Tujuan redirect yang tertunda di Fase 0 + halaman Kurban.
-- Situs lama /home/layanan/kurban berisi ajakan kurban + tombol "Daftar Kurban Online" ke WhatsApp
-- 6282184414795; dipertahankan sebagai halaman agar layanan tidak hilang.

UPDATE redirect SET ke = '/#kontak' WHERE dari = '/home/hubungi_kami';
UPDATE redirect SET ke = '/#mitra' WHERE dari = '/home/mitra';
UPDATE redirect SET ke = '/halaman/kurban' WHERE dari = '/home/layanan/kurban';

INSERT IGNORE INTO halaman (grup, judul, slug, konten, urutan, publish) VALUES
  ('layanan', 'Kurban Online', 'kurban',
   '<p>Mari berkurban bersama BAZNAS Kabupaten Bangka Selatan. Hewan kurban Anda akan disalurkan kepada mustahik yang membutuhkan di seluruh Bangka Selatan.</p>
<p>Pendaftaran kurban online dilayani melalui WhatsApp layanan kurban BAZNAS Bangka Selatan.</p>
<p><a href="https://wa.me/6282184414795?text=Assalamualaikum%2C%20saya%20ingin%20mendaftar%20kurban%20online." target="_blank" rel="noopener noreferrer">Daftar Kurban Online melalui WhatsApp</a></p>',
   2, 'Y');
