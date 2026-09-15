<?php

require_once 'config/db.php';
require_once __DIR__ . '/includes/auth.php';

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
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
            p.product_type,
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
            p.product_type,
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
        $isPot = (isset($product['product_type']) && strtolower(trim($product['product_type'])) === 'pot')
            || (isset($product['category_name']) && stripos($product['category_name'], 'pot') !== false)
            || ((int) ($product['category_id'] ?? 0) >= 7);

        $variationSql = '
            SELECT variation_id, color, size, pot_option, price, stock_quantity
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
                $variation['color'] = trim((string) ($variation['color'] ?? ''));
                $variations[] = $variation;
            }

            mysqli_stmt_close($variationStmt);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedVariationId = filter_var($_POST['variation_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1]
            ]);
            $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1]
            ]);
            $postedColor = trim((string) ($_POST['color'] ?? ''));
            $postedSize = trim((string) ($_POST['size'] ?? ''));
            $postedPotOption = trim((string) ($_POST['pot_option'] ?? ''));
            $selectedVariation = null;

            foreach ($variations as $variation) {
                if ($isPot) {
                    if (
                        $variation['variation_id'] === $postedVariationId
                        && $variation['size'] === $postedSize
                        && ($postedColor === '' || $variation['color'] === $postedColor)
                    ) {
                        $selectedVariation = $variation;
                        break;
                    }
                } else {
                    if (
                        $variation['variation_id'] === $postedVariationId
                        && $variation['size'] === $postedSize
                        && $variation['pot_option'] === $postedPotOption
                    ) {
                        $selectedVariation = $variation;
                        break;
                    }
                }
            }

            if (!$selectedVariation) {
                $cartError = $isPot ? 'Please choose a valid pot variation.' : 'Please choose a valid plant variation.';
            } elseif (!$quantity || $quantity > $selectedVariation['stock_quantity']) {
                $cartError = 'Please choose a quantity available in stock.';
            } else {
                $cartMessage = 'Your selection is ready to be added to the cart.';
            }
        }
    }
}

$isPot = $product ? (
    (isset($product['product_type']) && strtolower(trim($product['product_type'])) === 'pot')
    || (isset($product['category_name']) && stripos($product['category_name'], 'pot') !== false)
    || ((int) ($product['category_id'] ?? 0) >= 7)
) : false;

$variationData = [];
$sizeOptions = [];
$potOptions = [];
$colorOptions = [];
$colorImages = [];

foreach ($variations as $variation) {
    if ($isPot) {
        $col = $variation['color'];
        $sz = $variation['size'];
        $key = $col . '|' . $sz;
        $variationData[$key] = $variation;
        if (!empty($col)) {
            $colorOptions[$col] = true;
        }
    } else {
        $key = $variation['size'] . '|' . $variation['pot_option'];
        $variationData[$key] = $variation;
        if (!empty($variation['pot_option'])) {
            $potOptions[$variation['pot_option']] = true;
        }
    }
    if (!empty($variation['size'])) {
        $sizeOptions[$variation['size']] = true;
    }
}

$defaultVariation = $variations[0] ?? null;
$defaultColor = $isPot ? ($defaultVariation['color'] ?? array_key_first($colorOptions) ?? '') : '';
$pageTitle = $product ? $product['product_name'] . ' | Plantora' : 'Product Not Found | Plantora';

