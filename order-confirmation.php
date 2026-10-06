<?php
session_start();
require_once 'config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, p.payment_method, p.payment_status FROM orders o LEFT JOIN payments p ON p.order_id = o.order_id WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    // Order not found or doesn't belong to the user
    header("Location: profile.php");
    exit();
}

// Fetch order items
$stmt = $conn->prepare("SELECT oi.*, pr.product_name, pv.size, pv.color FROM order_items oi JOIN product_variations pv ON pv.variation_id = oi.variation_id JOIN products pr ON pr.product_id = pv.product_id WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$pageTitle = 'Order Confirmation | Plantora';
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
        .confirmation-wrap {
            max-width: 800px;
            margin: 60px auto 100px;
            padding: 0 5%;
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            color: #176b36;
            margin-bottom: 20px;
        }
        .confirmation-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: #0b3d20;
            margin-bottom: 10px;
        }
        .confirmation-subtitle {
            font-size: 16px;
            color: #4a6353;
            margin-bottom: 40px;
        }
        .order-details-card {
            background: #fff;
            border: 1px solid #e2ece5;
            border-radius: 12px;
            padding: 30px;
            text-align: left;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 30px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #f0f4f1;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .order-header-info h3 {
            font-size: 20px;
            color: #0b3d20;
            margin-bottom: 5px;
        }
        .order-header-info p {
            font-size: 14px;
            color: #4a6353;
        }
        .order-status-badge {
            background: #eaf5ec;
            color: #176b36;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        .order-section {
            margin-bottom: 25px;
        }
        .order-section h4 {
            font-size: 16px;
            color: #0b3d20;
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f4f1;
            padding-bottom: 5px;
        }
        .order-section p {
            font-size: 14px;
            color: #4a6353;
            line-height: 1.6;
        }
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .order-items-table th, .order-items-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #f0f4f1;
            font-size: 14px;
        }
        .order-items-table th {
            color: #0b3d20;
            font-weight: 600;
            background: #fbfdfc;
        }
        .order-items-table td {
            color: #4a6353;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 10px;
            font-size: 14px;
            color: #4a6353;
        }
        .totals-row.grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #0b3d20;
            border-top: 1px solid #e2ece5;
            padding-top: 15px;
            margin-top: 5px;
        }
        .btn-continue {
            display: inline-block;
            background: #176b36;
            color: #fff;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-continue:hover {
            background: #0f4f26;
        }
    </style>
</head>
<body>

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

<main class="confirmation-wrap">
    <div class="success-icon">
        <i class="fa-regular fa-circle-check"></i>
    </div>
    <?php if (isset($_GET['cancelled']) && $_GET['cancelled'] == 1): ?>
        <h1 class="confirmation-title" style="color: #c0392b;">Payment Cancelled</h1>
        <p class="confirmation-subtitle">You have cancelled the payment process. Your order has been saved, but remains unpaid.</p>
    <?php elseif ($order['payment_method'] === 'Online Payment' && $order['payment_status'] === 'Pending'): ?>
        <h1 class="confirmation-title">Order Received</h1>
        <p class="confirmation-subtitle">Your order has been placed, but your payment is being verified. Please check your order status shortly.</p>
    <?php elseif ($order['payment_method'] === 'Online Payment' && $order['payment_status'] === 'Failed'): ?>
        <h1 class="confirmation-title" style="color: #c0392b;">Payment Failed</h1>
        <p class="confirmation-subtitle">There was an issue processing your payment. Please try again or contact support.</p>
    <?php else: ?>
        <h1 class="confirmation-title">Thank You for Your Order!</h1>
        <p class="confirmation-subtitle">Your order has been successfully placed and is now being processed.</p>
    <?php endif; ?>
    
    <div class="order-details-card">
        <div class="order-header">
            <div class="order-header-info">
                <h3>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                <p>Placed on <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></p>
            </div>
            <div>
                <div class="order-status-badge"><?php echo htmlspecialchars($order['order_status']); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
            <div class="order-section">
                <h4>Delivery Address</h4>
                <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
            </div>
            <div class="order-section">
                <h4>Payment Method</h4>
                <p><?php echo htmlspecialchars($order['payment_method']); ?> (<?php echo htmlspecialchars($order['payment_status']); ?>)</p>
            </div>
        </div>

        <div class="order-section">
            <h4>Ordered Products</h4>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            <br>
                            <span style="font-size: 12px; color: #7f8c8d;">
                                <?php if($item['color']) echo "Color: " . htmlspecialchars($item['color']) . " "; ?>
                                <?php if($item['size']) echo "Size: " . htmlspecialchars($item['size']); ?>
                            </span>
                        </td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right;">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="totals-row">
                <span>Subtotal</span>
                <span>Rs. <?php echo number_format($order['subtotal'], 2); ?></span>
            </div>
            <div class="totals-row">
                <span>Delivery Charge</span>
                <span><?php echo $order['delivery_charge'] == 0 ? 'FREE' : 'Rs. ' . number_format($order['delivery_charge'], 2); ?></span>
            </div>
            <div class="totals-row grand-total">
                <span>Total Amount</span>
                <span>Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
    </div>
    
    <a href="shop.php" class="btn-continue">Continue Shopping</a>
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
<?php if (isset($_GET['clear_cart']) && $_GET['clear_cart'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.saveCart === 'function') {
            window.saveCart([]);
            if (typeof window.updateCartCounters === 'function') {
                window.updateCartCounters();
            }
        }
        // Remove clear_cart param from URL so refresh doesn't trigger it again unnecessarily
        const url = new URL(window.location);
        url.searchParams.delete('clear_cart');
        window.history.replaceState(null, '', url);
    });
</script>
<?php endif; ?>
</body>
</html>
