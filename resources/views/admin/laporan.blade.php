@extends('layouts.admin')
@section('title', 'Laporan')
@section('page', 'laporan')
@section('content')
<div class="head"><h1>Laporan Penjualan</h1><button class="btn sm" type="button" id="csv">{!! icon('download') !!} Unduh CSV</button></div>
@include('admin._stat', ['stat' => $stat])
<div class="card mt2">
  <div class="toolbar">
    <label class="sel">{!! icon('cal') !!}<select data-filter-for="rep" data-key="range" aria-label="Periode"><option value="7">7 Hari Terakhir</option><option value="14">14 Hari Terakhir</option><option value="30">30 Hari Terakhir</option></select></label>
    <label class="sel">{!! icon('wallet') !!}<select data-filter-for="rep" data-key="metode" aria-label="Metode bayar"><option value="">Semua Pembayaran</option><option>QRIS</option><option>Tunai</option></select></label>
    <label class="sel">{!! icon('filter') !!}<select data-filter-for="rep" data-key="urut" aria-label="Urutkan"><option value="tgl">Terbaru</option><option value="pend">Pendapatan Tertinggi</option><option value="pes">Pesanan Terbanyak</option></select></label>
  </div>
  <div class="list" style="--cols:1.5fr 1fr 1fr 1fr 1.5fr 1.3fr">
    <div class="lhead"><span>Tanggal</span><span class="r">Pesanan</span><span class="r">Ambil</span><span class="r">Antar</span><span class="r">Pendapatan</span><span class="r">Ongkir</span></div>
    <div class="list" data-list="rep" data-size="7" style="--cols:1.5fr 1fr 1fr 1fr 1.5fr 1.3fr"></div>
  </div>
  <div class="pager" data-pager-for="rep"></div>
</div>
@endsection
