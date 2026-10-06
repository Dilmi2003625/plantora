<?php
require_once __DIR__ . '/../config/db.php';

$tables = ['users', 'products', 'product_variations', 'cart', 'cart_items', 'orders', 'order_items', 'payments'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']} - {$row['Extra']}\n";
        }
    } else {
        echo "Table does not exist or error: " . mysqli_error($conn) . "\n";
    }
    echo "\n";
}
