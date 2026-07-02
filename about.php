<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'About Us';
$currentPage = 'about';
$additionalCSS = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    SITE_URL . 'css/About.css'
];
require_once __DIR__ . '/navbar.php';
?>
    <div class="container-fluid p-0">

       <!-- Hero Section -->
<section class="about-hero">
    <div class="container text-center text-white">
        <div class="hero-content">
            <h1 class="display-3 fw-bolder mb-4 text-uppercase" 
                style="text-shadow: 2px 2px 4px rgba(240, 244, 245, 0.3);">
                Empowering <span class="text-decoration-underline" style="color: var(--jirani-secondary);">Kakamega's</span> Local Economy
            </h1>
            <p class="h3 mb-5" style="letter-spacing: 0.05em;">
                Bridging <span class="fw-bold">Farmers</span>, 
                <span class="fw-bold">Artisans</span>, & 
                <span class="fw-bold">Communities</span> Through Digital Innovation
            </p>
            <div class="cta-badge bg-white text-dark px-4 py-2 rounded-pill d-inline-block shadow-sm">
                <span class="me-2">🌱</span>Growing Together Since 2024<span class="ms-2">🚜</span>
            </div>
        </div>
    </div>
</section>

    <!-- Our Story Section -->
<section class="py-6 bg-jirani-primary bg-opacity-10 position-relative overflow-hidden">
    <div class="container position-relative z-index-2">
        <div class="row align-items-center g-5">
            <div class="col-md-6">
                <div class="pe-lg-4">
                    <h2 class="display-5 mb-4 text-jirani-primary">
                        <span class="text-jirani-secondary">✳</span> Our Growing Journey
                    </h2>
                    
                    <div class="timeline-progress mb-4"></div>

                    <p class="lead mb-4">
                        Born in 2024 from Kakamega's rich soil, <span class="text-jirani-primary fw-bold">Jirani</span> 
                        blossomed to digitalize our vibrant markets. From humble roots connecting 
                        <span class="text-jirani-secondary">farmers-to-families</span>, we've cultivated:
                    </p>

                    <ul class="impact-stats list-unstyled">
                        <li class="d-flex align-items-center mb-3 p-3 bg-white rounded-3 shadow-sm">
                            <div class="stat-icon bg-jirani-primary text-white rounded-circle me-3">
                                <i class="fas fa-seedling fa-fw"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0 text-jirani-primary">500+</div>
                                <small class="text-jirani-gray">Local Farmers Empowered</small>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-3 p-3 bg-white rounded-3 shadow-sm">
                            <div class="stat-icon bg-jirani-primary text-white rounded-circle me-3">
                                <i class="fas fa-hands fa-fw"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0 text-jirani-primary">200+</div>
                                <small class="text-jirani-gray">Skilled Artisans Supported</small>
                            </div>
                        </li>
                        <li class="d-flex align-items-center p-3 bg-white rounded-3 shadow-sm">
                            <div class="stat-icon bg-jirani-primary text-white rounded-circle me-3">
                                <i class="fas fa-truck-fast fa-fw"></i>
                            </div>
                            <div>
                                <div class="h4 mb-0 text-jirani-primary">12</div>
                                <small class="text-jirani-gray">Sub-Counties with Same-Day Delivery</small>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="ratio ratio-16x9 border-4 border-jirani-primary rounded-4 overflow-hidden shadow-lg">
                    <video class="object-fit-cover" autoplay muted loop playsinline>
                        <source src="./Images/video sisal.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-overlay bg-jirani-secondary bg-opacity-10"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- How It Works Section -->
