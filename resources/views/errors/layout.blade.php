<!doctype html>
<html lang="id" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · Snack.Snackin</title>
@include('partials.icon')
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/app.css">
<link rel="stylesheet" href="/css/user.css">
</head>
<body data-area="error" data-page="error">
<main class="errpage">
  <a href="/" class="brand" aria-label="Snack.Snackin">@include('partials.brand')</a>
  <div class="errcard">
    <span class="errem" aria-hidden="true">@yield('emoji')</span>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <div class="erracts"><a class="btn primary xlg" href="/">Kembali ke menu</a><a class="btn xlg" href="/transaksi">Cek pesanan saya</a></div>
  </div>
</main>
</body>
</html>
