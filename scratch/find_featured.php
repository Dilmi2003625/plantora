<?php
require_once __DIR__ . '/../config/db.php';
$names = ['Snake Plant', 'Peace Lily', 'ZZ Plant', 'Monstera', 'Pothos', 'Aloe Vera'];
foreach ($names as $name) {
    $res = mysqli_query($conn, "SELECT p.product_id, p.product_name, p.image, pv.variation_id, pv.price, pv.stock_quantity, pv.size, pv.pot_option FROM products p JOIN product_variations pv ON p.product_id = pv.product_id WHERE p.product_name LIKE '%$name%' ORDER BY pv.variation_id ASC LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        echo "Found: {$row['product_name']} -> Product ID {$row['product_id']}, Variation ID {$row['variation_id']}, Price {$row['price']}, Stock {$row['stock_quantity']}, Size {$row['size']}, Image {$row['image']}\n";
    } else {
        echo "Not found for $name\n";
    }
}
