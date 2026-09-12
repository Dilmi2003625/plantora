<?php

require_once 'config/db.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

$product = null;
$variations = [];
$cartMessage = '';
$cartError = '';

if ($productId) {
    $productSql = '
        SELECT
            p.product_id,
            p.category_id,
            p.product_name,
            p.description,
            p.care_instructions,
            p.image,
            c.category_name,
            COALESCE(SUM(pv.stock_quantity), 0) AS total_stock
        FROM products p
        LEFT JOIN categories c
            ON p.category_id = c.category_id
        LEFT JOIN product_variations pv
            ON p.product_id = pv.product_id
        WHERE p.product_id = ?
        GROUP BY
            p.product_id,
            p.category_id,
            p.product_name,
            p.description,
            p.care_instructions,
            p.image,
            c.category_name
        LIMIT 1
    ';

    $productStmt = mysqli_prepare($conn, $productSql);

    if ($productStmt) {
        mysqli_stmt_bind_param($productStmt, 'i', $productId);
        mysqli_stmt_execute($productStmt);
        $productResult = mysqli_stmt_get_result($productStmt);
        $product = mysqli_fetch_assoc($productResult);
        mysqli_stmt_close($productStmt);
    }

    if ($product) {
        $variationSql = '
            SELECT variation_id, size, pot_option, price, stock_quantity
            FROM product_variations
            WHERE product_id = ?
            ORDER BY variation_id ASC
        ';

        $variationStmt = mysqli_prepare($conn, $variationSql);

        if ($variationStmt) {
            mysqli_stmt_bind_param($variationStmt, 'i', $productId);
            mysqli_stmt_execute($variationStmt);
            $variationResult = mysqli_stmt_get_result($variationStmt);

            while ($variation = mysqli_fetch_assoc($variationResult)) {
                $variation['variation_id'] = (int) $variation['variation_id'];
                $variation['price'] = (float) $variation['price'];
                $variation['stock_quantity'] = (int) $variation['stock_quantity'];
                $variations[] = $variation;
            }

            mysqli_stmt_close($variationStmt);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedVariationId = filter_input(INPUT_POST, 'variation_id', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1]
            ]);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1]
            ]);
            $postedSize = trim((string) ($_POST['size'] ?? ''));
            $postedPotOption = trim((string) ($_POST['pot_option'] ?? ''));
            $selectedVariation = null;

            foreach ($variations as $variation) {
                if (
                    $variation['variation_id'] === $postedVariationId
                    && $variation['size'] === $postedSize
                    && $variation['pot_option'] === $postedPotOption
                ) {
                    $selectedVariation = $variation;
                    break;
                }
            }

            if (!$selectedVariation) {
                $cartError = 'Please choose a valid plant variation.';
            } elseif (!$quantity || $quantity > $selectedVariation['stock_quantity']) {
                $cartError = 'Please choose a quantity available in stock.';
            } else {
                // The verified values below are ready for the future cart insert.
                $cartMessage = 'Your selection is ready to be added to the cart.';
            }
        }
    }
}

$variationData = [];
$sizeOptions = [];
$potOptions = [];

foreach ($variations as $variation) {
    $key = $variation['size'].'|'.$variation['pot_option'];
    $variationData[$key] = $variation;
    $sizeOptions[$variation['size']] = true;
    $potOptions[$variation['pot_option']] = true;
}

$defaultVariation = $variations[0] ?? null;
$pageTitle = $product ? $product['product_name'].' | Plantora' : 'Product Not Found | Plantora';

$categoryMap = [
    1 => ['slug' => 'low-light', 'title' => 'Low-Light Plants', 'badge' => 'Low Light', 'folder' => 'low-light'],
    2 => ['slug' => 'air-purifying', 'title' => 'Air-Purifying Plants', 'badge' => 'Air Purifying', 'folder' => 'air-purifying'],
    3 => ['slug' => 'easy-care', 'title' => 'Easy-Care Plants', 'badge' => 'Easy Care', 'folder' => 'easy-care'],
    4 => ['slug' => 'flowering', 'title' => 'Flowering Plants', 'badge' => 'Flowering', 'folder' => 'flowering'],
    5 => ['slug' => 'foliage', 'title' => 'Foliage Plants', 'badge' => 'Foliage', 'folder' => 'foliage'],
    6 => ['slug' => 'cacti-succulents', 'title' => 'Cacti & Succulents', 'badge' => 'Cacti & Succulents', 'folder' => 'cacti-succulents'],
];

