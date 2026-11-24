<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Masuk · Snack.Snackin</title>
@include('partials.icon')
<script>try{var t=localStorage.getItem('snk_theme');try{t=JSON.parse(t)}catch(e){}if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
<style>html{background:#221D3F}html[data-theme=light]{background:#F3F0FA}</style>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
</head>
<body data-area="login" data-page="login">
<div class="loginwrap">
  <form class="loginbox" id="loginform" novalidate>
    <div class="brand">@include('partials.brand')</div>
    <p class="muted" style="margin-bottom:24px">Masuk sebagai pemilik untuk mengelola pesanan.</p>
    <div class="field"><label for="email">Email</label><input class="input" id="email" type="email" placeholder="nama@email.com" autocomplete="username"><span class="errmsg" data-err="email">Masukkan email yang valid</span></div>
    <div class="field"><label for="pw">Kata sandi</label><input class="input" id="pw" type="password" placeholder="••••••••" autocomplete="current-password"><span class="errmsg" data-err="pw">Kata sandi wajib diisi</span></div>
    <div class="chip bad" id="loginerr" role="alert" hidden style="margin-bottom:12px">Email atau kata sandi salah, atau akun nonaktif</div>
    <button type="submit" class="btn primary block">Masuk</button>
    <p class="hint mt">Belum punya akun? Akun hanya dibuat oleh pemilik melalui menu Kelola Akun.</p>
  </form>
</div>
<div class="toast" id="toast" role="status"></div>
<script>window.SNK = {!! $seed ?? '{}' !!};</script>
<script src="/js/app.js"></script>
</body>
</html>
