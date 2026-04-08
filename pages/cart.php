<?php
/**
 * ============================================
 * Shopping Cart Page (UPGRADED v2)
 * With Variant Image Display
 * ============================================
 */
$page_title = 'Shopping Cart';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'add' && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $qty = isset($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;
    $variation_id = !empty($_GET['variation_id']) ? (int)$_GET['variation_id'] : null;
    
    // Check if product+variation already in cart
    if ($variation_id) {
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variation_id = ?");
        $check->bind_param("iii", $user_id, $pid, $variation_id);
    } else {
        $check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variation_id IS NULL");
        $check->bind_param("ii", $user_id, $pid);
    }
    $check->execute();
    $existing = $check->get_result();
    
    if ($existing->num_rows > 0) {
        $row = $existing->fetch_assoc();
        $new_qty = $row['quantity'] + $qty;
        $conn->query("UPDATE cart SET quantity = $new_qty WHERE id = {$row['id']}");
    } else {
        if ($variation_id) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variation_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $user_id, $pid, $variation_id, $qty);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $pid, $qty);
        }
        $stmt->execute();
    }
    $message = 'Product added to cart!';
}

if ($action === 'remove' && isset($_GET['id'])) {
    $cid = (int)$_GET['id'];
    $conn->query("DELETE FROM cart WHERE id = $cid AND user_id = $user_id");
    $message = 'Item removed from cart.';
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_id => $qty) {
            $cart_id = (int)$cart_id;
            $qty = max(1, (int)$qty);
            $conn->query("UPDATE cart SET quantity = $qty WHERE id = $cart_id AND user_id = $user_id");
        }
        $message = 'Cart updated!';
    }
}

if ($action === 'clear') {
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    $message = 'Cart cleared.';
}

// Fetch cart items with variation info including variant image
$cart_items = $conn->query("
    SELECT c.id as cart_id, c.quantity, c.variation_id,
           p.id as product_id, p.name, p.price, p.image, p.stock,
           pv.variant_type, pv.variant_value, pv.price_modifier, pv.price as var_price, pv.image as var_image, pv.stock as var_stock
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    LEFT JOIN product_variations pv ON c.variation_id = pv.id
    WHERE c.user_id = $user_id
");

$cart_total = 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $message ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if ($cart_items->num_rows > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>pages/cart.php">
            <input type="hidden" name="action" value="update">
            <div class="card border-0 shadow-sm glass-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Option</th>
                                    <th>Price</th>
                                    <th style="width:120px">Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $cart_items->fetch_assoc()): 
                                    // Use variant price if available, else base + modifier
                                    $item_price = ($item['var_price'] > 0) ? $item['var_price'] : ($item['price'] + ($item['price_modifier'] ?? 0));
                                    $subtotal = $item_price * $item['quantity'];
                                    $cart_total += $subtotal;
                                    // Use variant image if available
                                    $display_image = !empty($item['var_image']) ? $item['var_image'] : $item['image'];
                                    $max_stock = ($item['var_stock'] > 0) ? $item['var_stock'] : $item['stock'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($display_image) ?>" width="60" height="60" class="rounded me-3" style="object-fit:cover;"
                                                 onerror="this.src='https://via.placeholder.com/60x60/1a3a5c/ffffff?text=IMG'">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($item['name']) ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['variant_type']): ?>
                                            <span class="badge bg-info"><?= ucfirst($item['variant_type']) ?></span>
                                            <span class="fw-bold"><?= htmlspecialchars($item['variant_value']) ?></span>
                                            <?php if ($item['price_modifier'] > 0): ?>
                                                <small class="text-muted">(+$<?= number_format($item['price_modifier'], 2) ?>)</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?= number_format($item_price, 2) ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?= $item['cart_id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $max_stock ?>" class="form-control form-control-sm">
                                    </td>
                                    <td class="fw-bold">$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>pages/cart.php?action=remove&id=<?= $item['cart_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="row mt-4">
                <div class="col-lg-4 ms-auto">
                    <div class="card border-0 shadow-sm glass-card">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span><span class="fw-bold">$<?= number_format($cart_total, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span><span class="text-success">Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="h5 fw-bold">Total</span><span class="h5 fw-bold text-primary">$<?= number_format($cart_total, 2) ?></span>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100 mb-2"><i class="fas fa-sync me-2"></i>Update Cart</button>
                            <a href="<?= BASE_URL ?>pages/checkout.php" class="btn btn-primary w-100 btn-lg hg-btn-glow"><i class="fas fa-credit-card me-2"></i>Checkout</a>
                            <a href="<?= BASE_URL ?>pages/cart.php?action=clear" class="btn btn-outline-danger w-100 mt-2 btn-sm" onclick="return confirm('Clear entire cart?')">Clear Cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>Your cart is empty</h4>
            <p class="text-muted">Start shopping to add items to your cart.</p>
            <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-primary btn-lg hg-btn-glow"><i class="fas fa-store me-2"></i>Browse Products</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
