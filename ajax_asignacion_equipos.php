<?php
/**
 * ajax_asignacion_equipos.php
 * Handler dedicado para la asignación de equipos
 * Evita problemas de headers/routing de index.php
 */

// 1. Iniciar buffer para evitar output indeseado
ob_start();
session_start();

// 2. Dependencias mínimas
require_once 'conexion.php';
require_once 'security_utils.php';

// 3. Limpiar buffer antes de procesar
ob_clean();
header('Content-Type: application/json');

// 4. Verificaciones de Seguridad
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => 'Sesión expirada']);
    exit;
}

$rol = $_SESSION['usuario_rol'] ?? '';
if (!in_array($rol, ['SuperAdmin', 'RRHH', 'Admin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado']);
    exit;
}

// 5. Procesar Acciones
$action = $_REQUEST['ajax_action'] ?? '';

try {
    switch ($action) {
        case 'get_empleado_data':
            $emp_id = $_REQUEST['id'] ?? 0;
            if (!$emp_id)
                throw new Exception("ID empleado requerido");

            $stmt = $pdo->prepare("SELECT * FROM inventario WHERE asignado_a = ? ORDER BY fecha_asignacion DESC");
            $stmt->execute([$emp_id]);
            $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'activos' => $activos]);
            break;

        case 'asignar_equipo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                throw new Exception("Método no permitido");

            $emp_id = $_POST['empleado_id'] ?? 0;
            $eq_id = $_POST['equipo_id'] ?? 0;

            if (!$emp_id || !$eq_id)
                throw new Exception("Datos incompletos");

            $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW(), condicion = 'Asignado' WHERE id = ?");
            $stmt->execute([$emp_id, $eq_id]);

            if ($stmt->rowCount() > 0) {
                registrar_actividad("Asignar Equipo", "Equipo ID $eq_id asignado a Empleado ID $emp_id", $pdo);
                echo json_encode(['status' => 'success', 'msg' => '✅ Equipo asignado correctamente']);
            } else {
                // Verificar si ya estaba asignado (no es error crítico)
                echo json_encode(['status' => 'success', 'msg' => 'Equipo asignado (sin cambios)']);
            }
            break;

        case 'liberar_equipo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                throw new Exception("Método no permitido");

            $eq_id = $_POST['equipo_id'] ?? 0;
            if (!$eq_id)
                throw new Exception("ID equipo requerido");

            $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = NULL, fecha_asignacion = NULL, condicion = 'Disponible' WHERE id = ?");
            $stmt->execute([$eq_id]);

            registrar_actividad("Liberar Equipo", "Equipo ID $eq_id liberado", $pdo);
            echo json_encode(['status' => 'success', 'msg' => '✅ Equipo liberado correctamente']);
            break;

        default:
            throw new Exception("Acción no válida: $action");
    }

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}

// Finalizar script limpiamente
exit;
?>