<?php
/**
 * Plantora E-Commerce
 * Admin Order Details
 * File: admin/order-details.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Check if order exists
$order = null;
if ($orderId > 0) {
    $stmt = mysqli_prepare($conn, "
        SELECT 
            o.*, 
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
            p.payment_method, p.payment_status
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN payments p ON o.order_id = p.order_id
        WHERE o.order_id = ?
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }
}

if (!$order) {
    $error = "Order not found.";
}

// Handle Order Status Update
$allowedStatuses = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

if ($order && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = trim($_POST['order_status'] ?? '');
    
    // CSRF protection - simplified for admin
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request token.";
    } elseif (!in_array($newStatus, $allowedStatuses)) {
        $error = "Invalid order status provided.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET order_status = ? WHERE order_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $newStatus, $orderId);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Order status successfully updated to " . htmlspecialchars($newStatus) . ".";
                $order['order_status'] = $newStatus; // update local copy
            } else {
                $error = "Failed to update order status. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch order items
$orderItems = [];
if ($order) {
    $stmt = mysqli_prepare($conn, "
        SELECT 
            oi.quantity, oi.unit_price, oi.subtotal,
            pv.color, pv.size, pv.pot_option,
            pr.product_name
        FROM order_items oi
        LEFT JOIN product_variations pv ON oi.variation_id = pv.variation_id
        LEFT JOIN products pr ON pv.product_id = pr.product_id
        WHERE oi.order_id = ?
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $orderItems[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        case 'delivered': return 'status-delivered';
        case 'cancelled': return 'status-cancelled';
        case 'paid': return 'status-delivered';
        case 'failed': return 'status-cancelled';
        default: return 'status-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details | Plantora Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .details-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .details-card h3 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-dark);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        .detail-row {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .detail-row strong {
            display: inline-block;
            width: 130px;
            color: var(--text-muted);
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }
        .status-form select {
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-family: inherit;
        }
        .btn-submit {
            padding: 8px 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-error {
            background: var(--danger-light);
            color: var(--danger);
            border: 1px solid #fadbd8;
        }
        .alert-success {
            background: var(--success-light);
            color: #27ae60;
            border: 1px solid #d4efdf;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        Plantora Admin
    </div>
    <nav class="sidebar-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php" class="active">Orders</a>
        <a href="#" onclick="return false;" style="opacity:0.6;">Inventory</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.php">Back to Store</a>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-main">
    
    <header class="admin-header">
        <h1>Order Details</h1>
        <div class="admin-user">
            Welcome, <?php echo htmlspecialchars($adminName); ?>
        </div>
    </header>

    <a href="orders.php" class="back-link">&larr; Back to Orders</a>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
    <div class="details-grid">
        <div class="details-card">
            <h3>Customer & Delivery</h3>
            <div class="detail-row">
                <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?>
            </div>
            <div class="detail-row">
                <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?>
            </div>
            <div class="detail-row">
                <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?>
            </div>
            <div class="detail-row">
                <strong>Address:</strong><br>
                <span style="display:inline-block; margin-top:5px; color: var(--primary-dark);">
                    <?php echo nl2br(htmlspecialchars($order['delivery_address'] ?? 'N/A')); ?>
                </span>
            </div>
        </div>

        <div class="details-card">
            <h3>Order Information</h3>
            <div class="detail-row">
                <strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?>
            </div>
            <div class="detail-row">
                <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
            </div>
            <div class="detail-row">
                <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'Not Available'); ?>
            </div>
            <div class="detail-row">
                <strong>Payment Status:</strong> 
                <?php if (!empty($order['payment_status'])): ?>
                    <span class="status-badge <?php echo getStatusClass($order['payment_status']); ?>">
                        <?php echo htmlspecialchars($order['payment_status']); ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge status-pending">Not Available</span>
                <?php endif; ?>
            </div>
            <div class="detail-row">
                <strong>Order Status:</strong> 
                <span class="status-badge <?php echo getStatusClass($order['order_status']); ?>">
                    <?php echo htmlspecialchars($order['order_status']); ?>
                </span>
            </div>

            <!-- Status Update Form -->
            <form class="status-form" method="POST" action="order-details.php?id=<?php echo $orderId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label for="order_status" style="font-weight: 500; font-size: 14px;">Update Status:</label>
                <select name="order_status" id="order_status">
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo ($order['order_status'] === $status) ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_status" class="btn-submit">Update</button>
            </form>
        </div>
    </div>

    <h2 class="section-title">Order Items</h2>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Variation (Size/Color/Pot)</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orderItems)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No items found for this order.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orderItems as $item): ?>
                        <?php
                        $varDetails = [];
                        if (!empty($item['size'])) $varDetails[] = 'Size: ' . $item['size'];
                        if (!empty($item['color'])) $varDetails[] = 'Color: ' . $item['color'];
                        if (!empty($item['pot_option']) && $item['pot_option'] !== 'none') $varDetails[] = 'Pot: ' . $item['pot_option'];
                        $variationString = implode(' | ', $varDetails);
                        if (empty($variationString)) $variationString = 'Standard';
                        ?>
                    <tr>
                        <td style="font-weight: 500; color: var(--primary-dark);"><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></td>
                        <td style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($variationString); ?></td>
                        <td><?php echo (int)$item['quantity']; ?></td>
                        <td>Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="font-weight: 600;">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; padding-right: 20px;"><strong>Subtotal</strong></td>
                    <td style="font-weight: 600;">Rs. <?php echo number_format($order['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right; padding-right: 20px;"><strong>Delivery Charge</strong></td>
                    <td style="font-weight: 600;">Rs. <?php echo number_format($order['delivery_charge'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right; padding-right: 20px;"><strong>Total Amount</strong></td>
                    <td style="font-weight: 700; color: var(--primary-dark); font-size: 16px;">Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

</main>

</body>
</html>
