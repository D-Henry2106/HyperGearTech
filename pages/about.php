<?php

/**
 * About Page
 */
$page_title = 'About Us';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-info-circle me-2"></i>About Us</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 scroll-reveal">
                <h6 class="text-primary fw-bold text-uppercase">Our Story</h6>
                <h2 class="fw-bold mb-4">Welcome to <span class="text-primary">Hyper Gear Tech</span></h2>
                <p class="text-muted lead">We are passionate about bringing you the best electronic devices and computer accessories at competitive prices.</p>
                <p class="text-muted">Founded with a mission to make premium technology accessible to everyone, Hyper Gear Tech has grown into a trusted destination for keyboards, mice, monitors, headsets, and accessories. Our team of tech enthusiasts carefully curates every product to ensure quality and performance.</p>
                <div class="row g-3 mt-4">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <h3 class="fw-bold text-primary">500+</h3>
                            <small class="text-muted">Products</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <h3 class="fw-bold text-primary">10K+</h3>
                            <small class="text-muted">Happy Customers</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <h3 class="fw-bold text-primary">24/7</h3>
                            <small class="text-muted">Support</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded text-center">
                            <h3 class="fw-bold text-primary">30 Day</h3>
                            <small class="text-muted">Returns</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 scroll-reveal">
                <img src="https://via.placeholder.com/600x400/1a3a5c/ffffff?text=Hyper+Gear+Tech" class="img-fluid rounded shadow-lg" alt="About Hyper Gear Tech">
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>