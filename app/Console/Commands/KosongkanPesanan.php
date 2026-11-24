<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Domain\Services\OrderService;
use App\Infrastructure\EloquentStore;
use Illuminate\Console\Command;

/** php artisan snackin:kosongkan — hapus semua pesanan (mis. setelah presentasi). Menu, akun, dan pengaturan tetap. */
final class KosongkanPesanan extends Command
{
    protected $signature = 'snackin:kosongkan {--yes : Lewati pertanyaan konfirmasi}';
    protected $description = 'Hapus semua pesanan dan mulai nomor dari SNK-001 lagi (menu, akun, pengaturan tidak dihapus)';

    public function handle(EloquentStore $store): int
    {
        if (!$this->option('yes') && !$this->confirm('Semua pesanan akan dihapus permanen. Lanjutkan?')) return self::FAILURE;
        $n = (new OrderService($store))->clearAll('HAPUS');
        $this->info("$n pesanan dihapus.");
        return self::SUCCESS;
    }
}
