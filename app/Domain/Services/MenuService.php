<?php
declare(strict_types=1);
namespace App\Domain\Services;

use App\Domain\{ApiError, Presenter, Rules};
use App\Domain\Contracts\Store;

final class MenuService
{
    public function __construct(private Store $s) {}

    public function all(): array { return array_map([Presenter::class, 'menu'], $this->s->menuAll()); }

    /** @param ?string $foto path foto baru; "" = hapus foto; null = biarkan */
    public function save(?int $id, array $in, ?string $foto): array
    {
        if ($id !== null && !$this->s->menuFind($id)) throw ApiError::notFound('Menu tidak ditemukan.');
        [$e, $c] = Rules::menu($in);
        if ($e) throw ApiError::invalid($e);
        if ($foto !== null) $c['foto'] = $foto === '' ? null : $foto;
        return Presenter::menu($this->s->menuSave($id, $c));
    }

    public function setStok(int $id, bool $stok): array
    {
        $m = $this->s->menuFind($id) ?? throw ApiError::notFound('Menu tidak ditemukan.');
        return Presenter::menu($this->s->menuSave($id, ['nama' => $m['nama'], 'kategori' => $m['kategori'], 'harga' => $m['harga'], 'stok' => $stok, 'emoji' => $m['emoji'], 'desk' => $m['desk']]));
    }

    public function delete(int $id): void
    {
        if (!$this->s->menuFind($id)) throw ApiError::notFound('Menu tidak ditemukan.');
        $this->s->menuDelete($id);
    }
}
