<?php
require 'conexion.php';

try {
    // Valid query with JOIN
    $sql = "SELECT t.id, t.titulo, c.nombre as categoria_nombre, t.creador_id, t.fecha_creacion, u.nombre_completo as creador_nombre 
            FROM tickets t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN usuarios u ON t.creador_id = u.id
            WHERE c.nombre LIKE '%Formulario%' 
            ORDER BY t.id DESC LIMIT 10";

    $stmt = $pdo->query($sql);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($tickets) . " tickets with category name containing 'Formulario':\n\n";
    foreach ($tickets as $t) {
        echo "[ID: {$t['id']}] '{$t['titulo']}'\n";
        echo "   - Categoria: {$t['categoria_nombre']}\n";
        echo "   - Creado por: {$t['creador_nombre']} (ID: {$t['creador_id']})\n";
        echo "   - Fecha: {$t['fecha_creacion']}\n";
        echo "---------------------------------------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
