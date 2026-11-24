<?php
declare(strict_types=1);
namespace App\Domain\Services;

use App\Domain\{ApiError, Kode, OpenHours, OrderFlow, OrderInput, Presenter, Pricing};
use App\Domain\Contracts\Store;

final class OrderService
{
    public function __construct(private Store $s) {}

    /** Pesanan dari pengguna (tanpa login). Harga selalu dihitung ulang dari database. */
    public function place(array $in, int $now): array
    {
        [$e, $c] = OrderInput::validate($in);
        if ($e) throw ApiError::invalid($e);
        $set = $this->s->settings();
        if (!OpenHours::isOpen((bool)$set['aktif'], (string)$set['buka'], (string)$set['tutup'], Presenter::nowMinutes($now))) {
            throw ApiError::conflict('Toko sedang tutup. Silakan pesan saat toko buka.');
        }
        $order = $this->s->transaction(function () use ($c, $set, $now) {
            [$rows, $lines] = $this->lines($c['items']);
            $p = Pricing::total($lines, $c['jenis'], (int)$set['ongkir']);
            return $this->s->orderCreate([
                'nama' => $c['nama'], 'telp' => $c['telp'], 'jenis' => $c['jenis'], 'alamat' => $c['alamat'], 'lat' => $c['lat'], 'lng' => $c['lng'],
                'catatan' => $c['catatan'], 'ongkir' => $p['ongkir'], 'total' => $p['total'], 'bayar' => $c['bayar'],
                'status' => OrderFlow::initial($c['bayar']), 'uang' => null, 'sumber' => 'online',
                'token' => bin2hex(random_bytes(16)), 'created_at' => $now,
            ], $rows);
        });
        return ['order' => Presenter::order($order, $now), 'token' => $order['token']];
    }

    /** Pesanan langsung yang dicatat Pemilik di kasir: langsung selesai. */
    public function kasir(array $in, int $now): array
    {
        $e = [];
        $nama = trim((string)($in['nama'] ?? '')) ?: 'Pelanggan langsung';
        if (mb_strlen($nama) > 60) $e['nama'] = 'Nama maksimal 60 huruf.';
        $bayar = (string)($in['bayar'] ?? '');
        if (!in_array($bayar, ['Tunai', 'QRIS'], true)) $e['bayar'] = 'Pilih pembayaran Tunai atau QRIS.';
        [$ie, $c] = OrderInput::validate(['nama' => $nama, 'telp' => '0800000000', 'jenis' => 'Ambil', 'bayar' => $bayar ?: 'Tunai', 'items' => $in['items'] ?? []]);
        if (isset($ie['items'])) $e['items'] = $ie['items'];
        if ($e) throw ApiError::invalid($e);
        $order = $this->s->transaction(function () use ($c, $nama, $bayar, $in, $now) {
            [$rows, $lines] = $this->lines($c['items']);
            $p = Pricing::total($lines, 'Ambil', 0);
            $uang = null;
            if ($bayar === 'Tunai') {
                $uang = isset($in['uang']) && is_numeric($in['uang']) ? (int)$in['uang'] : 0;
                if ($uang < $p['total']) throw ApiError::invalid(['uang' => 'Uang diterima kurang dari total.']);
                if ($uang > 100000000) throw ApiError::invalid(['uang' => 'Uang diterima terlalu besar (maksimal Rp 100.000.000).']);
            }
            return $this->s->orderCreate([
                'nama' => $nama, 'telp' => '-', 'jenis' => 'Ambil', 'alamat' => '', 'lat' => null, 'lng' => null, 'catatan' => '',
                'ongkir' => 0, 'total' => $p['total'], 'bayar' => $bayar, 'status' => OrderFlow::SELESAI, 'uang' => $uang, 'dibayar' => $bayar === 'QRIS' ? $p['total'] : 0, 'sumber' => 'kasir',
                'token' => bin2hex(random_bytes(16)), 'created_at' => $now,
            ], $rows);
        });
        return Presenter::order($order, $now);
    }

