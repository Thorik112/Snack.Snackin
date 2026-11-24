<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Domain\ApiError;
use App\Domain\Services\{AccountService, MenuService, OrderService, SettingsService};
use PHPUnit\Framework\TestCase;
use Tests\Support\PdoStore;

final class ServicesTest extends TestCase
{
    private PdoStore $st;
    private OrderService $os;
    /** 31 Okt 2026 12:00 WIB (UTC+7) */
    private int $now;

    private function setUpStore(): void
    {
        $this->st = PdoStore::memory();
        $this->os = new OrderService($this->st);
        $this->now = (new \DateTimeImmutable('2026-10-31 12:00:00', new \DateTimeZone('Asia/Jakarta')))->getTimestamp();
        $this->st->menuSave(null, ['nama' => 'Pentol Bakar', 'kategori' => 'Bakaran', 'harga' => 8000, 'stok' => true, 'emoji' => '🍢', 'desk' => '']);
        $this->st->menuSave(null, ['nama' => 'Roti Bakar', 'kategori' => 'Camilan', 'harga' => 13000, 'stok' => false, 'emoji' => '🍞', 'desk' => '']);
        $this->st->menuSave(null, ['nama' => 'Es Teh', 'kategori' => 'Minuman', 'harga' => 4000, 'stok' => true, 'emoji' => '🧊', 'desk' => '']);
    }

    private function err(callable $fn): ?ApiError
    {
        try { $fn(); } catch (ApiError $e) { return $e; }
        return null;
    }

