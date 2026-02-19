<?php
/**
 * Controlador de Mantenimiento
 * Maneja las acciones relacionadas con:
 * - Registrar Mantenimiento Individual
 * - Actualizar Mantenimiento
 * - Crear Solicitud Masiva (Programar Sede)
 * - Guardar Reporte Masivo (Ejecución de Visita)
 */



if (isset($_POST['accion']) && (strpos($_POST['accion'], '_mantenimiento') !== false || $_POST['accion'] === 'programar_masivo' || $_POST['accion'] === 'guardar_reporte_masivo' || $_POST['accion'] === 'crear_solicitud_masiva') && ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico')) {
    try {


        if ($_POST['accion'] === 'registrar_mantenimiento') {
            $equipo_id = $_POST['equipo_id'] ?? null;

            // [NEW] Lógica de Auto-Creación de Equipo
            if (empty($equipo_id) && !empty($_POST['nueva_marca']) && !empty($_POST['nuevo_modelo'])) {
                try {
                    $n_tipo = $_POST['nuevo_tipo'];
                    $n_marca = $_POST['nueva_marca'];
                    $n_modelo = $_POST['nuevo_modelo'];
                    $n_serial = $_POST['nuevo_serial'] ?: 'SN-AUTO-' . time(); // Fallback si no hay serial

                    // Insertar en Inventario
                    $stmt_inv = $pdo->prepare("INSERT INTO inventario (tipo, marca, modelo, serial, estado, condicion, fecha_registro) VALUES (?, ?, ?, ?, 'Buen Estado', 'Disponible', NOW())");
                    $stmt_inv->execute([$n_tipo, $n_marca, $n_modelo, $n_serial]);

                    $equipo_id = $pdo->lastInsertId();

                    if (!$equipo_id) {
                        throw new Exception("Error al registrar el nuevo equipo automáticamente.");
                    }
                } catch (Exception $e) {
                    $_SESSION['mensaje'] = "Error al registrar equipo nuevo: " . $e->getMessage();
                    $_SESSION['tipo_mensaje'] = "error";
                    header("Location: index.php?view=mantenimiento_equipos");
                    exit;
                }
            }

            if (empty($equipo_id)) {
                $_SESSION['mensaje'] = "Debe seleccionar un equipo o registrar uno nuevo.";
                $_SESSION['tipo_mensaje'] = "error";
                header("Location: index.php?view=mantenimiento_equipos");
                exit;
            }

            $tipo = $_POST['tipo_mantenimiento'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $problema = $_POST['descripcion_problema'];
            $prioridad = $_POST['prioridad'] ?? 'Media';
            $estado = $_POST['estado'] ?? 'Programado';
            $checklist = isset($_POST['checklist']) ? json_encode($_POST['checklist']) : null;

            $sql = "INSERT INTO mantenimiento_equipos (equipo_id, tipo_mantenimiento, fecha_inicio, descripcion_problema, prioridad, estado, registrado_por, checklist) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$equipo_id, $tipo, $fecha_inicio, $problema, $prioridad, $estado, $usuario_id, $checklist]);

            $_SESSION['mensaje'] = "Mantenimiento registrado correctamente." . (isset($n_marca) ? " (Equipo Nuevo Creado)" : "");
            $_SESSION['tipo_mensaje'] = "success";
            registrar_actividad("Registrar Mantenimiento", "Equipo ID: " . $equipo_id, $pdo);

        } elseif ($_POST['accion'] === 'actualizar_mantenimiento') {
            $id = $_POST['id'];
            $tipo = $_POST['tipo_mantenimiento'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'] ?? null;
            $problema = $_POST['descripcion_problema'];
            $solucion = $_POST['descripcion_solucion'] ?? '';
            $costo = $_POST['costo'] ?? 0;
            $prioridad = $_POST['prioridad'] ?? 'Media';
            $estado = $_POST['estado'];
            $checklist = isset($_POST['checklist']) ? json_encode($_POST['checklist']) : null;

            // Si se completa o cancela
            if (($estado === 'Completado' || $estado === 'Cancelado') && empty($fecha_fin)) {
                $fecha_fin = date('Y-m-d H:i:s');
            }

            $sql = "UPDATE mantenimiento_equipos SET tipo_mantenimiento=?, fecha_inicio=?, fecha_fin=?, descripcion_problema=?, descripcion_solucion=?, costo=?, prioridad=?, estado=?, checklist=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tipo, $fecha_inicio, $fecha_fin, $problema, $solucion, $costo, $prioridad, $estado, $checklist, $id]);

            $_SESSION['mensaje'] = "Mantenimiento actualizado.";
            $_SESSION['tipo_mensaje'] = "success";
            registrar_actividad("Actualizar Mantenimiento", "ID: " . $id, $pdo);

        } elseif ($_POST['accion'] === 'guardar_reporte_masivo') {

            $visita_id = $_POST['visita_id'];
            $equipos = $_POST['equipos']; // Array
            $fecha_hoy = date('Y-m-d');

            $count = 0;
            if (is_array($equipos)) {
                $sql_ins = "INSERT INTO mantenimiento_equipos (equipo_id, tipo_mantenimiento, fecha_inicio, fecha_fin, descripcion_problema, descripcion_solucion, estado, registrado_por, prioridad, proveedor) 
                            VALUES (?, 'Preventivo', ?, ?, ?, ?, ?, ?, 'Media', ?)";
                $stmt_ins = $pdo->prepare($sql_ins);

                foreach ($equipos as $eq) {
                    // Solo procesar si el checkbox "seleccionado" fue marcado
                    if (isset($eq['seleccionado'])) {
                        $estado_eq = $eq['estado'];
                        $notas = $eq['notas'];
                        $tecnico_row = $eq['tecnico_externo'] ?? null; // [NEW] Per row
                        $desc_prob = "Mantenimiento Masivo (Visita #$visita_id)";

                        // Determinar fecha fin
                        $fecha_fin = ($estado_eq == 'Completado') ? date('Y-m-d H:i:s') : null;

                        $stmt_ins->execute([$eq['id'], $fecha_hoy, $fecha_fin, $desc_prob, $notas, $estado_eq, $usuario_id, $tecnico_row]);
                        $count++;
                    }
                }
            }

            // Actualizar Visita
            $stmt_upd = $pdo->prepare("UPDATE mantenimiento_solicitudes SET estado = 'Completado' WHERE id = ?");
            $stmt_upd->execute([$visita_id]);

            $_SESSION['mensaje'] = "Visita finalizada. Se generaron $count reportes individuales en el historial.";
            $_SESSION['tipo_mensaje'] = "success";
            registrar_actividad("Finalizar Visita Masiva", "ID: $visita_id", $pdo);

            header("Location: index.php?view=mantenimiento_equipos&tab=visitas&print_report=$visita_id");
            exit;

        } elseif ($_POST['accion'] === 'crear_solicitud_masiva') {
            $sucursal_id = !empty($_POST['sucursal_id']) ? $_POST['sucursal_id'] : null;
            $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
            $fecha = $_POST['fecha_inicio'];

            // Obtener nombres para titulos bonitos
            if ($sucursal_id) {
                $stmt_suc = $pdo->prepare("SELECT nombre FROM sucursales WHERE id = ?");
                $stmt_suc->execute([$sucursal_id]);
                $nom = $stmt_suc->fetchColumn();
                $titulo = "Visita Mantenimiento: $nom";
            } else {
                $titulo = "Visita Mantenimiento: Empresa Global";
            }
            $desc = $_POST['descripcion_masiva'];

            $sql = "INSERT INTO mantenimiento_solicitudes (empresa_id, sucursal_id, fecha_programada, titulo, descripcion, asignado_a, estado) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
            $stmt = $pdo->prepare($sql);

            $stmt->execute([$empresa_id, $sucursal_id, $fecha, $titulo, $desc, $usuario_id]);

            $_SESSION['mensaje'] = "Solicitud de Visita creada. Gestiónala en la pestaña 'Visitas Programadas'.";
            $_SESSION['tipo_mensaje'] = "success";
            registrar_actividad("Crear Solicitud Visita", $titulo, $pdo);
        }

        header("Location: index.php?view=mantenimiento_equipos&tab=tickets");
        exit;

    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: index.php?view=mantenimiento_equipos&tab=tickets");
        exit;
    }
}
