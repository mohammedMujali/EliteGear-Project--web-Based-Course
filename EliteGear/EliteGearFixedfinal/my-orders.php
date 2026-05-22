<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 12 Past Purchases: Returning customers can view previous orders.
 * Task 6 Checkout Support: Orders created from checkout are displayed here.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة طلباتي.
$pageTitle = 'EliteGear | My Orders';
$currentPage = 'my-orders';
include __DIR__ . '/includes/header.php';

// Load orders / تحميل الطلبات، ثم JavaScript يفلترها حسب المستخدم الحالي.
$orders = eg_load_json('data/orders.json');
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial orders / بيانات الطلبات: main.js يعرض فقط الطلبات الخاصة بالمستخدم المسجل. -->
<script type="application/json" id="initial-orders"><?php echo json_encode($orders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="my-orders-page" data-my-orders>
    <!-- Loading state / حالة تحميل بسيطة إلى أن يرسم JS قائمة الطلبات. -->
    <div class="order-loading">
        <div class="spinner"></div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
