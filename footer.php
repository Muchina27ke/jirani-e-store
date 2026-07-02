<?php // Footer include — close </main> then render footer ?>
</main>

<!-- ══ FOOTER ═══════════════════════════════════════════════ -->
<footer class="j-footer">
    <div class="j-container">
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;padding-bottom:0;">

            <!-- Brand col -->
            <div>
                <div class="j-footer-brand">🌱 Jirani</div>
                <p class="j-footer-desc">Connecting local farmers, crafters, and vendors with nearby customers. Fresh, local, and always community-first.</p>
                <div class="j-social-links">
                    <a href="#" class="j-social-link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="j-social-link" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="j-social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="j-social-link" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Shop col -->
            <div>
                <h5>Shop</h5>
                <a href="<?php echo SITE_URL; ?>fruits.php"><i class="fas fa-apple-alt me-2" style="width:14px;opacity:.6;"></i>Fruits</a>
                <a href="<?php echo SITE_URL; ?>vegetables.php"><i class="fas fa-carrot me-2" style="width:14px;opacity:.6;"></i>Vegetables</a>
                <a href="<?php echo SITE_URL; ?>handcrafts.php"><i class="fas fa-hands me-2" style="width:14px;opacity:.6;"></i>Handcrafts</a>
                <a href="<?php echo SITE_URL; ?>search.php"><i class="fas fa-search me-2" style="width:14px;opacity:.6;"></i>All Products</a>
            </div>

            <!-- Account col -->
            <div>
                <h5>Account</h5>
                <a href="<?php echo SITE_URL; ?>profile.php"><i class="fas fa-user me-2" style="width:14px;opacity:.6;"></i>Profile</a>
                <a href="<?php echo SITE_URL; ?>orders.php"><i class="fas fa-box me-2" style="width:14px;opacity:.6;"></i>My Orders</a>
                <a href="<?php echo SITE_URL; ?>wishlist.php"><i class="fas fa-heart me-2" style="width:14px;opacity:.6;"></i>Wishlist</a>
                <a href="<?php echo SITE_URL; ?>seller/dashboard.php"><i class="fas fa-store me-2" style="width:14px;opacity:.6;"></i>Sell on Jirani</a>
            </div>

            <!-- Help col -->
            <div>
                <h5>Help</h5>
                <a href="<?php echo SITE_URL; ?>about.php"><i class="fas fa-info-circle me-2" style="width:14px;opacity:.6;"></i>About Us</a>
                <a href="<?php echo SITE_URL; ?>contact_us.php"><i class="fas fa-envelope me-2" style="width:14px;opacity:.6;"></i>Contact Us</a>
                <a href="#"><i class="fas fa-shield-alt me-2" style="width:14px;opacity:.6;"></i>Privacy Policy</a>
                <a href="#"><i class="fas fa-file-contract me-2" style="width:14px;opacity:.6;"></i>Terms of Use</a>
            </div>
        </div>

        <!-- Trust badges -->
        <div style="margin-top:40px;padding:20px 0;border-top:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:32px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.5);font-size:0.82rem;">
                <i class="fas fa-lock" style="color:#00a651;"></i> Secure M-Pesa Payments
            </div>
            <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.5);font-size:0.82rem;">
                <i class="fas fa-shield-alt" style="color:var(--j-accent);"></i> Escrow Protected
            </div>
            <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.5);font-size:0.82rem;">
                <i class="fas fa-map-marker-alt" style="color:#3b82f6;"></i> Location-Aware Delivery
            </div>
            <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.5);font-size:0.82rem;">
                <i class="fas fa-leaf" style="color:#22c55e;"></i> 100% Local Vendors
            </div>
        </div>
    </div>

    <div class="j-footer-bottom">
        <div class="j-container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <span>&copy; <?php echo date('Y'); ?> Jirani. All rights reserved.</span>
            <span>Made with <i class="fas fa-heart" style="color:#ef4444;"></i> by Muchina in Kenya</span>
        </div>
    </div>
</footer>

<?php if (isset($additionalJS)): foreach ($additionalJS as $js): ?>
<script src="<?php echo htmlspecialchars($js); ?>"></script>
<?php endforeach; endif; ?>

<?php if (isset($pageJS)): ?>
<script><?php echo $pageJS; ?></script>
<?php endif; ?>

</body>
</html>