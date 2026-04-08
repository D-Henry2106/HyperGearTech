<?php

/**
 * ============================================
 * HyperGear Tech - Navigation Bar (UPGRADED)
 * ============================================
 * Liquid Glass navbar with smart menu for admin/customer
 */
?>
<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top hg-navbar glass-navbar">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= BASE_URL ?>">
            <i class="fas fa-bolt me-2 text-warning"></i>
            <span class="brand-text">Hyper<span class="text-warning">Gear</span> Tech</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>"><i class="fas fa-home me-1"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>pages/products.php"><i class="fas fa-store me-1"></i> Products</a>
                </li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                        <!-- Customer-only links -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>pages/profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>pages/orders.php"><i class="fas fa-truck me-1"></i> Order Status</a>
                        </li>
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="<?= BASE_URL ?>pages/cart.php">
                                <i class="fas fa-shopping-cart me-1"></i> Cart
                                <?php if ($cart_count > 0): ?>
                                    <span class="badge bg-warning text-dark cart-badge"><?= $cart_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <!-- Admin Panel button pushed to the right -->
                        <li class="nav-item ms-lg-3">
                            <a class="nav-link btn btn-warning text-dark fw-bold px-3 py-1 rounded-pill" href="<?= BASE_URL ?>admin/dashboard.php">
                                <i class="fas fa-cogs me-1"></i> Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Guest links -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>pages/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>