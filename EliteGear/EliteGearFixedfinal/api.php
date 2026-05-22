<?php
/*
 * API Controller / ملف API للعمليات الخلفية:
 * Task 1 Database Design: Syncs JSON demo data with MySQL tables.
 * Task 5 Add to Cart: Stores cart in PHP session endpoint.
 * Task 7 Buy: Persists orders/products/customer updates to database.
 * Task 12 Cookies/Sessions: Saves login user in PHP session and cookie.
 * Task 13 Forms Validation Support: Returns JSON success/error responses to JavaScript.
 * Author / المنفذ: EliteGear Team.
 */

// Session cookie setup / إعداد كوكي الجلسة بشكل آمن.
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Resource map / يحدد أي resource يكتب لأي JSON file كـ fallback بجانب MySQL.
$resourceMap = [
    'products' => __DIR__ . '/data/products.json',
    'orders' => __DIR__ . '/data/orders.json',
    'customers' => __DIR__ . '/data/customers.json',
    'admins' => __DIR__ . '/data/admins.json',
    'messages' => __DIR__ . '/data/contact-messages.json',
];

// JSON response helper / توحيد شكل الردود من الـ API.
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Request body parser / يقرأ JSON body القادم من fetch().
function read_payload() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// JSON fallback writer / يحفظ نسخة محلية إذا قاعدة البيانات غير متاحة.
function write_json_file($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        respond(['ok' => false, 'error' => 'Could not encode JSON.'], 400);
    }
    $json .= PHP_EOL;
    $handle = fopen($path, 'c+');
    if (!$handle) {
        respond(['ok' => false, 'error' => 'Could not open data file.'], 500);
    }
    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, $json);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

// JSON fallback reader / يقرأ seed data عند تعبئة قاعدة البيانات.
function read_json_file($path) {
    if (!is_file($path)) {
        return [];
    }

    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

// Compatibility helper / بديل array_is_list عشان PHP 8.0 في XAMPP.
function is_json_list($value) {
    if (!is_array($value)) {
        return false;
    }

    if ($value === []) {
        return true;
    }

    return array_keys($value) === range(0, count($value) - 1);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'database-status') {
    // Database status / يستخدم للتأكد أن MySQL متصل وعدد السجلات في الجداول.
    $counts = [];
    foreach (array_keys($resourceMap) as $resource) {
        $records = eg_db_fetch_all($resource);
        $counts[$resource] = is_array($records) ? count($records) : null;
    }
    respond([
        'ok' => true,
        'database' => eg_db_available(),
        'database_name' => EG_DB_NAME,
        'counts' => $counts,
    ]);
}

if (($method === 'GET' || $method === 'POST') && $action === 'seed-database') {
    // Seed database / تعبئة MySQL من ملفات JSON demo data.
    if (!eg_db_available()) {
        respond(['ok' => false, 'error' => 'MySQL is not available. Start MySQL in XAMPP first.'], 503);
    }

    $counts = [];
    foreach ($resourceMap as $resource => $path) {
        $records = read_json_file($path);
        $counts[$resource] = count($records);
        eg_db_save_records($resource, $records);
    }

    respond([
        'ok' => true,
        'database' => true,
        'database_name' => EG_DB_NAME,
        'counts' => $counts,
    ]);
}

if ($method === 'POST' && $action === 'session') {
    // Task 12 / Login session: يحفظ المستخدم الحالي داخل PHP session و cookie.
    $payload = read_payload();
    $user = isset($payload['user']) && is_array($payload['user']) ? $payload['user'] : null;
    if (!$user || empty($user['email'])) {
        respond(['ok' => false, 'error' => 'Missing user.'], 400);
    }
    $_SESSION['elitegear_user'] = [
        'id' => $user['id'] ?? '',
        'email' => $user['email'],
        'full_name' => $user['full_name'] ?? '',
        'user_name' => $user['user_name'] ?? '',
        'role' => $user['role'] ?? 'customer',
    ];
    setcookie('elitegear_logged_in', '1', 0, '/', '', false, true);
    respond(['ok' => true]);
}

if ($method === 'POST' && $action === 'logout') {
    // Logout / تسجيل الخروج: يمسح session والكوكي الخاصة بالمستخدم.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
    }
    setcookie('elitegear_logged_in', '', time() - 42000, '/');
    session_destroy();
    respond(['ok' => true]);
}

// ── SESSION CART (Task 5 requirement: PHP sessions for cart) ─────────────────
if ($method === 'POST' && $action === 'cart-save') {
    // Task 5 / Session cart save: يحفظ نسخة من السلة داخل PHP session.
    $payload = read_payload();
    $cart = isset($payload['cart']) && is_array($payload['cart']) ? $payload['cart'] : [];
    $_SESSION['elitegear_cart'] = $cart;
    respond(['ok' => true, 'count' => count($cart)]);
}

if ($method === 'GET' && $action === 'cart-load') {
    // Task 5 / Session cart load: يرجع السلة المخزنة في session.
    $cart = $_SESSION['elitegear_cart'] ?? [];
    respond(['ok' => true, 'cart' => $cart]);
}
// ─────────────────────────────────────────────────────────────────────────────

if ($method !== 'POST' || $action !== 'save') {
    // Unsupported action / أي action غير مدعوم يرجع error واضح.
    respond(['ok' => false, 'error' => 'Unsupported request.'], 404);
}

$payload = read_payload();
$resource = $payload['resource'] ?? '';
$records = $payload['records'] ?? null;

if (!isset($resourceMap[$resource])) {
    respond(['ok' => false, 'error' => 'Unknown resource.'], 400);
}

if (!is_json_list($records)) {
    respond(['ok' => false, 'error' => 'Records must be a JSON array.'], 400);
}

write_json_file($resourceMap[$resource], $records);
// Database sync / يحفظ نفس البيانات في MySQL إذا الاتصال متاح.
$databaseSaved = eg_db_available() ? eg_db_save_records($resource, $records) : false;
respond(['ok' => true, 'resource' => $resource, 'count' => count($records), 'database' => $databaseSaved]);