    private function input(array $over = []): array
    {
        return $over + ['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 2], ['id' => 3, 'qty' => 1]]];
    }

    public function test_place_order_computes_price_on_server(): void
    {
        $this->setUpStore();
        $in = $this->input(['jenis' => 'Antar', 'alamat' => 'Jl. Veteran No. 1, Malang', 'harga' => 1, 'total' => 1, 'items' => [['id' => 1, 'qty' => 2, 'harga' => 1], ['id' => 3, 'qty' => 1]]]);
        $r = $this->os->place($in, $this->now);
        $o = $r['order'];
        $this->assertSame('SNK-001', $o['kode']);
        $this->assertSame(25000, $o['total'], '2x8000 + 4000 + ongkir 5000, harga dari klien diabaikan');
        $this->assertSame(5000, $o['ongkir']);
        $this->assertSame('Diterima', $o['status'], 'tunai langsung diterima');
        $this->assertSame(0, $o['hari']);
        $this->assertSame('31 Okt 2026', $o['tgl']);
        $this->assertSame('12.00', $o['jam']);
        $this->assertSame(32, strlen($r['token']));
        $this->assertSame('Pentol Bakar', $o['items'][0]['nama']);
    }

    public function test_qris_starts_unpaid_and_ambil_has_no_ongkir(): void
    {
        $this->setUpStore();
        $o = $this->os->place($this->input(['bayar' => 'QRIS']), $this->now)['order'];
        $this->assertSame('Belum dibayar', $o['status']);
        $this->assertSame(0, $o['ongkir']);
        $this->assertSame(20000, $o['total']);
    }

    public function test_codes_are_sequential(): void
    {
        $this->setUpStore();
        $a = $this->os->place($this->input(), $this->now)['order']['kode'];
        $b = $this->os->place($this->input(), $this->now)['order']['kode'];
        $this->assertSame('SNK-001', $a);
        $this->assertSame('SNK-002', $b);
    }

    public function test_out_of_stock_and_unknown_menu_rejected(): void
    {
        $this->setUpStore();
        $e = $this->err(fn() => $this->os->place($this->input(['items' => [['id' => 2, 'qty' => 1]]]), $this->now));
        $this->assertSame(409, $e->status);
        $this->assertTrue(str_contains($e->getMessage(), 'Roti Bakar'));
        $e = $this->err(fn() => $this->os->place($this->input(['items' => [['id' => 999, 'qty' => 1]]]), $this->now));
        $this->assertSame(409, $e->status);
        $this->assertSame(0, count($this->st->ordersSince(0, 10)), 'tidak ada pesanan tersimpan');
    }

    public function test_shop_closed(): void
    {
        $this->setUpStore();
        $this->st->saveSettings(['aktif' => false]);
        $e = $this->err(fn() => $this->os->place($this->input(), $this->now));
        $this->assertSame(409, $e->status);
        $this->st->saveSettings(['aktif' => true]);
        $night = (new \DateTimeImmutable('2026-10-31 23:30:00', new \DateTimeZone('Asia/Jakarta')))->getTimestamp();
        $e = $this->err(fn() => $this->os->place($this->input(), $night));
        $this->assertSame(409, $e->status, 'di luar jam buka');
    }

    public function test_validation_error_is_422(): void
    {
        $this->setUpStore();
        $e = $this->err(fn() => $this->os->place(['nama' => ''], $this->now));
        $this->assertSame(422, $e->status);
        $this->assertArrayHasKey('nama', $e->errors);
    }

    public function test_user_cancel_only_before_processing(): void
    {
        $this->setUpStore();
        $r = $this->os->place($this->input(), $this->now);
        $c = $this->os->userCancel('SNK-001', $r['token'], $this->now);
        $this->assertSame('Dibatalkan', $c['status']);

        $r2 = $this->os->place($this->input(), $this->now);
        $this->os->setStatus('SNK-002', 'Diproses', $this->now);
        $e = $this->err(fn() => $this->os->userCancel('SNK-002', $r2['token'], $this->now));
        $this->assertSame(409, $e->status);

        $e = $this->err(fn() => $this->os->userCancel('SNK-002', str_repeat('a', 32), $this->now));
        $this->assertSame(404, $e->status, 'token salah');
        $e = $this->err(fn() => $this->os->userCancel('SNK-001', $r2['token'], $this->now));
        $this->assertSame(404, $e->status, 'token milik pesanan lain');
    }

    public function test_lookup_requires_phone_digits(): void
    {
        $this->setUpStore();
        $r = $this->os->place($this->input(), $this->now);
        $f = $this->os->lookup('snk 1', '7890', $this->now);
        $this->assertSame($r['token'], $f['token']);
        $this->assertSame(404, $this->err(fn() => $this->os->lookup('SNK-001', '0000', $this->now))->status);
        $this->assertSame(404, $this->err(fn() => $this->os->lookup('SNK-999', '7890', $this->now))->status);
        $this->assertSame(404, $this->err(fn() => $this->os->lookup('SNK-001', '78', $this->now))->status);
    }

    public function test_mine_returns_only_own_orders_newest_first(): void
    {
        $this->setUpStore();
        $a = $this->os->place($this->input(), $this->now);
        $b = $this->os->place($this->input(), $this->now + 60);
        $this->os->place($this->input(), $this->now + 120); // milik orang lain
        $m = $this->os->mine([$a['token'], $b['token'], 'bukan-token', ''], $this->now + 200);
        $this->assertSame(2, count($m));
        $this->assertSame('SNK-002', $m[0]['kode']);
        $this->assertSame($b['token'], $m[0]['token']);
        $this->assertSame([], $this->os->mine([], $this->now));
    }

    public function test_admin_status_flow_is_enforced(): void
    {
        $this->setUpStore();
        $this->os->place($this->input(['bayar' => 'QRIS', 'jenis' => 'Antar', 'alamat' => 'Jl. Veteran No. 1, Malang']), $this->now);
        $this->assertSame(409, $this->err(fn() => $this->os->setStatus('SNK-001', 'Diproses', $this->now))->status, 'harus dikonfirmasi bayar dulu');
        foreach (['Diterima', 'Diproses', 'Diantar', 'Selesai'] as $to) $this->assertSame($to, $this->os->setStatus('SNK-001', $to, $this->now)['status']);
        $this->assertSame(409, $this->err(fn() => $this->os->setStatus('SNK-001', 'Dibatalkan', $this->now))->status, 'selesai tidak bisa dibatalkan');
        $this->assertSame(404, $this->err(fn() => $this->os->setStatus('SNK-404', 'Diproses', $this->now))->status);
        $this->assertSame(409, $this->err(fn() => $this->os->setStatus('SNK-001', 'Ngawur', $this->now))->status);
    }

    public function test_kasir_order(): void
    {
        $this->setUpStore();
        $o = $this->os->kasir(['nama' => '', 'bayar' => 'Tunai', 'uang' => 50000, 'items' => [['id' => 1, 'qty' => 2]]], $this->now);
        $this->assertSame('Selesai', $o['status']);
        $this->assertSame('Pelanggan langsung', $o['nama']);
        $this->assertSame(16000, $o['total']);
        $this->assertSame(50000, $o['uang']);
        $this->assertSame('kasir', $o['sumber']);
        $e = $this->err(fn() => $this->os->kasir(['bayar' => 'Tunai', 'uang' => 1000, 'items' => [['id' => 1, 'qty' => 2]]], $this->now));
        $this->assertSame(422, $e->status);
        $q = $this->os->kasir(['bayar' => 'QRIS', 'items' => [['id' => 3, 'qty' => 1]]], $this->now);
        $this->assertSame(null, $q['uang']);
    }

    public function test_admin_list_limits_to_recent_days(): void
    {
        $this->setUpStore();
        $this->os->place($this->input(), $this->now - 80 * 86400 + 3600 * 3); // 80 hari lalu, sesuai jam buka
        $this->os->place($this->input(), $this->now - 3 * 86400);
        $l = $this->os->listForAdmin($this->now);
        $this->assertSame(1, count($l));
        $this->assertSame(3, $l[0]['hari']);
    }

    public function test_menu_service(): void
    {
        $this->setUpStore();
        $ms = new MenuService($this->st);
        $m = $ms->save(null, ['nama' => 'Nugget', 'kategori' => 'Bakaran', 'harga' => 8000, 'stok' => true, 'emoji' => '🍗'], '/storage/menu/a.jpg');
        $this->assertSame('/storage/menu/a.jpg', $m['foto']);
        $m2 = $ms->save($m['id'], ['nama' => 'Nugget Bakar', 'kategori' => 'Bakaran', 'harga' => '9000', 'stok' => '0'], null);
        $this->assertSame(9000, $m2['harga']);
        $this->assertFalse($m2['stok']);
        $this->assertSame('/storage/menu/a.jpg', $m2['foto'], 'foto dipertahankan bila tidak diganti');
        $m3 = $ms->save($m['id'], ['nama' => 'Nugget Bakar', 'kategori' => 'Bakaran', 'harga' => 9000], '');
        $this->assertSame(null, $m3['foto'], 'foto dihapus');
        $this->assertTrue($ms->setStok($m['id'], true)['stok']);
        $this->assertSame(422, $this->err(fn() => $ms->save(null, ['nama' => 'X', 'kategori' => '', 'harga' => 'abc'], null))->status);
        $this->assertSame(404, $this->err(fn() => $ms->save(999, ['nama' => 'Nugget', 'kategori' => 'Bakaran', 'harga' => 8000], null))->status);
        $ms->delete($m['id']);
        $this->assertSame(404, $this->err(fn() => $ms->delete($m['id']))->status);
        // pesanan lama tetap menyimpan nama dan harga saat dibeli
        $o = $this->os->place($this->input(), $this->now)['order'];
        $ms->save(1, ['nama' => 'Pentol Super', 'kategori' => 'Bakaran', 'harga' => 20000, 'stok' => true], null);
        $this->assertSame('Pentol Bakar', $this->os->listForAdmin($this->now)[0]['items'][0]['nama']);
        $this->assertSame(8000, $this->os->listForAdmin($this->now)[0]['items'][0]['harga']);
    }

    public function test_account_rules(): void
    {
        $this->setUpStore();
        $hash = password_hash('rahasia123', PASSWORD_BCRYPT);
        $this->st->accountSave(null, ['nama' => 'Lookman', 'email' => 'lookman@snackin.test', 'password' => $hash, 'aktif' => true]);
        $as = new AccountService($this->st, 1);
        $n = $as->save(null, ['nama' => 'Ferly', 'email' => 'FERLY@snackin.test', 'password' => 'sandi-panjang', 'aktif' => true], $this->now);
        $this->assertSame('ferly@snackin.test', $n['email']);
        $this->assertTrue(password_verify('sandi-panjang', $this->st->accountFind($n['id'])['password']));
        $this->assertArrayHasKey('email', $this->err(fn() => $as->save(null, ['nama' => 'Dobel', 'email' => 'ferly@snackin.test', 'password' => 'sandi-panjang'], $this->now))->errors);
        $this->assertArrayHasKey('password', $this->err(fn() => $as->save(null, ['nama' => 'Baru', 'email' => 'baru@snackin.test', 'password' => 'pendek'], $this->now))->errors);
        // ubah tanpa sandi: sandi lama dipertahankan
        $u = $as->save($n['id'], ['nama' => 'Ferly R', 'email' => 'ferly@snackin.test', 'aktif' => true], $this->now);
        $this->assertSame('Ferly R', $u['nama']);
        $this->assertTrue(password_verify('sandi-panjang', $this->st->accountFind($n['id'])['password']));
        $this->assertFalse(isset($u['password']), 'sandi tidak pernah dikirim ke klien');
        // aturan akun sendiri dan akun aktif terakhir
        $this->assertSame(409, $this->err(fn() => $as->setAktif(1, false, $this->now))->status, 'tidak boleh menonaktifkan diri sendiri');
        $this->assertSame(409, $this->err(fn() => $as->delete(1))->status, 'tidak boleh menghapus diri sendiri');
        $as->setAktif($n['id'], false, $this->now);
        $as2 = new AccountService($this->st, $n['id']);
        $this->assertSame(409, $this->err(fn() => $as2->setAktif(1, false, $this->now))->status, 'minimal satu akun aktif');
        $as->delete($n['id']);
        $this->assertSame(1, count($as->all($this->now)));
        $this->assertSame('Belum pernah', $as->all($this->now)[0]['terakhir']);
    }

    public function test_settings_service(): void
    {
        $this->setUpStore();
        $ss = new SettingsService($this->st);
        $s = $ss->update(['toko' => 'Dapur Baru', 'buka' => '9:30', 'tutup' => '22.00', 'ongkir' => '7000', 'aktif' => false, 'kertas' => '58', 'radius' => '4.5', 'lat' => '-7.9', 'lng' => '112.6', 'wa' => '0812-1111-2222']);
        $this->assertSame('09.30', $s['buka']);
        $this->assertSame(7000, $s['ongkir']);
        $this->assertFalse($s['aktif']);
        $this->assertSame('58', $s['kertas']);
        $this->assertSame(4.5, $s['radius']);
        $this->assertSame('Dapur Snack Snackin', (new SettingsService(PdoStore::memory()))->get()['toko'], 'nilai bawaan');
        $e = $this->err(fn() => $ss->update(['ongkir' => '-5', 'buka' => '99.99', 'kertas' => '70', 'toko' => 'A']));
        foreach (['ongkir', 'buka', 'kertas', 'toko'] as $k) $this->assertArrayHasKey($k, $e->errors, $k);
        $this->assertSame('/storage/qris/x.png', $ss->setQris('/storage/qris/x.png')['qris']);
        $this->assertSame('', $ss->setQris('')['qris']);
    }

    public function test_owner_edit_qris_paid_order_records_payment_difference(): void
    {
        $this->setUpStore();
        $o = $this->os->place($this->input(['bayar' => 'QRIS', 'jenis' => 'Antar', 'alamat' => 'Jl. Veteran 8 Malang', 'lat' => -7.9, 'lng' => 112.6, 'items' => [['id' => 1, 'qty' => 2]]]), $this->now)['order'];
        $this->assertSame(21000, $o['total']); $this->assertSame(0, $o['dibayar']);
        // belum dibayar: ubah bebas, belum ada selisih
        $e = $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 3]]], $this->now);
        $this->assertSame(29000, $e['total']); $this->assertSame(0, $e['selisih']); $this->assertTrue($e['diubah']);
        // tandai lunas: dibayar dicatat = total saat itu
        $p = $this->os->setStatus($o['kode'], 'Diterima', $this->now);
        $this->assertSame(29000, $p['dibayar']);
        // ubah setelah lunas: tambah Es Teh (harga baru), kurangi pentol -> harga lama pentol tetap 8000
        $e = $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 2], ['id' => 3, 'qty' => 1]]], $this->now);
        $this->assertSame(8000 * 2 + 4000 + 5000, $e['total']); $this->assertSame(25000 - 29000, $e['selisih']);
        // tidak bisa Selesai selama ada selisih
        $this->os->setStatus($o['kode'], 'Diproses', $this->now); $this->os->setStatus($o['kode'], 'Diantar', $this->now);
        $this->assertSame(409, $this->err(fn() => $this->os->setStatus($o['kode'], 'Selesai', $this->now))->status);
        $this->assertSame(409, $this->err(fn() => $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 1]]], $this->now))->status, 'tak bisa diubah setelah diantar');
        $s = $this->os->settle($o['kode'], $this->now); $this->assertSame(0, $s['selisih']);
        $this->assertSame('Selesai', $this->os->setStatus($o['kode'], 'Selesai', $this->now)['status']);
    }

    public function test_owner_edit_rules(): void
    {
        $this->setUpStore();
        $o = $this->os->place($this->input(), $this->now)['order'];
        $this->assertSame(422, $this->err(fn() => $this->os->edit($o['kode'], ['items' => []], $this->now))->status);
        $this->assertSame(409, $this->err(fn() => $this->os->edit($o['kode'], ['items' => [['id' => 2, 'qty' => 1]]], $this->now))->status, 'menu habis tidak bisa ditambah');
        $this->assertSame(422, $this->err(fn() => $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 0]]], $this->now))->status);
        $e = $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 1]], 'catatan' => 'Tanpa saus'], $this->now);
        $this->assertSame(8000, $e['total']); $this->assertSame('Tanpa saus', $e['catatan']); $this->assertSame(0, $e['selisih'], 'tunai tidak punya selisih');
        $this->assertSame(409, $this->err(fn() => $this->os->settle($o['kode'], $this->now))->status);
        $this->os->setStatus($o['kode'], 'Dibatalkan', $this->now);
        $this->assertSame(409, $this->err(fn() => $this->os->edit($o['kode'], ['items' => [['id' => 1, 'qty' => 2]]], $this->now))->status);
    }

    public function test_clear_all_orders_needs_confirmation_and_resets_numbers(): void
    {
        $this->setUpStore();
        $this->os->place($this->input(), $this->now); $this->os->place($this->input(), $this->now);
        $this->assertSame(422, $this->err(fn() => $this->os->clearAll('hapus'))->status);
        $this->assertSame(2, $this->os->clearAll('HAPUS'));
        $this->assertSame([], $this->os->listForAdmin($this->now));
        $this->assertSame('SNK-001', $this->os->place($this->input(), $this->now)['order']['kode']);
        $this->assertSame(3, count($this->st->menuAll()), 'menu tidak ikut terhapus');
    }
}
