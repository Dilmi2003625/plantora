<?php

require_once 'config/db.php';
require_once __DIR__ . '/includes/auth.php';

/* =====================================================
   1. DYNAMIC CATEGORY LOADING FROM DATABASE
===================================================== */

$allCategories = [];
$catById = [];
$catSlugMap = [
    // Pre-defined slug aliases for backwards-compatibility
    'cacti' => 6,
    'cacti-succulents' => 6,
    'terracotta-clay-pots' => 8,
    'terracotta-clay' => 8,
    'glazed-ceramic-pots' => 9,
    'glazed-ceramic' => 9,
    'plastic-pots' => 7,
    'fiberglass-fiber-clay' => 10,
    'cement-stone' => 11,
    'low-light' => 1,
    'air-purifying' => 2,
    'easy-care' => 3,
    'flowering' => 4,
    'foliage' => 5,
];

$catQuery = 'SELECT category_id, category_name, description FROM categories ORDER BY category_id ASC';
$catResult = mysqli_query($conn, $catQuery);
if ($catResult) {
    while ($catRow = mysqli_fetch_assoc($catResult)) {
        $cid = (int) $catRow['category_id'];
        $allCategories[] = $catRow;
        $catById[$cid] = $catRow;

        $generatedSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $catRow['category_name']), '-'));
        if (!isset($catSlugMap[$generatedSlug])) {
            $catSlugMap[$generatedSlug] = $cid;
        }
    }
}

// Category folder mapping for image resolution
$catFolderMap = [
    1 => 'low-light',
    2 => 'air-purifying',
    3 => 'easy-care',
    4 => 'flowering',
    5 => 'foliage',
    6 => 'cacti-succulents',
    7 => 'pots/plastic',
    8 => 'pots/terracotta-clay',
    9 => 'pots/glazed-ceramic',
    10 => 'pots/fiberglass-fiber-clay',
    11 => 'pots/cement-stone',
];

/* =====================================================
   2. FILTER PARAMETERS PARSING & VALIDATION
===================================================== */

$search = trim((string) ($_GET['search'] ?? ''));
$categoryParam = trim((string) ($_GET['category'] ?? ''));
$productType = trim((string) ($_GET['product_type'] ?? 'all'));
$priceRange = trim((string) ($_GET['price_range'] ?? 'all'));
$stock = trim((string) ($_GET['stock'] ?? 'all'));
$careLevel = trim((string) ($_GET['care_level'] ?? 'all'));
$material = trim((string) ($_GET['material'] ?? 'all'));
$color = trim((string) ($_GET['color'] ?? 'all'));
$size = trim((string) ($_GET['size'] ?? 'all'));
$sort = trim((string) ($_GET['sort'] ?? 'default'));

// Direct min_price / max_price support
$minPrice = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? (float) $_GET['min_price'] : null;
$maxPrice = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? (float) $_GET['max_price'] : null;

// Parse price_range preset
if (!empty($priceRange) && $priceRange !== 'all') {
    switch ($priceRange) {
        case 'under-1000':
            $maxPrice = 1000.0;
            break;
        case '1000-2500':
            $minPrice = 1000.0;
            $maxPrice = 2500.0;
            break;
        case '2500-5000':
            $minPrice = 2500.0;
            $maxPrice = 5000.0;
            break;
        case 'above-5000':
            $minPrice = 5000.0;
            break;
    }
}

// Determine active category details
$selectedCatId = null;
$isPotsGroup = false;
$isPlantsGroup = false;
$isPackagesGroup = false;

if ($categoryParam !== '' && $categoryParam !== 'all') {
    if ($categoryParam === 'pots') {
        $isPotsGroup = true;
    } elseif ($categoryParam === 'plants') {
        $isPlantsGroup = true;
    } elseif ($categoryParam === 'packages') {
        $isPackagesGroup = true;
    } elseif (is_numeric($categoryParam) && isset($catById[(int) $categoryParam])) {
        $selectedCatId = (int) $categoryParam;
    } elseif (isset($catSlugMap[$categoryParam])) {
        $selectedCatId = $catSlugMap[$categoryParam];
    }
}

// Category Hero Configuration
$categoryData = [
    'low-light' => [
        'id' => 1,
        'title' => 'Low-Light Plants',
        'description' => 'Beautiful plants that thrive in areas with limited sunlight.',
        'breadcrumb' => 'Low-Light Plants',
        'image' => 'images/category-banners/low-light-hero.jpg',
    ],
    'air-purifying' => [
        'id' => 2,
        'title' => 'Air-Purifying Plants',
        'description' => 'Bring freshness and cleaner air into your indoor space.',
        'breadcrumb' => 'Air-Purifying Plants',
        'image' => 'images/category-banners/air-purifying-hero.jpg',
    ],
    'easy-care' => [
        'id' => 3,
        'title' => 'Easy-Care Plants',
        'description' => 'Beautiful indoor plants that need less effort and simple care.',
        'breadcrumb' => 'Easy-Care Plants',
        'image' => 'images/category-banners/easy-care-hero.jpg',
    ],
    'flowering' => [
        'id' => 4,
        'title' => 'Flowering Plants',
        'description' => 'Add natural color, beauty, and freshness to your home.',
        'breadcrumb' => 'Flowering Plants',
        'image' => 'images/category-banners/flowering-hero.jpg',
    ],
    'foliage' => [
        'id' => 5,
        'title' => 'Foliage Plants',
        'description' => 'Discover lush green leaves and lasting indoor beauty.',
        'breadcrumb' => 'Foliage Plants',
        'image' => 'images/category-banners/foliage-hero.jpg',
    ],
    'cacti-succulents' => [
        'id' => 6,
        'title' => 'Cacti & Succulents',
        'description' => 'Small plants with unique shapes, textures, and big personality.',
        'breadcrumb' => 'Cacti & Succulents',
        'image' => 'images/category-banners/cacti-succulents-hero.jpg',
    ],
];

// Alias mapping
$categoryData['cacti'] = $categoryData['cacti-succulents'];

