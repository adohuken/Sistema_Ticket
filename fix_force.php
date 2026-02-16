<?php
require_once 'conexion.php';

$ids = [2, 3, 4, 5, 6];
$in = str_repeat('?,', count($ids) - 1) . '?';
$sql = "UPDATE inventario SET estado = 'Bueno' WHERE id IN ($in)";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

echo "Forced update executed. Rows affected: " . $stmt->rowCount() . "\n";
?>