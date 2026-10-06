<?php
/**
 * Plantora E-Commerce
 * Admin Orders Management
 * File: admin/orders.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Fetch all orders with their payment status
$ordersQuery = "
    SELECT 
        o.order_id, 
        u.name AS customer_name, 
        o.order_date, 
        o.total_amount, 
        o.order_status,
        p.payment_status
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN payments p ON o.order_id = p.order_id
    ORDER BY o.order_id DESC
";

$orders = [];
$res = mysqli_query($conn, $ordersQuery);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $orders[] = $row;
    }
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
    <title>Orders Management | Plantora Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
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
        <a href="inventory.php">Inventory</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.php">Back to Store</a>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="admin-main">
    
    <header class="admin-header">
        <h1>Orders Management</h1>
        <div class="admin-user">
            Welcome, <?php echo htmlspecialchars($adminName); ?>
        </div>
    </header>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Order Date</th>
                    <th>Total Amount</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #666;">No orders found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                        <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <?php if (!empty($order['payment_status'])): ?>
                                <span class="status-badge <?php echo getStatusClass($order['payment_status']); ?>">
                                    <?php echo htmlspecialchars($order['payment_status']); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Not Available</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo getStatusClass($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="order-details.php?id=<?php echo (int)$order['order_id']; ?>" class="btn-action">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>
