@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('page', 'pengaturan')
@section('head')
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
<script src="/vendor/leaflet/leaflet.js"></script>
@endsection
@section('content')
<div class="head"><h1>Pengaturan</h1></div>
<form class="grid2" id="setform" novalidate>
  <div class="card">
    <h2 style="margin-bottom:16px">Toko</h2>
    <div class="field"><label for="s-toko">Nama toko</label><input class="input" id="s-toko"><span class="errmsg" data-err="toko">Nama toko wajib diisi</span></div>
    <div class="field"><label for="s-wa">Nomor WhatsApp</label><input class="input" id="s-wa" inputmode="tel"><span class="errmsg" data-err="wa">Masukkan nomor yang valid (9 sampai 14 angka)</span></div>
    <div class="field"><label>Jam buka</label><div style="display:flex;gap:10px"><input class="input" id="s-buka" type="time" aria-label="Jam buka"><input class="input" id="s-tutup" type="time" aria-label="Jam tutup"></div></div>
    <div class="field"><label for="s-kertas">Lebar kertas struk</label><select class="input" id="s-kertas"><option value="80">80 mm (printer kasir standar)</option><option value="58">58 mm (printer mini)</option></select></div>
    <div class="field"><label for="s-footer">Pesan di bawah struk</label><input class="input" id="s-footer" maxlength="80" placeholder="Terima kasih sudah memesan!"></div>
    <div class="switchrow"><span>Cetak struk otomatis di Kasir</span><label class="sw"><input type="checkbox" id="s-autoprint" aria-label="Cetak struk otomatis di Kasir"><span></span></label></div>
    <div class="switchrow"><span>Toko menerima pesanan</span><label class="sw"><input type="checkbox" id="s-aktif" aria-label="Toko menerima pesanan"><span></span></label></div>
  </div>
  <div class="card">
    <h2 style="margin-bottom:16px">Pengantaran dan Pembayaran</h2>
    <div class="field"><label for="s-ongkir">Ongkos kirim (Rp)</label><input class="input" id="s-ongkir" inputmode="numeric"><span class="errmsg" data-err="ongkir">Isi dengan angka 0 atau lebih</span></div>
    <div class="field"><label for="s-area">Area antar</label><input class="input" id="s-area"></div>
    <div class="field"><label for="s-bayarbatas">Batas waktu bayar QRIS (menit)</label><input class="input" id="s-bayarbatas" inputmode="numeric" placeholder="30"><span class="hint">Pesanan QRIS yang belum dikonfirmasi setelah batas ini diberi tanda &ldquo;lewat batas&rdquo;. Pesanan tidak dibatalkan otomatis. Isi 0 untuk mematikan.</span></div>
    <div class="field"><label for="s-estimasi">Perkiraan waktu siap (menit)</label><input class="input" id="s-estimasi" inputmode="numeric" placeholder="20"><span class="hint">Ditampilkan ke pelanggan setelah memesan. Isi 0 untuk tidak menampilkan.</span></div>
    <div class="field"><label for="s-qris">Gambar QRIS statis</label><input class="input" id="s-qris" type="file" accept="image/png,image/jpeg" style="padding-top:10px"><span class="hint">JPG atau PNG, maksimal 2 MB. Langsung tersimpan setelah dipilih.</span><div class="fotoprev"><img id="s-qprev" alt="Pratinjau QRIS" hidden style="width:140px;height:140px;object-fit:contain;background:#fff;border-radius:10px;margin-top:8px"><button type="button" class="btn sm" id="s-qdel" hidden>Hapus QRIS</button></div></div>
    <div class="actions mt"><button class="btn primary" type="submit">Simpan Pengaturan</button><button class="btn" type="button" id="s-reset">Batalkan perubahan</button></div>
  </div>
  <div class="card" style="grid-column:1/-1">
    <h2 style="margin-bottom:6px">Lokasi Dapur</h2>
    <p class="muted" style="margin-bottom:16px">Dipakai pelanggan untuk melihat lokasi dapur dan menghitung jarak pengantaran.</p>
    <div class="grid2">
      <div>
        <div class="field"><label for="s-alamat">Alamat dapur</label><input class="input" id="s-alamat" placeholder="Jalan, nomor, kota"></div>
        <div class="field"><label for="s-radius">Jangkauan antar (km)</label><input class="input" id="s-radius" inputmode="decimal" placeholder="Contoh: 3"><span class="hint">Pelanggan di luar jarak ini tetap bisa memesan, tetapi mendapat peringatan.</span></div>
        <p class="muted" style="margin-top:4px">Koordinat: <b id="s-koord">-</b></p>
        <p class="hint" id="s-nomap" hidden>Peta tidak dapat dimuat. Periksa koneksi internet lalu muat ulang halaman.</p>
      </div>
      <div id="s-mapbox">
        <div class="field"><label for="s-q">Cari lokasi dapur</label><div style="display:flex;gap:8px"><input class="input" id="s-q" type="search" placeholder="Nama jalan atau tempat" autocomplete="off"><button type="button" class="btn primary" id="s-go">Cari</button></div>
          <p class="mnote" id="s-note" role="status" hidden></p><ul class="mres" id="s-res" hidden></ul></div>
        <div class="map" id="s-map" role="region" aria-label="Peta lokasi dapur"></div>
        <p class="hint" style="margin-top:8px">Ketuk peta atau geser penanda untuk memindah lokasi, lalu tekan Simpan Pengaturan.</p>
      </div>
    </div>
  </div>
</form>
<section class="card danger-zone" aria-labelledby="dz-t" style="margin-top:20px">
  <h2 id="dz-t">Kosongkan data pesanan</h2>
  <p class="muted mt">Menghapus <b>semua</b> pesanan dan laporan, lalu nomor pesanan mulai dari SNK-001 lagi. Menu, akun, dan pengaturan tidak ikut terhapus. Dipakai misalnya setelah presentasi, sebelum toko mulai memakai sistem ini. Tidak bisa dibatalkan. Pelanggan yang masih membuka halaman Transaksi akan melihat pesanannya hilang, jadi lakukan ini saat tidak ada pesanan yang sedang berjalan.</p>
  <div class="field" style="max-width:360px"><label for="dz-in">Ketik HAPUS untuk melanjutkan</label><input class="input" id="dz-in" autocomplete="off" placeholder="HAPUS"></div>
  <button type="button" class="btn danger" id="dz-btn" disabled>Hapus semua pesanan</button>
</section>
@endsection