<section class="py-6 bg-jirani-white">
    <div class="container">
        <h2 class="text-center mb-5 display-5 fw-bold text-jirani-primary position-relative">
            <span class="text-jirani-secondary me-2"></span>How Jirani Works
            <div class="title-underline mx-auto mt-3"></div>
        </h2>
        
        <div class="row g-5">
            <!-- For Sellers -->
            <div class="col-lg-6">
                <div class="work-card card h-100 border-3 border-jirani-primary hover-elevate">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <i class="fas fa-store fa-2x text-jirani-secondary me-3"></i>
                            <h3 class="h4 mb-0 text-jirani-gray">For Sellers</h3>
                        </div>
                        
                        <div class="step-list">
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">1</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">Create Your Shop</h4>
                                    <p class="text-jirani-gray">Quick registration with National ID & M-Pesa</p>
                                    <div class="step-features">
                                        <span class="badge bg-jirani-secondary bg-opacity-10 text-jirani-secondary">5-minute setup</span>
                                        <span class="badge bg-jirani-secondary bg-opacity-10 text-jirani-secondary">Zero fees</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">2</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">List Products</h4>
                                    <p class="text-jirani-gray">Bilingual listings (Swahili/English) with photo uploads</p>
                                    <div class="step-features">
                                        <i class="fas fa-camera text-jirani-secondary me-2"></i>
                                        <i class="fas fa-language text-jirani-secondary"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">3</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">Manage Orders</h4>
                                    <p class="text-jirani-gray">Real-time notifications via:
                                        <span class="d-inline-block mt-2">
                                            <i class="fab fa-whatsapp text-jirani-secondary me-2"></i>
                                            <i class="fas fa-sms text-jirani-secondary"></i>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- For Buyers -->
            <div class="col-lg-6">
                <div class="work-card card h-100 border-3 border-jirani-primary hover-elevate">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <i class="fas fa-shopping-basket fa-2x text-jirani-secondary me-3"></i>
                            <h3 class="h4 mb-0 text-jirani-gray">For Buyers</h3>
                        </div>
                        
                        <div class="step-list">
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">1</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">Discover Local Goods</h4>
                                    <p class="text-jirani-gray">Explore fresh produce & authentic crafts</p>
                                    <div class="step-features">
                                        <span class="badge bg-jirani-secondary bg-opacity-10 text-jirani-secondary">100% Local</span>
                                        <span class="badge bg-jirani-secondary bg-opacity-10 text-jirani-secondary">Seasonal Picks</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">2</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">Secure Checkout</h4>
                                    <p class="text-jirani-gray">Multiple payment options:</p>
                                    <div class="payment-methods">
                                        <i class="fas fa-mobile-alt text-jirani-secondary me-3"></i>
                                        <img src="./Images/mpesa-logo.png" alt="M-Pesa" width="60" class="me-3">
                                        <span class="text-jirani-secondary">Cash on Delivery</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="step-item">
                                <div class="step-number bg-jirani-primary">3</div>
                                <div class="step-content">
                                    <h4 class="h6 text-jirani-primary mb-2">Fast Delivery</h4>
                                    <p class="text-jirani-gray">
                                        <span class="d-block mb-2">24-hour delivery in 12 sub-counties</span>
                                        <i class="fas fa-truck-fast text-jirani-secondary me-2"></i>
                                        <i class="fas fa-map-marker-alt text-jirani-secondary"></i>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Safety Promise Section -->
