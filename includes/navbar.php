<?php
/**
 * Plantora E-Commerce
 * Navigation Bar Component
 * File: includes/navbar.php
 */
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="navbar" id="navbar">
    <a href="index.php" class="<?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">Home</a>

    <!-- INDOOR PLANTS DROPDOWN -->
    <div class="nav-dropdown">
        <a href="shop.php" class="dropdown-title <?php echo ($currentScript === 'shop.php' && empty($_GET['category'])) ? 'active' : ''; ?>">
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

    <!-- POTS DROPDOWN -->
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
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="admin/index.php" style="color: #c0392b; font-weight: bold;">Admin Panel</a>
    <?php endif; ?>
</nav>