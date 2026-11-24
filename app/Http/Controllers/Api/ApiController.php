<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Domain\ApiError;
use App\Infrastructure\EloquentStore;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Dasar semua controller API: mengubah ApiError dari lapisan domain menjadi respons JSON. */
abstract class ApiController extends Controller
{
    public function __construct(protected EloquentStore $store) {}

    protected function now(): int { return time(); }

    protected function run(callable $fn, int $status = 200): JsonResponse
    {
        try {
            return response()->json($fn(), $status);
        } catch (ApiError $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => (object)$e->errors], $e->status);
        }
    }
}
