<?php
require 'conexion.php';

try {
    $output = "";

    // 1. Find Users with Role ID 3
    $stmt_u = $pdo->prepare("SELECT * FROM usuarios WHERE rol_id = 3");
    $stmt_u->execute();
    $users = $stmt_u->fetchAll();

    $output .= "Users with Role ID 3:\n";
    foreach ($users as $u) {
        $output .= "ID: " . $u['id'] . " - " . $u['nombre_completo'] . "\n";

        // Check User Perms
        $stmt_up = $pdo->prepare("SELECT m.nombre FROM permisos_usuarios pu JOIN modulos m ON pu.modulo_id = m.id WHERE pu.usuario_id = ?");
        $stmt_up->execute([$u['id']]);
        $up = $stmt_up->fetchAll(PDO::FETCH_COLUMN);
        $output .= "  Extra Perms: " . implode(', ', $up) . "\n";
    }

    // 2. Insert Permissions for Role ID 3
    $modules_to_add = ['historial_tecnico', 'reportes', 'estadisticas_globales'];

    foreach ($modules_to_add as $mod_name) {
        // Get ID
        $stmt_m = $pdo->prepare("SELECT id FROM modulos WHERE nombre = ?");
        $stmt_m->execute([$mod_name]);
        $mod_id = $stmt_m->fetchColumn();

        if ($mod_id) {
            // Check existence
            $stmt_c = $pdo->prepare("SELECT Count(*) FROM permisos_roles WHERE rol_id = 3 AND modulo_id = ?");
            $stmt_c->execute([$mod_id]);
            if ($stmt_c->fetchColumn() == 0) {
                $stmt_i = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (3, ?)");
                $stmt_i->execute([$mod_id]);
                $output .= "Inserted $mod_name ($mod_id)\n";
            } else {
                $output .= "Exists $mod_name ($mod_id)\n";
            }
        } else {
            $output .= "Module $mod_name not found!\n";
        }
    }

    // Attempt explicit commit if transaction logic exists (though default PDO is autocommit)
    // $pdo->commit(); 

    file_put_contents('debug_final.txt', $output);
    echo "Done.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>