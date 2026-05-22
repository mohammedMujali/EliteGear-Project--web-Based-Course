<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 8 Authenticate Managers: Admin login checks manager/admin credentials.
 * Task 9 Add New Product: Admin dashboard contains product form modal.
 * Task 10 Modify/Delete Product: Admin can edit/delete products and update database.
 * Task 7 Buy/Admin Orders: Admin can view customer orders and update order status.
 * Task 15 Accessibility: Buttons/inputs use labels and keyboard-friendly controls.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة الإدارة.
$pageTitle = 'EliteGear | Admin';
$currentPage = 'admin';
include __DIR__ . '/includes/header.php';

// Admin dashboard data / بيانات لوحة التحكم: products, orders, admins, customers.
$products = eg_load_json('data/products.json');
$orders = eg_load_json('data/orders.json');
$admins = eg_load_json('data/admins.json');
$customers = eg_load_json('data/customers.json');

// Task 8 / Server-side security:
// نتحقق من PHP session هل المستخدم admin أو لا.
// If not admin, dashboard HTML is not rendered, so the browser cannot reveal it.

// ── SERVER-SIDE SECURITY ────────────────────────────────────────────────────
// Check whether the current PHP session belongs to an authenticated admin.
// If not, $isAdminLoggedIn stays false and we will NOT render the dashboard
// HTML at all — the browser never receives it, so no client-side trick can
// reveal it.
$isAdminLoggedIn = isset($_SESSION['elitegear_user'])
    && ($_SESSION['elitegear_user']['role'] ?? '') === 'admin';
// ───────────────────────────────────────────────────────────────────────────

