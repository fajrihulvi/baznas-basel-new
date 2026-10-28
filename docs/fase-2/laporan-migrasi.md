# Laporan migrasi konten (Fase 2)

Dijalankan: 2026-10-28 09:48 — database `baznas_basel`

| | Jumlah |
|---|---|
| Baris di tabel lama | 58 |
| Baris di `post` | 47 |
| · jenis `berita` | 26 |
| · jenis `artikel` | 13 |
| · jenis `berkah_zakat` | 3 |
| · jenis `program` | 5 |
| Kelompok duplikat digabung | 11 |
| Redirect dicatat/diperbarui | 98 |
| video.youtube_id terisi | 5 |

## Duplikat yang digabung
- berita#49 disimpan (sub_program: 7); digabung: program#53
- berita#54 disimpan (sub_program: 2); digabung: program#63
- berita#58 disimpan (sub_program: 2); digabung: program#66
- berita#59 disimpan (sub_program: 2); digabung: program#68
- berita#60 disimpan (sub_program: 2); digabung: program#69
- berita#61 disimpan (sub_program: 8); digabung: program#70
- berita#62 disimpan (sub_program: 2); digabung: program#71
- berita#63 disimpan (sub_program: 7); digabung: program#72
- berita#64 disimpan (sub_program: 5); digabung: program#73
- berita#65 disimpan (sub_program: 9); digabung: program#74
- artikel#22 disimpan (sub_program: 2); digabung: program#65

## Foto tidak ditemukan di public/uploads/post
Tidak ada.

## Slug yang diubah
Tidak ada.

## Link video yang tidak dikenali
Tidak ada.