$catIdToHeroSlug = [
    1 => 'low-light',
    2 => 'air-purifying',
    3 => 'easy-care',
    4 => 'flowering',
    5 => 'foliage',
    6 => 'cacti-succulents',
];

// Determine active hero configuration
$activeHeroKey = null;
if (isset($categoryData[$categoryParam])) {
    $activeHeroKey = $categoryParam;
} elseif ($selectedCatId !== null && isset($catIdToHeroSlug[$selectedCatId])) {
    $activeHeroKey = $catIdToHeroSlug[$selectedCatId];
}

if ($activeHeroKey && isset($categoryData[$activeHeroKey])) {
    $heroConfig = $categoryData[$activeHeroKey];
    $categoryTitle = $heroConfig['title'];
    $categoryDescription = $heroConfig['description'];
    $categoryFolder = $catFolderMap[$selectedCatId ?? $heroConfig['id']] ?? 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $heroConfig['title'];
    $heroDescription = $heroConfig['description'];
    $heroBreadcrumb = $heroConfig['breadcrumb'];
    $heroImagePath = $heroConfig['image'];
} elseif ($selectedCatId && isset($catById[$selectedCatId])) {
    $categoryTitle = $catById[$selectedCatId]['category_name'];
    $categoryDescription = $catById[$selectedCatId]['description'] ?: 'Discover our curated selection of ' . $categoryTitle . '.';
    $categoryFolder = $catFolderMap[$selectedCatId] ?? 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = $categoryTitle;
    $heroImagePath = null;
} elseif ($isPotsGroup) {
    $categoryTitle = 'Pots & Planters';
    $categoryDescription = 'Stylish and durable pots to showcase your greenery in Small (10 cm), Medium (15 cm), and Large (20 cm).';
    $categoryFolder = 'pots';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = $categoryTitle;
    $heroImagePath = null;
} elseif ($isPlantsGroup) {
    $categoryTitle = 'Indoor Plants';
    $categoryDescription = 'Lush, air-purifying, and low-maintenance indoor plants to greenify your home or office.';
    $categoryFolder = 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = $categoryTitle;
    $heroImagePath = null;
} elseif ($isPackagesGroup) {
    $categoryTitle = 'Gift Packages';
    $categoryDescription = 'Thoughtfully curated plant and pot packages perfect for gifting.';
    $categoryFolder = 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = $categoryTitle;
    $heroImagePath = null;
} elseif ($search !== '') {
    $categoryTitle = 'Search Results';
    $categoryDescription = 'Showing matching products for "' . htmlspecialchars($search) . '".';
    $categoryFolder = 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = $categoryTitle;
    $heroImagePath = null;
} else {
    $categoryTitle = 'All Plants & Pots';
    $categoryDescription = 'Explore our complete collection of beautiful indoor plants and handcrafted pots.';
    $categoryFolder = 'products';
    $heroEyebrow = 'PLANTORA COLLECTION';
    $heroTitle = $categoryTitle;
    $heroDescription = $categoryDescription;
    $heroBreadcrumb = '';
    $heroImagePath = null;
}

// Background image & graceful fallback resolution
$heroBgStyle = '';
if (!empty($heroImagePath) && file_exists(__DIR__ . '/' . $heroImagePath)) {
    $heroBgStyle = "background-image: linear-gradient(rgba(10, 36, 19, 0.58), rgba(10, 36, 19, 0.68)), url('" . htmlspecialchars($heroImagePath) . "');";
} else {
    // Fallback: graceful dark green Plantora gradient, no broken images or warnings
    $heroBgStyle = "background: linear-gradient(135deg, #103b22 0%, #0c2b19 100%);";
}

/* =====================================================
   3. DYNAMIC SQL QUERY WITH PREPARED STATEMENTS
===================================================== */

$whereConditions = [];
$havingConditions = [];
$bindTypes = '';
$bindParams = [];

// 3.1 Search condition
if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    // Stemming for cactus / cacti
    $stemTerm = (strtolower($search) === 'cactus') ? '%cact%' : $searchTerm;

    $whereConditions[] = '(p.product_name LIKE ? OR p.description LIKE ? OR c.category_name LIKE ? OR p.product_type LIKE ? OR p.product_name LIKE ? OR c.category_name LIKE ?)';
    $bindTypes .= 'ssssss';
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
    $bindParams[] = $stemTerm;
    $bindParams[] = $stemTerm;
}

// 3.2 Category condition
if ($selectedCatId !== null) {
    $whereConditions[] = 'p.category_id = ?';
    $bindTypes .= 'i';
    $bindParams[] = $selectedCatId;
} elseif ($isPotsGroup) {
    $whereConditions[] = '(p.category_id >= 7 OR p.product_type = \'Pot\')';
} elseif ($isPlantsGroup) {
    $whereConditions[] = '(p.category_id <= 6 OR p.product_type = \'Plant\')';
} elseif ($isPackagesGroup) {
    $whereConditions[] = 'p.product_type = \'Package\'';
}

// 3.3 Product Type condition
if ($productType !== '' && $productType !== 'all') {
    $pt = strtolower($productType);
    if ($pt === 'plant' || $pt === 'plants') {
        $whereConditions[] = '(p.product_type = \'Plant\' OR (p.category_id <= 6 AND (p.product_type = \'\' OR p.product_type IS NULL)))';
    } elseif ($pt === 'pot' || $pt === 'pots') {
        $whereConditions[] = '(p.product_type = \'Pot\' OR p.category_id >= 7)';
    } elseif ($pt === 'package' || $pt === 'packages') {
        $whereConditions[] = 'p.product_type = \'Package\'';
    }
}

// 3.4 Plant Care Level condition (applies only to plant products)
if ($careLevel !== '' && $careLevel !== 'all') {
    $whereConditions[] = '(p.care_level = ? AND (p.product_type = \'Plant\' OR p.category_id <= 6))';
    $bindTypes .= 's';
    $bindParams[] = $careLevel;
}

