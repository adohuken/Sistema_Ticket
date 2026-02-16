<?php
require 'c:/xampp/htdocs/Sistema_Ticket/conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS mantenimiento_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT,
    sucursal_id INT,
    fecha_programada DATE,
    titulo VARCHAR(100),
    descripcion TEXT,
    asignado_a INT,
    estado ENUM('Pendiente', 'En Proceso', 'Completado') DEFAULT 'Pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($sql);
    echo "Table 'mantenimiento_solicitudes' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>