<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "Testing user cart...\n";
$cartId = getUserCartId($conn, 1);
echo "User Cart ID: $cartId\n";

$items = getUserCartItems($conn, 1);
echo "Items in DB for User 1:\n";
print_r($items);
