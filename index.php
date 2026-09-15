<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Plantora | Bring Nature Home</title>

    <link rel="stylesheet" href="css/style.css?v=2">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<!-- =========================================================
     TOP BAR
========================================================= -->

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


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <!-- LOGO -->

    <a href="index.php" class="logo">

        <img src="images/logo.png"
             alt="Plantora - Bring Nature Home">

    </a>


    <!-- NAVIGATION -->

    <nav class="navbar" id="navbar">

    <a href="index.php" class="active">Home</a>

    <!-- INDOOR PLANTS DROPDOWN -->
    <div class="nav-dropdown">

        <a href="shop.php" class="dropdown-title">
            Indoor Plants <span class="arrow">⌄</span>
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

   <!-- POTS DROPDOWN -->
<div class="nav-dropdown">

    <a href="shop.php?category=pots" class="dropdown-title">
        Pots <span class="arrow">⌄</span>
    </a>

    <div class="dropdown-menu">

        <a href="shop.php?category=plastic-pots">
            🪴 Plastic Pots
        </a>

        <a href="shop.php?category=terracotta-clay">
            🏺 Terracotta & Clay Pots
        </a>

        <a href="shop.php?category=glazed-ceramic">
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


    <!-- SEARCH + LOGIN + CART -->
    <?php include __DIR__ . '/includes/header_actions.php'; ?>
</header>



<!-- =========================================================
     HERO SECTION
========================================================= -->

<section class="hero">

    <img
        src="images/hero-banner.jpeg"
        alt="Beautiful indoor plants"
        class="hero-image"
    >


    <div class="hero-overlay"></div>


    <div class="hero-content">

        <p class="hero-small">
            WELCOME TO PLANTORA
        </p>

        <h1>
            Bring Nature<br>
            Into Your Home
        </h1>

        <p class="hero-description">
            Beautiful indoor plants, stylish pots and
            thoughtful gift packages to create a greener
            and happier space.
        </p>


        <div class="hero-buttons">

            <a href="shop.php" class="btn-primary">
                Shop Now 🛒
            </a>

            <a href="shop.php" class="btn-secondary">
                Explore Plants 🌿
            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     BENEFITS
========================================================= -->

<section class="benefits">

    <!-- FREE DELIVERY -->
    <div class="benefit">

        <div class="benefit-icon">
            <i class="fa-solid fa-truck-fast"></i>
        </div>

        <div>
            <h3>Free Delivery</h3>
            <p>On orders over Rs. 5000</p>
        </div>

    </div>


    <!-- SECURE PAYMENT -->
    <div class="benefit">

        <div class="benefit-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div>
            <h3>Secure Payment</h3>
            <p>100% secure checkout</p>
        </div>

    </div>


    <!-- QUALITY PLANTS -->
    <div class="benefit">

        <div class="benefit-icon">
            <i class="fa-solid fa-leaf"></i>
        </div>

        <div>
            <h3>Quality Plants</h3>
            <p>Carefully selected</p>
        </div>

    </div>


    <!-- CUSTOMER SUPPORT -->
    <div class="benefit">

        <div class="benefit-icon">
            <i class="fa-solid fa-headset"></i>
        </div>

        <div>
            <h3>Customer Support</h3>
            <p>We're here to help</p>
        </div>

    </div>

</section>



<!-- =========================================================
     SHOP BY CATEGORY
========================================================= -->

<section class="section">

    <div class="section-heading">

        <p>EXPLORE OUR COLLECTION</p>

        <h2>
            Shop by Category
        </h2>

        <span>🌿</span>

    </div>


    <div class="category-grid">


        <a href="shop.php?category=low-light" class="category-card">

            <div class="category-image">
                <img src="images/categories/low_light.jpeg"
                     alt="Low Light Plants">
            </div>

            <h3>Low-Light Plants</h3>

            <p>Perfect for low light</p>

        </a>



        <a href="shop.php?category=air-purifying" class="category-card">

            <div class="category-image">
                <img src="images/categories/air_purifying.jpeg"
                     alt="Air Purifying Plants">
            </div>

            <h3>Air-Purifying</h3>

            <p>Fresh air naturally</p>

        </a>



        <a href="shop.php?category=easy-care" class="category-card">

            <div class="category-image">
                <img src="images/categories/easy_care.jpeg"
                     alt="Easy Care Plants">
            </div>

            <h3>Easy-Care Plants</h3>

            <p>Perfect for beginners</p>

        </a>



        <a href="shop.php?category=flowering" class="category-card">

            <div class="category-image">
                <img src="images/categories/flowering.jpeg "
                     alt="Flowering Plants">
            </div>

            <h3>Flowering Plants</h3>

            <p>Add colour to your home</p>

        </a>



        <a href="shop.php?category=foliage" class="category-card">

            <div class="category-image">
                <img src="images/categories/foliage.jpeg"
                     alt="Foliage Plants">
            </div>

            <h3>Foliage Plants</h3>

            <p>Beautiful green leaves</p>

        </a>



        <a href="shop.php?category=cactus" class="category-card">

            <div class="category-image">
                <img src="images/categories/cacti.jpeg"
                     alt="Cacti and Succulents">
            </div>

            <h3>Cacti & Succulents</h3>

            <p>Small plants, big style</p>

        </a>



        <a href="shop.php?category=pots" class="category-card">

            <div class="category-image">
                <img src="images/categories/pots.jpeg"
                     alt="Plant Pots">
            </div>

            <h3>Pots</h3>

            <p>Stylish pots for your plants</p>

        </a>



        <a href="shop.php?category=packages" class="category-card">

            <div class="category-image">
                <img src="images/categories/gift_packages.jpeg"
                     alt="Gift Packages">
            </div>

            <h3>Gift Packages</h3>

            <p>Give the gift of greenery</p>

        </a>

    </div>

