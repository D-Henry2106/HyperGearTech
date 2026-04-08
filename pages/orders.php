<?php
/**
 * ============================================
 * Order Status / History Page (UPGRADED v2)
 * With Variant Image Display
 * ============================================
 */
$page_title = 'My Orders';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';

function statusBadge($status) {
    $colors = ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'];
    $color = $colors[$status] ?? 'secondary';
    return "<span class='badge bg-$color text-uppercase'>$status</span>";
}
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-truck me-2"></i>My Orders</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($orders->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($order = $orders->fetch_assoc()): ?>
            <div class="col-12 scroll-reveal">
                <div class="card border-0 shadow-sm glass-card">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3" style="border-bottom: 1px solid rgba(0,0,0,0.08);">
                        <div>
                            <strong>Order #<?= $order['id'] ?></strong>
                            <small class="text-muted ms-3"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></small>
                        </div>
                        <div>
                            <?= statusBadge($order['status']) ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $details = $conn->query("
                            SELECT od.*, p.name, p.image,
                                   oio.variant_type, oio.variant_value, oio.variant_image
                            FROM order_details od 
                            JOIN products p ON od.product_id = p.id 
                            LEFT JOIN order_item_options oio ON oio.order_detail_id = od.id
                            WHERE od.order_id = {$order['id']}
                        ");
                        while ($d = $details->fetch_assoc()):
                            $display_img = !empty($d['variant_image']) ? $d['variant_image'] : $d['image'];
                        ?>
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($display_img) ?>" width="40" height="40" class="rounded me-3" style="object-fit:cover;"
                                 onerror="this.src='https://via.placeholder.com/40/1a3a5c/ffffff?text=IMG'">
                            <div class="flex-grow-1">
                                <span class="fw-bold"><?= htmlspecialchars($d['name']) ?></span>
                                <?php if (!empty($d['variant_type'])): ?>
                                    <span class="ms-2 badge bg-info" style="font-size:0.65rem"><?= ucfirst($d['variant_type']) ?></span>
                                    <span class="text-primary fw-bold small"><?= htmlspecialchars($d['variant_value']) ?></span>
                                <?php endif; ?>
                                <small class="text-muted d-block">Qty: <?= $d['quantity'] ?> × $<?= number_format($d['price'], 2) ?></small>
                            </div>
                            <span class="fw-bold">$<?= number_format($d['price'] * $d['quantity'], 2) ?></span>
                        </div>
                        <?php endwhile; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($order['shipping_address'] . ', ' . $order['shipping_city']) ?></small>
                            </div>
                            <div class="h5 fw-bold text-primary mb-0">Total: $<?= number_format($order['total_amount'], 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h4>No orders yet</h4>
            <p class="text-muted">Start shopping to see your orders here.</p>
            <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-primary hg-btn-glow">Browse Products</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
