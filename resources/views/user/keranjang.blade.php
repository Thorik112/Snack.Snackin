@extends('layouts.user')
@section('title', 'Keranjang')
@section('page', 'ukeranjang')
@section('content')
<h1 class="ptitle">Keranjang Anda</h1>
<div class="u2">
  <div class="card2" id="cartlines"></div>
  <aside class="card2 sumcard" id="cartfoot">
    <h2>Ringkasan</h2>
    <div class="total"><span>Subtotal</span><span id="sub">Rp 0</span></div>
    <p class="hint">Ongkir dihitung di langkah berikutnya jika pesanan diantar.</p>
    <a href="/konfirmasi" class="btn primary xlg block" id="to-pay">Lanjut ke pembayaran</a>
    <button type="button" class="btn block" id="cart-clear">Kosongkan keranjang</button>
  </aside>
</div>
@endsection
