<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 5 Add to Cart: Displays cart items stored by JS/PHP session sync.
 * Task 6 Checkout: Customer can modify, delete, clear cart, and proceed to checkout.
 * Task 7 Buy: Placing order saves it and updates products/order records through api.php.
 * Task 13 Forms Validation: Shipping and payment fields are validated in assets/js/cart.js.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة السلة.
$pageTitle = 'EliteGear | Cart';
$currentPage = 'cart';
include __DIR__ . '/includes/header.php';

// Load products and orders / تحميل المنتجات والطلبات للـ checkout و order creation.
$products = eg_load_json('data/products.json');
$orders = eg_load_json('data/orders.json');
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial data for JS / بيانات مبدئية للـ JavaScript عشان يعرض السلة ويحسب الإجمالي. -->
<script type="application/json" id="initial-products"><?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="initial-orders"><?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="cart-page" data-cart-page>
    <!-- Empty cart state / حالة السلة الفارغة: تظهر إذا ما فيه منتجات. -->
    <div class="cart-empty" data-cart-empty>
        <?php echo eg_icon('cart', 'empty-icon'); ?>
        <h2>Cart is empty</h2>
        <p>Start shopping to add items to your cart.</p>
        <a href="products.php" class="primary-button clip-shear">Shop Now</a>
    </div>

    <div class="cart-filled is-hidden" data-cart-filled>
        <!-- Filled cart state / حالة السلة الممتلئة: JS يخفي/يعرض هذا الجزء حسب محتوى السلة. -->
        <header class="page-banner">
            <div class="banner-inner cart-banner">
                <div class="section-heading banner-heading">
                    <span></span>
                    <h1>Shopping Cart</h1>
                </div>
                <button type="button" class="danger-link" data-clear-cart><span class="text-icon">&#128465;</span> Clear All</button>
            </div>
        </header>

        <section class="cart-shell">
            <div class="cart-grid">
                <div class="cart-items" data-cart-items></div>
                <aside class="cart-aside">
                    <!-- Task 6 / Order summary: يعرض عدد المنتجات والإجمالي قبل الدفع. -->
                    <div class="summary-card">
                        <h2>Order Summary</h2>
                        <div class="summary-lines">
                            <div><span data-summary-items>Subtotal (0 items)</span><span data-summary-subtotal>0.00 SAR</span></div>
                            <div><span>Shipping</span><span class="green">Free</span></div>
                            <div class="total"><span>Total</span><span data-summary-total>0.00 SAR</span></div>
                        </div>
                        <div data-checkout-login>
                            <p>Please login to checkout</p>
                            <a href="admin.php" class="primary-button full-button"><?php echo eg_icon('logout', 'icon icon-sm'); ?> Login to Checkout</a>
                        </div>
                        <button type="button" class="primary-button full-button" data-show-checkout><?php echo eg_icon('bag', 'icon icon-sm'); ?> Proceed to Checkout</button>
                    </div>

                    <div class="checkout-card is-hidden" data-checkout-card>
                        <!-- Task 6 + Task 7 / Checkout form: العميل يدخل الشحن والدفع ثم ينشئ order. -->
                        <h2>Shipping Details</h2>
                        <form class="checkout-form" data-checkout-form novalidate>
                            <div data-field-wrap="address">
                                <label for="checkout-address">Shipping Address *</label>
                                <input id="checkout-address" name="address" placeholder="e.g. Street Name, District">
                                <p class="field-error" data-error-for="address"></p>
                            </div>
                            <div data-field-wrap="city">
                                <label for="checkout-city">City *</label>
                                <input id="checkout-city" name="city" placeholder="e.g. Dammam">
                                <p class="field-error" data-error-for="city"></p>
                            </div>
                            <div data-field-wrap="phone">
                                <label for="checkout-phone">Phone Number *</label>
                                <input id="checkout-phone" name="phone" placeholder="+966 5X XXX XXXX">
                                <p class="field-error" data-error-for="phone"></p>
                            </div>
                            <div>
                                <label for="checkout-notes" class="muted-label">Notes (Optional)</label>
                                <textarea id="checkout-notes" name="notes" placeholder="Any special instructions..."></textarea>
                            </div>

                            <div class="payment-section">
                                <!-- Payment demo / دفع تجريبي: يتم حفظ آخر 4 أرقام فقط داخل الطلب. -->
                                <div class="payment-heading">
                                    <h3>Payment Details</h3>
                                    <span>Secure demo checkout</span>
                                </div>
                                <div class="payment-card-row">
                                    <div data-field-wrap="card_name">
                                        <label for="card-name">Cardholder Name *</label>
                                        <input id="card-name" name="card_name" placeholder="Ahmed Al-Rashid" autocomplete="cc-name">
                                        <p class="field-error" data-error-for="card_name"></p>
                                    </div>
                                    <div data-field-wrap="card_number">
                                        <label for="card-number">Card Number *</label>
                                        <input id="card-number" name="card_number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number" data-card-number>
                                        <p class="field-error" data-error-for="card_number"></p>
                                    </div>
                                    <div data-field-wrap="card_expiry">
                                        <label for="card-expiry">Expiry Date *</label>
                                        <input id="card-expiry" name="card_expiry" inputmode="numeric" maxlength="5" placeholder="MM/YY" autocomplete="cc-exp" data-card-expiry>
                                        <p class="field-error" data-error-for="card_expiry"></p>
                                    </div>
                                    <div data-field-wrap="card_cvv">
                                        <label for="card-cvv">CVV *</label>
                                        <input id="card-cvv" name="card_cvv" inputmode="numeric" maxlength="4" placeholder="123" autocomplete="cc-csc" data-card-cvv>
                                        <p class="field-error" data-error-for="card_cvv"></p>
                                    </div>
                                </div>
                                <p class="payment-note">Demo payment only. Card numbers are validated locally and only the last 4 digits are saved with the order.</p>
                            </div>
                            <button type="submit" class="primary-button full-button glow-green">&#10003; Place Order</button>
                        </form>
                    </div>
                </aside>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
