<?php
/**
 * ============================================
 * Admin - Products Management (UPGRADED v2)
 * With Variant Images, Gallery, Advanced CRUD
 * ============================================
 */
$page_title = 'Manage Products';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

$upload_dir = __DIR__ . '/../assets/images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';
$error = '';

// Allowed image extensions
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Handle DELETE GALLERY IMAGE
if (isset($_GET['delete_gallery_img'])) {
    $gid = (int)$_GET['delete_gallery_img'];
    $gi = $conn->query("SELECT image_path FROM product_images WHERE id = $gid");
    if ($gi && $grow = $gi->fetch_assoc()) {
        $gfile = $upload_dir . $grow['image_path'];
        if (file_exists($gfile)) unlink($gfile);
    }
    $conn->query("DELETE FROM product_images WHERE id = $gid");
    $message = 'Gallery image deleted.';
    if (isset($_GET['edit'])) {
        header('Location: ' . BASE_URL . 'admin/products.php?edit=' . (int)$_GET['edit']);
        exit;
    }
}

// Handle DELETE VARIATION
if (isset($_GET['delete_variant'])) {
    $vid = (int)$_GET['delete_variant'];
    // Delete variant image file if exists
    $vi = $conn->query("SELECT image FROM product_variations WHERE id = $vid");
    if ($vi && $vrow = $vi->fetch_assoc()) {
        if (!empty($vrow['image'])) {
            $vfile = $upload_dir . $vrow['image'];
            if (file_exists($vfile)) unlink($vfile);
        }
    }
    $conn->query("DELETE FROM product_variations WHERE id = $vid");
    $message = 'Variation deleted.';
}

// Handle DELETE PRODUCT
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    // Delete main image
    $img_q = $conn->query("SELECT image FROM products WHERE id = $did");
    if ($img_q && $img_row = $img_q->fetch_assoc()) {
        if ($img_row['image'] !== 'default.png' && file_exists($upload_dir . $img_row['image'])) {
            unlink($upload_dir . $img_row['image']);
        }
    }
    // Delete variant images
    $var_imgs = $conn->query("SELECT image FROM product_variations WHERE product_id = $did AND image IS NOT NULL");
    while ($var_imgs && $vimg = $var_imgs->fetch_assoc()) {
        if (!empty($vimg['image']) && file_exists($upload_dir . $vimg['image'])) unlink($upload_dir . $vimg['image']);
    }
    // Delete gallery images
    $gal_imgs = $conn->query("SELECT image_path FROM product_images WHERE product_id = $did");
    while ($gal_imgs && $gimg = $gal_imgs->fetch_assoc()) {
        if (file_exists($upload_dir . $gimg['image_path'])) unlink($upload_dir . $gimg['image_path']);
    }
    $conn->query("DELETE FROM products WHERE id = $did");
    $message = 'Product deleted successfully.';
}

