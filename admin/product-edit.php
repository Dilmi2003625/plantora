<?php
/**
 * Plantora E-Commerce
 * Admin Edit Product
 * File: admin/product-edit.php
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$productId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch existing product
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ?");
$product = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if (!$product) {
    die("Product not found."); // Simple die as instructed not to expose DB errors, but provide clear message
}

// Fetch categories
$categories = [];
$catRes = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $categories[] = $row;
    }
}
$allowedTypes = ['Plant', 'Pot', 'Package'];

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        $productName = trim($_POST['product_name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $productType = trim($_POST['product_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $careInstructions = trim($_POST['care_instructions'] ?? '');
        
        if (empty($productName)) {
            $error = "Product name is required.";
        } elseif ($categoryId <= 0) {
            $error = "Please select a valid category.";
        } elseif (!in_array($productType, $allowedTypes)) {
            $error = "Invalid product type.";
        } else {
            $imagePath = $product['image']; // retain old image
            
            // Handle Image Upload if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['image']['tmp_name'];
                $fileName = $_FILES['image']['name'];
                $fileSize = $_FILES['image']['size'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (!in_array($fileExt, $allowedExts)) {
                    $error = "Invalid image format. Allowed: JPG, PNG, WEBP.";
                } elseif ($fileSize > 5 * 1024 * 1024) {
                    $error = "Image size exceeds 5MB limit.";
                } else {
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = uniqid('prod_') . '.' . $fileExt;
                    $destination = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmp, $destination)) {
                        $imagePath = 'images/uploads/' . $newFileName;
                    } else {
                        $error = "Failed to save uploaded image.";
                    }
                }
            }

            if (empty($error)) {
                $updStmt = mysqli_prepare($conn, "UPDATE products SET category_id=?, product_name=?, product_type=?, description=?, care_instructions=?, image=? WHERE product_id=?");
                if ($updStmt) {
                    mysqli_stmt_bind_param($updStmt, "isssssi", $categoryId, $productName, $productType, $description, $careInstructions, $imagePath, $productId);
                    if (mysqli_stmt_execute($updStmt)) {
                        $success = "Product updated successfully.";
                        // refresh local variables
                        $product['product_name'] = $productName;
                        $product['category_id'] = $categoryId;
                        $product['product_type'] = $productType;
                        $product['description'] = $description;
                        $product['care_instructions'] = $careInstructions;
                        $product['image'] = $imagePath;
                    } else {
                        $error = "Database error. Could not update product.";
                    }
                    mysqli_stmt_close($updStmt);
                }
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
    <title>Edit Product | Plantora Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css?v=2">
    <style>
        .form-container { background: #fff; padding: 30px; border-radius: 8px; border: 1px solid var(--border); max-width: 800px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-family: inherit; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .btn-submit { background: var(--primary); color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-submit:hover { background: var(--primary-dark); }
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
        <h1>Edit Product</h1>
    </header>
    <a href="products.php" style="display:inline-block; margin-bottom:20px; color:var(--primary);">&larr; Back to Products</a>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($product['product_name']); ?>">
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Product Type *</label>
                <select name="product_type" class="form-control" required>
                    <?php foreach ($allowedTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo ($product['product_type'] === $type) ? 'selected' : ''; ?>>
                            <?php echo $type; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Care Instructions</label>
                <textarea name="care_instructions" class="form-control"><?php echo htmlspecialchars($product['care_instructions'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Product Image</label>
                <?php if ($product['image']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../<?php echo strpos($product['image'], '/')===false ? 'images/products/'.$product['image'] : $product['image']; ?>" alt="Current Image" style="height: 100px; border-radius: 4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small style="color:var(--text-muted);">Leave empty to keep existing image. Allowed formats: JPG, PNG, WEBP. Max size: 5MB.</small>
            </div>

            <button type="submit" name="edit_product" class="btn-submit">Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>
