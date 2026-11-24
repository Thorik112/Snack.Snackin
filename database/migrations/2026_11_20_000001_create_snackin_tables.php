<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Akun Pemilik memakai tabel users bawaan Laravel; tambah penanda aktif dan waktu masuk terakhir.
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('aktif')->default(true)->after('password');
            $t->timestamp('last_login')->nullable()->after('aktif');
        });

        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 60);
            $t->string('kategori', 30)->index();
            $t->unsignedInteger('harga');
            $t->boolean('stok')->default(true);
            $t->string('emoji', 16)->nullable();
            $t->string('foto')->nullable();
            $t->string('deskripsi', 200)->nullable();
            $t->unsignedInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('token', 64)->unique();
            $t->string('nama', 60);
            $t->string('telp', 20);
            $t->enum('jenis', ['Ambil', 'Antar']);
            $t->string('alamat')->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('catatan', 200)->nullable();
            $t->unsignedInteger('ongkir')->default(0);
            $t->unsignedInteger('total');
            $t->enum('bayar', ['Tunai', 'QRIS']);
            $t->string('status', 20)->index();
            $t->unsignedInteger('uang')->nullable();
            $t->unsignedInteger('dibayar')->default(0);      // jumlah yang sudah dikonfirmasi lunas (QRIS)
            $t->boolean('diubah')->default(false);           // pesanan pernah diubah Pemilik
            $t->enum('sumber', ['online', 'kasir'])->default('online');
            $t->timestamp('created_at')->useCurrent()->index();
            $t->timestamp('updated_at')->nullable();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $t->string('nama', 60);
            $t->unsignedSmallInteger('qty');
            $t->unsignedInteger('harga');
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('k', 40)->primary();
            $t->text('v');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_items');
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['aktif', 'last_login']);
        });
    }
};
