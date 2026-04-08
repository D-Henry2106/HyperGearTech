<?php

/**
 * ============================================
 * Hyper Gear Tech - Footer Include
 * ============================================
 * Site-wide footer with brand info, links, and scripts.
 */
?>
<!-- Footer -->
<footer class="hg-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-bolt text-warning me-2"></i>Hyper<span class="text-warning">Gear</span> Tech
                </h5>
                <p class="text-light opacity-75">Your one-stop shop for premium electronic devices and computer accessories. Quality products at competitive prices.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold text-warning mb-3">Quick Links</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= BASE_URL ?>">Home</a></li>
                    <li><a href="<?= BASE_URL ?>pages/products.php">Products</a></li>
                    <li><a href="<?= BASE_URL ?>pages/about.php">About Us</a></li>
                    <li><a href="<?= BASE_URL ?>pages/contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-warning mb-3">Categories</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= BASE_URL ?>pages/products.php?category=1">Keyboards</a></li>
                    <li><a href="<?= BASE_URL ?>pages/products.php?category=2">Mice</a></li>
                    <li><a href="<?= BASE_URL ?>pages/products.php?category=3">Monitors</a></li>
                    <li><a href="<?= BASE_URL ?>pages/products.php?category=4">Headsets</a></li>
                    <li><a href="<?= BASE_URL ?>pages/products.php?category=5">Accessories</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold text-warning mb-3">Contact Us</h6>
                <ul class="list-unstyled footer-links">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Tech Street, Silicon City</li>
                    <li><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</li>
                    <li><i class="fas fa-envelope me-2"></i> info@hypergear.com</li>
                    <li><i class="fas fa-clock me-2"></i> Mon-Sat: 9AM - 9PM</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom py-3">
        <div class="container text-center">
            <p class="mb-0 small opacity-75">
                &copy; <?= date('Y') ?> <strong>Hyper Gear Tech</strong>. All rights reserved.
                | Academic Final Project
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>

</html>