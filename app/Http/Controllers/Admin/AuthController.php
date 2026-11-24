<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\EloquentStore;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    /** Pembatasan percobaan masuk dipasang di route (throttle:5,1). Akun nonaktif ditolak dengan pesan yang sama. */
    public function login(Request $r, EloquentStore $store): JsonResponse
    {
        $cred = $r->validate(['email' => ['required', 'email', 'max:100'], 'password' => ['required', 'string', 'max:72']]);
        $ok = Auth::attempt(['email' => strtolower($cred['email']), 'password' => $cred['password'], 'aktif' => 1]);
        if (!$ok) return response()->json(['message' => 'Email atau kata sandi salah, atau akun nonaktif.'], 422);
        $r->session()->regenerate();
        $u = Auth::user();
        $store->touchLogin((int)$u->id, time());
        return response()->json(['ok' => true, 'nama' => $u->name]);
    }

    public function logout(Request $r): JsonResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return response()->json(['ok' => true]);
    }
}
