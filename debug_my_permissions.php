<?php
require 'db.php';

// Check permissions for PedroTest (or any user)
$user_name = "PedroTest"; // Change as needed

echo "<h1>Debug Permisos: $user_name</h1>";

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_completo = ?");
$stmt->execute([$user_name]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

echo "Role ID: " . $user['rol_id'] . "<br>";

// Get Permisos Usuario
$stmt_p = $pdo->prepare("
    SELECT m.nombre_clave 
    FROM permisos_usuarios pu
    JOIN modulos m ON pu.modulo_id = m.id
    WHERE pu.usuario_id = ?
");
$stmt_p->execute([$user['id']]);
$perms = $stmt_p->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Permisos Asignados (Directos):</h3>";
echo "<pre>" . print_r($perms, true) . "</pre>";

// Get Role Permissions
$stmt_r = $pdo->prepare("
    SELECT m.nombre_clave 
    FROM permisos_roles pr
    JOIN modulos m ON pr.modulo_id = m.id
    WHERE pr.rol_id = ?
");
$stmt_r->execute([$user['rol_id']]);
$role_perms = $stmt_r->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Permisos de Rol ($user[rol_id]):</h3>";
echo "<pre>" . print_r($role_perms, true) . "</pre>";

echo "<h3>Total Combined:</h3>";
$total = array_unique(array_merge($perms, $role_perms));
echo "<pre>" . print_r($total, true) . "</pre>";

?>