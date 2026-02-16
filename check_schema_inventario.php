<?php
require_once 'conexion.php';
$stmt = $pdo->query("SHOW FULL TABLES LIKE 'inventario'");
$row = $stmt->fetch(PDO::FETCH_NUM);
echo "Table: " . $row[0] . " | Type: " . $row[1] . "\n";

$stmt2 = $pdo->query("SHOW CREATE TABLE inventario");
$row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
print_r($row2);
?>