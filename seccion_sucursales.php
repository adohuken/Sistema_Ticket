<?php
/**
 * seccion_sucursales.php - Gestión de Empresas y Sucursales
 * REESCRITURA COMPLETA: Solución robusta para redirección y validaciones.
 */

// 1. Lógica de Procesamiento (PHP)
$mensaje = "";
$tipo_mensaje = "";
$redirect_script = ""; // Script JS para redirección forzada si headers fallan

// Recuperar mensaje flash de sesión (PRG Pattern)
if (isset($_SESSION['flash_msg'])) {
    $mensaje = $_SESSION['flash_msg'];
    $tipo_mensaje = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}

// Procesar POST solo si hay acción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    // Validación CSRF Global para este módulo
    if (!validar_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_msg'] = "Error de seguridad: Token inválido. Intenta de nuevo.";
        $_SESSION['flash_type'] = "error";
        $should_redirect = true;
    } else {
        $should_redirect = true; // Por defecto redirigimos al final
        try {
            // --- ACCIÓN: CREAR EMPRESA ---
            if ($_POST['accion'] === 'crear_empresa') {
                if (empty($_POST['nombre']) || empty($_POST['codigo'])) {
                    throw new Exception("Nombre y Código son obligatorios.");
                }

                // Validar duplicados
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE nombre = ? OR codigo = ?");
                $stmt->execute([$_POST['nombre'], $_POST['codigo']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Ya existe una empresa con ese Nombre o Código.");
                }

                $stmt = $pdo->prepare("INSERT INTO empresas (nombre, codigo, pais, activa) VALUES (?, ?, ?, 1)");
                $stmt->execute([$_POST['nombre'], $_POST['codigo'], $_POST['pais'] ?? 'Global']);

                $_SESSION['flash_msg'] = "Empresa creada exitosamente.";
                $_SESSION['flash_type'] = "success";
            }

            // --- ACCIÓN: EDITAR EMPRESA ---
            elseif ($_POST['accion'] === 'editar_empresa') {
                if (empty($_POST['empresa_id']))
                    throw new Exception("ID de empresa no válido.");

                // Validar duplicados (excluyendo actual)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE (nombre = ? OR codigo = ?) AND id != ?");
                $stmt->execute([$_POST['nombre'], $_POST['codigo'], $_POST['empresa_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Ya existe otra empresa con ese Nombre o Código.");
                }

                $stmt = $pdo->prepare("UPDATE empresas SET nombre = ?, codigo = ?, pais = ? WHERE id = ?");
                $stmt->execute([$_POST['nombre'], $_POST['codigo'], $_POST['pais'], $_POST['empresa_id']]);

                $_SESSION['flash_msg'] = "Empresa actualizada correctamente.";
                $_SESSION['flash_type'] = "success";
            }

            // --- ACCIÓN: ELIMINAR EMPRESA ---
            elseif ($_POST['accion'] === 'eliminar_empresa') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM empresas WHERE id = ?");
                    $stmt->execute([$_POST['empresa_id']]);
                    $_SESSION['flash_msg'] = "Empresa eliminada.";
                    $_SESSION['flash_type'] = "success";
                } catch (PDOException $e) {
                    throw new Exception("No se puede eliminar: Tiene registros asociados.");
                }
            }

            // --- ACCIÓN: CREAR SUCURSAL ---
            elseif ($_POST['accion'] === 'crear_sucursal') {
                if (empty($_POST['empresa_id']))
                    throw new Exception("Selecciona una empresa.");

                // Validar duplicados en sucursal
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sucursales WHERE codigo = ? OR (nombre = ? AND empresa_id = ?)");
                $stmt->execute([$_POST['codigo'], $_POST['nombre'], $_POST['empresa_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Ya existe una sucursal con ese nombre o código en esta empresa.");
                }

                $stmt = $pdo->prepare("INSERT INTO sucursales (empresa_id, nombre, codigo, ciudad, pais, activa) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$_POST['empresa_id'], $_POST['nombre'], $_POST['codigo'], $_POST['ciudad'], $_POST['pais']]);

                $_SESSION['flash_msg'] = "Sucursal agregada exitosamente.";
                $_SESSION['flash_type'] = "success";
            }

            // --- ACCIÓN: EDITAR SUCURSAL ---
            elseif ($_POST['accion'] === 'editar_sucursal') {
                // Obtener empresa actual para validación scoped
                $stmt_emp = $pdo->prepare("SELECT empresa_id FROM sucursales WHERE id = ?");
                $stmt_emp->execute([$_POST['sucursal_id']]);
                $emp_id = $stmt_emp->fetchColumn();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sucursales WHERE (codigo = ? OR (nombre = ? AND empresa_id = ?)) AND id != ?");
                $stmt->execute([$_POST['codigo'], $_POST['nombre'], $emp_id, $_POST['sucursal_id']]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Conflicto: Nombre o Código duplicado.");
                }

                $activa = isset($_POST['activa']) ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE sucursales SET nombre = ?, codigo = ?, ciudad = ?, pais = ?, activa = ? WHERE id = ?");
                $stmt->execute([$_POST['nombre'], $_POST['codigo'], $_POST['ciudad'], $_POST['pais'], $activa, $_POST['sucursal_id']]);

                $_SESSION['flash_msg'] = "Sucursal actualizada.";
                $_SESSION['flash_type'] = "success";
            }

            // --- ACCIÓN: ELIMINAR SUCURSAL ---
            elseif ($_POST['accion'] === 'eliminar_sucursal') {
                $stmt = $pdo->prepare("DELETE FROM sucursales WHERE id = ?");
                $stmt->execute([$_POST['sucursal_id']]);
                $_SESSION['flash_msg'] = "Sucursal eliminada.";
                $_SESSION['flash_type'] = "success";
            }

        } catch (Exception $e) {
            // Manejo de error "suave" (Warning)
            if (strpos($e->getMessage(), 'Ya existe') !== false || strpos($e->getMessage(), 'Conflicto') !== false) {
                $_SESSION['flash_msg'] = $e->getMessage();
                $_SESSION['flash_type'] = "warning";
            } else {
                $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
                $_SESSION['flash_type'] = "error";
            }
        }
    }

    // --- REDIRECCIÓN ROBUSTA (PHP + JS Fallback) ---
    if ($should_redirect) {
        if (!headers_sent()) {
            header("Location: index.php?view=sucursales");
            exit;
        } else {
            $redirect_script = "<script>window.location.href='index.php?view=sucursales';</script>";
        }
    }
}

// 2. Obtención de Datos para la Vista
try {
    $empresas = $pdo->query("SELECT * FROM empresas ORDER BY nombre")->fetchAll();

    // Obtener estructura jerárquica
    $stmt = $pdo->query("SELECT e.id as emp_id, e.nombre as emp_nombre, e.codigo as emp_codigo, e.pais as emp_pais,
                                s.id as suc_id, s.nombre as suc_nombre, s.codigo as suc_codigo, s.ciudad as suc_ciudad, s.activa as suc_activa
                         FROM empresas e 
                         LEFT JOIN sucursales s ON e.id = s.empresa_id 
                         ORDER BY e.nombre, s.nombre");
    $flat_data = $stmt->fetchAll();

    $estructura = [];
    foreach ($flat_data as $row) {
        if (!isset($estructura[$row['emp_id']])) {
            $estructura[$row['emp_id']] = [
                'id' => $row['emp_id'],
                'nombre' => $row['emp_nombre'],
                'codigo' => $row['emp_codigo'],
                'pais' => $row['emp_pais'],
                'sucursales' => []
            ];
        }
        if ($row['suc_id']) {
            $estructura[$row['emp_id']]['sucursales'][] = [
                'id' => $row['suc_id'],
                'nombre' => $row['suc_nombre'],
                'codigo' => $row['suc_codigo'],
                'ciudad' => $row['suc_ciudad'],
                'activa' => $row['suc_activa']
            ];
        }
    }
} catch (PDOException $e) {
    echo "Error BD: " . $e->getMessage();
}
?>

<!-- 3. Redirección JS si PHP falló (Hack para views incluidas) -->
<?php if ($redirect_script)
    echo $redirect_script; ?>

<!-- 4. HTML / VISTA -->
<div class="p-6 ml-10 animate-fade-in-down">

    <!-- Header General -->
    <div
        class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span class="p-2 bg-blue-100 text-blue-600 rounded-lg"><i class="ri-building-2-line text-xl"></i></span>
                Gestión de Sucursales
            </h2>
            <p class="text-slate-500 text-sm mt-1 ml-12">Administra la estructura de tus empresas y sus sedes.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="document.getElementById('modal_nueva_empresa').showModal()"
                class="px-4 py-2 bg-white border border-blue-200 text-blue-600 rounded-xl hover:bg-blue-50 font-medium transition-colors flex items-center gap-2">
                <i class="ri-building-line"></i> Nueva Empresa
            </button>
            <button onclick="document.getElementById('modal_nueva_sucursal').showModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 font-medium transition-colors flex items-center gap-2">
                <i class="ri-add-line"></i> Nueva Sucursal
            </button>
        </div>
    </div>

    <!-- Alertas Flash -->
    <?php if ($mensaje): ?>
        <div
            class="mb-6 p-4 rounded-xl border-l-4 flex items-center gap-3 shadow-sm
            <?php echo $tipo_mensaje === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-500' :
                ($tipo_mensaje === 'warning' ? 'bg-amber-50 text-amber-800 border-amber-500' : 'bg-red-50 text-red-800 border-red-500'); ?>">
            <i
                class="text-xl <?php echo $tipo_mensaje === 'success' ? 'ri-check-line' : ($tipo_mensaje === 'warning' ? 'ri-alert-line' : 'ri-error-warning-line'); ?>"></i>
            <span class="font-medium"><?php echo htmlspecialchars($mensaje); ?></span>
        </div>
    <?php endif; ?>

    <!-- Grid de Empresas -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">
        <?php foreach ($estructura as $emp): ?>
            <div
                class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

                <!-- Card Header -->
                <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white relative">
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-10 h-10 bg-white border border-slate-200 rounded-lg flex items-center justify-center text-blue-600 text-xl shadow-sm">
                            <i class="ri-building-4-fill"></i>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                <?php echo htmlspecialchars($emp['codigo']); ?>
                            </span>
                            <button onclick='editarEmpresa(<?php echo json_encode($emp); ?>)'
                                class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                title="Editar">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <form method="POST"
                                onsubmit="return confirm('¿Eliminar empresa <?php echo htmlspecialchars($emp['nombre']); ?>?');">
                                <input type="hidden" name="accion" value="eliminar_empresa">
                                <input type="hidden" name="empresa_id" value="<?php echo $emp['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                                <button
                                    class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                                    title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <h3 class="font-bold text-lg text-slate-800 leading-tight mb-1">
                        <?php echo htmlspecialchars($emp['nombre']); ?></h3>
                    <p class="text-xs text-slate-500 flex items-center gap-1">
                        <i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($emp['pais'] ?: 'Global'); ?>
                        <span class="mx-1">•</span>
                        <?php echo count($emp['sucursales']); ?> sedes
                    </p>
                </div>

                <!-- Card Body: Sucursales -->
                <div class="p-4 bg-slate-50/50 flex-1 flex flex-col gap-3">
                    <?php if (empty($emp['sucursales'])): ?>
                        <div onclick="document.querySelector('#modal_nueva_sucursal select[name=empresa_id]').value='<?php echo $emp['id']; ?>'; document.getElementById('modal_nueva_sucursal').showModal()"
                            class="h-32 border-2 border-dashed border-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-400 cursor-pointer hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50/50 transition-all group">
                            <i class="ri-store-add-line text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                            <span class="text-xs font-medium">Agregar Primera Sede</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($emp['sucursales'] as $suc): ?>
                            <div
                                class="bg-white border border-slate-100 p-3 rounded-xl hover:border-blue-300 hover:shadow-sm transition-all group relative">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center">
                                            <i class="ri-store-2-line"></i>
                                        </div>
                                        <span
                                            class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full border-2 border-white <?php echo $suc['activa'] ? 'bg-emerald-500' : 'bg-slate-300'; ?>"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-semibold text-slate-700 truncate">
                                            <?php echo htmlspecialchars($suc['nombre']); ?></h4>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono mt-0.5">
                                            <span><?php echo htmlspecialchars($suc['codigo']); ?></span>
                                            <span>•</span>
                                            <span class="truncate sans-serif"><?php echo htmlspecialchars($suc['ciudad']); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                        <button onclick='editarSucursal(<?php echo json_encode($suc); ?>)'
                                            class="p-1.5 text-slate-400 hover:text-blue-600 bg-slate-50 hover:bg-blue-50 rounded-lg">
                                            <i class="ri-pencil-fill"></i>
                                        </button>
                                        <form method="POST"
                                            onsubmit="return confirm('¿Eliminar sede <?php echo htmlspecialchars($suc['nombre']); ?>?');">
                                            <input type="hidden" name="accion" value="eliminar_sucursal">
                                            <input type="hidden" name="sucursal_id" value="<?php echo $suc['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                                            <button
                                                class="p-1.5 text-slate-400 hover:text-red-600 bg-slate-50 hover:bg-red-50 rounded-lg">
                                                <i class="ri-delete-bin-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Mini boton agregar mas -->
                        <button
                            onclick="document.querySelector('#modal_nueva_sucursal select[name=empresa_id]').value='<?php echo $emp['id']; ?>'; document.getElementById('modal_nueva_sucursal').showModal()"
                            class="w-full py-2 border border-dashed border-slate-300 rounded-lg text-xs text-slate-500 hover:border-blue-400 hover:text-blue-600 transition-colors flex items-center justify-center gap-1">
                            <i class="ri-add-line"></i> Agregar otra sede
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ================= MODALES ================= -->

<!-- Modal Nueva Empresa -->
<dialog id="modal_nueva_empresa" class="modal rounded-2xl shadow-2xl p-0 w-full max-w-lg backdrop:bg-slate-900/40">
    <form method="POST" class="bg-white">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Nueva Empresa</h3>
            <button type="button" onclick="this.closest('dialog').close()"
                class="text-slate-400 hover:text-red-500 transition-colors"><i
                    class="ri-close-line text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" name="accion" value="crear_empresa">
            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre</label>
                <input type="text" name="nombre" required
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Código</label>
                    <input type="text" name="codigo" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono uppercase px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País</label>
                    <input type="text" name="pais" placeholder="Global"
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        <div class="p-4 bg-slate-50 flex justify-end gap-2 rounded-b-2xl border-t border-slate-100">
            <button type="button" onclick="this.closest('dialog').close()"
                class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Cancelar</button>
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-lg shadow-blue-500/30 transition-all">Crear
                Empresa</button>
        </div>
    </form>
</dialog>

<!-- Modal Editar Empresa -->
<dialog id="modal_editar_empresa" class="modal rounded-2xl shadow-2xl p-0 w-full max-w-lg backdrop:bg-slate-900/40">
    <form method="POST" class="bg-white">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Editar Empresa</h3>
            <button type="button" onclick="this.closest('dialog').close()"
                class="text-slate-400 hover:text-red-500 transition-colors"><i
                    class="ri-close-line text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" name="accion" value="editar_empresa">
            <input type="hidden" name="empresa_id" id="edit_emp_id">
            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre</label>
                <input type="text" name="nombre" id="edit_emp_nombre" required
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Código</label>
                    <input type="text" name="codigo" id="edit_emp_codigo" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono uppercase px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País</label>
                    <input type="text" name="pais" id="edit_emp_pais"
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        <div class="p-4 bg-slate-50 flex justify-end gap-2 rounded-b-2xl border-t border-slate-100">
            <button type="button" onclick="this.closest('dialog').close()"
                class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Cancelar</button>
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-lg shadow-blue-500/30 transition-all">Guardar
                Cambios</button>
        </div>
    </form>
</dialog>

<!-- Modal Nueva Sucursal -->
<dialog id="modal_nueva_sucursal" class="modal rounded-2xl shadow-2xl p-0 w-full max-w-lg backdrop:bg-slate-900/40">
    <form method="POST" class="bg-white">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Nueva Sucursal</h3>
            <button type="button" onclick="this.closest('dialog').close()"
                class="text-slate-400 hover:text-red-500 transition-colors"><i
                    class="ri-close-line text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" name="accion" value="crear_sucursal">
            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                <select name="empresa_id" required
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Seleccione...</option>
                    <?php foreach ($empresas as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre Sede</label>
                <input type="text" name="nombre" required placeholder="Ej: Oficina Central"
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Código</label>
                    <input type="text" name="codigo" required placeholder="Ej: SC-001"
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono uppercase px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ciudad</label>
                    <input type="text" name="ciudad" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País</label>
                <input type="text" name="pais" required
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>
        <div class="p-4 bg-slate-50 flex justify-end gap-2 rounded-b-2xl border-t border-slate-100">
            <button type="button" onclick="this.closest('dialog').close()"
                class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Cancelar</button>
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-lg shadow-blue-500/30 transition-all">Crear
                Sucursal</button>
        </div>
    </form>
</dialog>

<!-- Modal Editar Sucursal -->
<dialog id="modal_editar_sucursal" class="modal rounded-2xl shadow-2xl p-0 w-full max-w-lg backdrop:bg-slate-900/40">
    <form method="POST" class="bg-white">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Editar Sucursal</h3>
            <button type="button" onclick="this.closest('dialog').close()"
                class="text-slate-400 hover:text-red-500 transition-colors"><i
                    class="ri-close-line text-xl"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" name="accion" value="editar_sucursal">
            <input type="hidden" name="sucursal_id" id="edit_suc_id">
            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">

            <!-- Banner Info -->
            <div class="bg-blue-50 rounded-lg p-3 flex gap-3 border border-blue-100">
                <div class="text-blue-500"><i class="ri-information-line text-xl"></i></div>
                <div>
                    <h4 class="text-xs font-bold text-blue-800 uppercase">Modo Edición</h4>
                    <p class="text-xs text-blue-600">Cambiar la empresa propietaria requiere eliminar y re-crear la
                        sucursal.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre Sede</label>
                <input type="text" name="nombre" id="edit_suc_nombre" required
                    class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Código</label>
                    <input type="text" name="codigo" id="edit_suc_codigo" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm font-mono uppercase px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ciudad</label>
                    <input type="text" name="ciudad" id="edit_suc_ciudad" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País</label>
                    <input type="text" name="pais" id="edit_suc_pais" required
                        class="w-full border border-slate-300 bg-slate-50 rounded-lg text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="activa" id="edit_suc_activa"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-slate-700">Activa/Operativa</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="p-4 bg-slate-50 flex justify-end gap-2 rounded-b-2xl border-t border-slate-100">
            <button type="button" onclick="this.closest('dialog').close()"
                class="px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Cancelar</button>
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium shadow-lg shadow-blue-500/30 transition-all">Guardar
                Cambios</button>
        </div>
    </form>
</dialog>

<script>
    function editarEmpresa(data) {
        document.getElementById('edit_emp_id').value = data.id;
        document.getElementById('edit_emp_nombre').value = data.nombre;
        document.getElementById('edit_emp_codigo').value = data.codigo;
        document.getElementById('edit_emp_pais').value = data.pais || '';
        document.getElementById('modal_editar_empresa').showModal();
    }

    function editarSucursal(data) {
        document.getElementById('edit_suc_id').value = data.id;
        document.getElementById('edit_suc_nombre').value = data.nombre;
        document.getElementById('edit_suc_codigo').value = data.codigo;
        document.getElementById('edit_suc_ciudad').value = data.ciudad;
        document.getElementById('edit_suc_pais').value = data.pais;
        document.getElementById('edit_suc_activa').checked = (data.activa == 1);
        document.getElementById('modal_editar_sucursal').showModal();
    }
</script>