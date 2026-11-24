<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Domain\PageData;
use App\Domain\Services\{MenuService, OrderService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\RateLimiter;

/** API untuk Pengguna (tanpa login). */
final class PublicController extends ApiController
{
    public function state(): JsonResponse
    {
        return $this->run(fn () => PageData::user($this->store, $this->now()));
    }

    public function place(Request $r): JsonResponse
    {
        return $this->run(fn () => (new OrderService($this->store))->place($r->only(['nama', 'telp', 'jenis', 'alamat', 'lat', 'lng', 'catatan', 'bayar', 'items']), $this->now()), 201);
    }

    public function mine(Request $r): JsonResponse
    {
        $t = $r->input('tokens', []);
        return $this->run(fn () => ['orders' => (new OrderService($this->store))->mine(is_array($t) ? $t : [], $this->now())]);
    }

    public function lookup(Request $r): JsonResponse
    {
        // Selain batas per IP: setiap kode pesanan hanya boleh ditebak 5 kali per 15 menit dari mana pun,
        // supaya 4 angka WhatsApp tidak bisa ditebak oleh penyerang yang memakai banyak alamat IP.
        $key = 'lookup-kode|' . strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string)$r->input('kode', '')));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['message' => 'Terlalu banyak percobaan untuk kode ini. Coba lagi dalam 15 menit, atau hubungi toko lewat WhatsApp.'], 429);
        }
        $res = $this->run(fn () => (new OrderService($this->store))->lookup((string)$r->input('kode', ''), (string)$r->input('telp4', ''), $this->now()));
        if ($res->getStatusCode() >= 400) RateLimiter::hit($key, 900);   // hanya percobaan yang gagal dihitung
        return $res;
    }

    public function cancel(Request $r, string $kode): JsonResponse
    {
        return $this->run(fn () => ['order' => (new OrderService($this->store))->userCancel($kode, (string)$r->input('token', ''), $this->now())]);
    }
}
