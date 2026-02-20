<?php
require 'conexion.php';

try {
    $stmt = $pdo->query("SELECT id, titulo, fecha_programada, estado FROM mantenimiento_solicitudes ORDER BY id DESC LIMIT 5");
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "--- Últimas 5 Visitas Programadas ---\n";
    foreach ($resultados as $row) {
        echo "ID: " . $row['id'] . " | Fecha: " . $row['fecha_programada'] . " | Estado: " . $row['estado'] . " | Título: " . $row['titulo'] . "\n";
    }

    if (empty($resultados)) {
        echo "No se encontraron registros en mantenimiento_solicitudes.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
