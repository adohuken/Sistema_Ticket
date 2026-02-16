<?php
require './conexion.php';
try {
    $stmt = $pdo->query("SELECT count(*) FROM mantenimiento_equipos WHERE estado = 'Programado'");
    echo "Tickets Programados: " . $stmt->fetchColumn() . "\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>