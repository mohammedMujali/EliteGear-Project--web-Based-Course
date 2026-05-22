<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 2 Web Design: Products page follows same EliteGear layout and style.
 * Task 3 Display Products: Retrieves products and displays image/cards.
 * Task 13 Forms Validation/JS Interaction: Search, category filter, and sort are handled by JS.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات الصفحة للـ title والـ active navbar link.
$pageTitle = 'EliteGear | Products';
$currentPage = 'products';
include __DIR__ . '/includes/header.php';

// Products source / مصدر المنتجات: MySQL database first, JSON fallback if DB is not running.
$products = eg_load_json('data/products.json');
eg_sort_newest($products);

// Active category / التصنيف الحالي: يقرأ query string مثل ?category=Cycling.
$activeCategory = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : 'All';
$categories = ['All', 'Football', 'Basketball', 'Tennis', 'Running', 'Swimming', 'Gym', 'Cycling', 'Boxing'];

// Sort options / خيارات الترتيب: JS يستخدم values عشان يعيد ترتيب product cards.
$sortOptions = [
    '-created_date' => 'Newest',
    'price' => 'Price: Low &#8594; High',
    '-price' => 'Price: High &#8594; Low',
    '-rating' => 'Rating',
];
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial products / بيانات المنتجات: يقرأها JS عشان filters والـ cart يعرفون المنتجات. -->
<script type="application/json" id="initial-products"><?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="catalog-page">
    <header class="page-banner">
        <div class="banner-inner">
            <div class="section-heading banner-heading">
                <span></span>
                <h1>All Products</h1>
            </div>
            <p class="catalog-count"><span data-product-count><?php echo count($products); ?></span> product(s) available</p>
        </div>
    </header>

    <section class="catalog-shell">
        <!-- Task 13 / Search + sort controls: inputs are accessible and handled in assets/js/main.js. -->
        <div class="catalog-controls">
            <label class="search-box">
                <?php echo eg_icon('search', 'input-icon'); ?>
                <input type="text" placeholder="Search products, brands..." data-product-search aria-label="Search products">
            </label>
            <label class="sort-box">
                <?php echo eg_icon('filter', 'icon icon-sm'); ?>
                <select data-product-sort aria-label="Sort products">
                    <?php foreach ($sortOptions as $value => $label): ?>
                        <option value="<?php echo eg_e($value); ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="category-filter" data-category-filter>
            <!-- Category filter / فلتر التصنيفات: كل button يغير المنتجات المعروضة بدون تغيير التصميم. -->
            <?php foreach ($categories as $category): ?>
                <button type="button" class="<?php echo $activeCategory === $category ? 'active' : ''; ?>" data-category="<?php echo eg_e($category); ?>" aria-pressed="<?php echo $activeCategory === $category ? 'true' : 'false'; ?>"><?php echo eg_e($category); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="product-grid" data-products-grid>
            <!-- Task 3 / Product cards: reusable PHP helper outputs image, price, stock, and add button. -->
            <?php foreach ($products as $product): ?>
                <?php echo eg_product_card($product); ?>
            <?php endforeach; ?>
        </div>

        <div class="empty-state is-hidden" data-products-empty>
            <p>No products found</p>
            <span>Try adjusting your search or category filter.</span>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
