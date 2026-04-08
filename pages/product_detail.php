<?php
/**
 * ============================================
 * Product Detail Page (UPGRADED v2)
 * With Variant Images, Gallery Slider, Dynamic Updates
 * ============================================
 */
require_once __DIR__ . '/../config/database.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: ' . BASE_URL . 'pages/products.php');
    exit;
}

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . BASE_URL . 'pages/products.php');
    exit;
}

$product = $result->fetch_assoc();
$page_title = $product['name'];

// Fetch product variations
$variations = [];
$variant_types = [];
$var_q = $conn->query("SELECT * FROM product_variations WHERE product_id = {$product['id']} ORDER BY variant_type, variant_value");
while ($v = $var_q->fetch_assoc()) {
    $variations[] = $v;
    if (!in_array($v['variant_type'], $variant_types)) {
        $variant_types[] = $v['variant_type'];
    }
}
$has_variations = !empty($variations);

// Fetch gallery images
$gallery_images = [];
$gal_q = $conn->query("SELECT * FROM product_images WHERE product_id = {$product['id']} ORDER BY sort_order");
while ($gi = $gal_q->fetch_assoc()) {
    $gallery_images[] = $gi;
}
$has_gallery = !empty($gallery_images);

// Fetch related products
$related = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = {$product['category_id']} AND p.id != {$product['id']} LIMIT 4");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';
?>

