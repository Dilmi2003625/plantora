<?php
/**
 * Plantora E-Commerce
 * Admin Inventory Management
 * File: admin/inventory.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$error = '';
$success = '';

// Handle Stock Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        $variationId = (int)($_POST['variation_id'] ?? 0);
        $newStock = (int)($_POST['stock_quantity'] ?? -1);
        
        if ($variationId <= 0) {
            $error = "Invalid variation ID.";
        } elseif ($newStock < 0) {
            $error = "Stock quantity cannot be negative.";
        } else {
            $upd = mysqli_prepare($conn, "UPDATE product_variations SET stock_quantity = ? WHERE variation_id = ?");
            if ($upd) {
                mysqli_stmt_bind_param($upd, "ii", $newStock, $variationId);
                if (mysqli_stmt_execute($upd)) {
                    $success = "Stock updated successfully.";
                } else {
                    $error = "Failed to update stock.";
                }
                mysqli_stmt_close($upd);
            }
        }
    }
}

// Fetch categories for filter dropdown
$categories = [];
$catRes = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $categories[] = $row;
    }
}
$allowedTypes = ['Plant', 'Pot', 'Package'];

// Fetch Summary Stats
$stats = [
    'total_variations' => 0,
    'total_stock' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0
];
$statRes = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_variations,
        COALESCE(SUM(stock_quantity), 0) as total_stock,
        SUM(CASE WHEN stock_quantity BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM product_variations
");
if ($statRes) {
    $stats = mysqli_fetch_assoc($statRes);
}

// Build Search and Filter Query
$searchQuery = trim($_GET['search'] ?? '');
$filterCategory = (int)($_GET['category'] ?? 0);
$filterType = trim($_GET['type'] ?? '');
$filterStock = trim($_GET['stock_status'] ?? '');

$sql = "
    SELECT 
        pv.*, 
        p.product_name, 
        p.product_type, 
        c.category_name 
    FROM product_variations pv
    JOIN products p ON pv.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($searchQuery)) {
    $sql .= " AND (p.product_name LIKE ? OR c.category_name LIKE ? OR pv.color LIKE ? OR pv.size LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}
if ($filterCategory > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filterCategory;
    $types .= 'i';
}
if (!empty($filterType) && in_array($filterType, $allowedTypes)) {
    $sql .= " AND p.product_type = ?";
    $params[] = $filterType;
    $types .= 's';
}
if (!empty($filterStock)) {
    if ($filterStock === 'in_stock') {
        $sql .= " AND pv.stock_quantity > 5";
    } elseif ($filterStock === 'low') {
        $sql .= " AND pv.stock_quantity BETWEEN 1 AND 5";
    } elseif ($filterStock === 'out') {
        $sql .= " AND pv.stock_quantity = 0";
    }
}

$sql .= " ORDER BY p.product_name ASC, pv.variation_id ASC";

$inventory = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $inventory[] = $row;
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
    <title>Inventory | Plantora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=3">
    <style>
        .filter-bar {
            background: #fff;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .filter-bar input, .filter-bar select {
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-family: inherit;
        }
        .filter-bar button {
            padding: 8px 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .filter-bar a.clear-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #fadbd8; }
        .alert-success { background: var(--success-light); color: #27ae60; border: 1px solid #d4efdf; }
        .update-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .update-form input[type="number"] {
            width: 70px;
            padding: 5px;
            border: 1px solid var(--border);
            border-radius: 4px;
        }
        .update-form button {
            padding: 5px 10px;
            background: var(--primary-light);
            color: var(--primary-dark);
            border: 1px solid var(--primary);
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }
        .update-form button:hover {
            background: var(--primary);
            color: #fff;
        }
    </style>
</head>
<body>
<aside class="admin-sidebar">
    <div class="sidebar-header">Plantora Admin</div>
    <nav class="sidebar-nav">
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="inventory.php" class="active">Inventory</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../index.php">Back to Store</a>
        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
</aside>
<main class="admin-main">
    <header class="admin-header">
        <h1>Inventory Management</h1>
        <div class="admin-user">Welcome, <?php echo htmlspecialchars($adminName); ?></div>
    </header>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="summary-cards">
        <div class="card">
            <div class="card-title">Total Variations</div>
            <div class="card-value"><?php echo number_format($stats['total_variations'] ?? 0); ?></div>
        </div>
        <div class="card">
            <div class="card-title">Total Stock Units</div>
            <div class="card-value"><?php echo number_format($stats['total_stock'] ?? 0); ?></div>
        </div>
        <div class="card card-low-stock">
            <div class="card-title" style="color:var(--warning);">Low Stock (1-5)</div>
            <div class="card-value" style="color:var(--warning);"><?php echo number_format($stats['low_stock'] ?? 0); ?></div>
        </div>
        <div class="card card-low-stock">
            <div class="card-title" style="color:var(--danger);">Out of Stock (0)</div>
            <div class="card-value" style="color:var(--danger);"><?php echo number_format($stats['out_of_stock'] ?? 0); ?></div>
        </div>
    </div>

    <form class="filter-bar" method="GET" action="inventory.php">
        <input type="text" name="search" placeholder="Search product, category, size, color..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 250px;">
        
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filterCategory == $cat['category_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="type">
            <option value="">All Types</option>
            <?php foreach ($allowedTypes as $type): ?>
                <option value="<?php echo $type; ?>" <?php echo ($filterType === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
            <?php endforeach; ?>
        </select>

        <select name="stock_status">
            <option value="">All Stock Status</option>
            <option value="in_stock" <?php echo ($filterStock === 'in_stock') ? 'selected' : ''; ?>>In Stock (> 5)</option>
            <option value="low" <?php echo ($filterStock === 'low') ? 'selected' : ''; ?>>Low Stock (1-5)</option>
            <option value="out" <?php echo ($filterStock === 'out') ? 'selected' : ''; ?>>Out of Stock (0)</option>
        </select>

        <button type="submit">Filter</button>
        <?php if (!empty($searchQuery) || $filterCategory > 0 || !empty($filterType) || !empty($filterStock)): ?>
            <a href="inventory.php" class="clear-link">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Var ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Details (Color/Size/Pot)</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Stock Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory)): ?>
                    <tr><td colspan="8" style="text-align: center;">No inventory records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($inventory as $row): ?>
                        <?php
                            $details = [];
                            if (!empty($row['color'])) $details[] = $row['color'];
                            if (!empty($row['size'])) $details[] = $row['size'];
                            if (!empty($row['pot_option'])) $details[] = $row['pot_option'];
                            $detailStr = implode(' | ', $details);
                            if (empty($detailStr)) $detailStr = 'Standard';
                        ?>
                        <tr>
                            <td>#<?php echo $row['variation_id']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['product_type']); ?></td>
                            <td style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($detailStr); ?></td>
                            <td>Rs. <?php echo number_format($row['price'], 2); ?></td>
                            <td><?php echo getStockBadge((int)$row['stock_quantity']); ?></td>
                            <td>
                                <form class="update-form" method="POST" action="inventory.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?'.htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="variation_id" value="<?php echo $row['variation_id']; ?>">
                                    <input type="number" name="stock_quantity" min="0" value="<?php echo (int)$row['stock_quantity']; ?>" required>
                                    <button type="submit" name="update_stock">Update</button>
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
