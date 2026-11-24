<?php
declare(strict_types=1);
namespace Tests\Support;

use App\Domain\{Defaults, Kode};
use App\Domain\Contracts\Store;

/** Implementasi Store dengan PDO + SQLite. Dipakai tes dan server uji; skema sama dengan migrasi Laravel. */
final class PdoStore implements Store
{
    public function __construct(public readonly \PDO $db)
    {
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public static function memory(): self { $s = new self(new \PDO('sqlite::memory:')); $s->migrate(); return $s; }

    public function migrate(): void
    {
        $this->db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, aktif INTEGER NOT NULL DEFAULT 1, last_login INTEGER);
CREATE TABLE IF NOT EXISTS menu_items (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT NOT NULL, kategori TEXT NOT NULL, harga INTEGER NOT NULL, stok INTEGER NOT NULL DEFAULT 1, emoji TEXT, foto TEXT, deskripsi TEXT, urutan INTEGER NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, kode TEXT NOT NULL UNIQUE CHECK(length(kode) <= 20), token TEXT NOT NULL UNIQUE, nama TEXT NOT NULL CHECK(length(nama) <= 60), telp TEXT NOT NULL CHECK(length(telp) <= 20), jenis TEXT NOT NULL, alamat TEXT, lat REAL, lng REAL, catatan TEXT, ongkir INTEGER NOT NULL, total INTEGER NOT NULL, bayar TEXT NOT NULL, status TEXT NOT NULL, uang INTEGER, dibayar INTEGER NOT NULL DEFAULT 0, diubah INTEGER NOT NULL DEFAULT 0, sumber TEXT NOT NULL DEFAULT 'online', created_at INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS orders_created ON orders(created_at);
CREATE TABLE IF NOT EXISTS order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE, menu_item_id INTEGER, nama TEXT NOT NULL, qty INTEGER NOT NULL, harga INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS settings (k TEXT PRIMARY KEY, v TEXT NOT NULL);
SQL);
    }

