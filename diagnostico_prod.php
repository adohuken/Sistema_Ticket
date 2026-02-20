<?php
/**
 * diagnostico_prod.php - Verificar tablas y vistas faltantes en producción
 * ELIMINAR DESPUÉS DE USAR
 */
require_once __DIR__ . '/conexion.php';

$tablas_requeridas = [
    // Existentes originales
    'tickets',
    'usuarios',
    'roles',
    'categorias',
    'asignaciones',
    'modulos',
    'permisos_roles',
    'permisos_usuarios',
    'empresas',
    'sucursales',
    'usuarios_accesos',
    'inventario',
    'personal',
    // Nuevas tablas
    'mantenimiento_equipos',
    'mantenimiento_solicitudes',
    'registros_365',
    'notificaciones',
    'formularios_rrhh',
    'herramientas_tecnico',
    'ticket_comentarios',
    'ticket_adjuntos',
    'log_actividad',
    'configuracion_sistema',
];

$vistas_requeridas = [
    'vista_personal_completo',
];

echo "<h2>Diagnóstico de Base de Datos en Producción</h2>";
echo "<pre style='font-family:monospace;'>";
echo "Servidor: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

echo "=== TABLAS ===\n";
$faltantes = [];
foreach ($tablas_requeridas as $tabla) {
    try {
        $res = $pdo->query("SELECT 1 FROM `$tabla` LIMIT 1");
        echo "[OK]      $tabla\n";
    } catch (PDOException $e) {
        echo "[FALTA]   $tabla  <--- " . $e->getMessage() . "\n";
        $faltantes[] = $tabla;
    }
}

echo "\n=== VISTAS ===\n";
foreach ($vistas_requeridas as $vista) {
    try {
        $res = $pdo->query("SELECT 1 FROM `$vista` LIMIT 1");
        echo "[OK]      $vista\n";
    } catch (PDOException $e) {
        echo "[FALTA]   $vista  <--- " . $e->getMessage() . "\n";
        $faltantes[] = $vista;
    }
}

echo "\n=== RESUMEN ===\n";
if (empty($faltantes)) {
    echo "Todo OK. No faltan tablas ni vistas.\n";
} else {
    echo "Faltan " . count($faltantes) . " tabla(s)/vista(s):\n";
    foreach ($faltantes as $f) {
        echo "  - $f\n";
    }
}
echo "</pre>";
?>