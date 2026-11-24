<?php
declare(strict_types=1);
namespace App\Domain\Services;

use App\Domain\{ApiError, Rules};
use App\Domain\Contracts\Store;

final class SettingsService
{
    public function __construct(private Store $s) {}

    public function get(): array { return $this->s->settings(); }

    public function update(array $in): array
    {
        [$e, $c] = Rules::settings($in);
        if ($e) throw ApiError::invalid($e);
        $this->s->saveSettings($c);
        return $this->s->settings();
    }

    /** @param string $path "" untuk menghapus QRIS */
    public function setQris(string $path): array
    {
        $this->s->saveSettings(['qris' => $path]);
        return $this->s->settings();
    }
}
