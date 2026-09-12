<?php

require_once 'config/db.php';

/* =====================================================
   CATEGORY CONFIGURATION & MAPPING
===================================================== */

$category = $_GET['category'] ?? 'low-light';

// Normalize aliases
if ($category === 'cacti') {
    $category = 'cacti-succulents';
}

$categoryMap = [
    'low-light' => [
        'id' => 1,
        'title' => 'Low-Light Plants',
        'badge' => 'Low Light',
        'folder' => 'low-light',
        'description' => 'Perfect plants for rooms with limited sunlight.',
    ],
    'air-purifying' => [
        'id' => 2,
        'title' => 'Air-Purifying Plants',
        'badge' => 'Air Purifying',
        'folder' => 'air-purifying',
        'description' => 'Indoor plants that help improve indoor air quality.',
    ],
    'easy-care' => [
        'id' => 3,
        'title' => 'Easy-Care Plants',
        'badge' => 'Easy Care',
        'folder' => 'easy-care',
        'description' => 'Low-maintenance plants suitable for beginners.',
    ],
    'flowering' => [
        'id' => 4,
        'title' => 'Flowering Plants',
        'badge' => 'Flowering',
        'folder' => 'flowering',
        'description' => 'Beautiful indoor plants with attractive flowers.',
    ],
    'foliage' => [
        'id' => 5,
        'title' => 'Foliage Plants',
        'badge' => 'Foliage',
        'folder' => 'foliage',
        'description' => 'Decorative plants grown mainly for their beautiful leaves.',
    ],
    'cacti-succulents' => [
        'id' => 6,
        'title' => 'Cacti & Succulents',
        'badge' => 'Cacti & Succulents',
        'folder' => 'cacti-succulents',
        'description' => 'Drought-tolerant plants that require less watering.',
    ],
];

if (!isset($categoryMap[$category])) {
    $category = 'low-light';
}

$currentCategory = $categoryMap[$category];
$categoryId = $currentCategory['id'];
$categoryTitle = $currentCategory['title'];
$categoryBadge = $currentCategory['badge'];
$categoryFolder = $currentCategory['folder'];
$categoryDescription = $currentCategory['description'];

// Category title from database if available
$catStmt = mysqli_prepare($conn, 'SELECT category_name, description FROM categories WHERE category_id = ? LIMIT 1');
if ($catStmt) {
    mysqli_stmt_bind_param($catStmt, 'i', $categoryId);
    mysqli_stmt_execute($catStmt);
    $catRes = mysqli_stmt_get_result($catStmt);
    if ($catRow = mysqli_fetch_assoc($catRes)) {
        if (!empty($catRow['category_name'])) {
            $categoryTitle = $catRow['category_name'];
        }
    }
    mysqli_stmt_close($catStmt);
}

/* =====================================================
   IMAGE RESOLUTION HELPER
===================================================== */

function getCategoryProductImage(string $categoryFolder, ?string $imageFilename): string
{
    $imageFilename = trim((string) $imageFilename);
    if ($imageFilename === '') {
        return 'images/logo.png';
    }

    // 1. Primary category folder
    $primary = "images/{$categoryFolder}/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $primary)) {
        return $primary;
    }

    // 2. If cacti & succulents, check alternative cacti folders
    if ($categoryFolder === 'cacti-succulents' || $categoryFolder === 'cacti') {
        $altCacti = "images/cacti/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $altCacti)) {
            return $altCacti;
        }
        $altSucculents = "images/cacti-succulents/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $altSucculents)) {
            return $altSucculents;
        }
    }

    // 3. Project products folder
    $prodPath = "images/products/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $prodPath)) {
        return $prodPath;
    }

    // 4. Base images folder
    $basePath = "images/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $basePath)) {
        return $basePath;
    }

    // 5. Low-light folder
    $lowLightPath = "images/low-light/{$imageFilename}";
    if (file_exists(__DIR__ . '/' . $lowLightPath)) {
        return $lowLightPath;
    }

    // 6. Safe category thumbnail fallback
    $catThumb = 'images/categories/' . str_replace('-', '_', $categoryFolder) . '.jpeg';
    if (file_exists(__DIR__ . '/' . $catThumb)) {
        return $catThumb;
    }

    return 'images/logo.png';
}