    /** @param list<array{id:int,qty:int}> $items @return array{0:list<array>,1:list<array>} baris pesanan dan baris harga */
    private function lines(array $items): array
    {
        $menu = $this->s->menuByIds(array_column($items, 'id'));
        $rows = []; $lines = []; $habis = []; $hilang = false;
        foreach ($items as $it) {
            $m = $menu[$it['id']] ?? null;
            if (!$m) { $hilang = true; continue; }
            if (!$m['stok']) { $habis[] = $m['nama']; continue; }
            $rows[] = ['menu_id' => (int)$m['id'], 'nama' => $m['nama'], 'qty' => $it['qty'], 'harga' => (int)$m['harga']];
            $lines[] = ['harga' => (int)$m['harga'], 'qty' => $it['qty']];
        }
        if ($hilang) throw ApiError::conflict('Ada menu yang sudah tidak tersedia. Muat ulang halaman lalu pilih lagi.', ['items' => 'Menu tidak tersedia.']);
        if ($habis) throw ApiError::conflict('Menu berikut sedang habis: ' . implode(', ', $habis) . '. Hapus dari keranjang untuk melanjutkan.', ['items' => implode(', ', $habis)]);
        return [$rows, $lines];
    }

    public function userCancel(string $kode, string $token, int $now): array
    {
        $o = $this->ownOrder($kode, $token);
        if (!OrderFlow::userCanCancel($o['status'])) {
            throw ApiError::conflict('Pesanan sudah diproses dan tidak bisa dibatalkan di sini. Hubungi toko lewat WhatsApp.');
        }
        return Presenter::order($this->s->orderSetStatus($o['kode'], OrderFlow::BATAL), $now);
    }

    private function ownOrder(string $kode, string $token): array
    {
        $o = strlen($token) >= 20 ? $this->s->orderByToken($token) : null;
        $k = Kode::normalize($kode);
        if (!$o || $k === null || $o['kode'] !== $k) throw ApiError::notFound('Pesanan tidak ditemukan di perangkat ini.');
        return $o;
    }

    /** Cari dari perangkat lain: kode pesanan + 4 digit terakhir nomor WhatsApp. */
    public function lookup(string $kode, string $telp4, int $now): array
    {
        $k = Kode::normalize($kode); $t = preg_replace('/\D+/', '', $telp4) ?? '';
        $o = $k !== null && strlen($t) === 4 ? $this->s->orderByKode($k) : null;
        if (!$o || substr(preg_replace('/\D+/', '', (string)$o['telp']) ?? '', -4) !== $t) {
            throw ApiError::notFound('Kode pesanan atau 4 digit akhir WhatsApp tidak cocok.');
        }
        return ['order' => Presenter::order($o, $now), 'token' => $o['token']];
    }

    /** @param list<mixed> $tokens */
    public function mine(array $tokens, int $now): array
    {
        $tokens = array_values(array_unique(array_filter(array_map('strval', $tokens), fn($t) => preg_match('/^[a-f0-9]{20,64}$/', $t) === 1)));
        $tokens = array_slice($tokens, 0, 30);
        $out = [];
        foreach ($this->s->ordersByTokens($tokens) as $o) $out[] = Presenter::order($o, $now) + ['token' => $o['token']];
        usort($out, fn($a, $b) => $b['dibuat'] <=> $a['dibuat'] ?: strcmp($b['kode'], $a['kode']));
        return $out;
    }

    public function setStatus(string $kode, string $to, int $now): array
    {
        $k = Kode::normalize($kode);
        $o = $k !== null ? $this->s->orderByKode($k) : null;
        if (!$o) throw ApiError::notFound('Pesanan tidak ditemukan.');
        if (!in_array($to, OrderFlow::ALL, true) || !OrderFlow::can($o['status'], $to, $o['jenis'])) {
            throw ApiError::conflict("Status tidak dapat diubah dari {$o['status']} ke {$to}.");
        }
        // QRIS: saat Pemilik menandai lunas, jumlah yang dibayar dicatat sebesar total saat itu.
        if ($o['status'] === OrderFlow::BELUM && $to === OrderFlow::DITERIMA && $o['bayar'] === 'QRIS') {
            return Presenter::order($this->s->orderUpdate($o['kode'], ['status' => $to, 'dibayar' => (int)$o['total']]), $now);
        }
        if ($to === OrderFlow::SELESAI && Pricing::selisih((string)$o['bayar'], (int)$o['dibayar'], (int)$o['total']) !== 0) {
            throw ApiError::conflict('Masih ada selisih pembayaran akibat pesanan diubah. Selesaikan selisihnya dulu (tagih atau kembalikan), lalu tandai selesai.');
        }
        return Presenter::order($this->s->orderSetStatus($o['kode'], $to), $now);
    }

