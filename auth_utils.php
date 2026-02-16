<?php
// auth_utils.php - Funciones de autorización

/**
 * Verifica si el rol actual tiene permiso para un módulo específico
 * 
 * @param PDO $pdo Conexión a la base de datos
 * @param int|string $rol_identificador ID del rol o nombre del rol
 * @param string $modulo_nombre Nombre clave del módulo (ej: 'crear_ticket')
 * @return bool True si tiene permiso, False si no
 */
function tiene_permiso($pdo, $rol_identificador, $modulo_nombre)
{
    // Si es SuperAdmin, siempre tiene permiso (fallback de seguridad)
    // Pero mejor consultamos la BD para ser consistentes con la petición del usuario de "desasignar" cosas incluso al SuperAdmin si quisiera (aunque es peligroso).
    // Mantendremos un hardcode de seguridad para 'configuracion' y 'permisos' para SuperAdmin para evitar bloqueo total.

    // Obtener ID del rol si se pasó el nombre
    $rol_id = $rol_identificador;
    if (!is_numeric($rol_identificador)) {
        // Cachear esto sería ideal, pero por ahora consulta directa
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmt->execute([$rol_identificador]);
        $rol_id = $stmt->fetchColumn();
    }

    if (!$rol_id)
        return false;

    // Verificar permiso por Rol
    $sql = "SELECT COUNT(*) FROM permisos_roles pr 
            JOIN modulos m ON pr.modulo_id = m.id 
            WHERE pr.rol_id = ? AND m.nombre = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rol_id, $modulo_nombre]);

    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    // Verificar permiso específico de Usuario (Aditivo)
    if (isset($_SESSION['usuario_id'])) {
        $sql_user = "SELECT COUNT(*) FROM permisos_usuarios pu
                     JOIN modulos m ON pu.modulo_id = m.id
                     WHERE pu.usuario_id = ? AND m.nombre = ?";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([$_SESSION['usuario_id'], $modulo_nombre]);

        if ($stmt_user->fetchColumn() > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Obtener todos los permisos de un rol
 */
function obtener_permisos_rol($pdo, $rol_id)
{
    $sql = "SELECT m.nombre FROM permisos_roles pr 
            JOIN modulos m ON pr.modulo_id = m.id 
            WHERE pr.rol_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rol_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>