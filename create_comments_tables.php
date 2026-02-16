<?php
require_once 'conexion.php';

try {
    // 1. Tabla de Comentarios
    $sql_comentarios = "CREATE TABLE IF NOT EXISTS ticket_comentarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        usuario_id INT NOT NULL,
        comentario TEXT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        es_interno BOOLEAN DEFAULT 0,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql_comentarios);
    echo "Tabla ticket_comentarios verificada/creada.<br>";

    // 2. Tabla de Adjuntos
    $sql_adjuntos = "CREATE TABLE IF NOT EXISTS ticket_adjuntos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        usuario_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(255) NOT NULL,
        tipo_archivo VARCHAR(50),
        tamano_archivo INT,
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql_adjuntos);
    echo "Tabla ticket_adjuntos verificada/creada.<br>";

    // Crear carpeta uploads si no existe
    $upload_dir = __DIR__ . '/uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Directorio 'uploads' creado.<br>";
    } else {
        echo "Directorio 'uploads' ya existe.<br>";
    }

} catch (PDOException $e) {
    die("Error al crear tablas: " . $e->getMessage());
}
?>