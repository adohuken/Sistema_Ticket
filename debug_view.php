<?php
require 'conexion.php';

try {
    $sql = "SELECT i.id, i.tipo, i.marca, i.modelo, i.serial, 
            CONCAT(u.nombres, ' ', u.apellidos) as usuario_asignado,
            s.nombre as sucursal
            FROM inventario i 
            LEFT JOIN vista_personal_completo u ON i.asignado_a = u.id
            LEFT JOIN sucursales s ON u.sucursal_id = s.id 
            WHERE i.estado != 'Baja' LIMIT 5";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Query OK. Rows: " . count($data) . "\n";
    print_r($data);

} catch (PDOException $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
