<?php
declare(strict_types=1);
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $r = $next($request);
        $h = $r->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=()');
        $h->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https://*.tile.openstreetmap.org; connect-src 'self' https://nominatim.openstreetmap.org; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
        if ($request->isSecure()) $h->set('Strict-Transport-Security', 'max-age=15552000');
        if ($request->is('admin*') || $request->is('api/*')) $h->set('Cache-Control', 'no-store');
        return $r;
    }
}
