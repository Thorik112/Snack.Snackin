<?php
declare(strict_types=1);
namespace App\Domain;

use App\Domain\Contracts\Store;
use App\Domain\Services\{AccountService, MenuService, OrderService};

/** Data awal yang ditanam di halaman (window.SNK) agar halaman langsung tampil tanpa menunggu API. */
final class PageData
{
    /** Pengaturan untuk frontend, ditambah status buka/tutup yang dihitung server. */
    public static function settings(Store $s, int $now): array
    {
        $c = $s->settings();
        $c['open'] = OpenHours::isOpen((bool)$c['aktif'], (string)$c['buka'], (string)$c['tutup'], Presenter::nowMinutes($now));
        return $c;
    }

    public static function user(Store $s, int $now): array
    {
        return ['menu' => (new MenuService($s))->all(), 'settings' => self::settings($s, $now), 'now' => $now];
    }

    /** @param array{id:int,nama:string,email:string} $me */
    public static function admin(Store $s, int $now, array $me): array
    {
        return self::user($s, $now) + [
            'orders' => (new OrderService($s))->listForAdmin($now),
            'akun' => (new AccountService($s, $me['id']))->all($now),
            'me' => $me,
        ];
    }

    public static function json(array $d): string
    {
        return json_encode($d, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
