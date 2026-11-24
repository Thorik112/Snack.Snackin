<?php
declare(strict_types=1);
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Hanya Pemilik yang sudah masuk (dan akunnya masih aktif) boleh membuka area admin. */
final class EnsureOwner
{
    public function handle(Request $request, Closure $next)
    {
        $u = Auth::user();
        if (!$u || !$u->aktif) {
            if ($u) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); }
            if ($request->expectsJson() || $request->is('admin/api/*')) {
                return response()->json(['message' => 'Sesi berakhir. Silakan masuk lagi.'], 401);
            }
            return redirect()->route('admin.login');
        }
        return $next($request);
    }
}
