<?php
session_start();
ob_start(); // Capturar cualquier output no deseado

// ============================================
// HANDLER AJAX EXCLUSIVO - AL INICIO ABSOLUTO
// ============================================

// [POLLING] Handler ligero para verificar nuevas notificaciones (Técnicos y todos los roles)
if (isset($_GET['action']) && $_GET['action'] === 'check_notifs') {
    ob_end_clean();
    header('Content-Type: application/json');
    require_once __DIR__ . '/conexion.php';
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['count' => 0, 'latest' => null]);
        exit;
    }
    try {
        $uid = $_SESSION['usuario_id'];
        $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
        $stmt_c->execute([$uid]);
        $count = (int) $stmt_c->fetchColumn();

        $latest = null;
        if ($count > 0) {
            $stmt_l = $pdo->prepare("SELECT id, titulo, mensaje, tipo FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY created_at DESC LIMIT 1");
            $stmt_l->execute([$uid]);
            $latest = $stmt_l->fetch(PDO::FETCH_ASSOC);
        }
        echo json_encode(['count' => $count, 'latest' => $latest]);
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'latest' => null]);
    }
    exit;
}

// [AJAX] Marcar notificación como leída (handler temprano — no requiere cargar toda la página)
if (isset($_POST['accion']) && $_POST['accion'] === 'marcar_notificacion') {
    ob_end_clean();
    header('Content-Type: application/json');
    require_once __DIR__ . '/conexion.php';

    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
        exit;
    }

    try {
        $notif_id = $_POST['id'] ?? '';
        $uid = $_SESSION['usuario_id'];

        if ($notif_id === 'todas') {
            $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?")->execute([$uid]);
        } else {
            $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?")->execute([$notif_id, $uid]);
        }
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// [NUEVO] Handler para gestión de permisos de usuario (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get_user_perms') {
    ob_end_clean();
    header('Content-Type: application/json');
    require_once __DIR__ . '/conexion.php';

    // Verificar sesión y permisos
    if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['SuperAdmin', 'Admin'])) {
        echo json_encode([]);
        exit;
    }

    try {
        $uid = $_GET['user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT modulo_id FROM permisos_usuarios WHERE usuario_id = ?");
        $stmt->execute([$uid]);
        $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($perms);
    } catch (Exception $e) {
        echo json_encode([]);
    }
    exit;
}

// [NUEVO] Handler para gestión de Herramientas de Técnico (AJAX)
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'manage_tools') {
    ob_end_clean();
    header('Content-Type: application/json');
    require_once __DIR__ . '/conexion.php';

    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Sesión expirada']);
        exit;
    }

    $sub_action = $_POST['sub_action'] ?? '';
    $user_id = $_SESSION['usuario_id'];

    if ($sub_action === 'add') {
        $nombre = $_POST['nombre'] ?? '';
        $url = $_POST['url'] ?? '';
        $icono = $_POST['icono'] ?? 'ri-link';

        if (empty($nombre) || empty($url)) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO herramientas_tecnico (usuario_id, nombre, url, icono) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $nombre, $url, $icono]);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error BD: ' . $e->getMessage()]);
        }

    } elseif ($sub_action === 'edit') {
        $id_tool = $_POST['id'] ?? 0;
        $nombre = $_POST['nombre'] ?? '';
        $url = $_POST['url'] ?? '';
        $icono = $_POST['icono'] ?? 'ri-link';

        if (empty($nombre) || empty($url)) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE herramientas_tecnico SET nombre = ?, url = ?, icono = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nombre, $url, $icono, $id_tool, $user_id]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error BD']);
        }

    } elseif ($sub_action === 'delete') {
        $id_tool = $_POST['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM herramientas_tecnico WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id_tool, $user_id]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'msg' => 'Error BD']);
        }
    }
    exit;
}