</section>



<!-- =========================================================
     BEST SELLING PLANTS
========================================================= -->

<section class="section best-selling">

    <div class="section-title-row">

        <div>

            <p class="section-label">
                CUSTOMER FAVOURITES
            </p>

            <h2>
                Best Selling Plants
            </h2>

        </div>


        <a href="shop.php" class="view-all">
            View All Products →
        </a>

    </div>



    <div class="product-grid">


        <!-- PRODUCT 1 -->

        <div class="product-card">

            <div class="product-image">

                <span class="product-badge">
                    Bestseller
                </span>

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/snake-plant.jpeg"
                    alt="Snake Plant"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Air Purifying Plant
                </p>

                <h3>
                    Snake Plant
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(128)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 2,990
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 2 -->

        <div class="product-card">

            <div class="product-image">

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/peace-lily.jpeg"
                    alt="Peace Lily"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Low-Light Plant
                </p>

                <h3>
                    Peace Lily
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(96)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 3,990
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 3 -->

        <div class="product-card">

            <div class="product-image">

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/zz-plant.jpeg"
                    alt="ZZ Plant"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Easy-Care Plant
                </p>

                <h3>
                    ZZ Plant
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(87)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 3,490
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 4 -->

        <div class="product-card">

            <div class="product-image">

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/monstera.jpeg"
                    alt="Monstera"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Indoor Foliage Plant
                </p>

                <h3>
                    Monstera
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(112)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 5,990
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 5 -->

        <div class="product-card">

            <div class="product-image">

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/pothos.jpeg"
                    alt="Pothos"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Air Purifying Plant
                </p>

                <h3>
                    Pothos
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(134)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 1,990
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>



        <!-- PRODUCT 6 -->

        <div class="product-card">

            <div class="product-image">

                <button class="wishlist">
                    ♡
                </button>

                <img
                    src="images/products/aloe-vera.jpeg"
                    alt="Aloe Vera"
                >

            </div>


            <div class="product-info">

                <p class="product-type">
                    Medicinal Plant
                </p>

                <h3>
                    Aloe Vera
                </h3>

                <div class="rating">
                    ★★★★★
                    <span>(75)</span>
                </div>

                <div class="product-bottom">

                    <strong>
                        Rs. 2,490
                    </strong>

                    <a href="cart.php" class="add-cart">
                        🛒
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     MISSION
========================================================= -->

<section class="mission-section" id="about">

    <div class="mission-image">

        <img
            src="images/mission.jpeg"
            alt="Green indoor living space"
        >

    </div>


    <div class="mission-content">

        <p class="mission-label">
            OUR MISSION
        </p>

        <h2>
            Bringing More Green
            <br>
            Into Everyday Life
        </h2>

        <p>
            At Plantora, our mission is to make it easy for
            everyone to bring the beauty and benefits of
            nature into their homes.
        </p>

        <p>
            We carefully select quality indoor plants and
            provide the knowledge you need to help them
            grow beautifully.
        </p>

        <a href="#about" class="mission-link">
            Learn More About Us →
        </a>

    </div>

</section>



<!-- =========================================================
     WHY CHOOSE PLANTORA
========================================================= -->

