<?php
declare(strict_types=1);
namespace App\Domain;

final class Pricing
{
    /**
     * Selisih pembayaran setelah pesanan diubah. Hanya untuk QRIS yang sudah dikonfirmasi lunas.
     * Positif = pelanggan masih kurang bayar; negatif = pelanggan lebih bayar (harus dikembalikan).
     */
    public static function selisih(string $bayar, int $dibayar, int $total): int
    {
        return $bayar === 'QRIS' && $dibayar > 0 ? $total - $dibayar : 0;
    }

    /** @param list<array{harga:int,qty:int}> $lines @return array{sub:int,ongkir:int,total:int} */
    public static function total(array $lines, string $jenis, int $ongkirTetap): array
    {
        $sub = 0;
        foreach ($lines as $l) $sub += $l['harga'] * $l['qty'];
        $ongkir = $jenis === 'Antar' ? max(0, $ongkirTetap) : 0;
        return ['sub' => $sub, 'ongkir' => $ongkir, 'total' => $sub + $ongkir];
    }
}
