<?php
require_once __DIR__ . '/../config/db.php';

$res = mysqli_query($conn, "SELECT p.product_id, p.product_name, p.image, pv.variation_id, pv.color, pv.size, pv.pot_option, pv.price, pv.stock_quantity FROM products p LEFT JOIN product_variations pv ON p.product_id = pv.product_id ORDER BY p.product_id, pv.variation_id");
while ($r = mysqli_fetch_assoc($res)) {
    echo "P#{$r['product_id']} {$r['product_name']} | Var#{$r['variation_id']} | size:{$r['size']} | pot:{$r['pot_option']} | col:{$r['color']} | Rs.{$r['price']} | stock:{$r['stock_quantity']} | img:{$r['image']}\n";
}
