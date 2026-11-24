<?php
namespace App\Support;

/** Data dummy untuk frontend. Ganti dengan query Eloquent saat database siap. */
class Dummy
{
    public static function menu(): array
    {
        $r = [
            ['Pentol Bakar','Bakaran',8000,1,'🍢'],['Sosis Bakar','Bakaran',7000,1,'🌭'],['Nugget Bakar','Bakaran',8000,1,'🍗'],
            ['Chikuwa Bakar','Bakaran',9000,1,'🍥'],['Cilok Kuah Pedas','Cilok',10000,1,'🍲'],['Pisang Crispy','Camilan',12000,1,'🍌'],
            ['Roti Bakar','Camilan',13000,0,'🍞'],['Nasi Bakar Ayam','Nasi Bakar',18000,1,'🍙'],['Nasi Bakar Tongkol','Nasi Bakar',17000,1,'🍙'],
            ['Pink Lava','Minuman',10000,1,'🥤'],['Capcin','Minuman',9000,1,'🧋'],['Teh Hijau Susu','Minuman',9000,1,'🍵'],
            ['Es Teh','Minuman',4000,1,'🧊'],['Susu Jelly','Minuman',11000,0,'🥛'],
        ];
        $d = [
            'Dibakar di atas bara, diolesi saus manis pedas. Kenyal dan gurih.',
            'Sosis juicy dibakar sampai harum, disajikan dengan saus pilihan.',
            'Nugget ayam hangat dengan kulit renyah di luar, lembut di dalam.',
            'Chikuwa dibakar tipis, gurih dengan aroma laut yang ringan.',
            'Cilok lembut dalam kuah pedas gurih. Hangat untuk cuaca dingin.',
            'Pisang manis dibalut tepung renyah, digoreng saat dipesan.',
            'Roti tebal dibakar dengan olesan mentega dan isian pilihan.',
            'Nasi bakar berisi ayam suwir berbumbu, dibungkus daun pisang.',
            'Nasi bakar isi tongkol pedas khas, harum daun dan bara.',
            'Minuman segar rasa buah dengan topping jelly dan es.',
            'Kopi susu cincau yang creamy, manisnya pas.',
            'Teh hijau dengan susu, ringan dan menyegarkan.',
            'Es teh manis klasik. Teman paling pas untuk makan bakaran.',
            'Susu segar dengan jelly kenyal. Dingin dan lembut.',
        ];
        return array_map(fn($x, $i) => ['id'=>$i + 1,'nama'=>$x[0],'kategori'=>$x[1],'harga'=>$x[2],'stok'=>(bool)$x[3],'emoji'=>$x[4],'desk'=>$d[$i] ?? ''], $r, array_keys($r));
    }

