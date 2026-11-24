<?php

if (!function_exists('rp')) {
    function rp($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
}
if (!function_exists('statusClass')) {
    function statusClass(string $s): string {
        return match ($s) {
            'Belum dibayar' => 'warn', 'Diterima' => 'info', 'Diproses' => 'violet',
            'Siap diambil', 'Diantar', 'Selesai' => 'ok', 'Dibatalkan' => 'bad', default => 'info',
        };
    }
}
if (!function_exists('spark')) {
    /** Titik sparkline (tinggi 0-48) dengan pola bergerigi yang deterministik. */
    function spark(int $seed, int $n = 40, float $trend = 0.4): array {
        $o = [];
        for ($i = 0; $i < $n; $i++) {
            $v = 20 + $trend * $i * 0.45 + 8 * sin($i * 0.5 + $seed) + 6 * sin($i * 1.15 + $seed * 1.7) + 3 * cos($i * 2.1 + $seed);
            $o[] = (int) max(3, min(48, round($v)));
        }
        return $o;
    }
}
if (!function_exists('icon')) {
    function icon(string $n): string {
        $p = [
            'home'=>'<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'list'=>'<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
            'cash'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
            'menu'=>'<path d="M4 7h16M4 12h16M4 17h10"/>', 'chart'=>'<path d="M21 12A9 9 0 1 1 12 3v9z"/>',
            'users'=>'<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2.2.6 3.5 2.5 3.5 6"/>',
            'gear'=>'<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
            'plus'=>'<path d="M12 5v14M5 12h14"/>', 'search'=>'<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
            'bag'=>'<path d="M5 8h14l-1 12H6L5 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>', 'receipt'=>'<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
            'check'=>'<path d="M5 12l5 5 9-10"/>', 'x'=>'<path d="M6 6l12 12M18 6L6 18"/>', 'menu2'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
            'print'=>'<path d="M7 9V3h10v6"/><path d="M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/>', 'pin'=>'<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>', 'down'=>'<path d="M6 9l6 6 6-6"/>', 'arrowup'=>'<path d="M12 19V5M6 11l6-6 6 6"/>', 'arrowdn'=>'<path d="M12 5v14M6 13l6 6 6-6"/>',
            'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', 'filter'=>'<path d="M4 7h16M7 12h10M10 17h4"/>',
            'cal'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>', 'left'=>'<path d="M15 6l-6 6 6 6"/>', 'right'=>'<path d="M9 6l6 6-6 6"/>',
            'wallet'=>'<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M16 13h2"/>', 'bowl'=>'<path d="M4 11h16a8 8 0 0 1-16 0zM8 7c0-2 2-2 2-4M13 7c0-2 2-2 2-4"/>',
            'cup'=>'<path d="M6 4h12l-1.5 16h-9L6 4zM6 9h12"/>', 'dots'=>'<path d="M6 12h.01M12 12h.01M18 12h.01"/>',
            'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>',
            'moon'=>'<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/>', 'out'=>'<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>', 'download'=>'<path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>',
        ];
        return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($p[$n] ?? '') . '</svg>';
    }
}
