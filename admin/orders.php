<?php
/**
 * Admin - Orders Management (UPGRADED v2)
 * With Variant Image Display in Order Details
 */
$page_title = 'Manage Orders';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$message = '';

if (isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE orders SET status='$status' WHERE id=$oid");
    $message = 'Order status updated.';
}

$orders = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");

// If viewing order details
$view_order = null;
$view_details = [];
if (isset($_GET['view'])) {
    $void = (int)$_GET['view'];
    $view_order = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email, u.phone as user_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $void")->fetch_assoc();
    if ($view_order) {
        $vd_q = $conn->query("
            SELECT od.*, p.name, p.image,
                   oio.variant_type, oio.variant_value, oio.variant_image
            FROM order_details od 
            JOIN products p ON od.product_id = p.id 
            LEFT JOIN order_item_options oio ON oio.order_detail_id = od.id
            WHERE od.order_id = $void
        ");
        while ($vd = $vd_q->fetch_assoc()) $view_details[] = $vd;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top hg-navbar glass-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-bolt text-warning me-2"></i>HyperGear <span class="text-warning">Admin</span></a>
        <div class="ms-auto"><a href="<?= BASE_URL ?>" class="btn btn-outline-light btn-sm me-2">View Site</a><a href="<?= BASE_URL ?>pages/logout.php" class="btn btn-outline-warning btn-sm">Logout</a></div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 bg-dark min-vh-100 py-4 admin-sidebar">
            <nav class="nav flex-column">
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/products.php"><i class="fas fa-box me-2"></i>Products</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                <a class="nav-link text-white active" href="<?= BASE_URL ?>admin/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/users.php"><i class="fas fa-users me-2"></i>Users</a>
            </nav>
        </div>
        <div class="col-lg-10 py-4 px-4">
            <h3 class="fw-bold mb-4"><i class="fas fa-shopping-bag me-2 text-primary"></i>Orders</h3>
            <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

            <!-- View Order Details Panel -->
            <?php if ($view_order): ?>
            <div class="card border-0 shadow-sm glass-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-eye me-2 text-primary"></i>Order #<?= $view_order['id'] ?> Details</h5>
                        <a href="<?= BASE_URL ?>admin/orders.php" class="btn btn-outline-secondary btn-sm">Close</a>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <small class="text-muted">Customer</small>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($view_order['first_name'] . ' ' . $view_order['last_name']) ?></p>
                            <small class="text-muted"><?= htmlspecialchars($view_order['email']) ?></small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Shipping</small>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($view_order['shipping_address'] . ', ' . $view_order['shipping_city']) ?></p>
                            <small class="text-muted">Phone: <?= htmlspecialchars($view_order['phone']) ?></small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Status</small>
                            <p class="mb-0">
                                <span class="badge bg-<?= ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'][$view_order['status']] ?? 'secondary' ?> fs-6"><?= ucfirst($view_order['status']) ?></span>
                            </p>
                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($view_order['created_at'])) ?></small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-dark"><tr><th>Product</th><th>Variation</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                            <tbody>
                                <?php foreach ($view_details as $vd): 
                                    $display_img = !empty($vd['variant_image']) ? $vd['variant_image'] : $vd['image'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($display_img) ?>" width="35" height="35" class="rounded me-2" style="object-fit:cover;" onerror="this.src='https://via.placeholder.com/35/1a3a5c/ffffff?text=IMG'">
                                            <span class="fw-bold"><?= htmlspecialchars($vd['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($vd['variant_type'])): ?>
                                            <span class="badge bg-info"><?= ucfirst($vd['variant_type']) ?></span>
                                            <span class="fw-bold"><?= htmlspecialchars($vd['variant_value']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $vd['quantity'] ?></td>
                                    <td>$<?= number_format($vd['price'], 2) ?></td>
                                    <td class="fw-bold">$<?= number_format($vd['price'] * $vd['quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="4" class="text-end fw-bold">Total:</td><td class="fw-bold text-primary fs-5">$<?= number_format($view_order['total_amount'], 2) ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search & Sort -->
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="orderSearch" class="form-control" placeholder="Search by customer or email..." onkeyup="filterOrders()">
                        <button class="btn btn-primary" type="button" onclick="filterOrders()"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select" onchange="filterOrders()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="orderSort" class="form-select" onchange="sortOrders()">
                        <option value="id-desc">Newest First</option>
                        <option value="id-asc">Oldest First</option>
                        <option value="total-desc">Total High-Low</option>
                        <option value="total-asc">Total Low-High</option>
                    </select>
                </div>
                <div class="col-md-2"><span class="text-muted small mt-2 d-block" id="orderCount"></span></div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark"><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>View</th><th>Update</th></tr></thead>
                            <tbody id="ordersBody">
                                <?php while ($o = $orders->fetch_assoc()): ?>
                                <tr data-id="<?= $o['id'] ?>" data-customer="<?= htmlspecialchars(strtolower($o['first_name'].' '.$o['last_name'])) ?>" data-email="<?= htmlspecialchars(strtolower($o['email'])) ?>" data-total="<?= $o['total_amount'] ?>" data-status="<?= $o['status'] ?>" data-date="<?= $o['created_at'] ?>">
                                    <td><?= $o['id'] ?></td>
                                    <td><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?><br><small class="text-muted"><?= $o['email'] ?></small></td>
                                    <td>$<?= number_format($o['total_amount'], 2) ?></td>
                                    <td><span class="badge bg-<?= ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'][$o['status']] ?? 'secondary' ?>"><?= $o['status'] ?></span></td>
                                    <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                                    <td><a href="?view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a></td>
                                    <td>
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="width:130px">
                                                <?php foreach(['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                                <option value="<?= $s ?>" <?= $o['status']==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary"><i class="fas fa-check"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterOrders() {
    const search = document.getElementById('orderSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#ordersBody tr');
    let visible = 0;
    rows.forEach(row => {
        const matchSearch = !search || (row.dataset.customer||'').includes(search) || (row.dataset.email||'').includes(search);
        const matchStatus = !status || row.dataset.status === status;
        row.style.display = (matchSearch && matchStatus) ? '' : 'none';
        if (matchSearch && matchStatus) visible++;
    });
    document.getElementById('orderCount').textContent = visible + ' order(s) found';
}

function sortOrders() {
    const val = document.getElementById('orderSort').value;
    const [field, dir] = val.split('-');
    const tbody = document.getElementById('ordersBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
        let va, vb;
        if (field === 'id') { va = parseInt(a.dataset.id); vb = parseInt(b.dataset.id); }
        else if (field === 'total') { va = parseFloat(a.dataset.total); vb = parseFloat(b.dataset.total); }
        return dir === 'asc' ? va - vb : vb - va;
    });
    rows.forEach(r => tbody.appendChild(r));
}
filterOrders();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