$currentCatId = (int) ($product['category_id'] ?? 1);
$catMeta = $categoryMap[$currentCatId] ?? $categoryMap[1];
$categorySlug = $catMeta['slug'];
$categoryTitle = !empty($product['category_name']) ? $product['category_name'] : $catMeta['title'];
$categoryBadge = $catMeta['badge'];
$categoryFolder = $catMeta['folder'];

function getProductDetailsImage(string $categoryFolder, ?string $imageFilename): string
{
    $imageFilename = trim((string) $imageFilename);
    if ($imageFilename === '') {
        return 'images/logo.png';
    }

    $primary = "images/{$categoryFolder}/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $primary)) {
        return $primary;
    }

    if ($categoryFolder === 'cacti-succulents' || $categoryFolder === 'cacti') {
        $altCacti = "images/cacti/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $altCacti)) {
            return $altCacti;
        }
        $altSucc = "images/cacti-succulents/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $altSucc)) {
            return $altSucc;
        }
    }

    $prodPath = "images/products/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $prodPath)) {
        return $prodPath;
    }

    $basePath = "images/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $basePath)) {
        return $basePath;
    }

    $lowLightPath = "images/low-light/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $lowLightPath)) {
        return $lowLightPath;
    }

    return 'images/logo.png';
}

$productImage = $product ? getProductDetailsImage($categoryFolder, $product['image']) : '';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="top-bar">
    <div>🌿 Bring Nature Into Your Home with Plantora</div>
    <div>🚚 Free Delivery on orders over Rs. 5000</div>
    <div>Help Center &nbsp; | &nbsp; Track Order &nbsp; | &nbsp; FAQs</div>
</div>

<header class="header">
    <a href="index.php" class="logo">
        <img src="images/logo.png" alt="Plantora">
    </a>

    <nav class="navbar" id="navbar">
        <a href="index.php">Home</a>
        <div class="nav-dropdown">
            <a href="shop.php" class="dropdown-title">Indoor Plants <span class="arrow">⌄</span></a>
            <div class="dropdown-menu">
                <a href="shop.php?category=low-light">🌿 Low-Light Plants</a>
                <a href="shop.php?category=air-purifying">🌱 Air-Purifying Plants</a>
                <a href="shop.php?category=easy-care">🌱 Easy-Care Plants</a>
                <a href="shop.php?category=flowering">🌸 Flowering Plants</a>
                <a href="shop.php?category=foliage">🌿 Foliage Plants</a>
                <a href="shop.php?category=cacti-succulents">🌵 Cacti & Succulents</a>
            </div>
        </div>
        <div class="nav-dropdown">
            <a href="shop.php?category=pots" class="dropdown-title">Pots <span class="arrow">⌄</span></a>
            <div class="dropdown-menu">
                <a href="shop.php?category=plastic-pots">🪴 Plastic Pots</a>
                <a href="shop.php?category=terracotta-clay-pots">🏺 Terracotta & Clay Pots</a>
                <a href="shop.php?category=glazed-ceramic-pots">🏺 Glazed Ceramic Pots</a>
                <a href="shop.php?category=fiberglass-fiber-clay">🪴 Fiberglass & Fiber Clay</a>
                <a href="shop.php?category=cement-stone">🪨 Cement & Stone</a>
            </div>
        </div>
        <a href="shop.php?category=packages">Gift Packages</a>
        <a href="care.php">Care & Tips</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
    </nav>

    <div class="header-actions">
        <div class="search-box">
            <input type="text" placeholder="Search plants...">
            <button type="button" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <a href="login.php" class="login-link"><i class="fa-regular fa-user"></i> Login</a>
        <a href="cart.php" class="cart-link" aria-label="Shopping cart">
            <i class="fa-solid fa-cart-shopping"></i><span class="cart-count">0</span>
        </a>
    </div>
</header>

