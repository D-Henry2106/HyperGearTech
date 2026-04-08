<?php

/**
 * ============================================
 * Admin Dashboard (UPGRADED)
 * With Liquid Glass Stats + Search/Sort
 * ============================================
 */
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount),0) as c FROM orders WHERE status != 'cancelled'")->fetch_assoc()['c'];
$recent_orders = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top hg-navbar glass-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>admin/dashboard.php">
            <i class="fas fa-bolt text-warning me-2"></i>HyperGear <span class="text-warning">Admin</span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-globe me-1"></i>View Site</a>
            <a href="<?= BASE_URL ?>pages/logout.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 bg-dark min-vh-100 py-4 admin-sidebar">
            <nav class="nav flex-column">
                <a class="nav-link text-white active" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/products.php"><i class="fas fa-box me-2"></i>Products</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/users.php"><i class="fas fa-users me-2"></i>Users</a>
            </nav>
        </div>

        <div class="col-lg-10 py-4 px-4">
            <h3 class="fw-bold mb-4"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard Overview</h3>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-primary text-white admin-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="opacity-75">Total Revenue</h6>
                                    <h3 class="fw-bold">$<?= number_format($total_revenue, 2) ?></h3>
                                </div>
                                <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-success text-white admin-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="opacity-75">Orders</h6>
                                    <h3 class="fw-bold"><?= $total_orders ?></h3>
                                </div>
                                <i class="fas fa-shopping-bag fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-info text-white admin-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="opacity-75">Products</h6>
                                    <h3 class="fw-bold"><?= $total_products ?></h3>
                                </div>
                                <i class="fas fa-box fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-warning text-dark admin-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="opacity-75">Customers</h6>
                                    <h3 class="fw-bold"><?= $total_users ?></h3>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">All Orders</h5>
            </div>

            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="dashSearch" class="form-control" placeholder="Search by customer or email..." onkeyup="filterDashOrders()">
                        <button class="btn btn-primary" type="button" onclick="filterDashOrders()"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="dashStatusFilter" class="form-select" onchange="filterDashOrders()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="dashSort" class="form-select" onchange="sortDashOrders()">
                        <option value="id-desc">Newest First</option>
                        <option value="id-asc">Oldest First</option>
                        <option value="total-desc">Total High-Low</option>
                        <option value="total-asc">Total Low-High</option>
                        <option value="name-asc">Customer A-Z</option>
                        <option value="name-desc">Customer Z-A</option>
                    </select>
                </div>
                <div class="col-md-2"><span class="text-muted small mt-2 d-block" id="dashCount"></span></div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="dashBody">
                                <?php while ($o = $recent_orders->fetch_assoc()): ?>
                                    <tr data-id="<?= $o['id'] ?>" data-customer="<?= htmlspecialchars(strtolower($o['first_name'] . ' ' . $o['last_name'])) ?>" data-email="<?= htmlspecialchars(strtolower($o['email'])) ?>" data-total="<?= $o['total_amount'] ?>" data-status="<?= $o['status'] ?>" data-date="<?= $o['created_at'] ?>">
                                        <td>#<?= $o['id'] ?></td>
                                        <td><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($o['email']) ?></small></td>
                                        <td>$<?= number_format($o['total_amount'], 2) ?></td>
                                        <td><span class="badge bg-<?= ['pending' => 'warning', 'processing' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'][$o['status']] ?? 'secondary' ?>"><?= $o['status'] ?></span></td>
                                        <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
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
    function filterDashOrders() {
        const search = document.getElementById('dashSearch').value.toLowerCase();
        const status = document.getElementById('dashStatusFilter').value;
        const rows = document.querySelectorAll('#dashBody tr');
        let visible = 0;
        rows.forEach(row => {
            const matchSearch = !search || (row.dataset.customer || '').includes(search) || (row.dataset.email || '').includes(search);
            const matchStatus = !status || row.dataset.status === status;
            row.style.display = (matchSearch && matchStatus) ? '' : 'none';
            if (matchSearch && matchStatus) visible++;
        });
        document.getElementById('dashCount').textContent = visible + ' order(s) found';
    }

    function sortDashOrders() {
        const val = document.getElementById('dashSort').value;
        const [field, dir] = val.split('-');
        const tbody = document.getElementById('dashBody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let va, vb;
            if (field === 'id') {
                va = parseInt(a.dataset.id);
                vb = parseInt(b.dataset.id);
            } else if (field === 'total') {
                va = parseFloat(a.dataset.total);
                vb = parseFloat(b.dataset.total);
            } else if (field === 'name') {
                va = a.dataset.customer;
                vb = b.dataset.customer;
            }
            if (typeof va === 'string') return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            return dir === 'asc' ? va - vb : vb - va;
        });
        rows.forEach(r => tbody.appendChild(r));
    }
    filterDashOrders();
</script>