$categoryMap = [
    1 => ['slug' => 'low-light', 'title' => 'Low-Light Plants', 'badge' => 'Low Light', 'folder' => 'low-light'],
    2 => ['slug' => 'air-purifying', 'title' => 'Air-Purifying Plants', 'badge' => 'Air Purifying', 'folder' => 'air-purifying'],
    3 => ['slug' => 'easy-care', 'title' => 'Easy-Care Plants', 'badge' => 'Easy Care', 'folder' => 'easy-care'],
    4 => ['slug' => 'flowering', 'title' => 'Flowering Plants', 'badge' => 'Flowering', 'folder' => 'flowering'],
    5 => ['slug' => 'foliage', 'title' => 'Foliage Plants', 'badge' => 'Foliage', 'folder' => 'foliage'],
    6 => ['slug' => 'cacti-succulents', 'title' => 'Cacti & Succulents', 'badge' => 'Cacti & Succulents', 'folder' => 'cacti-succulents'],
    7 => ['slug' => 'plastic-pots', 'title' => 'Plastic Pots', 'badge' => 'Plastic Pot', 'folder' => 'pots/plastic'],
    8 => ['slug' => 'terracotta-clay', 'title' => 'Terracotta & Clay Pots', 'badge' => 'Terracotta & Clay', 'folder' => 'pots/terracotta-clay'],
    9 => ['slug' => 'glazed-ceramic', 'title' => 'Glazed Ceramic Pots', 'badge' => 'Glazed Ceramic', 'folder' => 'pots/glazed-ceramic'],
    10 => ['slug' => 'fiberglass-fiber-clay', 'title' => 'Fiberglass & Fiber Clay', 'badge' => 'Fiberglass & Fiber Clay', 'folder' => 'pots/fiberglass-fiber-clay'],
    11 => ['slug' => 'cement-stone', 'title' => 'Cement & Stone Pots', 'badge' => 'Cement & Stone', 'folder' => 'pots/cement-stone'],
];

$currentCatId = (int) ($product['category_id'] ?? 1);
$catMeta = $categoryMap[$currentCatId] ?? ($isPot ? $categoryMap[7] : $categoryMap[1]);
$categorySlug = $catMeta['slug'];
$categoryTitle = !empty($product['category_name']) ? $product['category_name'] : $catMeta['title'];
$categoryBadge = $isPot ? $catMeta['badge'] : $catMeta['badge'];
$categoryFolder = $catMeta['folder'];

if (!function_exists('getProductDetailsImage')) {
    function getProductDetailsImage(string $categoryFolder, ?string $imageFilename): string
    {
        $imageFilename = trim((string) $imageFilename);
        if ($imageFilename === '') {
            return 'images/logo.png';
        }

        // Direct path checks
        if (file_exists(__DIR__ . '/' . $imageFilename)) {
            return $imageFilename;
        }
        if (file_exists(__DIR__ . '/images/' . $imageFilename)) {
            return 'images/' . $imageFilename;
        }

        // 1. Primary category folder
        $primary = "images/{$categoryFolder}/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $primary)) {
            return $primary;
        }

        // 2. Pots subfolder check
        if (file_exists(__DIR__ . '/images/pots/' . $imageFilename)) {
            return 'images/pots/' . $imageFilename;
        }

        // 3. Categories folder
        if (file_exists(__DIR__ . '/images/categories/' . $imageFilename)) {
            return 'images/categories/' . $imageFilename;
        }

        // 4. Cacti alternative checks
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
}

$productImage = $product ? getProductDetailsImage($categoryFolder, $product['image']) : '';

// Resolve color images for pot products
if ($isPot && $product) {
    $cleanDir = dirname($product['image']);
    if (strpos($cleanDir, 'images/') === 0) {
        $cleanDir = substr($cleanDir, 7);
    }
    foreach (array_keys($colorOptions) as $col) {
        $colSlug = strtolower(str_replace([' ', '_'], '-', trim($col)));
        $patterns = [
            __DIR__ . '/images/' . $cleanDir . '/*' . $colSlug . '*.jpeg',
            __DIR__ . '/images/' . $cleanDir . '/*' . $colSlug . '*.jpg',
            __DIR__ . '/images/' . $cleanDir . '/*' . $colSlug . '*.png',
            __DIR__ . '/images/pots/' . $cleanDir . '/*' . $colSlug . '*.jpeg',
            __DIR__ . '/images/pots/' . $cleanDir . '/*' . $colSlug . '*.jpg',
        ];
        $found = null;
        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches) && file_exists($matches[0])) {
                $absPath = str_replace('\\', '/', $matches[0]);
                $webRoot = str_replace('\\', '/', __DIR__ . '/');
                $found = str_replace($webRoot, '', $absPath);
                break;
            }
        }
        $colorImages[$col] = $found ?: $productImage;
    }
}

