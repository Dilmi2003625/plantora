<?php
session_start();
require_once 'config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php?return_url=checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info for pre-filling
$stmt = $conn->prepare("SELECT name, email, phone, shipping_address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request token. Please try again.";
    } else {
        $cart_data_json = $_POST['cart_data'] ?? '[]';
        $cart = json_decode($cart_data_json, true);

        if (empty($cart)) {
            $error = "Your cart is empty.";
        } else {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            $payment_method = $_POST['payment_method'] ?? '';

            if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city)) {
                $error = "Please fill in all required delivery details.";
            } elseif ($payment_method !== 'Cash on Delivery' && $payment_method !== 'Online Payment') {
                $error = "Selected payment method is currently unavailable.";
            } else {
                $full_address = $address . ', ' . $city;
                if (!empty($notes)) {
                    $full_address .= "\nNotes: $notes";
                }

                $conn->begin_transaction();
                try {
                    $subtotal = 0;
                    $items_to_insert = [];

                    foreach ($cart as $item) {
                        $variation_id = (int)$item['variation_id'];
                        $qty = (int)$item['quantity'];

                        if ($qty <= 0) throw new Exception("Invalid quantity for an item.");

                        $stmt = $conn->prepare("SELECT price, stock_quantity, p.product_name FROM product_variations pv JOIN products p ON p.product_id = pv.product_id WHERE pv.variation_id = ?");
                        $stmt->bind_param("i", $variation_id);
                        $stmt->execute();
                        $var_result = $stmt->get_result();
                        $var_data = $var_result->fetch_assoc();
                        $stmt->close();

                        if (!$var_data) throw new Exception("Product variation not found.");
                        if ($var_data['stock_quantity'] < $qty) throw new Exception("Not enough stock for {$var_data['product_name']}. Only {$var_data['stock_quantity']} left.");

                        $price = (float)$var_data['price'];
                        $item_subtotal = $price * $qty;
                        $subtotal += $item_subtotal;

                        $items_to_insert[] = [
                            'variation_id' => $variation_id,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'subtotal' => $item_subtotal,
                            'db_stock' => (int)$var_data['stock_quantity']
                        ];
                    }

                    $delivery_charge = ($subtotal >= 5000 || $subtotal == 0) ? 0 : 350;
                    $total_amount = $subtotal + $delivery_charge;

                    $stmt = $conn->prepare("INSERT INTO orders (user_id, delivery_address, delivery_charge, subtotal, total_amount, order_status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                    $stmt->bind_param("isddd", $user_id, $full_address, $delivery_charge, $subtotal, $total_amount);
                    $stmt->execute();
                    $order_id = $stmt->insert_id;
                    $stmt->close();

                    foreach ($items_to_insert as $it) {
                        $stmt = $conn->prepare("INSERT INTO order_items (order_id, variation_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("iiidd", $order_id, $it['variation_id'], $it['quantity'], $it['unit_price'], $it['subtotal']);
                        $stmt->execute();
                        $stmt->close();

                        $new_stock = $it['db_stock'] - $it['quantity'];
                        $stmt = $conn->prepare("UPDATE product_variations SET stock_quantity = ? WHERE variation_id = ?");
                        $stmt->bind_param("ii", $new_stock, $it['variation_id']);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, payment_status, payment_date) VALUES (?, ?, 'Pending', NOW())");
                    $stmt->bind_param("is", $order_id, $payment_method);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    
                    if ($payment_method === 'Online Payment') {
                        $payhere_config = require __DIR__ . '/config/payhere.php';
                        $merchant_id = $payhere_config['merchant_id'];
                        $merchant_secret = $payhere_config['merchant_secret'];
                        $currency = $payhere_config['currency'];
                        
                        $formatted_amount = number_format($total_amount, 2, '.', '');
                        $hash = strtoupper(md5($merchant_id . $order_id . $formatted_amount . $currency . strtoupper(md5($merchant_secret))));
                        
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                        $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/plantora";
                        
                        $return_url = $base_url . "/order-confirmation.php?id=$order_id&clear_cart=1";
                        $cancel_url = $base_url . "/order-confirmation.php?id=$order_id&cancelled=1";
                        $notify_url = $base_url . "/payhere-notify.php";
                        
                        $action_url = $payhere_config['sandbox'] ? 'https://sandbox.payhere.lk/pay/checkout' : 'https://www.payhere.lk/pay/checkout';

                        echo '<!DOCTYPE html><html><head><title>Redirecting to Payment...</title></head><body onload="document.getElementById(\'payhere-form\').submit();" style="text-align:center; padding-top:50px; font-family:sans-serif;">';
                        echo '<h2>Redirecting to secure payment gateway...</h2>';
                        echo '<p>Please wait, do not refresh the page.</p>';
                        echo '<form id="payhere-form" method="post" action="' . htmlspecialchars($action_url) . '">';
                        echo '<input type="hidden" name="merchant_id" value="' . htmlspecialchars($merchant_id) . '">';
                        echo '<input type="hidden" name="return_url" value="' . htmlspecialchars($return_url) . '">';
                        echo '<input type="hidden" name="cancel_url" value="' . htmlspecialchars($cancel_url) . '">';
                        echo '<input type="hidden" name="notify_url" value="' . htmlspecialchars($notify_url) . '">';
                        echo '<input type="hidden" name="order_id" value="' . htmlspecialchars($order_id) . '">';
                        echo '<input type="hidden" name="items" value="Plantora Order #' . htmlspecialchars($order_id) . '">';
                        echo '<input type="hidden" name="currency" value="' . htmlspecialchars($currency) . '">';
                        echo '<input type="hidden" name="amount" value="' . $formatted_amount . '">';
                        echo '<input type="hidden" name="first_name" value="' . htmlspecialchars($name) . '">';
                        echo '<input type="hidden" name="last_name" value="">';
                        echo '<input type="hidden" name="email" value="' . htmlspecialchars($email) . '">';
                        echo '<input type="hidden" name="phone" value="' . htmlspecialchars($phone) . '">';
                        echo '<input type="hidden" name="address" value="' . htmlspecialchars($address) . '">';
                        echo '<input type="hidden" name="city" value="' . htmlspecialchars($city) . '">';
                        echo '<input type="hidden" name="country" value="Sri Lanka">';
                        echo '<input type="hidden" name="hash" value="' . htmlspecialchars($hash) . '">';
                        echo '</form></body></html>';
                        exit();
                    }

                    header("Location: order-confirmation.php?id=$order_id&clear_cart=1");
                    exit();

                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Failed to process order: " . $e->getMessage();
                }
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Checkout | Plantora';
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
        .checkout-page-wrap {
            max-width: 1200px;
            margin: 40px auto 80px;
            padding: 0 5%;
        }
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
            color: #4a6353;
        }
        .breadcrumb a {
            color: #176b36;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .checkout-title-row {
            margin-bottom: 25px;
            border-bottom: 1px solid #e2ece5;
            padding-bottom: 15px;
        }
        .checkout-title-row h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: #0b3d20;
        }
        .checkout-layout {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 35px;
            align-items: start;
        }
        @media (max-width: 860px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
        .checkout-form-card, .checkout-summary-card {
            background: #fff;
            border: 1px solid #e2ece5;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .checkout-section-title {
            font-size: 20px;
            color: #0b3d20;
            margin-bottom: 18px;
            font-family: 'Playfair Display', serif;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 16px;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #0b3d20;
            margin-bottom: 8px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d4ebd7;
            border-radius: 6px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            background: #fbfdfc;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #176b36;
            background: #fff;
        }
        .payment-methods {
            margin-top: 10px;
        }
        .payment-option {
            display: flex;
            align-items: center;
            padding: 14px;
            border: 1px solid #d4ebd7;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #fbfdfc;
            cursor: pointer;
        }
        .payment-option.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .payment-option input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            accent-color: #176b36;
        }
        .payment-option span {
            font-size: 15px;
            font-weight: 600;
            color: #0b3d20;
        }
        .payment-option .badge {
            margin-left: auto;
            background: #f39c12;
            color: #fff;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        
        /* Summary Items */
        .summary-item-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f4f1;
        }
        .summary-item-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            background: #f7faf8;
            margin-right: 15px;
            border: 1px solid #e2ece5;
        }
        .summary-item-info {
            flex-grow: 1;
        }
        .summary-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #0b3d20;
            margin-bottom: 4px;
        }
        .summary-item-meta {
            font-size: 12px;
            color: #4a6353;
        }
        .summary-item-price {
            font-size: 14px;
            font-weight: 600;
            color: #0b3d20;
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
        
        .place-order-btn {
            display: block;
            width: 100%;
            background: #176b36;
            color: #fff;
            text-align: center;
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.2s;
        }
        .place-order-btn:hover {
            background: #0f4f26;
        }
        .place-order-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        .alert-error {
            background: #fdf2f2;
            color: #c0392b;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #fadbd8;
            font-weight: 500;
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
        <a href="shop.php">Indoor Plants</a>
        <a href="shop.php?category=pots">Pots</a>
        <a href="shop.php?category=packages">Gift Packages</a>
        <a href="care.php">Care & Tips</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
    </nav>

    <?php include __DIR__ . '/includes/header_actions.php'; ?>
</header>

<main class="checkout-page-wrap">
    <div class="breadcrumb">
        <a href="index.php">Home</a> &gt; <a href="cart.php">Cart</a> &gt; Checkout
    </div>
    <div class="checkout-title-row">
        <h1>Complete Your Order</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="checkout.php" id="checkout-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="cart_data" id="cart_data_input" value="">
        <input type="hidden" name="place_order" value="1">

        <div class="checkout-layout">
            <div class="checkout-form-card">
                <h2 class="checkout-section-title">Delivery Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? $user_data['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $user_data['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="text" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? $user_data['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Delivery Address *</label>
                    <textarea name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? $user_data['shipping_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Order Notes (Optional)</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Leave at the front door"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <h2 class="checkout-section-title" style="margin-top: 30px;">Payment Method</h2>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                        <span>Cash on Delivery (COD)</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Online Payment">
                        <span>PayHere Online Payment</span>
                        <span class="badge" style="background: #176b36;">Secure</span>
                    </label>
                </div>
            </div>
            
            <div class="checkout-summary-card">
                <h2 class="checkout-section-title">Order Summary</h2>
                <div id="checkout-items-container">
                    <!-- Filled by JS -->
                </div>
                
                <div id="checkout-totals-container">
                    <!-- Filled by JS -->
                </div>
                
                <button type="submit" class="place-order-btn" id="place-order-btn">Place Order</button>
            </div>
        </div>
    </form>
</main>

<footer class="footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <img src="images/logo.png" alt="Plantora Logo" class="footer-logo">
            <p>Beautiful indoor plants, stylish pots and thoughtful gift packages to greenify your space.</p>
        </div>
        <div class="footer-column">
            <h3>Quick Links</h3>
            <a href="index.php">Home</a><a href="shop.php">Indoor Plants</a><a href="shop.php?category=pots">Pots</a>
        </div>
        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>📍 Colombo, Sri Lanka</p><p>📞 +94 71 234 5678</p><p>✉ support@plantora.com</p>
        </div>
    </div>
    <div class="footer-bottom"><p>© 2026 Plantora. All Rights Reserved.</p></div>
</footer>

<script src="js/cart.js?v=2"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cart = typeof window.readCart === 'function' ? window.readCart() : [];
    const itemsContainer = document.getElementById('checkout-items-container');
    const totalsContainer = document.getElementById('checkout-totals-container');
    const form = document.getElementById('checkout-form');
    const cartDataInput = document.getElementById('cart_data_input');
    const btn = document.getElementById('place-order-btn');

    if (!cart || cart.length === 0) {
        itemsContainer.innerHTML = '<p>Your cart is empty.</p>';
        btn.disabled = true;
        btn.style.opacity = '0.5';
        return;
    }

    let subtotal = 0;
    let itemsHtml = '';

    cart.forEach(function(item) {
        const itemTotal = Number(item.price) * Number(item.quantity);
        subtotal += itemTotal;
        const imgSrc = item.image || 'images/logo.png';
        const colorBadge = item.color ? `<span style="margin-right: 8px;">Color: ${item.color}</span>` : '';
        const sizeBadge = item.size ? `<span>Size: ${item.size}</span>` : '';

        itemsHtml += `
            <div class="summary-item-row">
                <img src="${imgSrc}" class="summary-item-thumb" alt="${item.product_name}" onerror="this.src='images/logo.png'">
                <div class="summary-item-info">
                    <div class="summary-item-name">${item.product_name}</div>
                    <div class="summary-item-meta">
                        ${colorBadge}${sizeBadge}
                        <div>Qty: ${item.quantity}</div>
                    </div>
                </div>
                <div class="summary-item-price">Rs. ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            </div>
        `;
    });

    const deliveryFee = (subtotal >= 5000 || subtotal === 0) ? 0 : 350;
    const grandTotal = subtotal + deliveryFee;

    itemsContainer.innerHTML = itemsHtml;
    totalsContainer.innerHTML = `
        <div style="margin-top: 15px;">
            <div class="cart-summary-row">
                <span>Subtotal</span>
                <span>Rs. ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
            </div>
            <div class="cart-summary-row">
                <span>Delivery Charge</span>
                <span>${deliveryFee === 0 ? '<strong style="color:#176b36;">FREE</strong>' : 'Rs. ' + deliveryFee.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
            </div>
            <div class="cart-summary-row total">
                <span>Total Amount</span>
                <span>Rs. ${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
            </div>
        </div>
    `;

    cartDataInput.value = JSON.stringify(cart);

    form.addEventListener('submit', function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('Your cart is empty.');
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    });
});
</script>

</body>
</html>
