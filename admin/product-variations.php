<?php
/**
 * Plantora E-Commerce
 * Admin Product Variations Management
 * File: admin/product-variations.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$productId = (int)($_GET['product_id'] ?? 0);
$error = '';
$success = '';

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch Product Info
$stmt = mysqli_prepare($conn, "SELECT product_name FROM products WHERE product_id = ?");
$productName = "Unknown Product";
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $productName = $row['product_name'];
    } else {
        die("Product not found.");
    }
    mysqli_stmt_close($stmt);
}

// Handle Delete/Deactivate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_variation'])) {
    if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        $variationId = (int)$_POST['variation_id'];
        
        // Check foreign keys in cart_items and order_items
        $inCart = 0;
        $inOrders = 0;
        
        $chk1 = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM cart_items WHERE variation_id = ?");
        mysqli_stmt_bind_param($chk1, "i", $variationId);
        mysqli_stmt_execute($chk1);
        $res1 = mysqli_stmt_get_result($chk1);
        $inCart = mysqli_fetch_assoc($res1)['cnt'];
        mysqli_stmt_close($chk1);
        
        $chk2 = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM order_items WHERE variation_id = ?");
        mysqli_stmt_bind_param($chk2, "i", $variationId);
        mysqli_stmt_execute($chk2);
        $res2 = mysqli_stmt_get_result($chk2);
        $inOrders = mysqli_fetch_assoc($res2)['cnt'];
        mysqli_stmt_close($chk2);
        
        if ($inCart > 0 || $inOrders > 0) {
            $error = "Cannot delete this variation because it is referenced in existing orders or carts. Please set its stock to 0 to effectively deactivate it.";
        } else {
            $del = mysqli_prepare($conn, "DELETE FROM product_variations WHERE variation_id = ?");
            mysqli_stmt_bind_param($del, "i", $variationId);
            if (mysqli_stmt_execute($del)) {
                $success = "Variation deleted successfully.";
            } else {
                $error = "Failed to delete variation.";
            }
            mysqli_stmt_close($del);
        }
    }
}

// Fetch Variations
$variations = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM product_variations WHERE product_id = ? ORDER BY variation_id ASC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $variations[] = $row;
    }
    mysqli_stmt_close($stmt);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <title>Manage Variations | Plantora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
    <style>
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .btn-add { background: var(--primary); color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: 600; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #fadbd8; }
        .alert-success { background: var(--success-light); color: #27ae60; border: 1px solid #d4efdf; }
    </style>
</head>
<body>
<aside class="admin-sidebar">
    <div class="sidebar-header">Plantora Admin</div>
    <nav class="sidebar-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php" class="active">Products</a>
        <a href="orders.php">Orders</a>
    </nav>
</aside>
<main class="admin-main">
    <header class="admin-header">
        <h1>Variations: <?php echo htmlspecialchars($productName); ?></h1>
    </header>
    
    <a href="products.php" style="display:inline-block; margin-bottom:20px; color:var(--primary);">&larr; Back to Products</a>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="header-actions">
        <p>Manage pricing and stock for different variations of this product.</p>
        <a href="variation-add.php?product_id=<?php echo $productId; ?>" class="btn-add">+ Add Variation</a>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>Pot Option</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($variations)): ?>
                    <tr><td colspan="7" style="text-align: center;">No variations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($variations as $v): ?>
                        <tr>
                            <td>#<?php echo $v['variation_id']; ?></td>
                            <td><?php echo htmlspecialchars($v['color'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($v['size'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($v['pot_option'] ?? '-'); ?></td>
                            <td>Rs. <?php echo number_format($v['price'], 2); ?></td>
                            <td>
                                <?php echo $v['stock_quantity']; ?> <br>
                                <?php echo getStockBadge($v['stock_quantity']); ?>
                            </td>
                            <td style="display:flex; gap:10px;">
                                <a href="variation-edit.php?id=<?php echo $v['variation_id']; ?>" class="btn-action">Edit</a>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this variation? Note: Variations already in customer orders cannot be deleted.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="variation_id" value="<?php echo $v['variation_id']; ?>">
                                    <button type="submit" name="delete_variation" class="btn-action" style="background:var(--danger-light); color:var(--danger); border:none; cursor:pointer;">Delete</button>
                                </form>
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
