@extends('layouts.user')
@section('title', 'Menu')
@section('page', 'umenu')
@section('content')
<div class="split">
  <section class="stage" id="stage" aria-live="polite">
    <div class="plate-col">
      <button type="button" class="parrow prev" id="p-prev" aria-label="Menu sebelumnya">{!! icon('left') !!}</button>
      <button type="button" class="parrow next" id="p-next" aria-label="Menu berikutnya">{!! icon('right') !!}</button>
      <div class="plate" id="plate"><div class="plate-in" id="plate-in"></div><span class="soldout" id="soldout" hidden>Habis</span></div>
    </div>
    <div class="info" id="info">
      <p class="kicker"><span class="catchip" id="k-cat"></span></p>
      <h2 class="mname"><span class="l1" id="k-l1"></span> <span class="l2" id="k-l2"></span></h2>
      <p class="kdesc" id="k-desc"></p>
      <p class="avail" id="k-avail" hidden></p>
      <div class="buy">
        <p class="price" id="k-price"></p>
        <div class="qty big" role="group" aria-label="Jumlah"><button type="button" id="q-min" aria-label="Kurangi jumlah">−</button><b id="k-qty">1</b><button type="button" id="q-plus" aria-label="Tambah jumlah">+</button></div>
        <button type="button" class="btn primary xlg" id="k-add">Tambah ke Keranjang</button>
      </div>
    </div>
    <button type="button" class="seemore" id="seemore"></button>
  </section>
  <div class="listcol">
    <div class="lhd"><h1 class="ptitle">Mau pesan apa hari ini?</h1><p class="intro" id="intro"></p></div>
    <div class="filters">
      <div class="search">{!! icon('search') !!}<input class="input" id="u-cari" placeholder="Cari menu..." aria-label="Cari menu"></div>
      <div class="cats" id="u-tabs" role="tablist" aria-label="Kategori menu"></div>
    </div>
    <div class="mgrid" id="u-grid"></div>
  </div>
</div>
@endsection
@section('bar')
<div class="cfab" id="cta" hidden>
  <div class="cf-pop" role="region" aria-label="Ringkasan keranjang">
    <b class="cf-t">Keranjang Anda</b>
    <ul class="cf-list" id="cf-list"></ul>
    <div class="cf-sub"><span>Subtotal</span><b data-cart-sub>Rp 0</b></div>
    <a href="/keranjang" class="btn primary">Lihat keranjang →</a>
  </div>
  <a href="/keranjang" class="cf-btn" aria-label="Lihat keranjang">{!! icon('bag') !!}<b class="cf-n" data-cart-num>0</b></a>
</div>
@endsection