if (!function_exists('getPotColorHex')) {
    function getPotColorHex(string $colorName): string
    {
        $map = [
            'green' => '#2e7d32',
            'black' => '#1e1e1e',
            'white' => '#ffffff',
            'glossy white' => '#ffffff',
            'matte white' => '#f5f5f5',
            'soft cream' => '#f5f2eb',
            'terracotta' => '#c86446',
            'natural terracotta' => '#c86446',
            'aged terracotta' => '#b8593d',
            'terracotta brown' => '#9e5338',
            'rustic orange' => '#d35400',
            'gray' => '#808080',
            'light gray' => '#b5b8ba',
            'dark gray' => '#4a4d52',
            'slate gray' => '#5b6770',
            'charcoal gray' => '#3a3d40',
            'granite gray' => '#6c7176',
            'anthracite' => '#2c3036',
            'blue' => '#2980b9',
            'cobalt blue' => '#0047ab',
            'sky blue' => '#87ceeb',
            'navy blue' => '#1b2a4a',
            'beige' => '#d4b996',
            'light brown' => '#a77953',
            'dark brown' => '#5c3d2e',
            'clay brown' => '#8b5a3c',
            'natural clay' => '#b27453',
            'sage green' => '#8fa89b',
            'olive green' => '#556b2f',
            'blush pink' => '#e8c4c4',
            'matte black' => '#2b2b2b',
            'concrete gray' => '#8a8d91',
            'sandstone' => '#d2b48c',
            'natural stone' => '#8c857b',
            'charcoal stone' => '#2b2d30',
            'light cement' => '#b5b8ba',
            'dark cement' => '#4a4d52'
        ];
        $clean = strtolower(trim($colorName));
        return $map[$clean] ?? '#7f8c8d';
    }
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css?v=3">
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
                <a href="shop.php?category=terracotta-clay">🏺 Terracotta & Clay Pots</a>
                <a href="shop.php?category=glazed-ceramic">🏺 Glazed Ceramic Pots</a>
                <a href="shop.php?category=fiberglass-fiber-clay">🪴 Fiberglass & Fiber Clay</a>
                <a href="shop.php?category=cement-stone">🪨 Cement & Stone</a>
            </div>
        </div>
        <a href="shop.php?category=packages">Gift Packages</a>
        <a href="care.php">Care & Tips</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
    </nav>

    <?php include __DIR__ . '/includes/header_actions.php'; ?>
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
                        <strong><?php echo $isPot ? 'Crafted for plant care' : 'Made for calm corners'; ?></strong>
                        <span><?php echo $isPot ? 'Engineered for proper drainage and root breathability.' : 'A gentle green companion for your home.'; ?></span>
                    </div>
                </div>
            </div>

            <div class="product-details-info">
                <p class="product-eyebrow"><?php echo $isPot ? 'PLANT POT' : 'INDOOR PLANT'; ?></p>
                <h1><?php echo e($product['product_name']); ?></h1>
                <div class="product-details-rating" aria-label="5 out of 5 stars">★★★★★ <span>(24 reviews)</span></div>
                <p class="product-details-description"><?php echo e($product['description']); ?></p>

                <?php if ($isPot) { ?>
                    <div class="product-highlights" aria-label="Pot highlights">
                        <div><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Premium Quality Material</span></div>
                        <div><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i><span>Exact Centimeter Fit</span></div>
                    </div>
                <?php } else { ?>
                    <div class="product-highlights" aria-label="Plant highlights">
                        <div><i class="fa-solid fa-cloud-sun" aria-hidden="true"></i><span>Low indirect light</span></div>
                        <div><i class="fa-solid fa-droplet" aria-hidden="true"></i><span>Easy to care for</span></div>
                    </div>
                <?php } ?>

                <div class="product-care">
                    <h2><?php echo $isPot ? 'Care & Usage' : 'Care Information'; ?></h2>
                    <p><?php echo e($product['care_instructions'] ?: 'Care information is not currently available.'); ?></p>
                </div>

                <?php if ($cartMessage) { ?><p class="cart-form-message success" role="status"><?php echo e($cartMessage); ?></p><?php } ?>
                <?php if ($cartError) { ?><p class="cart-form-message error" role="alert"><?php echo e($cartError); ?></p><?php } ?>

                <form class="product-selection-form" method="post" action="product-details.php?id=<?php echo (int) $product['product_id']; ?>">
                    <div class="product-selection-heading">
                        <span>Make it yours</span>
                        <small><?php echo $isPot ? 'Choose a color and pot size to continue' : 'Choose a size and pot to continue'; ?></small>
                    </div>
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                    <input type="hidden" name="variation_id" id="variation-id" value="<?php echo (int) $defaultVariation['variation_id']; ?>">
                    <input type="hidden" name="variation_price" id="variation-price-input" value="<?php echo e(number_format($defaultVariation['price'], 2, '.', '')); ?>">
                    <input type="hidden" name="color" id="color-input" value="<?php echo e($defaultColor); ?>">
                    <input type="hidden" name="size" id="size-input" value="<?php echo e($defaultVariation['size']); ?>">
                    <input type="hidden" name="pot_option" id="pot-option-input" value="<?php echo e($defaultVariation['pot_option'] ?? ''); ?>">

                    <?php if ($isPot) { ?>
                        <?php if (!empty($colorOptions)) { ?>
                            <div class="product-option-group">
                                <label>Choose Pot Color: <strong id="selected-color-label"><?php echo e($defaultColor); ?></strong></label>
                                <div class="color-options-grid" role="radiogroup" aria-label="Pot color options">
                                    <?php foreach ($colorOptions as $colorName => $_available) {
                                        $hex = getPotColorHex($colorName);
                                        $isActive = ($colorName === $defaultColor);
                                    ?>
                                        <button type="button"
                                                class="color-option-btn<?php echo $isActive ? ' active' : ''; ?>"
                                                data-color="<?php echo e($colorName); ?>"
                                                style="background-color: <?php echo e($hex); ?>; <?php echo ($hex === '#ffffff' || $hex === '#f5f5f5' || $hex === '#f5f2eb') ? 'border: 1px solid #ccc;' : ''; ?>"
                                                title="<?php echo e($colorName); ?>"
                                                aria-label="<?php echo e($colorName); ?>"
                                                aria-checked="<?php echo $isActive ? 'true' : 'false'; ?>"
                                                role="radio">
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="product-option-group">
                            <label>Choose Pot Size:</label>
                            <div class="size-options-grid" role="radiogroup" aria-label="Pot size options">
                                <?php foreach ($sizeOptions as $size => $_available) {
                                    $isActive = ($size === $defaultVariation['size']);
                                ?>
                                    <button type="button"
                                            class="size-option-btn<?php echo $isActive ? ' active' : ''; ?>"
                                            data-size="<?php echo e($size); ?>"
                                            aria-checked="<?php echo $isActive ? 'true' : 'false'; ?>"
                                            role="radio">
                                        <?php echo e($size); ?>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="product-option-group">
                            <label for="size-select">Choose Plant Size:</label>
                            <select id="size-select" name="size">
                                <?php foreach ($sizeOptions as $size => $_available) { ?>
                                    <option value="<?php echo e($size); ?>"<?php echo $size === $defaultVariation['size'] ? ' selected' : ''; ?>><?php echo e($size); ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="product-option-group">
                            <label for="pot-select">Choose Pot Option:</label>
                            <select id="pot-select" name="pot_option">
                                <?php foreach ($potOptions as $potOption => $_available) { ?>
                                    <option value="<?php echo e($potOption); ?>"<?php echo $potOption === $defaultVariation['pot_option'] ? ' selected' : ''; ?>><?php echo e($potOption); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } ?>

                    <div class="product-price-row">
                        <span>Price</span>
                        <strong id="variation-price">Rs. <?php echo number_format($defaultVariation['price'], 2); ?></strong>
                    </div>
                    <p class="product-stock" id="variation-stock">
                        <?php echo $defaultVariation['stock_quantity'] > 0 ? 'In Stock (' . $defaultVariation['stock_quantity'] . ' available)' : 'Out of Stock'; ?>
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

<script src="js/cart.js?v=2"></script>

<?php if ($product && $defaultVariation) { ?>
<script>
const isPot = <?php echo $isPot ? 'true' : 'false'; ?>;
const variationData = <?php echo json_encode($variationData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const colorImages = <?php echo json_encode($colorImages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

const sizeSelect = document.getElementById('size-select');
const potSelect = document.getElementById('pot-select');
const variationIdInput = document.getElementById('variation-id');
const variationPriceInput = document.getElementById('variation-price-input');
const colorInput = document.getElementById('color-input');
const sizeInput = document.getElementById('size-input');
const potOptionInput = document.getElementById('pot-option-input');
const priceElement = document.getElementById('variation-price');
const stockElement = document.getElementById('variation-stock');
const quantityInput = document.getElementById('quantity');
const addButton = document.getElementById('add-to-cart');
const productSelectionForm = document.querySelector('.product-selection-form');
const selectedColorLabel = document.getElementById('selected-color-label');
const mainProductImage = document.querySelector('.product-details-image');

function updateVariation() {
    let key;
    if (isPot) {
        const curColor = colorInput ? colorInput.value : '';
        const curSize = sizeInput ? sizeInput.value : '';
        key = curColor + '|' + curSize;
    } else {
        key = (sizeSelect ? sizeSelect.value : '') + '|' + (potSelect ? potSelect.value : '');
    }
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
    if (colorInput && variation.color) {
        colorInput.value = variation.color;
    }
    if (potOptionInput) {
        potOptionInput.value = variation.pot_option || '';
    }
    priceElement.textContent = 'Rs. ' + Number(variation.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    if (variation.stock_quantity > 0) {
        stockElement.textContent = 'In Stock (' + variation.stock_quantity + ' available)';
        stockElement.style.color = '';
        quantityInput.max = Math.max(variation.stock_quantity, 1);
        quantityInput.value = Math.min(Math.max(parseInt(quantityInput.value, 10) || 1, 1), Math.max(variation.stock_quantity, 1));
        addButton.disabled = false;
    } else {
        stockElement.textContent = 'Out of Stock';
        stockElement.style.color = '#c0392b';
        quantityInput.max = 1;
        addButton.disabled = true;
    }
}

if (isPot) {
    const colorButtons = document.querySelectorAll('.color-option-btn');
    colorButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const color = this.getAttribute('data-color');
            colorButtons.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-checked', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-checked', 'true');
            if (colorInput) colorInput.value = color;
            if (selectedColorLabel) selectedColorLabel.textContent = color;

            if (mainProductImage && colorImages && colorImages[color]) {
                mainProductImage.src = colorImages[color];
            }

            updateVariation();
        });
    });

    const sizeButtons = document.querySelectorAll('.size-option-btn');
    sizeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const size = this.getAttribute('data-size');
            sizeButtons.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-checked', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-checked', 'true');
            if (sizeInput) sizeInput.value = size;

            updateVariation();
        });
    });
} else {
    if (sizeSelect) sizeSelect.addEventListener('change', updateVariation);
    if (potSelect) potSelect.addEventListener('change', updateVariation);
}

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

