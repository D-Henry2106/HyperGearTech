<?php

/**
 * Admin - Categories Management
 */
$page_title = 'Manage Categories';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$message = '';

// DELETE
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id = $did");
    $message = 'Category deleted.';
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $desc = $conn->real_escape_string(trim($_POST['description']));
    $icon = $conn->real_escape_string(trim($_POST['icon'] ?: 'fa-tag'));

    if (!empty($name)) {
        if (isset($_POST['cat_id']) && $_POST['cat_id'] > 0) {
            $cid = (int)$_POST['cat_id'];
            $conn->query("UPDATE categories SET name='$name', description='$desc', icon='$icon' WHERE id=$cid");
            $message = 'Category updated.';
        } else {
            $conn->query("INSERT INTO categories (name, description, icon) VALUES ('$name','$desc','$icon')");
            $message = 'Category added.';
        }
    }
}

$edit_cat = isset($_GET['edit']) ? $conn->query("SELECT * FROM categories WHERE id=" . (int)$_GET['edit'])->fetch_assoc() : null;
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) as product_count FROM categories c ORDER BY c.id");

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
                <a class="nav-link text-white active" href="<?= BASE_URL ?>admin/categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/users.php"><i class="fas fa-users me-2"></i>Users</a>
            </nav>
        </div>
        <div class="col-lg-10 py-4 px-4">
            <h3 class="fw-bold mb-4">Categories</h3>
            <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold"><?= $edit_cat ? 'Edit' : 'Add' ?> Category</h5>
                            <form method="POST">
                                <?php if ($edit_cat): ?><input type="hidden" name="cat_id" value="<?= $edit_cat['id'] ?>"><?php endif; ?>
                                <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>"></div>
                                <div class="mb-3"><label class="form-label">Icon (FA class)</label><input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($edit_cat['icon'] ?? 'fa-tag') ?>"></div>
                                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($edit_cat['description'] ?? '') ?></textarea></div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                                <?php if ($edit_cat): ?><a href="<?= BASE_URL ?>admin/categories.php" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <!-- Search -->
                    <div class="row mb-3 g-2">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" id="catSearch" class="form-control" placeholder="Search categories..." onkeyup="filterCats()">
                                <button class="btn btn-primary" type="button" onclick="filterCats()"><i class="fas fa-search me-1"></i>Search</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="catSort" class="form-select" onchange="sortCats()">
                                <option value="id-asc">ID Ascending</option>
                                <option value="id-desc">ID Descending</option>
                                <option value="name-asc">Name A-Z</option>
                                <option value="name-desc">Name Z-A</option>
                                <option value="count-desc">Most Products</option>
                                <option value="count-asc">Least Products</option>
                            </select>
                        </div>
                        <div class="col-md-2"><span class="text-muted small mt-2 d-block" id="catCount"></span></div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Icon</th>
                                        <th>Name</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="catBody">
                                    <?php while ($c = $categories->fetch_assoc()): ?>
                                        <tr data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars(strtolower($c['name'])) ?>" data-count="<?= $c['product_count'] ?>">
                                            <td><?= $c['id'] ?></td>
                                            <td><i class="fas <?= htmlspecialchars($c['icon']) ?>"></i></td>
                                            <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                            <td><?= $c['product_count'] ?></td>
                                            <td>
                                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete? Products in this category will also be deleted.')"><i class="fas fa-trash"></i></a>
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
</div>

<script>
    function filterCats() {
        const search = document.getElementById('catSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#catBody tr');
        let visible = 0;
        rows.forEach(row => {
            const match = !search || (row.dataset.name || '').includes(search);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.getElementById('catCount').textContent = visible + ' category(ies) found';
    }

    function sortCats() {
        const val = document.getElementById('catSort').value;
        const [field, dir] = val.split('-');
        const tbody = document.getElementById('catBody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            let va, vb;
            if (field === 'id') {
                va = parseInt(a.dataset.id);
                vb = parseInt(b.dataset.id);
            } else if (field === 'name') {
                va = a.dataset.name;
                vb = b.dataset.name;
            } else if (field === 'count') {
                va = parseInt(a.dataset.count);
                vb = parseInt(b.dataset.count);
            }
            if (typeof va === 'string') return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            return dir === 'asc' ? va - vb : vb - va;
        });
        rows.forEach(r => tbody.appendChild(r));
    }
    filterCats();
</script>

