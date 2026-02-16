<?php
require_once 'conexion.php';
$stmt = $pdo->query("DESCRIBE inventario condicion");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>