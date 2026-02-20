<?php
require 'conexion.php';

try {
    $user_id = 3; // JuanTest

    // 1. Get current user extra perms for logging
    $stmt = $pdo->prepare("SELECT m.nombre FROM permisos_usuarios pu JOIN modulos m ON pu.modulo_id = m.id WHERE pu.usuario_id = ?");
    $stmt->execute([$user_id]);
    $extra = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "User $user_id had extra perms: " . implode(', ', $extra) . "\n";

    // 2. Delete all extra perms for this user
    $stmt_del = $pdo->prepare("DELETE FROM permisos_usuarios WHERE usuario_id = ?");
    $stmt_del->execute([$user_id]);

    echo "Deleted extra permissions for User $user_id.\n";

    // 3. Verify Role 3 permissions
    echo "\n--- Verifying Role 3 (Tecnico) Permissions ---\n";
    $stmt_role = $pdo->query("SELECT m.nombre FROM permisos_roles pr JOIN modulos m ON pr.modulo_id = m.id WHERE pr.rol_id = 3");
    $role_perms = $stmt_role->fetchAll(PDO::FETCH_COLUMN);
    echo "Role 3 has: " . implode(', ', $role_perms) . "\n";

    if (in_array('historial_tecnico', $role_perms)) {
        echo "OK: historial_tecnico is present.\n";
    } else {
        echo "WARNING: historial_tecnico is MISSING for Role 3!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>