<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Domain\{Geo, Kode, OpenHours, OrderFlow, OrderInput, Pricing};
use PHPUnit\Framework\TestCase;

final class DomainTest extends TestCase
{
    public function test_initial_status_by_payment(): void
    {
        $this->assertSame('Belum dibayar', OrderFlow::initial('QRIS'));
        $this->assertSame('Diterima', OrderFlow::initial('Tunai'));
    }

    public function test_flow_ambil_and_antar(): void
    {
        $this->assertTrue(OrderFlow::can('Diproses', 'Siap diambil', 'Ambil'));
        $this->assertFalse(OrderFlow::can('Diproses', 'Diantar', 'Ambil'));
        $this->assertTrue(OrderFlow::can('Diproses', 'Diantar', 'Antar'));
        $this->assertFalse(OrderFlow::can('Diterima', 'Selesai', 'Ambil'), 'tidak boleh melompati status');
        $this->assertFalse(OrderFlow::can('Selesai', 'Diproses', 'Ambil'), 'status akhir tidak bisa diubah');
        $this->assertFalse(OrderFlow::can('Dibatalkan', 'Diterima', 'Ambil'));
        $this->assertSame([], OrderFlow::next('Selesai', 'Antar'));
        $this->assertTrue(OrderFlow::can('Belum dibayar', 'Diterima', 'Ambil'));
        $this->assertFalse(OrderFlow::can('Siap diambil', 'Dibatalkan', 'Ambil'), 'sudah siap tidak bisa dibatalkan');
    }

    public function test_user_cancel_rules(): void
    {
        $this->assertTrue(OrderFlow::userCanCancel('Belum dibayar'));
        $this->assertTrue(OrderFlow::userCanCancel('Diterima'));
        $this->assertFalse(OrderFlow::userCanCancel('Diproses'));
        $this->assertFalse(OrderFlow::userCanCancel('Selesai'));
    }

    public function test_pricing(): void
    {
        $l = [['harga' => 8000, 'qty' => 2], ['harga' => 12000, 'qty' => 1]];
        $this->assertSame(['sub' => 28000, 'ongkir' => 0, 'total' => 28000], Pricing::total($l, 'Ambil', 5000));
        $this->assertSame(['sub' => 28000, 'ongkir' => 5000, 'total' => 33000], Pricing::total($l, 'Antar', 5000));
    }

    public function test_open_hours(): void
    {
        $this->assertTrue(OpenHours::isOpen(true, '10.00', '21.00', 10 * 60));
        $this->assertTrue(OpenHours::isOpen(true, '10.00', '21.00', 20 * 60 + 59));
        $this->assertFalse(OpenHours::isOpen(true, '10.00', '21.00', 21 * 60));
        $this->assertFalse(OpenHours::isOpen(true, '10.00', '21.00', 9 * 60 + 59));
        $this->assertFalse(OpenHours::isOpen(false, '10.00', '21.00', 12 * 60), 'saklar mati = tutup');
        $this->assertTrue(OpenHours::isOpen(true, '18:00', '02:00', 23 * 60), 'lewat tengah malam');
        $this->assertTrue(OpenHours::isOpen(true, '18:00', '02:00', 60));
        $this->assertFalse(OpenHours::isOpen(true, '18:00', '02:00', 12 * 60));
        $this->assertSame(630, OpenHours::minutes('10.30'));
        $this->assertNull(OpenHours::minutes('25.00'));
    }

    public function test_kode(): void
    {
        $this->assertSame('SNK-007', Kode::fromId(7));
        $this->assertSame('SNK-1234', Kode::fromId(1234));
        $this->assertSame('SNK-345', Kode::normalize('345'));
        $this->assertSame('SNK-345', Kode::normalize(' snk 345 '));
        $this->assertSame('SNK-007', Kode::normalize('snk-7'));
        $this->assertNull(Kode::normalize('abc'));
        $this->assertNull(Kode::normalize(''));
    }

    public function test_geo(): void
    {
        $this->assertEqualsWithDelta(0.0, Geo::km(-7.96, 112.61, -7.96, 112.61), 0.0001);
        // 0.01 derajat lintang ~ 1.11 km
        $this->assertEqualsWithDelta(1.11, Geo::km(-7.96, 112.61, -7.95, 112.61), 0.02);
    }

    public function test_phone_normalisation(): void
    {
        $this->assertSame('081234567890', OrderInput::phone('+62 812-3456-7890'));
        $this->assertSame('081234567890', OrderInput::phone('81234567890'));
        $this->assertSame('081234567890', OrderInput::phone('0812 3456 7890'));
    }

    public function test_input_valid(): void
    {
        [$e, $c] = OrderInput::validate([
            'nama' => ' Budi ', 'telp' => '0812-3456-7890', 'jenis' => 'Antar', 'alamat' => 'Jl. Veteran No. 1, Malang',
            'lat' => '-7.95', 'lng' => '112.62', 'bayar' => 'QRIS', 'items' => [['id' => 1, 'qty' => 2], ['id' => 1, 'qty' => 1], ['id' => 3, 'qty' => 4]],
        ]);
        $this->assertSame([], $e);
        $this->assertSame('Budi', $c['nama']);
        $this->assertSame('081234567890', $c['telp']);
        $this->assertSame([['id' => 1, 'qty' => 3], ['id' => 3, 'qty' => 4]], $c['items'], 'baris sama digabung');
        $this->assertSame(-7.95, $c['lat']);
    }

    public function test_input_errors(): void
    {
        [$e] = OrderInput::validate(['nama' => '', 'telp' => '123', 'jenis' => 'X', 'bayar' => 'Kartu', 'items' => []]);
        foreach (['nama', 'telp', 'jenis', 'bayar', 'items'] as $k) $this->assertArrayHasKey($k, $e, $k);

        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Antar', 'alamat' => 'pendek', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 1]]]);
        $this->assertArrayHasKey('alamat', $e);

        [$e, $c] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'alamat' => 'diabaikan', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 1]]]);
        $this->assertSame([], $e);
        $this->assertSame('', $c['alamat'], 'alamat dikosongkan untuk Ambil');

        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 0]]]);
        $this->assertArrayHasKey('items', $e);
        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 100]]]);
        $this->assertArrayHasKey('items', $e);
        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 60], ['id' => 1, 'qty' => 60]]]);
        $this->assertArrayHasKey('items', $e, 'penggabungan tidak boleh melewati batas');
        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Ambil', 'bayar' => 'Tunai', 'items' => 'bukan-array']);
        $this->assertArrayHasKey('items', $e);
        [$e] = OrderInput::validate(['nama' => 'Budi', 'telp' => '081234567890', 'jenis' => 'Antar', 'alamat' => 'Jl. Veteran No. 1', 'lat' => '999', 'lng' => '1', 'bayar' => 'Tunai', 'items' => [['id' => 1, 'qty' => 1]]]);
        $this->assertArrayHasKey('lat', $e);
    }
}
