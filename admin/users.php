<?php

/**
 * Admin - Users List with Delete CRUD
 */
$page_title = 'Manage Users';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$message = '';
$error = '';

// Handle delete (single or bulk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_users'])) {
    if (!empty($_POST['user_ids'])) {
        $ids = array_map('intval', $_POST['user_ids']);
        // Filter out admin users - only allow deleting customers
        $safe_ids = implode(',', $ids);
        $admin_check = $conn->query("SELECT id FROM users WHERE id IN ($safe_ids) AND role = 'admin'");
        $admin_ids = [];
        while ($row = $admin_check->fetch_assoc()) {
            $admin_ids[] = $row['id'];
        }
        $deletable = array_diff($ids, $admin_ids);

        if (empty($deletable)) {
            $error = 'Cannot delete admin users.';
        } else {
            $delete_ids = implode(',', $deletable);
            $conn->query("DELETE FROM users WHERE id IN ($delete_ids) AND role != 'admin'");
            $deleted_count = $conn->affected_rows;
            $message = "$deleted_count user(s) deleted successfully.";
            if (!empty($admin_ids)) {
                $message .= ' Admin accounts were skipped.';
            }
        }
    } else {
        $error = 'No users selected.';
    }
}

$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as order_count FROM users u ORDER BY u.created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top hg-navbar">
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
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a>
                <a class="nav-link text-white active" href="<?= BASE_URL ?>admin/users.php"><i class="fas fa-users me-2"></i>Users</a>
            </nav>
        </div>
        <div class="col-lg-10 py-4 px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Users</h3>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="row mb-3 g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="userSearch" class="form-control" placeholder="Search by name, email, or phone..." onkeyup="filterUsers()">
                        <button class="btn btn-primary" type="button" onclick="filterUsers()"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="roleFilter" class="form-select" onchange="filterUsers()">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="sortBy" class="form-select" onchange="sortUsers()">
                        <option value="id-desc">Newest First</option>
                        <option value="id-asc">Oldest First</option>
                        <option value="name-asc">Name A-Z</option>
                        <option value="name-desc">Name Z-A</option>
                        <option value="orders-desc">Most Orders</option>
                        <option value="orders-asc">Least Orders</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <span class="text-muted small mt-2 d-block" id="resultCount"></span>
                </div>
            </div>

            <form method="POST" id="usersForm">
                <input type="hidden" name="delete_users" value="1">
                <div class="mb-3">
                    <button type="button" class="btn btn-danger btn-sm" id="deleteSelectedBtn" disabled onclick="confirmDelete()">
                        <i class="fas fa-trash me-1"></i>Delete Selected
                    </button>
                    <span class="text-muted ms-2 small" id="selectedCount"></span>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="usersTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                                        <th class="sortable" onclick="sortByColumn('id')"># <i class="fas fa-sort ms-1"></i></th>
                                        <th class="sortable" onclick="sortByColumn('name')">Name <i class="fas fa-sort ms-1"></i></th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="sortable" onclick="sortByColumn('role')">Role <i class="fas fa-sort ms-1"></i></th>
                                        <th class="sortable" onclick="sortByColumn('orders')">Orders <i class="fas fa-sort ms-1"></i></th>
                                        <th class="sortable" onclick="sortByColumn('date')">Joined <i class="fas fa-sort ms-1"></i></th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="usersBody">
                                    <?php while ($u = $users->fetch_assoc()): ?>
                                        <tr data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars(strtolower($u['first_name'] . ' ' . $u['last_name'])) ?>" data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>" data-phone="<?= htmlspecialchars($u['phone'] ?? '') ?>" data-role="<?= $u['role'] ?>" data-orders="<?= $u['order_count'] ?>" data-date="<?= $u['created_at'] ?>">
                                            <td>
                                                <?php if ($u['role'] !== 'admin'): ?>
                                                    <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" class="user-checkbox" onchange="updateCount()">
                                                <?php else: ?>
                                                    <i class="fas fa-shield-alt text-muted" title="Admin - cannot delete"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $u['id'] ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                            <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= $u['role'] ?></span></td>
                                            <td><?= $u['order_count'] ?></td>
                                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                            <td>
                                                <?php if ($u['role'] !== 'admin'): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSingle(<?= $u['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small">Protected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .sortable {
        cursor: pointer;
        user-select: none;
    }

    .sortable:hover {
        color: #ffc107;
    }
</style>
<script>
    function toggleAll(el) {
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') cb.checked = el.checked;
        });
        updateCount();
    }

    function updateCount() {
        const checked = document.querySelectorAll('.user-checkbox:checked').length;
        document.getElementById('deleteSelectedBtn').disabled = checked === 0;
        document.getElementById('selectedCount').textContent = checked > 0 ? checked + ' user(s) selected' : '';
    }

    function confirmDelete() {
        const count = document.querySelectorAll('.user-checkbox:checked').length;
        if (count > 0 && confirm('Are you sure you want to delete ' + count + ' user(s)? This action cannot be undone.')) {
            document.getElementById('usersForm').submit();
        }
    }

    function deleteSingle(id) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = (parseInt(cb.value) === id));
            document.getElementById('usersForm').submit();
        }
    }

    function filterUsers() {
        const search = document.getElementById('userSearch').value.toLowerCase();
        const role = document.getElementById('roleFilter').value;
        const rows = document.querySelectorAll('#usersBody tr');
        let visible = 0;
        rows.forEach(row => {
            const name = row.dataset.name || '';
            const email = row.dataset.email || '';
            const phone = row.dataset.phone || '';
            const userRole = row.dataset.role || '';
            const matchSearch = !search || name.includes(search) || email.includes(search) || phone.includes(search);
            const matchRole = !role || userRole === role;
            row.style.display = (matchSearch && matchRole) ? '' : 'none';
            if (matchSearch && matchRole) visible++;
        });
        document.getElementById('resultCount').textContent = visible + ' user(s) found';
        document.getElementById('selectAll').checked = false;
        updateCount();
    }

    function sortUsers() {
        const val = document.getElementById('sortBy').value;
        const [field, dir] = val.split('-');
        const tbody = document.getElementById('usersBody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let va, vb;
            if (field === 'id') {
                va = parseInt(a.dataset.id);
                vb = parseInt(b.dataset.id);
            } else if (field === 'name') {
                va = a.dataset.name;
                vb = b.dataset.name;
            } else if (field === 'orders') {
                va = parseInt(a.dataset.orders);
                vb = parseInt(b.dataset.orders);
            }
            if (typeof va === 'string') return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            return dir === 'asc' ? va - vb : vb - va;
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function sortByColumn(col) {
        const select = document.getElementById('sortBy');
        const map = {
            id: 'id',
            name: 'name',
            orders: 'orders',
            role: 'name',
            date: 'id'
        };
        const key = map[col] || 'id';
        const current = select.value;
        if (current.startsWith(key + '-asc')) select.value = key + '-desc';
        else select.value = key + '-asc';
        sortUsers();
    }

    // Init count
    filterUsers();
</script>