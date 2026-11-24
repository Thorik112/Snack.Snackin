<?php
declare(strict_types=1);
namespace App\Infrastructure;

use App\Domain\{Defaults, Kode};
use App\Domain\Contracts\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Implementasi Store untuk Laravel + MySQL memakai query builder. Perilaku disamakan dengan Tests\Support\PdoStore. */
final class EloquentStore implements Store
{
    private function tz(): string { return (string)config('app.timezone', 'Asia/Jakarta'); }
    private function toDb(int $ts): string { return Carbon::createFromTimestamp($ts, $this->tz())->format('Y-m-d H:i:s'); }
    private function fromDb(?string $v): ?int { return $v === null ? null : Carbon::createFromFormat('Y-m-d H:i:s', $v, $this->tz())->getTimestamp(); }
    private function now(): string { return $this->toDb(time()); }

    public function transaction(callable $fn): mixed { return DB::transaction($fn); }

    // ---- pengaturan
    public function settings(): array
    {
        $out = Defaults::settings();
        foreach (DB::table('settings')->get() as $r) $out[$r->k] = json_decode($r->v, true);
        return $out;
    }

    public function saveSettings(array $kv): void
    {
        $rows = [];
        foreach ($kv as $k => $v) $rows[] = ['k' => (string)$k, 'v' => json_encode($v, JSON_UNESCAPED_UNICODE)];
        if ($rows) DB::table('settings')->upsert($rows, ['k'], ['v']);
    }

    // ---- menu
    private function menuRow(object $r): array
    {
        return ['id' => (int)$r->id, 'nama' => $r->nama, 'kategori' => $r->kategori, 'harga' => (int)$r->harga, 'stok' => (bool)$r->stok,
            'emoji' => (string)$r->emoji, 'foto' => $r->foto ?: null, 'desk' => (string)$r->deskripsi];
    }
    public function menuAll(): array { return DB::table('menu_items')->orderBy('urutan')->orderBy('id')->get()->map(fn($r) => $this->menuRow($r))->all(); }
    public function menuFind(int $id): ?array { $r = DB::table('menu_items')->find($id); return $r ? $this->menuRow($r) : null; }
    public function menuByIds(array $ids): array
    {
        $out = [];
        if (!$ids) return $out;
        foreach (DB::table('menu_items')->whereIn('id', array_values($ids))->get() as $r) $out[(int)$r->id] = $this->menuRow($r);
        return $out;
    }
    public function menuSave(?int $id, array $d): array
    {
        $vals = ['nama' => $d['nama'], 'kategori' => $d['kategori'], 'harga' => $d['harga'], 'stok' => (int)$d['stok'], 'emoji' => $d['emoji'] ?? '', 'deskripsi' => $d['desk'] ?? '', 'updated_at' => $this->now()];
        if (array_key_exists('foto', $d)) $vals['foto'] = $d['foto'];
        if ($id === null) {
            $vals['created_at'] = $this->now();
            $vals['urutan'] = (int)DB::table('menu_items')->max('urutan') + 1;
            $id = (int)DB::table('menu_items')->insertGetId($vals);
        } else {
            DB::table('menu_items')->where('id', $id)->update($vals);
        }
        return $this->menuFind($id);
    }
    public function menuDelete(int $id): void { DB::table('menu_items')->where('id', $id)->delete(); }

    // ---- akun
    private function accRow(object $r): array
    {
        return ['id' => (int)$r->id, 'nama' => $r->name, 'email' => $r->email, 'aktif' => (bool)$r->aktif, 'last_login' => $this->fromDb($r->last_login), 'password' => $r->password];
    }
    public function accountAll(): array { return DB::table('users')->orderBy('id')->get()->map(fn($r) => $this->accRow($r))->all(); }
    public function accountFind(int $id): ?array { $r = DB::table('users')->find($id); return $r ? $this->accRow($r) : null; }
    public function accountByEmail(string $email): ?array { $r = DB::table('users')->whereRaw('lower(email) = ?', [strtolower($email)])->first(); return $r ? $this->accRow($r) : null; }
    public function accountSave(?int $id, array $d): array
    {
        $vals = ['name' => $d['nama'], 'email' => $d['email'], 'aktif' => (int)$d['aktif'], 'updated_at' => $this->now()];
        if (isset($d['password'])) $vals['password'] = $d['password'];
        if ($id === null) {
            $vals['created_at'] = $this->now();
            $id = (int)DB::table('users')->insertGetId($vals);
        } else {
            DB::table('users')->where('id', $id)->update($vals);
        }
        return $this->accountFind($id);
    }
    public function accountDelete(int $id): void { DB::table('users')->where('id', $id)->delete(); }
    public function touchLogin(int $id, int $ts): void { DB::table('users')->where('id', $id)->update(['last_login' => $this->toDb($ts)]); }