// Handle ADD / EDIT PRODUCT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $name   = $conn->real_escape_string(trim($_POST['name']));
    $cat_id = (int)$_POST['category_id'];
    $desc   = $conn->real_escape_string(trim($_POST['description']));
    $price  = (float)$_POST['price'];
    $old_p  = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : 'NULL';
    $stock  = (int)$_POST['stock'];
    $feat   = isset($_POST['featured']) ? 1 : 0;

    $image_name = 'default.png';
    if (isset($_POST['product_id']) && $_POST['product_id'] > 0) {
        $pid = (int)$_POST['product_id'];
        $cur = $conn->query("SELECT image FROM products WHERE id = $pid");
        if ($cur && $cur_row = $cur->fetch_assoc()) {
            $image_name = $cur_row['image'];
        }
    }

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['image_file']['tmp_name'];
        $file_name = $_FILES['image_file']['name'];
        $file_size = $_FILES['image_file']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $error = 'Invalid image format. Allowed: JPG, JPEG, PNG, GIF, WEBP.';
        } elseif ($file_size > 5 * 1024 * 1024) {
            $error = 'Image file too large. Maximum 5MB allowed.';
        } else {
            $new_filename = 'product_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
            $destination  = $upload_dir . $new_filename;
            if (move_uploaded_file($file_tmp, $destination)) {
                if (isset($pid) && $image_name !== 'default.png') {
                    $old_file = $upload_dir . $image_name;
                    if (file_exists($old_file)) unlink($old_file);
                }
                $image_name = $new_filename;
            } else {
                $error = 'Failed to upload image.';
            }
        }
    }

    if (empty($name) || $cat_id <= 0 || $price <= 0) {
        $error = 'Please fill required fields correctly.';
    }

    if (empty($error)) {
        $image_sql = $conn->real_escape_string($image_name);
        $old_price_sql = $old_p === 'NULL' ? 'NULL' : "'$old_p'";

        if (isset($_POST['product_id']) && $_POST['product_id'] > 0) {
            $pid = (int)$_POST['product_id'];
            $conn->query("UPDATE products SET name='$name', category_id=$cat_id, description='$desc', price=$price, old_price=$old_price_sql, stock=$stock, featured=$feat, image='$image_sql' WHERE id=$pid");
            $message = 'Product updated successfully.';
        } else {
            $conn->query("INSERT INTO products (name, category_id, description, price, old_price, stock, featured, image) VALUES ('$name', $cat_id, '$desc', $price, $old_price_sql, $stock, $feat, '$image_sql')");
            $pid = $conn->insert_id;
            $message = 'Product added successfully.';
        }

        // Save variations with images
        if (isset($_POST['var_type']) && is_array($_POST['var_type'])) {
            $conn->query("DELETE FROM product_variations WHERE product_id = $pid");
            foreach ($_POST['var_type'] as $i => $vtype) {
                $vtype = $conn->real_escape_string(trim($vtype));
                $vvalue = $conn->real_escape_string(trim($_POST['var_value'][$i] ?? ''));
                $vprice = (float)($_POST['var_price'][$i] ?? 0);
                $vprice_mod = (float)($_POST['var_price_mod'][$i] ?? 0);
                $vstock = max(0, (int)($_POST['var_stock'][$i] ?? 0));
                $vimage = '';
                
                // Handle variant image upload
                if (isset($_FILES['var_image']['name'][$i]) && $_FILES['var_image']['error'][$i] === UPLOAD_ERR_OK) {
                    $vf_ext = strtolower(pathinfo($_FILES['var_image']['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($vf_ext, $allowed_ext) && $_FILES['var_image']['size'][$i] <= 5 * 1024 * 1024) {
                        $vf_name = 'variant_' . time() . '_' . mt_rand(1000, 9999) . '_' . $i . '.' . $vf_ext;
                        if (move_uploaded_file($_FILES['var_image']['tmp_name'][$i], $upload_dir . $vf_name)) {
                            $vimage = $vf_name;
                        }
                    }
                }
                
                if (!empty($vtype) && !empty($vvalue)) {
                    $vimage_sql = !empty($vimage) ? "'" . $conn->real_escape_string($vimage) . "'" : "NULL";
                    $conn->query("INSERT INTO product_variations (product_id, variant_type, variant_value, price, price_modifier, stock, image) VALUES ($pid, '$vtype', '$vvalue', $vprice, $vprice_mod, $vstock, $vimage_sql)");
                }
            }
        }

        // Handle gallery images upload
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $gallery_count = count($_FILES['gallery_images']['name']);
            for ($g = 0; $g < $gallery_count; $g++) {
                if ($_FILES['gallery_images']['error'][$g] === UPLOAD_ERR_OK) {
                    $gf_ext = strtolower(pathinfo($_FILES['gallery_images']['name'][$g], PATHINFO_EXTENSION));
                    if (in_array($gf_ext, $allowed_ext) && $_FILES['gallery_images']['size'][$g] <= 5 * 1024 * 1024) {
                        $gf_name = 'gallery_' . time() . '_' . mt_rand(1000, 9999) . '_' . $g . '.' . $gf_ext;
                        if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$g], $upload_dir . $gf_name)) {
                            $gf_name_sql = $conn->real_escape_string($gf_name);
                            $conn->query("INSERT INTO product_images (product_id, image_path, sort_order) VALUES ($pid, '$gf_name_sql', $g)");
                        }
                    }
                }
            }
        }
    }
}

