<?php
require 'conexion.php';

try {
    // 1. Get Technician Role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Tecnico'");
    $stmt->execute();
    $role_id = $stmt->fetchColumn();

    if (!$role_id) {
        die("Error: 'Tecnico' role not found.");
    }

    // 2. Get Module IDs for 'historial_tecnico' and 'reportes'
    $modules_to_sync = ['historial_tecnico', 'reportes', 'estadisticas_globales'];
    $module_ids = [];

    $stmt_mod = $pdo->prepare("SELECT id, nombre FROM modulos WHERE nombre IN (?, ?, ?)");
    $stmt_mod->execute($modules_to_sync);
    $modules = $stmt_mod->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modules as $m) {
        $module_ids[$m['nombre']] = $m['id'];
    }

    // 3. Insert permissions if they don't exist
    $inserted_count = 0;
    $stmt_check = $pdo->prepare("SELECT Count(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");

    foreach ($modules_to_sync as $mod_name) {
        if (isset($module_ids[$mod_name])) {
            $mod_id = $module_ids[$mod_name];

            // Check existence
            $stmt_check->execute([$role_id, $mod_id]);
            if ($stmt_check->fetchColumn() == 0) {
                // Insert
                $stmt_insert->execute([$role_id, $mod_id]);
                echo "Inserted permission: $mod_name for Tecnico (Role ID: $role_id, Module ID: $mod_id)\n";
                $inserted_count++;
            } else {
                echo "Permission already exists: $mod_name\n";
            }
        } else {
            echo "Warning: Module '$mod_name' not found in fields.\n";
        }
    }

    echo "Sync completed. $inserted_count permissions added.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>