// 3.5 Pot Material condition (applies only to pot products)
if ($material !== '' && $material !== 'all') {
    $whereConditions[] = '(p.material = ? AND (p.product_type = \'Pot\' OR p.category_id >= 7))';
    $bindTypes .= 's';
    $bindParams[] = $material;
}

// 3.6 Pot Color condition (applies only to pot products)
if ($color !== '' && $color !== 'all') {
    $whereConditions[] = 'EXISTS (SELECT 1 FROM product_variations pv_col WHERE pv_col.product_id = p.product_id AND pv_col.color = ?)';
    $bindTypes .= 's';
    $bindParams[] = $color;
}

// 3.7 Pot Size condition (centimeters)
if ($size !== '' && $size !== 'all') {
    $whereConditions[] = 'EXISTS (SELECT 1 FROM product_variations pv_sz WHERE pv_sz.product_id = p.product_id AND pv_sz.size = ?)';
    $bindTypes .= 's';
    $bindParams[] = $size;
}

// 3.8 Price condition (HAVING MIN(pv.price))
if ($minPrice !== null) {
    $havingConditions[] = 'MIN(pv.price) >= ?';
    $bindTypes .= 'd';
    $bindParams[] = $minPrice;
}
if ($maxPrice !== null) {
    $havingConditions[] = 'MIN(pv.price) <= ?';
    $bindTypes .= 'd';
    $bindParams[] = $maxPrice;
}

// 3.9 Stock condition (HAVING SUM(pv.stock_quantity))
if ($stock === 'in-stock') {
    $havingConditions[] = 'SUM(pv.stock_quantity) > 0';
} elseif ($stock === 'out-of-stock') {
    $havingConditions[] = 'SUM(pv.stock_quantity) = 0';
}

// 3.10 Sorting Whitelist
$sortOrder = 'p.product_id ASC';
switch ($sort) {
    case 'price-low':
    case 'low-high':
        $sortOrder = 'starting_price ASC, p.product_id ASC';
        break;
    case 'price-high':
    case 'high-low':
        $sortOrder = 'starting_price DESC, p.product_id ASC';
        break;
    case 'name-asc':
    case 'az':
        $sortOrder = 'p.product_name ASC';
        break;
    case 'name-desc':
    case 'za':
        $sortOrder = 'p.product_name DESC';
        break;
    case 'newest':
        $sortOrder = 'p.created_at DESC, p.product_id DESC';
        break;
    default:
        $sortOrder = 'p.product_id ASC';
        break;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
$havingClause = !empty($havingConditions) ? 'HAVING ' . implode(' AND ', $havingConditions) : '';

$sql = "
    SELECT
        p.product_id,
        p.category_id,
        p.product_name,
        p.product_type,
        p.description,
        p.care_instructions,
        p.care_level,
        p.material,
        p.image,
        c.category_name,
        MIN(pv.price) AS starting_price,
        SUM(pv.stock_quantity) AS total_stock,
        MIN(pv.variation_id) AS first_variation_id,
        GROUP_CONCAT(DISTINCT pv.color ORDER BY pv.variation_id SEPARATOR ', ') AS available_colors
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.category_id
    INNER JOIN product_variations pv
        ON p.product_id = pv.product_id
    {$whereClause}
    GROUP BY
        p.product_id,
        p.category_id,
        p.product_name,
        p.product_type,
        p.description,
        p.care_instructions,
        p.care_level,
        p.material,
        p.image,
        c.category_name
    {$havingClause}
    ORDER BY {$sortOrder}
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    exit('Database query preparation failed: ' . mysqli_error($conn));
}

if (!empty($bindParams)) {
    mysqli_stmt_bind_param($stmt, $bindTypes, ...$bindParams);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$productCount = mysqli_num_rows($result);

/* =====================================================
   4. IMAGE RESOLUTION HELPER
===================================================== */

if (!function_exists('getCategoryProductImage')) {
    function getCategoryProductImage(string $categoryFolder, ?string $imageFilename): string
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

        // 3. Check categories folder
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

        // 5. Products folder
        $prodPath = "images/products/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $prodPath)) {
            return $prodPath;
        }

        // 6. Base images folder
        $basePath = "images/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $basePath)) {
            return $basePath;
        }

        // 7. Low-light folder
        $lowLightPath = "images/low-light/{$imageFilename}";
        if (file_exists(__DIR__ . '/' . $lowLightPath)) {
            return $lowLightPath;
        }

        return 'images/logo.png';
    }
}

/* =====================================================
   5. ACTIVE FILTERS & URL BUILDER
===================================================== */

function buildShopUrl(array $overrides = [], array $removals = []): string
{
    $params = $_GET;
    foreach ($removals as $k) {
        unset($params[$k]);
    }
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '' || $v === 'all') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $query = http_build_query($params);
    return 'shop.php' . ($query ? '?' . $query : '');
}

$activeFilters = [];

if ($search !== '') {
    $activeFilters[] = [
        'label' => 'Search: "' . htmlspecialchars($search) . '"',
        'remove_url' => buildShopUrl([], ['search'])
    ];
}

if ($categoryParam !== '' && $categoryParam !== 'all') {
    $activeFilters[] = [
        'label' => 'Category: ' . htmlspecialchars($categoryTitle),
        'remove_url' => buildShopUrl([], ['category'])
    ];
}

if ($productType !== '' && $productType !== 'all') {
    $activeFilters[] = [
        'label' => 'Type: ' . htmlspecialchars(ucfirst($productType)),
        'remove_url' => buildShopUrl([], ['product_type'])
    ];
}