if (isset($_REQUEST['ajax_action']) && isset($_GET['view']) && $_GET['view'] === 'asignacion_equipos') {
    // Limpiar buffer
    ob_end_clean();
    header('Content-Type: application/json');

    // Dependencias
    require_once __DIR__ . '/conexion.php';
    require_once __DIR__ . '/security_utils.php';

    // Verificar Sesión
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

    $action = $_REQUEST['ajax_action'];

    try {
        if ($action === 'get_empleado_data') {
            $id = $_REQUEST['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM inventario WHERE asignado_a = ? ORDER BY fecha_asignacion DESC");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'activos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Check
            if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
                echo json_encode(['status' => 'error', 'msg' => 'Error de seguridad: Token CSRF inválido']);
                exit;
            }

            if ($action === 'asignar_equipo') {
                $uid = $_POST['empleado_id'];
                $eid = $_POST['equipo_id'];

                $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW(), condicion = 'Asignado' WHERE id = ?");
                $stmt->execute([$uid, $eid]);

                if ($stmt->rowCount() > 0) {
                    registrar_actividad("Asignar Equipo", "Equipo $eid asig a $uid", $pdo);
                }
                echo json_encode(['status' => 'success', 'msg' => 'Asignado correctamente']);
                exit;
            }

            if ($action === 'liberar_equipo') {
                $eid = $_POST['equipo_id'];
                $pdo->prepare("UPDATE inventario SET asignado_a = NULL, fecha_asignacion = NULL, condicion = 'Disponible' WHERE id = ?")->execute([$eid]);
                registrar_actividad("Liberar Equipo", "Equipo $eid liberado", $pdo);
                echo json_encode(['status' => 'success', 'msg' => 'Liberado correctamente']);
                exit;
            }

            if ($action === 'asignar_multiples_equipos') {
                $uid = $_POST['empleado_id'];
                $eids_json = $_POST['equipo_ids']; // Esperamos JSON string
                $eids = json_decode($eids_json, true);

                if (!is_array($eids) || empty($eids)) {
                    echo json_encode(['status' => 'error', 'msg' => 'No hay equipos seleccionados']);
                    exit;
                }

                $count = 0;
                $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW(), condicion = 'Asignado' WHERE id = ?");

                foreach ($eids as $eid) {
                    $stmt->execute([$uid, $eid]);
                    if ($stmt->rowCount() > 0) {
                        registrar_actividad("Asignar Equipo", "Equipo $eid asig a $uid (Lote)", $pdo);
                        $count++;
                    }
                }

                echo json_encode(['status' => 'success', 'msg' => "Se asignaron $count equipos correctamente"]);
                exit;
            }

            // [NEW] Asset Creation
            if ($action === 'ajax_crear_activo') {
                $tipo = $_POST['tipo'];
                $marca = $_POST['marca'];
                $modelo = $_POST['modelo'];
                $serial = $_POST['serial'];
                $estado = $_POST['estado'];
                $sku = $_POST['sku'] ?? '';
                $comentarios = $_POST['comentarios'] ?? null; // [NEW]

                // Validate Uniqueness
                $stmtCheck = $pdo->prepare("SELECT id FROM inventario WHERE serial = ? OR (sku != '' AND sku = ?)");
                $stmtCheck->execute([$serial, $sku]);
                if ($stmtCheck->fetch()) {
                    echo json_encode(['status' => 'error', 'msg' => 'El Serial o SKU ya existe en el sistema.']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO inventario (tipo, marca, modelo, serial, sku, condicion, estado, comentarios) VALUES (?, ?, ?, ?, ?, 'Disponible', ?, ?)");
                $stmt->execute([$tipo, $marca, $modelo, $serial, $sku, $estado, $comentarios]);

                if ($stmt->rowCount() > 0) {
                    registrar_actividad("Crear Activo", "Nuevo $tipo: $marca $modelo", $pdo);
                    echo json_encode(['status' => 'success', 'msg' => 'Activo creado correctamente']);
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'No se pudo guardar en base de datos']);
                }
                exit;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        exit;
    }
    exit;
}
// ============================================

// --- CONFIGURACIÓN DE COLORES DE ROLES ---
$config_file_colors = 'config_rol_colors.json';
if (!file_exists($config_file_colors)) {
    $defaults_colors = ['SuperAdmin' => 'fuchsia', 'Admin' => 'indigo', 'Tecnico' => 'cyan', 'RRHH' => 'pink', 'Usuario' => 'slate'];
    file_put_contents($config_file_colors, json_encode($defaults_colors));
}
$GLOBALS['rol_colors_config'] = json_decode(file_get_contents($config_file_colors), true);

$GLOBALS['colores_badges_map'] = [
    'purple' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
    'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
    'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'green' => 'bg-green-50 text-green-700 ring-green-600/20',
    'lime' => 'bg-lime-50 text-lime-700 ring-lime-600/20',
    'yellow' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
    'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
    'red' => 'bg-red-50 text-red-700 ring-red-600/20',
    'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    'pink' => 'bg-pink-50 text-pink-700 ring-pink-600/20',
    'fuchsia' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-600/20',
    'slate' => 'bg-slate-50 text-slate-600 ring-slate-500/20',
    'gray' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
    'black' => 'bg-slate-800 text-white ring-slate-700/50'
];
// -----------------------------------------
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security_utils.php';
require_once __DIR__ . '/auth_utils.php';

// [NEW] Controladores
$usuario_id = $_SESSION['usuario_id'] ?? null;
$rol_usuario = $_SESSION['usuario_rol'] ?? null;

require_once __DIR__ . '/controllers/mantenimiento_controller.php';


// ============================================
// AJAX HANDLER - DEBE EJECUTARSE ANTES DE CUALQUIER HTML
// ============================================
if (isset($_GET['ajax_action']) && isset($_GET['view']) && $_GET['view'] === 'asignacion_equipos') {
    // Limpiar cualquier output previo
    ob_clean();

    // Verificar sesión para AJAX
    if (!isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Sesión expirada']);
        exit;
    }

    $usuario_id = $_SESSION['usuario_id'];
    $rol_usuario = $_SESSION['usuario_rol'];

    // Verificar permisos
    if (!in_array($rol_usuario, ['SuperAdmin', 'RRHH', 'Admin'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
        exit;
    }

    header('Content-Type: application/json');

    // Handler: Obtener datos de empleado y activos asignados
    if ($_GET['ajax_action'] === 'get_empleado_data' && isset($_GET['id'])) {
        $emp_id = $_GET['id'];

        $stmt_assets = $pdo->prepare("
            SELECT * 
            FROM inventario 
            WHERE asignado_a = ?
            ORDER BY fecha_asignacion DESC
        ");
        $stmt_assets->execute([$emp_id]);
        $activos = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'activos' => $activos]);
        exit;
    }

    // Handler: Equipos disponibles de una sucursal (para modulo asignacion)
    if ($_GET['ajax_action'] === 'get_inventario_sucursal' && isset($_GET['sucursal_id'])) {
        $sucursal_id = (int) $_GET['sucursal_id'];
        $stmt_inv = $pdo->prepare("
            SELECT id, tipo, marca, modelo, serial, sku, condicion, estado
            FROM inventario
            WHERE sucursal_id = ? AND condicion = 'Disponible'
            ORDER BY tipo, marca
        ");
        $stmt_inv->execute([$sucursal_id]);
        $equipos = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'equipos' => $equipos]);
        exit;
    }
}

// Handler AJAX Specífico para Reporte Mantenimiento (Guardar Specs)
if (isset($_GET['view']) && $_GET['view'] === 'mantenimiento_reporte' && isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'guardar_specs') {
    ob_clean();
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $id = $_POST['id'];
            $proc = $_POST['procesador'] ?? null;
            $ram = $_POST['ram'] ?? null;
            $hdd = $_POST['disco_duro'] ?? null;
            $av = $_POST['antivirus'] ?? null;
            $od = $_POST['onedrive'] ?? null;
            $bk = $_POST['backup_status'] ?? null;
            $sc = $_POST['screenconnect'] ?? null;

            $stmt = $pdo->prepare("UPDATE inventario SET procesador=?, ram=?, disco_duro=?, antivirus=?, onedrive=?, backup_status=?, screenconnect=? WHERE id=?");
            $stmt->execute([$proc, $ram, $hdd, $av, $od, $bk, $sc, $id]);

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }
    exit;
}

// Handler AJAX para POST (asignar/liberar equipos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && isset($_GET['view']) && $_GET['view'] === 'asignacion_equipos') {
    // Limpiar cualquier output previo
    ob_clean();

    // DEBUG: Log a archivo para mayor seguridad
    $log_content = date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_POST, true) . "\n";
    file_put_contents('debug_ajax.txt', $log_content, FILE_APPEND);

    // DEBUG: Log para verificar que llega aquí
    error_log("🔧 POST Handler ejecutado - ajax_action: " . ($_POST['ajax_action'] ?? 'NO SET'));

    // Verificar sesión
    if (!isset($_SESSION['usuario_id'])) {
        file_put_contents('debug_ajax.txt', "ERROR: Sesión expirada\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => 'Sesión expirada']);
        exit;
    }

    $rol_usuario = $_SESSION['usuario_rol'];

    // Verificar permisos
    if (!in_array($rol_usuario, ['SuperAdmin', 'RRHH', 'Admin'])) {
        file_put_contents('debug_ajax.txt', "ERROR: Acceso denegado ($rol_usuario)\n", FILE_APPEND);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado']);
        exit;
    }

    header('Content-Type: application/json');

    try {
        if ($_POST['ajax_action'] === 'asignar_equipo') {
            $emp_id = $_POST['empleado_id'];
            $eq_id = $_POST['equipo_id'];

            error_log("✅ Asignando equipo $eq_id a empleado $emp_id");
            file_put_contents('debug_ajax.txt', "✅ Intentando asignar $eq_id a $emp_id\n", FILE_APPEND);

            $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW(), condicion = 'Asignado' WHERE id = ?");
            $result = $stmt->execute([$emp_id, $eq_id]);

            $rows = $stmt->rowCount();
            file_put_contents('debug_ajax.txt', "✅ Resultado execute: " . ($result ? 'TRUE' : 'FALSE') . " - Rows affected: $rows\n", FILE_APPEND);

            if ($rows > 0) {
                registrar_actividad("Asignar Equipo", "Equipo ID $eq_id asignado a Empleado ID $emp_id", $pdo);
                echo json_encode(['status' => 'success', 'msg' => '✅ Equipo asignado correctamente']);
            } else {
                // Si rows es 0, puede ser que el ID no exista o la condición del WHERE no se cumpla
                // Ojo: si ya estaba asignado a ese user con los mismos datos, rowCount es 0 en MySQL
                echo json_encode(['status' => 'success', 'msg' => '✅ Equipo asignado (sin cambios detectados)']);
            }
            exit;
        }

        if ($_POST['ajax_action'] === 'liberar_equipo') {
            $eq_id = $_POST['equipo_id'];

            file_put_contents('debug_ajax.txt', "✅ Intentando liberar $eq_id\n", FILE_APPEND);

            $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = NULL, fecha_asignacion = NULL, condicion = 'Disponible' WHERE id = ?");
            $stmt->execute([$eq_id]);

            registrar_actividad("Liberar Equipo", "Equipo ID $eq_id liberado (devuelto a inventario)", $pdo);
            echo json_encode(['status' => 'success', 'msg' => '✅ Equipo liberado correctamente']);
            exit;
        }
    } catch (Exception $e) {
        error_log("❌ Error en asignación: " . $e->getMessage());
        file_put_contents('debug_ajax.txt', "❌ ERROR EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}
// ============================================
// FIN AJAX HANDLER (Asignación)
// ============================================

// ============================================
// AJAX HANDLER - GESTIÓN PERSONAL (DAR DE BAJA)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && isset($_GET['view']) && $_GET['view'] === 'personal') {
    ob_clean();
    header('Content-Type: application/json');

    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Sesión expirada']);
        exit;
    }

    $rol_usuario = $_SESSION['usuario_rol'];
    if (!in_array($rol_usuario, ['SuperAdmin', 'RRHH', 'Admin'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado']);
        exit;
    }

    try {
        if ($_POST['ajax_action'] === 'get_empleado_assets') {
            $emp_id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT tipo, marca, modelo, serial, condicion FROM inventario WHERE asignado_a = ? AND condicion = 'Asignado'");
            $stmt->execute([$emp_id]);
            $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'assets' => $assets]);
            exit;
        }

        if ($_POST['ajax_action'] === 'dar_baja_personal') {
            $emp_id = $_POST['id'];

            // 1. Gestionar Activos Asignados (Auto-Revisión)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventario WHERE asignado_a = ? AND condicion = 'Asignado'");
            $stmt->execute([$emp_id]);
            $assigned_count = $stmt->fetchColumn();

            if ($assigned_count > 0) {
                // Actualizar activos a 'En Revisión' y desasignar
                $stmt_update = $pdo->prepare("UPDATE inventario SET condicion = 'En Revisión', asignado_a = NULL, fecha_asignacion = NULL WHERE asignado_a = ? AND condicion = 'Asignado'");
                $stmt_update->execute([$emp_id]);
            }

            // 2. Actualizar Estado del Personal
            $stmt = $pdo->prepare("UPDATE personal SET estado = 'Inactivo', fecha_salida = CURDATE() WHERE id = ?");
            $stmt->execute([$emp_id]);

            $msg = "Colaborador dado de baja correctamente.";
            if ($assigned_count > 0) {
                $msg .= " Se movieron $assigned_count activos a estado 'En Revisión'.";
            }

            registrar_actividad("Dar de Baja", "Empleado ID $emp_id desactivado. $assigned_count activos pasaron a revisión.", $pdo);

            echo json_encode(['status' => 'success', 'msg' => $msg]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}


// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol_usuario = $_SESSION['usuario_rol'];
$nombre_usuario = $_SESSION['usuario_nombre'];

// [FIX] Cargar permisos del usuario para validar acciones POST
$permisos_usuario = [];
if (isset($pdo) && $rol_usuario) {
    // Necesitamos el ID del rol para buscar sus permisos
    $stmt_rol = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
    $stmt_rol->execute([$rol_usuario]);
    $rol_id_actual = $stmt_rol->fetchColumn();

    $permisos_rol = [];
    if ($rol_id_actual) {
        $permisos_rol = obtener_permisos_rol($pdo, $rol_id_actual);
    }

    // Obtener permisos adicionales del usuario
    $permisos_extra_user = [];
    if (isset($usuario_id)) {
        $stmt_pe = $pdo->prepare("
            SELECT m.nombre 
            FROM permisos_usuarios pu
            JOIN modulos m ON pu.modulo_id = m.id
            WHERE pu.usuario_id = ?
        ");
        $stmt_pe->execute([$usuario_id]);
        $permisos_extra_user = $stmt_pe->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fusionar
    $permisos_usuario = array_unique(array_merge($permisos_rol, $permisos_extra_user));
}

// Inicializar Token CSRF si no existe
generar_csrf_token();

$mensaje_accion = '';

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF para todas las peticiones POST
    if (!validar_csrf_token($_POST['csrf_token'] ?? '')) {
        $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error de seguridad: Token CSRF inválido.</div>";
        error_log("Intento de CSRF detectado - Usuario ID: " . $usuario_id);
    } else {
        // 0. Crear Usuario (Nuevo sistema con Empresa/Sucursal)
        if (isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            try {
                $password_hash = password_hash($_POST['password_usuario'], PASSWORD_DEFAULT);

                // Preparar valores nulos para empresa/sucursal si están vacíos
                $empresa = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
                $sucursal = !empty($_POST['sucursal_id']) ? $_POST['sucursal_id'] : null;
                $empresa_asignada_nueva = !empty($_POST['empresa_asignada']) ? $_POST['empresa_asignada'] : null;

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, rol_id, empresa_id, sucursal_id, empresa_asignada) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['nombre_usuario'],
                    $_POST['email_usuario'],
                    $password_hash,
                    $_POST['rol_usuario'],
                    $empresa,
                    $sucursal,
                    $empresa_asignada_nueva
                ]);
                $nuevo_user_id = $pdo->lastInsertId();


                // Procesar Permisos de Acceso Múltiple (RRHH)
                if (isset($_POST['permisos_sucursal']) && is_array($_POST['permisos_sucursal'])) {
                    $stmt_permiso = $pdo->prepare("INSERT INTO usuarios_accesos (usuario_id, sucursal_id) VALUES (?, ?)");
                    foreach ($_POST['permisos_sucursal'] as $suc_id_permiso) {
                        // Evitar duplicados o vacíos
                        if (!empty($suc_id_permiso)) {
                            // Usar INSERT IGNORE o try-catch silencioso para evitar errores de clave única
                            try {
                                $stmt_permiso->execute([$nuevo_user_id, $suc_id_permiso]);
                            } catch (PDOException $ex_perm) {
                                // Ignorar duplicados
                            }
                        }
                    }
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Usuario creado exitosamente con asignación empresarial.</div>";
                registrar_actividad("Crear Usuario", "Usuario: " . $_POST['nombre_usuario'] . " (Empresa ID: $empresa)", $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al crear usuario: " . $e->getMessage() . "</div>";
            }
        }

        // 1. Crear Usuario (Legacy - Mantener por compatibilidad si algo más lo usa)
        if (isset($_POST['nombre_usuario']) && !isset($_POST['accion']) && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            try {
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, rol_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nombre_usuario'], $_POST['email'], $password_hash, $_POST['rol']]);
                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Usuario creado exitosamente.</div>";
                registrar_actividad("Crear Usuario", "Usuario creado: " . $_POST['nombre_usuario'], $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al crear usuario: " . $e->getMessage() . "</div>";
            }
        }

        // 1.5. Actualizar Usuario (Nuevo - con gestión de permisos)
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_usuario' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            try {
                $user_id_edit = $_POST['usuario_id'];

                // Actualizar datos básicos
                $empresa_asignada_edit = !empty($_POST['empresa_asignada']) ? $_POST['empresa_asignada'] : null;

                if (!empty($_POST['password'])) {
                    // Si se proporcionó nueva contraseña
                    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, password = ?, rol_id = ?, empresa_asignada = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nombre_usuario'],
                        $_POST['email'],
                        $password_hash,
                        $_POST['rol'],
                        $empresa_asignada_edit,
                        $user_id_edit
                    ]);
                } else {
                    // Sin cambio de contraseña
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, rol_id = ?, empresa_asignada = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['nombre_usuario'],
                        $_POST['email'],
                        $_POST['rol'],
                        $empresa_asignada_edit,
                        $user_id_edit
                    ]);
                }


                // Actualizar permisos de sucursales (RRHH)
                // 1. Borrar permisos existentes
                $stmt_delete = $pdo->prepare("DELETE FROM usuarios_accesos WHERE usuario_id = ?");
                $stmt_delete->execute([$user_id_edit]);

                // 2. Insertar nuevos permisos si se seleccionaron
                if (isset($_POST['permisos_sucursal']) && is_array($_POST['permisos_sucursal'])) {
                    $stmt_insert = $pdo->prepare("INSERT INTO usuarios_accesos (usuario_id, sucursal_id) VALUES (?, ?)");
                    foreach ($_POST['permisos_sucursal'] as $suc_id) {
                        if (!empty($suc_id)) {
                            try {
                                $stmt_insert->execute([$user_id_edit, $suc_id]);
                            } catch (PDOException $ex) {
                                // Ignorar duplicados
                            }
                        }
                    }
                }

                // 3. Actualizar Permisos Adicionales (Tabla permisos_usuarios) [NUEVO]
                // Primero borrar anteriores para este usuario
                $stmt_del_pu = $pdo->prepare("DELETE FROM permisos_usuarios WHERE usuario_id = ?");
                $stmt_del_pu->execute([$user_id_edit]);

                // Insertar nuevos permisos seleccionados
                if (isset($_POST['permisos_extra']) && is_array($_POST['permisos_extra'])) {
                    $stmt_ins_pu = $pdo->prepare("INSERT INTO permisos_usuarios (usuario_id, modulo_id) VALUES (?, ?)");
                    foreach ($_POST['permisos_extra'] as $mod_id) {
                        if (!empty($mod_id)) {
                            try {
                                $stmt_ins_pu->execute([$user_id_edit, $mod_id]);
                            } catch (PDOException $e) {
                                // Ignorar errores de duplicados (UNIQUE constraint)
                            }
                        }
                    }
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Usuario actualizado exitosamente.</div>";
                registrar_actividad("Actualizar Usuario", "Usuario ID: " . $user_id_edit, $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar usuario: " . $e->getMessage() . "</div>";
            }
        }

        // 2. Asignar Ticket
        if (isset($_POST['asignar_tecnico']) && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            try {
                $stmt = $pdo->prepare("INSERT INTO asignaciones (ticket_id, tecnico_id, asignado_por) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['ticket_id'], $_POST['tecnico_id'], $usuario_id]);

                $stmt = $pdo->prepare("UPDATE tickets SET estado = 'Asignado' WHERE id = ?");
                $stmt->execute([$_POST['ticket_id']]);

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Técnico asignado correctamente.</div>";
                registrar_actividad("Asignar Ticket", "Ticket ID: " . $_POST['ticket_id'] . " a Técnico ID: " . $_POST['tecnico_id'], $pdo);

                // Notificar al técnico
                try {
                    // Obtener título del ticket para mensaje más descriptivo
                    $stmt_tkt = $pdo->prepare("SELECT titulo FROM tickets WHERE id = ?");
                    $stmt_tkt->execute([$_POST['ticket_id']]);
                    $titulo_ticket = $stmt_tkt->fetchColumn() ?: 'Sin título';

                    // Obtener nombre del admin que asigna
                    $stmt_adm = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
                    $stmt_adm->execute([$usuario_id]);
                    $nombre_admin = $stmt_adm->fetchColumn() ?: 'el administrador';

                    $stmt_notif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) VALUES (?, ?, ?, 'warning', ?)");
                    $stmt_notif->execute([
                        $_POST['tecnico_id'],
                        "🎫 Nuevo Ticket Asignado #" . $_POST['ticket_id'],
                        "Se te asignó el ticket: \"{$titulo_ticket}\". Asignado por: {$nombre_admin}.",
                        "index.php?view=asignados"
                    ]);
                } catch (Exception $ex) {
                }

                // [EMAIL] Alerta: Ticket Asignado al técnico
                try {
                    require_once __DIR__ . '/enviar_notificacion_email.php';
                    $stmt_alert_cfg = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('alert_email_ticket_asignado', 'alert_email_ticket_reasignado')");
                    $alert_vals = [];
                    foreach ($stmt_alert_cfg->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $alert_vals[$row['clave']] = $row['valor'];
                    }

                    if (!empty($alert_vals['alert_email_ticket_asignado'])) {
                        $stmt_tec = $pdo->prepare("SELECT email, nombre_completo FROM usuarios WHERE id = ?");
                        $stmt_tec->execute([$_POST['tecnico_id']]);
                        $tec = $stmt_tec->fetch();
                        if ($tec && !empty($tec['email'])) {
                            $cuerpo = "Hola <strong>{$tec['nombre_completo']}</strong>,<br><br>"
                                . "Se te ha asignado el siguiente ticket:<br><br>"
                                . "<strong>Ticket:</strong> #{$_POST['ticket_id']} — " . htmlspecialchars($titulo_ticket) . "<br>"
                                . "<strong>Asignado por:</strong> " . htmlspecialchars($nombre_admin);
                            $html = emailAlertaHTML(
                                '🎫 Nuevo Ticket Asignado',
                                $cuerpo,
                                'Ver Ticket',
                                'index.php?view=asignados'
                            );
                            enviarAlertaEmail($tec['email'], "Ticket Asignado #{$_POST['ticket_id']}", $html);
                        }
                    }
                } catch (Exception $ex_mail) {
                    error_log("Email alerta asignacion: " . $ex_mail->getMessage());
                }
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al asignar: " . $e->getMessage() . "</div>";
            }
        }

        // 3. Crear Ticket (Solo si NO estamos editando, es decir, no hay ticket_id)
        if (isset($_POST['titulo_ticket']) && !isset($_POST['ticket_id'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO tickets (titulo, descripcion, prioridad, estado, categoria_id, creador_id, fecha_creacion) VALUES (?, ?, ?, 'Pendiente', ?, ?, NOW())");
                $stmt->execute([
                    $_POST['titulo_ticket'],
                    $_POST['descripcion_ticket'],
                    $_POST['prioridad'],
                    $_POST['categoria_id'],
                    $usuario_id
                ]);

                $new_ticket_id = $pdo->lastInsertId();

                // [EMAIL] Alerta: Ticket Nuevo al admin destino
                try {
                    require_once __DIR__ . '/enviar_notificacion_email.php';
                    $stmt_ac3 = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('alert_email_ticket_nuevo', 'alert_email_admin_destino')");
                    $alv3 = [];
                    foreach ($stmt_ac3->fetchAll(PDO::FETCH_ASSOC) as $r) {
                        $alv3[$r['clave']] = $r['valor'];
                    }
                    if (!empty($alv3['alert_email_ticket_nuevo']) && !empty($alv3['alert_email_admin_destino'])) {
                        $cuerpo_nuevo = "Se ha creado un nuevo ticket en el sistema:<br><br>"
                            . "<strong>Ticket:</strong> #{$new_ticket_id} — " . htmlspecialchars($_POST['titulo_ticket']) . "<br>"
                            . "<strong>Prioridad:</strong> " . htmlspecialchars($_POST['prioridad']);
                        $html_nuevo = emailAlertaHTML(
                            '📩 Nuevo Ticket Creado',
                            $cuerpo_nuevo,
                            'Ver Ticket',
                            'index.php?view=seguimiento'
                        );
                        enviarAlertaEmail($alv3['alert_email_admin_destino'], "Nuevo Ticket #{$new_ticket_id}", $html_nuevo);
                    }
                } catch (Exception $ex_mail3) {
                    error_log("Email alerta nuevo ticket: " . $ex_mail3->getMessage());
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Ticket creado exitosamente.</div>";
                registrar_actividad("Crear Ticket", "Ticket creado: " . $_POST['titulo_ticket'], $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al crear ticket: " . $e->getMessage() . "</div>";
            }
        }

        // 4. Actualizar Estado Ticket
        // 4. Actualizar Estado y Prioridad Ticket (Gestión Técnico)
        if (isset($_POST['nuevo_estado'])) {
            try {
                // Preparamos SQL dinámico

                // VALIDACIÓN DE SEGURIDAD: Si ya está "Completo", NO permitir cambios de estado
                $stmt_check_status = $pdo->prepare("SELECT estado FROM tickets WHERE id = ?");
                $stmt_check_status->execute([$_POST['ticket_id']]);
                $current_status = $stmt_check_status->fetchColumn();

                if ($current_status === 'Completo' && $_POST['nuevo_estado'] !== 'Completo') {
                    throw new Exception("Este ticket ya está completado y no puede ser modificado.");
                }

                $sql = "UPDATE tickets SET estado = ?";
                $params = [$_POST['nuevo_estado']];
                $accion_msg = "Estado actualizado a " . $_POST['nuevo_estado'];

                if (isset($_POST['prioridad'])) {
                    $sql .= ", prioridad = ?";
                    $params[] = $_POST['prioridad'];
                    $accion_msg .= " y Prioridad a " . $_POST['prioridad'];
                }

                // Actualizar Título y Descripción si se envían
                if (!empty($_POST['titulo_ticket'])) {
                    $sql .= ", titulo = ?";
                    $params[] = $_POST['titulo_ticket'];
                }
                if (!empty($_POST['descripcion_ticket'])) {
                    $sql .= ", descripcion = ?";
                    $params[] = $_POST['descripcion_ticket'];
                    $accion_msg .= " (Información actualizada)";
                }

                // Actualizar Categoría si se envía
                if (!empty($_POST['categoria_id'])) {
                    $sql .= ", categoria_id = ?";
                    $params[] = $_POST['categoria_id'];
                    $accion_msg .= " (Categoría actualizada)";
                }

                // Guardar nota de resolución en su propia columna
                if (!empty($_POST['comentarios_resolucion'])) {
                    // Si ya había resolución previa, la conservamos añadiendo la nueva
                    $sql .= ", resolucion = IF(resolucion IS NULL OR resolucion = '', ?, CONCAT(resolucion, '\n\n---\n\n', ?))";
                    $nueva_nota = "📝 [" . date('d/m/Y H:i') . "] " . $_POST['comentarios_resolucion'];
                    $params[] = $nueva_nota; // Para el caso vacío
                    $params[] = $nueva_nota; // Para el CONCAT
                    $accion_msg .= " + Nota de resolución guardada";
                }

                $sql .= " WHERE id = ?";
                $params[] = $_POST['ticket_id'];

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Registrar Actividad MOVIDO AL FINAL para incluir mensaje completo
                // registrar_actividad("Actualizar Ticket", ...); // Removed duplicate

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Ticket actualizado correctamente.</div>";



                // Notificar al creador si el estado es final
                if ($_POST['nuevo_estado'] === 'Completo') {
                    try {
                        $stmt_creador = $pdo->prepare("SELECT creador_id, titulo FROM tickets WHERE id = ?");
                        $stmt_creador->execute([$_POST['ticket_id']]);
                        $ticket_info = $stmt_creador->fetch();

                        if ($ticket_info) {
                            $stmt_notif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) VALUES (?, ?, ?, ?, ?)");
                            $stmt_notif->execute([
                                $ticket_info['creador_id'],
                                "Ticket Completo",
                                "Tu ticket #{$_POST['ticket_id']} '{$ticket_info['titulo']}' ha sido marcado como Completo.",
                                'success',
                                "index.php?view=mis_tickets"
                            ]);
                        }
                    } catch (Exception $ex) {
                    }
                }

                // [EMAIL] Alerta: Ticket Completo/Cerrado
                try {
                    if ($_POST['nuevo_estado'] === 'Completo') {
                        require_once __DIR__ . '/enviar_notificacion_email.php';
                        $stmt_ac2 = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'alert_email_ticket_cerrado' LIMIT 1");
                        $alert_cerrado = $stmt_ac2 ? $stmt_ac2->fetchColumn() : '0';
                        if ($alert_cerrado === '1') {
                            // Obtener email del creador
                            $stmt_cr = $pdo->prepare("
                                SELECT u.email, u.nombre_completo, t.titulo
                                FROM tickets t
                                JOIN usuarios u ON u.id = t.creador_id
                                WHERE t.id = ?
                            ");
                            $stmt_cr->execute([$_POST['ticket_id']]);
                            $cr = $stmt_cr->fetch();
                            if ($cr && !empty($cr['email'])) {
                                $cuerpo_cierre = "Hola <strong>{$cr['nombre_completo']}</strong>,<br><br>"
                                    . "Tu ticket ha sido marcado como <strong>Completo</strong>:<br><br>"
                                    . "<strong>Ticket:</strong> #{$_POST['ticket_id']} — " . htmlspecialchars($cr['titulo']);
                                if (!empty($_POST['comentarios_resolucion'])) {
                                    $cuerpo_cierre .= "<br><br><strong>Nota de cierre:</strong> "
                                        . nl2br(htmlspecialchars($_POST['comentarios_resolucion']));
                                }
                                $html_cierre = emailAlertaHTML(
                                    '✅ Tu Ticket ha sido Resuelto',
                                    $cuerpo_cierre,
                                    'Ver Ticket',
                                    'index.php?view=mis_tickets'
                                );
                                enviarAlertaEmail($cr['email'], "Ticket #{$_POST['ticket_id']} Resuelto", $html_cierre);
                            }
                        }
                    }
                } catch (Exception $ex_mail2) {
                    error_log("Email alerta cierre: " . $ex_mail2->getMessage());
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ $accion_msg.</div>";

                registrar_actividad("Actualizar Ticket", "Ticket ID: " . $_POST['ticket_id'] . " - " . $accion_msg, $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar: " . $e->getMessage() . "</div>";
            }
        }

        // 5.5 Nuevo Comentario (Chat System) [NEW]
        if (isset($_POST['accion_comentar']) && !empty($_POST['nuevo_comentario'])) {
            try {
                $ticket_id = $_POST['ticket_id'];
                $comentario = trim($_POST['nuevo_comentario']);

                // 1. Insertar Comentario
                $stmt = $pdo->prepare("INSERT INTO ticket_comentarios (ticket_id, usuario_id, comentario) VALUES (?, ?, ?)");
                $stmt->execute([$ticket_id, $usuario_id, $comentario]);
                $comentario_id = $pdo->lastInsertId();

                // 2. Manejar Adjunto (Si existe)
                if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['archivo_adjunto'];
                    $upload_dir = 'uploads/'; // Asegurarse que existe
                    if (!is_dir($upload_dir))
                        mkdir($upload_dir, 0755, true);

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $stmt_adj = $pdo->prepare("INSERT INTO ticket_adjuntos (ticket_id, usuario_id, nombre_archivo, ruta_archivo, tipo_archivo, tamano_archivo) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_adj->execute([$ticket_id, $usuario_id, $file['name'], $filepath, $file['type'], $file['size']]);

                        try {
                            $pdo->exec("ALTER TABLE ticket_adjuntos ADD COLUMN comentario_id INT DEFAULT NULL");
                            $pdo->prepare("UPDATE ticket_adjuntos SET comentario_id = ? WHERE ruta_archivo = ?")->execute([$comentario_id, $filepath]);
                        } catch (Exception $e) {
                        }
                    }
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Comentario agregado.</div>";

            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al comentar: " . $e->getMessage() . "</div>";
            }
        }

        // 5. Registrar Ingreso (RRHH)
        if (isset($_POST['accion']) && $_POST['accion'] === 'registrar_ingreso' && ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin')) {
            try {
                $pdo->beginTransaction();

                $sql = "INSERT INTO formularios_rrhh (
                    tipo, fecha_solicitud, nombre_colaborador, cedula_telefono, cargo_zona,
                    disponibilidad_licencias, detalle_licencias,
                    correo_nuevo, direccion_correo,
                    remitente_mostrar, detalle_remitente,
                    respaldo_nube, detalle_respaldo,
                    reenvios_correo, detalle_reenvios,
                    otras_indicaciones,
                    asignacion_equipo, detalle_asignacion,
                    nube_movil, detalle_nube_movil,
                    equipo_usado, especificacion_equipo_usado,
                    creado_por
                ) VALUES (
                    'Ingreso', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
                $stmt = $pdo->prepare($sql);
                $cedula_telefono = $_POST['cedula'] . ' / ' . ($_POST['telefono'] ?? 'N/A');
                $stmt->execute([
                    $_POST['fecha_solicitud'],
                    $_POST['nombre_colaborador'],
                    $cedula_telefono,
                    $_POST['cargo_zona'],
                    $_POST['disponibilidad_licencias'],
                    // [MOD] Procesar Array de Licencias
                    implode(', ', array_filter(array_merge($_POST['licencias'] ?? [], [$_POST['licencias_otras'] ?? '']))),
                    $_POST['correo_nuevo'],
                    $_POST['direccion_correo'] ?? null,
                    $_POST['remitente_mostrar'],
                    $_POST['detalle_remitente'] ?? null,
                    $_POST['respaldo_nube'],
                    $_POST['detalle_respaldo'] ?? null,
                    $_POST['reenvios_correo'],
                    $_POST['detalle_reenvios'] ?? null,
                    $_POST['otras_indicaciones'] ?? null,
                    $_POST['asignacion_equipo'],
                    $_POST['detalle_asignacion'] ?? null,
                    $_POST['nube_movil'] ?? 'NO',
                    $_POST['detalle_nube_movil'] ?? null,
                    isset($_POST['equipo_usado']) ? 'SI' : 'NO',
                    $_POST['especificacion_equipo_usado'] ?? null,
                    $usuario_id
                ]);

                $formulario_id = $pdo->lastInsertId();

                // --- ACTUALIZACIÓN DE INVENTARIO ---
                // Si se seleccionaron equipos del inventario, actualizamos su estado
                $campos_equipos = ['equipo_laptop_id', 'equipo_monitor_id', 'equipo_movil_id', 'equipo_mobiliario_id'];
                $equipos_asignados_nombres = []; // Para guardar nombres y ponerlos en el ticket

                foreach ($campos_equipos as $campo_eq) {
                    if (!empty($_POST[$campo_eq])) {
                        $id_equipo = $_POST[$campo_eq];

                        // Actualizar inventario
                        $stmt_inv = $pdo->prepare("UPDATE inventario SET condicion = 'Asignado', asignado_a = ?, fecha_asignacion = NOW() WHERE id = ?");
                        $stmt_inv->execute([$_POST['nombre_colaborador'], $id_equipo]);

                        // Obtener detalles para el ticket (opcional pero útil)
                        $stmt_det = $pdo->prepare("SELECT tipo, marca, modelo, serial FROM inventario WHERE id = ?");
                        $stmt_det->execute([$id_equipo]);
                        $eq_info = $stmt_det->fetch();
                        if ($eq_info) {
                            $equipos_asignados_nombres[] = "{$eq_info['tipo']}: {$eq_info['marca']} {$eq_info['modelo']} (SN: {$eq_info['serial']})";
                        }
                    }
                }
                // -----------------------------------

                // Intentar buscar categoría 'Cuentas y Accesos' o similar, sino la primera, sino NULL
                $stmt_cat = $pdo->prepare("SELECT id FROM categorias WHERE nombre LIKE ? OR nombre LIKE ? LIMIT 1");
                $stmt_cat->execute(['%Cuentas%', '%RRHH%']);
                $categoria_id = $stmt_cat->fetchColumn();

                if (!$categoria_id) {
                    $stmt_cat_any = $pdo->query("SELECT id FROM categorias LIMIT 1");
                    $categoria_id = $stmt_cat_any->fetchColumn();
                }

                // Si sigue siendo false (tabla vacía), se asigna NULL para evitar error FK
                if ($categoria_id === false) {
                    $categoria_id = null;
                }

                // Crear ticket automático
                $titulo = "Nuevo Ingreso: " . $_POST['nombre_colaborador'];
                $descripcion = "SOLICITUD DE INGRESO DE NUEVO COLABORADOR\n\n";
                $descripcion .= "INFORMACIÓN DEL COLABORADOR:\n";
                $descripcion .= "• Nombre: " . $_POST['nombre_colaborador'] . "\n";
                $descripcion .= "• Cargo/Zona: " . $_POST['cargo_zona'] . "\n";
                $descripcion .= "• Fecha de Ingreso: " . date('d/m/Y', strtotime($_POST['fecha_solicitud'])) . "\n";

                // [NEW] Agregar Licencias solicitadas al ticket
                $licencias_str = implode(', ', array_filter(array_merge($_POST['licencias'] ?? [], [$_POST['licencias_otras'] ?? ''])));
                if (!empty($licencias_str)) {
                    $descripcion .= "\nLICENCIAS SOLICITADAS:\n• " . $licencias_str . "\n";
                }

                if (!empty($equipos_asignados_nombres)) {
                    $descripcion .= "\nEQUIPOS ASIGNADOS:\n";
                    foreach ($equipos_asignados_nombres as $equipo) {
                        $descripcion .= "• " . $equipo . "\n";
                    }
                } else {
                    $descripcion .= "\nEQUIPOS: Pendiente de asignación";
                }

                $stmt_ticket = $pdo->prepare("INSERT INTO tickets (titulo, descripcion, estado, prioridad, creador_id, categoria_id, fecha_creacion) VALUES (?, ?, 'Pendiente', 'Media', ?, ?, NOW())");
                $stmt_ticket->execute([$titulo, $descripcion, $usuario_id, $categoria_id]);

                $ticket_id = $pdo->lastInsertId();
                $pdo->commit();

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Solicitud de ingreso registrada exitosamente. Solicitud #{$ticket_id}</div>";
                registrar_actividad("Registrar Ingreso RRHH", "Ingreso: " . $_POST['nombre_colaborador'] . " - Ticket #" . $ticket_id, $pdo);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }

        // 5.5 Gestión Registros 365
        if (isset($_POST['accion']) && strpos($_POST['accion'], '_registro_365') !== false && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico')) {
            try {
                if ($_POST['accion'] === 'crear_registro_365') {
                    $stmt = $pdo->prepare("INSERT INTO registros_365 (email, licencia, usuario_id, empresa_id, sucursal_id, cargo_id, estado, observaciones, fecha_asignacion, password_ag, password_azure, cuenta_gmail, password_gmail, telefono_principal, telefono_secundario, pin_windows, notas_adicionales) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $fecha_asignacion = !empty($_POST['usuario_id']) ? date('Y-m-d') : null;
                    $usuario_id = !empty($_POST['usuario_id']) ? $_POST['usuario_id'] : null;
                    $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
                    $sucursal_id = !empty($_POST['sucursal_id']) ? $_POST['sucursal_id'] : null;
                    $cargo_id = !empty($_POST['cargo_id']) ? $_POST['cargo_id'] : null;

                    $stmt->execute([
                        $_POST['email'],
                        $_POST['licencia'],
                        $usuario_id,
                        $empresa_id,
                        $sucursal_id,
                        $cargo_id,
                        $_POST['estado'],
                        $_POST['observaciones'],
                        $fecha_asignacion,
                        $_POST['password_ag'] ?? null,
                        $_POST['password_azure'] ?? null,
                        $_POST['cuenta_gmail'] ?? null,
                        $_POST['password_gmail'] ?? null,
                        $_POST['telefono_principal'] ?? null,
                        $_POST['telefono_secundario'] ?? null,
                        $_POST['pin_windows'] ?? null,
                        $_POST['notas_adicionales'] ?? null
                    ]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Cuenta 365 registrada exitosamente.</div>";
                    registrar_actividad("Crear Cuenta 365", "Email: " . $_POST['email'], $pdo);
                    $_SESSION['mensaje_exito'] = "Cuenta 365 registrada exitosamente.";
                    header("Location: index.php?view=registros_365");
                    exit;

                } elseif ($_POST['accion'] === 'editar_registro_365') {
                    $stmt = $pdo->prepare("UPDATE registros_365 SET email = ?, licencia = ?, usuario_id = ?, empresa_id = ?, sucursal_id = ?, cargo_id = ?, estado = ?, observaciones = ?, password_ag = ?, password_azure = ?, cuenta_gmail = ?, password_gmail = ?, telefono_principal = ?, telefono_secundario = ?, pin_windows = ?, notas_adicionales = ? WHERE id = ?");
                    $usuario_id = !empty($_POST['usuario_id']) ? $_POST['usuario_id'] : null;
                    $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
                    $sucursal_id = !empty($_POST['sucursal_id']) ? $_POST['sucursal_id'] : null;
                    $cargo_id = !empty($_POST['cargo_id']) ? $_POST['cargo_id'] : null;

                    // Si cambia la asignación, actualizar fecha
                    $stmt_check = $pdo->prepare("SELECT usuario_id, fecha_asignacion FROM registros_365 WHERE id = ?");
                    $stmt_check->execute([$_POST['id']]);
                    $actual = $stmt_check->fetch();

                    if ($usuario_id && $usuario_id != $actual['usuario_id']) {
                        $stmt_date = $pdo->prepare("UPDATE registros_365 SET fecha_asignacion = ? WHERE id = ?");
                        $stmt_date->execute([date('Y-m-d H:i:s'), $_POST['id']]);
                    } elseif (!$usuario_id) {
                        $stmt_date = $pdo->prepare("UPDATE registros_365 SET fecha_asignacion = NULL WHERE id = ?");
                        $stmt_date->execute([$_POST['id']]);
                    }

                    $stmt->execute([
                        $_POST['email'],
                        $_POST['licencia'],
                        $usuario_id,
                        $empresa_id,
                        $sucursal_id,
                        $cargo_id,
                        $_POST['estado'],
                        $_POST['observaciones'],
                        $_POST['password_ag'] ?? null,
                        $_POST['password_azure'] ?? null,
                        $_POST['cuenta_gmail'] ?? null,
                        $_POST['password_gmail'] ?? null,
                        $_POST['telefono_principal'] ?? null,
                        $_POST['telefono_secundario'] ?? null,
                        $_POST['pin_windows'] ?? null,
                        $_POST['notas_adicionales'] ?? null,
                        $_POST['id']
                    ]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Cuenta 365 actualizada exitosamente.</div>";
                    registrar_actividad("Editar Cuenta 365", "ID: " . $_POST['id'], $pdo);
                    $_SESSION['mensaje_exito'] = "Cuenta 365 actualizada exitosamente.";
                    $redirect_view = $_POST['redirect_view'] ?? 'registros_365';
                    header("Location: index.php?view=" . $redirect_view);
                    exit;

                } elseif ($_POST['accion'] === 'eliminar_registro_365') {
                    $stmt = $pdo->prepare("DELETE FROM registros_365 WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Cuenta 365 eliminada.</div>";
                    registrar_actividad("Eliminar Cuenta 365", "ID: " . $_POST['id'], $pdo);
                }

            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }



        // 5.6 Control Mantenimiento
        require_once __DIR__ . '/controllers/mantenimiento_controller.php';

        // 6. Registrar Salida (RRHH)
        if (isset($_POST['accion']) && $_POST['accion'] === 'registrar_salida' && ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin')) {
            try {
                $pdo->beginTransaction();

                $sql = "INSERT INTO formularios_rrhh (
                    tipo, fecha_solicitud, nombre_colaborador, cedula_telefono, cargo_zona, fecha_efectiva,
                    bloqueo_correo, cuenta_correo_bloqueo,
                    respaldo_info, detalle_respaldo_salida,
                    redireccion_correo, email_redireccion,
                    devolucion_equipo, detalle_devolucion_equipo,
                    devolucion_movil, detalle_devolucion_movil,
                    observaciones, creado_por
                ) VALUES (
                    'Salida', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
                $stmt = $pdo->prepare($sql);
                // [MOD] Procesar Array de Licencias Revocación
                $licencias_revocar_str = isset($_POST['licencias_revocar']) ? implode(', ', $_POST['licencias_revocar']) : '';

                $stmt->execute([
                    $_POST['fecha_solicitud'],
                    $_POST['nombre_colaborador'],
                    $cedula_telefono_salida,
                    $_POST['cargo_zona'],
                    $_POST['fecha_efectiva'],
                    $_POST['bloqueo_correo'],
                    $_POST['cuenta_correo'] ?? null,
                    $_POST['respaldo_info'],
                    $_POST['detalle_respaldo_salida'] ?? null,
                    $_POST['redireccion_correo'],
                    $_POST['email_redireccion'] ?? null,
                    $_POST['devolucion_equipo'],
                        // Usamos detalle_devolucion_equipo para guardar también las licencias a revocar si no hay columna específica
                    ($_POST['detalle_devolucion_equipo'] ?? '') . ($licencias_revocar_str ? " [Licencias a Revocar: $licencias_revocar_str]" : ''),
                    $_POST['devolucion_movil'],
                    $_POST['detalle_devolucion_movil'] ?? null,
                    $_POST['observaciones'] ?? null,
                    $usuario_id
                ]);

                $formulario_id = $pdo->lastInsertId();

                // Intentar buscar categoría 'Cuentas y Accesos' o similar, sino la primera, sino NULL
                $stmt_cat = $pdo->prepare("SELECT id FROM categorias WHERE nombre LIKE ? OR nombre LIKE ? LIMIT 1");
                $stmt_cat->execute(['%Cuentas%', '%RRHH%']);
                $categoria_id = $stmt_cat->fetchColumn();

                if (!$categoria_id) {
                    $stmt_cat_any = $pdo->query("SELECT id FROM categorias LIMIT 1");
                    $categoria_id = $stmt_cat_any->fetchColumn();
                }

                if ($categoria_id === false) {
                    $categoria_id = null;
                }

                $descripcion_ticket = "SOLICITUD DE BAJA DE COLABORADOR\n\n";
                $descripcion_ticket .= "INFORMACIÓN DEL COLABORADOR:\n";
                $descripcion_ticket .= "• Nombre: " . $_POST['nombre_colaborador'] . "\n";
                $descripcion_ticket .= "• Cargo/Zona: " . $_POST['cargo_zona'] . "\n";
                $descripcion_ticket .= "• Fecha Efectiva de Salida: " . date('d/m/Y', strtotime($_POST['fecha_efectiva']));

                // [NEW] Agregar Licencias a Revocar al ticket
                if (!empty($licencias_revocar_str)) {
                    $descripcion_ticket .= "\n\nLICENCIAS A REVOCAR/LIBERAR:\n• " . $licencias_revocar_str;
                }

                $sql_ticket = "INSERT INTO tickets (titulo, descripcion, prioridad, estado, categoria_id, creador_id, fecha_creacion) 
                               VALUES (?, ?, 'Alta', 'Pendiente', ?, ?, NOW())";
                $stmt_ticket = $pdo->prepare($sql_ticket);
                $stmt_ticket->execute([
                    "Baja de Personal: " . $_POST['nombre_colaborador'],
                    $descripcion_ticket,
                    $categoria_id,
                    $usuario_id
                ]);

                $ticket_id = $pdo->lastInsertId();
                $pdo->commit();

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Solicitud de baja procesada exitosamente. Solicitud #{$ticket_id}</div>";
                registrar_actividad("Registrar Salida RRHH", "Salida: " . $_POST['nombre_colaborador'] . " - Ticket #" . $ticket_id, $pdo);
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }

        // 7. Gestión de Inventario y Asignaciones
        if (isset($_POST['accion']) && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH')) {
            try {
                // Registrar Nuevo Activo
                if ($_POST['accion'] === 'guardar_activo_inventario') {
                    // Verificar duplicados
                    $stmt_check = $pdo->prepare("SELECT id FROM inventario WHERE serial = ?");
                    $stmt_check->execute([$_POST['serial']]);
                    if ($stmt_check->fetch()) {
                        throw new Exception("El serial " . $_POST['serial'] . " ya existe en el inventario.");
                    }

                    $stmt = $pdo->prepare("
                        INSERT INTO inventario (tipo, marca, modelo, serial, sku, estado, condicion, registrado_por) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Disponible', ?)
                    ");
                    $stmt->execute([
                        $_POST['tipo'],
                        $_POST['marca'],
                        $_POST['modelo'],
                        $_POST['serial'],
                        $_POST['sku'] ?? null,
                        $_POST['estado'],
                        $usuario_id
                    ]);

                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Equipo registrado exitosamente.</div>";
                    registrar_actividad("Registrar Activo", "Serial: " . $_POST['serial'], $pdo);
                }

                // Asignar Equipos Múltiples
                elseif ($_POST['accion'] === 'asignar_equipos_multiples') {
                    $equipos_ids = explode(',', $_POST['equipos_ids']);
                    $empleado_id = $_POST['empleado_id'];
                    $fecha_asignacion = date('Y-m-d H:i:s');

                    $pdo->beginTransaction();

                    foreach ($equipos_ids as $eq_id) {
                        if (empty($eq_id))
                            continue;

                        $stmt = $pdo->prepare("
                            UPDATE inventario 
                            SET asignado_a = ?, fecha_asignacion = ?, condicion = 'Asignado' 
                            WHERE id = ? AND condicion = 'Disponible'
                        ");
                        $stmt->execute([$empleado_id, $fecha_asignacion, $eq_id]);
                    }

                    $pdo->commit();
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Equipos asignados exitosamente.</div>";
                    registrar_actividad("Asignar Equipos", "Empleado ID: " . $empleado_id, $pdo);
                }

                // Reasignar Equipo
                elseif ($_POST['accion'] === 'reasignar_equipo') {
                    $eq_id = $_POST['equipo_id'];
                    $nuevo_empleado_id = $_POST['nuevo_empleado_id'];
                    $fecha_asignacion = date('Y-m-d H:i:s');

                    $stmt = $pdo->prepare("
                        UPDATE inventario 
                        SET asignado_a = ?, fecha_asignacion = ?, condicion = 'Asignado' 
                        WHERE id = ?
                    ");
                    $stmt->execute([$nuevo_empleado_id, $fecha_asignacion, $eq_id]);

                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Equipo reasignado exitosamente.</div>";
                    registrar_actividad("Reasignar Equipo", "ID: " . $eq_id, $pdo);
                }

                // Liberar Equipo
                elseif ($_POST['accion'] === 'liberar_equipo') {
                    // Check logic applied above effectively
                    $eq_id = $_POST['equipo_id'];

                    $stmt = $pdo->prepare("
                        UPDATE inventario 
                        SET asignado_a = NULL, fecha_asignacion = NULL, condicion = 'Disponible' 
                        WHERE id = ?
                    ");
                    $stmt->execute([$eq_id]);

                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Equipo liberado exitosamente.</div>";
                    registrar_actividad("Liberar Equipo", "ID: " . $eq_id, $pdo);
                }

            } catch (Exception $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }

        // 8. Configuración: Guardar Colores de Roles
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_colores_roles' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('configuracion', $permisos_usuario ?? []))) {
            try {
                if (isset($_POST['colores']) && is_array($_POST['colores'])) {
                    $new_config = array_merge($GLOBALS['rol_colors_config'] ?? [], $_POST['colores']);
                    file_put_contents('config_rol_colors.json', json_encode($new_config, JSON_PRETTY_PRINT));
                    $GLOBALS['rol_colors_config'] = $new_config; // Actualizar runtime
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Configuración de colores actualizada correctamente.</div>";
                    registrar_actividad("Configuración", "Colores de roles actualizados", $pdo);
                }
            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al guardar configuración: " . $e->getMessage() . "</div>";
            }
        }

        // 8.5 Configuración: Guardar Logos de Actas
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_logos_actas' && $rol_usuario === 'SuperAdmin') {
            try {
                // Crear directorio si no existe
                $upload_dir = __DIR__ . '/uploads/logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $logos_actualizados = [];
                $logos_config = ['logo_mastertec', 'logo_master_suministros', 'logo_centro'];

                foreach ($logos_config as $logo_key) {
                    if (isset($_FILES[$logo_key]) && $_FILES[$logo_key]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$logo_key];

                        // Validar tipo de archivo
                        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                        if (!in_array($file['type'], $allowed_types)) {
                            throw new Exception("El archivo {$logo_key} debe ser JPG o PNG");
                        }

                        // Validar tamaño (2MB máximo)
                        if ($file['size'] > 2 * 1024 * 1024) {
                            throw new Exception("El archivo {$logo_key} no debe exceder 2MB");
                        }

                        // Generar nombre único
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = $logo_key . '_' . time() . '.' . $extension;
                        $filepath = $upload_dir . $filename;

                        // Mover archivo
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            // Guardar ruta relativa en BD
                            $relative_path = 'uploads/logos/' . $filename;

                            // Actualizar en BD
                            $stmt = $pdo->prepare("UPDATE configuracion_sistema SET valor = ? WHERE clave = ?");
                            $stmt->execute([$relative_path, $logo_key]);

                            $logos_actualizados[] = $logo_key;

                            // Eliminar logo anterior si existe
                            $stmt_old = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
                            $stmt_old->execute([$logo_key]);
                            $old_path = $stmt_old->fetchColumn();
                            if ($old_path && file_exists(__DIR__ . '/' . $old_path) && $old_path !== $relative_path) {
                                unlink(__DIR__ . '/' . $old_path);
                            }
                        }
                    }
                }

                if (count($logos_actualizados) > 0) {
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Logos actualizados correctamente: " . implode(', ', $logos_actualizados) . "</div>";
                    registrar_actividad("Configuración", "Logos de actas actualizados", $pdo);
                } else {
                    $mensaje_accion = "<div class='bg-blue-100 text-blue-800 p-4 rounded mb-4'>ℹ️ No se seleccionaron archivos nuevos para subir.</div>";
                }

            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al guardar logos: " . $e->getMessage() . "</div>";
            }
        }

        // 8.5.1 Configuración: Guardar Configuración de Correo (SMTP)
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_config_mail' && $rol_usuario === 'SuperAdmin') {
            try {
                $driver = $_POST['driver'] ?? 'mail';
                $host = $_POST['host'] ?? '';
                $port = intval($_POST['port'] ?? 587);
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $encryption = $_POST['encryption'] ?? 'tls';
                $from_address = $_POST['from_address'] ?? '';
                $from_name = $_POST['from_name'] ?? '';

                // Si la contraseña está vacía, intentar mantener la anterior
                if (empty($password)) {
                    $current_config = require __DIR__ . '/config_mail.php';
                    $password = $current_config['password'] ?? '';
                }

                $new_config = [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'username' => $username,
                    'password' => $password,
                    'encryption' => $encryption,
                    'from_address' => $from_address,
                    'from_name' => $from_name,
                    'admin_email' => 'admin@empresa.com' // Mantener o hacer dinámico también
                ];

                $content = "<?php\n\nreturn " . var_export($new_config, true) . ";\n";

                if (file_put_contents(__DIR__ . '/config_mail.php', $content)) {
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Configuración de correo actualizada correctamente.</div>";
                    registrar_actividad("Configuración", "SMTP actualizado", $pdo);
                } else {
                    throw new Exception("No se pudo escribir en config_mail.php. Verifique permisos.");
                }

            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al guardar email: " . $e->getMessage() . "</div>";
            }
        }

        // 8.5.2 Configuración: Guardar Alertas de Correo
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_config_alertas_email' && $rol_usuario === 'SuperAdmin') {
            try {
                $alertas = [
                    'alert_email_ticket_asignado' => 'Email al técnico cuando se le asigna un ticket',
                    'alert_email_ticket_reasignado' => 'Email al técnico cuando se le reasigna un ticket',
                    'alert_email_ticket_cerrado' => 'Email al solicitante cuando su ticket se cierra',
                    'alert_email_ticket_nuevo' => 'Email al admin cuando entra un ticket nuevo',
                ];

                $stmt_upsert = $pdo->prepare("
                    INSERT INTO configuracion_sistema (clave, valor, descripcion)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
                ");

                foreach ($alertas as $clave => $desc) {
                    $valor = isset($_POST[$clave]) ? '1' : '0';
                    $stmt_upsert->execute([$clave, $valor, $desc]);
                }

                // Guardar email admin destino y email from
                foreach (['alert_email_admin_destino', 'alert_email_from'] as $campo) {
                    $val = trim($_POST[$campo] ?? '');
                    $desc_campo = $campo === 'alert_email_admin_destino' ? 'Email del admin receptor de tickets nuevos' : 'Dirección De: para alertas de correo';
                    $stmt_upsert->execute([$campo, $val, $desc_campo]);
                }

                registrar_actividad("Configuración", "Alertas de correo actualizadas", $pdo);

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'msg' => 'Configuración guardada']);
                    exit;
                }
                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Configuración de alertas de correo guardada.</div>";
            } catch (Exception $e) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    while (ob_get_level())
                        ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
                    exit;
                }
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }

        // 8.6 Configuración: Guardar Contenido de Actas
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_contenido_actas' && $rol_usuario === 'SuperAdmin') {
            try {
                $stmt = $pdo->prepare("UPDATE configuracion_sistema SET valor = ? WHERE clave = ?");
                $campos_actualizados = 0;

                // Lista de campos permitidos para actualizar
                $campos_permitidos = [
                    'acta_titulo_empresa',
                    'acta_subtitulo_empresa',
                    'acta_ingreso_titulo',
                    'acta_ingreso_descripcion',
                    'acta_ingreso_nota_pie',
                    'acta_ingreso_seccion_datos',
                    'acta_ingreso_seccion_correo',
                    'acta_ingreso_seccion_equipos',
                    'acta_ingreso_seccion_accesos',
                    'acta_salida_titulo',
                    'acta_salida_descripcion',
                    'acta_salida_nota_pie',
                    'acta_salida_seccion_datos',
                    'acta_salida_seccion_correo',
                    'acta_salida_seccion_equipos',
                    'acta_salida_seccion_respaldo',
                    'acta_label_colaborador',
                    'acta_label_cedula',
                    'acta_label_telefono',
                    'acta_label_cargo',
                    'acta_label_fecha',
                    'acta_label_correo',
                    'acta_label_equipos',
                    'acta_label_observaciones'
                ];

                foreach ($campos_permitidos as $campo) {
                    if (isset($_POST[$campo])) {
                        $stmt->execute([$_POST[$campo], $campo]);
                        if ($stmt->rowCount() > 0) {
                            $campos_actualizados++;
                        }
                    }
                }

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Contenido actualizado correctamente. {$campos_actualizados} campos modificados.</div>";
                registrar_actividad("Configuración", "Contenido de actas actualizado", $pdo);

            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al guardar contenido: " . $e->getMessage() . "</div>";
            }
        }

        // 7. Gestión de Categorías
        // ... (existing code)

        // [NEW] 7.5 Procesar Solicitud de Licencia
        if (isset($_POST['accion']) && $_POST['accion'] === 'solicitar_licencia') {
            try {
                // 1. Obtener Categoria adecuada (Licencias o Software)
                // Usamos LIKE para ser más flexible si cambia la BD
                $stmt_cat = $pdo->prepare("SELECT id FROM categorias WHERE nombre LIKE ? OR nombre LIKE ? LIMIT 1");
                $stmt_cat->execute(['%Licencia%', '%Software%']);
                $cat_id = $stmt_cat->fetchColumn();

                if (!$cat_id) {
                    // Fallback a "Otros" o la primera que encuentre
                    $stmt_cat_any = $pdo->query("SELECT id FROM categorias LIMIT 1");
                    $cat_id = $stmt_cat_any->fetchColumn();
                }

                // 2. Construir Descripción del Ticket
                $beneficiario = $_POST['beneficiario'] ?? 'N/A';
                $tipo_licencia = $_POST['tipo_licencia'] ?? 'N/A';
                $prioridad = $_POST['prioridad'] ?? 'Media';
                $departamento = $_POST['departamento'] ?? 'N/A';
                $justificacion = $_POST['justificacion'] ?? '';

                $titulo_ticket = "Solicitud de Licencia: $tipo_licencia ($beneficiario)";

                $descripcion_ticket = "SOLICITUD DE LICENCIA DE SOFTWARE\n\n";
                $descripcion_ticket .= "👤 Beneficiario: $beneficiario\n";
                $descripcion_ticket .= "🏢 Departamento: $departamento\n";
                $descripcion_ticket .= "💿 Tipo de Licencia: $tipo_licencia\n\n";
                $descripcion_ticket .= "📝 Justificación:\n$justificacion";

                // 3. Crear el Ticket
                $sql = "INSERT INTO tickets (titulo, descripcion, prioridad, estado, categoria_id, creador_id, fecha_creacion) VALUES (?, ?, ?, 'Pendiente', ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $titulo_ticket,
                    $descripcion_ticket,
                    $prioridad,
                    $cat_id,
                    $usuario_id
                ]);
                $new_ticket_id = $pdo->lastInsertId();

                // [NEW] 4. Registrar en Formularios RRHH (Para generar Acta)
                // Reutilizamos columnas:
                // nombre_colaborador -> beneficiario
                // cargo_zona -> departamento
                // detalle_licencias -> tipo_licencia
                // otras_indicaciones -> justificacion
                $sql_form = "INSERT INTO formularios_rrhh (tipo, fecha_solicitud, nombre_colaborador, cargo_zona, detalle_licencias, otras_indicaciones, creado_por) VALUES ('Licencia', NOW(), ?, ?, ?, ?, ?)";
                $stmt_form = $pdo->prepare($sql_form);
                $stmt_form->execute([
                    $beneficiario,
                    $departamento,
                    $tipo_licencia,
                    $justificacion,
                    $usuario_id
                ]);
                $form_id = $pdo->lastInsertId();

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>✓ Solicitud enviada correctamente. Ticket #$new_ticket_id y registro #$form_id creados.</div>";
                registrar_actividad("Solicitud Licencia", "Ticket $new_ticket_id creado para $beneficiario", $pdo);

            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al procesar solicitud: " . $e->getMessage() . "</div>";
            }
        }

        // 7. Gestión de Categorías
        if (isset($_POST['accion']) && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('categorias', $permisos_usuario ?? []))) {
            // Crear Categoría
            if ($_POST['accion'] === 'crear_categoria' && !empty($_POST['nombre_categoria'])) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                    $stmt->execute([$_POST['nombre_categoria']]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Categoría creada exitosamente.</div>";
                    registrar_actividad("Crear Categoría", "Categoría: " . $_POST['nombre_categoria'], $pdo);
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
                }
            }
            // Eliminar Categoría
            if ($_POST['accion'] === 'eliminar_categoria' && !empty($_POST['categoria_id'])) {
                try {
                    $stmt = $pdo->prepare("SELECT nombre FROM categorias WHERE id = ?");
                    $stmt->execute([$_POST['categoria_id']]);
                    $cat_nombre = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
                    $stmt->execute([$_POST['categoria_id']]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Categoría eliminada.</div>";
                    registrar_actividad("Eliminar Categoría", "Categoría eliminada: " . $cat_nombre, $pdo);
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: No se puede eliminar si tiene tickets asociados.</div>";
                }
            }
        }

        // --- Gestión de Cargos ---
        if (isset($_POST['accion']) && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('cargos', $permisos_usuario ?? []))) {
            // Crear Cargo
            if ($_POST['accion'] === 'crear_cargo' && !empty($_POST['nombre'])) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO cargos (nombre, descripcion) VALUES (?, ?)");
                    $stmt->execute([$_POST['nombre'], $_POST['descripcion'] ?? null]);
                    $_SESSION['mensaje_exito'] = "Cargo creado exitosamente.";
                    header("Location: index.php?view=cargos");
                    exit;
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al crear cargo.</div>";
                }
            }

            // Toggle Cargo (activar/desactivar)
            if ($_POST['accion'] === 'toggle_cargo' && !empty($_POST['id'])) {
                try {
                    $stmt = $pdo->prepare("UPDATE cargos SET activo = ? WHERE id = ?");
                    $stmt->execute([$_POST['activo'], $_POST['id']]);
                    $_SESSION['mensaje_exito'] = "Cargo actualizado.";
                    header("Location: index.php?view=cargos");
                    exit;
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar cargo.</div>";
                }
            }

            // Eliminar Cargo
            if ($_POST['accion'] === 'eliminar_cargo' && !empty($_POST['id'])) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM cargos WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $_SESSION['mensaje_exito'] = "Cargo eliminado.";
                    header("Location: index.php?view=cargos");
                    exit;
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: El cargo está en uso.</div>";
                }
            }
        }

        // 8. Eliminar Usuario
        if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_usuario' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            if (!empty($_POST['usuario_id']) && $_POST['usuario_id'] != $usuario_id) {
                try {
                    $stmt = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
                    $stmt->execute([$_POST['usuario_id']]);
                    $user_nombre = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->execute([$_POST['usuario_id']]);
                    $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Usuario eliminado exitosamente.</div>";
                    registrar_actividad("Eliminar Usuario", "Usuario eliminado: " . $user_nombre, $pdo);
                } catch (PDOException $e) {
                    $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al eliminar usuario: " . $e->getMessage() . "</div>";
                }
            } else {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: No puedes eliminar tu propio usuario o ID inválido.</div>";
            }
        }

        // 9. Gestión de Permisos Específicos de Usuario [NUEVO]
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_permisos_usuario' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin')) {
            // CSRF Check (Injected)
            if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
                throw new Exception('Error de seguridad: Token CSRF inválido');
            }
            try {
                $uid_target = $_POST['usuario_id'];

                // 1. Borrar permisos anteriores de este usuario
                $stmt_del = $pdo->prepare("DELETE FROM permisos_usuarios WHERE usuario_id = ?");
                $stmt_del->execute([$uid_target]);

                // 2. Insertar nuevos permisos seleccionados
                if (isset($_POST['modulos_extra']) && is_array($_POST['modulos_extra'])) {
                    $stmt_ins = $pdo->prepare("INSERT INTO permisos_usuarios (usuario_id, modulo_id) VALUES (?, ?)");
                    foreach ($_POST['modulos_extra'] as $mid) {
                        try {
                            $stmt_ins->execute([$uid_target, $mid]);
                        } catch (PDOException $e) {
                            // Ignorar duplicados si los hubiera
                        }
                    }
                }

                // Obtener nombre para log
                $stmt_name = $pdo->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
                $stmt_name->execute([$uid_target]);
                $u_name = $stmt_name->fetchColumn();

                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Permisos actualizados para usuario: " . htmlspecialchars($u_name) . "</div>";
                registrar_actividad("Permisos Usuario", "Actualizados permisos para ID: $uid_target", $pdo);

                // Redirigir para feedback visual
                header("Location: index.php?view=permisos&success=1");
                exit;

            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar permisos: " . $e->getMessage() . "</div>";
            }
        }

        // 9. Guardar Activo Inventario
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_activo_inventario' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? []))) {
            try {
                $tipo = $_POST['tipo'];
                $marca = $_POST['marca'];
                $modelo = $_POST['modelo'];
                $serial = $_POST['serial'];
                $sku = trim($_POST['sku'] ?? '');
                $estado = $_POST['estado'];
                $condicion = 'Disponible';

                // Validar SKU duplicado si no está vacío
                if (!empty($sku)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM inventario WHERE sku = ?");
                    $stmt_check->execute([$sku]);
                    if ($stmt_check->fetch()) {
                        throw new Exception("El SKU '$sku' ya está registrado en el sistema.");
                    }
                }

                // Validar Serial duplicado
                $stmt_check_serial = $pdo->prepare("SELECT id FROM inventario WHERE serial = ?");
                $stmt_check_serial->execute([$serial]);
                if ($stmt_check_serial->fetch()) {
                    throw new Exception("El Serial '$serial' ya está registrado en el sistema.");
                }

                $sucursal_inv = !empty($_POST['sucursal_id']) ? (int) $_POST['sucursal_id'] : null;

                $sql = "INSERT INTO inventario (tipo, marca, modelo, serial, sku, estado, condicion, sucursal_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tipo, $marca, $modelo, $serial, !empty($sku) ? $sku : null, $estado, $condicion, $sucursal_inv]);

                // Guardar éxito en sesión para mostrarlo tras redirección
                $_SESSION['mensaje_exito'] = "✅ Activo registrado correctamente: $tipo $marca $modelo";

                // Redirigir explícitamente a la vista de inventario
                header("Location: index.php?view=inventario");
                exit; // Detener ejecución para asegurar redirección
            } catch (Exception $e) {
                // Captura tanto excepciones manuales como de PDO
                $mensaje_error_txt = $e->getMessage();
                // Si es error de duplicado de BD que se pasó por alto
                if (strpos($mensaje_error_txt, 'Duplicate entry') !== false) {
                    $mensaje_error_txt = "Error: SKU o Serial ya registrado.";
                }
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al registrar activo: " . $mensaje_error_txt . "</div>";
            }
        }

        // 10. Eliminar Activo Inventario
        if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_activo_inventario' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? []))) {
            try {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM inventario WHERE id = ?");
                $stmt->execute([$id]);

                $_SESSION['mensaje_exito'] = "✅ Activo eliminado correctamente.";
                header("Location: index.php?view=inventario");
                exit;
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al eliminar activo: " . $e->getMessage() . "</div>";
            }
        }

        // 11. Actualizar Activo Inventario
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_activo_inventario' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? []))) {
            try {
                $id = $_POST['id'];
                $tipo = $_POST['tipo'];
                $marca = $_POST['marca'];
                $modelo = $_POST['modelo'];
                $serial = $_POST['serial'];
                $sku = trim($_POST['sku'] ?? '');
                $estado = $_POST['estado'];

                // Campos Tecnicos [NEW]
                $procesador = $_POST['procesador'] ?? null;
                $ram = $_POST['ram'] ?? null;
                $disco_duro = $_POST['disco_duro'] ?? null;
                $sistema_operativo = $_POST['sistema_operativo'] ?? null;
                $ip_address = $_POST['ip_address'] ?? null;
                $mac_address = $_POST['mac_address'] ?? null;
                $anydesk_id = $_POST['anydesk_id'] ?? null;

                // Validar SKU duplicado (excluyendo el actual)
                if (!empty($sku)) {
                    $stmt_check = $pdo->prepare("SELECT id FROM inventario WHERE sku = ? AND id != ?");
                    $stmt_check->execute([$sku, $id]);
                    if ($stmt_check->fetch()) {
                        throw new Exception("El SKU '$sku' ya está asignado a otro equipo.");
                    }
                }

                // Validar Serial duplicado (excluyendo el actual)
                $stmt_check_serial = $pdo->prepare("SELECT id FROM inventario WHERE serial = ? AND id != ?");
                $stmt_check_serial->execute([$serial, $id]);
                if ($stmt_check_serial->fetch()) {
                    throw new Exception("El Serial '$serial' ya está asignado a otro equipo.");
                }

                $sucursal_inv_upd = !empty($_POST['sucursal_id']) ? (int) $_POST['sucursal_id'] : null;

                $sql = "UPDATE inventario SET tipo = ?, marca = ?, modelo = ?, serial = ?, sku = ?, estado = ?, procesador = ?, ram = ?, disco_duro = ?, sistema_operativo = ?, ip_address = ?, mac_address = ?, anydesk_id = ?, sucursal_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tipo, $marca, $modelo, $serial, !empty($sku) ? $sku : null, $estado, $procesador, $ram, $disco_duro, $sistema_operativo, $ip_address, $mac_address, $anydesk_id, $sucursal_inv_upd, $id]);

                $_SESSION['mensaje_exito'] = "✅ Activo actualizado correctamente.";
                header("Location: index.php?view=inventario");
                exit;
            } catch (Exception $e) {
                $mensaje_error_txt = $e->getMessage();
                if (strpos($mensaje_error_txt, 'Duplicate entry') !== false) {
                    $mensaje_error_txt = "Error: SKU o Serial ya registrado en otro activo.";
                }
                // Usar sesión para el error y redirigir
                $_SESSION['error_accion'] = $mensaje_error_txt;
                header("Location: index.php?view=editar_activo_inventario&id=" . $id);
                exit;
            }
        }

        // 12. Procesar Importación Inventario
        // 12. Procesar Importación Inventario
        if (isset($_POST['accion']) && $_POST['accion'] === 'procesar_importacion_inventario' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? []))) {
            if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['archivo_csv']['tmp_name'];
                $handle = fopen($tmpName, 'r');

                if ($handle !== FALSE) {
                    $row = 0;
                    $success = 0;
                    $errors = 0;
                    $error_msgs = [];

                    // Detectar separador (coma o punto y coma)
                    $firstLine = fgets($handle);
                    rewind($handle); // Volver al inicio despues de leer
                    $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                        $row++;
                        if ($row == 1)
                            continue; // Saltar cabecera

                        // Mapeo básico por índice, asumiendo orden del template:
                        // 0: Tipo, 1: Marca, 2: Modelo, 3: Serial, 4: SKU, 5: Estado, 6: Condicion
                        // Verificar que tenga al menos columnas criticas (hasta serial)
                        if (count($data) < 4) {
                            $errors++;
                            continue;
                        }

                        try {
                            $tipo = trim($data[0] ?? '');
                            $marca = trim($data[1] ?? '');
                            $modelo = trim($data[2] ?? '');
                            $serial = trim($data[3] ?? '');
                            $sku = trim($data[4] ?? ''); // Opcional
                            $estado = trim($data[5] ?? 'Bueno');
                            $condicion = trim($data[6] ?? 'Disponible');

                            if (empty($tipo) || empty($marca) || empty($modelo) || empty($serial)) {
                                throw new Exception("Datos incompletos en fila $row");
                            }

                            // Verificar duplicados SKUs si existe
                            if (!empty($sku)) {
                                $stmt = $pdo->prepare("SELECT id FROM inventario WHERE sku = ?");
                                $stmt->execute([$sku]);
                                if ($stmt->fetch())
                                    throw new Exception("SKU $sku duplicado");
                            }
                            // Verificar Serial
                            $stmt = $pdo->prepare("SELECT id FROM inventario WHERE serial = ?");
                            $stmt->execute([$serial]);
                            if ($stmt->fetch())
                                throw new Exception("Serial $serial duplicado");

                            $sql = "INSERT INTO inventario (tipo, marca, modelo, serial, sku, estado, condicion) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$tipo, $marca, $modelo, $serial, !empty($sku) ? $sku : null, $estado, $condicion]);
                            $success++;
                        } catch (Exception $e) {
                            $errors++;
                            if (count($error_msgs) < 5) { // Solo guardar primeros 5 errores
                                $error_msgs[] = "Fila $row: " . $e->getMessage();
                            }
                        }
                    }
                    fclose($handle);

                    $msg = "Proceso finalizado. ✅ Insertados: $success. ❌ Errores: $errors.";
                    if ($errors > 0) {
                        $msg .= " <br>Detalles (primeros errores): <ul class='text-left list-disc list-inside mt-2 text-sm'>" . implode('', array_map(function ($m) {
                            return "<li>$m</li>";
                        }, $error_msgs)) . "</ul>";
                        $_SESSION['mensaje_error'] = $msg;
                    } else {
                        $_SESSION['mensaje_exito'] = $msg;
                    }

                } else {
                    $_SESSION['mensaje_error'] = "No se pudo abrir el archivo.";
                }
            } else {
                $_SESSION['mensaje_error'] = "Error al subir archivo.";
            }
            header("Location: index.php?view=inventario");
            exit;
        }

        // 13. Asignar Equipo a Empleado
        if (isset($_POST['accion']) && $_POST['accion'] === 'asignar_equipo' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_asignacion_equipos', $permisos_usuario ?? []))) {
            try {
                $equipo_id = $_POST['equipo_id'];
                $empleado_id = $_POST['empleado_id'];
                $notas = $_POST['notas'] ?? '';

                // Verificar que el equipo esté disponible
                $stmt_check = $pdo->prepare("SELECT condicion FROM inventario WHERE id = ?");
                $stmt_check->execute([$equipo_id]);
                $equipo = $stmt_check->fetch();

                if (!$equipo || $equipo['condicion'] !== 'Disponible') {
                    throw new Exception("El equipo seleccionado no está disponible para asignación.");
                }

                // Actualizar el inventario
                $stmt = $pdo->prepare("UPDATE inventario SET condicion = 'Asignado', asignado_a = ?, fecha_asignacion = NOW() WHERE id = ?");
                $stmt->execute([$empleado_id, $equipo_id]);

                // Registrar en historial (opcional, si tienes tabla de historial)
                // $stmt_hist = $pdo->prepare("INSERT INTO historial_asignaciones (equipo_id, empleado_id, accion, notas, fecha) VALUES (?, ?, 'Asignado', ?, NOW())");
                // $stmt_hist->execute([$equipo_id, $empleado_id, $notas]);

                $_SESSION['mensaje_exito'] = "✅ Equipo asignado correctamente al empleado.";
                registrar_actividad("Asignar Equipo", "Equipo ID $equipo_id asignado a Empleado ID $empleado_id", $pdo);
                header("Location: index.php?view=asignacion_equipos");
                exit;
            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al asignar equipo: " . $e->getMessage() . "</div>";
            }
        }

        // 14. Reasignar Equipo
        if (isset($_POST['accion']) && $_POST['accion'] === 'reasignar_equipo' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH')) {
            try {
                $equipo_id = $_POST['equipo_id'];
                $nuevo_empleado_id = $_POST['nuevo_empleado_id'];
                $motivo = $_POST['motivo'] ?? 'Reasignación directa';

                // Verificar estado
                $stmt_check = $pdo->prepare("SELECT condicion, asignado_a FROM inventario WHERE id = ?");
                $stmt_check->execute([$equipo_id]);
                $equipo = $stmt_check->fetch();

                if (!$equipo || $equipo['condicion'] !== 'Asignado') {
                    throw new Exception("El equipo no está asignado actualmente.");
                }

                $antiguo_empleado_id = $equipo['asignado_a'];

                // Actualizar inventario
                $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW() WHERE id = ?");
                $stmt->execute([$nuevo_empleado_id, $equipo_id]);

                // Registrar actividad (historial)
                registrar_actividad(
                    "Reasignar Equipo",
                    "Equipo ID $equipo_id reasignado de Emp ID $antiguo_empleado_id a Emp ID $nuevo_empleado_id. Motivo: $motivo",
                    $pdo
                );

                $_SESSION['mensaje_exito'] = "✅ Equipo reasignado correctamente.";
                header("Location: index.php?view=asignacion_equipos");
                exit;
            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al reasignar equipo: " . $e->getMessage() . "</div>";
            }
        }

        // 15. Guardar Preferencias de Notificaciones
        if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_preferencias_notifs') {
            try {
                $notifs_email = isset($_POST['notifs_email']) ? 1 : 0;
                $notifs_sonido = isset($_POST['notifs_sonido']) ? 1 : 0;
                $usuario_id = $_SESSION['usuario_id'];

                $stmt = $pdo->prepare("UPDATE usuarios SET notifs_email = ?, notifs_sonido = ? WHERE id = ?");
                $stmt->execute([$notifs_email, $notifs_sonido, $usuario_id]);

                // Mensaje solo si no es AJAX
                if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                    $_SESSION['mensaje_exito'] = "✅ Preferencias guardadas correctamente.";
                    header("Location: index.php?view=config");
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success']);
                    exit;
                }
            } catch (Exception $e) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    exit;
                }
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error: " . $e->getMessage() . "</div>";
            }
        }

        // 16. Marcar Notificación como Leída
        if (isset($_POST['accion']) && $_POST['accion'] === 'marcar_notificacion') {
            try {
                $notif_id = $_POST['id'];
                $usuario_id = $_SESSION['usuario_id'];

                if ($notif_id === 'todas') {
                    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
                    $stmt->execute([$usuario_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
                    $stmt->execute([$notif_id, $usuario_id]);
                }

                header('Content-Type: application/json');
                echo json_encode(['status' => 'success']);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }

        // 16b. Enviar Notificación (Admin)
        if (isset($_POST['accion']) && $_POST['accion'] === 'enviar_notificacion_admin' && in_array($rol_usuario, ['Admin', 'SuperAdmin'])) {
            try {
                $destinatario_id = $_POST['destinatario_id'] ?? '';
                $tipo_notif = in_array($_POST['tipo'] ?? '', ['info', 'success', 'warning', 'error']) ? $_POST['tipo'] : 'info';
                $titulo_notif = trim($_POST['titulo'] ?? '');
                $mensaje_notif = trim($_POST['mensaje'] ?? '');
                $enlace_notif = trim($_POST['enlace'] ?? '') ?: null;

                if (empty($titulo_notif) || empty($mensaje_notif)) {
                    throw new Exception('El título y el mensaje son obligatorios.');
                }

                $enviados = 0;
                if ($destinatario_id === 'todos') {
                    // Enviar a todos los usuarios
                    $stmt_ids = $pdo->query("SELECT id FROM usuarios");
                    $ids_usuarios = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);
                    $stmt_notif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) VALUES (?, ?, ?, ?, ?)");
                    foreach ($ids_usuarios as $uid) {
                        $stmt_notif->execute([$uid, $titulo_notif, $mensaje_notif, $tipo_notif, $enlace_notif]);
                        $enviados++;
                    }
                } else {
                    $stmt_notif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) VALUES (?, ?, ?, ?, ?)");
                    $stmt_notif->execute([intval($destinatario_id), $titulo_notif, $mensaje_notif, $tipo_notif, $enlace_notif]);
                    $enviados = 1;
                }

                registrar_actividad("Notificación Admin", "Enviada a $enviados usuario(s): $titulo_notif", $pdo);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'msg' => "Notificación enviada a $enviados usuario(s)."]);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
                exit;
            }
        }

        // 13b. Asignar Múltiples Equipos a Empleado
        if (isset($_POST['accion']) && $_POST['accion'] === 'asignar_equipos_multiples' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_asignacion_equipos', $permisos_usuario ?? []))) {
            try {
                $empleado_id = $_POST['empleado_id'];
                $equipos_ids = $_POST['equipos_ids'] ?? '';
                $notas = $_POST['notas'] ?? '';

                if (empty($equipos_ids)) {
                    throw new Exception("Debes seleccionar al menos un equipo.");
                }

                // Convertir string de IDs a array
                $equipos_array = explode(',', $equipos_ids);
                $equipos_array = array_filter($equipos_array); // Eliminar vacíos

                if (empty($equipos_array)) {
                    throw new Exception("Debes seleccionar al menos un equipo.");
                }

                $pdo->beginTransaction();
                $equipos_asignados = 0;
                $errores = [];

                foreach ($equipos_array as $equipo_id) {
                    try {
                        // Verificar que el equipo esté disponible
                        $stmt_check = $pdo->prepare("SELECT condicion, tipo, marca, modelo FROM inventario WHERE id = ?");
                        $stmt_check->execute([$equipo_id]);
                        $equipo = $stmt_check->fetch();

                        if (!$equipo) {
                            $errores[] = "Equipo ID $equipo_id no encontrado";
                            continue;
                        }

                        if ($equipo['condicion'] !== 'Disponible') {
                            $errores[] = "{$equipo['tipo']} {$equipo['marca']} no está disponible";
                            continue;
                        }

                        // Actualizar el inventario
                        $stmt = $pdo->prepare("UPDATE inventario SET condicion = 'Asignado', asignado_a = ?, fecha_asignacion = NOW() WHERE id = ?");
                        $stmt->execute([$empleado_id, $equipo_id]);
                        $equipos_asignados++;

                    } catch (Exception $e) {
                        $errores[] = "Error en equipo ID $equipo_id: " . $e->getMessage();
                    }
                }

                $pdo->commit();

                // Mensaje de resultado
                if ($equipos_asignados > 0) {
                    $msg = "✅ $equipos_asignados equipo" . ($equipos_asignados > 1 ? 's' : '') . " asignado" . ($equipos_asignados > 1 ? 's' : '') . " correctamente.";
                    if (!empty($errores)) {
                        $msg .= " ⚠️ " . count($errores) . " error(es): " . implode(', ', $errores);
                    }
                    $_SESSION['mensaje_exito'] = $msg;
                    registrar_actividad("Asignar Equipos", "$equipos_asignados equipos asignados a Empleado ID $empleado_id", $pdo);
                } else {
                    $_SESSION['mensaje_error'] = "❌ No se pudo asignar ningún equipo. Errores: " . implode(', ', $errores);
                }

                header("Location: index.php?view=asignacion_equipos");
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al asignar equipos: " . $e->getMessage() . "</div>";
            }
        }

        // 14. Reasignar Equipo
        if (isset($_POST['accion']) && $_POST['accion'] === 'reasignar_equipo' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH')) {
            try {
                $equipo_id = $_POST['equipo_id'];
                $nuevo_empleado_id = $_POST['nuevo_empleado_id'];

                // Actualizar el inventario
                $stmt = $pdo->prepare("UPDATE inventario SET asignado_a = ?, fecha_asignacion = NOW() WHERE id = ?");
                $stmt->execute([$nuevo_empleado_id, $equipo_id]);

                $_SESSION['mensaje_exito'] = "✅ Equipo reasignado correctamente.";
                registrar_actividad("Reasignar Equipo", "Equipo ID $equipo_id reasignado a Empleado ID $nuevo_empleado_id", $pdo);
                header("Location: index.php?view=asignacion_equipos");
                exit;
            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al reasignar equipo: " . $e->getMessage() . "</div>";
            }
        }

        // 15. Liberar Equipo
        if (isset($_POST['accion']) && $_POST['accion'] === 'liberar_equipo' && ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_asignacion_equipos', $permisos_usuario ?? []))) {
            try {
                $equipo_id = $_POST['equipo_id'];

                // Actualizar el inventario
                $stmt = $pdo->prepare("UPDATE inventario SET condicion = 'Disponible', asignado_a = NULL, fecha_asignacion = NULL WHERE id = ?");
                $stmt->execute([$equipo_id]);

                $_SESSION['mensaje_exito'] = "✅ Equipo liberado correctamente. Ahora está disponible para nuevas asignaciones.";
                registrar_actividad("Liberar Equipo", "Equipo ID $equipo_id liberado", $pdo);
                header("Location: index.php?view=asignacion_equipos");
                exit;
            } catch (Exception $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al liberar equipo: " . $e->getMessage() . "</div>";
            }
        }

        // 9. Editar Usuario (Actualizar)
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_usuario' && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('gestion_usuarios', $permisos_usuario ?? []))) {
            try {
                $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol_id = ? WHERE id = ?";
                $params = [$_POST['nombre_usuario'], $_POST['email'], $_POST['rol'], $_POST['usuario_id']];

                // Si se proporcionó contraseña, actualizarla también
                if (!empty($_POST['password'])) {
                    $sql = "UPDATE usuarios SET nombre_completo = ?, email = ?, rol_id = ?, password = ? WHERE id = ?";
                    $params = [$_POST['nombre_usuario'], $_POST['email'], $_POST['rol'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['usuario_id']];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $mensaje_accion = "<div class='bg-green-100 text-green-800 p-4 rounded mb-4'>Usuario actualizado exitosamente.</div>";
                registrar_actividad("Actualizar Usuario", "Usuario actualizado: " . $_POST['nombre_usuario'], $pdo);
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar usuario: " . $e->getMessage() . "</div>";
            }
        }

        // 10. Actualizar Permisos (SuperAdmin)
        if (isset($_POST['actualizar_permisos']) && $rol_usuario === 'SuperAdmin') {
            // CSRF Check
            if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
                throw new Exception('Error de seguridad: Token CSRF inválido');
            }
            try {
                $rol_id = $_POST['rol_id'];
                $modulos_seleccionados = $_POST['modulos'] ?? [];

                // Eliminar todos los permisos actuales del rol
                $stmt = $pdo->prepare("DELETE FROM permisos_roles WHERE rol_id = ?");
                $stmt->execute([$rol_id]);

                // Insertar los nuevos permisos
                if (!empty($modulos_seleccionados)) {
                    $stmt = $pdo->prepare("INSERT INTO permisos_roles (rol_id, modulo_id) VALUES (?, ?)");
                    foreach ($modulos_seleccionados as $modulo_id) {
                        $stmt->execute([$rol_id, $modulo_id]);
                    }
                }

                registrar_actividad("Actualizar Permisos", "Permisos actualizados para rol ID: " . $rol_id, $pdo);

                // Redirigir para evitar reenvío de formulario
                header("Location: index.php?view=permisos&success=1");
                exit;
            } catch (PDOException $e) {
                $mensaje_accion = "<div class='bg-red-100 text-red-800 p-4 rounded mb-4'>Error al actualizar permisos: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// --- CARGA DE DATOS ---
try {
    $stmt = $pdo->query("SELECT u.id, u.nombre_completo as nombre, u.email, u.rol_id, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}

try {
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}

try {
    $stmt = $pdo->query("SELECT id, nombre FROM roles");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

try {
    $stmt = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre ASC");
    $todas_las_empresas = $stmt->fetchAll();
} catch (PDOException $e) {
    $todas_las_empresas = [];
}

try {
    $stmt = $pdo->query("SELECT id, nombre, empresa_id FROM sucursales ORDER BY nombre ASC");
    $todas_las_sucursales = $stmt->fetchAll();
} catch (PDOException $e) {
    $todas_las_sucursales = [];
}

try {
    $sql_tickets = "SELECT t.*, c.nombre as categoria, u.nombre_completo as creador, e.pais as creador_pais, e.id as empresa_id, e.nombre as empresa_nombre, s.nombre as sucursal_nombre,
                    (SELECT tecnico_id FROM asignaciones WHERE ticket_id = t.id ORDER BY fecha_asignacion DESC LIMIT 1) as tecnico_id 
                    FROM tickets t 
                    LEFT JOIN categorias c ON t.categoria_id = c.id
                    LEFT JOIN usuarios u ON t.creador_id = u.id
                    LEFT JOIN empresas e ON u.empresa_id = e.id
                    LEFT JOIN sucursales s ON u.sucursal_id = s.id
                    ORDER BY t.id DESC";
    $stmt = $pdo->query($sql_tickets);
    $tickets = $stmt->fetchAll();

    // Ordenamiento global: Resueltos/Cerrados al final
    usort($tickets, function ($a, $b) {
        $a_resuelto = in_array($a['estado'], ['Resuelto', 'Cerrado']);
        $b_resuelto = in_array($b['estado'], ['Resuelto', 'Cerrado']);

        if ($a_resuelto && !$b_resuelto)
            return 1;  // A va después
        if (!$a_resuelto && $b_resuelto)
            return -1; // A va antes

        // Si ambos son del mismo grupo, mantener orden por ID descendente
        return $b['id'] - $a['id'];
    });
} catch (PDOException $e) {
    $tickets = [];
}

try {
    $stmt = $pdo->query("SELECT * FROM formularios_rrhh ORDER BY fecha_registro DESC");
    $formularios = $stmt->fetchAll();
} catch (PDOException $e) {
    $formularios = [];
}

// --- HTML ---
include __DIR__ . '/contenedor_0_base.php';
include __DIR__ . '/seccion_1_cabecera.php';
include __DIR__ . '/seccion_2_menu_lateral.php';

if (!empty($mensaje_accion)) {
    echo "<div class='ml-64 p-6'>$mensaje_accion</div>";
}

$view = $_GET['view'] ?? 'dashboard';

switch ($view) {
    case 'backup':
        if ($rol_usuario === 'SuperAdmin' || in_array('backup_bd', $permisos_usuario ?? [])) {
            include __DIR__ . '/backup_bd.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'restore':
        if ($rol_usuario === 'SuperAdmin' || in_array('restaurar_bd', $permisos_usuario ?? [])) {
            include __DIR__ . '/restaurar_bd.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'restart':
        if ($rol_usuario === 'SuperAdmin' || in_array('reiniciar_bd', $permisos_usuario ?? [])) {
            include __DIR__ . '/reiniciar_bd.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'dashboard':
        include __DIR__ . '/seccion_5_panel_inferior.php';
        break;

    case 'editar_ticket':
        $ticket_id = $_GET['id'] ?? 0;
        // Consulta corregida: obtiene tecnico_id de la tabla asignaciones
        $stmt_check = $pdo->prepare("SELECT t.*, c.nombre as categoria_nombre, u.nombre_completo as creador_nombre,
                                     (SELECT tecnico_id FROM asignaciones WHERE ticket_id = t.id ORDER BY fecha_asignacion DESC LIMIT 1) as tecnico_id
                                     FROM tickets t 
                                     LEFT JOIN categorias c ON t.categoria_id = c.id 
                                     LEFT JOIN usuarios u ON t.creador_id = u.id 
                                     WHERE t.id = ?");
        $stmt_check->execute([$ticket_id]);
        $ticket_editar = $stmt_check->fetch();

        // [NEW] Cargar Comentarios y Adjuntos
        $comentarios = [];
        if ($ticket_editar) {
            $stmt_com = $pdo->prepare("
                SELECT c.*, u.nombre_completo as nombre_usuario, r.nombre as rol_usuario 
                FROM ticket_comentarios c 
                JOIN usuarios u ON c.usuario_id = u.id 
                LEFT JOIN roles r ON u.rol_id = r.id
                WHERE c.ticket_id = ? 
                ORDER BY c.fecha_creacion ASC
            ");
            $stmt_com->execute([$ticket_id]);
            $comentarios = $stmt_com->fetchAll(PDO::FETCH_ASSOC);

            // Cargar adjuntos y vincularlos a comentarios (o general si no tienen id)
            // Optimizacion: Traer todos los adjuntos del ticket y distribuirlos en PHP
            $stmt_adj = $pdo->prepare("SELECT * FROM ticket_adjuntos WHERE ticket_id = ?");
            $stmt_adj->execute([$ticket_id]);
            $todos_adjuntos = $stmt_adj->fetchAll(PDO::FETCH_ASSOC);

            // Vincular adjuntos a objetos de comentario
            foreach ($comentarios as &$com) {
                $com['adjuntos'] = array_filter($todos_adjuntos, function ($adj) use ($com) {
                    // Si tenemos columna comentario_id, usarla. Si no, fallback por fecha (menos preciso).
                    // Asumiremos que la columna existe tras nuestro ALTER.
                    return isset($adj['comentario_id']) && $adj['comentario_id'] == $com['id'];
                });
            }
            unset($com); // Romper referencia
        }


        if ($ticket_editar) {
            // Verificación estricta de permisos
            $es_admin = ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin');
            $es_creador = ($ticket_editar['creador_id'] == $usuario_id);
            $es_tecnico_asignado = ($ticket_editar['tecnico_id'] == $usuario_id);

            if ($es_admin || $es_creador || $es_tecnico_asignado) {
                include __DIR__ . '/seccion_3_editar_ticket.php';
            } else {
                echo "<div class='ml-64 p-6 text-red-500 bg-red-50 border border-red-200 rounded-lg m-6'>
                        <h3 class='font-bold'><i class='ri-lock-line'></i> Acceso Denegado</h3>
                        <p>No tienes permiso para gestionar este ticket.</p>
                      </div>";
            }
        } else {
            echo "<div class='ml-64 p-6'>Ticket no encontrado.</div>";
        }
        break;

    case 'crear_ticket':
        if ($rol_usuario === 'Tecnico') {
            echo "<div class='ml-72 p-6 text-red-500 bg-red-50 border border-red-200 rounded-lg m-6'>
                <h3 class='font-bold text-lg'><i class='ri-error-warning-line'></i> Acceso Restringido</h3>
                <p>Los técnicos no tienen permisos para crear tickets manualmente.</p>
            </div>";
        } else {
            $mostrar_solo_ticket = true;
            include __DIR__ . '/seccion_3_formulario.php';
        }
        break;

    // [NEW] Solicitud de Licencia
    case 'solicitud_licencia':
        // Todos pueden solicitar, o restringir si es necesario. Por ahora todos.
        include __DIR__ . '/seccion_solicitud_licencia.php';
        break;

    case 'mis_tickets':
        $mostrar_solo_mis_tickets = true;
        include __DIR__ . '/seccion_4_listados.php';
        break;

    case 'usuarios':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('gestion_usuarios', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_gestion_usuarios.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'notificaciones':
        include __DIR__ . '/seccion_notificaciones.php';
        break;

    case 'crear_usuario':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('gestion_usuarios', $permisos_usuario ?? [])) {
            $mostrar_solo_usuario = true;
            include __DIR__ . '/seccion_3_formulario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'historial_tecnico':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico') {
            include __DIR__ . '/seccion_historial_tecnico.php';
        } else {
            echo "<div class='ml-64 p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'editar_usuario':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('gestion_usuarios', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_editar_usuario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'asignar':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('asignar_tickets', $permisos_usuario ?? [])) {
            $mostrar_solo_tabla_tickets_admin = true;
            include __DIR__ . '/seccion_4_listados.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'asignados':
        if ($rol_usuario === 'Tecnico') {
            $mostrar_mis_asignaciones = true;
            include __DIR__ . '/seccion_4_listados.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'reportes':
        if ($rol_usuario === 'Gerencia' || $rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_5_panel_inferior.php';
        }
        break;

    case 'historial_rrhh':
    case 'ingreso':
    case 'salida':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Gerencia' || in_array('rrhh_historial', $permisos_usuario ?? [])) {
            $mostrar_listado_rrhh = true;
            include __DIR__ . '/seccion_4_listados.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'formularios_rrhh':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH') {
            include __DIR__ . '/seccion_formularios_rrhh.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'inventario':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_inventario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'registro_equipo':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_registro_equipo', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_registro_equipo.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'importar_inventario':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_importar_inventario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    // --- Módulo de Gestión de Personal (Ya definido al final del archivo) ---
    // (Bloque eliminado para evitar duplicados)

    case 'editar_activo_inventario':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('rrhh_inventario', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_editar_activo_inventario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'listados':
        $mostrar_listado_general = true;
        include __DIR__ . '/seccion_4_listados.php';
        break;

    case 'nuevo_ingreso':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin' || in_array('rrhh_altas', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_rrhh_ingreso.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'rrhh_menu':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin' || in_array('rrhh_altas', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_rrhh_menu.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'nueva_salida':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin' || in_array('rrhh_altas', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_rrhh_salida.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'generar_acta':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || $rol_usuario === 'Admin') {
            include __DIR__ . '/seccion_generar_acta.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'asignacion_equipo': // Alias para asignacion_equipos
    case 'asignacion_equipos':
        if ($rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || $rol_usuario === 'Admin' || in_array('rrhh_asignacion_equipos', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_asignacion_equipos.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'config':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('configuracion', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_configuracion.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'permisos':
        if ($rol_usuario === 'SuperAdmin' || in_array('gestion_permisos', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_gestion_permisos.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'categorias':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('categorias', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_categorias.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;







    case 'seguimiento':
        include __DIR__ . '/seccion_seguimiento_tickets.php';
        break;

    case 'colaboradores':
        include __DIR__ . '/seccion_colaboradores.php';
        break;

    // --- Módulo de Personal ---
    case 'personal':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('gestion_personal', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_personal_lista.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'personal_nuevo':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('gestion_personal', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_personal_formulario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'personal_detalle':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('gestion_personal', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_personal_detalle.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'personal_editar':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('gestion_personal', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_personal_editar.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'personal_importar':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH' || in_array('gestion_personal', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_personal_importar.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'generar_acta_entrega':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH') {
            include __DIR__ . '/seccion_generar_acta.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'reportes_nuevo':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Gerencia' || in_array('reportes', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_reportes.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'sucursales':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || in_array('gestion_sucursales', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_sucursales.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'cargos':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('cargos', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_cargos.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    // Caso historial_tecnico eliminado por duplicidad (ya definido arriba)


    // --- Módulo Cuentas 365 ---
    case 'registros_365':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('registros_365', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_registros_365_lista.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'registros_365_importar':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('registros_365', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_registros_365_importar.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'registros_365_formulario':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('registros_365', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_registros_365_formulario.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    // --- Módulo Mantenimiento ---
    case 'mantenimiento_equipos':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('mantenimiento_equipos', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_mantenimiento_equipos.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'mantenimiento_reporte':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('mantenimiento_equipos', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_mantenimiento_reporte.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    // --- Módulo Visualización IT ---
    case 'visualizacion_it':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico' || in_array('visualizacion_it', $permisos_usuario ?? [])) {
            include __DIR__ . '/seccion_visualizacion_it.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'estadisticas_globales':
        // Verificación basada en PERMISOS (DB) en lugar de roles duros
        if (in_array('estadisticas_globales', $permisos_usuario) || $rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin') {

            // --- LOGICA DE BACKEND PARA ESTADISTICAS ---
            $stats_kpis = [];
            $stats_charts = [];

            // 0. Captura de Filtros
            $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01', strtotime('-5 months'));
            $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $filtro_tecnico = $_GET['tecnico_id'] ?? ''; // ID del técnico seleccionado o vacío

            // Obtener lista de técnicos para el filtro (Solo Rol 'Tecnico')
            try {
                // Primero obtener ID del rol Tecnico
                $stmt_roles = $pdo->query("SELECT id FROM roles WHERE nombre = 'Tecnico'");
                $rol_tecnico_id = $stmt_roles->fetchColumn();

                if ($rol_tecnico_id) {
                    $stmt_tecs = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE rol_id = ?");
                    $stmt_tecs->execute([$rol_tecnico_id]);
                    $lista_tecnicos = $stmt_tecs->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $lista_tecnicos = [];
                }
            } catch (Exception $e) {
                // Fallback silencioso si falla carga lista
                $lista_tecnicos = [];
            }

            // Obtener lista de RRHH para el filtro
            try {
                // Primero obtener ID del rol RRHH
                $stmt_roles_rrhh = $pdo->query("SELECT id FROM roles WHERE nombre = 'RRHH'");
                $rol_rrhh_id = $stmt_roles_rrhh->fetchColumn();

                if ($rol_rrhh_id) {
                    $stmt_rrhh = $pdo->prepare("SELECT id, nombre_completo FROM usuarios WHERE rol_id = ?");
                    $stmt_rrhh->execute([$rol_rrhh_id]);
                    $lista_rrhh = $stmt_rrhh->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $lista_rrhh = [];
                }
            } catch (Exception $e) {
                $lista_rrhh = [];
            }

            $filtro_rrhh = $_GET['rrhh_id'] ?? ''; // ID del RRHH seleccionado


            try {
                // Construir cláusula WHERE dinámica y parámetros
                $where_base = "tickets.fecha_creacion BETWEEN ? AND ?";
                $params_base = [$fecha_inicio, $fecha_fin . ' 23:59:59'];

                $join_asignaciones = "";
                if ($filtro_tecnico) {
                    $join_asignaciones = "JOIN asignaciones a ON tickets.id = a.ticket_id";
                    $where_base .= " AND a.tecnico_id = ?";
                    $params_base[] = $filtro_tecnico;
                }

                if ($filtro_rrhh) {
                    $where_base .= " AND tickets.creador_id = ?";
                    $params_base[] = $filtro_rrhh;
                }

                // 1. KPIs Globales

                // Eficacia (Filtrada)
                // Siempre consultamos los tickets filtrados para los KPIs
                $sql_t = "SELECT tickets.estado FROM tickets $join_asignaciones WHERE $where_base";
                $stmt_t = $pdo->prepare($sql_t);
                $stmt_t->execute($params_base);
                $tickets_filtrados = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

                $total_t = count($tickets_filtrados);
                $resueltos_t = count(array_filter($tickets_filtrados, fn($t) => $t['estado'] === 'Completo'));
                $stats_kpis['eficacia'] = $total_t > 0 ? round(($resueltos_t / $total_t) * 100) : 0;

                // Abiertos
                $stats_kpis['abiertos'] = count(array_filter($tickets_filtrados, fn($t) => $t['estado'] === 'Pendiente'));

                // Inventario Total (No afectado por filtro técnico)
                $stmt = $pdo->query("SELECT COUNT(*) FROM inventario");
                $stats_kpis['total_activos'] = $stmt->fetchColumn();

                // Personal Activo (No afectado por filtro técnico)
                $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
                $stats_kpis['personal_activo'] = $stmt->fetchColumn();


                // 2. Gráfico Tendencia
                $sql_tendencia = "
                    SELECT DATE_FORMAT(tickets.fecha_creacion, '%Y-%m') as mes, COUNT(tickets.id) as cantidad 
                    FROM tickets 
                    $join_asignaciones
                    WHERE $where_base
                    GROUP BY DATE_FORMAT(tickets.fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC
                ";
                $stmt = $pdo->prepare($sql_tendencia);
                $stmt->execute($params_base);
                $tendencia_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

                // Rellenar meses vacíos
                $labels_tend = [];
                $data_tend = [];

                $start = new DateTime($fecha_inicio);
                $end = new DateTime($fecha_fin);
                $end->modify('first day of next month');
                $interval = DateInterval::createFromDateString('1 month');
                $period = new DatePeriod($start, $interval, $end);

                foreach ($period as $dt) {
                    $m = $dt->format("Y-m");
                    $labels_tend[] = $dt->format("M y");
                    $data_tend[] = $tendencia_raw[$m] ?? 0;
                }
                $stats_charts['tendencia'] = ['labels' => $labels_tend, 'data' => $data_tend];


                // 3. Gráfico Categorías
                $sql_cat = "
                    SELECT c.nombre, COUNT(tickets.id) as cantidad
                    FROM tickets 
                    JOIN categorias c ON tickets.categoria_id = c.id
                    $join_asignaciones
                    WHERE $where_base
                    GROUP BY c.nombre
                    ORDER BY cantidad DESC
                    LIMIT 6
                ";
                // Re-usar params base que ya tienen las fechas y el ID técnico si existe
                $stmt = $pdo->prepare($sql_cat);
                $stmt->execute($params_base);
                $cats_raw = $stmt->fetchAll();
                $stats_charts['categorias'] = [
                    'labels' => array_column($cats_raw, 'nombre'),
                    'data' => array_column($cats_raw, 'cantidad')
                ];


                // 4. Inventario (Tipos) - Sin cambios
                $stmt = $pdo->query("SELECT tipo, COUNT(*) as cant FROM inventario GROUP BY tipo ORDER BY cant DESC LIMIT 5");
                $inv_raw = $stmt->fetchAll();
                $stats_charts['inventario'] = [
                    'labels' => array_column($inv_raw, 'tipo'),
                    'data' => array_column($inv_raw, 'cant')
                ];


                // 5. RRHH (Ingresos vs Salidas) - Filtrado por fecha efectiva y Creador (RRHH)
                $sql_rrhh = "
                    SELECT 
                        DATE_FORMAT(
                            CASE 
                                WHEN tipo = 'Salida' THEN fecha_efectiva 
                                ELSE fecha_solicitud 
                            END, '%Y-%m') as mes,
                        SUM(CASE WHEN tipo = 'Ingreso' THEN 1 ELSE 0 END) as ingresos,
                        SUM(CASE WHEN tipo = 'Salida' THEN 1 ELSE 0 END) as salidas
                    FROM formularios_rrhh
                    WHERE 
                        (CASE 
                            WHEN tipo = 'Salida' THEN fecha_efectiva 
                            ELSE fecha_solicitud 
                        END) BETWEEN ? AND ?
                ";

                $params_rrhh = [$fecha_inicio, $fecha_fin . ' 23:59:59'];

                if ($filtro_rrhh) {
                    $sql_rrhh .= " AND creado_por = ?";
                    $params_rrhh[] = $filtro_rrhh;
                }

                $sql_rrhh .= " GROUP BY mes ORDER BY mes ASC";

                $stmt = $pdo->prepare($sql_rrhh);
                $stmt->execute($params_rrhh);
                $rrhh_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stats_charts['rrhh'] = [
                    'labels' => array_map(fn($r) => date('M', strtotime($r['mes'] . '-01')), $rrhh_raw),
                    'ingresos' => array_column($rrhh_raw, 'ingresos'),
                    'salidas' => array_column($rrhh_raw, 'salidas')
                ];

                // 6. Top Tecnicos (Global o Filtrado)
                // Usamos la consulta optimizada SQL para obtener el top
                $sql_top = "
                    SELECT a.tecnico_id, COUNT(tickets.id) as cantidad
                    FROM tickets
                    JOIN asignaciones a ON tickets.id = a.ticket_id
                    WHERE $where_base 
                    AND tickets.estado = 'Completo'
                    GROUP BY a.tecnico_id
                    ORDER BY cantidad DESC
                    LIMIT 5
                ";
                $stmt_top = $pdo->prepare($sql_top);
                $stmt_top->execute($params_base);
                $top_raw = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

                // Obtener nombres si no estan cargados
                if (!isset($usuarios)) {
                    $stmt_u = $pdo->query("SELECT id, nombre_completo as nombre FROM usuarios");
                    $usuarios = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
                }

                $nombres_tec = [];
                $datos_tec = [];

                if ($top_raw) {
                    foreach ($top_raw as $row) {
                        foreach ($usuarios as $u) {
                            if ($u['id'] == $row['tecnico_id']) {
                                $nombres_tec[] = explode(' ', $u['nombre'])[0];
                                $datos_tec[] = $row['cantidad'];
                                break;
                            }
                        }
                    }
                } else {
                    // Fallback para gráfico vacío
                    $nombres_tec = ['Sin datos'];
                    $datos_tec = [0];
                }

                $stats_charts['tecnicos'] = ['labels' => $nombres_tec, 'data' => $datos_tec];


            } catch (Exception $e) {
                // Log error para depuración
                error_log("Error en Estadísticas: " . $e->getMessage());
                echo "<div class='bg-red-100 text-red-700 p-4 rounded mb-4'>Error al cargar estadísticas: " . $e->getMessage() . "</div>";
            }

            include __DIR__ . '/seccion_estadisticas_globales.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'editar_rrhh':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'RRHH') {
            include __DIR__ . '/seccion_editar_rrhh.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;


    // Duplicates removed

    case 'notificaciones':
        include __DIR__ . '/seccion_notificaciones.php';
        break;

    default:
        echo "<div class='p-6'>Vista no encontrada.</div>";
        break;
}
?>

<script>
    // Manejar clicks en elementos con data-href (dashboard cards)
    document.addEventListener('DOMContentLoaded', function () {
        const clickableCards = document.querySelectorAll('.dashboard-card-clickable, [data-href]');

        clickableCards.forEach(card => {
            card.addEventListener('click', function (e) {
                const href = this.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/footer.php'; ?>