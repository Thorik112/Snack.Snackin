<?php

/**
 * Batas permintaan per alamat IP: [jumlah, menit]. Wi-Fi kampus/kantor memakai satu IP untuk banyak orang,
 * jadi batasnya sengaja longgar. Ubah lewat .env bila perlu (mis. SNK_LIMIT_ORDERS=10).
 */
return [
    // Isi "*" bila situs berada di belakang proxy/CDN/terowongan (mis. Cloudflare Tunnel) supaya IP asli pengunjung terbaca.
    // Jangan diisi bila situs diakses langsung (hosting biasa): header X-Forwarded-For akan dipercaya dan batas permintaan bisa dilewati.
    'trusted_proxies' => env('SNK_TRUSTED_PROXIES', ''),

    'limits' => [
        'snk-state'  => [(int)env('SNK_LIMIT_STATE', 900), 1],     // pembaruan menu/pesanan (polling)
        'snk-mine'   => [(int)env('SNK_LIMIT_MINE', 600), 1],
        'snk-orders' => [(int)env('SNK_LIMIT_ORDERS', 60), 10],    // pesanan baru per 10 menit
        'snk-lookup' => [(int)env('SNK_LIMIT_LOOKUP', 30), 1],
        'snk-cancel' => [(int)env('SNK_LIMIT_CANCEL', 60), 1],
        'snk-login'  => [(int)env('SNK_LIMIT_LOGIN', 5), 1],       // percobaan login per menit
    ],
];
