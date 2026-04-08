<?php

/**
 * Order Success Page
 */
$page_title = 'Order Placed';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center scroll-reveal">
                <div class="card border-0 shadow-lg p-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    <h2 class="fw-bold">Order Placed Successfully!</h2>
                    <p class="text-muted lead">Thank you for your purchase.</p>
                    <?php if ($order_id): ?>
                        <p class="fw-bold">Order #<?= $order_id ?></p>
                    <?php endif; ?>
                    <div class="mt-4 d-flex gap-3 justify-content-center">
                        <a href="<?= BASE_URL ?>pages/orders.php" class="btn btn-primary hg-btn-glow"><i class="fas fa-truck me-2"></i>Track Order</a>
                        <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-outline-primary"><i class="fas fa-store me-2"></i>Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>