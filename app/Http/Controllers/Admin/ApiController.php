<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Domain\Services\{AccountService, MenuService, OrderService, SettingsService};
use App\Domain\PageData;
use App\Http\Controllers\Api\ApiController as Base;
use Illuminate\Http\{JsonResponse, Request, UploadedFile};
use Illuminate\Support\Facades\{Auth, Storage};

/** API untuk Pemilik. Semua route di sini dilindungi EnsureOwner. */
final class ApiController extends Base
{
    private function me(): ?int { return Auth::id() ? (int)Auth::id() : null; }

    // ---------- pesanan ----------
    public function orders(): JsonResponse
    {
        return $this->run(fn () => ['orders' => (new OrderService($this->store))->listForAdmin($this->now())]);
    }

    public function kasir(Request $r): JsonResponse
    {
        return $this->run(fn () => ['order' => (new OrderService($this->store))->kasir($r->only(['nama', 'bayar', 'uang', 'items']), $this->now())], 201);
    }

    public function status(Request $r, string $kode): JsonResponse
    {
        return $this->run(fn () => ['order' => (new OrderService($this->store))->setStatus($kode, (string)$r->input('status', ''), $this->now())]);
    }

    public function orderEdit(Request $r, string $kode): JsonResponse
    {
        return $this->run(fn () => ['order' => (new OrderService($this->store))->edit($kode, $r->only(['items', 'catatan']), $this->now())]);
    }

    public function orderSettle(string $kode): JsonResponse
    {
        return $this->run(fn () => ['order' => (new OrderService($this->store))->settle($kode, $this->now())]);
    }

    public function ordersClear(Request $r): JsonResponse
    {
        return $this->run(fn () => ['dihapus' => (new OrderService($this->store))->clearAll((string)$r->input('confirm', ''))]);
    }

    // ---------- menu ----------
    public function menuSave(Request $r, ?int $id = null): JsonResponse
    {
        $r->validate(['foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048']], ['foto.*' => 'Foto harus JPG atau PNG, maksimal 2 MB.']);
        return $this->run(function () use ($r, $id) {
            $old = $id ? ($this->store->menuFind($id)['foto'] ?? null) : null;
            $foto = null; // null = biarkan
            if ($r->hasFile('foto')) $foto = $this->saveUpload($r->file('foto'), 'menu');
            elseif ($r->boolean('hapus_foto')) $foto = '';
            $m = (new MenuService($this->store))->save($id, $r->all(), $foto);
            if ($foto !== null) $this->dropUpload($old, 'menu');
            return ['menu' => $m];
        }, $id ? 200 : 201);
    }

    public function menuStok(Request $r, int $id): JsonResponse
    {
        return $this->run(fn () => ['menu' => (new MenuService($this->store))->setStok($id, $r->boolean('stok'))]);
    }

    public function menuDelete(int $id): JsonResponse
    {
        return $this->run(function () use ($id) {
            $old = $this->store->menuFind($id)['foto'] ?? null;
            (new MenuService($this->store))->delete($id);
            $this->dropUpload($old, 'menu');
            return ['ok' => true];
        });
    }

    // ---------- akun ----------
    private function accounts(): AccountService { return new AccountService($this->store, $this->me()); }

    public function akun(): JsonResponse { return $this->run(fn () => ['akun' => $this->accounts()->all($this->now())]); }

    public function akunSave(Request $r, ?int $id = null): JsonResponse
    {
        return $this->run(fn () => ['akun' => $this->accounts()->save($id, $r->only(['nama', 'email', 'password', 'aktif']), $this->now())], $id ? 200 : 201);
    }

    public function akunAktif(Request $r, int $id): JsonResponse
    {
        return $this->run(fn () => ['akun' => $this->accounts()->setAktif($id, $r->boolean('aktif'), $this->now())]);
    }

    public function akunDelete(int $id): JsonResponse
    {
        return $this->run(function () use ($id) { $this->accounts()->delete($id); return ['ok' => true]; });
    }

    // ---------- pengaturan ----------
    public function settings(Request $r): JsonResponse
    {
        return $this->run(function () use ($r) {
            (new SettingsService($this->store))->update($r->all());
            return ['settings' => PageData::settings($this->store, $this->now())];
        });
    }

    public function qrisSave(Request $r): JsonResponse
    {
        $r->validate(['qris' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048']], ['qris.*' => 'Gambar QRIS harus JPG atau PNG, maksimal 2 MB.']);
        return $this->run(function () use ($r) {
            $old = $this->store->settings()['qris'] ?? '';
            (new SettingsService($this->store))->setQris($this->saveUpload($r->file('qris'), 'qris'));
            $this->dropUpload($old, 'qris');
            return ['settings' => PageData::settings($this->store, $this->now())];
        });
    }

    public function qrisDelete(): JsonResponse
    {
        return $this->run(function () {
            $old = $this->store->settings()['qris'] ?? '';
            (new SettingsService($this->store))->setQris('');
            $this->dropUpload($old, 'qris');
            return ['settings' => PageData::settings($this->store, $this->now())];
        });
    }

    // ---------- berkas ----------
    private function saveUpload(UploadedFile $f, string $dir): string
    {
        return '/storage/' . $f->store($dir, 'public');   // jalankan: php artisan storage:link
    }

    /** Hapus berkas lama hanya bila berada di storage/menu atau storage/qris kita sendiri. */
    private function dropUpload(?string $url, string $dir): void
    {
        $prefix = '/storage/' . $dir . '/';
        if ($url && str_starts_with($url, $prefix) && !str_contains($url, '..')) Storage::disk('public')->delete(substr($url, 9));
    }
}
