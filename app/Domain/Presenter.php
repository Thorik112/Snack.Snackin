<?php
declare(strict_types=1);
namespace App\Domain;

/** Mengubah baris data menjadi bentuk JSON yang dipakai frontend. */
final class Presenter
{
    private const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    public const TZ = 'Asia/Jakarta';

    private static function dt(int $ts): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone(self::TZ));
    }

    public static function tgl(int $ts): string
    {
        $d = self::dt($ts);
        return $d->format('j') . ' ' . self::MON[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
    }

    public static function jam(int $ts): string { return self::dt($ts)->format('H.i'); }

    /** Selisih hari kalender (0 = hari ini). */
    public static function hari(int $ts, int $nowTs): int
    {
        $a = self::dt($ts)->setTime(0, 0); $b = self::dt($nowTs)->setTime(0, 0);
        return max(0, (int)round(($b->getTimestamp() - $a->getTimestamp()) / 86400));
    }

    public static function nowMinutes(int $nowTs): int
    {
        $d = self::dt($nowTs);
        return (int)$d->format('G') * 60 + (int)$d->format('i');
    }

    public static function order(array $o, int $nowTs): array
    {
        $ts = (int)$o['created_at'];
        return [
            'kode' => $o['kode'], 'nama' => $o['nama'], 'telp' => $o['telp'], 'jenis' => $o['jenis'], 'alamat' => (string)($o['alamat'] ?? ''),
            'lat' => isset($o['lat']) ? (float)$o['lat'] : null, 'lng' => isset($o['lng']) ? (float)$o['lng'] : null,
            'catatan' => (string)($o['catatan'] ?? ''),
            'items' => array_map(fn($i) => ['id' => isset($i['menu_id']) ? (int)$i['menu_id'] : null, 'nama' => $i['nama'], 'qty' => (int)$i['qty'], 'harga' => (int)$i['harga']], $o['items'] ?? []),
            'ongkir' => (int)$o['ongkir'], 'total' => (int)$o['total'], 'bayar' => $o['bayar'], 'status' => $o['status'],
            'uang' => isset($o['uang']) ? (int)$o['uang'] : null, 'sumber' => $o['sumber'] ?? 'online',
            'dibayar' => (int)($o['dibayar'] ?? 0), 'selisih' => Pricing::selisih((string)$o['bayar'], (int)($o['dibayar'] ?? 0), (int)$o['total']), 'diubah' => (bool)($o['diubah'] ?? false),
            'hari' => self::hari($ts, $nowTs), 'tgl' => self::tgl($ts), 'jam' => self::jam($ts), 'dibuat' => $ts,
        ];
    }

    public static function menu(array $m): array
    {
        return ['id' => (int)$m['id'], 'nama' => $m['nama'], 'kategori' => $m['kategori'], 'harga' => (int)$m['harga'], 'stok' => (bool)$m['stok'],
            'emoji' => (string)($m['emoji'] ?? ''), 'foto' => $m['foto'] ?: null, 'desk' => (string)($m['desk'] ?? '')];
    }

    public static function account(array $a, int $nowTs): array
    {
        return ['id' => (int)$a['id'], 'nama' => $a['nama'], 'email' => $a['email'], 'peran' => 'Pemilik', 'aktif' => (bool)$a['aktif'],
            'terakhir' => self::terakhir($a['last_login'] ?? null, $nowTs)];
    }

    public static function terakhir(?int $ts, int $nowTs): string
    {
        if (!$ts) return 'Belum pernah';
        $h = self::hari($ts, $nowTs);
        if ($h === 0) return 'Hari ini ' . self::jam($ts);
        if ($h === 1) return 'Kemarin ' . self::jam($ts);
        return self::tgl($ts);
    }
}
