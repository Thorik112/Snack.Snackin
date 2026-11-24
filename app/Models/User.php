<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Akun Pemilik. Tidak ada pendaftaran publik; akun hanya dibuat dari menu Kelola Akun atau seeder. */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'aktif'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'last_login' => 'datetime', 'password' => 'hashed'];
    }
}
