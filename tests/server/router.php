<?php
/**
 * SERVER UJI (bukan untuk produksi): meniru routes/web.php + controller Laravel memakai
 * kelas domain yang sama dan PdoStore (SQLite). Dipakai untuk menguji frontend tanpa Laravel.
 * Jalankan:  SNK_DB=/tmp/snk.sqlite php -S 127.0.0.1:8080 -t public tests/server/router.php   (dari folder proyek)
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
spl_autoload_register(function ($c) use ($root) {
    foreach (['App\\' => $root . '/app/', 'Tests\\' => $root . '/tests/'] as $p => $d) {
        if (str_starts_with($c, $p)) { $f = $d . str_replace('\\', '/', substr($c, strlen($p))) . '.php'; if (is_file($f)) require $f; }
    }
});
date_default_timezone_set('Asia/Jakarta');
require $root . '/app/Support/Dummy.php';
require $root . '/app/Support/helpers.php';

use App\Domain\{ApiError, PageData};
use App\Domain\Services\{AccountService, MenuService, OrderService, SettingsService};
use App\Support\Dummy;
use Tests\Support\PdoStore;

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];
if (preg_match('#^/(css|js|img|vendor|storage)/#', $path) && is_file($root . '/public' . $path)) return false;

session_name('snk_test'); session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
function csrf_token() { return $_SESSION['csrf']; }

$dbFile = getenv('SNK_DB') ?: sys_get_temp_dir() . '/snk_test.sqlite';
$fresh = !is_file($dbFile);
$store = new PdoStore(new PDO('sqlite:' . $dbFile));
$store->migrate();
if ($fresh) {
    $store->db->exec("INSERT INTO users (name,email,password,aktif) VALUES ('Pemilik','owner@snackin.test','" . password_hash('rahasia123', PASSWORD_BCRYPT) . "',1)");
    foreach (Dummy::menu() as $i => $m) $store->db->prepare('INSERT INTO menu_items (nama,kategori,harga,stok,emoji,deskripsi,urutan) VALUES (?,?,?,?,?,?,?)')->execute([$m['nama'], $m['kategori'], $m['harga'], $m['stok'] ? 1 : 0, $m['emoji'], $m['desk'], $i + 1]);
}
$now = time();
function json_out($data, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function body(): array { static $b; if ($b === null) { $raw = file_get_contents('php://input'); $j = $raw !== '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json') ? json_decode($raw, true) : null; $b = is_array($j) ? $j : $_POST; } return $b; }
function throttle(string $k, int $max, int $sec): void { $t = &$_SESSION['thr'][$k]; $t = array_values(array_filter($t ?? [], fn($x) => $x > time() - $sec)); if (count($t) >= $max) json_out(['message' => 'Too Many Attempts.'], 429); $t[] = time(); }
function me(): ?array { global $store; $id = $_SESSION['uid'] ?? null; if (!$id) return null; $a = $store->accountFind((int)$id); return $a && $a['aktif'] ? $a : null; }

header('X-Content-Type-Options: nosniff');

// ---------- API ----------
if (str_starts_with($path, '/api/') || str_starts_with($path, '/admin/api/')) {
    if ($method !== 'GET' && ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') !== $_SESSION['csrf']) json_out(['message' => 'CSRF token mismatch.'], 419);
    $admin = str_starts_with($path, '/admin/api/') && $path !== '/admin/api/login';
    if ($admin && !me()) json_out(['message' => 'Sesi berakhir. Silakan masuk lagi.'], 401);
    $b = body(); $myId = me()['id'] ?? null;
    $acc = fn() => new AccountService($store, $myId);
    $upload = function (string $field, string $dir) use ($root): ?string {
        $f = $_FILES[$field] ?? null; if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][mime_content_type($f['tmp_name'])] ?? null;
        if (!$ext || $f['size'] > 2 * 1024 * 1024) json_out(['message' => 'The ' . $field . ' failed validation.', 'errors' => [$field => ['Foto harus JPG atau PNG, maksimal 2 MB.']]], 422);
        @mkdir($root . '/public/storage/' . $dir, 0777, true);
        $n = bin2hex(random_bytes(8)) . '.' . $ext; move_uploaded_file($f['tmp_name'], $root . '/public/storage/' . $dir . '/' . $n);
        return '/storage/' . $dir . '/' . $n;
    };
    try {
        $os = new OrderService($store); $ms = new MenuService($store);
        if ($path === '/api/state' && $method === 'GET') json_out(PageData::user($store, $now));
        if ($path === '/api/orders' && $method === 'POST') { throttle('place', 10, 600); json_out($os->place($b, $now), 201); }
        if ($path === '/api/orders/mine') json_out(['orders' => $os->mine(is_array($b['tokens'] ?? null) ? $b['tokens'] : [], $now)]);
        if ($path === '/api/orders/lookup') { throttle('lookup', 10, 60); json_out($os->lookup((string)($b['kode'] ?? ''), (string)($b['telp4'] ?? ''), $now)); }
        if (preg_match('#^/api/orders/([^/]+)/cancel$#', $path, $m)) json_out(['order' => $os->userCancel($m[1], (string)($b['token'] ?? ''), $now)]);

        if ($path === '/admin/api/login') {
            throttle('login', 5, 60);
            $a = $store->accountByEmail(strtolower((string)($b['email'] ?? '')));
            if (!$a || !$a['aktif'] || !password_verify((string)($b['password'] ?? ''), $a['password_hash'] ?? ($store->db->query('SELECT password FROM users WHERE id=' . (int)$a['id'])->fetchColumn()))) json_out(['message' => 'Email atau kata sandi salah, atau akun nonaktif.'], 422);
            session_regenerate_id(true); $_SESSION['uid'] = $a['id']; $store->touchLogin((int)$a['id'], $now);
            json_out(['ok' => true, 'nama' => $a['nama']]);
        }
        if ($path === '/admin/api/logout') { $_SESSION = []; session_destroy(); json_out(['ok' => true]); }
        if ($path === '/admin/api/orders' && $method === 'GET') json_out(['orders' => $os->listForAdmin($now)]);
        if ($path === '/admin/api/orders/kasir') json_out(['order' => $os->kasir($b, $now)], 201);
        if ($path === '/admin/api/orders' && $method === 'DELETE') json_out(['dihapus' => $os->clearAll((string)($b['confirm'] ?? ''))]);
        if (preg_match('#^/admin/api/orders/([^/]+)/selisih$#', $path, $m)) json_out(['order' => $os->settle($m[1], $now)]);
        if (preg_match('#^/admin/api/orders/([^/]+)$#', $path, $m) && $method === 'POST') json_out(['order' => $os->edit($m[1], $b, $now)]);
        if (preg_match('#^/admin/api/orders/([^/]+)/status$#', $path, $m)) json_out(['order' => $os->setStatus($m[1], (string)($b['status'] ?? ''), $now)]);
        if (preg_match('#^/admin/api/menu(?:/(\d+))?$#', $path, $m) && $method === 'POST') {
            $id = isset($m[1]) ? (int)$m[1] : null; $foto = $upload('foto', 'menu');
            if ($foto === null && filter_var($b['hapus_foto'] ?? false, FILTER_VALIDATE_BOOLEAN)) $foto = '';
            json_out(['menu' => $ms->save($id, $b, $foto)], $id ? 200 : 201);
        }
        if (preg_match('#^/admin/api/menu/(\d+)/stok$#', $path, $m)) json_out(['menu' => $ms->setStok((int)$m[1], filter_var($b['stok'] ?? false, FILTER_VALIDATE_BOOLEAN))]);
        if (preg_match('#^/admin/api/menu/(\d+)$#', $path, $m) && $method === 'DELETE') { $ms->delete((int)$m[1]); json_out(['ok' => true]); }
        if ($path === '/admin/api/akun' && $method === 'GET') json_out(['akun' => $acc()->all($now)]);
        if (preg_match('#^/admin/api/akun(?:/(\d+))?$#', $path, $m) && $method === 'POST') { $id = isset($m[1]) ? (int)$m[1] : null; json_out(['akun' => $acc()->save($id, $b, $now)], $id ? 200 : 201); }
        if (preg_match('#^/admin/api/akun/(\d+)/aktif$#', $path, $m)) json_out(['akun' => $acc()->setAktif((int)$m[1], filter_var($b['aktif'] ?? false, FILTER_VALIDATE_BOOLEAN), $now)]);
        if (preg_match('#^/admin/api/akun/(\d+)$#', $path, $m) && $method === 'DELETE') { $acc()->delete((int)$m[1]); json_out(['ok' => true]); }
        if ($path === '/admin/api/settings') { (new SettingsService($store))->update($b); json_out(['settings' => PageData::settings($store, $now)]); }
        if ($path === '/admin/api/settings/qris' && $method === 'POST') { $u = $upload('qris', 'qris'); if (!$u) json_out(['message' => 'invalid', 'errors' => ['qris' => ['Pilih gambar QRIS.']]], 422); (new SettingsService($store))->setQris($u); json_out(['settings' => PageData::settings($store, $now)]); }
        if ($path === '/admin/api/settings/qris' && $method === 'DELETE') { (new SettingsService($store))->setQris(''); json_out(['settings' => PageData::settings($store, $now)]); }
        json_out(['message' => 'Not found'], 404);
    } catch (ApiError $e) {
        json_out(['message' => $e->getMessage(), 'errors' => (object)$e->errors], $e->status);
    }
}

// ---------- test helper: set jam sistem tidak dipakai; reset DB ----------
if ($path === '/__reset' && $method === 'POST') { $store->db->exec('DELETE FROM order_items; DELETE FROM orders;'); json_out(['ok' => true]); }

// ---------- halaman ----------
$GLOBALS['sections'] = []; $GLOBALS['stack'] = [];
class Req { public string $route = ''; function routeIs($n) { return $this->route === $n; } function is($p) { return false; } }
$GLOBALS['req'] = new Req; function request() { return $GLOBALS['req']; }
function compileBlade($src) {
    $src = preg_replace('/@extends\(\'([^\']+)\'\)/', '<?php $__ext=\'$1\'; ?>', $src);
    $src = preg_replace('/@section\(\'(\w+)\',\s*\'((?:[^\'\\\\]|\\\\.)*)\'\)/', '<?php $GLOBALS[\'sections\'][\'$1\']=\'$2\'; ?>', $src);
    $src = preg_replace('/@section\(\'(\w+)\'\)/', '<?php ob_start(); $GLOBALS[\'stack\'][]=\'$1\'; ?>', $src);
    $src = str_replace('@endsection', '<?php $__n=array_pop($GLOBALS[\'stack\']); $GLOBALS[\'sections\'][$__n]=ob_get_clean(); ?>', $src);
    $src = preg_replace('/@yield\(\'(\w+)\'\)/', '<?php echo $GLOBALS[\'sections\'][\'$1\'] ?? \'\'; ?>', $src);
    $src = preg_replace_callback('/@include\(\'([^\']+)\'(?:,\s*(\[.*?\]))?\)/', fn($m) => '<?php echo renderView(\'' . $m[1] . '\', array_merge(get_defined_vars(), ' . ($m[2] ?? '[]') . ')); ?>', $src);
    $src = preg_replace('/@foreach\((.*?)\)\s*$/m', '<?php foreach($1): ?>', $src);
    $src = str_replace('@endforeach', '<?php endforeach; ?>', $src);
    $src = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?php echo $1; ?>', $src);
    return preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars((string)($1)); ?>', $src);
}
function renderView($__name, $__data) {
    global $root;
    $__php = compileBlade(file_get_contents($root . '/resources/views/' . str_replace('.', '/', $__name) . '.blade.php'));
    foreach ($__data as $__k => $__v) { if (!str_starts_with($__k, '__')) $$__k = $__v; }
    unset($__k, $__v); $__ext = null;
    ob_start(); eval('?>' . $__php); $__out = ob_get_clean();
    return $__ext ? renderView($__ext, $__data) : $__out;
}
function page(string $view, string $route, array $data) { $GLOBALS['req']->route = $route; echo renderView($view, $data); exit; }

$userPages = ['/' => 'user.menu', '/keranjang' => 'user.keranjang', '/konfirmasi' => 'user.konfirmasi', '/berhasil' => 'user.sukses', '/transaksi' => 'user.transaksi'];
if (isset($userPages[$path])) page($userPages[$path], $userPages[$path], ['seed' => PageData::json(PageData::user($store, $now))]);
if ($path === '/admin/login') { if (me()) { header('Location: /admin'); exit; } page('admin.login', 'admin.login', ['seed' => PageData::json(PageData::user($store, $now))]); }
$adminPages = ['/admin' => ['admin.dashboard', fn() => Dummy::dashboard()], '/admin/pesanan' => ['admin.pesanan'], '/admin/kasir' => ['admin.kasir'], '/admin/menu' => ['admin.menu'],
    '/admin/laporan' => ['admin.laporan', fn() => ['stat' => Dummy::laporanStat()]], '/admin/akun' => ['admin.akun'], '/admin/pengaturan' => ['admin.pengaturan']];
if (isset($adminPages[$path])) {
    $u = me(); if (!$u) { header('Location: /admin/login'); exit; }
    [$v, $fn] = $adminPages[$path] + [1 => fn() => []];
    page($v, $v, ['seed' => PageData::json(PageData::admin($store, $now, ['id' => $u['id'], 'nama' => $u['nama'], 'email' => $u['email']]))] + $fn());
}
http_response_code(404); page('errors.404', 'error', []);
