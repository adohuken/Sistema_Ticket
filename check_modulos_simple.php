<?php
require 'conexion.php';
$stmt = $pdo->query("DESCRIBE modulos");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(", ", $cols);

echo "\nRows:\n";
$stmt = $pdo->query("SELECT * FROM modulos");
foreach ($stmt as $row) {
    print_r($row);
}
?>