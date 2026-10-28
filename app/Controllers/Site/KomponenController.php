<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Models\Layanan;
use App\Models\Post;
use App\Models\Video;
use App\Services\SiteService;

/**
 * Katalog komponen untuk pengembangan (hanya terdaftar bila APP_DEBUG=true).
 */
final class KomponenController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('site/komponen', [
            'posts'     => Post::terbaru(8),
            'pilar'     => SiteService::pilar(),
            'layanan'   => Layanan::terbit(),
            'video'     => Video::terbaru(3),
            'paginator' => new Paginator(120, 10, 5, '/_komponen', ['jenis' => 'berita']),
            // Contoh — tabel testimoni masih kosong
            'testimoni' => [
                ['id' => 1, 'nama' => 'Siti Aminah', 'profesi' => 'Penerima bantuan modal usaha', 'foto' => null, 'rating' => 5,
                 'isi' => 'Alhamdulillah, bantuan modal usaha dari BAZNAS Bangka Selatan membuat warung saya bisa berjalan lagi. Sekarang saya bisa membiayai sekolah anak-anak dan mulai menabung sedikit demi sedikit. Terima kasih kepada para muzaki yang telah menitipkan zakatnya.'],
                ['id' => 2, 'nama' => 'Ahmad Fauzi', 'profesi' => 'ASN, Muzaki', 'foto' => null, 'rating' => 4,
                 'isi' => 'Bayar zakat penghasilan sekarang praktis, cukup transfer dan konfirmasi.'],
            ],
        ]);
    }
}