<!-- Page Header -->
<section class="page-header py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-warning">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>pages/products.php" class="text-warning">Products</a></li>
                <li class="breadcrumb-item text-white-50 active"><?= htmlspecialchars($product['name']) ?></li>
            </ol>
        </nav>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Product Image & Gallery -->
            <div class="col-lg-5 scroll-reveal">
                <div class="product-detail-img-wrapper glass-card p-3">
                    <!-- Main Image -->
                    <div class="main-image-container" style="position:relative;overflow:hidden;border-radius:12px;background:linear-gradient(135deg,#e3f0ff,#f0f7ff);">
                        <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($product['image']) ?>" 
                             class="img-fluid rounded main-product-img" 
                             id="mainProductImage"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             style="width:100%;height:350px;object-fit:contain;transition:all 0.4s ease;cursor:zoom-in;"
                             onerror="this.src='https://via.placeholder.com/500x400/1a3a5c/ffffff?text=<?= urlencode($product['name']) ?>'">
                        <?php if ($product['old_price']): ?>
                            <span class="sale-badge sale-badge-lg">SALE</span>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery Slider -->
                    <?php if ($has_gallery): ?>
                    <div class="gallery-slider mt-3" style="position:relative;">
                        <div class="gallery-track" id="galleryTrack" style="display:flex;gap:8px;overflow-x:auto;scroll-behavior:smooth;padding:4px 0;scrollbar-width:thin;">
                            <!-- Main image as first thumbnail -->
                            <div class="gallery-thumb active" onclick="changeMainImage(this)" data-src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($product['image']) ?>" style="min-width:65px;height:65px;border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid var(--hg-blue-primary);transition:all 0.3s ease;flex-shrink:0;">
                                <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($product['image']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='https://via.placeholder.com/65/1a3a5c/ffffff?text=1'">
                            </div>
                            <?php foreach ($gallery_images as $idx => $gi): ?>
                            <div class="gallery-thumb" onclick="changeMainImage(this)" data-src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($gi['image_path']) ?>" style="min-width:65px;height:65px;border-radius:10px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:all 0.3s ease;flex-shrink:0;">
                                <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($gi['image_path']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='https://via.placeholder.com/65/1a3a5c/ffffff?text=<?= $idx+2 ?>'">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Scroll Arrows -->
                        <button type="button" onclick="scrollGallery(-1)" class="btn btn-sm btn-light gallery-arrow gallery-arrow-left" style="position:absolute;left:-10px;top:50%;transform:translateY(-50%);border-radius:50%;width:30px;height:30px;padding:0;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:2;"><i class="fas fa-chevron-left" style="font-size:0.7rem;"></i></button>
                        <button type="button" onclick="scrollGallery(1)" class="btn btn-sm btn-light gallery-arrow gallery-arrow-right" style="position:absolute;right:-10px;top:50%;transform:translateY(-50%);border-radius:50%;width:30px;height:30px;padding:0;box-shadow:0 2px 8px rgba(0,0,0,0.15);z-index:2;"><i class="fas fa-chevron-right" style="font-size:0.7rem;"></i></button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-7 scroll-reveal">
                <span class="badge bg-primary mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                <h2 class="fw-bold mb-3"><?= htmlspecialchars($product['name']) ?></h2>
                
                <div class="d-flex align-items-center mb-3">
                    <span class="h3 fw-bold text-primary mb-0" id="displayPrice">$<?= number_format($product['price'], 2) ?></span>
                    <?php if ($product['old_price']): ?>
                        <span class="h5 text-muted text-decoration-line-through ms-3" id="displayOldPrice">$<?= number_format($product['old_price'], 2) ?></span>
                        <span class="badge bg-danger ms-2" id="displaySaveBadge">Save $<?= number_format($product['old_price'] - $product['price'], 2) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-3" id="stockDisplay">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>In Stock (<span id="stockCount"><?= $product['stock'] ?></span> available)</span>
                    <?php else: ?>
                        <span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                    <?php endif; ?>
                </div>

                <!-- Variation Selection -->
                <?php if ($has_variations): ?>
                <div class="mb-4" id="variationSection">
                    <?php foreach ($variant_types as $type): ?>
                    <div class="mb-3">
                        <label class="fw-bold text-capitalize mb-2">
                            <i class="fas fa-<?= $type === 'color' ? 'palette' : ($type === 'storage' ? 'hdd' : 'code-branch') ?> me-1 text-primary"></i>
                            Select <?= ucfirst($type) ?> <span class="text-danger">*</span>
                        </label>
                        <div class="variant-options" data-type="<?= $type ?>">
                            <?php foreach ($variations as $v): ?>
                                <?php if ($v['variant_type'] === $type): ?>
                                <button type="button" class="variant-btn" 
                                        data-variant-id="<?= $v['id'] ?>"
                                        data-variant-type="<?= $v['variant_type'] ?>"
                                        data-variant-value="<?= htmlspecialchars($v['variant_value']) ?>"
                                        data-price="<?= $v['price'] ?>"
                                        data-price-mod="<?= $v['price_modifier'] ?>"
                                        data-stock="<?= $v['stock'] ?>"
                                        data-image="<?= !empty($v['image']) ? BASE_URL . 'assets/images/' . htmlspecialchars($v['image']) : '' ?>"
                                        onclick="selectVariant(this, '<?= $type ?>')">
                                    <?php if ($type === 'color'): ?>
                                        <span class="variant-color-swatch" style="background:<?= strtolower($v['variant_value']) ?>;"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($v['variant_value']) ?>
                                    <?php if ($v['price_modifier'] > 0): ?>
                                        <span class="variant-price-mod">(+$<?= number_format($v['price_modifier'], 2) ?>)</span>
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="error-<?= $type ?>">Please select a <?= $type ?>.</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id']) && $product['stock'] > 0): ?>
                    <form action="<?= BASE_URL ?>pages/cart.php" method="GET" class="d-flex align-items-center gap-3 mt-4" id="addToCartForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="variation_id" id="selectedVariationId" value="">
                        <label class="fw-bold">Qty:</label>
                        <input type="number" name="qty" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control" style="width:80px;" id="qtyInput">
                        <button type="submit" class="btn btn-primary btn-lg hg-btn-glow add-to-cart-btn" id="addToCartBtn">
                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                        </button>
                    </form>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-outline-primary btn-lg mt-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Purchase
                    </a>
                <?php endif; ?>

                <!-- Features -->
                <div class="row g-3 mt-4">
                    <div class="col-6"><div class="d-flex align-items-center"><i class="fas fa-shipping-fast text-primary me-2"></i><small>Free Shipping</small></div></div>
                    <div class="col-6"><div class="d-flex align-items-center"><i class="fas fa-shield-alt text-primary me-2"></i><small>1 Year Warranty</small></div></div>
                    <div class="col-6"><div class="d-flex align-items-center"><i class="fas fa-undo text-primary me-2"></i><small>30-Day Returns</small></div></div>
                    <div class="col-6"><div class="d-flex align-items-center"><i class="fas fa-lock text-primary me-2"></i><small>Secure Checkout</small></div></div>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <div class="mt-5 pt-4 border-top scroll-reveal">
            <h4 class="fw-bold mb-4"><i class="fas fa-file-alt text-primary me-2"></i>Product Description</h4>
            <div class="card border-0 shadow-sm glass-card">
                <div class="card-body p-4">
                    <p class="text-muted mb-0" style="white-space: pre-line; line-height: 1.8;"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if ($related->num_rows > 0): ?>
        <div class="mt-5 pt-5 border-top">
            <h4 class="fw-bold mb-4">Related Products</h4>
            <div class="row g-4">
                <?php while ($r = $related->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-3 scroll-reveal">
                    <div class="product-card card h-100 border-0 shadow-sm">
                        <div class="product-img-wrapper">
                            <img src="<?= BASE_URL ?>assets/images/<?= htmlspecialchars($r['image']) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($r['name']) ?>"
                                 onerror="this.src='https://via.placeholder.com/300x250/1a3a5c/ffffff?text=<?= urlencode($r['name']) ?>'">
                        </div>
                        <div class="card-body">
                            <h6 class="card-title fw-bold"><?= htmlspecialchars($r['name']) ?></h6>
                            <span class="h6 fw-bold text-primary">$<?= number_format($r['price'], 2) ?></span>
                            <div class="mt-2">
                                <a href="<?= BASE_URL ?>pages/product_detail.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
const basePrice = <?= $product['price'] ?>;
const hasVariations = <?= $has_variations ? 'true' : 'false' ?>;
const variantTypes = <?= json_encode($variant_types) ?>;
const selectedVariants = {};
const defaultImage = document.getElementById('mainProductImage')?.src || '';

function selectVariant(btn, type) {
    // Deselect siblings
    btn.closest('.variant-options').querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    selectedVariants[type] = {
        id: btn.dataset.variantId,
        value: btn.dataset.variantValue,
        price: parseFloat(btn.dataset.price),
        priceMod: parseFloat(btn.dataset.priceMod),
        stock: parseInt(btn.dataset.stock),
        image: btn.dataset.image
    };
    
    // Hide error
    const errEl = document.getElementById('error-' + type);
    if (errEl) errEl.classList.add('d-none');
    
    // Update price - use variant's own price if set, otherwise base + modifier
    let displayPrice = basePrice;
    let totalMod = 0;
    Object.values(selectedVariants).forEach(v => {
        if (v.price > 0) displayPrice = v.price;
        totalMod += v.priceMod;
    });
    // If variant has its own price, use it; otherwise use base + modifier
    const lastSelected = selectedVariants[type];
    if (lastSelected.price > 0) {
        displayPrice = lastSelected.price;
    } else {
        displayPrice = basePrice + totalMod;
    }
    document.getElementById('displayPrice').textContent = '$' + displayPrice.toFixed(2);
    
    // Update stock display
    const stockEl = document.getElementById('stockCount');
    const stockDisplay = document.getElementById('stockDisplay');
    if (stockEl && stockDisplay) {
        if (lastSelected.stock > 0) {
            stockDisplay.innerHTML = '<span class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>In Stock (<span id="stockCount">' + lastSelected.stock + '</span> available)</span>';
        } else {
            stockDisplay.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>';
        }
    }
    
    // Update max qty
    const qtyInput = document.getElementById('qtyInput');
    if (qtyInput) {
        qtyInput.max = lastSelected.stock;
        if (parseInt(qtyInput.value) > lastSelected.stock) qtyInput.value = lastSelected.stock;
    }
    
    // Update image if variant has one
    if (lastSelected.image) {
        const mainImg = document.getElementById('mainProductImage');
        if (mainImg) {
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = lastSelected.image;
                mainImg.style.opacity = '1';
            }, 200);
        }
    }
    
    // Set variation_id
    document.getElementById('selectedVariationId').value = btn.dataset.variantId;
}

// Gallery functions
function changeMainImage(thumb) {
    const src = thumb.dataset.src;
    const mainImg = document.getElementById('mainProductImage');
    if (mainImg && src) {
        // Deselect all thumbs
        document.querySelectorAll('.gallery-thumb').forEach(t => {
            t.style.borderColor = 'transparent';
            t.style.transform = 'scale(1)';
        });
        // Select this one
        thumb.style.borderColor = 'var(--hg-blue-primary)';
        thumb.style.transform = 'scale(1.05)';
        // Animate image change
        mainImg.style.opacity = '0';
        setTimeout(() => {
            mainImg.src = src;
            mainImg.style.opacity = '1';
        }, 200);
    }
}

function scrollGallery(direction) {
    const track = document.getElementById('galleryTrack');
    if (track) {
        track.scrollBy({ left: direction * 150, behavior: 'smooth' });
    }
}

// Hover zoom on main image
const mainImgContainer = document.querySelector('.main-image-container');
const mainImg = document.getElementById('mainProductImage');
if (mainImgContainer && mainImg) {
    mainImgContainer.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        mainImg.style.transformOrigin = x + '% ' + y + '%';
        mainImg.style.transform = 'scale(1.5)';
    });
    mainImgContainer.addEventListener('mouseleave', function() {
        mainImg.style.transform = 'scale(1)';
        mainImg.style.transformOrigin = 'center center';
    });
}

// Form validation for variations
const form = document.getElementById('addToCartForm');
if (form && hasVariations) {
    form.addEventListener('submit', function(e) {
        let valid = true;
        variantTypes.forEach(type => {
            if (!selectedVariants[type]) {
                valid = false;
                const errEl = document.getElementById('error-' + type);
                if (errEl) errEl.classList.remove('d-none');
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please select all product options before adding to cart.');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
