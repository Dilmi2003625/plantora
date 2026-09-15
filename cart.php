<?php
require_once 'config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Shopping Cart | Plantora';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .cart-page-wrap {
            max-width: 1200px;
            margin: 40px auto 80px;
            padding: 0 5%;
        }
        .cart-title-row {
            margin-bottom: 25px;
            border-bottom: 1px solid #e2ece5;
            padding-bottom: 15px;
        }
        .cart-title-row h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: #0b3d20;
        }
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 35px;
            align-items: start;
        }
        @media (max-width: 860px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }
        .cart-items-card {
            background: #fff;
            border: 1px solid #e2ece5;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .cart-item-row {
            display: grid;
            grid-template-columns: 80px 1.5fr 1fr 1fr auto;
            align-items: center;
            padding: 18px 20px;
            border-bottom: 1px solid #f0f4f1;
            gap: 15px;
        }
        .cart-item-row:last-child {
            border-bottom: none;
        }
        .cart-item-thumb {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 8px;
            background: #f7faf8;
        }
        .cart-item-name {
            font-size: 16px;
            font-weight: 600;
            color: #0b3d20;
            margin-bottom: 4px;
        }
        .cart-item-size-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            color: #176b36;
            background: #eaf5ec;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .cart-item-price {
            font-size: 15px;
            color: #4a6353;
        }
        .cart-item-subtotal {
            font-size: 16px;
            font-weight: 700;
            color: #0b3d20;
        }
        .cart-qty-ctrl {
            display: inline-flex;
            align-items: center;
            border: 1px solid #d4ebd7;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .cart-qty-btn {
            background: #f7faf8;
            border: none;
            width: 28px;
            height: 32px;
            font-size: 16px;
            cursor: pointer;
            color: #176b36;
        }
        .cart-qty-btn:hover {
            background: #eaf5ec;
        }
        .cart-qty-val {
            width: 38px;
            text-align: center;
            border: none;
            font-weight: 600;
            color: #0b3d20;
            font-size: 14px;
        }
        .cart-remove-btn {
            background: none;
            border: none;
            color: #c0392b;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            transition: color 0.2s;
        }
        .cart-remove-btn:hover {
            color: #e74c3c;
        }
        .cart-summary-card {
            background: #fbfdfc;
            border: 1px solid #d4ebd7;
            border-radius: 12px;
            padding: 24px;
        }
        .cart-summary-card h2 {
            font-size: 20px;
            color: #0b3d20;
            margin-bottom: 18px;
            font-family: 'Playfair Display', serif;
        }
        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 15px;
            margin-bottom: 12px;
            color: #4a6353;
        }
        .cart-summary-row.total {
            border-top: 1px solid #d4ebd7;
            padding-top: 14px;
            margin-top: 14px;
            font-size: 18px;
            font-weight: 700;
            color: #0b3d20;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            background: #176b36;
            color: #fff;
            text-align: center;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.2s;
        }
        .checkout-btn:hover {
            background: #0f4f26;
        }
        .cart-empty-view {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border: 1px solid #e2ece5;
            border-radius: 12px;
        }
        .cart-empty-view i {
            font-size: 48px;
            color: #a8d5b5;
            margin-bottom: 15px;
        }
        .cart-empty-view h2 {
            font-size: 24px;
            color: #0b3d20;
            margin-bottom: 10px;
        }
        .cart-empty-view a {
            display: inline-block;
            margin-top: 15px;
            background: #176b36;
            color: #fff;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
    </style>
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

<main class="cart-page-wrap">
    <div class="cart-title-row">
        <h1>Your Shopping Cart</h1>
    </div>

    <div id="cart-container">
        <!-- Rendered by JavaScript from localStorage -->
    </div>
</main>

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
<script>
function renderCartPage() {
    const container = document.getElementById('cart-container');
    const cart = typeof window.readCart === 'function' ? window.readCart() : [];

    if (!cart || cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty-view">
                <i class="fa-solid fa-cart-shopping"></i>
                <h2>Your Cart is Currently Empty</h2>
                <p>Discover our beautiful plants and stylish pots to start filling your space.</p>
                <a href="shop.php">Start Shopping 🌿</a>
            </div>
        `;
        return;
    }

    let subtotal = 0;
    let itemsHtml = '';

    cart.forEach(function(item, index) {
        const itemTotal = Number(item.price) * Number(item.quantity);
        subtotal += itemTotal;
        const imgSrc = item.image || 'images/logo.png';
        const colorBadge = item.color ? `<span class="cart-item-color-badge">Color: ${item.color}</span>` : '';
        const sizeBadge = item.size ? `<span class="cart-item-size-badge">${item.size}</span>` : '';

        itemsHtml += `
            <div class="cart-item-row" data-variation-id="${item.variation_id}">
                <img src="${imgSrc}" class="cart-item-thumb" alt="${item.product_name}" onerror="this.src='images/logo.png'">
                <div>
                    <div class="cart-item-name">${item.product_name}</div>
                    <div style="margin-top: 4px;">
                        ${colorBadge}${sizeBadge}
                    </div>
                </div>
                <div class="cart-item-price">Rs. ${Number(item.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                <div>
                    <div class="cart-qty-ctrl">
                        <button class="cart-qty-btn" onclick="changeQuantity(${item.variation_id}, -1)">−</button>
                        <span class="cart-qty-val">${item.quantity}</span>
                        <button class="cart-qty-btn" onclick="changeQuantity(${item.variation_id}, 1)">+</button>
                    </div>
                </div>
                <div class="cart-item-subtotal">Rs. ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                <div>
                    <button class="cart-remove-btn" title="Remove Item" onclick="removeItem(${item.variation_id})">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    });

    const deliveryFee = subtotal >= 5000 || subtotal === 0 ? 0 : 350;
    const grandTotal = subtotal + deliveryFee;

    container.innerHTML = `
        <div class="cart-layout">
            <div class="cart-items-card">
                ${itemsHtml}
            </div>
            <div class="cart-summary-card">
                <h2>Order Summary</h2>
                <div class="cart-summary-row">
                    <span>Subtotal</span>
                    <span>Rs. ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="cart-summary-row">
                    <span>Delivery</span>
                    <span>${deliveryFee === 0 ? '<strong style="color:#176b36;">FREE</strong>' : 'Rs. ' + deliveryFee.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
                <div class="cart-summary-row total">
                    <span>Grand Total</span>
                    <span>Rs. ${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                </div>
                <button class="checkout-btn" onclick="handleCheckout()">Proceed to Checkout</button>
                <div style="text-align:center; margin-top:15px;">
                    <a href="shop.php" style="color:#176b36; font-size:14px; font-weight:600;">← Continue Shopping</a>
                </div>
            </div>
        </div>
    `;
}

const isUserLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
function handleCheckout() {
    if (!isUserLoggedIn) {
        alert('Please log in to your Plantora account to proceed to checkout.');
        window.location.href = 'login.php?return_url=cart.php';
    } else {
        alert('Thank you, ' + <?php echo json_encode($_SESSION['user_name'] ?? 'Customer'); ?> + '! Your order checkout is ready.');
    }
}

function changeQuantity(variationId, delta) {
    const cart = window.readCart();
    const item = cart.find(i => Number(i.variation_id) === Number(variationId));
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            removeItem(variationId);
            return;
        }
        window.saveCart(cart);
        window.updateCartCounters();
        renderCartPage();
    }
}

function removeItem(variationId) {
    let cart = window.readCart();
    cart = cart.filter(i => Number(i.variation_id) !== Number(variationId));
    window.saveCart(cart);
    window.updateCartCounters();
    renderCartPage();
}

document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
</body>
</html>
