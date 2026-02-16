<?php
require 'conexion.php';
try {
    $stmt = $pdo->query("DESCRIBE modulos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);

    echo "\n\nContent:\n";
    $stmt = $pdo->query("SELECT * FROM modulos LIMIT 5");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
?>