    public static function orders(): array
    {
        $menu = self::menu(); $names = ['Rina','Dimas','Salsa','Bagas','Nabila','Fikri','Putri','Yoga','Aulia','Rafi','Intan','Bima','Nadia','Eko'];
        $mon = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; $out = []; $c = 0;
        for ($hari = 29; $hari >= 0; $hari--) {
            $n = $hari === 0 ? 12 : 8 + (($hari * 5) % 7);
            for ($idx = 0; $idx < $n; $idx++, $c++) {
                $k = $c % 6; $jenis = in_array($k, [1, 3]) ? 'Ambil' : 'Antar'; $bayar = in_array($k, [0, 3]) ? 'QRIS' : 'Tunai';
                $items = []; $sub = 0;
                for ($j = 0; $j < 1 + ($c % 3); $j++) {
                    $m = $menu[($c * 3 + $j * 5) % 12]; $q = 1 + (($c + $j) % 3);
                    $items[] = ['nama'=>$m['nama'],'qty'=>$q,'harga'=>$m['harga']]; $sub += $q * $m['harga'];
                }
                $ongkir = $jenis === 'Antar' ? 5000 : 0; $ready = $jenis === 'Antar' ? 'Diantar' : 'Siap diambil';
                if ($hari === 0) { $status = [0=>'Belum dibayar',1=>'Diterima',2=>'Diproses',3=>$ready,4=>'Selesai',5=>$ready][$idx % 6]; }
                else { $status = $c % 13 === 5 ? 'Dibatalkan' : 'Selesai'; }
                if ($status === 'Belum dibayar' && $bayar === 'Tunai') $status = 'Diterima';
                $ts = strtotime('2026-10-31') - $hari * 86400;
                $out[] = [
                    'kode'=>sprintf('SNK-%03d', $c + 1), 'nama'=>$names[$c % 14], 'telp'=>sprintf('0812-%04d-%04d', 1000 + $c * 37 % 9000, 2000 + $c * 53 % 8000),
                    'jenis'=>$jenis, 'alamat'=>$jenis === 'Antar' ? 'Jl. Contoh No. ' . (3 + $c % 40) . ', Malang' : '', 'catatan'=>$c % 4 === 0 ? 'Tidak pedas' : '',
                    'items'=>$items, 'ongkir'=>$ongkir, 'total'=>$sub + $ongkir, 'bayar'=>$bayar, 'status'=>$status, 'hari'=>$hari,
                    'tgl'=>date('j', $ts) . ' ' . $mon[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts), 'jam'=>sprintf('%02d.%02d', 9 + intdiv($idx * 11, $n), ($idx * 17) % 60),
                ];
            }
        }
        return array_reverse($out);
    }

    public static function akun(): array
    {
        $r = [['Lookman','lookman@snackin.test',1,'Hari ini 08.12'],['Ferly','ferly@snackin.test',1,'Kemarin 20.40'],['Mita','mita@snackin.test',0,'24 Okt 2026'],
              ['Dewi','dewi@snackin.test',1,'23 Okt 2026'],['Arman','arman@snackin.test',1,'22 Okt 2026'],['Sinta','sinta@snackin.test',0,'20 Okt 2026'],['Kevin','kevin@snackin.test',1,'18 Okt 2026']];
        return array_map(fn($x, $i) => ['id'=>$i + 1,'nama'=>$x[0],'email'=>$x[1],'peran'=>'Pemilik','aktif'=>(bool)$x[2],'terakhir'=>$x[3],'sandi'=>'password123'], $r, array_keys($r));
    }

    public static function settings(): array
    {
        return ['toko'=>'Dapur Snack Snackin','wa'=>'0812-0000-0000','buka'=>'10.00','tutup'=>'21.00','aktif'=>true,'ongkir'=>5000,'area'=>'Radius 3 km dari toko','alamat'=>'Malang, Jawa Timur (contoh, atur alamat dapur di Pengaturan)','lat'=>-7.9608,'lng'=>112.6173,'radius'=>3,'kertas'=>'80','footer'=>'Terima kasih sudah memesan!','autoprint'=>false];
    }

    /** Kartu ringkasan Laporan (isi dihitung JavaScript dari data pesanan). */
    public static function laporanStat(): array
    {
        return [
            ['key'=>'rev','label'=>'Total Pendapatan'], ['key'=>'cnt','label'=>'Jumlah Pesanan'],
            ['key'=>'bat','label'=>'Pesanan Dibatalkan','sorot'=>true], ['key'=>'avg','label'=>'Rata-rata per Pesanan'],
        ];
    }

    /** Kartu ringkasan Dashboard (isi dan grafik dihitung JavaScript dari data pesanan). */
    public static function dashboard(): array
    {
        return ['stat'=>[
            ['key'=>'today','label'=>'Pesanan Hari Ini'], ['key'=>'revenue','label'=>'Pendapatan Hari Ini'],
            ['key'=>'action','label'=>'Perlu Tindakan','sorot'=>true], ['key'=>'delivering','label'=>'Sedang Diantar'],
        ]];
    }
}
