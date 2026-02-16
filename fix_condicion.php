<?php
require_once 'conexion.php';

// Fix empty conditions
$sql = "UPDATE inventario SET condicion = 'Disponible' WHERE condicion IS NULL OR condicion = ''";
$stmt = $pdo->prepare($sql);
$stmt->execute();

echo "Condition correction executed. Rows affected: " . $stmt->rowCount() . "\n";
?>