<?php
/**
 * Plantora E-Commerce
 * Admin Add Variation
 * File: admin/variation-add.php
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

// Fetch Product Info to verify it exists
$stmt = mysqli_prepare($conn, "SELECT product_name FROM products WHERE product_id = ?");
$productName = "";
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

$allowedPotOptions = ['Without Pot', 'With Pot'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_variation'])) {
    if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        $color = trim($_POST['color'] ?? '');
        $size = trim($_POST['size'] ?? '');
        $potOption = trim($_POST['pot_option'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        
        if ($price <= 0) {
            $error = "Price must be greater than 0.";
        } elseif ($stock < 0) {
            $error = "Stock cannot be negative.";
        } elseif (!empty($potOption) && !in_array($potOption, $allowedPotOptions)) {
            $error = "Invalid pot option.";
        } else {
            // Empty string for pot_option should become NULL if not applicable
            $finalPotOption = empty($potOption) ? null : $potOption;
            
            $ins = mysqli_prepare($conn, "INSERT INTO product_variations (product_id, color, size, pot_option, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            if ($ins) {
                mysqli_stmt_bind_param($ins, "isssdi", $productId, $color, $size, $finalPotOption, $price, $stock);
                if (mysqli_stmt_execute($ins)) {
                    header("Location: product-variations.php?product_id=" . $productId . "&msg=created");
                    exit;
                } else {
                    $error = "Failed to add variation.";
                }
                mysqli_stmt_close($ins);
            }
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Variation | Plantora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
    <style>
        .form-container { background: #fff; padding: 30px; border-radius: 8px; border: 1px solid var(--border); max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-family: inherit; }
        .btn-submit { background: var(--primary); color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--primary-dark); }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-error { background: var(--danger-light); color: var(--danger); border: 1px solid #fadbd8; }
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
        <h1>Add Variation for <?php echo htmlspecialchars($productName); ?></h1>
    </header>
    
    <a href="product-variations.php?product_id=<?php echo $productId; ?>" style="display:inline-block; margin-bottom:20px; color:var(--primary);">&larr; Back to Variations</a>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label>Color (Optional)</label>
                <input type="text" name="color" class="form-control" placeholder="e.g. Green, Terracotta" value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Size (Optional)</label>
                <input type="text" name="size" class="form-control" placeholder="e.g. Small, Medium, Large" value="<?php echo htmlspecialchars($_POST['size'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Pot Option (Optional)</label>
                <select name="pot_option" class="form-control">
                    <option value="">-- None / Not Applicable --</option>
                    <?php foreach ($allowedPotOptions as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (isset($_POST['pot_option']) && $_POST['pot_option'] === $opt) ? 'selected' : ''; ?>>
                            <?php echo $opt; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Price (Rs.) *</label>
                <input type="number" step="0.01" min="1" name="price" class="form-control" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Stock Quantity *</label>
                <input type="number" min="0" name="stock_quantity" class="form-control" required value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>">
            </div>

            <button type="submit" name="add_variation" class="btn-submit">Add Variation</button>
        </form>
    </div>
</main>
</body>
</html>
