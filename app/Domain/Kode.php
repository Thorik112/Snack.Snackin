<?php
declare(strict_types=1);
namespace App\Domain;

final class Kode
{
    public static function fromId(int $id): string
    {
        return 'SNK-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
    }

    /** "345", "snk345", "SNK-345" -> "SNK-345"; null bila bukan kode. */
    public static function normalize(string $in): ?string
    {
        $s = strtoupper(preg_replace('/[\s\-_]/', '', $in) ?? '');
        if (preg_match('/^(?:SNK)?(\d{1,9})$/', $s, $m)) return self::fromId((int)$m[1]);
        return null;
    }
}
