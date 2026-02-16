<?php
require_once 'conexion.php';

// Bulk update empty or null states to 'Bueno'
$sql = "UPDATE inventario SET estado = 'Bueno' WHERE estado IS NULL OR estado = ''";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$count = $stmt->rowCount();

echo "Se actualizaron $count registros. El estado ahora es 'Bueno' por defecto.";
?>