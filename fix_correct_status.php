<?php
require_once 'conexion.php';

$ids = [2, 3, 4, 5, 6]; // The items with empty status
$in = str_repeat('?,', count($ids) - 1) . '?';
// Use the CORRECT value 'Buen Estado'
$sql = "UPDATE inventario SET estado = 'Buen Estado' WHERE id IN ($in)";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

echo "Correction executed. Rows affected: " . $stmt->rowCount() . "\n";
?>