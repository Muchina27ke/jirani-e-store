<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Contact Us';
$currentPage = 'contact';
$additionalCSS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'css/contactus.css',
    'contact_us.css'
];
require_once __DIR__ . '/navbar.php';
?>
        <!-- Contact Content -->
        <section class="contact-hero">
            <div class="container text-center">
                <h1 class="display-6 fw-bold mb-3">Contact Jirani</h1>
                <p class="lead">Have questions? We're here to help!</p>
            </div>
        </section>

        <main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="contact-card card p-4">
                        <!-- Display status messages -->
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
                                role="alert">
                                <?php if ($_GET['status'] === 'success'): ?>
                                    <i class="fas fa-check-circle me-2"></i>Your message has been sent successfully! We'll get back to you soon.
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle me-2"></i>Error: <?= htmlspecialchars($_GET['messages'] ?? 'Something went wrong') ?>
                                <?php endif; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="contactForm" action="<?php echo SITE_URL; ?>submit_contact.php" method="POST">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">Please enter your name</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">+254</span>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               pattern="^7[0-9]{8}$" placeholder="7XX XXX XXX" required
                                               title="Enter 9 digits starting with 7 (e.g., 712345678)">
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid phone number (9 digits starting with 7)</div>
                                    <small class="text-muted">Enter 9 digits starting with 7, without leading 0</small>
                                </div>

                                <div class="col-12">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">Please enter a valid email</div>
                                </div>
                                    

                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Select a subject</option>
                                        <option>Order Inquiry</option>
                                        <option>Product Support</option>
                                        <option>Partnership</option>
                                        <option>Other</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a subject</div>
                                </div>
                                                        <!-- Added Textarea Section -->
                                                        <div class="col-12">
                                        <label for="message" class="form-label">Your Message</label>
                                        <textarea 
                                            class="form-control" 
                                            id="message" 
                                            name="message" 
                                            rows="4"
                                            placeholder="Enter your message..."
                                            style="border-color: var(--jirani-primary);
                                                color: var(--jirani-gray);
                                                background-color: var(--jirani-white);
                                                transition: all 0.3s ease;"
                                        ></textarea>
                                        <div class="invalid-feedback" style="color: var(--jirani-secondary);">
                                            Please enter your message
                                        </div>
                                    </div>

                              
                                <!-- From Uiverse.io by Carlos-vargs --> 
                                <button type="submit" class="button">
                                    <span class="text">send message</span>
                                    <span class="icon"><svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" data-icon="paper-plane" width="20px" aria-hidden="true"><path d="M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.9 42.5 15.8L282 426l124.6 52.2c14.2 6 30.4-2.9 33-18.2l72-432C515 7.8 493.3-6.8 476 3.2z" fill="currentColor"></path></svg></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Company Info -->
                    <div class="text-center mt-5">
                        <h3 class="h5 mb-3">Our Office</h3>
                        <div class="d-flex flex-column gap-2 text-muted">
                            <div><i class="fa-solid fa-location-dot me-2"></i>Kakamega Town, Moi Avenue</div>
                            <div><i class="fa-solid fa-phone me-2"></i>+254 717 632 437</div>
                            <div><i class="fa-solid fa-envelope me-2"></i>support@jirani.co.ke</div>
                            <div class="mt-3">
                                <a href="#" class="btn btn-outline-jirani">
                                    <i class="fa-brands fa-whatsapp me-2"></i>Chat on WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Form Validation -->
    <script>
        (() => {
            'use strict'
            const form = document.getElementById('contactForm')
            
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })()
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const dropdownButton = document.querySelector('#subjectDropdown');
    const hiddenInput = document.querySelector('#subject');

    if (dropdownItems && dropdownButton && hiddenInput) {
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const value = this.getAttribute('data-value');
                dropdownButton.textContent = value;
                hiddenInput.value = value;
                dropdownButton.classList.remove('is-invalid');
            });
        });
    }

    // Add validation
    const form = document.querySelector('form');
    if (form && hiddenInput) {
        form.addEventListener('submit', function(e) {
            if (!hiddenInput.value && dropdownButton) {
                dropdownButton.classList.add('is-invalid');
                e.preventDefault();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>