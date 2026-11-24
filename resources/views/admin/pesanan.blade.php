@extends('layouts.admin')
@section('title', 'Pesanan Masuk')
@section('page', 'pesanan')
@section('content')
<div class="head"><h1>Pesanan Masuk</h1><span class="muted" style="display:flex;align-items:center;gap:8px">{!! icon('clock') !!} <span id="today">&nbsp;</span></span></div>
<div class="card">
  <div class="toolbar">
    <div class="search">{!! icon('search') !!}<input class="input" data-search-for="orders" placeholder="Cari kode atau nama..." aria-label="Cari pesanan"></div>
    <label class="sel">{!! icon('cal') !!}<select data-filter-for="orders" data-key="range" aria-label="Rentang tanggal"><option value="0">Hari Ini</option><option value="6">7 Hari Terakhir</option><option value="30">Bulan Ini</option></select></label>
    <label class="sel">{!! icon('filter') !!}<select data-filter-for="orders" data-key="status" aria-label="Filter status"><option value="">Semua Status</option><option>Belum dibayar</option><option>Diterima</option><option>Diproses</option><option>Siap diambil</option><option>Diantar</option><option>Selesai</option><option>Dibatalkan</option></select></label>
    <label class="sel"><select data-filter-for="orders" data-key="jenis" aria-label="Filter penerimaan"><option value="">Ambil dan Antar</option><option>Ambil</option><option>Antar</option></select></label>
  </div>
  <div class="list" style="--cols:121fr 156fr 143fr 165fr 197fr 204fr 72fr">
    <div class="lhead"><span>Kode</span><span>Pelanggan</span><span>Pesanan</span><span>Penerimaan</span><span>Total Dan Bayar</span><span>Status</span><span>Aksi</span></div>
    <div class="list" data-list="orders" data-size="20" style="--cols:121fr 156fr 143fr 165fr 197fr 204fr 72fr"></div>
  </div>
  <div class="pager" data-pager-for="orders"></div>
</div>
@endsection
@section('overlays')
  @include('admin._detail')
@endsection
