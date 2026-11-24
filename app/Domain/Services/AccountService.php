<?php
declare(strict_types=1);
namespace App\Domain\Services;

use App\Domain\{ApiError, Presenter, Rules};
use App\Domain\Contracts\Store;

final class AccountService
{
    public function __construct(private Store $s, private ?int $actorId) {}

    public function all(int $now): array { return array_map(fn($a) => Presenter::account($a, $now), $this->s->accountAll()); }

    private function activeCount(): int { return count(array_filter($this->s->accountAll(), fn($a) => $a['aktif'])); }

    public function save(?int $id, array $in, int $now): array
    {
        $cur = $id !== null ? ($this->s->accountFind($id) ?? throw ApiError::notFound('Akun tidak ditemukan.')) : null;
        [$e, $c] = Rules::account($in, $cur === null);
        $dup = isset($c['email']) ? $this->s->accountByEmail($c['email']) : null;
        if ($dup && $dup['id'] !== $id) $e['email'] = 'Email sudah dipakai akun lain.';
        if ($e) throw ApiError::invalid($e);
        if ($cur && $cur['aktif'] && !$c['aktif']) $this->guardDeactivate($cur);
        if (isset($c['password'])) $c['password'] = password_hash($c['password'], PASSWORD_BCRYPT);
        return Presenter::account($this->s->accountSave($id, $c), $now);
    }

    public function setAktif(int $id, bool $aktif, int $now): array
    {
        $a = $this->s->accountFind($id) ?? throw ApiError::notFound('Akun tidak ditemukan.');
        if ($a['aktif'] && !$aktif) $this->guardDeactivate($a);
        return Presenter::account($this->s->accountSave($id, ['nama' => $a['nama'], 'email' => $a['email'], 'aktif' => $aktif]), $now);
    }

    public function delete(int $id): void
    {
        $a = $this->s->accountFind($id) ?? throw ApiError::notFound('Akun tidak ditemukan.');
        if ($id === $this->actorId) throw ApiError::conflict('Anda tidak dapat menghapus akun yang sedang dipakai.');
        if ($a['aktif'] && $this->activeCount() <= 1) throw ApiError::conflict('Minimal harus ada satu akun aktif.');
        $this->s->accountDelete($id);
    }

    private function guardDeactivate(array $a): void
    {
        if ($a['id'] === $this->actorId) throw ApiError::conflict('Anda tidak dapat menonaktifkan akun yang sedang dipakai.');
        if ($this->activeCount() <= 1) throw ApiError::conflict('Minimal harus ada satu akun aktif.');
    }
}