<section class="why-section">

    <div class="section-heading">

        <p>
            WHY PLANTORA?
        </p>

        <h2>
            More Than Just Plants
        </h2>


        <div class="heading-description">
            Everything you need to create a greener,
            healthier and happier space.
        </div>

    </div>



    <div class="why-grid">


      <div class="why-card">

        <div class="why-icon">
            <i class="fa-solid fa-seedling"></i>
        </div>

        <h3>Healthy Plants</h3>

        <p>
        Every plant is carefully selected and
        prepared before reaching your home.
        </p>

        </div>


        <div class="why-card">

         <div class="why-icon">
                <i class="fa-solid fa-box-open"></i>
            </div>

            <h3>Safe Packaging</h3>

    <p>
        Our plants are packed carefully to
        ensure they arrive safely.
    </p>

        </div>


        <div class="why-card">

    <div class="why-icon">
        <i class="fa-solid fa-hand-holding-heart"></i>
    </div>

    <h3>Plant Care Support</h3>

    <p>
        Get useful care tips to help your
        plants stay healthy and beautiful.
    </p>

</div>



        <div class="why-card">

    <div class="why-icon">
        <i class="fa-solid fa-truck"></i>
    </div>

    <h3>Reliable Delivery</h3>

    <p>
        We make sure your order reaches you
        safely and on time.
    </p>

</div>

</section>



<!-- =========================================================
     CUSTOMER REVIEWS
========================================================= -->

<section class="reviews-section">

    <div class="section-heading">

        <p>
            HAPPY PLANT PARENTS
        </p>

        <h2>
            What Our Customers Say
        </h2>

    </div>



    <div class="reviews-grid">


        <div class="review-card">

            <div class="review-stars">
                ★★★★★
            </div>

            <p>
                "The plant arrived healthy and beautifully
                packaged. It looks perfect in my living room!"
            </p>

            <strong>
                — Nadeesha
            </strong>

        </div>



        <div class="review-card">

            <div class="review-stars">
                ★★★★★
            </div>

            <p>
                "Great quality plants and very helpful
                customer service. I will definitely order again."
            </p>

            <strong>
                — Kavindu
            </strong>

        </div>



        <div class="review-card">

            <div class="review-stars">
                ★★★★★
            </div>

            <p>
                "I bought a gift package for my friend.
                The packaging was beautiful!"
            </p>

            <strong>
                — Amaya
            </strong>

        </div>

    </div>

</section>



<!-- =========================================================
     PLANT CARE
========================================================= -->

<section class="care-section" id="care">

    <div class="care-content">

        <p class="section-label">
            PLANT CARE GUIDE
        </p>

        <h2>
            Keep Your Plants
            <br>
            Happy & Healthy 🌿
        </h2>

        <p>
            Learn simple plant-care tips to help your
            indoor plants grow beautifully.
        </p>

        <a href="#" class="btn-primary">
            Explore Care Tips →
        </a>

    </div>


    <div class="care-cards">

        <div class="care-card">

            <div>
                💧
            </div>

            <h3>
                Watering
            </h3>

            <p>
                Water according to your plant's needs.
            </p>

        </div>


        <div class="care-card">

            <div>
                ☀️
            </div>

            <h3>
                Light
            </h3>

            <p>
                Give your plant the right amount of sunlight.
            </p>

        </div>


        <div class="care-card">

            <div>
                🌱
            </div>

            <h3>
                Soil & Repotting
            </h3>

            <p>
                Use suitable soil and enough growing space.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     NEWSLETTER
========================================================= -->

<section class="newsletter">

    <div class="newsletter-icon">
        ✉️
    </div>

    <div>

        <h2>
            Join the Plantora Family!
        </h2>

        <p>
            Get the latest offers, new arrivals and plant care tips.
        </p>

    </div>


    <form class="newsletter-form">

        <input
            type="email"
            placeholder="Enter your email address"
        >

        <button type="submit">
            Subscribe
        </button>

    </form>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="footer" id="contact">

    <div class="footer-leaf watermark">
        🌿
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


            <div class="social-icons">

                <a href="#">f</a>
                <a href="#">◎</a>
                <a href="#">♪</a>
                <a href="#">▶</a>

            </div>

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
                Plants
            </a>

            <a href="shop.php?category=pots">
                Pots
            </a>

            <a href="shop.php?category=packages">
                Gift Packages
            </a>

            <a href="#about">
                About Us
            </a>

            <a href="#contact">
                Contact
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

            <a href="#">
                Privacy Policy
            </a>

            <a href="#">
                Terms & Conditions
            </a>

        </div>



        <!-- CONTACT -->

        <div class="footer-column">

            <h3>
                Contact Us
            </h3>

            <p>
                📍 123 Green Street,
                Colombo, Sri Lanka
            </p>

            <p>
                📞 +94 71 234 5678
            </p>

            <p>
                ✉️ support@plantora.com
            </p>

            <p>
                🕘 Mon - Sun: 9:00 AM - 9:00 PM
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
<script src="js/cart.js?v=1"></script>

<script>
function toggleMenu() {
    const navbar = document.getElementById("navbar");
    navbar.classList.toggle("show");
}
</script>

</body>
</html>