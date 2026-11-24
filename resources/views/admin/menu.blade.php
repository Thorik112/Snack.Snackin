@extends('layouts.admin')
@section('title', 'Kelola Menu')
@section('page', 'menu')
@section('content')
<div class="head"><h1>Kelola Menu</h1><button class="btn primary" type="button" data-menu-add>{!! icon('plus') !!} Tambah Menu</button></div>
<div class="card">
  <div class="toolbar">
    <div class="search">{!! icon('search') !!}<input class="input" data-search-for="menu" placeholder="Cari menu..." aria-label="Cari menu"></div>
    <label class="sel">{!! icon('filter') !!}<select data-filter-for="menu" data-key="kategori" aria-label="Filter kategori"><option value="">Semua Kategori</option><option>Bakaran</option><option>Cilok</option><option>Camilan</option><option>Nasi Bakar</option><option>Minuman</option></select></label>
    <label class="sel"><select data-filter-for="menu" data-key="stok" aria-label="Filter ketersediaan"><option value="">Semua Stok</option><option value="1">Tersedia</option><option value="0">Habis</option></select></label>
  </div>
  <div class="list" style="--cols:2.2fr 1.2fr 1.2fr 1fr 190px">
    <div class="lhead"><span>Menu</span><span>Kategori</span><span>Harga</span><span>Tersedia</span><span class="r">Aksi</span></div>
    <div class="list" data-list="menu" data-size="8" style="--cols:2.2fr 1.2fr 1.2fr 1fr 190px"></div>
  </div>
  <div class="pager" data-pager-for="menu"></div>
</div>
@endsection
@section('overlays')
  @include('admin._formmenu')
@endsection
