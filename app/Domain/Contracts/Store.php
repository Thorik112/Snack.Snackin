<?php
declare(strict_types=1);
namespace App\Domain\Contracts;

/**
 * Akses data yang dibutuhkan aturan bisnis. Implementasi: App\Infrastructure\EloquentStore (Laravel + MySQL)
 * dan Tests\Support\PdoStore (SQLite, untuk tes dan server uji). Semua tanggal berupa UNIX timestamp (detik).
 */
interface Store
{
    public function transaction(callable $fn): mixed;

    /** @return array<string,mixed> pengaturan (nilai bawaan sudah digabung). */
    public function settings(): array;
    /** @param array<string,mixed> $kv */
    public function saveSettings(array $kv): void;

    /** @return list<array{id:int,nama:string,kategori:string,harga:int,stok:bool,emoji:string,foto:?string,desk:string}> */
    public function menuAll(): array;
    public function menuFind(int $id): ?array;
    /** @param list<int> $ids @return array<int,array> menu per id */
    public function menuByIds(array $ids): array;
    /** @param array<string,mixed> $data nama,kategori,harga,stok,emoji,desk, (opsional) foto */
    public function menuSave(?int $id, array $data): array;
    public function menuDelete(int $id): void;

    /** @return list<array{id:int,nama:string,email:string,aktif:bool,last_login:?int,password:string}> */
    public function accountAll(): array;
    public function accountFind(int $id): ?array;
    public function accountByEmail(string $email): ?array;
    /** @param array<string,mixed> $data nama,email,aktif, (opsional) password (sudah di-hash) */
    public function accountSave(?int $id, array $data): array;
    public function accountDelete(int $id): void;

    /**
     * @param array<string,mixed> $row nama,telp,jenis,alamat,lat,lng,catatan,ongkir,total,bayar,status,uang,sumber,token,created_at
     * @param list<array{menu_id:?int,nama:string,qty:int,harga:int}> $items
     * @return array pesanan lengkap dengan kode dan items
     */
    public function orderCreate(array $row, array $items): array;
    public function orderByKode(string $kode): ?array;
    public function orderByToken(string $token): ?array;
    /** @param list<string> $tokens */
    public function ordersByTokens(array $tokens): array;
    public function ordersSince(int $sinceTs, int $limit): array;
    public function orderSetStatus(string $kode, string $status): array;
    /** Ubah kolom pesanan; bila $items bukan null, seluruh baris item diganti. */
    public function orderUpdate(string $kode, array $fields, ?array $items = null): array;
    /** Hapus semua pesanan dan reset nomor urut. Mengembalikan jumlah pesanan yang dihapus. */
    public function ordersClear(): int;
}
