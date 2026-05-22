<?php
/*
 * Shared Navbar / شريط التنقل المشترك:
 * Task 2 Web Design: Same navigation appears on all pages.
 * Task 6 Checkout: Cart icon/count is visible from every page.
 * Task 8 Authenticate Managers: Admin link appears only for admin users.
 * Task 12 Cookies/Sessions: Login/logout UI updates based on current session user.
 * Author / المنفذ: EliteGear Team.
 */

// Active page / الصفحة الحالية: تستخدم لإضافة active class على الرابط المناسب.
$navPage = $currentPage ?? '';
?>
<nav class="site-nav">
    <div class="velocity-line"></div>
    <div class="nav-inner">
        <div class="nav-row">
            <a href="index.php" class="brand" aria-label="EliteGear home">
                <?php echo eg_icon('zap', 'brand-icon'); ?>
                <span>ELITE<span>GEAR</span></span>
            </a>

            <div class="nav-links">
                <a href="index.php" class="<?php echo $navPage === 'home' ? 'active' : ''; ?>">Home</a>
                <a href="products.php" class="<?php echo $navPage === 'products' ? 'active' : ''; ?>">Products</a>
                <a href="contact.php" class="<?php echo $navPage === 'contact' ? 'active' : ''; ?>">Contact</a>
                <a href="admin.php" class="admin-nav-link <?php echo $navPage === 'admin' ? 'active' : ''; ?>" data-admin-only><?php echo eg_icon('shield', 'icon icon-sm'); ?> Admin</a>
            </div>

            <div class="nav-actions">
                <!-- Task 6 / Cart shortcut: cart count is updated by JavaScript on every page. -->
                <a href="cart.php" class="cart-link" aria-label="Cart">
                    <?php echo eg_icon('cart', 'icon icon-lg'); ?>
                    <span class="cart-count" data-cart-count>0</span>
                </a>

                <div class="guest-actions" data-guest-auth>
                    <!-- Guest auth links / روابط الزائر: تظهر إذا ما فيه user logged in. -->
                    <a href="admin.php" class="nav-login">Login</a>
                    <a href="register.php" class="nav-register">Register</a>
                </div>

                <div class="user-actions" data-user-auth>
                    <!-- Logged-in user actions / تظهر للمستخدم المسجل: My Orders + logout. -->
                    <a href="my-orders.php" class="my-orders-link <?php echo $navPage === 'my-orders' ? 'active' : ''; ?>"><?php echo eg_icon('orders', 'icon icon-sm'); ?> My Orders</a>
                    <span class="user-label"><?php echo eg_icon('user', 'icon icon-sm'); ?><span data-user-name></span></span>
                    <button type="button" class="logout-button" data-logout aria-label="Logout"><?php echo eg_icon('logout', 'icon icon-sm'); ?></button>
                </div>

                <button type="button" class="mobile-menu-button" data-mobile-toggle aria-label="Toggle menu">
                    <!-- Mobile menu button / زر القائمة للجوال: keyboard accessible لأنه button. -->
                    <span data-menu-open><?php echo eg_icon('menu', 'icon icon-lg'); ?></span>
                    <span data-menu-close><?php echo eg_icon('close', 'icon icon-lg'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <div class="mobile-menu" data-mobile-menu>
        <!-- Mobile navigation / روابط الجوال: نفس الصفحات الأساسية لكن بتصميم responsive. -->
        <a href="index.php" class="<?php echo $navPage === 'home' ? 'active' : ''; ?>">Home</a>
        <a href="products.php" class="<?php echo $navPage === 'products' ? 'active' : ''; ?>">Products</a>
        <a href="contact.php" class="<?php echo $navPage === 'contact' ? 'active' : ''; ?>">Contact</a>
        <a href="admin.php" class="admin-nav-link" data-admin-only>Admin Dashboard</a>
        <a href="my-orders.php" data-mobile-user-link><?php echo eg_icon('orders', 'icon icon-sm'); ?> My Orders</a>
        <div class="mobile-auth" data-guest-auth>
            <a href="admin.php">Login</a>
            <a href="register.php" class="mobile-register">Register</a>
        </div>
        <div class="mobile-user" data-user-auth>
            <p data-user-name></p>
            <button type="button" data-logout>Logout</button>
        </div>
    </div>
</nav>
<main class="site-main">
