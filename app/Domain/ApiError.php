<?php
declare(strict_types=1);
namespace App\Domain;

/** Kesalahan yang dikembalikan ke klien sebagai JSON {message, errors}. */
final class ApiError extends \RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(public readonly int $status, string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public static function invalid(array $errors, string $message = 'Data belum benar. Periksa isian Anda.'): self { return new self(422, $message, $errors); }
    public static function conflict(string $message, array $errors = []): self { return new self(409, $message, $errors); }
    public static function notFound(string $message = 'Data tidak ditemukan.'): self { return new self(404, $message); }
    public static function forbidden(string $message = 'Tindakan ini tidak diizinkan.'): self { return new self(403, $message); }
}
