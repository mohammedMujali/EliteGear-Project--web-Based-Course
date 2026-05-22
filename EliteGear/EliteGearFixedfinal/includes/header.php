<?php
/*
 * Shared Header / ملف الهيدر المشترك:
 * Task 2 Web Design: Starts every page with same HTML head, CSS, and JS files.
 * Task 12 Cookies/Sessions: Starts PHP session with safe cookie settings.
 * Task 15 Accessibility: Central icon helper returns aria-hidden SVG icons.
 * Task 16 Efficiency: Reusable helper functions reduce repeated code.
 * Author / المنفذ: EliteGear Team.
 */

// Session setup / إعداد الجلسة: SameSite + httponly يساعدان في حماية login/session.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// Escape helper / دالة حماية النصوص قبل طباعتها في HTML لمنع XSS.
if (!function_exists('eg_e')) {
    function eg_e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Data loader / تحميل البيانات: يحاول القراءة من MySQL ثم يرجع JSON fallback إذا احتجنا.
if (!function_exists('eg_load_json')) {
    function eg_load_json($relativePath, $fallback = []) {
        $path = __DIR__ . '/../' . ltrim($relativePath, '/');
        $data = $fallback;

        if (is_file($path)) {
            $json = file_get_contents($path);
            $decoded = json_decode($json, true);
            $data = is_array($decoded) ? $decoded : $fallback;
        }

        $resource = eg_db_resource_for_path($relativePath);
        if ($resource) {
            $dbData = eg_db_fetch_all($resource);
            if (is_array($dbData)) {
                if (count($dbData) > 0 || count($data) === 0) {
                    return $dbData;
                }

                if (eg_db_save_records($resource, $data)) {
                    $seeded = eg_db_fetch_all($resource);
                    return is_array($seeded) ? $seeded : $data;
                }
            }
        }

        return $data;
    }
}

// Icon helper / دالة الأيقونات: تجمع SVG icons في مكان واحد بدل التكرار في كل صفحة.
if (!function_exists('eg_icon')) {
    function eg_icon($name, $class = 'icon') {
        $icons = [
            'zap' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 10-13h-7l0-7Z"/></svg>',
            'cart' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2.5 3h3l2.2 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7"/></svg>',
            'menu' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>',
            'close' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>',
            'user' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
            'logout' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 5v14"/></svg>',
            'shield' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 20 6v6c0 5-3.4 8.3-8 9-4.6-.7-8-4-8-9V6l8-3Z"/></svg>',
            'orders' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 6h12M8 12h12M8 18h12"/><path d="M4 6h.01M4 12h.01M4 18h.01"/></svg>',
            'search' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>',
            'filter' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M7 12h10M10 17h4"/></svg>',
            'bag' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 13H7L6 8Z"/><path d="M9 8a3 3 0 1 1 6 0"/></svg>',
            'package' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="M3 7v10l9 4 9-4V7"/><path d="M12 11v10"/></svg>',
            'truck' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
            'home' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>',
            'check' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
            'alert' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 4.2 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/></svg>',
            'help' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.7 2.7 0 0 1 5 1.4c0 2-2.5 2.1-2.5 4"/><path d="M12 18h.01"/></svg>',
            'mail' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>',
            'phone' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.5v3a2 2 0 0 1-2.2 2 19 19 0 0 1-8.3-3 18.5 18.5 0 0 1-5.7-5.7 19 19 0 0 1-3-8.4A2 2 0 0 1 4.8 2h3a2 2 0 0 1 2 1.7l.4 2.8a2 2 0 0 1-.6 1.8L8.4 9.5a15 15 0 0 0 6.1 6.1l1.2-1.2a2 2 0 0 1 1.8-.6l2.8.4a2 2 0 0 1 1.7 2.3Z"/></svg>',
            'pin' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-5.3 7-12A7 7 0 1 0 5 9c0 6.7 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>',
            'image' => '<svg class="' . eg_e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v14H4z"/><circle cx="9" cy="10" r="2"/><path d="m4 17 4-4 3 3 4-5 5 6"/></svg>',
        ];

        return $icons[$name] ?? '<span class="' . eg_e($class) . '" aria-hidden="true"></span>';
    }
}

// Price helper / حساب السعر بعد الخصم إذا المنتج عليه discount.
if (!function_exists('eg_discounted_price')) {
    function eg_discounted_price($product) {
        $price = (float)($product['price'] ?? 0);
        $discount = (float)($product['discount_percent'] ?? 0);
        return $discount > 0 ? $price * (1 - $discount / 100) : $price;
    }
}

// Sorting helper / ترتيب المنتجات حسب created_date من الأحدث للأقدم.
if (!function_exists('eg_sort_newest')) {
    function eg_sort_newest(&$products) {
        usort($products, function ($a, $b) {
            return strcmp((string)($b['created_date'] ?? ''), (string)($a['created_date'] ?? ''));
        });
    }
}

// Product card component / كرت المنتج reusable: مستخدم في Home و Products.
if (!function_exists('eg_product_card')) {
    function eg_product_card($product) {
        $id = eg_e($product['id'] ?? '');
        $name = eg_e($product['name'] ?? '');
        $category = eg_e($product['category'] ?? '');
        $brand = eg_e($product['brand'] ?? '');
        $image = eg_e($product['image_url'] ?? '');
        $stock = (int)($product['stock'] ?? 0);
        $discount = (float)($product['discount_percent'] ?? 0);
        $rating = (float)($product['rating'] ?? 0);
        $price = (float)($product['price'] ?? 0);
        $discounted = eg_discounted_price($product);
        $featured = !empty($product['is_featured']);
        $lowStock = $stock > 0 && $stock <= 5;
        $created = eg_e($product['created_date'] ?? '');
        $sizes = eg_e($product['sizes'] ?? '');
        $colors = eg_e($product['colors'] ?? '');

        ob_start();
        ?>
        <a href="product-detail.php?id=<?php echo $id; ?>" class="product-card" data-product-card data-id="<?php echo $id; ?>" data-category="<?php echo $category; ?>" data-name="<?php echo $name; ?>" data-brand="<?php echo $brand; ?>" data-price="<?php echo eg_e($discounted); ?>" data-rating="<?php echo eg_e($rating); ?>" data-created="<?php echo $created; ?>">
            <div class="product-media">
                <?php if ($image): ?>
                    <img src="<?php echo $image; ?>" alt="<?php echo $name; ?>" class="product-image">
                <?php else: ?>
                    <div class="product-placeholder">&#127947;&#65039;</div>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                    <div class="badge badge-discount">-<?php echo eg_e($discount); ?>%</div>
                <?php endif; ?>
                <?php if ($featured): ?>
                    <div class="badge badge-featured">Featured</div>
                <?php endif; ?>
                <?php if ($stock <= 0): ?>
                    <div class="stock-overlay"><span>Out of Stock</span></div>
                <?php endif; ?>
                <div class="product-card-overlay">
                    <div class="product-options-preview">
                        <?php if ($sizes): ?><p><span>SIZES</span> <?php echo $sizes; ?></p><?php endif; ?>
                        <?php if ($colors): ?><p><span>COLORS</span> <?php echo $colors; ?></p><?php endif; ?>
                    </div>
                    <?php if ($stock > 0): ?>
                        <button type="button" class="quick-add-button" data-add-product="<?php echo $id; ?>"><span class="text-icon">+</span> Quick Add</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="product-card-body">
                <div class="product-card-topline">
                    <span><?php echo $category; ?></span>
                    <?php if ($lowStock): ?><span class="low-stock">LOW STOCK</span><?php endif; ?>
                </div>
                <h3 title="<?php echo $name; ?>"><?php echo $name; ?></h3>
                <?php if ($brand): ?><p class="product-brand"><?php echo $brand; ?></p><?php endif; ?>
                <?php if ($rating > 0): ?>
                    <div class="stars" aria-label="<?php echo eg_e($rating); ?> out of 5">
                        <?php for ($s = 1; $s <= 5; $s++): ?><span class="<?php echo $s <= round($rating) ? 'is-on' : ''; ?>">&#9733;</span><?php endfor; ?>
                    </div>
                <?php endif; ?>
                <div class="product-card-footer">
                    <div class="product-price">
                        <span><?php echo number_format($discounted, 2); ?> SAR</span>
                        <?php if ($discount > 0): ?><del><?php echo number_format($price, 2); ?></del><?php endif; ?>
                    </div>
                    <button type="button" class="icon-button add-cart-button" data-add-product="<?php echo $id; ?>" <?php echo $stock <= 0 ? 'disabled' : ''; ?> aria-label="Add <?php echo $name; ?> to cart"><?php echo eg_icon('cart', 'icon icon-sm'); ?></button>
                </div>
            </div>
        </a>
        <?php
        return ob_get_clean();
    }
}

$pageTitle = $pageTitle ?? 'EliteGear';
$currentPage = $currentPage ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo eg_e($pageTitle); ?></title>
    <meta name="description" content="EliteGear sports equipment store">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        window.ELITE_PAGE = <?php echo json_encode($currentPage); ?>;
        window.ELITE_SESSION_USER = <?php echo json_encode($_SESSION['elitegear_user'] ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script defer src="assets/js/main.js"></script>
    <script defer src="assets/js/cart.js"></script>
    <script defer src="assets/js/admin.js"></script>
</head>
<body>
<div class="site-shell">
