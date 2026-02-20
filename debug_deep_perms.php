<?php
require 'conexion.php';

try {
    $output = "";

    // 1. Check Roles
    $output .= "--- ROLES ---\n";
    $roles = $pdo->query("SELECT * FROM roles")->fetchAll();
    foreach ($roles as $r) {
        $output .= "ID: " . $r['id'] . " - " . $r['nombre'] . "\n";
    }

    // 2. Check Users named 'Tecnico%'
    $output .= "\n--- USUARIOS 'Tecnico%' ---\n";
    $stmt_u = $pdo->query("SELECT * FROM usuarios WHERE nombre_completo LIKE 'Tecnico%'");
    $users = $stmt_u->fetchAll();
    foreach ($users as $u) {
        $output .= "ID: " . $u['id'] . " - " . $u['nombre_completo'] . " (Rol: " . $u['rol_id'] . ")\n";

        // Check User Perms
        $stmt_up = $pdo->prepare("SELECT m.nombre FROM permisos_usuarios pu JOIN modulos m ON pu.modulo_id = m.id WHERE pu.usuario_id = ?");
        $stmt_up->execute([$u['id']]);
        $up = $stmt_up->fetchAll(PDO::FETCH_COLUMN);
        $output .= "  User Perms: " . implode(', ', $up) . "\n";
    }

    // 3. Check Role Permissions for 'Tecnico' (Dynamic ID)
    $tecnico_role_id = 0;
    foreach ($roles as $r) {
        if ($r['nombre'] === 'Tecnico')
            $tecnico_role_id = $r['id'];
    }
    $output .= "\n--- PERMISOS ROL 'Tecnico' (ID $tecnico_role_id) ---\n";

    $stmt2 = $pdo->prepare("SELECT modulo_id, m.nombre FROM permisos_roles pr JOIN modulos m ON pr.modulo_id = m.id WHERE rol_id = ?");
    $stmt2->execute([$tecnico_role_id]);
    $p_roles = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $output .= "Role Perms:\n";
    foreach ($p_roles as $p) {
        $output .= "- " . $p['nombre'] . " (ID: " . $p['modulo_id'] . ")\n";
    }

    file_put_contents('debug_output.txt', $output);
    echo "Done writing to debug_output.txt";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>