if ($priceRange !== '' && $priceRange !== 'all') {
    $priceLabels = [
        'under-1000' => 'Under Rs. 1,000',
        '1000-2500' => 'Rs. 1,000 - Rs. 2,500',
        '2500-5000' => 'Rs. 2,500 - Rs. 5,000',
        'above-5000' => 'Above Rs. 5,000'
    ];
    $activeFilters[] = [
        'label' => 'Price: ' . ($priceLabels[$priceRange] ?? $priceRange),
        'remove_url' => buildShopUrl([], ['price_range', 'min_price', 'max_price'])
    ];
} elseif ($minPrice !== null || $maxPrice !== null) {
    $customLabel = 'Price: ';
    if ($minPrice !== null && $maxPrice !== null) {
        $customLabel .= 'Rs. ' . number_format($minPrice) . ' - Rs. ' . number_format($maxPrice);
    } elseif ($minPrice !== null) {
        $customLabel .= 'Above Rs. ' . number_format($minPrice);
    } else {
        $customLabel .= 'Under Rs. ' . number_format($maxPrice);
    }
    $activeFilters[] = [
        'label' => $customLabel,
        'remove_url' => buildShopUrl([], ['price_range', 'min_price', 'max_price'])
    ];
}

if ($stock !== '' && $stock !== 'all') {
    $stockLabels = [
        'in-stock' => 'In Stock',
        'out-of-stock' => 'Out of Stock'
    ];
    $activeFilters[] = [
        'label' => 'Stock: ' . ($stockLabels[$stock] ?? $stock),
        'remove_url' => buildShopUrl([], ['stock'])
    ];
}

if ($careLevel !== '' && $careLevel !== 'all') {
    $activeFilters[] = [
        'label' => 'Care: ' . htmlspecialchars($careLevel),
        'remove_url' => buildShopUrl([], ['care_level'])
    ];
}

if ($material !== '' && $material !== 'all') {
    $activeFilters[] = [
        'label' => 'Material: ' . htmlspecialchars($material),
        'remove_url' => buildShopUrl([], ['material'])
    ];
}

if ($color !== '' && $color !== 'all') {
    $activeFilters[] = [
        'label' => 'Color: ' . htmlspecialchars($color),
        'remove_url' => buildShopUrl([], ['color'])
    ];
}

