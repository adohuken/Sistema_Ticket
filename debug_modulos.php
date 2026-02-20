<?php
require 'conexion.php';

try {
    $stmt = $pdo->query("SELECT * FROM modulos ORDER BY nombre");
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Lista de Módulos en BD</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre (Key)</th><th>Etiqueta</th></tr>";
    foreach ($modulos as $m) {
        echo "<tr>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td>" . $m['nombre'] . "</td>";
        echo "<td>" . $m['etiqueta'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2>Permisos del Rol Tecnico</h2>";
    $stmt2 = $pdo->query("
        SELECT m.nombre 
        FROM permisos_roles pr 
        JOIN modulos m ON pr.modulo_id = m.id 
        JOIN roles r ON pr.rol_id = r.id 
        WHERE r.nombre = 'Tecnico'
    ");
    $perms = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($perms, true) . "</pre>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>