<?php
require_once __DIR__ . '/conexion.php';

echo "=== Ajustando permisos RRHH de Gerencia ===\n\n";

try {
    // 1. Obtener ID del rol Gerencia
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Gerencia'");
    $stmt->execute();
    $rol_gerencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rol_gerencia) {
        echo "❌ Error: No se encontró el rol 'Gerencia'\n";
        exit(1);
    }

    $rol_id = $rol_gerencia['id'];
    echo "✅ Rol Gerencia encontrado (ID: $rol_id)\n\n";

    // 2. Eliminar permisos de Altas y Bajas
    $modulos_eliminar = ['rrhh_altas', 'rrhh_bajas'];
    $permisos_eliminados = 0;

    foreach ($modulos_eliminar as $modulo_nombre) {
        $stmt = $pdo->prepare("SELECT id FROM modulos WHERE nombre = ?");
        $stmt->execute([$modulo_nombre]);
        $modulo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($modulo) {
            $modulo_id = $modulo['id'];

            // Eliminar permiso
            $stmt = $pdo->prepare("DELETE FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
            $stmt->execute([$rol_id, $modulo_id]);

            if ($stmt->rowCount() > 0) {
                echo "✅ Permiso eliminado: $modulo_nombre\n";
                $permisos_eliminados++;
            } else {
                echo "ℹ️  Permiso no existía: $modulo_nombre\n";
            }
        }
    }

    echo "\n=== Resumen ===\n";
    echo "Permisos eliminados: $permisos_eliminados\n\n";

    // 3. Mostrar permisos actuales de Gerencia
    echo "Permisos actuales del rol Gerencia:\n";
    $stmt = $pdo->prepare("
        SELECT m.nombre 
        FROM permisos_roles pr 
        JOIN modulos m ON pr.modulo_id = m.id 
        WHERE pr.rol_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$rol_id]);
    $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($permisos as $permiso) {
        echo "  - $permiso\n";
    }

    echo "\n✅ Gerencia ahora solo tiene acceso de LECTURA al historial RRHH\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>