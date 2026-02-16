<?php
/**
 * asignar_permiso_inventario.php
 * Script para asignar el permiso de inventario a los roles correspondientes
 */

$host = 'localhost';
$dbname = 'sistema_ticket';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Asignando permiso de Inventario ===\n\n";

    // Obtener ID del módulo inventario
    $stmt = $pdo->query("SELECT id FROM modulos WHERE nombre = 'rrhh_inventario'");
    $modulo_id = $stmt->fetchColumn();

    if (!$modulo_id) {
        die("❌ Error: Módulo 'rrhh_inventario' no encontrado\n");
    }

    echo "✓ Módulo 'rrhh_inventario' encontrado (ID: $modulo_id)\n\n";

    // Obtener roles uno por uno
    $roles_nombres = ['SuperAdmin', 'RRHH', 'Admin'];

    foreach ($roles_nombres as $rol_nombre) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmt->execute([$rol_nombre]);
        $rol_id = $stmt->fetchColumn();

        if (!$rol_id) {
            echo "⚠️  Rol '$rol_nombre' no encontrado\n";
            continue;
        }

        // Verificar si ya tiene el permiso
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
        $stmt->execute([$rol_id, $modulo_id]);

        if ($stmt->fetchColumn() > 0) {
            echo "⚠️  Rol '$rol_nombre' ya tiene el permiso asignado\n";
        } else {
            $stmt = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");
            $stmt->execute([$rol_id, $modulo_id]);
            echo "✅ Permiso asignado a rol '$rol_nombre'\n";
        }
    }

    echo "\n✅ Proceso completado\n";

} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>