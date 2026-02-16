<?php
require 'conexion.php';

// Actualizar tickets completos vacíos
$stmt = $pdo->prepare("UPDATE tickets SET resolucion = '📝 [15/12/2025 00:00] ready' WHERE estado = 'Completo' AND (resolucion IS NULL OR resolucion = '')");
$stmt->execute();

echo "Se actualizaron " . $stmt->rowCount() . " tickets vacíos con un valor por defecto.";
