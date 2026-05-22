<?php
/*
 * CIS311 Tasks Covered / المهام المغطاة:
 * Task 2 Web Design: Contact page uses same EliteGear layout.
 * Task 11 Display Address and Location Map: Google map iframe is shown on Contact page.
 * Task 13 Forms Validation: Contact form validation is handled by JavaScript.
 * Author / المنفذ: EliteGear Team.
 */

// Page metadata / معلومات صفحة التواصل.
$pageTitle = 'EliteGear | Contact';
$currentPage = 'contact';
include __DIR__ . '/includes/header.php';

// Contact messages / رسائل التواصل تحفظ في database أو JSON عن طريق api.php.
$messages = eg_load_json('data/contact-messages.json');
include __DIR__ . '/includes/navbar.php';
?>
<!-- Initial messages / بيانات الرسائل: JS يستخدمها عند إرسال Contact form. -->
<script type="application/json" id="initial-contact-messages"><?php echo json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="contact-page">
    <header class="page-banner narrow-banner">
        <div class="banner-inner">
            <div class="section-heading banner-heading">
                <span></span>
                <h1>Contact Us</h1>
            </div>
        </div>
    </header>

    <section class="contact-shell">
        <!-- Contact info cards / بطاقات معلومات التواصل: الموقع، الهاتف، والإيميل. -->
        <div class="contact-cards">
            <div class="contact-card"><?php echo eg_icon('pin', 'icon icon-lg'); ?><p>Location</p><span>Khobar, Saudi Arabia</span></div>
            <div class="contact-card"><?php echo eg_icon('phone', 'icon icon-lg'); ?><p>Phone</p><span>+966 13 805 1116</span></div>
            <div class="contact-card"><?php echo eg_icon('mail', 'icon icon-lg'); ?><p>Email</p><span>info@elitegear.sa</span></div>
        </div>

        <!-- CIS311 Task 11 / Google Maps Location: يعرض موقع المتجر على الخريطة داخل صفحة Contact. -->
        <div class="map-container" style="margin: 2rem 0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.18);">
            <!-- Google Map iframe / خريطة Google بدون API key، title و aria-label للـ accessibility. -->
            <iframe
                title="EliteGear Store Location - College of Computer Science and IT"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3572.4!2d50.1916045!3d26.3956525!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e49ef811304efab%3A0xe664343a49ebbf2b!2sCollege+of+Computer+Science+and+Information+Technology!5e0!3m2!1sen!2ssa!4v1715000000000!5m2!1sen!2ssa"
                width="100%"
                height="380"
                style="border:0; display:block;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                aria-label="Google Map showing College of Computer Science and Information Technology location">
            </iframe>
        </div>

        <!-- Task 13 / Contact form: JavaScript يتحقق من الاسم والإيميل والرسالة قبل الحفظ. -->
        <form class="contact-form" data-contact-form novalidate>
            <div class="two-cols">
                <input name="name" placeholder="Name *">
                <input name="email" type="email" placeholder="Email *">
            </div>
            <input name="subject" placeholder="Subject">
            <textarea name="message" rows="6" placeholder="Message *"></textarea>
            <div class="form-message error is-hidden" data-contact-error></div>
            <button type="submit" class="primary-button clip-shear"><span class="text-icon">&#9993;</span><span>Send Message</span></button>
        </form>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