if ($size !== '' && $size !== 'all') {
    $activeFilters[] = [
        'label' => 'Size: ' . htmlspecialchars($size),
        'remove_url' => buildShopUrl([], ['size'])
    ];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($categoryTitle); ?> | Plantora</title>

    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css?v=6">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="top-bar">
    <div>🌿 Bring Nature Into Your Home with Plantora</div>
    <div>🚚 Free Delivery on orders over Rs. 5000</div>
    <div>Help Center &nbsp; | &nbsp; Track Order &nbsp; | &nbsp; FAQs</div>
</div>

<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">
    <!-- LOGO -->
    <a href="index.php" class="logo">
        <img src="images/logo.png" alt="Plantora">
    </a>

    <!-- NAVIGATION -->
    <nav class="navbar" id="navbar">
        <a href="index.php">Home</a>

        <!-- INDOOR PLANTS -->
        <div class="nav-dropdown">
            <a href="shop.php?category=plants" class="dropdown-title">
                Indoor Plants <span class="arrow">⌄</span>
            </a>
            <div class="dropdown-menu">
                <a href="shop.php?category=low-light">🌿 Low-Light Plants</a>
                <a href="shop.php?category=air-purifying">🌱 Air-Purifying Plants</a>
                <a href="shop.php?category=easy-care">🪴 Easy-Care Plants</a>
                <a href="shop.php?category=flowering">🌸 Flowering Plants</a>
                <a href="shop.php?category=foliage">🍃 Foliage Plants</a>
                <a href="shop.php?category=cacti-succulents">🌵 Cacti & Succulents</a>
            </div>
        </div>

        <!-- POTS -->
        <div class="nav-dropdown">
            <a href="shop.php?category=pots" class="dropdown-title">
                Pots <span class="arrow">⌄</span>
            </a>
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

    <!-- HEADER ACTIONS -->
    <?php include __DIR__ . '/includes/header_actions.php'; ?>
</header>

<!-- =====================================================
     CATEGORY PAGE HERO BANNER
===================================================== -->

<section class="category-hero shop-hero" style="<?php echo $heroBgStyle; ?>">
    <div class="category-hero-overlay shop-hero-content">
        <span class="category-eyebrow">PLANTORA COLLECTION</span>
        <h1 class="category-hero-title"><?php echo htmlspecialchars($heroTitle); ?></h1>
        <?php if (!empty($heroDescription)) { ?>
            <p class="category-hero-desc"><?php echo htmlspecialchars($heroDescription); ?></p>
        <?php } ?>
        <div class="category-hero-breadcrumb breadcrumb">
            <a href="index.php">Home</a>
            <span class="sep">/</span>
            <a href="shop.php">Shop</a>
            <?php if (!empty($heroBreadcrumb) && $heroBreadcrumb !== 'All Plants & Pots') { ?>
                <span class="sep">/</span>
                <span class="current"><?php echo htmlspecialchars($heroBreadcrumb); ?></span>
            <?php } ?>
        </div>
    </div>
</section>

<!-- =====================================================
     TWO-COLUMN SHOP SECTION
===================================================== -->

<section class="shop-section">

    <!-- MOBILE EXPANDABLE CONTROLS BAR -->
    <div class="shop-mobile-controls">
        <div class="shop-mobile-search-row">
            <input type="text"
                   id="mobile-search-input"
                   class="sidebar-input"
                   placeholder="Search plants, pots..."
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="button" id="mobile-search-btn" class="sidebar-search-btn" aria-label="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
        <div class="shop-mobile-actions-row">
            <button type="button" class="shop-mobile-filter-toggle" id="mobile-filter-toggle">
                <i class="fa-solid fa-sliders"></i>
                <span>Filters ▾</span>
                <?php if (!empty($activeFilters)) { ?>
                    <span class="shop-filter-count-badge" style="background:#176b36; color:#fff; border-radius:10px; padding:1px 6px; font-size:11px;"><?php echo count($activeFilters); ?></span>
                <?php } ?>
            </button>
            <select id="mobile-sort-select" class="shop-mobile-sort-select">
                <option value="default" <?php echo ($sort === 'default' || $sort === 'featured') ? 'selected' : ''; ?>>Sort: Default</option>
                <option value="price-low" <?php echo ($sort === 'price-low' || $sort === 'low-high') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price-high" <?php echo ($sort === 'price-high' || $sort === 'high-low') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="name-asc" <?php echo ($sort === 'name-asc' || $sort === 'az') ? 'selected' : ''; ?>>Name: A to Z</option>
                <option value="name-desc" <?php echo ($sort === 'name-desc' || $sort === 'za') ? 'selected' : ''; ?>>Name: Z to A</option>
                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
            </select>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN LAYOUT -->
    <div class="shop-two-col-layout">

        <!-- ==============================================
             LEFT FILTER SIDEBAR
        ============================================== -->
        <aside class="shop-sidebar" id="shop-sidebar">
            <div class="shop-sidebar-header">
                <h3><i class="fa-solid fa-sliders"></i> Filters</h3>
                <?php if (!empty($activeFilters)) { ?>
                    <a href="shop.php" class="shop-sidebar-clear-top">Clear All</a>
                <?php } ?>
            </div>

            <form action="shop.php" method="GET" id="shop-sidebar-form">
                <!-- Preserve sort in form -->
                <input type="hidden" name="sort" id="sidebar-sort-input" value="<?php echo htmlspecialchars($sort); ?>">

                <!-- 1. SEARCH INPUT -->
                <div class="sidebar-group">
                    <label for="sidebar-search">Search Products</label>
                    <div class="sidebar-search-row">
                        <input type="text"
                               name="search"
                               id="sidebar-search"
                               class="sidebar-input"
                               placeholder="Search plants, pots..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="sidebar-search-btn" aria-label="Search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                </div>

                <!-- SECTION: COMMON FILTERS -->
                <div class="sidebar-section-title">
                    <i class="fa-solid fa-layer-group"></i> Common Filters
                </div>

                <!-- Category Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-category">Category</label>
                    <select name="category" id="sidebar-category" class="sidebar-select">
                        <option value="all" <?php echo ($categoryParam === '' || $categoryParam === 'all') ? 'selected' : ''; ?>>All Categories</option>
                        <optgroup label="Indoor Plants">
                            <option value="plants" <?php echo ($categoryParam === 'plants') ? 'selected' : ''; ?>>All Indoor Plants</option>
                            <?php foreach ($allCategories as $catItem) { 
                                if ((int)$catItem['category_id'] <= 6) { 
                                    $isSel = ($selectedCatId === (int)$catItem['category_id']);
                            ?>
                                <option value="<?php echo (int)$catItem['category_id']; ?>" <?php echo $isSel ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($catItem['category_name']); ?>
                                </option>
                            <?php } } ?>
                        </optgroup>
                        <optgroup label="Pots & Planters">
                            <option value="pots" <?php echo ($isPotsGroup) ? 'selected' : ''; ?>>All Pots & Planters</option>
                            <?php foreach ($allCategories as $catItem) { 
                                if ((int)$catItem['category_id'] >= 7) { 
                                    $isSel = ($selectedCatId === (int)$catItem['category_id']);
                            ?>
                                <option value="<?php echo (int)$catItem['category_id']; ?>" <?php echo $isSel ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($catItem['category_name']); ?>
                                </option>
                            <?php } } ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Product Type Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-type">Product Type</label>
                    <select name="product_type" id="sidebar-type" class="sidebar-select">
                        <option value="all" <?php echo ($productType === 'all' || $productType === '') ? 'selected' : ''; ?>>All Products</option>
                        <option value="Plant" <?php echo (strcasecmp($productType, 'Plant') === 0 || strcasecmp($productType, 'Plants') === 0) ? 'selected' : ''; ?>>Plants</option>
                        <option value="Pot" <?php echo (strcasecmp($productType, 'Pot') === 0 || strcasecmp($productType, 'Pots') === 0) ? 'selected' : ''; ?>>Pots</option>
                        <option value="Package" <?php echo (strcasecmp($productType, 'Package') === 0 || strcasecmp($productType, 'Packages') === 0) ? 'selected' : ''; ?>>Packages</option>
                    </select>
                </div>

                <!-- Price Range Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-price">Price Range</label>
                    <select name="price_range" id="sidebar-price" class="sidebar-select">
                        <option value="all" <?php echo ($priceRange === 'all' && $minPrice === null && $maxPrice === null) ? 'selected' : ''; ?>>All Prices</option>
                        <option value="under-1000" <?php echo ($priceRange === 'under-1000') ? 'selected' : ''; ?>>Under Rs. 1,000</option>
                        <option value="1000-2500" <?php echo ($priceRange === '1000-2500') ? 'selected' : ''; ?>>Rs. 1,000 - Rs. 2,500</option>
                        <option value="2500-5000" <?php echo ($priceRange === '2500-5000') ? 'selected' : ''; ?>>Rs. 2,500 - Rs. 5,000</option>
                        <option value="above-5000" <?php echo ($priceRange === 'above-5000') ? 'selected' : ''; ?>>Above Rs. 5,000</option>
                    </select>
                </div>

                <!-- Stock Availability Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-stock">Stock Availability</label>
                    <select name="stock" id="sidebar-stock" class="sidebar-select">
                        <option value="all" <?php echo ($stock === 'all' || $stock === '') ? 'selected' : ''; ?>>All Products</option>
                        <option value="in-stock" <?php echo ($stock === 'in-stock') ? 'selected' : ''; ?>>In Stock</option>
                        <option value="out-of-stock" <?php echo ($stock === 'out-of-stock') ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>

                <!-- SECTION: PLANT OPTIONS -->
                <div class="sidebar-section-title">
                    <i class="fa-solid fa-leaf"></i> Plant Filters
                </div>

                <!-- Plant Care Level Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-care">Plant Care Level</label>
                    <select name="care_level" id="sidebar-care" class="sidebar-select">
                        <option value="all" <?php echo ($careLevel === 'all' || $careLevel === '') ? 'selected' : ''; ?>>All Care Levels</option>
                        <option value="Very Easy" <?php echo ($careLevel === 'Very Easy') ? 'selected' : ''; ?>>Very Easy</option>
                        <option value="Easy" <?php echo ($careLevel === 'Easy') ? 'selected' : ''; ?>>Easy</option>
                        <option value="Moderate" <?php echo ($careLevel === 'Moderate') ? 'selected' : ''; ?>>Moderate</option>
                        <option value="High Care" <?php echo ($careLevel === 'High Care') ? 'selected' : ''; ?>>High Care</option>
                    </select>
                </div>

                <!-- SECTION: POT SPECIFICATIONS -->
                <div class="sidebar-section-title">
                    <i class="fa-solid fa-shapes"></i> Pot Filters
                </div>

                <!-- Pot Material Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-material">Pot Material</label>
                    <select name="material" id="sidebar-material" class="sidebar-select">
                        <option value="all" <?php echo ($material === 'all' || $material === '') ? 'selected' : ''; ?>>All Materials</option>
                        <option value="Plastic" <?php echo ($material === 'Plastic') ? 'selected' : ''; ?>>Plastic</option>
                        <option value="Terracotta" <?php echo ($material === 'Terracotta') ? 'selected' : ''; ?>>Terracotta</option>
                        <option value="Clay" <?php echo ($material === 'Clay') ? 'selected' : ''; ?>>Clay</option>
                        <option value="Ceramic" <?php echo ($material === 'Ceramic') ? 'selected' : ''; ?>>Ceramic</option>
                        <option value="Fiberglass" <?php echo ($material === 'Fiberglass') ? 'selected' : ''; ?>>Fiberglass</option>
                        <option value="Fiber Clay" <?php echo ($material === 'Fiber Clay') ? 'selected' : ''; ?>>Fiber Clay</option>
                        <option value="Cement" <?php echo ($material === 'Cement') ? 'selected' : ''; ?>>Cement</option>
                        <option value="Stone" <?php echo ($material === 'Stone') ? 'selected' : ''; ?>>Stone</option>
                    </select>
                </div>

                <!-- Pot Color Swatches Filter -->
                <div class="sidebar-group">
                    <label>Pot Color</label>
                    <div class="pot-color-swatches-filter">
                        <?php
                        $availablePotColors = [
                            'all' => ['label' => 'All Colors', 'hex' => 'transparent', 'is_all' => true],
                            'Green' => ['label' => 'Green', 'hex' => '#2e7d32'],
                            'Black' => ['label' => 'Black', 'hex' => '#222222'],
                            'White' => ['label' => 'White', 'hex' => '#f5f5f5', 'border' => true],
                            'Gray' => ['label' => 'Gray', 'hex' => '#808080'],
                            'Terracotta' => ['label' => 'Terracotta', 'hex' => '#c86443'],
                            'Blue' => ['label' => 'Blue', 'hex' => '#1e55a0'],
                            'Beige' => ['label' => 'Beige', 'hex' => '#d4c6b2'],
                            'Natural Terracotta' => ['label' => 'Natural Terracotta', 'hex' => '#c86443'],
                            'Charcoal' => ['label' => 'Charcoal', 'hex' => '#2a2d30'],
                        ];
                        foreach ($availablePotColors as $colKey => $colData) {
                            $isColActive = ($color === $colKey) || ($colKey === 'all' && ($color === '' || $color === 'all'));
                        ?>
                            <label class="pot-color-swatch-item" title="<?php echo htmlspecialchars($colData['label']); ?>">
                                <input type="radio" name="color" value="<?php echo htmlspecialchars($colKey); ?>" <?php echo $isColActive ? 'checked' : ''; ?>>
                                <?php if (!empty($colData['is_all'])) { ?>
                                    <span class="pot-color-swatch-dot" style="background:#fff; border: 1.5px dashed #8fa394; display:flex; align-items:center; justify-content:center; font-size:10px; color:#556e5d; font-weight:700;">✕</span>
                                <?php } else { ?>
                                    <span class="pot-color-swatch-dot" style="background:<?php echo $colData['hex']; ?>; <?php echo !empty($colData['border']) ? 'border: 1px solid #ccc;' : ''; ?>"></span>
                                <?php } ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <!-- Pot Size (Centimeters) Filter -->
                <div class="sidebar-group">
                    <label for="sidebar-size">Pot Size (cm)</label>
                    <select name="size" id="sidebar-size" class="sidebar-select">
                        <option value="all" <?php echo ($size === 'all' || $size === '') ? 'selected' : ''; ?>>All Sizes</option>
                        <option value="Small (10 cm)" <?php echo ($size === 'Small (10 cm)') ? 'selected' : ''; ?>>Small (10 cm)</option>
                        <option value="Medium (15 cm)" <?php echo ($size === 'Medium (15 cm)') ? 'selected' : ''; ?>>Medium (15 cm)</option>
                        <option value="Large (20 cm)" <?php echo ($size === 'Large (20 cm)') ? 'selected' : ''; ?>>Large (20 cm)</option>
                    </select>
                </div>

                <!-- Sidebar Action Buttons -->
                <div class="sidebar-actions">
                    <button type="submit" class="sidebar-apply-btn">
                        <i class="fa-solid fa-filter"></i> Apply Filters
                    </button>
                    <a href="shop.php" class="sidebar-clear-btn">
                        <i class="fa-solid fa-rotate-left"></i> Clear All Filters
                    </a>
                </div>
            </form>
        </aside>

        <!-- ==============================================
             RIGHT PRODUCT RESULTS COLUMN
        ============================================== -->
        <div class="shop-results-column">

            <!-- RESULTS HEADER & SORT DROPDOWN -->
            <div class="shop-results-header">
                <div class="shop-results-header-left">
                    <h2><?php echo htmlspecialchars($categoryTitle); ?></h2>
                    <p><?php echo htmlspecialchars($categoryDescription); ?></p>
                    <span class="shop-results-count">
                        <i class="fa-solid fa-leaf" style="font-size:11px; margin-right:4px;"></i>
                        Showing <?php echo $productCount; ?> <?php echo ($productCount === 1) ? 'product' : 'products'; ?>
                    </span>
                </div>

                <div class="shop-sort-wrap">
                    <label for="desktop-sort">Sort By:</label>
                    <select id="desktop-sort" class="shop-sort-select">
                        <option value="default" <?php echo ($sort === 'default' || $sort === 'featured') ? 'selected' : ''; ?>>Default (Featured)</option>
                        <option value="price-low" <?php echo ($sort === 'price-low' || $sort === 'low-high') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price-high" <?php echo ($sort === 'price-high' || $sort === 'high-low') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name-asc" <?php echo ($sort === 'name-asc' || $sort === 'az') ? 'selected' : ''; ?>>Name: A to Z</option>
                        <option value="name-desc" <?php echo ($sort === 'name-desc' || $sort === 'za') ? 'selected' : ''; ?>>Name: Z to A</option>
                        <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
                    </select>
                </div>
            </div>

            <!-- ACTIVE FILTERS TAGS -->
            <?php if (!empty($activeFilters)) { ?>
                <div class="shop-active-filters-row">
                    <span class="active-title">Active Filters:</span>
                    <?php foreach ($activeFilters as $filterItem) { ?>
                        <span class="shop-filter-pill">
                            <?php echo $filterItem['label']; ?>
                            <a href="<?php echo htmlspecialchars($filterItem['remove_url']); ?>" class="shop-filter-pill-remove" title="Remove filter" aria-label="Remove filter">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        </span>
                    <?php } ?>
                    <a href="shop.php" class="shop-clear-all-link">Clear All</a>
                </div>
            <?php } ?>

            <!-- PRODUCT CARDS GRID -->
            <div class="shop-product-grid">

                <?php if ($productCount > 0) { ?>

                    <?php while ($plant = mysqli_fetch_assoc($result)) {
                        $isPotCard = (isset($plant['product_type']) && strcasecmp(trim($plant['product_type']), 'pot') === 0) 
                            || in_array((int)($plant['category_id'] ?? 0), [7, 8, 9, 10, 11], true);
                        
                        $plantFolder = $catFolderMap[(int)($plant['category_id'] ?? 0)] ?? $categoryFolder;
                        $plantImg = getCategoryProductImage($plantFolder, $plant['image']);
                        $colorsList = !empty($plant['available_colors']) ? array_map('trim', explode(',', $plant['available_colors'])) : [];
                        $isOutOfStock = ((int) $plant['total_stock'] <= 0);
                        $cardBadge = !empty($plant['category_name']) ? $plant['category_name'] : ($isPotCard ? 'Pots' : 'Plants');
                    ?>

                        <!-- PRODUCT CARD -->
                        <div class="shop-product-card <?php echo $isOutOfStock ? 'out-of-stock' : ''; ?>">

                            <!-- IMAGE -->
                            <div class="shop-product-image">
                                <?php if ($isOutOfStock) { ?>
                                    <span class="shop-badge out-of-stock">Out of Stock</span>
                                <?php } else { ?>
                                    <span class="shop-badge"><?php echo htmlspecialchars($cardBadge); ?></span>
                                <?php } ?>

                                <button class="shop-wishlist" type="button" aria-label="Add to wishlist">
                                    <i class="fa-regular fa-heart"></i>
                                </button>

                                <a href="product-details.php?id=<?php echo (int) $plant['product_id']; ?>"
                                   class="shop-product-image-link"
                                   aria-label="View <?php echo htmlspecialchars($plant['product_name']); ?> details">
                                    <img src="<?php echo htmlspecialchars($plantImg); ?>"
                                         alt="<?php echo htmlspecialchars($plant['product_name']); ?>"
                                         onerror="this.onerror=null;this.src='images/logo.png';">
                                </a>
                            </div>

                            <!-- DETAILS -->
                            <div class="shop-product-info">
                                <p class="shop-product-category">
                                    <?php echo htmlspecialchars($plant['category_name'] ?: ($isPotCard ? 'Plant Pot' : 'Indoor Plant')); ?>
                                </p>

                                <h3>
                                    <a href="product-details.php?id=<?php echo (int) $plant['product_id']; ?>">
                                        <?php echo htmlspecialchars($plant['product_name']); ?>
                                    </a>
                                </h3>

                                <!-- RATING -->
                                <div class="shop-rating">
                                    <span>★★★★★</span>
                                    <small>(24)</small>
                                </div>

                                <!-- AVAILABLE COLORS IF POT -->
                                <?php if ($isPotCard && !empty($colorsList)) { 
                                    $colorHexMap = [
                                        'green' => '#2e7d32',
                                        'black' => '#222222',
                                        'white' => '#f5f5f5',
                                        'terracotta' => '#c86443',
                                        'natural terracotta' => '#c86443',
                                        'rustic orange' => '#d4662c',
                                        'light brown' => '#b27a52',
                                        'dark brown' => '#764830',
                                        'cream' => '#e0d2b9',
                                        'sand' => '#cdb28e',
                                        'gray' => '#888888',
                                        'natural gray' => '#919496',
                                        'dark gray' => '#585c5f',
                                        'white cement' => '#e2e4e6',
                                        'sandstone' => '#c3b298',
                                        'charcoal' => '#2a2d30',
                                        'stone beige' => '#b9ac9b',
                                        'blue' => '#1e55a0',
                                        'yellow' => '#ebb92d',
                                        'pastel pink' => '#eeb2bc',
                                        'beige' => '#d4c6b2',
                                    ];
                                ?>
                                    <div class="shop-color-swatches" title="Available Colors: <?php echo htmlspecialchars(implode(', ', $colorsList)); ?>">
                                        <span class="shop-color-label">Colors:</span>
                                        <?php foreach ($colorsList as $col) {
                                            $hex = $colorHexMap[strtolower($col)] ?? '#888888';
                                            echo '<span class="shop-color-dot" style="background:' . $hex . ';" title="' . htmlspecialchars($col) . '"></span>';
                                        } ?>
                                    </div>
                                <?php } ?>

                                <!-- CENTIMETER SIZES IF POT -->
                                <?php if ($isPotCard) { ?>
                                    <div class="shop-pot-sizes-badge">
                                        <i class="fa-solid fa-ruler-combined"></i> Small (10 cm) • Medium (15 cm) • Large (20 cm)
                                    </div>
                                <?php } ?>

                                <!-- DESCRIPTION -->
                                <p class="shop-description">
                                    <?php echo htmlspecialchars($plant['description']); ?>
                                </p>

                                <!-- STOCK STATUS -->
                                <?php if (!$isOutOfStock) { ?>
                                    <span class="shop-stock-status in-stock">
                                        <i class="fa-solid fa-check" style="margin-right:3px;"></i> In Stock
                                    </span>
                                <?php } else { ?>
                                    <span class="shop-stock-status out-of-stock">
                                        <i class="fa-solid fa-circle-xmark" style="margin-right:3px;"></i> Out of Stock
                                    </span>
                                <?php } ?>

                                <!-- PRODUCT BOTTOM -->
                                <div class="shop-product-bottom">
                                    <strong>
                                        From Rs. <?php echo number_format((float) $plant['starting_price'], 2); ?>
                                    </strong>
                                </div>

                                <div class="shop-card-actions">
                                    <a href="product-details.php?id=<?php echo (int) $plant['product_id']; ?>" class="shop-view-details-btn">
                                        View Details <i class="fa-solid fa-arrow-right" style="font-size:11px; margin-left:5px;"></i>
                                    </a>

                                    <button type="button"
                                            class="shop-add-cart"
                                            title="<?php echo $isOutOfStock ? 'Currently Out of Stock' : 'Add to Cart'; ?>"
                                            aria-label="Add <?php echo htmlspecialchars($plant['product_name']); ?> to cart"
                                            <?php echo $isOutOfStock ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''; ?>
                                            data-product="<?php echo htmlspecialchars(json_encode([
                                                'product_id' => (int) $plant['product_id'],
                                                'variation_id' => (int) $plant['first_variation_id'],
                                                'product_name' => $plant['product_name'],
                                                'category' => $plant['category_name'] ?: $categoryTitle,
                                                'color' => $isPotCard ? ($colorsList[0] ?? '') : '',
                                                'size' => $isPotCard ? 'Small (10 cm)' : '',
                                                'price' => (float) $plant['starting_price'],
                                                'image' => $plantImg
                                            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">
                                        <i class="fa-solid fa-cart-plus"></i>
                                    </button>
                                </div>

                            </div>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <!-- EMPTY RESULTS COMPONENT -->
                    <div class="shop-empty-results">
                        <i class="fa-solid fa-seedling empty-icon"></i>
                        <h3>No products matched your search.</h3>
                        <p>Try another plant or pot name, or adjust your filter options to discover our greenery collection.</p>
                        <a href="shop.php" class="shop-empty-clear-btn">
                            <i class="fa-solid fa-rotate-left"></i> Clear All Filters
                        </a>
                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</section>

<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">
    <div class="footer-leaf watermark">
        <i class="fa-solid fa-leaf"></i>
    </div>

    <div class="footer-grid">
        <div class="footer-brand">
            <img src="images/logo.png" alt="Plantora Logo" class="footer-logo">
            <p>Beautiful indoor plants, stylish pots and thoughtful gift packages to greenify your space.</p>
        </div>

        <div class="footer-column">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a>
            <a href="shop.php">Indoor Plants</a>
            <a href="shop.php?category=pots">Pots</a>
            <a href="shop.php?category=packages">Gift Packages</a>
        </div>

        <div class="footer-column">
            <h3>Customer Service</h3>
            <a href="#">Help Center</a>
            <a href="#">Track Order</a>
            <a href="#">FAQs</a>
            <a href="#">Returns & Refunds</a>
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>📍 Colombo, Sri Lanka</p>
            <p>📞 +94 71 234 5678</p>
            <p>✉ support@plantora.com</p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© 2026 Plantora. All rights reserved.</p>
        <p>Bring Nature Home 🌿</p>
    </div>
</footer>

<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="js/cart.js?v=2"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const desktopSort = document.getElementById("desktop-sort");
    const mobileSort = document.getElementById("mobile-sort-select");
    const sidebarSortInput = document.getElementById("sidebar-sort-input");
    const filterForm = document.getElementById("shop-sidebar-form");

    function applySort(val) {
        if (sidebarSortInput) sidebarSortInput.value = val;
        if (filterForm) filterForm.submit();
    }

    if (desktopSort) {
        desktopSort.addEventListener("change", function () {
            applySort(this.value);
        });
    }

    if (mobileSort) {
        mobileSort.addEventListener("change", function () {
            applySort(this.value);
        });
    }

    // Auto-submit sidebar selects on desktop on change
    const sidebarSelects = document.querySelectorAll(".sidebar-select");
    sidebarSelects.forEach(function (sel) {
        sel.addEventListener("change", function () {
            if (window.innerWidth > 900) {
                if (filterForm) filterForm.submit();
            }
        });
    });

    // Auto-submit on color swatch selection
    const colorRadios = document.querySelectorAll('input[name="color"]');
    colorRadios.forEach(function (radio) {
        radio.addEventListener("change", function () {
            if (window.innerWidth > 900) {
                if (filterForm) filterForm.submit();
            }
        });
    });

    // Mobile filter toggle
    const mobileToggle = document.getElementById("mobile-filter-toggle");
    const shopSidebar = document.getElementById("shop-sidebar");
    if (mobileToggle && shopSidebar) {
        mobileToggle.addEventListener("click", function () {
            shopSidebar.classList.toggle("is-open");
        });
    }

    // Mobile search sync
    const mobileSearchInput = document.getElementById("mobile-search-input");
    const mobileSearchBtn = document.getElementById("mobile-search-btn");
    const sidebarSearchInput = document.getElementById("sidebar-search");

    if (mobileSearchBtn && mobileSearchInput && sidebarSearchInput) {
        mobileSearchBtn.addEventListener("click", function () {
            sidebarSearchInput.value = mobileSearchInput.value;
            if (filterForm) filterForm.submit();
        });
        mobileSearchInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                sidebarSearchInput.value = mobileSearchInput.value;
                if (filterForm) filterForm.submit();
            }
        });
    }
});
</script>

</body>
</html>