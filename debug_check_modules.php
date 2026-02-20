<?php
require 'conexion.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM modulos WHERE nombre IN ('reportes', 'historial_tecnico', 'estadisticas_globales')");
    $stmt->execute();
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Modules Found:\n";
    foreach ($modulos as $m) {
        echo "- " . $m['nombre'] . " (ID: " . $m['id'] . ")\n";
    }

    if (empty($modulos)) {
        echo "No modules found matching 'reportes', 'historial_tecnico', or 'estadisticas_globales'.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>