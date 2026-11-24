<?php
namespace Database\Seeders;

use App\Support\Dummy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** OPSIONAL: pesanan contoh 30 hari untuk mencoba Laporan. Jangan dijalankan di toko sungguhan: php artisan db:seed --class=DemoSeeder */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $menu = DB::table('menu_items')->pluck('id', 'nama');
        foreach (array_reverse(Dummy::orders()) as $o) {
            [$h, $m] = array_map('intval', explode('.', $o['jam']));
            $ts = strtotime('today ' . sprintf('%02d:%02d', $h, $m)) - $o['hari'] * 86400;
            $id = DB::table('orders')->insertGetId([
                'kode' => 'TMP' . bin2hex(random_bytes(4)), 'token' => bin2hex(random_bytes(16)), 'nama' => $o['nama'], 'telp' => preg_replace('/\D/', '', $o['telp']),
                'jenis' => $o['jenis'], 'alamat' => $o['alamat'], 'catatan' => $o['catatan'], 'ongkir' => $o['ongkir'], 'total' => $o['total'], 'bayar' => $o['bayar'],
                'status' => $o['hari'] === 0 ? $o['status'] : $o['status'], 'sumber' => 'online', 'created_at' => date('Y-m-d H:i:s', $ts),
            ]);
            DB::table('orders')->where('id', $id)->update(['kode' => \App\Domain\Kode::fromId($id)]);
            foreach ($o['items'] as $i) DB::table('order_items')->insert(['order_id' => $id, 'menu_item_id' => $menu[$i['nama']] ?? null, 'nama' => $i['nama'], 'qty' => $i['qty'], 'harga' => $i['harga']]);
        }
    }
}
