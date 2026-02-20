<?php
require_once __DIR__ . '/conexion.php';

// Mostrar roles
echo "=== ROLES ===\n";
$roles = $pdo->query("SELECT id, nombre FROM roles")->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r)
    echo $r['id'] . ': ' . $r['nombre'] . "\n";

// Módulo notificaciones
$modulo_id = $pdo->query("SELECT id FROM modulos WHERE nombre = 'notificaciones'")->fetchColumn();
echo "\nMódulo notificaciones ID: $modulo_id\n";

// Asignar a todos los roles
foreach ($roles as $r) {
    $count = $pdo->prepare("SELECT COUNT(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
    $count->execute([$r['id'], $modulo_id]);
    if ($count->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)")->execute([$r['id'], $modulo_id]);
        echo "✅ Permiso asignado a rol '{$r['nombre']}'\n";
    } else {
        echo "ℹ️ Rol '{$r['nombre']}' ya tiene el permiso\n";
    }
}
echo "\nListo.\n";