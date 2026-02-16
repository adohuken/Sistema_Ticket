<?php
require_once __DIR__ . '/conexion.php';

// 1. List All Modules
echo "--- MODULES IN DB ---\n";
$stmt = $pdo->query("SELECT id, nombre, etiqueta FROM modulos");
$mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($mods as $m) {
    echo "ID: {$m['id']} | Key: {$m['nombre']} | Label: {$m['etiqueta']}\n";
}

// 2. List Permissions for user PedroTest (or all users to find him)
echo "\n\n--- USER PERMISSIONS ---\n";
$stmt = $pdo->query("
    SELECT u.nombre_completo, u.rol_id, m.nombre as modulo_key 
    FROM usuarios u 
    JOIN permisos_usuarios pu ON u.id = pu.usuario_id 
    JOIN modulos m ON pu.modulo_id = m.id
    WHERE u.nombre_completo LIKE '%Pedro%' OR u.nombre_completo LIKE '%Test%'
");
$perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($perms)) {
    echo "No custom permissions found for any user named Pedro/Test.\n";
} else {
    foreach ($perms as $p) {
        echo "User: {$p['nombre_completo']} | Rol: {$p['rol_id']} | Has Access To: {$p['modulo_key']}\n";
    }
}
?>