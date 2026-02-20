<?php
require 'conexion.php';

try {
    $output = "";

    // 1. Get SuperAdmin Role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'SuperAdmin'");
    $stmt->execute();
    $role_id = $stmt->fetchColumn();

    if (!$role_id) {
        die("Error: 'SuperAdmin' role not found.");
    }

    // 2. Get ALL Module IDs
    $stmt_mod = $pdo->query("SELECT id, nombre FROM modulos");
    $modules = $stmt_mod->fetchAll(PDO::FETCH_ASSOC);

    // 3. Insert permissions if they don't exist
    $inserted_count = 0;
    $stmt_check = $pdo->prepare("SELECT Count(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");

    foreach ($modules as $m) {
        $mod_id = $m['id'];
        $mod_name = $m['nombre'];

        // Check existence
        $stmt_check->execute([$role_id, $mod_id]);
        if ($stmt_check->fetchColumn() == 0) {
            // Insert
            $stmt_insert->execute([$role_id, $mod_id]);
            $output .= "Inserted: $mod_name ($mod_id)\n";
            $inserted_count++;
        }
    }

    $output .= "Sync completed. $inserted_count permissions added to SuperAdmin.\n";

    file_put_contents('debug_superadmin.txt', $output);
    echo "Done.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>