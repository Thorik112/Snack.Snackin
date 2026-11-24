<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Domain\PageData;
use App\Infrastructure\EloquentStore;
use App\Support\Dummy;
use Illuminate\Support\Facades\Auth;

/** Menampilkan halaman Blade dengan data awal (window.SNK) dari database. */
final class PageController extends Controller
{
    public function __construct(private EloquentStore $store) {}

    public function user(string $view)
    {
        return view($view, ['seed' => PageData::json(PageData::user($this->store, time()))]);
    }

    public function login()
    {
        $u = Auth::user();
        if ($u && $u->aktif) return redirect()->route('admin.dashboard');
        return view('admin.login', ['seed' => PageData::json(PageData::user($this->store, time()))]);
    }

    public function admin(string $view, array $extra = [])
    {
        $u = Auth::user();
        $me = ['id' => (int)$u->id, 'nama' => (string)$u->name, 'email' => (string)$u->email];
        return view($view, ['seed' => PageData::json(PageData::admin($this->store, time(), $me))] + $extra);
    }
}
