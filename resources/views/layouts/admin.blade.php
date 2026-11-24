<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · Snack.Snackin</title>
@include('partials.icon')
<script>try{var t=localStorage.getItem('snk_theme');try{t=JSON.parse(t)}catch(e){}if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<style>html{background:#221D3F}html[data-theme=light]{background:#F3F0FA}</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
@yield('head')
</head>
<body data-area="admin" data-page="@yield('page')">
<div class="shell" id="shell">
  <aside class="side" id="side">
    <div class="brand">@include('partials.brand')</div>
    <button type="button" class="sclose" id="sclose" aria-label="Tutup menu samping">{!! icon('x') !!}</button>
    <div class="sline"></div>
    <nav class="nav">
      <a href="/admin" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">{!! icon('home') !!} Dashboard</a>
      <a href="/admin/pesanan" class="{{ request()->routeIs('admin.pesanan') ? 'on' : '' }}">{!! icon('list') !!} Pesanan Masuk <span class="navbadge" id="nb-pesanan" hidden aria-label="pesanan perlu tindakan"></span></a>
      <a href="/admin/kasir" class="{{ request()->routeIs('admin.kasir') ? 'on' : '' }}">{!! icon('cash') !!} Kasir</a>
      <a href="/admin/menu" class="{{ request()->routeIs('admin.menu') ? 'on' : '' }}">{!! icon('menu') !!} Kelola Menu</a>
      <a href="/admin/laporan" class="{{ request()->routeIs('admin.laporan') ? 'on' : '' }}">{!! icon('chart') !!} Laporan</a>
      <a href="/admin/akun" class="{{ request()->routeIs('admin.akun') ? 'on' : '' }}">{!! icon('users') !!} Kelola Akun</a>
    </nav>
    <div class="sline" style="margin-top:0"></div>
  </aside>
  <div>
    <header class="topbar">
      <div style="display:flex;align-items:center">
        <button class="burger" id="burger" type="button" aria-label="Tutup menu samping" aria-expanded="true" aria-controls="side">{!! icon('menu2') !!}</button>
        <div class="crumb">Dashboard / <b>@yield('title')</b></div>
      </div>
      <div class="me">
        <a href="/admin/pengaturan" class="gear" aria-label="Pengaturan">{!! icon('gear') !!}</a>
        <div class="dd" id="userdd">
          <button type="button" id="ddbtn" aria-haspopup="true" aria-expanded="false"><span class="ava" id="whoava">L</span><span id="whoname">Lookman</span> {!! icon('down') !!}</button>
          <div class="ddm" id="ddm" hidden>
            <div class="who">Masuk sebagai Pemilik</div>
            <button type="button" data-theme-toggle><span data-theme-label>Mode terang</span> <span data-theme-icon>{!! icon('sun') !!}</span></button>
            <hr>
            <a href="/admin/login" data-logout>Keluar {!! icon('out') !!}</a>
          </div>
        </div>
      </div>
    </header>
    <main class="page">
      @yield('content')
    </main>
  </div>
</div>
<div class="scrim" id="scrim" hidden></div>
@yield('overlays')
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.SNK = {!! $seed ?? '{}' !!};</script>
<script src="/js/app.js"></script>
</body>
</html>
