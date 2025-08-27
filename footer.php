<?php
require_once __DIR__ . '/config/config.php';

$db = getDbConnection();

require_once __DIR__ . '/includes/Navigation.php';

// Initialize footer
$footer = new Footer($db);
?>

<!-- Footer -->
<style>
    :root {
        --jirani-primary: #2A5C3D;
        --jirani-secondary: #FFA726;
        --jirani-white: #FFFFFF;
        --jirani-gray: #333333;
    }

    html,
    body {
        height: 100%;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    main,
    .container,
    .orders-container,
    .cart-container {
        flex: 1 0 auto;
    }

    footer.jirani-footer {
        color: var(--jirani-gray);
        background: linear-gradient(135deg, var(--jirani-white) 0%, #f4f7f6 100%) !important;
        border-top: 4px solid var(--jirani-primary);
        position: relative;
        bottom: 0;
        left: 0;
        width: 100%;
        z-index: 100;
        font-size: 1rem;
    }

    .jirani-footer a,
    .jirani-footer p,
    .jirani-footer span,
    .jirani-footer h5,
    .jirani-footer h6 {
        color: var(--jirani-primary) !important;
        text-shadow: none;
        transition: color 0.2s;
    }

    .jirani-footer a:hover {
        color: var(--jirani-secondary) !important;
        text-decoration: underline;
    }

    .jirani-footer .text-primary {
        color: var(--jirani-primary) !important;
    }

    .jirani-footer .footer-logo {
        height: 40px;
        margin-right: 0.5rem;
    }

    .jirani-footer .social-icons a {
        color: var(--jirani-primary) !important;
        font-size: 1.5rem;
        margin-right: 0.5rem;
        transition: color 0.2s;
    }

    .jirani-footer .social-icons a:hover {
        color: var(--jirani-secondary) !important;
    }

    .jirani-footer .footer-bottom {
        border-top: 1px solid #e0e0e0;
        padding-top: 1rem;
        font-size: 0.95rem;
        color: #888;
    }

    @media (max-width: 768px) {
        .jirani-footer {
            font-size: 0.98rem;
        }

        .jirani-footer .footer-logo {
            height: 32px;
        }
    }
</style>
<?php
// Render the Jirani-themed footer
ob_start();
?>
<footer class="jirani-footer py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="d-flex align-items-center mb-3">
                    <img src="<?php echo SITE_URL; ?>title_logo.jpg" alt="Jirani" class="footer-logo">
                    <h5 class="mb-0 text-primary">Jirani</h5>
                </div>
                <p class="text-muted">
                    Your trusted local marketplace connecting communities through commerce.<br>
                    Discover fresh products from verified vendors in your neighborhood.
                </p>
                <div class="d-flex social-icons gap-3">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="text-muted text-decoration-none">Home</a>
                    </li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>about.php"
                            class="text-muted text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>categories.php"
                            class="text-muted text-decoration-none">Categories</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>vendors.php"
                            class="text-muted text-decoration-none">Vendors</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>contact_us.php"
                            class="text-muted text-decoration-none">Contact</a></li>
                </ul>
            </div>
            <!-- Categories -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Categories</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>fruits.php"
                            class="text-muted text-decoration-none">Fruits</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>vegetables.php"
                            class="text-muted text-decoration-none">Vegetables</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>handcrafts.php"
                            class="text-muted text-decoration-none">Handcrafts</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>electronics.php"
                            class="text-muted text-decoration-none">Electronics</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>fashion.php"
                            class="text-muted text-decoration-none">Fashion</a></li>
                </ul>
            </div>
            <!-- Support -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>help.php"
                            class="text-muted text-decoration-none">Help Center</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>faq.php"
                            class="text-muted text-decoration-none">FAQ</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>shipping.php"
                            class="text-muted text-decoration-none">Shipping Info</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>returns.php"
                            class="text-muted text-decoration-none">Returns</a></li>
                    <li class="mb-2"><a href="<?php echo SITE_URL; ?>track-order.php"
                            class="text-muted text-decoration-none">Track Order</a></li>
                </ul>
            </div>
            <!-- Contact Info -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h6 class="text-primary mb-3">Contact Info</h6>
                <ul class="list-unstyled">
                    <li class="mb-2 text-muted"><i class="fas fa-phone me-2"></i>+254 700 000 000</li>
                    <li class="mb-2 text-muted"><i class="fas fa-envelope me-2"></i>support@jirani.com</li>
                    <li class="mb-2 text-muted"><i class="fas fa-map-marker-alt me-2"></i>Nairobi, Kenya</li>
                    <li class="mb-2 text-muted"><i class="fas fa-clock me-2"></i>24/7 Support</li>
                </ul>
            </div>
        </div>
        <hr class="my-4" style="border-color: var(--jirani-primary); opacity: 0.15;">
        <div class="row align-items-center footer-bottom">
            <div class="col-md-6">
                <p class="mb-0 text-muted">© <?php echo date('Y'); ?> Jirani. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="<?php echo SITE_URL; ?>privacy.php"
                            class="text-muted text-decoration-none">Privacy Policy</a></li>
                    <li class="list-inline-item"><a href="<?php echo SITE_URL; ?>terms.php"
                            class="text-muted text-decoration-none">Terms of Service</a></li>
                    <li class="list-inline-item"><a href="<?php echo SITE_URL; ?>cookies.php"
                            class="text-muted text-decoration-none">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<?php echo ob_get_clean(); ?>

<!-- Additional Footer Scripts -->
<script>
    // Newsletter subscription
    function subscribeNewsletter() {
        const email = document.getElementById('newsletterEmail').value;
        if (!email) {
            showToast('Please enter your email address', 'warning');
            return;
        }

        fetch('api/newsletter/subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Successfully subscribed to newsletter!', 'success');
                    document.getElementById('newsletterEmail').value = '';
                } else {
                    showToast(data.message || 'Failed to subscribe', 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
            });
    }

    // Live chat functionality
    function openLiveChat() {
        // Implement live chat integration here
        showToast('Live chat will be available soon!', 'info');
    }

    // Feedback form
    function submitFeedback() {
        const feedback = document.getElementById('feedbackText').value;
        if (!feedback.trim()) {
            showToast('Please enter your feedback', 'warning');
            return;
        }

        fetch('api/feedback/submit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ feedback: feedback })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Thank you for your feedback!', 'success');
                    document.getElementById('feedbackText').value = '';
                } else {
                    showToast(data.message || 'Failed to submit feedback', 'error');
                }
            })
            .catch(error => {
                showToast('An error occurred', 'error');
            });
    }

    // Cookie consent
    function acceptCookies() {
        localStorage.setItem('cookiesAccepted', 'true');
        document.getElementById('cookieConsent').style.display = 'none';
    }

    // Show cookie consent if not already accepted
    document.addEventListener('DOMContentLoaded', function () {
        if (!localStorage.getItem('cookiesAccepted')) {
            const cookieConsent = document.createElement('div');
            cookieConsent.id = 'cookieConsent';
            cookieConsent.className = 'position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3';
            cookieConsent.style.zIndex = '1060';
            cookieConsent.innerHTML = `
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <p class="mb-0">
                            <i class="fas fa-cookie-bite me-2"></i>
                            We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.
                            <a href="cookies.php" class="text-primary">Learn more</a>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <button class="btn btn-primary btn-sm me-2" onclick="acceptCookies()">Accept</button>
                        <button class="btn btn-outline-light btn-sm" onclick="document.getElementById('cookieConsent').style.display='none'">Decline</button>
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(cookieConsent);
        }
    });
</script>

<!-- Performance monitoring -->
<script>
    // Basic performance monitoring
    window.addEventListener('load', function () {
        const loadTime = performance.now();
        if (loadTime > 3000) {
            console.warn('Page load time is slow:', loadTime + 'ms');
        }
    });

    // Error tracking
    window.addEventListener('error', function (e) {
        console.error('JavaScript error:', e.error);
        // You can send this to your error tracking service
    });
</script>