/* =====================================================
   SORTING & GET PRODUCTS FROM DATABASE
===================================================== */

$sort = $_GET['sort'] ?? 'featured';
$sortMap = [
    'low-high' => 'starting_price ASC, p.product_id ASC',
    'high-low' => 'starting_price DESC, p.product_id ASC',
    'newest' => 'p.product_id DESC',
    'featured' => 'p.product_id ASC',
];
$orderBy = $sortMap[$sort] ?? 'p.product_id ASC';

$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.care_instructions,
        p.image,
        MIN(pv.price) AS starting_price,
        SUM(pv.stock_quantity) AS total_stock,
        MIN(pv.variation_id) AS first_variation_id
    FROM products p
    INNER JOIN product_variations pv
        ON p.product_id = pv.product_id
    WHERE p.category_id = ?
    GROUP BY
        p.product_id,
        p.product_name,
        p.description,
        p.care_instructions,
        p.image
    ORDER BY {$orderBy}
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    exit('Database query preparation failed: '.mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $categoryId);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$productCount = mysqli_num_rows($result);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($categoryTitle); ?> | Plantora
    </title>

    <!-- Main CSS -->
    <link rel="stylesheet"
          href="css/style.css?v=2">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet">

</head>


<body>


<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="top-bar">

    <div>
        🌿 Bring Nature Into Your Home with Plantora
    </div>

    <div>
        🚚 Free Delivery on orders over Rs. 5000
    </div>

    <div>
        Help Center &nbsp; | &nbsp; Track Order &nbsp; | &nbsp; FAQs
    </div>

</div>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">


    <!-- LOGO -->

    <a href="index.php" class="logo">

        <img src="images/logo.png"
             alt="Plantora">

    </a>


    <!-- NAVIGATION -->

    <nav class="navbar" id="navbar">


        <a href="index.php">
            Home
        </a>


        <!-- INDOOR PLANTS -->

        <div class="nav-dropdown">

            <a href="shop.php"
               class="dropdown-title">

                Indoor Plants

                <span class="arrow">
                    ⌄
                </span>

            </a>


            <div class="dropdown-menu">

                <a href="shop.php?category=low-light">
                    🌿 Low-Light Plants
                </a>

                <a href="shop.php?category=air-purifying">
                    🌱 Air-Purifying Plants
                </a>

                <a href="shop.php?category=easy-care">
                    🌱 Easy-Care Plants
                </a>

                <a href="shop.php?category=flowering">
                    🌸 Flowering Plants
                </a>

                <a href="shop.php?category=foliage">
                    🌿 Foliage Plants
                </a>

                <a href="shop.php?category=cacti-succulents">
                    🌵 Cacti & Succulents
                </a>

            </div>

        </div>


        <!-- POTS -->

        <div class="nav-dropdown">

            <a href="shop.php?category=pots"
               class="dropdown-title">

                Pots

                <span class="arrow">
                    ⌄
                </span>

            </a>


            <div class="dropdown-menu">

                <a href="shop.php?category=plastic-pots">
                    🪴 Plastic Pots
                </a>

                <a href="shop.php?category=terracotta-clay-pots">
                    🏺 Terracotta & Clay Pots
                </a>

                <a href="shop.php?category=glazed-ceramic-pots">
                    🏺 Glazed Ceramic Pots
                </a>

                <a href="shop.php?category=fiberglass-fiber-clay">
                    🪴 Fiberglass & Fiber Clay
                </a>

                <a href="shop.php?category=cement-stone">
                    🪨 Cement & Stone
                </a>

            </div>

        </div>


        <!-- GIFT PACKAGES -->

        <a href="shop.php?category=packages">
            Gift Packages
        </a>


        <!-- CARE -->

        <a href="care.php">
            Care & Tips
        </a>


        <!-- ABOUT -->

        <a href="about.php">
            About Us
        </a>


        <!-- CONTACT -->

        <a href="contact.php">
            Contact Us
        </a>

    </nav>


    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <!-- SEARCH -->

        <div class="search-box">

            <input type="text"
                   placeholder="Search plants...">

            <button type="button">

                <i class="fa-solid fa-magnifying-glass"></i>

            </button>

        </div>


        <!-- LOGIN -->

        <a href="login.php"
           class="login-link">

            <i class="fa-regular fa-user"></i>

            Login

        </a>


        <!-- CART -->

        <a href="cart.php"
           class="cart-link">

            <i class="fa-solid fa-cart-shopping"></i>

            <span class="cart-count">
                0
            </span>

        </a>

    </div>

