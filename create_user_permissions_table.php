<?php
require_once 'conexion.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS permisos_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        modulo_id INT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_permiso_usuario (usuario_id, modulo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Tabla 'permisos_usuarios' creada o verificada correctamente.";
} catch (PDOException $e) {
    echo "Error al crear tabla: " . $e->getMessage();
}
?>