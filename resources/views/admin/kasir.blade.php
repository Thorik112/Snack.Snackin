@extends('layouts.admin')
@section('title', 'Kasir')
@section('page', 'kasir')
@section('content')
<div class="head"><h1>Kasir</h1><span class="muted">Catat pesanan langsung (tunai atau QRIS)</span></div>
<div class="kasir">
  <div class="card">
    <div class="toolbar">
      <div class="search">{!! icon('search') !!}<input class="input" id="k-cari" placeholder="Cari menu..." aria-label="Cari menu"></div>
      <div class="tabs" id="k-tabs"></div>
    </div>
    <div class="menugrid" id="k-grid"></div>
  </div>
  <form class="card" id="kform" style="position:sticky;top:16px" novalidate>
    <h2>Pesanan Baru</h2>
    <div id="k-cart"></div>
    <div class="total"><span>Total</span><span id="k-total">Rp 0</span></div>
    <div class="field"><label for="k-nama">Nama pelanggan (opsional)</label><input class="input" id="k-nama" placeholder="Contoh: Pak Budi"></div>
    <div class="field"><label>Metode bayar</label>
      <div class="seg"><label><input type="radio" name="kb" value="Tunai" checked> Tunai</label><label><input type="radio" name="kb" value="QRIS"> QRIS</label></div></div>
    <div id="k-tunai">
      <div class="field"><label for="k-uang">Uang diterima (Rp)</label><input id="k-uang" class="input" inputmode="numeric" placeholder="0"><span class="errmsg" id="k-err">Uang diterima kurang dari total</span></div>
      <div class="kv"><span class="muted">Kembalian</span><b id="k-kembali">Rp 0</b></div>
    </div>
    <div id="k-qris" hidden><div class="qrbox" id="k-qrbox"></div><p class="hint" style="text-align:center">Minta pelanggan scan, lalu pastikan pembayaran masuk.</p></div>
    <button class="btn primary block mt" type="submit">Simpan dan Selesai</button>
    <button class="btn block mt" type="button" id="k-reset">Kosongkan</button>
  </form>
</div>
@endsection
@section('overlays')
<div class="overlay center" id="ksukses">
  <div class="sheet dialog" role="dialog" aria-label="Transaksi tersimpan">
    <h2>Transaksi tersimpan</h2>
    <p class="muted mt" id="ks-info"></p>
    <div class="actions mt2"><button type="button" class="btn" data-close>Tutup</button><button type="button" class="btn primary" id="ks-print">{!! icon('print') !!} Cetak struk</button></div>
  </div>
</div>
@endsection
