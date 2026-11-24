<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · Snack.Snackin</title>
@include('partials.icon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
<link rel="stylesheet" href="/css/user.css">
@yield('head')
</head>
<body data-area="user" data-page="@yield('page')">
<div class="uwrap">
  <header class="uhead">
    <div class="hl"><a href="/" class="brand" aria-label="Snack.Snackin, ke halaman menu">@include('partials.brand')</a><span class="chip ok" id="opennow">Buka sampai 21.00</span></div>
    <nav class="topnav" aria-label="Navigasi">
      <a href="/" class="{{ request()->routeIs('user.menu') ? 'on' : '' }}">Menu</a>
      <a href="/keranjang" class="{{ request()->routeIs('user.keranjang') || request()->routeIs('user.konfirmasi') ? 'on' : '' }}">Keranjang</a>
      <a href="/transaksi" class="{{ request()->routeIs('user.transaksi') ? 'on' : '' }}">Transaksi</a>
    </nav>
  </header>
  @yield('content')
  <footer class="ufoot" id="ufoot">
    <div class="uf-col"><b>Cara memesan</b>
      <ol><li>Pilih menu dan masukkan ke keranjang.</li><li>Isi data Anda, pilih ambil atau diantar, lalu bayar tunai atau QRIS.</li><li>Pantau status pesanan di menu Transaksi.</li></ol></div>
    <div class="uf-col"><b>Dapur kami</b><p data-f="alamat">&nbsp;</p><p data-f="jam">&nbsp;</p></div>
    <div class="uf-col"><b>Ada pertanyaan?</b><p>Hubungi kami lewat WhatsApp <span data-f="wanum"></span>.</p><a class="btn" data-f="wa" target="_blank" rel="noopener" href="#">Chat WhatsApp</a></div>
  </footer>
</div>
@yield('bar')
<nav class="dock" aria-label="Menu utama">
  <a href="/" class="{{ request()->routeIs('user.menu') ? 'on' : '' }}">{!! icon('bowl') !!}<span>Menu</span></a>
  <a href="/keranjang" class="{{ request()->routeIs('user.keranjang') || request()->routeIs('user.konfirmasi') ? 'on' : '' }}">{!! icon('bag') !!}<span>Keranjang</span><b class="badge cartbadge" hidden>0</b></a>
  <a href="/transaksi" class="{{ request()->routeIs('user.transaksi') ? 'on' : '' }}">{!! icon('receipt') !!}<span>Transaksi</span></a>
</nav>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.SNK = {!! $seed ?? '{}' !!};</script>
<script src="/js/app.js"></script>
</body>
</html>