// Handle ADD SINGLE VARIATION (quick add with image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variation'])) {
    $pid = (int)$_POST['variation_product_id'];
    $vtype = $conn->real_escape_string(trim($_POST['new_var_type']));
    $vvalue = $conn->real_escape_string(trim($_POST['new_var_value']));
    $vprice = (float)($_POST['new_var_price'] ?? 0);
    $vprice_mod = (float)($_POST['new_var_price_mod'] ?? 0);
    $vstock = max(0, (int)($_POST['new_var_stock'] ?? 0));
    $vimage = 'NULL';
    
    // Handle variant image
    if (isset($_FILES['new_var_image']) && $_FILES['new_var_image']['error'] === UPLOAD_ERR_OK) {
        $vf_ext = strtolower(pathinfo($_FILES['new_var_image']['name'], PATHINFO_EXTENSION));
        if (in_array($vf_ext, $allowed_ext) && $_FILES['new_var_image']['size'] <= 5 * 1024 * 1024) {
            $vf_name = 'variant_' . time() . '_' . mt_rand(1000, 9999) . '.' . $vf_ext;
            if (move_uploaded_file($_FILES['new_var_image']['tmp_name'], $upload_dir . $vf_name)) {
                $vimage = "'" . $conn->real_escape_string($vf_name) . "'";
            }
        }
    }
    
    if ($pid > 0 && !empty($vtype) && !empty($vvalue)) {
        $conn->query("INSERT INTO product_variations (product_id, variant_type, variant_value, price, price_modifier, stock, image) VALUES ($pid, '$vtype', '$vvalue', $vprice, $vprice_mod, $vstock, $vimage)");
        $message = 'Variation added.';
    }
}

// Fetch editing product + its variations + gallery
$edit_product = null;
$edit_variations = [];
$edit_gallery = [];
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_product = $conn->query("SELECT * FROM products WHERE id = $eid")->fetch_assoc();
    if ($edit_product) {
        $var_q = $conn->query("SELECT * FROM product_variations WHERE product_id = $eid ORDER BY variant_type, variant_value");
        while ($v = $var_q->fetch_assoc()) $edit_variations[] = $v;
        $gal_q = $conn->query("SELECT * FROM product_images WHERE product_id = $eid ORDER BY sort_order");
        while ($gi = $gal_q->fetch_assoc()) $edit_gallery[] = $gi;
    }
}

// View variations for a product
$view_variations = [];
$view_product_name = '';
$vid_pid = 0;
if (isset($_GET['variants'])) {
    $vid_pid = (int)$_GET['variants'];
    $vp = $conn->query("SELECT name FROM products WHERE id = $vid_pid");
    if ($vp && $vp_row = $vp->fetch_assoc()) {
        $view_product_name = $vp_row['name'];
        $vv_q = $conn->query("SELECT * FROM product_variations WHERE product_id = $vid_pid ORDER BY variant_type, variant_value");
        while ($v = $vv_q->fetch_assoc()) $view_variations[] = $v;
    }
}

