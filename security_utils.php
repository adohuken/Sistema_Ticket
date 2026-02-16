<?php
/**
 * security_utils.php - Utilidades de Seguridad
 * Funciones para protección CSRF, sanitización y validación
 */

/**
 * Genera un token CSRF y lo almacena en la sesión
 */
function generar_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF del formulario
 */
function validar_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Regenera el token CSRF (usar después de operaciones críticas)
 */
function regenerar_csrf_token()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Sanitiza entrada de texto
 */
function sanitizar_texto($texto)
{
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza email
 */
function sanitizar_email($email)
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Valida email
 */
function validar_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Registra error en archivo de log
 */
function registrar_error($mensaje, $contexto = [])
{
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/errores_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $usuario = $_SESSION['usuario_nombre'] ?? 'Anónimo';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';

    $log_entry = sprintf(
        "[%s] Usuario: %s | IP: %s | Mensaje: %s | Contexto: %s\n",
        $timestamp,
        $usuario,
        $ip,
        $mensaje,
        json_encode($contexto)
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Registra actividad del sistema
 */
function registrar_actividad($accion, $detalles = '', $pdo = null)
{
    // 1. Guardar en archivo de texto (Backup)
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/actividad_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $usuario = $_SESSION['usuario_nombre'] ?? 'Sistema';
    $usuario_id = $_SESSION['usuario_id'] ?? 0;

    $log_entry = sprintf(
        "[%s] Usuario ID: %d (%s) | Acción: %s | Detalles: %s\n",
        $timestamp,
        $usuario_id,
        $usuario,
        $accion,
        $detalles
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // 2. Guardar en Base de Datos (Principal para Auditoría)
    if ($pdo && $usuario_id > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO historial_actividad (usuario_id, accion, descripcion) VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id, $accion, $detalles]);
        } catch (PDOException $e) {
            // Silenciar error de log para no interrumpir el flujo principal
            error_log("Error al guardar log en BD: " . $e->getMessage());
        }
    }
}

/**
 * Valida permisos de usuario
 */
function validar_permiso($roles_permitidos)
{
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }

    if (is_array($roles_permitidos)) {
        return in_array($_SESSION['usuario_rol'], $roles_permitidos);
    }

    return $_SESSION['usuario_rol'] === $roles_permitidos;
}

/**
 * Genera campo oculto de token CSRF para formularios
 */
function campo_csrf()
{
    $token = generar_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
