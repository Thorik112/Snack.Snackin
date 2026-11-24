<?php
declare(strict_types=1);
namespace App\Domain;

/** Alur status pesanan. Tanpa ketergantungan framework supaya bisa diuji sendiri. */
final class OrderFlow
{
    public const BELUM = 'Belum dibayar', DITERIMA = 'Diterima', DIPROSES = 'Diproses',
        SIAP = 'Siap diambil', DIANTAR = 'Diantar', SELESAI = 'Selesai', BATAL = 'Dibatalkan';
    public const ALL = [self::BELUM, self::DITERIMA, self::DIPROSES, self::SIAP, self::DIANTAR, self::SELESAI, self::BATAL];
    /** Status yang menunggu tindakan Pemilik. */
    public const NEEDS_ACTION = [self::BELUM, self::DITERIMA];

    /** QRIS menunggu konfirmasi bayar; tunai langsung diterima (dibayar saat diterima). */
    public static function initial(string $bayar): string
    {
        return $bayar === 'QRIS' ? self::BELUM : self::DITERIMA;
    }

    public static function ready(string $jenis): string
    {
        return $jenis === 'Antar' ? self::DIANTAR : self::SIAP;
    }

    /** @return list<string> status tujuan yang sah dari status sekarang. */
    public static function next(string $status, string $jenis): array
    {
        return match ($status) {
            self::BELUM => [self::DITERIMA, self::BATAL],
            self::DITERIMA => [self::DIPROSES, self::BATAL],
            self::DIPROSES => [self::ready($jenis), self::BATAL],
            self::SIAP, self::DIANTAR => [self::SELESAI],
            default => [],
        };
    }

    public static function can(string $from, string $to, string $jenis): bool
    {
        return in_array($to, self::next($from, $jenis), true);
    }

    /** Pengguna hanya boleh membatalkan sebelum pesanan diproses. */
    public static function userCanCancel(string $status): bool
    {
        return in_array($status, self::NEEDS_ACTION, true);
    }

    /** Pemilik boleh mengubah isi pesanan selama belum siap diambil/diantar. */
    public static function canEdit(string $status): bool
    {
        return in_array($status, [self::BELUM, self::DITERIMA, self::DIPROSES], true);
    }

    public static function isClosed(string $status): bool
    {
        return $status === self::SELESAI || $status === self::BATAL;
    }
}
