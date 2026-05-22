<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 4 Display Product Details: Shows selected product details by product id.
 * Task 5 Add to Cart: Quantity/stock checks happen in JS before adding to cart/session.
 * Task 13 Forms Validation: Quantity, size, and stock messages are validated by JavaScript.
 * Task 14 Help Window: Product Details page includes popup help window.
 * Task 15 Accessibility: Uses labels, aria-label, keyboard-friendly buttons, and modal controls.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات الصفحة.
$pageTitle = 'EliteGear | Product Detail';
$currentPage = 'products';
include __DIR__ . '/includes/header.php';

// Load all products / تحميل كل المنتجات عشان نبحث عن المنتج المطلوب من URL.
$products = eg_load_json('data/products.json');
$productId = $_GET['id'] ?? '';
$product = null;

// Find selected product / البحث عن المنتج المحدد بناء على id الموجود في الرابط.
foreach ($products as $candidate) {
    if (($candidate['id'] ?? '') === $productId) {
        $product = $candidate;
        break;
    }
}
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial data / البيانات المرسلة للـ JS: products للـ cart و product-detail-data للصفحة الحالية. -->
<script type="application/json" id="initial-products"><?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<?php if ($product): ?>
<script type="application/json" id="product-detail-data"><?php echo json_encode($product, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<?php endif; ?>

<?php if (!$product): ?>
    <div class="not-found-state">
        <p>Product not found</p>
        <a href="products.php" class="primary-button">Back to Products</a>
    </div>
<?php else: ?>
    <?php
    // Prepare display values / تجهيز قيم العرض مثل السعر بعد الخصم، الألوان، المقاسات، والمخزون.
    $discounted = eg_discounted_price($product);
    $colors = array_filter(array_map('trim', explode(',', $product['colors'] ?? '')));
    $sizes = array_filter(array_map('trim', explode(',', $product['sizes'] ?? '')));
    $stock = (int)($product['stock'] ?? 0);
    $discount = (float)($product['discount_percent'] ?? 0);
    $rating = (float)($product['rating'] ?? 0);
    $isLowStock = $stock > 0 && $stock <= 5;
    ?>
    <div class="detail-page">
        <div class="detail-shell">
            <button type="button" class="back-button" data-go-back><span class="text-icon">&#8249;</span> Back</button>
            <div class="detail-grid">
                <div class="detail-media">
                    <!-- Product image / صورة المنتج: Task 4 requires selected product details with picture. -->
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?php echo eg_e($product['image_url']); ?>" alt="<?php echo eg_e($product['name']); ?>">
                    <?php else: ?>
                        <div class="detail-placeholder">&#127947;&#65039;</div>
                    <?php endif; ?>
                    <?php if ($discount > 0): ?><div class="detail-discount">-<?php echo eg_e($discount); ?>% OFF</div><?php endif; ?>
                    <?php if (!empty($product['is_featured'])): ?><div class="detail-featured">Featured</div><?php endif; ?>
                </div>

                <div class="detail-info">
                    <!-- Product information / معلومات المنتج: الاسم، السعر، الوصف، التقييم، والماركة. -->
                    <div class="detail-meta">
                        <span><?php echo eg_e($product['category']); ?></span>
                        <?php if (!empty($product['brand'])): ?><small><?php echo eg_e($product['brand']); ?></small><?php endif; ?>
                    </div>
                    <h1><?php echo eg_e($product['name']); ?></h1>
                    <?php if ($rating > 0): ?>
                        <div class="detail-rating">
                            <div class="stars"><?php for ($s = 1; $s <= 5; $s++): ?><span class="<?php echo $s <= round($rating) ? 'is-on' : ''; ?>">&#9733;</span><?php endfor; ?></div>
                            <span><?php echo eg_e($rating); ?>/5</span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-price">
                        <strong><?php echo number_format($discounted, 2); ?></strong>
                        <span>SAR</span>
                        <?php if ($discount > 0): ?><del><?php echo number_format((float)$product['price'], 2); ?> SAR</del><?php endif; ?>
                    </div>
                    <?php if (!empty($product['description'])): ?><p class="detail-description"><?php echo eg_e($product['description']); ?></p><?php endif; ?>

                    <?php if (count($colors) > 0): ?>
                        <!-- Color options / خيارات الألوان: JS يخزن اللون المختار قبل الإضافة للسلة. -->
                        <div class="option-block">
                            <p>Available Colors <span data-selected-color></span></p>
                            <div class="option-row">
                                <?php foreach ($colors as $color): ?><button type="button" data-option-color="<?php echo eg_e($color); ?>"><?php echo eg_e($color); ?></button><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (count($sizes) > 0): ?>
                        <!-- Size options / خيارات المقاس: يمنع إضافة المنتج إذا المقاس مطلوب ولم يتم اختياره. -->
                        <div class="option-block">
                            <p>Sizes <span data-selected-size></span></p>
                            <div class="option-row">
                                <?php foreach ($sizes as $size): ?><button type="button" data-option-size="<?php echo eg_e($size); ?>"><?php echo eg_e($size); ?></button><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="stock-row">
                        <!-- Stock display / عرض المخزون: يوضح هل المنتج متوفر أو low stock أو out of stock. -->
                        <span class="<?php echo $stock <= 0 ? 'danger' : ($isLowStock ? 'warning' : ''); ?>"></span>
                        <strong class="<?php echo $stock <= 0 ? 'danger' : ($isLowStock ? 'warning' : ''); ?>"><?php echo $stock <= 0 ? 'Out of Stock' : ($isLowStock ? 'Low Stock, ' . $stock . ' left' : 'In Stock, ' . $stock . ' available'); ?></strong>
                    </div>

                    <div class="detail-purchase">
                        <!-- Task 5 / Add to Cart area: quantity input + Add button controlled by main.js. -->
                        <div class="qty-row">
                            <label for="detail-qty">QTY</label>
                            <input id="detail-qty" type="number" min="1" max="<?php echo eg_e($stock); ?>" placeholder="1" data-detail-qty aria-label="Quantity">
                        </div>
                        <button type="button" class="detail-add-button" data-detail-add <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                            <?php echo eg_icon('cart', 'icon icon-md'); ?><span>Add to Cart</span>
                        </button>
                    </div>
                    <div class="form-message error is-hidden" data-stock-error></div>
                    <!-- Help button / زر المساعدة: يفتح popup الخاص بـ CIS311 Task 14. -->
                    <button type="button" class="help-button" data-help-open aria-haspopup="dialog" aria-controls="product-help-modal"><?php echo eg_icon('help', 'icon icon-sm'); ?> Need Help?</button>
                </div>
            </div>
        </div>

        <!-- CIS311 Task 14 / Help popup: يشرح للعميل خطوات اختيار الكمية والإضافة للسلة. -->
        <div class="modal-layer is-hidden" id="product-help-modal" data-help-modal role="dialog" aria-modal="true" aria-labelledby="product-help-title" aria-hidden="true">
            <div class="help-modal">
                <div class="modal-header">
                    <!-- Popup title / عنوان النافذة المطلوبة في Task 14. -->
                    <div><?php echo eg_icon('help', 'icon icon-md'); ?><h2 id="product-help-title">Product Help</h2></div>
                    <!-- Close button / زر إغلاق واضح ومناسب للـ accessibility. -->
                    <button type="button" data-help-close aria-label="Close product help popup"><?php echo eg_icon('close', 'icon icon-md'); ?></button>
                </div>
                <div class="help-steps">
                    <!-- Help steps / خطوات المساعدة: كل نقطة تظهر برقم واضح للشرح. -->
                    <?php foreach (['Select the quantity you want.', 'Make sure the product is in stock.', 'Click Add to Cart to save the item in your cart.', 'Go to the cart page to modify quantity, delete items, or checkout.', 'If the requested quantity is higher than stock, the system will show an error message.'] as $index => $text): ?>
                        <div><span><?php echo $index + 1; ?></span><p><?php echo eg_e($text); ?></p></div>
                    <?php endforeach; ?>
                </div>
                <!-- Extra close action / زر إغلاق إضافي أسفل النافذة. -->
                <button type="button" class="primary-button full-button" data-help-close>Close Help</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