    /**
     * Pemilik mengubah isi pesanan (jumlah, tambah/hapus menu, catatan) selama belum siap.
     * Harga baris lama dipertahankan; menu baru memakai harga sekarang. Ongkir tidak berubah.
     * @param array{items?:mixed,catatan?:mixed} $in
     */
    public function edit(string $kode, array $in, int $now): array
    {
        return $this->s->transaction(function () use ($kode, $in, $now) {
            $k = Kode::normalize($kode);
            $o = $k !== null ? $this->s->orderByKode($k) : null;
            if (!$o) throw ApiError::notFound('Pesanan tidak ditemukan.');
            if (!OrderFlow::canEdit($o['status'])) throw ApiError::conflict('Pesanan yang sudah siap, selesai, atau dibatalkan tidak bisa diubah.');
            $catatan = trim((string)($in['catatan'] ?? $o['catatan']));
            if (mb_strlen($catatan) > 200) throw ApiError::invalid(['catatan' => 'Catatan maksimal 200 huruf.']);
            $req = [];
            foreach ((array)($in['items'] ?? []) as $it) {
                $id = isset($it['id']) && is_numeric($it['id']) ? (int)$it['id'] : 0; $q = isset($it['qty']) && is_numeric($it['qty']) ? (int)$it['qty'] : 0;
                if ($id < 1 || $q < 1 || $q > 99) throw ApiError::invalid(['items' => 'Jumlah tiap menu harus 1 sampai 99.']);
                $req[$id] = min(99, ($req[$id] ?? 0) + $q);
            }
            $locked = array_values(array_filter($o['items'], fn($i) => $i['menu_id'] === null));   // menu yang sudah dihapus dari daftar: tidak bisa diubah
            if (!$req && !$locked) throw ApiError::invalid(['items' => 'Pesanan harus berisi minimal satu menu. Untuk membatalkan, gunakan tombol Batalkan.']);
            if (count($req) + count($locked) > 30) throw ApiError::invalid(['items' => 'Terlalu banyak jenis menu (maksimal 30).']);
            $old = []; foreach ($o['items'] as $i) if ($i['menu_id'] !== null) $old[$i['menu_id']] = $i;
            $menu = $this->s->menuByIds(array_keys($req));
            $rows = $locked; $sub = 0; foreach ($locked as $l) $sub += $l['harga'] * $l['qty'];
            foreach ($req as $id => $q) {
                if (isset($old[$id])) { $nm = $old[$id]['nama']; $hg = (int)$old[$id]['harga']; }                       // baris lama: harga dikunci
                else {
                    $m = $menu[$id] ?? null;
                    if (!$m) throw ApiError::conflict('Ada menu yang tidak ditemukan. Muat ulang halaman lalu coba lagi.');
                    if (!$m['stok']) throw ApiError::conflict("Menu {$m['nama']} sedang habis.");
                    $nm = $m['nama']; $hg = (int)$m['harga'];
                }
                $rows[] = ['menu_id' => $id, 'nama' => $nm, 'qty' => $q, 'harga' => $hg]; $sub += $hg * $q;
            }
            $total = $sub + (int)$o['ongkir'];
            return Presenter::order($this->s->orderUpdate($o['kode'], ['total' => $total, 'catatan' => $catatan, 'diubah' => true], $rows), $now);
        });
    }

    /** Pemilik menyatakan selisih pembayaran sudah diselesaikan (ditagih atau dikembalikan). */
    public function settle(string $kode, int $now): array
    {
        $k = Kode::normalize($kode);
        $o = $k !== null ? $this->s->orderByKode($k) : null;
        if (!$o) throw ApiError::notFound('Pesanan tidak ditemukan.');
        if (Pricing::selisih((string)$o['bayar'], (int)$o['dibayar'], (int)$o['total']) === 0) throw ApiError::conflict('Pesanan ini tidak memiliki selisih pembayaran.');
        return Presenter::order($this->s->orderUpdate($o['kode'], ['dibayar' => (int)$o['total']]), $now);
    }

    /** Menghapus SEMUA pesanan (mis. setelah presentasi). Menu, akun, dan pengaturan tidak disentuh. */
    public function clearAll(string $confirm): int
    {
        if (trim($confirm) !== 'HAPUS') throw ApiError::invalid(['confirm' => 'Ketik HAPUS (huruf besar) untuk melanjutkan.']);
        return $this->s->ordersClear();
    }

    public function listForAdmin(int $now, int $days = 60): array
    {
        return array_map(fn($o) => Presenter::order($o, $now), $this->s->ordersSince($now - $days * 86400, 2000));
    }
}
