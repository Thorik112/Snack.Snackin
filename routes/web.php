<?php

use App\Http\Controllers\Admin\{ApiController as AdminApi, AuthController};
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\{EnsureOwner, SecurityHeaders};
use App\Support\Dummy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{RateLimiter, Route};

require_once app_path('Support/helpers.php');

// Unggahan (foto menu, QRIS) disimpan langsung di public/storage, jadi `php artisan storage:link` tidak wajib (hosting bersama sering melarang symlink).
config(['filesystems.disks.public.root' => public_path('storage'), 'filesystems.disks.public.url' => '/storage']);

if ($tp = config('snackin.trusted_proxies')) {
    \Illuminate\Http\Request::setTrustedProxies($tp === '*' ? ['0.0.0.0/0', '::/0'] : array_map('trim', explode(',', $tp)), \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
}

// Pembatas per jenis permintaan, dihitung per alamat IP. (throttle:N,M bawaan memakai satu hitungan bersama untuk semua route, jadi tidak dipakai.)
foreach (config('snackin.limits', ['snk-state' => [900, 1], 'snk-mine' => [600, 1], 'snk-orders' => [60, 10], 'snk-lookup' => [30, 1], 'snk-cancel' => [60, 1], 'snk-login' => [5, 1]]) as $name => [$max, $min]) {
    RateLimiter::for($name, fn (Request $r) => Limit::perMinutes($min, $max)->by($name . '|' . $r->ip())->response(fn () => response()->json(['message' => 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'], 429)));
}

Route::middleware(SecurityHeaders::class)->group(function () {

    // ---------- PENGGUNA (tanpa login) ----------
    Route::get('/', fn (PageController $p) => $p->user('user.menu'))->name('user.menu');
    Route::get('/keranjang', fn (PageController $p) => $p->user('user.keranjang'))->name('user.keranjang');
    Route::get('/konfirmasi', fn (PageController $p) => $p->user('user.konfirmasi'))->name('user.konfirmasi');
    Route::get('/berhasil', fn (PageController $p) => $p->user('user.sukses'))->name('user.sukses');
    Route::get('/transaksi', fn (PageController $p) => $p->user('user.transaksi'))->name('user.transaksi');

    Route::prefix('api')->group(function () {
        Route::get('/state', [PublicController::class, 'state'])->middleware('throttle:snk-state');
        Route::post('/orders', [PublicController::class, 'place'])->middleware('throttle:snk-orders');
        Route::post('/orders/mine', [PublicController::class, 'mine'])->middleware('throttle:snk-mine');
        Route::post('/orders/lookup', [PublicController::class, 'lookup'])->middleware('throttle:snk-lookup');
        Route::post('/orders/{kode}/cancel', [PublicController::class, 'cancel'])->middleware('throttle:snk-cancel');
    });

    // ---------- PEMILIK ----------
    Route::get('/admin/login', fn (PageController $p) => $p->login())->name('admin.login');
    Route::post('/admin/api/login', [AuthController::class, 'login'])->middleware('throttle:snk-login');

    Route::middleware(EnsureOwner::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn (PageController $p) => $p->admin('admin.dashboard', Dummy::dashboard()))->name('dashboard');
        Route::get('/pesanan', fn (PageController $p) => $p->admin('admin.pesanan'))->name('pesanan');
        Route::get('/kasir', fn (PageController $p) => $p->admin('admin.kasir'))->name('kasir');
        Route::get('/menu', fn (PageController $p) => $p->admin('admin.menu'))->name('menu');
        Route::get('/laporan', fn (PageController $p) => $p->admin('admin.laporan', ['stat' => Dummy::laporanStat()]))->name('laporan');
        Route::get('/akun', fn (PageController $p) => $p->admin('admin.akun'))->name('akun');
        Route::get('/pengaturan', fn (PageController $p) => $p->admin('admin.pengaturan'))->name('pengaturan');

        Route::prefix('api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/orders', [AdminApi::class, 'orders']);
            Route::post('/orders/kasir', [AdminApi::class, 'kasir']);
            Route::post('/orders/{kode}/status', [AdminApi::class, 'status']);
            Route::post('/orders/{kode}/selisih', [AdminApi::class, 'orderSettle']);
            Route::post('/orders/{kode}', [AdminApi::class, 'orderEdit']);
            Route::delete('/orders', [AdminApi::class, 'ordersClear']);
            Route::post('/menu', [AdminApi::class, 'menuSave']);
            Route::post('/menu/{id}', [AdminApi::class, 'menuSave'])->whereNumber('id');
            Route::post('/menu/{id}/stok', [AdminApi::class, 'menuStok'])->whereNumber('id');
            Route::delete('/menu/{id}', [AdminApi::class, 'menuDelete'])->whereNumber('id');
            Route::get('/akun', [AdminApi::class, 'akun']);
            Route::post('/akun', [AdminApi::class, 'akunSave']);
            Route::post('/akun/{id}', [AdminApi::class, 'akunSave'])->whereNumber('id');
            Route::post('/akun/{id}/aktif', [AdminApi::class, 'akunAktif'])->whereNumber('id');
            Route::delete('/akun/{id}', [AdminApi::class, 'akunDelete'])->whereNumber('id');
            Route::post('/settings', [AdminApi::class, 'settings']);
            Route::post('/settings/qris', [AdminApi::class, 'qrisSave']);
            Route::delete('/settings/qris', [AdminApi::class, 'qrisDelete']);
        });
    });
});
