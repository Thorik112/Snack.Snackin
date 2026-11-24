<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/** php artisan snackin:sandi email@contoh.com — atur ulang kata sandi Pemilik yang lupa (atau buat akun baru). */
final class AturSandi extends Command
{
    protected $signature = 'snackin:sandi {email} {--nama=Pemilik}';
    protected $description = 'Atur ulang kata sandi akun Pemilik (membuat akunnya bila belum ada) dan aktifkan';

    public function handle(): int
    {
        $email = strtolower((string)$this->argument('email'));
        $pass = (string)$this->secret('Kata sandi baru (minimal 8 karakter)');
        if (strlen($pass) < 8) { $this->error('Kata sandi minimal 8 karakter.'); return self::FAILURE; }
        User::updateOrCreate(['email' => $email], ['name' => $this->option('nama'), 'password' => Hash::make($pass), 'aktif' => true]);
        $this->info("Akun $email siap dipakai.");
        return self::SUCCESS;
    }
}