    // ---- pesanan
    private function hydrate(\Illuminate\Support\Collection $rows): array
    {
        if ($rows->isEmpty()) return [];
        $items = DB::table('order_items')->whereIn('order_id', $rows->pluck('id')->all())->orderBy('id')->get()->groupBy('order_id');
        return $rows->map(function ($r) use ($items) {
            return [
                'id' => (int)$r->id, 'kode' => $r->kode, 'token' => $r->token, 'nama' => $r->nama, 'telp' => $r->telp, 'jenis' => $r->jenis,
                'alamat' => (string)$r->alamat, 'lat' => $r->lat === null ? null : (float)$r->lat, 'lng' => $r->lng === null ? null : (float)$r->lng,
                'catatan' => (string)$r->catatan, 'ongkir' => (int)$r->ongkir, 'total' => (int)$r->total, 'bayar' => $r->bayar, 'status' => $r->status,
                'uang' => $r->uang === null ? null : (int)$r->uang, 'dibayar' => (int)$r->dibayar, 'diubah' => (bool)$r->diubah, 'sumber' => $r->sumber, 'created_at' => $this->fromDb($r->created_at),
                'items' => ($items[$r->id] ?? collect())->map(fn($i) => ['menu_id' => $i->menu_item_id === null ? null : (int)$i->menu_item_id, 'nama' => $i->nama, 'qty' => (int)$i->qty, 'harga' => (int)$i->harga])->all(),
            ];
        })->all();
    }
    public function orderCreate(array $row, array $items): array
    {
        return DB::transaction(function () use ($row, $items) {
            $id = (int)DB::table('orders')->insertGetId([
                'kode' => 'T' . substr($row['token'], 0, 18), 'token' => $row['token'], 'nama' => $row['nama'], 'telp' => $row['telp'], 'jenis' => $row['jenis'],
                'alamat' => $row['alamat'], 'lat' => $row['lat'], 'lng' => $row['lng'], 'catatan' => $row['catatan'], 'ongkir' => $row['ongkir'],
                'total' => $row['total'], 'bayar' => $row['bayar'], 'status' => $row['status'], 'uang' => $row['uang'], 'dibayar' => $row['dibayar'] ?? 0, 'sumber' => $row['sumber'],
                'created_at' => $this->toDb($row['created_at']), 'updated_at' => $this->now(),
            ]);
            DB::table('orders')->where('id', $id)->update(['kode' => Kode::fromId($id)]);
            DB::table('order_items')->insert(array_map(fn($i) => ['order_id' => $id, 'menu_item_id' => $i['menu_id'], 'nama' => $i['nama'], 'qty' => $i['qty'], 'harga' => $i['harga']], $items));
            return $this->orderByKode(Kode::fromId($id));
        });
    }
    public function orderByKode(string $kode): ?array { return $this->hydrate(DB::table('orders')->where('kode', $kode)->get())[0] ?? null; }
    public function orderByToken(string $t): ?array { return $this->hydrate(DB::table('orders')->where('token', $t)->get())[0] ?? null; }
    public function ordersByTokens(array $tokens): array { return $tokens ? $this->hydrate(DB::table('orders')->whereIn('token', array_values($tokens))->get()) : []; }
    public function ordersSince(int $ts, int $limit): array
    {
        return $this->hydrate(DB::table('orders')->where('created_at', '>=', $this->toDb($ts))->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get());
    }
    public function orderSetStatus(string $kode, string $status): array
    {
        DB::table('orders')->where('kode', $kode)->update(['status' => $status, 'updated_at' => $this->now()]);
        return $this->orderByKode($kode);
    }
    public function orderUpdate(string $kode, array $fields, ?array $items = null): array
    {
        return DB::transaction(function () use ($kode, $fields, $items) {
            $id = (int)DB::table('orders')->where('kode', $kode)->value('id');
            DB::table('orders')->where('id', $id)->update($fields + ['updated_at' => $this->now()]);
            if ($items !== null) {
                DB::table('order_items')->where('order_id', $id)->delete();
                DB::table('order_items')->insert(array_map(fn($i) => ['order_id' => $id, 'menu_item_id' => $i['menu_id'], 'nama' => $i['nama'], 'qty' => $i['qty'], 'harga' => $i['harga']], $items));
            }
            return $this->orderByKode($kode);
        });
    }
    public function ordersClear(): int
    {
        $n = (int)DB::table('orders')->count();
        DB::transaction(function () { DB::table('order_items')->delete(); DB::table('orders')->delete(); });
        $drv = DB::connection()->getDriverName();
        if ($drv === 'mysql' || $drv === 'mariadb') { DB::statement('ALTER TABLE orders AUTO_INCREMENT = 1'); DB::statement('ALTER TABLE order_items AUTO_INCREMENT = 1'); }
        elseif ($drv === 'sqlite') DB::table('sqlite_sequence')->whereIn('name', ['orders', 'order_items'])->delete();
        return $n;
    }
}
