<?php
require 'conexion.php';

try {
    $stmt = $pdo->query("
        SELECT m.nombre 
        FROM permisos_roles pr 
        JOIN modulos m ON pr.modulo_id = m.id 
        JOIN roles r ON pr.rol_id = r.id 
        WHERE r.nombre = 'Tecnico'
    ");
    $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Permisos Tecnico:\n";
    foreach ($perms as $p) {
        echo "- $p\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>