<?php
require 'conexion.php';
try {
    $stmt = $pdo->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] == 'estado') {
            echo "Estado Type: " . $col['Type'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