include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial admin data / بيانات مبدئية للـ admin.js عشان login/dashboard/orders/products. -->
<script type="application/json" id="initial-products"><?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="initial-orders"><?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="initial-admins"><?php echo json_encode($admins, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
<script type="application/json" id="initial-customers"><?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="admin-page" data-admin-page>
    <!-- Task 8 / Login screen: يسمح باختيار Customer أو Admin قبل إدخال البيانات. -->
    <section class="admin-login-screen" data-admin-login>
        <div class="admin-grid-bg"></div>
        <div class="admin-login-inner">
            <div class="admin-role-select" data-role-select>
                <div class="auth-head admin-auth-head">
                    <div class="brand auth-brand">
                        <?php echo eg_icon('zap', 'brand-icon'); ?>
                        <span>ELITE<span>GEAR</span></span>
                    </div>
                    <h1>Sign in</h1>
                    <p>Choose how you want to login</p>
                </div>
                <div class="role-grid">
                    <button type="button" data-role="customer">
                        <span class="role-icon"><?php echo eg_icon('bag', 'icon icon-xl'); ?></span>
                        <strong>Customer</strong>
                        <small>Shop & track orders</small>
                    </button>
                    <button type="button" data-role="admin">
                        <span class="role-icon admin-role-icon"><?php echo eg_icon('shield', 'icon icon-xl'); ?></span>
                        <strong>Admin</strong>
                        <small>Manage the store</small>
                    </button>
                </div>
                <div class="velocity-line muted-line"></div>
            </div>

            <div class="admin-login-form-wrap is-hidden" data-login-form-wrap>
                <div class="auth-head admin-auth-head">
                    <div class="brand auth-brand">
                        <?php echo eg_icon('zap', 'brand-icon'); ?>
                        <span>ELITE<span>GEAR</span></span>
                    </div>
                    <div class="login-role-icon" data-login-role-icon><?php echo eg_icon('shield', 'icon icon-lg'); ?></div>
                    <h1 data-login-title><span>ADMIN</span> LOGIN</h1>
                    <p data-login-subtitle>Login to continue</p>
                </div>
                <div class="login-panel">
                    <form class="stack-form" data-local-login novalidate>
                        <div>
                            <label for="local-login-email">Email Address</label>
                            <input id="local-login-email" name="email" type="text" placeholder="admin@elitegear.com" autocomplete="email">
                        </div>
                        <div>
                            <label for="local-login-password">Password</label>
                            <div class="password-field">
                                <input id="local-login-password" name="password" type="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autocomplete="current-password" data-password-input>
                                <button type="button" data-toggle-password aria-label="Toggle password">&#9680;</button>
                            </div>
                        </div>
                        <div class="form-message error is-hidden" data-login-error></div>
                        <button type="submit" class="primary-button full-button auth-submit"><?php echo eg_icon('logout', 'icon icon-md'); ?> <span>Login</span></button>
                    </form>
                    <button type="button" class="back-role-button" data-back-role>&#8592; Back to role selection</button>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isAdminLoggedIn): ?>
    <section class="admin-dashboard" data-admin-dashboard>
        <!-- Admin dashboard / لوحة التحكم: لا تظهر إلا إذا كان session role = admin. -->
        <header class="admin-topbar">
            <div class="admin-topbar-inner">
                <div class="admin-title">
                    <?php echo eg_icon('shield', 'icon icon-lg'); ?>
                    <span></span>
                    <div>
                        <h1>Admin Dashboard</h1>
                        <p>Welcome, <span data-admin-name></span></p>
                    </div>
                    <strong>Admin</strong>
                </div>
                <button type="button" class="admin-logout" data-logout><?php echo eg_icon('logout', 'icon icon-sm'); ?> Logout</button>
            </div>
        </header>

        <div class="admin-content">
            <div class="metric-grid">
                <div><p>Total Products</p><strong data-total-products>0</strong></div>
                <div><p>In Stock</p><strong data-in-stock>0</strong></div>
                <div><p>Orders</p><strong data-total-orders>0</strong></div>
            </div>

            <section class="admin-panel">
                <!-- Customer orders / طلبات العملاء: admin.js يعرض الطلبات ويحدث status. -->
                <div class="admin-section-head">
                    <div>
                        <h2>Customer Orders</h2>
                        <p>View customer orders and update their delivery status.</p>
                    </div>
                </div>
                <div class="admin-orders-list" data-admin-orders></div>
            </section>

            <section class="admin-panel">
                <!-- Task 9 + Task 10 / Product management: add, edit, delete products. -->
                <div class="admin-section-head">
                    <div>
                        <h2>Products</h2>
                        <p>Add, edit, and remove products from the shop.</p>
                    </div>
                    <button type="button" class="primary-button" data-add-product-admin><span class="text-icon">+</span> Add Product</button>
                </div>

                <div class="admin-controls">
                    <label class="search-box">
                        <?php echo eg_icon('search', 'input-icon'); ?>
                        <input placeholder="Search by name, category, brand..." data-admin-search>
                    </label>
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
                        <tbody data-admin-products></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="modal-layer is-hidden" data-product-modal>
            <!-- Product form modal / نافذة المنتج: تستخدم للإضافة والتعديل بنفس الفورم. -->
            <div class="product-modal">
                <div class="modal-header">
                    <h2 data-modal-title>New Product</h2>
                    <button type="button" data-close-product-modal><?php echo eg_icon('close', 'icon icon-md'); ?></button>
                </div>
                <form class="product-form" data-product-form novalidate>
                    <input type="hidden" name="id">
                    <div class="image-form-row">
                        <label>Product Image</label>
                        <div>
                            <div class="image-preview" data-image-preview><?php echo eg_icon('image', 'icon icon-xl'); ?></div>
                            <div class="image-inputs">
                                <label class="upload-button"><span class="text-icon">&#8679;</span> <span data-upload-label>Upload Image</span><input type="file" accept="image/*" data-image-file></label>
                                <p>or paste image URL below</p>
                                <input name="image_url" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div><label>Product Name *</label><input name="name" placeholder="Nike Football Boots"><p class="field-error" data-error-for="name"></p></div>
                        <div><label>Brand</label><input name="brand" placeholder="Nike"></div>
                        <div><label>Price (SAR) *</label><input name="price" type="number" step="0.01" placeholder="299.99"><p class="field-error" data-error-for="price"></p></div>
                        <div><label>Stock *</label><input name="stock" type="number" placeholder="50"><p class="field-error" data-error-for="stock"></p></div>
                        <div><label>Discount %</label><input name="discount_percent" type="number" placeholder="10"></div>
                        <div><label>Rating (1-5)</label><input name="rating" type="number" step="0.1" placeholder="4.5"></div>
                        <div><label>Category *</label><select name="category"><option>Football</option><option>Basketball</option><option>Tennis</option><option>Running</option><option>Swimming</option><option>Gym</option><option>Cycling</option><option>Boxing</option></select></div>
                        <div><label>Colors</label><input name="colors" placeholder="Red, Blue, Black"></div>
                        <div class="wide"><label>Sizes</label><input name="sizes" placeholder="S, M, L, XL"></div>
                        <div class="wide"><label>Description</label><textarea name="description" placeholder="Product description..." rows="3"></textarea></div>
                        <div class="checkbox-row"><input type="checkbox" id="is_featured" name="is_featured"><label for="is_featured">Featured product</label></div>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="primary-button"><?php echo eg_icon('check', 'icon icon-sm'); ?> <span data-save-label>Add Product</span></button>
                        <button type="button" class="secondary-button" data-close-product-modal>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php endif; // end $isAdminLoggedIn — dashboard HTML only sent to authenticated admins ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
