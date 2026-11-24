@php
    $__i = null;
    foreach (['favicon.svg', 'favicon.png', 'favicon.ico', 'logo.svg', 'logo.png', 'logo.webp', 'logo.jpg'] as $__n) { if (is_file(public_path('img/' . $__n))) { $__i = 'img/' . $__n; break; } }
    $__i = $__i ?: 'favicon.svg';
@endphp
<link rel="icon" href="/{{ $__i }}?v={{ @filemtime(public_path($__i)) }}">
