<?php
/**
 * Plantora E-Commerce
 * Header Actions Component (Search, User Auth, Cart)
 * File: includes/header_actions.php
 */

require_once __DIR__ . '/auth.php';

$loggedIn = isLoggedIn();
$userName = '';
if ($loggedIn) {
    $fullName = $_SESSION['user_name'] ?? 'User';
    // Short first name for clean navbar display
    $parts = explode(' ', trim($fullName));
    $userName = $parts[0];
}
?>
<div class="header-actions">
    <!-- SEARCH FORM -->
    <form action="shop.php" method="GET" class="search-box">
        <input 
            type="text" 
            name="search" 
            placeholder="Search plants..." 
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
            aria-label="Search plants"
        >
        <button type="submit" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>

    <!-- AUTHENTICATION STATE -->
    <?php if ($loggedIn): ?>
        <div class="user-nav-actions">
            <a href="profile.php" class="login-link user-pill" title="My Account (<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>)">
                <i class="fa-solid fa-circle-user"></i>
                <span class="user-pill-name">Hi, <?php echo htmlspecialchars($userName); ?></span>
            </a>
            <a href="logout.php" class="logout-nav-link" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span class="logout-text">Logout</span>
            </a>
        </div>
    <?php else: ?>
        <div class="guest-nav-actions">
            <a href="login.php" class="login-link">
                <i class="fa-regular fa-user"></i> Login
            </a>
        </div>
    <?php endif; ?>

    <!-- CART -->
    <a href="cart.php" class="cart-link" aria-label="Shopping Cart">
        <i class="fa-solid fa-cart-shopping"></i>
        <span class="cart-count">0</span>
    </a>
</div>
