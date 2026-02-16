<?php
require 'conexion.php';
try {
    $stmt = $pdo->query("DESCRIBE personal_historial");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Estructura de personal_historial:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