</header>


<!-- =====================================================
     PAGE HERO
===================================================== -->

<section class="shop-hero">

    <div class="shop-hero-content">

        <p>
            PLANTORA COLLECTION
        </p>

        <h1>
            <?php echo htmlspecialchars($categoryTitle); ?>
        </h1>

        <div class="breadcrumb">

            <a href="index.php">
                Home
            </a>

            <span>
                /
            </span>

            <span>
                Indoor Plants
            </span>

            <?php if ($category !== 'all') { ?>

                <span>
                    /
                </span>

                <span>
                    <?php echo htmlspecialchars($categoryTitle); ?>
                </span>

            <?php } ?>

        </div>

    </div>

</section>


<!-- =====================================================
     SHOP CONTENT
===================================================== -->

<section class="shop-section">


    <!-- PAGE TOP -->

    <div class="shop-top">

        <div>

            <h2>
                <?php echo htmlspecialchars($categoryTitle); ?>
            </h2>

            <p>
                <?php echo htmlspecialchars($categoryDescription); ?>
            </p>

            <span class="shop-product-count" style="display:inline-block; margin-top:8px; font-size:12px; font-weight:600; color:#176b36; background:#eaf5ec; padding:4px 12px; border-radius:15px; border:1px solid #d4ebd7;">
                <i class="fa-solid fa-leaf" style="font-size:11px; margin-right:5px;"></i><?php echo $productCount; ?> <?php echo $productCount === 1 ? 'plant' : 'plants'; ?> available
            </span>

        </div>


        <!-- SORT -->

        <div class="shop-sort">

            <label for="sort">
                Sort By:
            </label>

            <select id="sort">

                <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>
                    Featured
                </option>

                <option value="low-high" <?php echo $sort === 'low-high' ? 'selected' : ''; ?>>
                    Price: Low to High
                </option>

                <option value="high-low" <?php echo $sort === 'high-low' ? 'selected' : ''; ?>>
                    Price: High to Low
                </option>

                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>
                    Newest
                </option>

            </select>

        </div>

    </div>


    <!-- =================================================
         PRODUCTS
    ================================================= -->

    <div class="shop-product-grid">


        <?php if ($productCount > 0) { ?>


            <?php while ($plant = mysqli_fetch_assoc($result)) {
                $plantImg = getCategoryProductImage($categoryFolder, $plant['image']);
            ?>


                <!-- PRODUCT CARD -->

                <div class="shop-product-card">


                    <!-- IMAGE -->

                    <div class="shop-product-image">


                        <span class="shop-badge">
                            <?php echo htmlspecialchars($categoryBadge); ?>
                        </span>


                        <button class="shop-wishlist"
                                type="button"
                                aria-label="Add to wishlist">

                            <i class="fa-regular fa-heart"></i>

                        </button>


                        <a href="product-details.php?id=<?php echo (int) $plant['product_id']; ?>"
                           class="shop-product-image-link"
                           aria-label="View <?php echo htmlspecialchars($plant['product_name']); ?> details">

                            <img
                                src="<?php echo htmlspecialchars($plantImg); ?>"
                                alt="<?php echo htmlspecialchars($plant['product_name']); ?>"
                                onerror="this.onerror=null;this.src='images/logo.png';"
                            >

                        </a>

                    </div>


                    <!-- DETAILS -->

                    <div class="shop-product-info">


                        <p class="shop-product-category">
                            Indoor Plant
                        </p>


                        <h3>
                            <a href="product-details.php?id=<?php echo (int) $plant['product_id']; ?>">
                                <?php echo htmlspecialchars($plant['product_name']); ?>
                            </a>
                        </h3>


                        <!-- RATING -->

                        <div class="shop-rating">

                            <span>
                                ★★★★★
                            </span>

                            <small>
                                (24)
                            </small>

                        </div>


                        <!-- DESCRIPTION -->

                        <p class="shop-description">

                            <?php echo htmlspecialchars($plant['description']); ?>

                        </p>


                        <!-- PRODUCT BOTTOM -->

                        <div class="shop-product-bottom">


                            <strong>

                                From Rs.
                                <?php echo number_format(
                                    (float) $plant['starting_price'],
                                    2
                                ); ?>

                            </strong>


                            <!-- ADD TO CART -->

                            <button type="button"
                                    class="shop-add-cart"
                                    title="Add to Cart"
                                    aria-label="Add <?php echo htmlspecialchars($plant['product_name']); ?> to cart"
                                    data-product="<?php echo htmlspecialchars(json_encode([
                                        'product_id' => (int) $plant['product_id'],
                                        'variation_id' => (int) $plant['first_variation_id'],
                                        'product_name' => $plant['product_name'],
                                        'price' => (float) $plant['starting_price']
                                    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">

                                <i class="fa-solid fa-cart-plus"></i>

                            </button>

                        </div>


                        <!-- STOCK -->

                        <?php if ((int) $plant['total_stock'] > 0) { ?>

                            <small class="stock-available">
                                In Stock
                            </small>

                        <?php } else { ?>

                            <small class="stock-out">
                                Out of Stock
                            </small>

                        <?php } ?>


                    </div>

                </div>


            <?php } ?>


        <?php } else { ?>


            <!-- NO PRODUCTS -->

            <div class="no-products">

                <i class="fa-solid fa-leaf"></i>

                <h3>
                    No Plants Found
                </h3>

                <p>
                    No products are available in this category yet.
                </p>

                <a href="shop.php">
                    View All Plants
                </a>

            </div>


        <?php } ?>


    </div>

