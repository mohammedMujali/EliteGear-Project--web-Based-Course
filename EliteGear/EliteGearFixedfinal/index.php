<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة في الصفحة:
 * Task 2 Web Design: Home page layout with shared header/navbar/footer.
 * Task 3 Display Products: Shows latest product cards from database/JSON data.
 * Task 12 Past Purchases: Previous orders area is filled by cookies/session/localStorage JS.
 * Task 16 Efficiency: Uses includes and helper functions instead of repeating layout code.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات الصفحة: تستخدمها header و navbar لتحديد العنوان والصفحة الحالية.
$pageTitle = 'EliteGear | Home';
$currentPage = 'home';
include __DIR__ . '/includes/header.php';

// Load products and orders / تحميل المنتجات والطلبات من MySQL إذا متاح، أو JSON كـ fallback.
$products = eg_load_json('data/products.json');
$orders = eg_load_json('data/orders.json');

// Sort products newest first / ترتيب المنتجات من الأحدث للأقدم لعرض New Arrivals.
eg_sort_newest($products);
$newArrivals = array_slice($products, 0, 8);

// Category list / قائمة التصنيفات: كل تصنيف له رمز بسيط للعرض في الصفحة الرئيسية.
$categories = [
    'Football' => '&#9917;',
    'Basketball' => '&#127936;',
    'Tennis' => '&#127934;',
    'Running' => '&#127939;',
    'Swimming' => '&#127946;',
    'Gym' => '&#128170;',
    'Cycling' => '&#128692;',
    'Boxing' => '&#129354;',
];
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial data / بيانات مبدئية: JavaScript يقرأها عشان يعرض المنتجات والطلبات بدون API call إضافي. -->
<script type="application/json" id="initial-products"><?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="initial-orders"><?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="home-page">
    <section class="hero-section">
        <!-- Hero / الواجهة الرئيسية: أول جزء يشوفه المستخدم في الموقع. -->
        <div class="hero-marquee" aria-hidden="true">
            <div class="marquee-track">
                <?php for ($i = 0; $i < 8; $i++): ?><span>ELITE PERFORMANCE &middot; </span><?php endfor; ?>
            </div>
        </div>

        <div class="hero-content">
            <div class="hero-copy">
                <div class="eyebrow"><span></span><p>EliteGear Sports Store</p></div>
                <h1>Sports gear for everyone</h1>
                <p class="hero-subtitle">Find simple and affordable sports equipment for school, training, and everyday fitness.</p>
                <div class="hero-actions">
                    <a href="products.php" class="primary-button clip-shear">Shop Now</a>
                    <a href="contact.php" class="secondary-button">Contact Us</a>
                </div>
            </div>
        </div>
        <div class="velocity-line hero-line"></div>
    </section>

    <section class="stats-band">
        <div class="stats-grid">
            <div class="stat-item"><?php echo eg_icon('shield', 'icon icon-md'); ?><div><strong>50+</strong><span>Brands</span></div></div>
            <div class="stat-item"><?php echo eg_icon('truck', 'icon icon-md'); ?><div><strong>24h</strong><span>Delivery</span></div></div>
            <div class="stat-item"><?php echo eg_icon('check', 'icon icon-md'); ?><div><strong>100%</strong><span>Checked Items</span></div></div>
            <div class="stat-item"><?php echo eg_icon('help', 'icon icon-md'); ?><div><strong>24/7</strong><span>Help</span></div></div>
        </div>
    </section>

    <section class="page-section">
        <!-- Task 3 / عرض المنتجات حسب التصنيف: الروابط تنقل المستخدم لصفحة المنتجات مع فلتر category. -->
        <div class="section-heading">
            <span></span>
            <h2>Shop by Category</h2>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $category => $icon): ?>
                <a href="products.php?category=<?php echo urlencode($category); ?>" class="category-tile">
                    <span class="category-icon"><?php echo $icon; ?></span>
                    <span><?php echo eg_e($category); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="page-section previous-gains is-hidden" data-previous-gains>
        <!-- Task 12 / Past purchases: JS يملأ هذا القسم للعميل الراجع بناء على بيانات الطلبات. -->
        <div class="previous-card">
            <div class="section-heading previous-heading">
                <span></span>
                <div>
                    <h2>Previous Orders</h2>
                    <p data-previous-welcome>Welcome back</p>
                </div>
            </div>
            <div class="previous-orders" data-previous-orders></div>
        </div>
    </section>

    <section class="page-section arrivals-section">
        <!-- Task 3 / New arrivals: يعرض أحدث المنتجات باستخدام reusable product card helper. -->
        <div class="section-row">
            <div class="section-heading">
                <span></span>
                <h2>New Arrivals</h2>
            </div>
            <a href="products.php" class="view-all">View All <span class="text-icon">&#8250;</span></a>
        </div>

        <?php if (count($newArrivals) > 0): ?>
            <div class="product-grid">
                <?php foreach ($newArrivals as $product): ?>
                    <?php echo eg_product_card($product); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?php echo eg_icon('zap', 'empty-icon'); ?>
                <p>No products yet</p>
                <span>Admin can add products from the dashboard.</span>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
