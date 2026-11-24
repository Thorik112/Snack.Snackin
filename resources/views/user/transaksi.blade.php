@extends('layouts.user')
@section('title', 'Transaksi')
@section('page', 'utransaksi')
@section('content')
<h1 class="ptitle">Transaksi</h1>
<p class="muted lead">Pesanan yang Anda buat dari perangkat ini tampil di sini dan diperbarui otomatis.</p>
<form class="findbox ucard" id="t-find" novalidate>
  <p class="fb-t"><b>Memesan dari perangkat lain?</b> Masukkan kode pesanan dan 4 angka terakhir nomor WhatsApp yang dipakai saat memesan.</p>
  <div class="row fb-row"><div class="fld"><label for="t-kode">Kode pesanan</label><input id="t-kode" class="input" placeholder="Contoh: SNK-345" autocomplete="off" inputmode="text"></div><div class="fld"><label for="t-telp">4 angka akhir WhatsApp</label><input id="t-telp" class="input" placeholder="Contoh: 4821" autocomplete="off" inputmode="numeric" maxlength="4"></div><button type="submit" class="btn primary">Cari</button></div>
  <span class="errmsg" id="t-err" role="alert"></span>
</form>
<div class="tabs" id="t-tabs"><button class="tab on" data-f="">Semua</button><button class="tab" data-f="aktif">Berjalan</button><button class="tab" data-f="selesai">Selesai</button></div>
<div id="t-list" data-list="trx" data-size="3"></div>
<div class="pager" data-pager-for="trx"></div>
@endsection
@section('bar')
<div class="overlay center" id="uqris">
  <div class="sheet dialog"><h2>Bayar dengan QRIS</h2><p class="muted mt">Pesanan <b id="uq-kode"></b>. Scan, lalu bayar tepat <b id="uq-nom"></b>.</p>
    <div class="qrbox big" id="uq-box"></div><p class="hint" style="text-align:center">Setelah membayar, pemilik akan memeriksa lalu memproses pesanan Anda.</p>
    <div class="actions mt2"><button class="btn primary xlg" data-close>Tutup</button></div></div>
</div>
<div class="overlay center" id="ubatal">
  <div class="sheet dialog"><h2>Batalkan pesanan ini?</h2><p class="muted mt">Pesanan hanya bisa dibatalkan sebelum diproses.</p>
    <div class="actions mt2"><button class="btn" data-close>Kembali</button><button class="btn primary" id="ubatal-ok">Ya, batalkan</button></div></div>
</div>
@endsection
