<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 6 Checkout Support: Customer can review order after checkout.
 * Task 12 Past Purchases: Order tracking works with saved order history.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة تتبع الطلب.
$pageTitle = 'EliteGear | Order Tracking';
$currentPage = 'order-tracking';
include __DIR__ . '/includes/header.php';

// Load orders / تحميل الطلبات، و main.js يختار الطلب المطلوب من query id.
$orders = eg_load_json('data/orders.json');
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial orders / بيانات الطلبات: تستخدم لتفاصيل التتبع والـ status steps. -->
<script type="application/json" id="initial-orders"><?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="order-page" data-order-tracking>
    <!-- Loading state / يظهر مؤقتا إلى أن يبني JavaScript واجهة تتبع الطلب. -->
    <div class="order-loading">
        <div class="spinner"></div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