<?php if (!$product) { ?>
    <main class="product-not-found">
        <i class="fa-solid fa-leaf" aria-hidden="true"></i>
        <h1>Product not found.</h1>
        <p>We could not find a product for this link.</p>
        <a href="shop.php" class="product-back-link">Return to the shop</a>
    </main>
<?php } elseif (!$defaultVariation) { ?>
    <main class="product-not-found">
        <i class="fa-solid fa-leaf" aria-hidden="true"></i>
        <h1><?php echo e($product['product_name']); ?></h1>
        <p>This product is currently unavailable.</p>
        <a href="shop.php" class="product-back-link">Return to the shop</a>
    </main>
<?php } else { ?>
    <main class="product-details-page">
        <div class="product-breadcrumb">
            <a href="index.php">Home</a><span>/</span><a href="shop.php?category=<?php echo e($categorySlug); ?>"><?php echo e($categoryTitle); ?></a><span>/</span><span><?php echo e($product['product_name']); ?></span>
        </div>

        <section class="product-details-layout">
            <div class="product-details-image-wrap">
                <span class="product-details-badge"><?php echo e($categoryBadge); ?></span>
                <img class="product-details-image" src="<?php echo e($productImage); ?>" alt="<?php echo e($product['product_name']); ?>" onerror="this.onerror=null;this.src='images/logo.png';">
                <div class="product-image-note">
                    <i class="fa-solid fa-leaf" aria-hidden="true"></i>
                    <div>
                        <strong>Made for calm corners</strong>
                        <span>A gentle green companion for your home.</span>
                    </div>
                </div>
            </div>

            <div class="product-details-info">
                <p class="product-eyebrow">INDOOR PLANT</p>
                <h1><?php echo e($product['product_name']); ?></h1>
                <div class="product-details-rating" aria-label="5 out of 5 stars">★★★★★ <span>(24 reviews)</span></div>
                <p class="product-details-description"><?php echo e($product['description']); ?></p>

                <div class="product-highlights" aria-label="Plant highlights">
                    <div><i class="fa-solid fa-cloud-sun" aria-hidden="true"></i><span>Low indirect light</span></div>
                    <div><i class="fa-solid fa-droplet" aria-hidden="true"></i><span>Easy to care for</span></div>
                </div>

                <div class="product-care">
                    <h2>Care Information</h2>
                    <p><?php echo e($product['care_instructions'] ?: 'Care information is not currently available.'); ?></p>
                </div>

                <?php if ($cartMessage) { ?><p class="cart-form-message success" role="status"><?php echo e($cartMessage); ?></p><?php } ?>
                <?php if ($cartError) { ?><p class="cart-form-message error" role="alert"><?php echo e($cartError); ?></p><?php } ?>

                <form class="product-selection-form" method="post" action="product-details.php?id=<?php echo (int) $product['product_id']; ?>">
                    <div class="product-selection-heading">
                        <span>Make it yours</span>
                        <small>Choose a size and pot to continue</small>
                    </div>
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                    <input type="hidden" name="variation_id" id="variation-id" value="<?php echo (int) $defaultVariation['variation_id']; ?>">
                    <input type="hidden" name="variation_price" id="variation-price-input" value="<?php echo e(number_format($defaultVariation['price'], 2, '.', '')); ?>">
                    <input type="hidden" name="size" id="size-input" value="<?php echo e($defaultVariation['size']); ?>">
                    <input type="hidden" name="pot_option" id="pot-option-input" value="<?php echo e($defaultVariation['pot_option']); ?>">

                    <div class="product-option-group">
                        <label for="size-select">Plant Size</label>
                        <select id="size-select" name="size_selection">
                            <?php foreach ($sizeOptions as $size => $_available) { ?>
                                <option value="<?php echo e($size); ?>"<?php echo $size === $defaultVariation['size'] ? ' selected' : ''; ?>><?php echo e($size); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="product-option-group">
                        <label for="pot-select">Pot Option</label>
                        <select id="pot-select" name="pot_selection">
                            <?php foreach ($potOptions as $potOption => $_available) { ?>
                                <option value="<?php echo e($potOption); ?>"<?php echo $potOption === $defaultVariation['pot_option'] ? ' selected' : ''; ?>><?php echo e($potOption); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="product-price-row">
                        <span>Price</span>
                        <strong id="variation-price">Rs. <?php echo number_format($defaultVariation['price'], 2); ?></strong>
                    </div>
                    <p class="product-stock" id="variation-stock">
                        <?php echo $defaultVariation['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </p>

                    <div class="product-purchase-row">
                        <div class="quantity-control" aria-label="Quantity selector">
                            <button type="button" id="quantity-minus" aria-label="Decrease quantity">−</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo (int) $defaultVariation['stock_quantity']; ?>" inputmode="numeric">
                            <button type="button" id="quantity-plus" aria-label="Increase quantity">+</button>
                        </div>
                        <button class="product-add-button" id="add-to-cart" type="submit"<?php echo $defaultVariation['stock_quantity'] < 1 ? ' disabled' : ''; ?>>
                            <i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Add to Cart
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
<?php } ?>

<footer class="footer">
    <div class="footer-leaf watermark"><i class="fa-solid fa-leaf"></i></div>
    <div class="footer-grid">
        <div class="footer-brand">
            <img src="images/logo.png" alt="Plantora Logo" class="footer-logo">
            <p>Beautiful indoor plants, stylish pots and thoughtful gift packages to greenify your space.</p>
        </div>
        <div class="footer-column">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a><a href="shop.php">Indoor Plants</a><a href="shop.php?category=pots">Pots</a><a href="shop.php?category=packages">Gift Packages</a>
        </div>
        <div class="footer-column">
            <h3>Customer Service</h3>
            <a href="#">Help Center</a><a href="#">Track Order</a><a href="#">FAQs</a><a href="#">Returns & Refunds</a>
        </div>
        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>📍 Colombo, Sri Lanka</p><p>📞 +94 71 234 5678</p><p>✉ support@plantora.com</p>
        </div>
    </div>
    <div class="footer-bottom"><p>© 2026 Plantora. All Rights Reserved.</p><div><a href="#">Privacy Policy</a><a href="#">Terms & Conditions</a></div></div>
</footer>

<script src="js/cart.js?v=1"></script>

<?php if ($product && $defaultVariation) { ?>
<script>
const variationData = <?php echo json_encode($variationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const sizeSelect = document.getElementById('size-select');
const potSelect = document.getElementById('pot-select');
const variationIdInput = document.getElementById('variation-id');
const variationPriceInput = document.getElementById('variation-price-input');
const sizeInput = document.getElementById('size-input');
const potOptionInput = document.getElementById('pot-option-input');
const priceElement = document.getElementById('variation-price');
const stockElement = document.getElementById('variation-stock');
const quantityInput = document.getElementById('quantity');
const addButton = document.getElementById('add-to-cart');

function updateVariation() {
    const key = sizeSelect.value + '|' + potSelect.value;
    const variation = variationData[key];

    if (!variation) {
        variationIdInput.value = '';
        priceElement.textContent = 'Unavailable';
        stockElement.textContent = 'This combination is unavailable.';
        addButton.disabled = true;
        return;
    }

    variationIdInput.value = variation.variation_id;
    variationPriceInput.value = Number(variation.price).toFixed(2);
    sizeInput.value = variation.size;
    potOptionInput.value = variation.pot_option;
    priceElement.textContent = 'Rs. ' + Number(variation.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    stockElement.textContent = variation.stock_quantity > 0 ? 'In Stock (' + variation.stock_quantity + ' available)' : 'Out of Stock';
    quantityInput.max = Math.max(variation.stock_quantity, 1);
    quantityInput.value = Math.min(Math.max(parseInt(quantityInput.value, 10) || 1, 1), Math.max(variation.stock_quantity, 1));
    addButton.disabled = variation.stock_quantity < 1;
}

sizeSelect.addEventListener('change', updateVariation);
potSelect.addEventListener('change', updateVariation);
document.getElementById('quantity-minus').addEventListener('click', function () {
    quantityInput.value = Math.max(1, (parseInt(quantityInput.value, 10) || 1) - 1);
});
document.getElementById('quantity-plus').addEventListener('click', function () {
    const maximum = parseInt(quantityInput.max, 10) || 1;
    quantityInput.value = Math.min(maximum, (parseInt(quantityInput.value, 10) || 1) + 1);
});
quantityInput.addEventListener('change', function () {
    const maximum = parseInt(quantityInput.max, 10) || 1;
    quantityInput.value = Math.min(maximum, Math.max(1, parseInt(quantityInput.value, 10) || 1));
});
</script>
<?php } ?>
</body>
</html>
