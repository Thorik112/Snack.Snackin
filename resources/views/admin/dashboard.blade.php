@extends('layouts.admin')
@section('title', 'Overview')
@section('page', 'dashboard')
@section('content')
<div class="head">
  <h1>Overview</h1>
  <div class="actions">
    <button class="btn sm" type="button" data-open="habis">{!! icon('check') !!} Tandai Menu Habis</button>
    <button class="btn sm" type="button" data-menu-add>{!! icon('plus') !!} Tambah Menu</button>
    <a class="btn sm" href="/admin/kasir">{!! icon('cash') !!} Catat Pesanan Langsung</a>
  </div>
</div>

@include('admin._stat', ['stat' => $stat])

<div class="card mt2">
  <div class="head" style="margin-bottom:8px"><h2>Antrean Pesanan</h2>
    <label class="sel">{!! icon('filter') !!}<select data-filter-for="queue" data-key="status" aria-label="Filter status"><option value="">Semua</option><option>Belum dibayar</option><option>Diterima</option><option>Diproses</option><option>Siap diambil</option><option>Diantar</option></select></label></div>
  <div class="list" style="--cols:121fr 156fr 143fr 165fr 197fr 204fr 72fr">
    <div class="lhead"><span>Kode</span><span>Pelanggan</span><span>Pesanan</span><span>Penerimaan</span><span>Total Dan Bayar</span><span>Status</span><span>Aksi</span></div>
    <div class="list" data-list="queue" data-size="5" style="--cols:121fr 156fr 143fr 165fr 197fr 204fr 72fr"></div>
  </div>
  <div class="pager" data-pager-for="queue"></div>
</div>

<div class="grid-main mt2">
  <div class="card">
    <h2>Menu Terlaris Hari Ini</h2>
    <div id="ch-donut"></div>
  </div>
  <div class="card">
    <div class="head" style="margin:0;min-height:0"><h2>Penjualan 7 Hari Terakhir</h2>
      <div class="seg2" id="wk-metric" role="group" aria-label="Metrik"><button type="button" class="on" data-m="rev">Pendapatan</button><button type="button" data-m="cnt">Pesanan</button></div></div>
    <div id="ch-week"></div>
  </div>
</div>

<div class="grid2 mt2">
  <div class="card chart2">
    <div class="ctitle"><span class="muted">Ambil&nbsp; vs Antar</span><i class="sep"></i><span><i class="d p"></i>Ambil</span><span><i class="d t"></i>Antar</span></div>
    <div id="ch-ab"></div>
  </div>
  <div class="card chart2">
    <div class="ctitle"><span class="muted">QRIS&nbsp; vs Tunai</span><i class="sep"></i><span><i class="d p"></i>QRIS</span><span><i class="d t"></i>Tunai</span></div>
    <div id="ch-qt"></div>
  </div>
</div>
@endsection
@section('overlays')
  @include('admin._detail')
  @include('admin._formmenu')
<div class="overlay center" id="habis">
  <div class="sheet dialog">
    <div class="top"><h2>Tandai Menu Habis</h2><button class="btn sm" data-close aria-label="Tutup">{!! icon('x') !!}</button></div>
    <p class="muted" style="margin-bottom:8px">Nyalakan tombol untuk menu yang masih tersedia.</p>
    <div id="habis-list" style="max-height:50vh;overflow:auto"></div>
    <div class="actions mt2"><button class="btn" data-close>Tutup</button></div>
  </div>
</div>
@endsection
