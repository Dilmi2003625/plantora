<?php
/**
 * Plantora E-Commerce
 * Admin Products Management
 * File: admin/products.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$searchQuery = trim($_GET['search'] ?? '');

$sql = "
    SELECT 
        p.product_id, 
        p.product_name, 
        p.product_type, 
        p.image,
        c.category_name,
        COUNT(pv.variation_id) as variation_count,
        COALESCE(SUM(pv.stock_quantity), 0) as total_stock
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN product_variations pv ON p.product_id = pv.product_id
";

$params = [];
$types = '';

if (!empty($searchQuery)) {
    $sql .= " WHERE p.product_name LIKE ? OR c.category_name LIKE ?";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$sql .= " GROUP BY p.product_id, p.product_name, p.product_type, p.image, c.category_name ORDER BY p.product_id DESC";

$stmt = mysqli_prepare($conn, $sql);
$products = [];
if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $products[] = $row;
    }
    mysqli_stmt_close($stmt);
}

function getStockBadge($stock) {
    if ($stock > 5) return '<span class="status-badge status-delivered">In Stock</span>';
    if ($stock > 0) return '<span class="status-badge status-pending">Low Stock</span>';
    return '<span class="status-badge status-cancelled">Out of Stock</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Plantora Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
            width: 300px;
        }
        .search-bar button {
            padding: 10px 20px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-add {
            background: var(--primary);
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        .prod-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background: #eee;
        }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="sidebar-header">Plantora Admin</div>
    <nav class="sidebar-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php" class="active">Products</a>
        <a href="orders.php">Orders</a>
        <a href="inventory.php">Inventory</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.php">Back to Store</a>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</aside>

<main class="admin-main">
    <header class="admin-header">
        <h1>Products</h1>
        <div class="admin-user">Welcome, <?php echo htmlspecialchars($adminName); ?></div>
    </header>

    <div class="header-actions">
        <form class="search-bar" method="GET" action="products.php">
            <input type="text" name="search" placeholder="Search product or category..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit">Search</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="products.php" style="line-height: 40px; margin-left:10px; color: var(--primary);">Clear</a>
            <?php endif; ?>
        </form>
        <a href="product-add.php" class="btn-add">+ Add Product</a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Variations</th>
                    <th>Stock Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" style="text-align: center;">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php 
                                    $imgSrc = $p['image'] ? '../' . $p['image'] : '../images/logo.png'; 
                                    // Handle cases where image is just filename
                                    if ($p['image'] && strpos($p['image'], '/') === false) {
                                        $imgSrc = '../images/products/' . $p['image']; 
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Product" class="prod-img" onerror="this.src='../images/logo.png';">
                            </td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($p['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($p['product_type']); ?></td>
                            <td><?php echo (int)$p['variation_count']; ?></td>
                            <td><?php echo getStockBadge((int)$p['total_stock']); ?></td>
                            <td>
                                <a href="product-edit.php?id=<?php echo $p['product_id']; ?>" class="btn-action">Edit</a>
                                <a href="product-variations.php?product_id=<?php echo $p['product_id']; ?>" class="btn-action" style="margin-left: 5px;">Variations</a>
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
