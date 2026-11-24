<?php
declare(strict_types=1);
namespace App\Domain;

/** Validasi dan normalisasi masukan pesanan; satu sumber aturan untuk API dan uji. */
final class OrderInput
{
    public const MAX_LINES = 30, MAX_QTY = 99;

    public static function phone(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($d, '62')) $d = '0' . substr($d, 2);
        elseif ($d !== '' && $d[0] === '8') $d = '0' . $d;
        return $d;
    }

    /** @return array{0: array<string,string>, 1: array<string,mixed>} [errors, clean] */
    public static function validate(array $in): array
    {
        $e = []; $c = [];
        $c['nama'] = trim((string)($in['nama'] ?? ''));
        $len = mb_strlen($c['nama']);
        if ($len < 2 || $len > 60) $e['nama'] = 'Nama wajib diisi (2 sampai 60 huruf).';

        $c['telp'] = self::phone((string)($in['telp'] ?? ''));
        if (!preg_match('/^0\d{8,13}$/', $c['telp'])) $e['telp'] = 'Nomor WhatsApp tidak valid. Contoh: 081234567890.';

        $c['jenis'] = (string)($in['jenis'] ?? '');
        if (!in_array($c['jenis'], ['Ambil', 'Antar'], true)) $e['jenis'] = 'Pilih Ambil di toko atau Diantar.';

        $c['bayar'] = (string)($in['bayar'] ?? '');
        if (!in_array($c['bayar'], ['Tunai', 'QRIS'], true)) $e['bayar'] = 'Pilih pembayaran Tunai atau QRIS.';

        $c['alamat'] = trim((string)($in['alamat'] ?? ''));
        if ($c['jenis'] === 'Antar') {
            if (mb_strlen($c['alamat']) < 8 || mb_strlen($c['alamat']) > 255) $e['alamat'] = 'Alamat pengantaran wajib diisi (minimal 8 huruf).';
        } else {
            $c['alamat'] = '';
        }

        $lat = $in['lat'] ?? null; $lng = $in['lng'] ?? null;
        if ($c['jenis'] === 'Antar' && $lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) $e['lat'] = 'Koordinat lokasi tidak valid.';
            else { $c['lat'] = round((float)$lat, 7); $c['lng'] = round((float)$lng, 7); }
        } else { $c['lat'] = null; $c['lng'] = null; }

        $c['catatan'] = trim((string)($in['catatan'] ?? ''));
        if (mb_strlen($c['catatan']) > 200) $e['catatan'] = 'Catatan maksimal 200 huruf.';

        $c['items'] = [];
        $items = $in['items'] ?? null;
        if (!is_array($items) || count($items) < 1) $e['items'] = 'Keranjang kosong.';
        elseif (count($items) > self::MAX_LINES) $e['items'] = 'Terlalu banyak jenis menu dalam satu pesanan.';
        else {
            $merged = [];
            foreach ($items as $it) {
                $id = is_array($it) ? ($it['id'] ?? null) : null; $q = is_array($it) ? ($it['qty'] ?? null) : null;
                if (!is_numeric($id) || !is_numeric($q) || (int)$q < 1 || (int)$q > self::MAX_QTY) { $e['items'] = 'Jumlah menu harus antara 1 dan ' . self::MAX_QTY . '.'; break; }
                $merged[(int)$id] = ($merged[(int)$id] ?? 0) + (int)$q;
            }
            foreach ($merged as $id => $q) { if ($q > self::MAX_QTY) { $e['items'] = 'Jumlah menu maksimal ' . self::MAX_QTY . ' per jenis.'; break; } }
            if (!isset($e['items'])) foreach ($merged as $id => $q) $c['items'][] = ['id' => $id, 'qty' => $q];
        }
        return [$e, $c];
    }
}