<section class="safety-promise py-6">
    <div class="container">
        <h2 class="text-center mb-5 display-5 fw-bold text-jirani-primary position-relative">
            <span class="text-jirani-secondary me-2">🛡️</span>Our Safety Promise
            <div class="title-underline mx-auto mt-3"></div>
        </h2>

        <div class="row g-4">
            <!-- Verified Sellers Card -->
            <div class="col-md-4">
                <div class="safety-card card h-100 border-2 border-jirani-primary hover-elevate">
                    <div class="card-body p-4 text-center">
                        <div class="icon-wrapper mb-4">
                            <div class="icon-bg bg-jirani-primary"></div>
                            <i class="fas fa-user-shield fa-3x text-jirani-white"></i>
                        </div>
                        <h3 class="h5 text-jirani-gray mb-3">Verified Sellers</h3>
                        <ul class="list-unstyled text-jirani-gray">
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-jirani-secondary me-2"></i>
                                KRA-certified profiles
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-jirani-secondary me-2"></i>
                                Physical location verified
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-jirani-secondary me-2"></i>
                                Monthly quality checks
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Secure Payments Card -->
            <div class="col-md-4">
                <div class="safety-card card h-100 border-2 border-jirani-primary hover-elevate">
                    <div class="card-body p-4 text-center">
                        <div class="icon-wrapper mb-4">
                            <div class="icon-bg bg-jirani-primary"></div>
                            <i class="fas fa-lock fa-3x text-jirani-white"></i>
                            <img src="./Images/mpesa-logo.png" alt="M-Pesa" class="payment-logo" width="80">
                        </div>
                        <h3 class="h5 text-jirani-gray mb-3">Secure Payments</h3>
                        <div class="security-features">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-shield-alt text-jirani-secondary me-3"></i>
                                <span>End-to-end encryption</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-bell text-jirani-secondary me-3"></i>
                                <span>Instant SMS confirmations</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-history text-jirani-secondary me-3"></i>
                                <span>7-day payment protection</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Community Support Card -->
            <div class="col-md-4">
                <div class="safety-card card h-100 border-2 border-jirani-primary hover-elevate">
                    <div class="card-body p-4 text-center">
                        <div class="icon-wrapper mb-4">
                            <div class="icon-bg bg-jirani-primary"></div>
                            <i class="fas fa-headset fa-3x text-jirani-white"></i>
                        </div>
                        <h3 class="h5 text-jirani-gray mb-3">Community Support</h3>
                        <div class="support-channels">
                            <div class="channel-item bg-jirani-primary bg-opacity-10 p-3 rounded-3 mb-3">
                                <i class="fab fa-whatsapp text-jirani-secondary me-2"></i>
                                <span>WhatsApp: +254 17 632 437</span>
                            </div>
                            <div class="channel-item bg-jirani-primary bg-opacity-10 p-3 rounded-3 mb-3">
                                <i class="fas fa-phone text-jirani-secondary me-2"></i>
                                <span>Call: 1523 (24/7)</span>
                            </div>
                            <div class="channel-item bg-jirani-primary bg-opacity-10 p-3 rounded-3">
                                <i class="fas fa-comments text-jirani-secondary me-2"></i>
                                <span>Swahili/English Support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     </section>

  <!-- Team Section -->
<section class="team-section py-6 bg-jirani-white">
    <div class="container">
        <h2 class="text-center mb-5 display-5 fw-bold text-jirani-primary position-relative">
            <span class="text-jirani-secondary me-2">👥</span>Meet Our Tribe
            <div class="title-underline mx-auto mt-3"></div>
        </h2>
        
        <div class="row g-4" id="teamMembers">
            <?php
            $team = [
                [
                    'name' => 'David Njuguna', 
                    'role' => 'Founder', 
                    'img'  => 'Images/team/founder.jpg',
                    'bio'  => 'Agricultural economist from University of Nairobi',
                    'deco' => '🌱'
                ],
                [
                    'name' => 'Amina Wangari', 
                    'role' => 'Tech Lead', 
                    'img'  => 'Images/team/tech_leader.jpeg',
                    'bio'  => 'Full-stack developer specializing in e-commerce',
                    'deco' => '💻'
                ],
                [
                    'name' => 'Peter Ouma', 
                    'role' => 'Community Manager', 
                    'img'  => 'Images/team/manager.jpg',
                    'bio'  => '10+ years in rural development projects',
                    'deco' => '🤝'
                ]
            ];

            foreach ($team as $member) {
                echo '<div class="col-md-4">
                        <div class="team-card card h-100 border-2 border-jirani-primary hover-elevate">
                            <div class="card-img-wrap overflow-hidden position-relative">
                                <img src="' . $member['img'] . '" class="card-img-top team-img" alt="' . $member['name'] . '">
                                <div class="deco-icon">' . $member['deco'] . '</div>
                            </div>
                            <div class="card-body text-center p-4">
                                <h3 class="h5 mb-2 text-jirani-gray">' . $member['name'] . '</h3>
                                <div class="role-badge bg-jirani-secondary text-white rounded-pill px-3 py-1 d-inline-block mb-3 small">
                                    ' . $member['role'] . '
                                </div>
                                <p class="text-jirani-gray mb-0">
                                    <i class="fas fa-leaf text-jirani-secondary me-2"></i>
                                    ' . $member['bio'] . '
                                </p>
                            </div>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
</section>

</div>

<!-- Custom JS -->
    <script>
        // Animate team cards on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('#teamMembers .card').forEach((card) => {
            card.style.opacity = 0;
            card.style.transform = 'translateY(50px)';
            card.style.transition = 'all 0.5s ease-out';
            observer.observe(card);
        });
    </script>

<?php require_once __DIR__ . '/footer.php'; ?>