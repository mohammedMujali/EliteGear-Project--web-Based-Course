<!--
Shared Footer / الفوتر المشترك:
Task 2 Web Design: Same footer and navigation style on all pages.
Task 11 Contact Info: Shows store contact/location summary.
Task 16 Efficiency: Included once instead of repeating footer HTML in every page.
Author / المنفذ: EliteGear Team.
-->
</main>
<footer class="site-footer">
    <div class="velocity-line"></div>
    <div class="footer-inner">
        <div class="footer-grid">
            <div>
                <!-- Brand summary / تعريف بسيط بالمشروع والبراند. -->
                <a href="index.php" class="brand footer-brand">
                    <?php echo eg_icon('zap', 'brand-icon'); ?>
                    <span>ELITE<span>GEAR</span></span>
                </a>
                <p class="footer-copy">A simple sports equipment store project made for web design practice.</p>
                <div class="social-row">
                    <a href="#" aria-label="Instagram">IG</a>
                    <a href="#" aria-label="Twitter">X</a>
                    <a href="#" aria-label="Facebook">f</a>
                </div>
            </div>

            <div>
                <!-- Footer navigation / روابط سريعة للصفحات الأساسية. -->
                <h4>Navigation</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>

            <div>
                <!-- Category links / روابط التصنيفات تنقل Products page مع category query. -->
                <h4>Categories</h4>
                <ul>
                    <li><a href="products.php?category=Football">Football</a></li>
                    <li><a href="products.php?category=Basketball">Basketball</a></li>
                    <li><a href="products.php?category=Tennis">Tennis</a></li>
                    <li><a href="products.php?category=Running">Running</a></li>
                    <li><a href="products.php?category=Swimming">Swimming</a></li>
                    <li><a href="products.php?category=Gym">Gym</a></li>
                </ul>
            </div>

            <div>
                <!-- Task 11 / Contact summary: address, phone, and email. -->
                <h4>Contact</h4>
                <ul class="contact-list">
                    <li><?php echo eg_icon('pin', 'icon icon-sm'); ?>Khobar, Saudi Arabia</li>
                    <li><?php echo eg_icon('phone', 'icon icon-sm'); ?>+966 13 805 1116</li>
                    <li><?php echo eg_icon('mail', 'icon icon-sm'); ?>info@elitegear.sa</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 EliteGear. All rights reserved.</p>
            <p>Student project</p>
        </div>
    </div>
</footer>
</div>
<!-- Toast stack / منطقة رسائل قصيرة يستخدمها JavaScript للتنبيهات validation/success. -->
<div class="toast-stack" data-toast-stack></div>
</body>
</html>
