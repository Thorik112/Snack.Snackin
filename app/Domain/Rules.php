<?php
declare(strict_types=1);
namespace App\Domain;

/** Validasi menu, akun, dan pengaturan. Mengembalikan [errors, clean]. */
final class Rules
{
    private static function bool(mixed $v): bool
    {
        return $v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on';
    }

    public static function menu(array $in): array
    {
        $e = []; $c = [];
        $c['nama'] = trim((string)($in['nama'] ?? ''));
        if (mb_strlen($c['nama']) < 2 || mb_strlen($c['nama']) > 60) $e['nama'] = 'Nama menu wajib diisi (2 sampai 60 huruf).';
        $c['kategori'] = trim((string)($in['kategori'] ?? ''));
        if (mb_strlen($c['kategori']) < 2 || mb_strlen($c['kategori']) > 30) $e['kategori'] = 'Kategori wajib diisi (2 sampai 30 huruf).';
        $h = $in['harga'] ?? null;
        if (!is_numeric($h) || (int)$h != $h || (int)$h < 500 || (int)$h > 1000000) $e['harga'] = 'Harga harus angka antara Rp 500 dan Rp 1.000.000.';
        else $c['harga'] = (int)$h;
        $c['stok'] = self::bool($in['stok'] ?? true);
        $c['emoji'] = trim((string)($in['emoji'] ?? ''));
        if (mb_strlen($c['emoji']) > 8) $e['emoji'] = 'Emoji terlalu panjang.';
        $c['desk'] = trim((string)($in['desk'] ?? ''));
        if (mb_strlen($c['desk']) > 200) $e['desk'] = 'Deskripsi maksimal 200 huruf.';
        return [$e, $c];
    }

    public static function account(array $in, bool $passwordRequired): array
    {
        $e = []; $c = [];
        $c['nama'] = trim((string)($in['nama'] ?? ''));
        if (mb_strlen($c['nama']) < 2 || mb_strlen($c['nama']) > 50) $e['nama'] = 'Nama wajib diisi (2 sampai 50 huruf).';
        $c['email'] = strtolower(trim((string)($in['email'] ?? '')));
        if (!filter_var($c['email'], FILTER_VALIDATE_EMAIL) || strlen($c['email']) > 100) $e['email'] = 'Email tidak valid.';
        $pw = (string)($in['password'] ?? '');
        if ($pw !== '' || $passwordRequired) {
            if (strlen($pw) < 8 || strlen($pw) > 72) $e['password'] = 'Kata sandi minimal 8 karakter.';
            else $c['password'] = $pw;
        }
        $c['aktif'] = array_key_exists('aktif', $in) ? self::bool($in['aktif']) : true;
        return [$e, $c];
    }

    public static function settings(array $in): array
    {
        $e = []; $c = [];
        $str = function (string $k, int $min, int $max, string $msg) use (&$e, &$c, $in) {
            if (!array_key_exists($k, $in)) return;
            $v = trim((string)$in[$k]);
            if (mb_strlen($v) < $min || mb_strlen($v) > $max) $e[$k] = $msg; else $c[$k] = $v;
        };
        $str('toko', 2, 60, 'Nama toko wajib diisi (2 sampai 60 huruf).');
        $str('area', 0, 80, 'Keterangan area maksimal 80 huruf.');
        $str('alamat', 0, 255, 'Alamat maksimal 255 huruf.');
        $str('footer', 0, 80, 'Pesan struk maksimal 80 huruf.');
        if (array_key_exists('wa', $in)) {
            $d = preg_replace('/\D+/', '', (string)$in['wa']) ?? '';
            if (strlen($d) < 9 || strlen($d) > 14) $e['wa'] = 'Nomor WhatsApp tidak valid.'; else $c['wa'] = trim((string)$in['wa']);
        }
        foreach (['buka', 'tutup'] as $k) {
            if (!array_key_exists($k, $in)) continue;
            $m = OpenHours::minutes((string)$in[$k]);
            if ($m === null) $e[$k] = 'Jam tidak valid.'; else $c[$k] = sprintf('%02d.%02d', intdiv($m, 60), $m % 60);
        }
        if (array_key_exists('aktif', $in)) $c['aktif'] = self::bool($in['aktif']);
        if (array_key_exists('autoprint', $in)) $c['autoprint'] = self::bool($in['autoprint']);
        foreach (['ongkir' => [0, 100000, 'Ongkir harus antara Rp 0 dan Rp 100.000.'], 'estimasi' => [0, 240, 'Estimasi 0 sampai 240 menit.'], 'bayarbatas' => [0, 1440, 'Batas waktu bayar 0 sampai 1440 menit.']] as $k => [$lo, $hi, $msg]) {
            if (!array_key_exists($k, $in)) continue;
            $v = $in[$k];
            if (!is_numeric($v) || (int)$v != $v || (int)$v < $lo || (int)$v > $hi) $e[$k] = $msg; else $c[$k] = (int)$v;
        }
        if (array_key_exists('radius', $in)) {
            $v = $in['radius'];
            if ($v === '' || $v === null) $c['radius'] = 0;
            elseif (!is_numeric($v) || $v < 0 || $v > 100) $e['radius'] = 'Jangkauan 0 sampai 100 km.'; else $c['radius'] = (float)$v;
        }
        foreach (['lat' => 90, 'lng' => 180] as $k => $lim) {
            if (!array_key_exists($k, $in)) continue;
            if (!is_numeric($in[$k]) || abs((float)$in[$k]) > $lim) $e[$k] = 'Koordinat tidak valid.'; else $c[$k] = round((float)$in[$k], 7);
        }
        if (array_key_exists('kertas', $in)) {
            if (!in_array((string)$in['kertas'], ['58', '80'], true)) $e['kertas'] = 'Pilih 58 atau 80 mm.'; else $c['kertas'] = (string)$in['kertas'];
        }
        return [$e, $c];
    }
}
