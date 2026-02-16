<?php
/**
 * activar_modulo_personal.php
 * Registra el módulo 'gestion_personal' y asigna permisos
 */

require_once __DIR__ . '/conexion.php';

try {
    echo "Iniciando configuración de permisos para el módulo de Personal...\n";

    // 1. Verificar si el módulo ya existe
    $stmt = $pdo->prepare("SELECT id FROM modulos WHERE nombre = 'gestion_personal'");
    $stmt->execute();
    $modulo_id = $stmt->fetchColumn();

    if (!$modulo_id) {
        // Insertar el módulo
        $stmt = $pdo->prepare("INSERT INTO modulos (nombre, descripcion) VALUES ('gestion_personal', 'Módulo de Gestión de Empleados Multi-Empresa')");
        $stmt->execute();
        $modulo_id = $pdo->lastInsertId();
        echo "✅ Módulo 'gestion_personal' creado (ID: $modulo_id).\n";
    } else {
        echo "ℹ️ El módulo ya existía (ID: $modulo_id).\n";
    }

    // 2. Asignar permisos a roles: SuperAdmin, Admin, RRHH
    // Obtener IDs de roles
    $roles_objetivo = ['SuperAdmin', 'Admin', 'RRHH'];

    foreach ($roles_objetivo as $nombre_rol) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmt->execute([$nombre_rol]);
        $rol_id = $stmt->fetchColumn();

        if ($rol_id) {
            // Verificar si ya tiene el permiso
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM permisos_roles WHERE rol_id = ? AND modulo_id = ?");
            $stmt_check->execute([$rol_id, $modulo_id]);

            if ($stmt_check->fetchColumn() == 0) {
                // Asignar permiso
                $stmt_insert = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");
                $stmt_insert->execute([$rol_id, $modulo_id]);
                echo "✅ Permiso asignado al rol '$nombre_rol'.\n";
            } else {
                echo "ℹ️ El rol '$nombre_rol' ya tenía acceso.\n";
            }
        } else {
            echo "⚠️ Rol '$nombre_rol' no encontrado en la base de datos.\n";
        }
    }

    echo "\n¡Configuración completada exitosamente!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>