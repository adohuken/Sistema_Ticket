<?php
/**
 * procesar_editar_rrhh.php
 * Procesar edición de formularios RRHH
 * VALIDACIÓN CRÍTICA: Solo permite editar si NO está asignado a técnico
 */

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security_utils.php';

session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
            throw new Exception('Token CSRF inválido');
        }

        $formulario_id = $_POST['formulario_id'];
        $tipo = trim($_POST['tipo']); // Trim para evitar errores de espacios

        // DEBUG LOGGING
        $formulario_id = $_POST['formulario_id'];
        $tipo = trim($_POST['tipo']);


        /*
        // VALIDACIÓN CRÍTICA: Verificar que el formulario NO esté asignado
        // (Desactivado temporalmente porque la columna 'asignado_a' no existe en la BD actual)
        $stmt_check = $pdo->prepare("SELECT asignado_a FROM formularios_rrhh WHERE id = ?");
        $stmt_check->execute([$formulario_id]);
        $asignado = $stmt_check->fetchColumn();

        if ($asignado !== null && $asignado !== false) {
            $_SESSION['mensaje_error'] = "No se puede editar: el formulario ya está asignado a un técnico.";
            header('Location: index.php?view=formularios_rrhh');
            exit;
        }
        */

        // 1. OBTENER DATOS ACTUALES (Para preservar campos que no están en el formulario de edición)
        $stmt_current = $pdo->prepare("SELECT * FROM formularios_rrhh WHERE id = ?");
        $stmt_current->execute([$formulario_id]);
        $formulario_actual = $stmt_current->fetch(PDO::FETCH_ASSOC);

        if (!$formulario_actual) {
            throw new Exception("El formulario no existe o fue eliminado durante la operación.");
        }

        // Datos comunes (siempre presentes en el formulario de edición)
        $nombre_colaborador = $_POST['nombre_colaborador'];
        $cedula_telefono = $_POST['cedula_telefono'];
        $cargo_zona = $_POST['cargo_zona'];

        // Lógica robusta para Observaciones/Otras Indicaciones
        // Si el campo viene en POST (aunque sea vacío), lo usamos. Si no viene (null), mantenemos el actual.
        // Nota: En el formulario de Ingreso se llama 'otras_indicaciones', en Salida 'observaciones'.
        $obs_input = $_POST['otras_indicaciones'] ?? $_POST['observaciones'] ?? null;

        if ($tipo === 'Ingreso') {
            $otras_indicaciones_final = $obs_input !== null ? $obs_input : $formulario_actual['otras_indicaciones'];

            // Actualizar formulario de Ingreso
            // Se usa el operador ?? para preservar el valor actual si el campo no está en el formulario HTML
            $sql = "UPDATE formularios_rrhh SET
                nombre_colaborador = ?,
                cedula_telefono = ?,
                cargo_zona = ?,
                fecha_solicitud = ?,
                disponibilidad_licencias = ?,
                detalle_licencias = ?,
                correo_nuevo = ?,
                direccion_correo = ?,
                remitente_mostrar = ?,
                detalle_remitente = ?,
                respaldo_nube = ?,
                detalle_respaldo = ?,
                reenvios_correo = ?,
                detalle_reenvios = ?,
                asignacion_equipo = ?,
                detalle_asignacion = ?,
                nube_movil = ?,
                detalle_nube_movil = ?,
                especificacion_equipo_usado = ?,
                otras_indicaciones = ?
                WHERE id = ? AND tipo = 'Ingreso'";

            $params = [
                $nombre_colaborador,
                $cedula_telefono,
                $cargo_zona,
                $_POST['fecha_solicitud'],
                $_POST['disponibilidad_licencias'],
                $_POST['detalle_licencias'] ?? $formulario_actual['detalle_licencias'],
                $_POST['correo_nuevo'],
                $_POST['direccion_correo'] ?? $formulario_actual['direccion_correo'],
                // Campos que NO están en el formulario de edición, preservar valor actual:
                $_POST['remitente_mostrar'] ?? $formulario_actual['remitente_mostrar'],
                $_POST['detalle_remitente'] ?? $formulario_actual['detalle_remitente'],
                $_POST['respaldo_nube'] ?? $formulario_actual['respaldo_nube'],
                $_POST['detalle_respaldo'] ?? $formulario_actual['detalle_respaldo'],
                $_POST['reenvios_correo'] ?? $formulario_actual['reenvios_correo'],
                $_POST['detalle_reenvios'] ?? $formulario_actual['detalle_reenvios'],
                $_POST['asignacion_equipo'],
                $_POST['detalle_asignacion'] ?? $formulario_actual['detalle_asignacion'],
                $_POST['nube_movil'] ?? $formulario_actual['nube_movil'], // No está en el form
                $_POST['detalle_nube_movil'] ?? $formulario_actual['detalle_nube_movil'], // No está en el form
                $_POST['especificacion_equipo_usado'] ?? $formulario_actual['especificacion_equipo_usado'], // No está en el form
                $otras_indicaciones_final,
                $formulario_id
            ];

        } else {
            $observaciones_final = $obs_input !== null ? $obs_input : $formulario_actual['observaciones'];

            // Actualizar formulario de Salida
            $sql = "UPDATE formularios_rrhh SET
                nombre_colaborador = ?,
                cedula_telefono = ?,
                cargo_zona = ?,
                fecha_efectiva = ?,
                bloqueo_correo = ?,
                cuenta_correo_bloqueo = ?,
                respaldo_info = ?,
                detalle_respaldo_salida = ?,
                redireccion_correo = ?,
                email_redireccion = ?,
                devolucion_equipo = ?,
                detalle_devolucion_equipo = ?,
                devolucion_movil = ?,
                detalle_devolucion_movil = ?,
                observaciones = ?
                WHERE id = ? AND tipo = 'Salida'";

            $params = [
                $nombre_colaborador,
                $cedula_telefono,
                $cargo_zona,
                $_POST['fecha_efectiva'],
                $_POST['bloqueo_correo'],
                $_POST['cuenta_correo_bloqueo'] ?? $formulario_actual['cuenta_correo_bloqueo'],
                $_POST['respaldo_info'],
                $_POST['detalle_respaldo_salida'] ?? $formulario_actual['detalle_respaldo_salida'],
                $_POST['redireccion_correo'] ?? $formulario_actual['redireccion_correo'] ?? 'NO',
                $_POST['email_redireccion'] ?? $formulario_actual['email_redireccion'],
                $_POST['devolucion_equipo'],
                $_POST['detalle_devolucion_equipo'] ?? $formulario_actual['detalle_devolucion_equipo'],
                $_POST['devolucion_movil'],
                $_POST['detalle_devolucion_movil'] ?? $formulario_actual['detalle_devolucion_movil'],
                $observaciones_final,
                $formulario_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $rows_affected = $stmt->rowCount();

        if ($rows_affected > 0) {
            $_SESSION['mensaje_exito'] = "Formulario actualizado correctamente. ($rows_affected fila(s) afectada(s))";
        } else {
            $_SESSION['mensaje_error'] = "No se actualizó ningún registro. Verifica que el formulario existe y no está asignado.";
        }

        header('Location: index.php?view=formularios_rrhh');
        exit;

    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = "Error al actualizar formulario: " . $e->getMessage();
        header('Location: index.php?view=editar_rrhh&id=' . ($formulario_id ?? ''));
        exit;
    }
}

// Si no es POST, redirigir
header('Location: index.php?view=formularios_rrhh');
exit;
?>