<?php
require_once __DIR__ . '/../config/db.php';
$res = mysqli_query($conn, 'SHOW CREATE TABLE cart_items');
$row = mysqli_fetch_row($res);
echo $row[1];