if (productSelectionForm) {
    productSelectionForm.addEventListener('submit', function (event) {
        const varId = parseInt(variationIdInput.value, 10);
        const qty = parseInt(quantityInput.value, 10) || 1;
        const currentPrice = parseFloat(variationPriceInput.value) || 0;
        const currentSize = sizeInput.value || (sizeSelect ? sizeSelect.value : '');
        const currentColor = isPot && colorInput ? colorInput.value : '';
        const currentImage = (isPot && currentColor && colorImages[currentColor]) ? colorImages[currentColor] : (mainProductImage ? mainProductImage.getAttribute('src') : <?php echo json_encode($productImage); ?>);

        if (!varId || addButton.disabled) {
            event.preventDefault();
            return;
        }

        if (typeof window.addToCart === 'function') {
            event.preventDefault();
            window.addToCart({
                product_id: <?php echo (int) $product['product_id']; ?>,
                variation_id: varId,
                product_name: <?php echo json_encode($product['product_name']); ?>,
                category: <?php echo json_encode($categoryTitle); ?>,
                color: currentColor,
                size: currentSize,
                price: currentPrice,
                quantity: qty,
                image: currentImage
            });

            let msg = document.querySelector('.cart-form-message.success');
            if (!msg) {
                msg = document.createElement('p');
                msg.className = 'cart-form-message success';
                msg.setAttribute('role', 'status');
                productSelectionForm.parentNode.insertBefore(msg, productSelectionForm);
            }
            let itemDescription = <?php echo json_encode($product['product_name']); ?>;
            if (currentColor) {
                itemDescription += ' (' + currentColor + ')';
            }
            itemDescription += ' - ' + currentSize;
            msg.textContent = 'Added ' + qty + ' × ' + itemDescription + ' to your cart!';
        }
    });
}

updateVariation();
</script>
<?php } ?>
</body>
</html>
