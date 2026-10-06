<?php
/**
 * Plantora E-Commerce
 * Admin Dashboard Landing Page
 * File: admin/index.php
 */

// 1. Include the admin authentication guard FIRST
require_once __DIR__ . '/auth.php';

// Include database connection
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';

// ==========================================
// FETCH DASHBOARD SUMMARY DATA
// ==========================================

// 1. Total Products
$totalProducts = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalProducts = (int)$row['cnt'];
}

// 2. Total Orders
$totalOrders = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalOrders = (int)$row['cnt'];
}

// 3. Total Revenue (excluding Cancelled and Pending orders)
$totalRevenue = 0.0;
$res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE order_status NOT IN ('Cancelled', 'Pending')");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $totalRevenue = (float)$row['total'];
}

// 4. Low Stock
$lowStockCount = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM product_variations WHERE stock_quantity <= 5");
if ($res) {
    $row = mysqli_fetch_assoc($res);
    $lowStockCount = (int)$row['cnt'];
}

// ==========================================
// FETCH RECENT ORDERS
// ==========================================
$recentOrders = [];
$ordersQuery = "
    SELECT 
        o.order_id, 
        u.name AS customer_name, 
        o.order_date, 
        o.total_amount, 
        o.order_status 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_id DESC 
    LIMIT 5
";
$res = mysqli_query($conn, $ordersQuery);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recentOrders[] = $row;
    }
}

// Helper for status badge class
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        case 'delivered': return 'status-delivered';
        case 'cancelled': return 'status-cancelled';
        default: return 'status-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantora Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=1">
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        Plantora Admin
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="active">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
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
        <h1>Dashboard</h1>
        <div class="admin-user">
            Welcome, <?php echo htmlspecialchars($adminName); ?>
        </div>
    </header>

    <div class="summary-cards">
        <div class="card">
            <div class="card-title">Total Products</div>
            <div class="card-value"><?php echo number_format($totalProducts); ?></div>
        </div>
        <div class="card">
            <div class="card-title">Total Orders</div>
            <div class="card-value"><?php echo number_format($totalOrders); ?></div>
        </div>
        <div class="card">
            <div class="card-title">Total Revenue</div>
            <div class="card-value">Rs. <?php echo number_format($totalRevenue, 2); ?></div>
        </div>
        <a href="inventory.php?stock_status=low" class="card card-low-stock" style="text-decoration: none; display: block;">
            <div class="card-title" style="color:var(--danger);">Low Stock Items</div>
            <div class="card-value" style="color:var(--danger);"><?php echo number_format($lowStockCount); ?></div>
        </a>
    </div>

    <h2 class="section-title">Recent Orders</h2>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">No orders have been placed yet.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                        <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
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
