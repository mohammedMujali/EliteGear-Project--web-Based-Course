<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 13 Forms Validation: Register form is validated by JavaScript before saving.
 * Task 12 Returning Customers: Customer account data supports order history and cookies/session login.
 * Task 15 Accessibility: Labels and button aria-labels support keyboard/screen reader use.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة التسجيل.
$pageTitle = 'EliteGear | Register';
$currentPage = 'register';
include __DIR__ . '/includes/header.php';

// Load existing customers / تحميل العملاء الحاليين للتحقق من الإيميل وحفظ المستخدم الجديد.
$customers = eg_load_json('data/customers.json');
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial customers / بيانات العملاء: يستخدمها JS للتسجيل وتجنب تكرار الحسابات. -->
<script type="application/json" id="initial-customers"><?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="auth-page register-page">
    <div class="auth-watermark">REGISTER</div>
    <div class="auth-card">
        <div class="auth-head">
            <a href="index.php" class="brand auth-brand">
                <?php echo eg_icon('zap', 'brand-icon'); ?>
                <span>ELITE<span>GEAR</span></span>
            </a>
            <div class="velocity-line small-line"></div>
            <h1>Create Account</h1>
            <p>Join EliteGear today, it's free!</p>
        </div>

        <!-- Task 13 / Register validation: main.js checks name, email, password, and confirmation. -->
        <form class="stack-form" data-register-form novalidate>
            <div data-field-wrap="name">
                <label for="register-name">Full Name</label>
                <input id="register-name" name="name" type="text" placeholder="Ahmed Al-Rashid">
                <p class="field-error" data-error-for="name"></p>
            </div>
            <div data-field-wrap="email">
                <label for="register-email">Email Address</label>
                <input id="register-email" name="email" type="email" placeholder="you@example.com">
                <p class="field-error" data-error-for="email"></p>
            </div>
            <div data-field-wrap="password">
                <label for="register-password">Password</label>
                <div class="password-field">
                    <input id="register-password" name="password" type="password" placeholder="Min. 6 characters" data-password-input>
                    <button type="button" data-toggle-password aria-label="Toggle password">&#9680;</button>
                </div>
                <p class="field-error" data-error-for="password"></p>
            </div>
            <div data-field-wrap="confirm">
                <label for="register-confirm">Confirm Password</label>
                <input id="register-confirm" name="confirm" type="password" placeholder="Re-enter password">
                <p class="field-error" data-error-for="confirm"></p>
            </div>
            <button type="submit" class="primary-button full-button glow-green auth-submit"><?php echo eg_icon('user', 'icon icon-md'); ?> <span>Create Account</span></button>
        </form>

        <div class="auth-switch">Already have an account? <a href="admin.php">Sign in</a></div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
