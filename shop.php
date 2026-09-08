<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Low-Light Plants | Plantora</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<?php

$category = $_GET['category'] ?? 'all';

$plants = [
    [
        'name' => 'Peace Lily',
        'category' => 'low-light',
        'price' => 2990,
        'image' => 'images/low-light/peace-lily.jpeg',
        'description' => 'Beautiful indoor plant that grows well in low-light areas.',
    ],

    [
        'name' => 'Snake Plant',
        'category' => 'low-light',
        'price' => 2490,
        'image' => 'images/low-light/snake-plant.jpeg',
        'description' => 'Easy-care indoor plant suitable for low-light spaces.',
    ],

    [
        'name' => 'ZZ Plant',
        'category' => 'low-light',
        'price' => 3490,
        'image' => 'images/low-light/zz-plant.jpeg',
        'description' => 'Hardy indoor plant that can tolerate low-light conditions.',
    ],

    [
        'name' => 'Chinese Evergreen',
        'category' => 'low-light',
        'price' => 3290,
        'image' => 'images/low-light/chinese-evergreen.jpeg',
        'description' => 'Attractive foliage plant suitable for indoor spaces.',
    ],

    [
        'name' => 'Cast Iron Plant',
        'category' => 'low-light',
        'price' => 2890,
        'image' => 'images/low-light/cast-iron.jpg',
        'description' => 'Strong and easy-care plant for low-light environments.',
    ],

    [
        'name' => 'Heartleaf Philodendron',
        'category' => 'low-light',
        'price' => 3990,
        'image' => 'images/low-light/heartleaf-philodendron.jpeg',
        'description' => 'Beautiful trailing plant that adapts well to indoor conditions.',
    ],
];

// Filter products

if ($category !== 'all') {
    $filteredPlants = array_filter($plants, function ($plant) use ($category) {
        return $plant['category'] === $category;
    });
} else {
    $filteredPlants = $plants;
}

// Category title

$categoryTitle = 'Indoor Plants';

if ($category === 'low-light') {
    $categoryTitle = 'Low-Light Plants';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $categoryTitle; ?> | Plantora
    </title>


    <!-- Main CSS -->

    <link rel="stylesheet"
          href="css/style.css?v=2">


    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


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
                <span class="arrow">⌄</span>

            </a>


            <div class="dropdown-menu">

                <a href="shop.php?category=low-light">
                    🌿 Low-Light Plants
                </a>

                <a href="shop.php?category=air-purifying">
                    🌱 Air-Purifying Plants
                </a>

                <a href="shop.php?category=easy-care">
                    🪴 Easy-Care Plants
                </a>

                <a href="shop.php?category=flowering">
                    🌸 Flowering Plants
                </a>

                <a href="shop.php?category=foliage">
                    🍃 Foliage Plants
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
                <span class="arrow">⌄</span>

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



        <a href="shop.php?category=packages">
            Gift Packages
        </a>


        <a href="care.php">
            Care & Tips
        </a>


        <a href="about.php">
            About Us
        </a>


        <a href="contact.php">
            Contact Us
        </a>

    </nav>



    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <div class="search-box">

            <input type="text"
                   placeholder="Search plants...">

            <button type="button">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

        </div>


        <a href="login.php"
           class="login-link">

            <i class="fa-regular fa-user"></i>
            Login

        </a>


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
            <?php echo $categoryTitle; ?>
        </h1>

        <div class="breadcrumb">

            <a href="index.php">
                Home
            </a>

            <span> / </span>

            <span>
                Indoor Plants
            </span>

            <?php if ($category === 'low-light') { ?>

                <span> / </span>

                <span>
                    Low-Light Plants
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
                Low-Light Plants
            </h2>

            <p>
                Perfect plants for rooms with limited sunlight.
            </p>

        </div>


        <div class="shop-sort">

            <label for="sort">
                Sort By:
            </label>

            <select id="sort">

                <option>
                    Featured
                </option>

                <option>
                    Price: Low to High
                </option>

                <option>
                    Price: High to Low
                </option>

                <option>
                    Newest
                </option>

            </select>

        </div>

    </div>



    <!-- PRODUCTS -->

    <div class="shop-product-grid">


        <?php if (count($filteredPlants) > 0) { ?>


            <?php foreach ($filteredPlants as $plant) { ?>


                <div class="shop-product-card">


                    <!-- IMAGE -->

                    <div class="shop-product-image">

                        <span class="shop-badge">
                            Low Light
                        </span>


                        <button class="shop-wishlist">
                            <i class="fa-regular fa-heart"></i>
                        </button>


                        <img
                            src="<?php echo $plant['image']; ?>"
                            alt="<?php echo $plant['name']; ?>"
                        >

                    </div>



                    <!-- DETAILS -->

                    <div class="shop-product-info">


                        <p class="shop-product-category">
                            Indoor Plant
                        </p>


                        <h3>
                            <?php echo $plant['name']; ?>
                        </h3>


                        <div class="shop-rating">

                            <span>
                                ★★★★★
                            </span>

                            <small>
                                (24)
                            </small>

                        </div>


                        <p class="shop-description">

                            <?php echo $plant['description']; ?>

                        </p>


                        <div class="shop-product-bottom">


                            <strong>

                                Rs.
                                <?php echo number_format($plant['price']); ?>

                            </strong>


                            <a href="cart.php"
                               class="shop-add-cart">

                                <i class="fa-solid fa-cart-plus"></i>

                            </a>

                        </div>

                    </div>

                </div>


            <?php } ?>


        <?php } else { ?>


            <div class="no-products">

                <i class="fa-solid fa-leaf"></i>

                <h3>
                    No Plants Found
                </h3>

                <p>
                    There are currently no plants in this category.
                </p>

            </div>


        <?php } ?>


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


    <div class="footer-bottom">

        <p>
            © 2026 Plantora. All rights reserved.
        </p>

        <p>
            Bring Nature Home 🌿
        </p>

    </div>

</footer>


</body>
</html>