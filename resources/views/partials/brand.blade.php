@php
    $__f = null;
    foreach (['svg', 'png', 'webp', 'jpg'] as $__e) { if (is_file(public_path('img/logo.' . $__e))) { $__f = 'img/logo.' . $__e; break; } }
@endphp
@if($__f)<img class="brand-img" src="/{{ $__f }}?v={{ filemtime(public_path($__f)) }}" alt="Snack.Snackin">@else Snack<i>.</i>Snackin @endif