// Fetch all products and categories
$products = $conn->query("SELECT p.*, c.name as category_name, (SELECT COUNT(*) FROM product_variations WHERE product_id = p.id) as var_count FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$cat_list = [];
while ($c = $categories->fetch_assoc()) $cat_list[] = $c;

require_once __DIR__ . '/../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top hg-navbar glass-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-bolt text-warning me-2"></i>HyperGear <span class="text-warning">Admin</span></a>
        <div class="ms-auto"><a href="<?= BASE_URL ?>" class="btn btn-outline-light btn-sm me-2"><i class="fas fa-globe me-1"></i>View Site</a><a href="<?= BASE_URL ?>pages/logout.php" class="btn btn-outline-warning btn-sm"><i class="fas fa-sign-out-alt"></i></a></div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2 bg-dark min-vh-100 py-4 admin-sidebar">
            <nav class="nav flex-column">
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a class="nav-link text-white active" href="<?= BASE_URL ?>admin/products.php"><i class="fas fa-box me-2"></i>Products</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/categories.php"><i class="fas fa-tags me-2"></i>Categories</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a>
                <a class="nav-link text-white-50" href="<?= BASE_URL ?>admin/users.php"><i class="fas fa-users me-2"></i>Users</a>
            </nav>
        </div>

        <div class="col-lg-10 py-4 px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0"><i class="fas fa-box me-2 text-primary"></i>Products</h3>
                <button class="btn btn-primary hg-btn-glow" data-bs-toggle="collapse" data-bs-target="#productForm"><i class="fas fa-plus me-2"></i>Add Product</button>
            </div>

            <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $message ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <!-- View Variations Panel -->
            <?php if (!empty($view_variations) || isset($_GET['variants'])): ?>
            <div class="card border-0 shadow-sm glass-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fas fa-palette me-2 text-primary"></i>Variations for "<?= htmlspecialchars($view_product_name) ?>"</h5>
                        <a href="<?= BASE_URL ?>admin/products.php" class="btn btn-outline-secondary btn-sm">Close</a>
                    </div>
                    <?php if (!empty($view_variations)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-dark"><tr><th>#</th><th>Image</th><th>Type</th><th>Value</th><th>Price</th><th>Price +/-</th><th>Stock</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($view_variations as $vv): ?>
                                <tr>
                                    <td><?= $vv['id'] ?></td>
                                    <td>
                                        <?php if (!empty($vv['image'])): ?>
                                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($vv['image']) ?>" width="35" height="35" class="rounded" style="object-fit:cover;" onerror="this.src='https://via.placeholder.com/35/1a3a5c/ffffff?text=V'">
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= ucfirst($vv['variant_type']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($vv['variant_value']) ?></td>
                                    <td>$<?= number_format($vv['price'], 2) ?></td>
                                    <td><?= $vv['price_modifier'] > 0 ? '+$'.number_format($vv['price_modifier'],2) : '-' ?></td>
                                    <td><?= $vv['stock'] ?></td>
                                    <td><a href="?delete_variant=<?= $vv['id'] ?>&variants=<?= $vid_pid ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this variation?')"><i class="fas fa-trash"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No variations yet.</p>
                    <?php endif; ?>
                    <!-- Quick Add Variation with Image -->
                    <form method="POST" enctype="multipart/form-data" class="mt-3 p-3 bg-light rounded">
                        <h6 class="fw-bold mb-2"><i class="fas fa-plus-circle me-1"></i>Quick Add Variation</h6>
                        <input type="hidden" name="variation_product_id" value="<?= $vid_pid ?>">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label small">Type</label>
                                <select name="new_var_type" class="form-select form-select-sm" required>
                                    <option value="color">Color</option>
                                    <option value="storage">Storage</option>
                                    <option value="version">Version</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Value</label>
                                <input type="text" name="new_var_value" class="form-control form-control-sm" required placeholder="e.g. Black">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small">Price</label>
                                <input type="number" name="new_var_price" class="form-control form-control-sm" step="0.01" value="0" required>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small">+/-</label>
                                <input type="number" name="new_var_price_mod" class="form-control form-control-sm" step="0.01" value="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small">Stock</label>
                                <input type="number" name="new_var_stock" class="form-control form-control-sm" value="0" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Image</label>
                                <input type="file" name="new_var_image" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="add_variation" class="btn btn-primary btn-sm w-100"><i class="fas fa-plus me-1"></i>Add</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add/Edit Product Form -->
            <div class="collapse <?= $edit_product || $error ? 'show' : '' ?> mb-4" id="productForm">
                <div class="card border-0 shadow-sm glass-card">
                    <div class="card-body">
                        <h5 class="fw-bold"><?= $edit_product ? 'Edit' : 'Add New' ?> Product</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="save_product" value="1">
                            <?php if ($edit_product): ?><input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>"><?php endif; ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Category *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select...</option>
                                        <?php foreach ($cat_list as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($edit_product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Price ($) *</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required value="<?= $edit_product['price'] ?? '' ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"><i class="fas fa-image me-1"></i>Product Image</label>
                                    <input type="file" name="image_file" class="form-control" accept="image/*" id="imageInput">
                                    <small class="text-muted">Allowed: JPG, JPEG, PNG, GIF, WEBP (max 5MB)</small>
                                    <?php if ($edit_product && $edit_product['image']): ?>
                                        <div class="mt-2 d-flex align-items-center gap-2">
                                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($edit_product['image']) ?>" width="60" height="60" class="rounded border" style="object-fit:cover;" onerror="this.src='https://via.placeholder.com/60/1a3a5c/ffffff?text=IMG'">
                                            <span class="small text-muted">Current: <?= htmlspecialchars($edit_product['image']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Image Preview</label>
                                    <div id="imagePreview" class="border rounded d-flex align-items-center justify-content-center" style="height:120px;background:#f8f9fa;">
                                        <span class="text-muted small" id="previewText"><i class="fas fa-cloud-upload-alt me-1"></i>Select an image to preview</span>
                                        <img id="previewImg" src="" class="rounded" style="max-height:110px;max-width:100%;display:none;object-fit:contain;">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Old Price ($)</label>
                                    <input type="number" name="old_price" class="form-control" step="0.01" value="<?= $edit_product['old_price'] ?? '' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Stock *</label>
                                    <input type="number" name="stock" class="form-control" required value="<?= $edit_product['stock'] ?? 0 ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="featured" id="featured" <?= ($edit_product['featured'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="featured">Featured</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                                </div>

                                <!-- Gallery Images Upload -->
                                <div class="col-12">
                                    <hr>
                                    <h6 class="fw-bold mb-2"><i class="fas fa-images me-2 text-primary"></i>Product Gallery Images</h6>
                                    <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple id="galleryInput">
                                    <small class="text-muted">Upload multiple images for product gallery slider. Max 5MB each.</small>
                                    <div id="galleryPreview" class="d-flex gap-2 mt-2 flex-wrap"></div>
                                    <?php if (!empty($edit_gallery)): ?>
                                    <div class="mt-2">
                                        <small class="fw-bold text-muted">Existing Gallery:</small>
                                        <div class="d-flex gap-2 mt-1 flex-wrap">
                                            <?php foreach ($edit_gallery as $gi): ?>
                                            <div class="position-relative" style="width:80px;height:80px;">
                                                <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($gi['image_path']) ?>" class="rounded border" style="width:80px;height:80px;object-fit:cover;" onerror="this.src='https://via.placeholder.com/80/1a3a5c/ffffff?text=IMG'">
                                                <a href="?delete_gallery_img=<?= $gi['id'] ?>&edit=<?= $edit_product['id'] ?>" class="position-absolute top-0 end-0 badge bg-danger" onclick="return confirm('Delete this gallery image?')" style="cursor:pointer;font-size:0.6rem;"><i class="fas fa-times"></i></a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Variations Section -->
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0"><i class="fas fa-palette me-2 text-primary"></i>Product Variations</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariationRow()"><i class="fas fa-plus me-1"></i>Add Variation</button>
                                    </div>
                                    <div class="small text-muted mb-2">Each variant requires: type, value, price, stock. Image is optional.</div>
                                    <div id="variationsContainer">
                                        <?php if (!empty($edit_variations)): ?>
                                            <?php foreach ($edit_variations as $i => $ev): ?>
                                            <div class="row g-2 mb-2 variation-row align-items-end">
                                                <div class="col-md-2">
                                                    <select name="var_type[]" class="form-select form-select-sm">
                                                        <option value="color" <?= $ev['variant_type']==='color'?'selected':'' ?>>Color</option>
                                                        <option value="storage" <?= $ev['variant_type']==='storage'?'selected':'' ?>>Storage</option>
                                                        <option value="version" <?= $ev['variant_type']==='version'?'selected':'' ?>>Version</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2"><input type="text" name="var_value[]" class="form-control form-control-sm" value="<?= htmlspecialchars($ev['variant_value']) ?>" placeholder="Value"></div>
                                                <div class="col-md-2"><input type="number" name="var_price[]" class="form-control form-control-sm" step="0.01" value="<?= $ev['price'] ?>" placeholder="Price" required></div>
                                                <div class="col-md-1"><input type="number" name="var_price_mod[]" class="form-control form-control-sm" step="0.01" value="<?= $ev['price_modifier'] ?>" placeholder="+/-"></div>
                                                <div class="col-md-1"><input type="number" name="var_stock[]" class="form-control form-control-sm" value="<?= $ev['stock'] ?>" placeholder="Stock" min="0"></div>
                                                <div class="col-md-2">
                                                    <input type="file" name="var_image[]" class="form-control form-control-sm" accept="image/*">
                                                    <?php if (!empty($ev['image'])): ?>
                                                    <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($ev['image']) ?>" width="25" height="25" class="rounded mt-1" style="object-fit:cover;">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.variation-row').remove()"><i class="fas fa-times"></i></button></div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">Add color, storage, or version options with individual pricing and images.</small>
                                </div>

                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary hg-btn-glow"><i class="fas fa-save me-2"></i>Save Product</button>
                                    <?php if ($edit_product): ?><a href="<?= BASE_URL ?>admin/products.php" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Search & Sort -->
            <div class="row mb-3 g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="prodSearch" class="form-control" placeholder="Search by name or category..." onkeyup="filterProducts()">
                        <button class="btn btn-primary" type="button" onclick="filterProducts()"><i class="fas fa-search me-1"></i>Search</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="prodSort" class="form-select" onchange="sortProducts()">
                        <option value="id-desc">Newest First</option>
                        <option value="id-asc">Oldest First</option>
                        <option value="name-asc">Name A-Z</option>
                        <option value="name-desc">Name Z-A</option>
                        <option value="price-asc">Price Low-High</option>
                        <option value="price-desc">Price High-Low</option>
                        <option value="stock-asc">Stock Low-High</option>
                        <option value="stock-desc">Stock High-Low</option>
                    </select>
                </div>
                <div class="col-md-2"><span class="text-muted small mt-2 d-block" id="prodCount"></span></div>
            </div>

            <!-- Products Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="productsTable">
                            <thead class="table-dark">
                                <tr><th>#</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Variants</th><th>Featured</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="prodBody">
                                <?php while ($p = $products->fetch_assoc()): ?>
                                <tr data-id="<?= $p['id'] ?>" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-cat="<?= htmlspecialchars(strtolower($p['category_name'])) ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock'] ?>">
                                    <td><?= $p['id'] ?></td>
                                    <td><img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($p['image']) ?>" width="40" height="40" class="rounded" style="object-fit:cover;" onerror="this.src='https://via.placeholder.com/40/1a3a5c/ffffff?text=IMG'"></td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td>$<?= number_format($p['price'], 2) ?></td>
                                    <td><?= $p['stock'] ?></td>
                                    <td>
                                        <?php if ($p['var_count'] > 0): ?>
                                            <a href="?variants=<?= $p['id'] ?>" class="badge bg-info text-decoration-none"><?= $p['var_count'] ?> variant(s)</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $p['featured'] ? '<i class="fas fa-star text-warning"></i>' : '-' ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>admin/products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        <a href="<?= BASE_URL ?>admin/products.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product and its image?')"><i class="fas fa-trash"></i></a>
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
// Image preview
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('previewImg');
    const text = document.getElementById('previewText');
    if (file) {
        const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowed.includes(file.type)) { alert('Invalid file type.'); e.target.value = ''; return; }
        if (file.size > 5 * 1024 * 1024) { alert('File too large. Maximum 5MB.'); e.target.value = ''; return; }
        const reader = new FileReader();
        reader.onload = function(ev) { preview.src = ev.target.result; preview.style.display = 'block'; text.style.display = 'none'; };
        reader.readAsDataURL(file);
    } else { preview.style.display = 'none'; text.style.display = 'block'; }
});

// Gallery images preview
document.getElementById('galleryInput').addEventListener('change', function(e) {
    const container = document.getElementById('galleryPreview');
    container.innerHTML = '';
    const files = e.target.files;
    for (let i = 0; i < files.length; i++) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.className = 'rounded border';
            img.style.cssText = 'width:70px;height:70px;object-fit:cover;';
            container.appendChild(img);
        };
        reader.readAsDataURL(files[i]);
    }
});

// Add variation row dynamically
function addVariationRow() {
    const container = document.getElementById('variationsContainer');
    const html = `
    <div class="row g-2 mb-2 variation-row align-items-end">
        <div class="col-md-2">
            <select name="var_type[]" class="form-select form-select-sm">
                <option value="color">Color</option>
                <option value="storage">Storage</option>
                <option value="version">Version</option>
            </select>
        </div>
        <div class="col-md-2"><input type="text" name="var_value[]" class="form-control form-control-sm" placeholder="e.g. Black, 256GB"></div>
        <div class="col-md-2"><input type="number" name="var_price[]" class="form-control form-control-sm" step="0.01" value="0" placeholder="Price" required></div>
        <div class="col-md-1"><input type="number" name="var_price_mod[]" class="form-control form-control-sm" step="0.01" value="0" placeholder="+/-"></div>
        <div class="col-md-1"><input type="number" name="var_stock[]" class="form-control form-control-sm" value="0" placeholder="Stock" min="0"></div>
        <div class="col-md-2"><input type="file" name="var_image[]" class="form-control form-control-sm" accept="image/*"></div>
        <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.variation-row').remove()"><i class="fas fa-times"></i></button></div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

// Search & Sort
function filterProducts() {
    const search = document.getElementById('prodSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#prodBody tr');
    let visible = 0;
    rows.forEach(row => {
        const match = !search || (row.dataset.name || '').includes(search) || (row.dataset.cat || '').includes(search);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('prodCount').textContent = visible + ' product(s) found';
}

function sortProducts() {
    const val = document.getElementById('prodSort').value;
    const [field, dir] = val.split('-');
    const tbody = document.getElementById('prodBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
        let va, vb;
        if (field === 'id') { va = parseInt(a.dataset.id); vb = parseInt(b.dataset.id); }
        else if (field === 'name') { va = a.dataset.name; vb = b.dataset.name; }
        else if (field === 'price') { va = parseFloat(a.dataset.price); vb = parseFloat(b.dataset.price); }
        else if (field === 'stock') { va = parseInt(a.dataset.stock); vb = parseInt(b.dataset.stock); }
        if (typeof va === 'string') return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
        return dir === 'asc' ? va - vb : vb - va;
    });
    rows.forEach(r => tbody.appendChild(r));
}
filterProducts();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
