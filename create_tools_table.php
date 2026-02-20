<?php
require_once 'conexion.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS herramientas_tecnico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            url VARCHAR(255) NOT NULL,
            icono VARCHAR(50) DEFAULT 'ri-link',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tabla 'herramientas_tecnico' creada o ya existe.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>