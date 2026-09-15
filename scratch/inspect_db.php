<?php
require_once __DIR__ . '/../config/db.php';

echo "Connected successfully to " . $database . "\n";
$tables = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($tables)) {
    $tableName = $row[0];
    echo "\n--- TABLE: $tableName ---\n";
    $cols = mysqli_query($conn, "DESCRIBE `$tableName`");
    while ($col = mysqli_fetch_assoc($cols)) {
        echo "  " . str_pad($col['Field'], 20) . " " . str_pad($col['Type'], 20) . " " . $col['Null'] . " " . $col['Key'] . "\n";
    }
}
