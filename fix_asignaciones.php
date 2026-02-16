<?php
require_once __DIR__ . '/conexion.php';

echo "=== MIGRACIÓN DE DATOS: NOMBRES A IDS ===\n";

// 1. Obtener todos los inventarios con asignado_a no numérico
$stmt = $pdo->query("SELECT id, asignado_a FROM inventario WHERE asignado_a IS NOT NULL AND asignado_a NOT REGEXP '^[0-9]+$'");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($equipos)) {
    echo "No se encontraron registros para corregir.\n";
    exit;
}

echo "Encontrados " . count($equipos) . " registros con nombres en lugar de IDs.\n\n";

$actualizados = 0;
foreach ($equipos as $eq) {
    $nombre = trim($eq['asignado_a']);
    echo "Procesando Equipo ID {$eq['id']} (Asignado a: '$nombre')...\n";

    // Intentar buscar ID por nombre en vista_personal_completo
    // La vista concatena nombres y apellidos, así que buscamos coincidencia
    // Como el nombre guardado parece ser nombre completo, buscamos por LIKE

    // Primero intentamos búsqueda exacta en nombre_completo de usuarios si existe columna
    // Si no, buscamos en nombres y apellidos

    // Asumimos que el nombre guardado es "Nombre Apellido"
    $stmt_u = $pdo->prepare("SELECT id FROM vista_personal_completo WHERE CONCAT(nombres, ' ', apellidos) = ? LIMIT 1");
    $stmt_u->execute([$nombre]);
    $userId = $stmt_u->fetchColumn();

    // Si no encuentra, intentar LIKE
    if (!$userId) {
        $stmt_u = $pdo->prepare("SELECT id FROM vista_personal_completo WHERE ? LIKE CONCAT('%', nombres, ' ', apellidos, '%') LIMIT 1");
        $stmt_u->execute([$nombre]);
        $userId = $stmt_u->fetchColumn();
    }

    // Intento 3: buscar en tabla usuarios si tiene columna nombre_completo (según schema check anterior, sí tiene)
    if (!$userId) {
        $stmt_u = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_completo = ? LIMIT 1");
        $stmt_u->execute([$nombre]);
        $userId = $stmt_u->fetchColumn();
    }

    if ($userId) {
        echo "  -> Encontrado Usuario ID: $userId. Actualizando...\n";
        $stmt_upd = $pdo->prepare("UPDATE inventario SET asignado_a = ? WHERE id = ?");
        $stmt_upd->execute([$userId, $eq['id']]);
        $actualizados++;
    } else {
        echo "  [!] ERROR: No se encontró usuario para '$nombre'.\n";
    }
}

echo "\nResultados: $actualizados registros actualizados correctamente.\n";
?>