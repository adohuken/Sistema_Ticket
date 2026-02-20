<?php
require 'conexion.php';
try {
    echo "--- Mantenimiento Solicitudes ---\n";
    $stmt = $pdo->query("DESCRIBE mantenimiento_solicitudes");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    echo "\n--- Mantenimiento Equipos ---\n";
    $stmt = $pdo->query("DESCRIBE mantenimiento_equipos");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}