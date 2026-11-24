<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal Berita Mini')</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .alert { padding: 10px; background: #d1fae5; margin-bottom: 1rem; }
        .error { color: #b91c1c; font-size: 0.9rem; }
        label { display: block; margin-top: 1rem; }
        input, select, textarea { width: 100%; padding: 6px; }
    </style>
</head>
<body>
    <h1><a href="{{ route('berita.index') }}">Portal Berita Mini</a></h1>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @yield('content')
</body>
</html>