<?php
require 'conexion.php';

try {
    $tecnico_role_id = 3;

    // Modules that Tecnico should have
    $modules_needed = ['historial_tecnico', 'reportes', 'estadisticas_globales', 'mis_tareas'];

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");

    foreach ($modules_needed as $mod_name) {
        $stmt_m = $pdo->prepare("SELECT id FROM modulos WHERE nombre = ?");
        $stmt_m->execute([$mod_name]);
        $mod_id = $stmt_m->fetchColumn();

        if (!$mod_id) {
            echo "Module not found: $mod_name\n";
            continue;
        }

        $stmt_check->execute([$tecnico_role_id, $mod_id]);
        if ($stmt_check->fetchColumn() == 0) {
            $stmt_insert->execute([$tecnico_role_id, $mod_id]);
            echo "Inserted: $mod_name (ID: $mod_id)\n";
        } else {
            echo "Already exists: $mod_name\n";
        }
    }

    // Verify final state
    echo "\n--- Final Role 3 Permissions ---\n";
    $stmt_v = $pdo->query("SELECT m.nombre FROM permisos_roles pr JOIN modulos m ON pr.modulo_id = m.id WHERE pr.rol_id = 3 ORDER BY m.nombre");
    $perms = $stmt_v->fetchAll(PDO::FETCH_COLUMN);
    foreach ($perms as $p) {
        echo "- $p\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>