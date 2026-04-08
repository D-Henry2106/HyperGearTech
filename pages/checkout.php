<?php
/**
 * ============================================
 * Checkout Page (UPGRADED v2)
 * With Variant Image + Phone Validation
 * ============================================
 */
$page_title = 'Checkout';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Fetch cart items with variation info
$cart = $conn->query("
    SELECT c.*, p.name, p.price, p.stock, p.image,
           pv.variant_type, pv.variant_value, pv.price_modifier, pv.price as var_price, pv.image as var_image
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    LEFT JOIN product_variations pv ON c.variation_id = pv.id
    WHERE c.user_id = $user_id
");

if ($cart->num_rows === 0) {
    header('Location: ' . BASE_URL . 'pages/cart.php');
    exit;
}

$items = [];
$total = 0;
while ($row = $cart->fetch_assoc()) {
    $row['final_price'] = ($row['var_price'] > 0) ? $row['var_price'] : ($row['price'] + ($row['price_modifier'] ?? 0));
    $row['display_image'] = !empty($row['var_image']) ? $row['var_image'] : $row['image'];
    $items[] = $row;
    $total += $row['final_price'] * $row['quantity'];
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $city    = $conn->real_escape_string(trim($_POST['city'] ?? ''));
    $phone   = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $notes   = $conn->real_escape_string(trim($_POST['notes'] ?? ''));

    if (empty($address) || empty($city) || empty($phone)) {
        $error = 'Please fill in all required shipping fields.';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } else {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, shipping_city, phone, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("idssss", $user_id, $total, $address, $city, $phone, $notes);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            foreach ($items as $item) {
                // Insert order detail
                $conn->query("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES ($order_id, {$item['product_id']}, {$item['quantity']}, {$item['final_price']})");
                $od_id = $conn->insert_id;
                
                // Insert order item options if variation exists
                if (!empty($item['variant_type'])) {
                    $vtype = $conn->real_escape_string($item['variant_type']);
                    $vvalue = $conn->real_escape_string($item['variant_value']);
                    $vimg = !empty($item['var_image']) ? "'" . $conn->real_escape_string($item['var_image']) . "'" : "NULL";
                    $conn->query("INSERT INTO order_item_options (order_detail_id, variant_type, variant_value, variant_image) VALUES ($od_id, '$vtype', '$vvalue', $vimg)");
                }
                
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
            
            $conn->query("DELETE FROM cart WHERE user_id = $user_id");
            
            header('Location: ' . BASE_URL . 'pages/order_success.php?id=' . $order_id);
            exit;
        } else {
            $error = 'Failed to place order. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-credit-card me-2"></i>Checkout</h2>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <!-- Shipping Info -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm glass-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="fas fa-truck me-2"></i>Shipping Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Shipping Address *</label>
                                    <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">City *</label>
                                    <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone * <small class="text-muted">(exactly 10 digits)</small></label>
                                    <input type="text" name="phone" class="form-control" required maxlength="10" minlength="10" pattern="\d{10}" title="Phone number must be exactly 10 digits" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Notes (optional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm glass-card">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                            <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($item['display_image']) ?>" width="40" height="40" class="rounded me-2" style="object-fit:cover;" onerror="this.src='https://via.placeholder.com/40/1a3a5c/ffffff?text=IMG'">
                                    <div>
                                        <small class="fw-bold"><?= htmlspecialchars($item['name']) ?></small>
                                        <small class="text-muted d-block">x<?= $item['quantity'] ?></small>
                                        <?php if (!empty($item['variant_type'])): ?>
                                            <small class="d-block">
                                                <span class="badge bg-info" style="font-size:0.65rem"><?= ucfirst($item['variant_type']) ?></span>
                                                <span class="text-primary fw-bold"><?= htmlspecialchars($item['variant_value']) ?></span>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span>$<?= number_format($item['final_price'] * $item['quantity'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between"><span>Subtotal</span><span>$<?= number_format($total, 2) ?></span></div>
                            <div class="d-flex justify-content-between"><span>Shipping</span><span class="text-success">Free</span></div>
                            <hr>
                            <div class="d-flex justify-content-between"><span class="h5 fw-bold">Total</span><span class="h5 fw-bold text-primary">$<?= number_format($total, 2) ?></span></div>
                            
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment" checked>
                                    <label class="form-check-label fw-bold"><i class="fas fa-money-bill me-1"></i> Cash on Delivery</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg mt-3 hg-btn-glow">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.querySelectorAll('input[name="phone"]').forEach(function(input) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        validatePhone(this);
    });
    input.addEventListener('blur', function() { validatePhone(this); });
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text');
        this.value = text.replace(/[^0-9]/g, '').substring(0, 10);
        validatePhone(this);
    });
});

function validatePhone(input) {
    var errorId = input.getAttribute('data-error-id') || 'phone-error';
    var errorEl = document.getElementById(errorId);
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.id = errorId;
        errorEl.className = 'text-danger small mt-1';
        input.parentNode.appendChild(errorEl);
        input.setAttribute('data-error-id', errorId);
    }
    if (input.value.length === 0) {
        errorEl.textContent = 'Phone number is required.';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else if (input.value.length < 10) {
        errorEl.textContent = 'Phone number must be exactly 10 digits. You entered ' + input.value.length + ' digit(s).';
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
    } else {
        errorEl.textContent = '';
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
    }
}

document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var phoneInput = form.querySelector('input[name="phone"]');
        if (phoneInput && phoneInput.value.replace(/[^0-9]/g, '').length !== 10) {
            e.preventDefault();
            validatePhone(phoneInput);
            phoneInput.focus();
            alert('Please enter exactly 10 digits for the phone number.');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
