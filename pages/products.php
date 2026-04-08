<?php
/**
 * ============================================
 * Products Page - Browse, Search, Filter, Sort
 * ============================================
 */
$page_title = 'Products';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = "WHERE 1=1";
if ($category_filter > 0) $where .= " AND p.category_id = $category_filter";
if ($search) $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";

// Sort
$order = match($sort) {
    'price_low'  => "p.price ASC",
    'price_high' => "p.price DESC",
    'name'       => "p.name ASC",
    default      => "p.id DESC"
};

// Count total results
$count_q = $conn->query("SELECT COUNT(*) as total FROM products p $where");
$total = $count_q->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Fetch products
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id $where ORDER BY $order LIMIT $per_page OFFSET $offset");

// Fetch categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!-- Page Header -->
<section class="page-header py-4">
    <div class="container">
        <h2 class="fw-bold text-white mb-0"><i class="fas fa-store me-2"></i>Products</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 mt-2">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-warning">Home</a></li>
                <li class="breadcrumb-item text-white-50 active">Products</li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-filter me-2"></i>Filters</h6>
                        
                        <!-- Search -->
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                            <?php if ($category_filter): ?>
                                <input type="hidden" name="category" value="<?= $category_filter ?>">
                            <?php endif; ?>
                        </form>

                        <!-- Category Filter -->
                        <h6 class="fw-bold mb-2 mt-3">Categories</h6>
                        <div class="list-group list-group-flush">
                            <a href="<?= BASE_URL ?>pages/products.php" class="list-group-item list-group-item-action border-0 <?= !$category_filter ? 'active' : '' ?>">
                                All Categories
                            </a>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <a href="<?= BASE_URL ?>pages/products.php?category=<?= $cat['id'] ?>" 
                               class="list-group-item list-group-item-action border-0 <?= $category_filter == $cat['id'] ? 'active' : '' ?>">
                                <i class="fas <?= $cat['icon'] ?> me-2"></i><?= htmlspecialchars($cat['name']) ?>
                            </a>
                            <?php endwhile; ?>
                        </div>

                        <!-- Sort -->
                        <h6 class="fw-bold mb-2 mt-4">Sort By</h6>
                        <select class="form-select" onchange="window.location.href=this.value">
                            <?php 
                            $sorts = ['newest'=>'Newest First','price_low'=>'Price: Low to High','price_high'=>'Price: High to Low','name'=>'Name A-Z'];
                            foreach($sorts as $k=>$v): 
                                $url = BASE_URL . "pages/products.php?" . http_build_query(array_merge($_GET, ['sort'=>$k]));
                            ?>
                            <option value="<?= $url ?>" <?= $sort==$k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0 text-muted">Showing <strong><?= $total ?></strong> product(s)</p>
                </div>

                <?php if ($products->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($prod = $products->fetch_assoc()): ?>
                    <div class="col-sm-6 col-md-4 scroll-reveal">
                        <div class="product-card card h-100 border-0 shadow-sm">
                            <?php if ($prod['old_price']): ?>
                                <span class="sale-badge">SALE</span>
                            <?php endif; ?>
                            <div class="product-img-wrapper">
                                <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($prod['image']) ?>" 
                                     class="card-img-top product-img" 
                                     alt="<?= htmlspecialchars($prod['name']) ?>"
                                     onerror="this.src='https://via.placeholder.com/300x250/1a3a5c/ffffff?text=<?= urlencode($prod['name']) ?>'">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <small class="text-muted mb-1"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($prod['category_name']) ?></small>
                                <h6 class="card-title fw-bold"><?= htmlspecialchars($prod['name']) ?></h6>
                                <p class="text-muted small flex-grow-1"><?= mb_strimwidth(htmlspecialchars($prod['description']), 0, 80, '...') ?></p>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="h5 fw-bold text-primary mb-0">$<?= number_format($prod['price'], 2) ?></span>
                                    <?php if ($prod['old_price']): ?>
                                        <span class="text-muted text-decoration-line-through ms-2 small">$<?= number_format($prod['old_price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="small mb-2 <?= $prod['stock'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <i class="fas fa-<?= $prod['stock'] > 0 ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                    <?= $prod['stock'] > 0 ? 'In Stock ('.$prod['stock'].')' : 'Out of Stock' ?>
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $prod['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1"><i class="fas fa-eye me-1"></i>View</a>
                                    <?php if (isset($_SESSION['user_id']) && $prod['stock'] > 0): ?>
                                    <a href="<?= BASE_URL ?>pages/cart.php?action=add&id=<?= $prod['id'] ?>" class="btn btn-primary btn-sm flex-grow-1 add-to-cart-btn"><i class="fas fa-cart-plus me-1"></i>Add</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>pages/products.php?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">«</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>pages/products.php?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>pages/products.php?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">»</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No products found</h5>
                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                    <a href="<?= BASE_URL ?>pages/products.php" class="btn btn-primary">View All Products</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
