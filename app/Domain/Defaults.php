<?php
declare(strict_types=1);
namespace App\Domain;

final class Defaults
{
    /** Pengaturan awal toko. Pemilik mengubahnya lewat menu Pengaturan. */
    public static function settings(): array
    {
        return [
            'toko' => 'Dapur Snack Snackin', 'wa' => '0812-0000-0000', 'buka' => '10.00', 'tutup' => '21.00', 'aktif' => true,
            'ongkir' => 5000, 'area' => 'Radius 3 km dari toko', 'alamat' => '', 'lat' => -7.9608, 'lng' => 112.6173, 'radius' => 3,
            'kertas' => '80', 'footer' => 'Terima kasih sudah memesan!', 'autoprint' => false, 'estimasi' => 20, 'bayarbatas' => 30, 'qris' => '',
        ];
    }
}
