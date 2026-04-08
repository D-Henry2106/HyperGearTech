<?php

/**
 * ============================================
 * Hyper Gear Tech - Home Page (index.php)
 * ============================================
 * Landing page with hero banner, featured products,
 * category preview, and promotional sections.
 */
$page_title = 'Home';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navigation.php';

// Fetch featured products
$featured = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 ORDER BY p.id DESC LIMIT 8");

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY id");
?>

<!-- Hero Banner Slider -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <div class="hero-slide slide-1">
                    <div class="container">
                        <div class="row align-items-center min-vh-60">
                            <div class="col-lg-7 text-white slide-content">
                                <h6 class="text-warning fw-bold mb-2 animate-fade-in">⚡ NEW ARRIVALS</h6>
                                <h1 class="display-3 fw-bold mb-3 animate-fade-in">Premium Gaming<br><span class="text-warning">Keyboards</span></h1>
                                <p class="lead mb-4 opacity-75 animate-fade-in">Mechanical precision meets stunning RGB. Elevate your gaming experience.</p>
                                <a href="<?= BASE_URL ?>pages/products.php?category=1" class="btn btn-warning btn-lg px-5 fw-bold hg-btn-glow animate-fade-in">
                                    Shop Now <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="carousel-item">
                <div class="hero-slide slide-2">
                    <div class="container">
                        <div class="row align-items-center min-vh-60">
                            <div class="col-lg-7 text-white slide-content">
                                <h6 class="text-warning fw-bold mb-2">🎧 BEST SELLERS</h6>
                                <h1 class="display-3 fw-bold mb-3">Immersive<br><span class="text-warning">Audio</span> Gear</h1>
                                <p class="lead mb-4 opacity-75">Crystal-clear sound and noise cancellation for total immersion.</p>
                                <a href="<?= BASE_URL ?>pages/products.php?category=4" class="btn btn-warning btn-lg px-5 fw-bold hg-btn-glow">
                                    Explore <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="carousel-item">
                <div class="hero-slide slide-3">
                    <div class="container">
                        <div class="row align-items-center min-vh-60">
                            <div class="col-lg-7 text-white slide-content">
                                <h6 class="text-warning fw-bold mb-2">🖥️ UP TO 30% OFF</h6>
                                <h1 class="display-3 fw-bold mb-3">Ultra-Wide<br><span class="text-warning">Monitors</span></h1>
                                <p class="lead mb-4 opacity-75">Stunning visuals with lightning-fast refresh rates.</p>
                                <a href="<?= BASE_URL ?>pages/products.php?category=3" class="btn btn-warning btn-lg px-5 fw-bold hg-btn-glow">
                                    View Deals <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <h6 class="text-primary fw-bold text-uppercase">Browse By</h6>
            <h2 class="fw-bold">Shop by Category</h2>
            <div class="hg-divider mx-auto"></div>
        </div>
        <div class="row g-4">
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg scroll-reveal">
                    <a href="<?= BASE_URL ?>pages/products.php?category=<?= $cat['id'] ?>" class="text-decoration-none">
                        <div class="category-card text-center p-4">
                            <div class="category-icon mb-3">
                                <i class="fas <?= htmlspecialchars($cat['icon']) ?> fa-2x"></i>
                            </div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($cat['name']) ?></h6>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5 bg-light-blue">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <h6 class="text-primary fw-bold text-uppercase">Top Picks</h6>
            <h2 class="fw-bold">Featured Products</h2>
            <div class="hg-divider mx-auto"></div>
        </div>
        <div class="row g-4">
            <?php while ($prod = $featured->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-3 scroll-reveal">
                    <div class="product-card card h-100 border-0 shadow-sm">
                        <?php if ($prod['old_price']): ?>
                            <span class="sale-badge">SALE</span>
                        <?php endif; ?>
                        <div class="product-img-wrapper">
                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($prod['image']) ?>"
                                class="card-img-top product-img"
                                alt="<?= htmlspecialchars($prod['name']) ?>"
                                onerror="this.src='https://via.placeholder.com/300x250/1a3a5c/ffffff?text=<?= urlencode($prod['name']) ?>'">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <small class="text-muted mb-1"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($prod['category_name']) ?></small>
                            <h6 class="card-title fw-bold"><?= htmlspecialchars($prod['name']) ?></h6>
                            <div class="mt-auto">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="h5 fw-bold text-primary mb-0">$<?= number_format($prod['price'], 2) ?></span>
                                    <?php if ($prod['old_price']): ?>
                                        <span class="text-muted text-decoration-line-through ms-2 small">$<?= number_format($prod['old_price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $prod['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="<?= BASE_URL ?>pages/cart.php?action=add&id=<?= $prod['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 add-to-cart-btn">
                                            <i class="fas fa-cart-plus me-1"></i> Add
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-5 scroll-reveal">
            <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-primary btn-lg px-5 hg-btn-glow">
                View All Products <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Promo Banner -->
<section class="promo-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-white scroll-reveal">
                <h2 class="fw-bold display-6">🔥 Special Offer - Up to 30% Off!</h2>
                <p class="lead opacity-75">Get the best deals on premium gaming gear. Limited time only.</p>
            </div>
            <div class="col-lg-4 text-lg-end scroll-reveal">
                <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-warning btn-lg px-5 fw-bold">
                    Shop Deals <i class="fas fa-bolt ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <h6 class="text-primary fw-bold text-uppercase">Why Choose Us</h6>
            <h2 class="fw-bold">The HyperGear Advantage</h2>
            <div class="hg-divider mx-auto"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-3 scroll-reveal">
                <div class="text-center p-4 feature-card">
                    <div class="feature-icon mb-3"><i class="fas fa-shipping-fast fa-2x"></i></div>
                    <h6 class="fw-bold">Free Shipping</h6>
                    <small class="text-muted">On orders over $50</small>
                </div>
            </div>
            <div class="col-md-3 scroll-reveal">
                <div class="text-center p-4 feature-card">
                    <div class="feature-icon mb-3"><i class="fas fa-shield-alt fa-2x"></i></div>
                    <h6 class="fw-bold">Secure Payments</h6>
                    <small class="text-muted">100% protected</small>
                </div>
            </div>
            <div class="col-md-3 scroll-reveal">
                <div class="text-center p-4 feature-card">
                    <div class="feature-icon mb-3"><i class="fas fa-undo fa-2x"></i></div>
                    <h6 class="fw-bold">Easy Returns</h6>
                    <small class="text-muted">30-day return policy</small>
                </div>
            </div>
            <div class="col-md-3 scroll-reveal">
                <div class="text-center p-4 feature-card">
                    <div class="feature-icon mb-3"><i class="fas fa-headset fa-2x"></i></div>
                    <h6 class="fw-bold">24/7 Support</h6>
                    <small class="text-muted">We're here to help</small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>