</section>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">


    <!-- WATERMARK -->

    <div class="footer-leaf watermark">

        <i class="fa-solid fa-leaf"></i>

    </div>


    <div class="footer-grid">


        <!-- BRAND -->

        <div class="footer-brand">

            <img
                src="images/logo.png"
                alt="Plantora Logo"
                class="footer-logo"
            >

            <p>
                Beautiful indoor plants, stylish pots
                and thoughtful gift packages to
                greenify your space.
            </p>

        </div>


        <!-- QUICK LINKS -->

        <div class="footer-column">

            <h3>
                Quick Links
            </h3>

            <a href="index.php">
                Home
            </a>

            <a href="shop.php">
                Indoor Plants
            </a>

            <a href="shop.php?category=pots">
                Pots
            </a>

            <a href="shop.php?category=packages">
                Gift Packages
            </a>

        </div>


        <!-- CUSTOMER SERVICE -->

        <div class="footer-column">

            <h3>
                Customer Service
            </h3>

            <a href="#">
                Help Center
            </a>

            <a href="#">
                Track Order
            </a>

            <a href="#">
                FAQs
            </a>

            <a href="#">
                Returns & Refunds
            </a>

        </div>


        <!-- CONTACT -->

        <div class="footer-column">

            <h3>
                Contact Us
            </h3>

            <p>
                📍 Colombo, Sri Lanka
            </p>

            <p>
                📞 +94 71 234 5678
            </p>

            <p>
                ✉ support@plantora.com
            </p>

        </div>

    </div>


    <!-- FOOTER BOTTOM -->

    <div class="footer-bottom">

        <p>
            © 2026 Plantora. All rights reserved.
        </p>

        <p>
            Bring Nature Home 🌿
        </p>

    </div>

</footer>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="js/cart.js?v=1"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const sortSelect = document.getElementById("sort");

    if (sortSelect) {

        sortSelect.addEventListener("change", function () {

            const selectedValue = this.value;

            const currentUrl = new URL(window.location.href);

            currentUrl.searchParams.set("sort", selectedValue);

            window.location.href = currentUrl.toString();

        });

    }

});

</script>


</body>
</html>