    public function transaction(callable $fn): mixed
    {
        if ($this->db->inTransaction()) return $fn();
        $this->db->beginTransaction();
        try { $r = $fn(); $this->db->commit(); return $r; } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    private function q(string $sql, array $p = []): \PDOStatement { $st = $this->db->prepare($sql); $st->execute($p); return $st; }

    // ---- pengaturan
    public function settings(): array
    {
        $out = Defaults::settings();
        foreach ($this->q('SELECT k, v FROM settings')->fetchAll() as $r) $out[$r['k']] = json_decode($r['v'], true);
        return $out;
    }

    public function saveSettings(array $kv): void
    {
        foreach ($kv as $k => $v) $this->q('INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v', [$k, json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }

    // ---- menu
    private function menuRow(array $r): array
    {
        return ['id' => (int)$r['id'], 'nama' => $r['nama'], 'kategori' => $r['kategori'], 'harga' => (int)$r['harga'], 'stok' => (bool)$r['stok'],
            'emoji' => (string)$r['emoji'], 'foto' => $r['foto'] ?: null, 'desk' => (string)$r['deskripsi']];
    }
    public function menuAll(): array { return array_map([$this, 'menuRow'], $this->q('SELECT * FROM menu_items ORDER BY urutan, id')->fetchAll()); }
    public function menuFind(int $id): ?array { $r = $this->q('SELECT * FROM menu_items WHERE id = ?', [$id])->fetch(); return $r ? $this->menuRow($r) : null; }
    public function menuByIds(array $ids): array
    {
        if (!$ids) return [];
        $in = implode(',', array_fill(0, count($ids), '?')); $out = [];
        foreach ($this->q("SELECT * FROM menu_items WHERE id IN ($in)", array_values($ids))->fetchAll() as $r) $out[(int)$r['id']] = $this->menuRow($r);
        return $out;
    }
    public function menuSave(?int $id, array $d): array
    {
        if ($id === null) {
            $this->q('INSERT INTO menu_items (nama, kategori, harga, stok, emoji, foto, deskripsi, urutan) VALUES (?,?,?,?,?,?,?, (SELECT COALESCE(MAX(urutan),0)+1 FROM menu_items))',
                [$d['nama'], $d['kategori'], $d['harga'], (int)$d['stok'], $d['emoji'] ?? '', $d['foto'] ?? null, $d['desk'] ?? '']);
            $id = (int)$this->db->lastInsertId();
        } else {
            $this->q('UPDATE menu_items SET nama=?, kategori=?, harga=?, stok=?, emoji=?, deskripsi=? WHERE id=?', [$d['nama'], $d['kategori'], $d['harga'], (int)$d['stok'], $d['emoji'] ?? '', $d['desk'] ?? '', $id]);
            if (array_key_exists('foto', $d)) $this->q('UPDATE menu_items SET foto=? WHERE id=?', [$d['foto'], $id]);
        }
        return $this->menuFind($id);
    }
    public function menuDelete(int $id): void { $this->q('DELETE FROM menu_items WHERE id = ?', [$id]); }

    // ---- akun
    private function accRow(array $r): array
    {
        return ['id' => (int)$r['id'], 'nama' => $r['name'], 'email' => $r['email'], 'aktif' => (bool)$r['aktif'], 'last_login' => $r['last_login'] === null ? null : (int)$r['last_login'], 'password' => $r['password']];
    }
    public function accountAll(): array { return array_map([$this, 'accRow'], $this->q('SELECT * FROM users ORDER BY id')->fetchAll()); }
    public function accountFind(int $id): ?array { $r = $this->q('SELECT * FROM users WHERE id = ?', [$id])->fetch(); return $r ? $this->accRow($r) : null; }
    public function accountByEmail(string $email): ?array { $r = $this->q('SELECT * FROM users WHERE lower(email) = lower(?)', [$email])->fetch(); return $r ? $this->accRow($r) : null; }
    public function accountSave(?int $id, array $d): array
    {
        if ($id === null) {
            $this->q('INSERT INTO users (name, email, password, aktif) VALUES (?,?,?,?)', [$d['nama'], $d['email'], $d['password'], (int)$d['aktif']]);
            $id = (int)$this->db->lastInsertId();
        } else {
            $this->q('UPDATE users SET name=?, email=?, aktif=? WHERE id=?', [$d['nama'], $d['email'], (int)$d['aktif'], $id]);
            if (isset($d['password'])) $this->q('UPDATE users SET password=? WHERE id=?', [$d['password'], $id]);
        }
        return $this->accountFind($id);
    }
    public function accountDelete(int $id): void { $this->q('DELETE FROM users WHERE id = ?', [$id]); }
    public function touchLogin(int $id, int $ts): void { $this->q('UPDATE users SET last_login=? WHERE id=?', [$ts, $id]); }

    // ---- pesanan
    private function withItems(array $rows): array
    {
        if (!$rows) return [];
        $ids = array_column($rows, 'id'); $in = implode(',', array_fill(0, count($ids), '?')); $items = [];
        foreach ($this->q("SELECT * FROM order_items WHERE order_id IN ($in) ORDER BY id", $ids)->fetchAll() as $i) {
            $items[(int)$i['order_id']][] = ['menu_id' => $i['menu_item_id'] === null ? null : (int)$i['menu_item_id'], 'nama' => $i['nama'], 'qty' => (int)$i['qty'], 'harga' => (int)$i['harga']];
        }
        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id']; $r['created_at'] = (int)$r['created_at']; $r['items'] = $items[$r['id']] ?? [];
            foreach (['ongkir', 'total'] as $k) $r[$k] = (int)$r[$k];
            foreach (['lat', 'lng'] as $k) $r[$k] = $r[$k] === null ? null : (float)$r[$k];
            $r['uang'] = $r['uang'] === null ? null : (int)$r['uang'];
            $r['dibayar'] = (int)$r['dibayar']; $r['diubah'] = (bool)$r['diubah'];
        }
        return $rows;
    }
    public function orderCreate(array $row, array $items): array
    {
        return $this->transaction(function () use ($row, $items) {
            $this->q('INSERT INTO orders (kode, token, nama, telp, jenis, alamat, lat, lng, catatan, ongkir, total, bayar, status, uang, dibayar, sumber, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                ['T' . substr($row['token'], 0, 18), $row['token'], $row['nama'], $row['telp'], $row['jenis'], $row['alamat'], $row['lat'], $row['lng'], $row['catatan'], $row['ongkir'], $row['total'], $row['bayar'], $row['status'], $row['uang'], $row['dibayar'] ?? 0, $row['sumber'], $row['created_at']]);
            $id = (int)$this->db->lastInsertId();
            $this->q('UPDATE orders SET kode = ? WHERE id = ?', [Kode::fromId($id), $id]);
            foreach ($items as $i) $this->q('INSERT INTO order_items (order_id, menu_item_id, nama, qty, harga) VALUES (?,?,?,?,?)', [$id, $i['menu_id'], $i['nama'], $i['qty'], $i['harga']]);
            return $this->orderByKode(Kode::fromId($id));
        });
    }
    public function orderByKode(string $kode): ?array { return $this->withItems($this->q('SELECT * FROM orders WHERE kode = ?', [$kode])->fetchAll())[0] ?? null; }
    public function orderByToken(string $t): ?array { return $this->withItems($this->q('SELECT * FROM orders WHERE token = ?', [$t])->fetchAll())[0] ?? null; }
    public function ordersByTokens(array $tokens): array
    {
        if (!$tokens) return [];
        $in = implode(',', array_fill(0, count($tokens), '?'));
        return $this->withItems($this->q("SELECT * FROM orders WHERE token IN ($in)", array_values($tokens))->fetchAll());
    }
    public function ordersSince(int $ts, int $limit): array
    {
        return $this->withItems($this->q('SELECT * FROM orders WHERE created_at >= ? ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit, [$ts])->fetchAll());
    }
    public function orderSetStatus(string $kode, string $status): array
    {
        $this->q('UPDATE orders SET status = ? WHERE kode = ?', [$status, $kode]);
        return $this->orderByKode($kode);
    }
    public function orderUpdate(string $kode, array $fields, ?array $items = null): array
    {
        return $this->transaction(function () use ($kode, $fields, $items) {
            $id = (int)$this->q('SELECT id FROM orders WHERE kode = ?', [$kode])->fetchColumn();
            foreach ($fields as $k => $v) $this->q("UPDATE orders SET $k = ? WHERE id = ?", [is_bool($v) ? (int)$v : $v, $id]);
            if ($items !== null) {
                $this->q('DELETE FROM order_items WHERE order_id = ?', [$id]);
                foreach ($items as $i) $this->q('INSERT INTO order_items (order_id, menu_item_id, nama, qty, harga) VALUES (?,?,?,?,?)', [$id, $i['menu_id'], $i['nama'], $i['qty'], $i['harga']]);
            }
            return $this->orderByKode($kode);
        });
    }
    public function ordersClear(): int
    {
        $n = (int)$this->q('SELECT COUNT(*) FROM orders')->fetchColumn();
        $this->db->exec("DELETE FROM order_items; DELETE FROM orders; DELETE FROM sqlite_sequence WHERE name IN ('orders','order_items');");
        return $n;
    }
}
