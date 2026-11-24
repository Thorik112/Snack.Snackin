@extends('layouts.user')
@section('title', 'Konfirmasi')
@section('page', 'ukonfirmasi')
@section('head')
<link rel="stylesheet" href="/vendor/leaflet/leaflet.css">
<script src="/vendor/leaflet/leaflet.js"></script>
@endsection
@section('content')
<a href="/keranjang" class="backlink">← Kembali ke keranjang</a>
<h1 class="ptitle">Konfirmasi Pesanan</h1>
<div class="u2 rev">
  <form class="card2" id="coform" novalidate>
    <h2><i class="step">1</i> Data Anda</h2>
    <div class="field"><label for="n">Nama</label><input class="input" id="n" placeholder="Nama Anda" autocomplete="name"><span class="errmsg" data-err="n">Nama wajib diisi</span></div>
    <div class="field"><label for="w">Nomor WhatsApp</label><input class="input" id="w" inputmode="tel" placeholder="08xxxxxxxxxx" autocomplete="tel"><span class="errmsg" data-err="w">Masukkan nomor yang valid (9 sampai 14 angka)</span></div>

    <h2><i class="step">2</i> Cara Menerima</h2>
    <div class="seg opts">
      <label class="opt"><input type="radio" name="jenis" value="Ambil" checked><b>Ambil di toko</b><small>Gratis, tanpa ongkir</small></label>
      <label class="opt"><input type="radio" name="jenis" value="Antar"><b>Diantar</b><small>Ke alamat Anda</small></label>
    </div>
    <div class="mapwrap" id="mapwrap">
      <div id="m-ambil" class="mhead"><b>Lokasi dapur kami</b><span id="m-dapur-al"></span><a id="m-dapur-rute" class="mlink" target="_blank" rel="noopener">Buka rute di Google Maps ↗</a></div>
      <div id="m-antar" class="msearch" hidden>
        <label for="m-q">Cari lokasi pengantaran</label>
        <div class="row"><input class="input" id="m-q" type="search" placeholder="Nama jalan, gedung, atau tempat" autocomplete="off" enterkeyhint="search"><button type="button" class="btn primary" id="m-go">Cari</button></div>
        <p class="mnote" id="m-note" role="status" hidden></p>
        <ul class="mres" id="m-res" hidden></ul>
      </div>
      <div class="map" id="map" role="region" aria-label="Peta lokasi"></div>
      <div class="mmeta"><span id="m-info" role="status"></span><span class="macts"><button type="button" class="btn sm" id="m-loc">Gunakan lokasi saya</button><a id="m-gmaps" class="mlink" target="_blank" rel="noopener" hidden>Buka di Google Maps ↗</a></span></div>
      <span class="errmsg" data-err="m">Tentukan lokasi pengantaran dulu: cari alamat atau ketuk peta lalu geser penandanya.</span>
      <span id="m-dapur" hidden></span>
    </div>
    <p class="hint" id="m-nomap" hidden>Peta tidak dapat dimuat. Anda tetap bisa memesan dengan menulis alamat lengkap di bawah.</p>
    <div class="field" id="alamat" hidden><label for="a">Alamat lengkap dan patokan</label><input class="input" id="a" placeholder="Jalan, nomor rumah, patokan"><span class="hint">Terisi otomatis dari peta. Tambahkan nomor rumah atau patokan.</span><span class="errmsg" data-err="a">Alamat wajib diisi untuk pesanan diantar (minimal 8 huruf)</span></div>
    <div class="field"><label for="c">Catatan (opsional)</label><input class="input" id="c" placeholder="Contoh: tidak pedas"></div>

    <h2><i class="step">3</i> Pembayaran</h2>
    <div class="seg opts">
      <label class="opt"><input type="radio" name="bayar" value="Tunai" checked><b>Tunai</b><small>Bayar saat menerima</small></label>
      <label class="opt"><input type="radio" name="bayar" value="QRIS"><b>QRIS</b><small>Scan lalu bayar</small></label>
    </div>
    <div id="qris" hidden><div class="qrbox" id="c-qrbox"></div><p class="hint" id="qris-scan" style="text-align:center">Scan, bayar sesuai total, lalu pemilik akan mengonfirmasi pembayaran Anda.</p><div class="ucard kasirbox" id="qris-kasir" hidden><b>Bayar di kasir</b><p>Setelah memesan, datang ke kasir dan scan QRIS di sana. Sebutkan kode pesanan Anda.</p></div></div>
  </form>
  <aside class="card2 sumcard">
    <h2>Pesanan Anda</h2>
    <div id="c-items"></div>
    <div class="mt2">
      <div class="kv"><span class="muted">Subtotal</span><b id="c-sub">Rp 0</b></div>
      <div class="kv"><span class="muted">Ongkir</span><b id="c-ongkir">Rp 0</b></div>
      <div class="kv big"><b>Total</b><b id="c-total">Rp 0</b></div>
    </div>
    <button class="btn primary xlg block mt2" type="submit" form="coform" id="c-submit">Buat Pesanan</button>
  </aside>
</div>
@endsection
