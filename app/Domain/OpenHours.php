<?php
declare(strict_types=1);
namespace App\Domain;

final class OpenHours
{
    /** "10.00" atau "10:00" -> menit sejak tengah malam; null bila tidak valid. */
    public static function minutes(string $hhmm): ?int
    {
        if (!preg_match('/^(\d{1,2})[.:](\d{2})$/', trim($hhmm), $m)) return null;
        $h = (int)$m[1]; $mi = (int)$m[2];
        return ($h > 23 || $mi > 59) ? null : $h * 60 + $mi;
    }

    /** Toko menerima pesanan bila tombol aktif menyala dan jam sekarang di antara buka dan tutup (mendukung lewat tengah malam). */
    public static function isOpen(bool $aktif, string $buka, string $tutup, int $nowMinutes): bool
    {
        if (!$aktif) return false;
        $b = self::minutes($buka); $t = self::minutes($tutup);
        if ($b === null || $t === null || $b === $t) return true;
        return $b < $t ? ($nowMinutes >= $b && $nowMinutes < $t) : ($nowMinutes >= $b || $nowMinutes < $t);
    }
}
