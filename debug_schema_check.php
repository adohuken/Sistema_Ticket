<?php
require 'conexion.php';

$output = "";
try {
    $output .= "--- mantenimiento_equipos ---\n";
    $stmt = $pdo->query("DESCRIBE mantenimiento_equipos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $output .= $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    $output .= "\n--- inventario ---\n";
    $stmt = $pdo->query("DESCRIBE inventario");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $output .= $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    $output .= "Error: " . $e->getMessage();
}

file_put_contents('schema_output.txt', $output);
echo "Schema saved to schema_output.txt";
