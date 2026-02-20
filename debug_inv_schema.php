<?php
require_once 'conexion.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM inventario");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - " . $col['Null'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>