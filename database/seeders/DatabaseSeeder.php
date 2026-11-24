<?php
namespace Database\Seeders;

use App\Models\User;
use App\Support\Dummy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};

class DatabaseSeeder extends Seeder
{
    /** Membuat akun Pemilik pertama (dari .env) dan daftar menu awal. Aman dijalankan ulang. */
    public function run(): void
    {
        $email = strtolower((string)env('OWNER_EMAIL', ''));
        $pass = (string)env('OWNER_PASSWORD', '');
        if ($email === '' || strlen($pass) < 8) {
            $this->command?->error('Isi OWNER_EMAIL dan OWNER_PASSWORD (minimal 8 karakter) di .env sebelum menjalankan seeder.');
            return;
        }
        User::updateOrCreate(['email' => $email], ['name' => env('OWNER_NAME', 'Pemilik'), 'password' => Hash::make($pass), 'aktif' => true]);

        if (DB::table('menu_items')->count() === 0) {
            foreach (Dummy::menu() as $i => $m) {
                DB::table('menu_items')->insert(['nama' => $m['nama'], 'kategori' => $m['kategori'], 'harga' => $m['harga'], 'stok' => $m['stok'] ? 1 : 0,
                    'emoji' => $m['emoji'], 'deskripsi' => $m['desk'], 'urutan' => $i + 1]);
            }
        }
    }
}
