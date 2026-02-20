<?php
// AJAX HANDLERS
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'obtener_equipos_visita') {
    // Limpiar buffer y header JSON
    ob_end_clean();
    header('Content-Type: application/json');

    // Verificar permisos
    if (!in_array($rol_usuario, ['Admin', 'SuperAdmin', 'Tecnico'])) {
        echo json_encode(['error' => 'Sin permiso']);
        exit;
    }

    $empresa_id = $_GET['empresa_id'] ?? null;
    $sucursal_id = $_GET['sucursal_id'] ?? null;

    $sql = "SELECT i.id, i.tipo, i.marca, i.modelo, i.serial, 
            CONCAT(u.nombres, ' ', u.apellidos) as usuario_asignado,
            s.nombre as sucursal
            FROM inventario i 
            LEFT JOIN vista_personal_completo u ON i.asignado_a = u.id
            LEFT JOIN sucursales s ON u.sucursal_id = s.id 
            WHERE i.estado != 'Baja'";
    $params = [];

    if ($sucursal_id) {
        $sql .= " AND s.id = ?";
        $params[] = $sucursal_id;
    } elseif ($empresa_id) {
        $sql .= " AND s.empresa_id = ?";
        $params[] = $empresa_id;
    }

    // Solo equipos asignados? O todos? El usuario pidió "Pc asignadas"
    // Pero mejor mostramos todo lo que podamos vincular a la sede.

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error SQL: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * seccion_mantenimiento_equipos.php - Control de Mantenimiento Unificado
 */

// --- LÓGICA DE REPORTE TÉCNICO ---
// Filtros Reporte
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_sucursal = $_GET['sucursal'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Obtener listas
$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sucursales = $pdo->query("SELECT id, nombre, empresa_id FROM sucursales WHERE activa=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Query Inventario (Reporte)
$sql_inv = "
    SELECT i.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as usuario_asignado,
           u.sucursal_nombre,
           e.nombre as empresa_nombre
    FROM inventario i
    LEFT JOIN vista_personal_completo u ON i.asignado_a = u.id
    LEFT JOIN sucursales s ON u.sucursal_id = s.id
    LEFT JOIN empresas e ON s.empresa_id = e.id
    WHERE 1=1 -- Mostrar todo el inventario activo
";
$params_inv = [];

if ($filtro_empresa) {
    $sql_inv .= " AND e.id = ?";
    $params_inv[] = $filtro_empresa;
}
if ($filtro_sucursal) {
    $sql_inv .= " AND s.id = ?";
    $params_inv[] = $filtro_sucursal;
}
if ($busqueda) {
    $sql_inv .= " AND (i.serial LIKE ? OR i.modelo LIKE ? OR u.nombres LIKE ?)";
    $term = "%$busqueda%";
    $params_inv[] = $term;
    $params_inv[] = $term;
    $params_inv[] = $term;
}
$sql_inv .= " ORDER BY e.nombre, s.nombre, i.tipo";
$stmt_inv = $pdo->prepare($sql_inv);
$stmt_inv->execute($params_inv);
$equipos = $stmt_inv->fetchAll(PDO::FETCH_ASSOC);


// --- LÓGICA DE TICKETS DE MANTENIMIENTO ---
$filtro_estado = $_GET['estado'] ?? 'Todos';
$filtro_tipo = $_GET['tipo'] ?? '';
$busqueda_ticket = $_GET['busqueda_ticket'] ?? '';

$sql_mant = "
    SELECT m.*, 
           i.tipo as equipo_tipo, i.marca, i.modelo, i.serial,
           CONCAT(u.nombres, ' ', u.apellidos) as tecnico_nombre
    FROM mantenimiento_equipos m
    JOIN inventario i ON m.equipo_id = i.id
    LEFT JOIN vista_personal_completo u ON m.registrado_por = u.id
    WHERE 1=1
";
$params_mant = [];

// Excluir 'Preparacion' de la pestaña principal de Tickets
$sql_mant .= " AND m.tipo_mantenimiento != 'Preparacion'";

if ($filtro_estado && $filtro_estado !== 'Todos') {
    $sql_mant .= " AND m.estado = ?";
    $params_mant[] = $filtro_estado;
}
if ($filtro_tipo) {
    $sql_mant .= " AND m.tipo_mantenimiento = ?";
    $params_mant[] = $filtro_tipo;
}
if ($busqueda_ticket) {
    $sql_mant .= " AND (i.serial LIKE ? OR i.modelo LIKE ? OR u.nombres LIKE ?)";
    $term = "%$busqueda_ticket%";
    $params_mant[] = $term;
    $params_mant[] = $term;
    $params_mant[] = $term;
}
$sql_mant .= " ORDER BY m.fecha_inicio DESC";
$stmt_mant = $pdo->prepare($sql_mant);
$stmt_mant->execute($params_mant);
$mantenimientos = $stmt_mant->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas rápidas (Tickets)
$stmt_stats = $pdo->query("
    SELECT 
        SUM(CASE WHEN estado = 'En Proceso' AND tipo_mantenimiento != 'Preparacion' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN estado = 'Programado' AND tipo_mantenimiento != 'Preparacion' THEN 1 ELSE 0 END) as programados,
        SUM(CASE WHEN estado = 'Completado' AND MONTH(fecha_fin) = MONTH(CURRENT_DATE()) AND tipo_mantenimiento != 'Preparacion' THEN 1 ELSE 0 END) as completados_mes,
        SUM(CASE WHEN tipo_mantenimiento = 'Preparacion' THEN 1 ELSE 0 END) as total_ingresos
    FROM mantenimiento_equipos
");
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC) ?: ['en_proceso' => 0, 'programados' => 0, 'completados_mes' => 0, 'total_ingresos' => 0];

// Pestaña Activa
$active_tab = $_GET['tab'] ?? 'reporte'; // 'reporte', 'tickets', 'ingresos'
?>

<div class="p-6 flex-1 bg-slate-50 min-h-screen">
    <div class="max-w-[1920px] mx-auto space-y-6">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                    <span class="bg-amber-600 text-white p-3 rounded-xl shadow-lg shadow-amber-500/30">
                        <i class="ri-tools-fill"></i>
                    </span>
                    Control de Mantenimiento
                </h1>
                <p class="text-slate-500 mt-2">Gestión integral de activos y servicios</p>
            </div>

            <div class="flex gap-3">
                <button onclick="abrirModalMasivo()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-xl font-semibold shadow-lg shadow-indigo-500/30 transition-all flex items-center gap-2">
                    <i class="ri-building-line text-xl"></i>
                    Programar Sede
                </button>
                <button onclick="abrirModalMantenimiento()"
                    class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-amber-500/30 transition-all flex items-center gap-2">
                    <i class="ri-add-line text-xl"></i>
                    Registrar Servicio / Ingreso
                </button>
            </div>
        </div>

        <!-- TABS Navigation -->
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <a href="index.php?view=mantenimiento_equipos&tab=visitas"
                    class="<?= $active_tab === 'visitas' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="ri-calendar-check-line text-lg"></i>
                    Visitas Programadas
                </a>

                <a href="index.php?view=mantenimiento_equipos&tab=reporte"
                    class="<?= $active_tab === 'reporte' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="ri-file-list-3-line text-lg"></i>
                    Cargas de Trabajo (Reporte Técnico)
                </a>

                <a href="index.php?view=mantenimiento_equipos&tab=tickets"
                    class="<?= $active_tab === 'tickets' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="ri-history-line text-lg"></i>
                    Historial de Reparaciones
                    <span
                        class="bg-slate-100 text-slate-600 py-0.5 px-2.5 rounded-full text-xs ml-2"><?= $stats['en_proceso'] + $stats['programados'] ?></span>
                </a>

                <a href="index.php?view=mantenimiento_equipos&tab=ingresos"
                    class="<?= $active_tab === 'ingresos' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                    <i class="ri-login-circle-line text-lg"></i>
                    Bitácora de Ingresos
                    <span
                        class="bg-indigo-100 text-indigo-600 py-0.5 px-2.5 rounded-full text-xs ml-2"><?= $stats['total_ingresos'] ?></span>
                </a>
            </nav>
        </div>

        <!-- CONTENIDO TABS -->

        <!-- TAB VISITAS -->
        <div id="tab-visitas" class="<?= $active_tab === 'visitas' ? 'block' : 'hidden' ?>">
            <?php
            // Fetch Visitas Logic
            $sql_visitas = "SELECT v.*, e.nombre as empresa_nombre, s.nombre as sucursal_nombre, 
                            CONCAT(u.nombres, ' ', u.apellidos) as tecnico
                            FROM mantenimiento_solicitudes v
                            LEFT JOIN empresas e ON v.empresa_id = e.id
                            LEFT JOIN sucursales s ON v.sucursal_id = s.id
                            LEFT JOIN vista_personal_completo u ON v.asignado_a = u.id
                            ORDER BY v.fecha_programada DESC";
            $visitas = $pdo->query($sql_visitas)->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-slate-700">Visitas de Mantenimiento Masivo</h3>
                    <button onclick="abrirModalMasivo()"
                        class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded font-bold hover:bg-indigo-200">
                        + Nueva Solicitud
                    </button>
                </div>
                <table class="w-full text-sm text-left text-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Fecha</th>
                            <th class="px-6 py-3">Sede / Título</th>
                            <th class="px-6 py-3">Asignado a</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visitas)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center">No hay visitas programadas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visitas as $bi): ?>
                                <tr class="bg-white border-b hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium"><?= date('d/m/Y', strtotime($bi['fecha_programada'])) ?>
                                    </td>
                                    <td class="px-6 py-4 relative">
                                        <div class="font-bold text-slate-800"><?= htmlspecialchars($bi['titulo']) ?></div>
                                        <div class="text-xs text-slate-500">
                                            <?= htmlspecialchars($bi['empresa_nombre'] . ($bi['sucursal_nombre'] ? " - " . $bi['sucursal_nombre'] : "")) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($bi['tecnico']) ?></td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-2 py-1 rounded text-xs font-bold 
                                            <?= $bi['estado'] == 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : ($bi['estado'] == 'Completado' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') ?>">
                                            <?= $bi['estado'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <?php if ($bi['estado'] != 'Completado'): ?>
                                                <button
                                                    onclick="ejecutarVisita(<?= $bi['id'] ?>, '<?= $bi['empresa_id'] ?>', '<?= $bi['sucursal_id'] ?>', '<?= htmlspecialchars($bi['titulo']) ?>')"
                                                    class="text-indigo-600 hover:text-indigo-900 font-bold border border-indigo-200 px-3 py-1 rounded hover:bg-indigo-50 transition-colors"
                                                    title="Ejecutar Visita">
                                                    <i class="ri-play-circle-line align-middle"></i> Ejecutar
                                                </button>
                                            <?php endif; ?>

                                            <a href="imprimir_reporte_visita.php?id=<?= $bi['id'] ?>" target="_blank"
                                                class="text-slate-500 hover:text-indigo-600 border border-slate-200 p-1.5 rounded-lg hover:bg-slate-50 transition-colors"
                                                title="Ver Reporte">
                                                <i class="ri-printer-line text-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 1: REPORTE TÉCNICO -->
        <div id="tab-reporte" class="<?= $active_tab === 'reporte' ? 'block' : 'hidden' ?>">

            <!-- Filtros Reporte -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="view" value="mantenimiento_equipos">
                    <input type="hidden" name="tab" value="reporte">

                    <div class="w-full md:w-64">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                        <select name="empresa" onchange="this.form.submit()"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none bg-white text-sm">
                            <option value="">Todas las Empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $filtro_empresa == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-full md:w-64">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Búsqueda</label>
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm"
                            placeholder="Serial, Modelo, Usuario...">
                    </div>

                    <button type="submit"
                        class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-900 transition-colors text-sm font-medium">
                        Filtrar
                    </button>
                    <button type="button" onclick="exportarReporte()"
                        class="ml-auto text-emerald-600 hover:text-emerald-700 font-medium text-sm flex items-center gap-1">
                        <i class="ri-file-excel-2-line"></i> Exportar
                    </button>
                </form>
            </div>

            <!-- Tabla Reporte -->
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="tabla-reporte">
                        <thead class="bg-sky-600 text-white">
                            <tr>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    Asignación</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    Equipo</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    Marca/Modelo</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30 max-w-[100px]">
                                    Serie</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    Procesador</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    HDD/SSD</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    RAM</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    Antivirus</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    OneDrive</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    BackUp</th>
                                <th
                                    class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                    ScreenConnect</th>
                                <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            <?php if (empty($equipos)): ?>
                                <tr>
                                    <td colspan="12" class="p-8 text-center text-slate-500">No se encontraron equipos.</td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $current_group = null;
                                foreach ($equipos as $eq):
                                    $emp_name = $eq['empresa_nombre'] ?? 'Sin Empresa';
                                    $suc_name = $eq['sucursal_nombre'] ?? 'Sin Sucursal (Global/No Asignado)';
                                    $group_key = "$emp_name - $suc_name";

                                    if ($group_key !== $current_group):
                                        $current_group = $group_key;
                                        ?>
                                        <!-- Fila de Agrupación (Sucursal) -->
                                        <tr class="bg-slate-50 border-y-2 border-slate-200">
                                            <td colspan="12"
                                                class="px-4 py-3 text-sm font-bold text-slate-700 uppercase tracking-wider">
                                                <div class="flex items-center gap-2">
                                                    <i class="ri-building-2-line text-amber-600"></i>
                                                    <?= htmlspecialchars($group_key) ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <tr
                                        class="hover:bg-blue-50/50 group transition-colors border-b border-slate-50 last:border-0">
                                        <!-- ... resto de la fila ... -->
                                    <tr class="hover:bg-blue-50/50 group transition-colors">
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?php if ($eq['usuario_asignado']): ?>
                                                <div class="font-bold text-blue-700"><?= htmlspecialchars($eq['empresa_nombre']) ?>
                                                </div>
                                                <div class="text-[10px] text-slate-500">
                                                    <?= htmlspecialchars($eq['sucursal_nombre']) ?>
                                                </div>
                                                <div class="mt-0.5 text-slate-800 font-medium">
                                                    <?= htmlspecialchars($eq['usuario_asignado']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic">Sin Asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 font-bold align-middle">
                                            <?= htmlspecialchars($eq['tipo']) ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <div class="font-semibold"><?= htmlspecialchars($eq['marca']) ?></div>
                                            <div class="text-slate-500"><?= htmlspecialchars($eq['modelo']) ?></div>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 font-mono text-[10px] align-middle">
                                            <?= htmlspecialchars($eq['serial']) ?>
                                        </td>

                                        <!-- Specs -->
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['procesador'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['disco_duro'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['ram'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['antivirus'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['onedrive'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['backup_status'] ?: '---') ?>
                                        </td>
                                        <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                            <?= htmlspecialchars($eq['screenconnect'] ?: '---') ?>
                                        </td>

                                        <td class="px-3 py-2 align-middle text-center flex justify-center gap-2">
                                            <button onclick='editarSpecs(<?= json_encode($eq) ?>)'
                                                class="w-8 h-8 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-300 transition-colors shadow-sm"
                                                title="Editar Especificaciones">
                                                <i class="ri-edit-2-line"></i>
                                            </button>
                                            <button onclick='prepararMantenimiento(<?= json_encode($eq) ?>)'
                                                class="w-8 h-8 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-400 hover:text-amber-600 hover:border-amber-300 transition-colors shadow-sm"
                                                title="Registrar Mantenimiento">
                                                <i class="ri-tools-line"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: TICKETS DE REPARACIÓN -->
        <div id="tab-tickets" class="<?= $active_tab === 'tickets' ? 'block' : 'hidden' ?>">

            <!-- Stats (Solo visibles en esta tab) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
                        <i class="ri-loader-4-line"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['en_proceso'] ?></h3>
                        <p class="text-sm text-slate-500">En Proceso</p>
                    </div>
                </div>
                <!-- ...otros stats... -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">
                        <i class="ri-calendar-event-line"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['programados'] ?></h3>
                        <p class="text-sm text-slate-500">Programados</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-2xl">
                        <i class="ri-checkbox-circle-line"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['completados_mes'] ?></h3>
                        <p class="text-sm text-slate-500">Completados este Mes</p>
                    </div>
                </div>
            </div>

            <!-- Filtros Tickets -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-6">
                <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="view" value="mantenimiento_equipos">
                    <input type="hidden" name="tab" value="tickets">

                    <div class="w-48">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado</label>
                        <select name="estado"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none bg-white">
                            <option value="Todos" <?= $filtro_estado == 'Todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="En Proceso" <?= $filtro_estado == 'En Proceso' ? 'selected' : '' ?>>En Proceso
                            </option>
                            <option value="Programado" <?= $filtro_estado == 'Programado' ? 'selected' : '' ?>>Programado
                            </option>
                            </option>
                        </select>
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo</label>
                        <select name="tipo"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none bg-white">
                            <option value="">Todos</option>
                            <option value="Preventivo" <?= $filtro_tipo == 'Preventivo' ? 'selected' : '' ?>>Preventivo
                            </option>
                            <option value="Correctivo" <?= $filtro_tipo == 'Correctivo' ? 'selected' : '' ?>>Correctivo
                            </option>
                            <option value="Upgrade" <?= $filtro_tipo == 'Upgrade' ? 'selected' : '' ?>>Upgrade</option>
                            </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Búsqueda</label>
                        <input type="text" name="busqueda_ticket" value="<?= htmlspecialchars($busqueda_ticket) ?>"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none"
                            placeholder="Serial, Modelo, Técnico...">
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition-colors">
                        <i class="ri-search-line mr-2"></i>Filtrar
                    </button>
                </form>
            </div>

            <!-- Tabla Tickets -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Equipo</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Problema</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($mantenimientos)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">No hay tickets.</td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($mantenimientos as $m): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="font-semibold text-slate-800">
                                                        <?= htmlspecialchars($m['equipo_tipo'] . ' ' . $m['marca']) ?>
                                                    </div>
                                                    <code
                                                        class="text-xs bg-slate-100 px-1 rounded"><?= htmlspecialchars($m['serial']) ?></code>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-slate-700">
                                                        <?= htmlspecialchars($m['tipo_mantenimiento']) ?>
                                                    </div>
                                                    <div class="text-xs text-slate-500">
                                                        <?= htmlspecialchars($m['descripcion_problema']) ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span
                                                        class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700"><?= $m['estado'] ?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <button onclick='editarMantenimiento(<?= json_encode($m) ?>)'
                                                        class="text-amber-600 hover:text-amber-800 font-medium">Editar</button>
                                                    <button type="button" onclick='verDetallesMantenimiento(<?= json_encode($m) ?>)'
                                                        class="ml-2 text-blue-600 hover:text-blue-800 font-medium" title="Ver Detalles">
                                                        <i class="ri-eye-line text-lg align-bottom"></i>
                                                    </button>
                                                </td>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: BITÁCORA DE INGRESOS (Preparación/Inicialización) -->
        <div id="tab-ingresos" class="<?= $active_tab === 'ingresos' ? 'block' : 'hidden' ?>">
             <?php
             // Lógica específica para esta pestaña
             $filtro_estado_ingreso = $_GET['estado_ingreso'] ?? 'Todos';
             $busqueda_ingreso = $_GET['busqueda_ingreso'] ?? '';

             $sql_ingresos = "
                SELECT m.*, 
                       i.tipo as equipo_tipo, i.marca, i.modelo, i.serial,
                       CONCAT(u.nombres, ' ', u.apellidos) as tecnico_nombre
                FROM mantenimiento_equipos m
                JOIN inventario i ON m.equipo_id = i.id
                LEFT JOIN vista_personal_completo u ON m.registrado_por = u.id
                WHERE m.tipo_mantenimiento = 'Preparacion'
            ";
             $params_ingresos = [];

             if ($filtro_estado_ingreso && $filtro_estado_ingreso !== 'Todos') {
                 $sql_ingresos .= " AND m.estado = ?";
                 $params_ingresos[] = $filtro_estado_ingreso;
             }
             if ($busqueda_ingreso) {
                 $sql_ingresos .= " AND (i.serial LIKE ? OR i.modelo LIKE ? OR u.nombres LIKE ?)";
                 $term = "%$busqueda_ingreso%";
                 $params_ingresos[] = $term;
                 $params_ingresos[] = $term;
                 $params_ingresos[] = $term;
             }
             $sql_ingresos .= " ORDER BY m.fecha_inicio DESC";
             $stmt_ingresos = $pdo->prepare($sql_ingresos);
             $stmt_ingresos->execute($params_ingresos);
             $ingresos = $stmt_ingresos->fetchAll(PDO::FETCH_ASSOC);
             ?>

            <!-- Filtros Ingresos -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-6">
                 <div class="flex items-center gap-3 mb-4 p-3 bg-indigo-50 rounded-lg border border-indigo-100 text-indigo-800">
                    <i class="ri-information-fill text-xl"></i>
                    <div>
                        <p class="font-bold text-sm">Bitácora de Ingresos y Preparación</p>
                        <p class="text-xs opacity-80">Aquí se muestran exclusivamente los equipos que ingresan para configuración inicial, formateo o preparación.</p>
                    </div>
                </div>

                <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                    <input type="hidden" name="view" value="mantenimiento_equipos">
                    <input type="hidden" name="tab" value="ingresos">

                    <div class="w-48">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado</label>
                        <select name="estado_ingreso"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="Todos" <?= $filtro_estado_ingreso == 'Todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="En Proceso" <?= $filtro_estado_ingreso == 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="Programado" <?= $filtro_estado_ingreso == 'Programado' ? 'selected' : '' ?>>Programado</option>
                            <option value="Completado" <?= $filtro_estado_ingreso == 'Completado' ? 'selected' : '' ?>>Completado</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Búsqueda</label>
                        <input type="text" name="busqueda_ingreso" value="<?= htmlspecialchars($busqueda_ingreso) ?>"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                            placeholder="Serial, Modelo...">
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30">
                        <i class="ri-search-line mr-2"></i>Filtrar
                    </button>
                </form>
            </div>

            <!-- Tabla Ingresos -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-indigo-50 border-b border-indigo-100 text-indigo-900">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Equipo / Activo</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Fecha Ingreso</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                             <?php if (empty($ingresos)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">No hay registros de ingresos.</td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($ingresos as $ing): ?>
                                            <tr class="hover:bg-slate-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="font-bold text-slate-800">
                                                        <?= htmlspecialchars($ing['equipo_tipo'] . ' ' . $ing['marca']) ?>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-mono border border-indigo-200"><?= htmlspecialchars($ing['serial']) ?></span>
                                                        <span class="text-xs text-slate-500"><?= htmlspecialchars($ing['modelo']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-medium text-slate-700">
                                                        <?= date('d/m/Y', strtotime($ing['fecha_inicio'])) ?>
                                                    </div>
                                                    <div class="text-xs text-slate-400">
                                                        <?= date('H:i A', strtotime($ing['fecha_inicio'])) ?>
                                                    </div>
                                                </td>
                                                 <td class="px-6 py-4">
                                                    <span class="px-2 py-1 rounded-full text-xs font-bold 
                                                <?= $ing['estado'] == 'En Proceso' ? 'bg-amber-100 text-amber-700' : ($ing['estado'] == 'Completado' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') ?>">
                                                        <?= $ing['estado'] ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                     <button onclick='editarMantenimiento(<?= json_encode($ing) ?>)'
                                                        class="text-indigo-600 hover:text-indigo-800 font-medium">Editar</button>
                                                    <button type="button" onclick='verDetallesMantenimiento(<?= json_encode($ing) ?>)'
                                                        class="ml-2 text-slate-500 hover:text-indigo-600 font-medium" title="Ver Detalles">
                                                        <i class="ri-eye-line text-lg align-bottom"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Mantenimiento (El mismo de antes) -->
<div id="modal-mantenimiento"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <!-- ... (Contenido del modal, simplificado para brevity en este tool call, pero en el archivo real debe ir completo) ... -->
    <!-- Copiaré el modal completo en la escritura real, aquí solo indico estructura -->
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[85vh] overflow-hidden">
        <div class="bg-amber-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h3 class="text-xl font-bold text-white flex items-center gap-2" id="modal-titulo">
                <i class="ri-tools-fill"></i> Registrar Mantenimiento
            </h3>
            <button onclick="cerrarModalMantenimiento()" class="text-white/80 hover:text-white transition-colors">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <div class="overflow-y-auto p-6 flex-grow">
            <form id="form-mantenimiento" method="POST" action="index.php" class="space-y-4"
                onsubmit="guardarMantenimiento(event)">
                <input type="hidden" name="accion" id="form-accion" value="registrar_mantenimiento">
                <input type="hidden" name="view" value="mantenimiento_equipos"> <!-- Se queda en la misma view -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="id" id="mantenimiento-id">

                <div id="div-seleccion-equipo" class="bg-slate-50 p-4 rounded-xl border-2 border-slate-200">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Equipo <span
                            class="text-red-500">*</span></label>
                    <?php
                    // Query mejorado para incluir asignación
                    $stmt_eq = $pdo->query("
                        SELECT i.id, i.tipo, i.marca, i.modelo, i.serial, 
                               CONCAT(u.nombres, ' ', u.apellidos) as usuario
                        FROM inventario i
                        LEFT JOIN vista_personal_completo u ON i.asignado_a = u.id
                        WHERE i.estado != 'Baja'
                        ORDER BY u.nombres, i.tipo
                    ");
                    $equipos_list = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <!-- Toggle: Buscar vs Nuevo -->
                    <div class="flex items-center justify-between mb-3 bg-slate-100 p-1 rounded-lg">
                        <button type="button" id="btn-modo-buscar" onclick="toggleModoEquipo('buscar')"
                            class="flex-1 px-3 py-1.5 rounded-md text-sm font-bold transition-all bg-white text-indigo-600 shadow-sm">
                            <i class="ri-search-line"></i> Buscar Existente
                        </button>
                        <button type="button" id="btn-modo-nuevo" onclick="toggleModoEquipo('nuevo')"
                            class="flex-1 px-3 py-1.5 rounded-md text-sm font-bold text-slate-500 hover:text-indigo-600 transition-all">
                            <i class="ri-add-circle-line"></i> Registrar Nuevo
                        </button>
                    </div>

                    <!-- MODO BUSCAR -->
                    <div id="modo-buscar-container">
                        <div class="relative mb-2">
                            <input type="text" id="filtro_equipo_input" onkeyup="filtrarEquipos()"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none"
                                placeholder="🔍 Buscar por usuario, serie o modelo...">
                            <div
                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i class="ri-search-line"></i>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mb-2 px-1">
                            <label
                                class="flex items-center gap-2 text-xs font-bold text-slate-500 cursor-pointer select-none">
                                <input type="checkbox" id="check_sin_asignar" onchange="filtrarEquipos()"
                                    class="rounded text-amber-600 focus:ring-amber-500">
                                Mostrar solo Sin Asignar
                            </label>
                        </div>

                        <select name="equipo_id" id="equipo_id"
                            class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg outline-none cursor-pointer">
                            <option value="">Seleccionar equipo...</option>
                            <?php foreach ($equipos_list as $eq):
                                $es_asignado = !empty($eq['usuario']);
                                $texto = ($es_asignado ? $eq['usuario'] : 'Sin Asignar') . ' | ' . $eq['tipo'] . ' ' . $eq['marca'] . ' (' . $eq['serial'] . ')';
                                ?>
                                    <option value="<?= $eq['id'] ?>" data-asignado="<?= $es_asignado ? '1' : '0' ?>">
                                        <?= htmlspecialchars($texto) ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- MODO NUEVO -->
                    <div id="modo-nuevo-container" class="hidden space-y-3 animate-fade-in-down">
                        <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-lg mb-2">
                            <p class="text-xs text-indigo-700 flex items-center gap-2">
                                <i class="ri-information-fill"></i>
                                Se creará un nuevo activo en inventario automáticamente.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tipo <span
                                        class="text-red-500">*</span></label>
                                <select name="nuevo_tipo" id="nuevo_tipo"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="Laptop">Laptop</option>
                                    <option value="PC">PC Escritorio</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Movil">Celular/Tablet</option>
                                    <option value="Impresora">Impresora</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Marca <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="nueva_marca" id="nueva_marca" placeholder="Ej: Dell"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Modelo <span
                                        class="text-red-500">*</span></label>
                                <input type="text" name="nuevo_modelo" id="nuevo_modelo" placeholder="Ej: Latitude 5420"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Serial <span
                                        class="text-slate-400 font-normal">(Auto si vacío)</span></label>
                                <input type="text" name="nuevo_serial" id="nuevo_serial" placeholder="SN..."
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 font-mono">
                            </div>
                        </div>
                    </div>
                </div>
                <div id="div-info-equipo" class="hidden bg-blue-50 p-4 rounded-xl border-2 border-blue-200">
                    <span id="info-equipo-texto"></span>
                </div>

                <!-- Inputs básicos -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tipo</label>
                        <select name="tipo_mantenimiento" id="tipo_mantenimiento"
                            class="w-full px-4 py-2 border rounded">
                            <option value="Preventivo">Preventivo</option>
                            <option value="Correctivo">Correctivo</option>
                            <option value="Upgrade">Upgrade</option>
                            <option value="Preparacion">Preparación / Inicialización</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Estado</label>
                        <select name="estado" id="estado" class="w-full px-4 py-2 border rounded">
                            <option value="Programado">Programado</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completado">Completado</option>
                        </select>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= date('Y-m-d') ?>"
                            class="w-full px-4 py-2 border rounded">
                    </div>
                </div>

                <!-- Checklist de Mantenimiento -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <label class="block text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                        <i class="ri-list-check text-amber-600"></i> Protocolo de Servicio
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[limpieza_fisica]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Limpieza Física / Interna
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[actualizacion_so]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Actualización S.O.
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[antivirus]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Antivirus / Seguridad
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[optimizacion]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Optimización Disco / Temporales
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[cables]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Revisión Cables / Periféricos
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors">
                            <input type="checkbox" name="checklist[backup]"
                                class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            Verificación Backup
                        </label>

                        <!-- Nuevos para Preparación -->
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors text-indigo-700 font-medium">
                            <input type="checkbox" name="checklist[formateo]"
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            Formateo / Wiping
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors text-indigo-700 font-medium">
                            <input type="checkbox" name="checklist[instalacion_so]"
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            Instalación S.O.
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer hover:bg-white p-2 rounded transition-colors text-indigo-700 font-medium">
                            <input type="checkbox" name="checklist[configuracion_inicial]"
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                            Configuración Inicial (Apps/Correo)
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-sm font-bold text-slate-700">Comentarios / Diagnóstico</label>
                    <textarea name="descripcion_problema" id="descripcion_problema" required
                        class="w-full px-4 py-2 border rounded" rows="3"
                        placeholder="Detalles específicos del servicio..."></textarea>
                </div>

            </form>
        </div>
        <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-200 flex-shrink-0">
            <button type="button" onclick="cerrarModalMantenimiento()"
                class="px-4 py-2 border rounded hover:bg-white transition-colors">Cancelar</button>
            <button type="submit" form="form-mantenimiento"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded shadow-lg shadow-amber-500/30 transition-all">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Edición Specs (El nuevo modal) -->
<div id="modalSpecs" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="cerrarModalSpecs()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all w-full max-w-lg">
                <form id="formSpecs" onsubmit="guardarSpecs(event)">
                    <input type="hidden" name="ajax_action" value="guardar_specs">
                    <!-- Fix action name matching index.php -->
                    <input type="hidden" name="id" id="edit_id">

                    <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2"><i class="ri-cpu-line"></i>
                            Ficha Técnica</h3>
                        <button type="button" onclick="cerrarModalSpecs()" class="text-white/80 hover:text-white"><i
                                class="ri-close-line text-2xl"></i></button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mb-4">
                            <h4 class="font-bold text-blue-900 text-sm" id="edit_titulo">Equipo</h4>
                            <p class="text-xs text-blue-700" id="edit_subtitulo">SN: ...</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">Procesador</label><input
                                    type="text" name="procesador" id="edit_procesador"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">RAM</label><input
                                    type="text" name="ram" id="edit_ram"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div class="col-span-2"><label class="block text-xs font-bold text-slate-500 mb-1">Disco
                                    Duro</label><input type="text" name="disco_duro" id="edit_disco_duro"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-1">Antivirus</label><input
                                    type="text" name="antivirus" id="edit_antivirus"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">OneDrive</label>
                                <select name="onedrive" id="edit_onedrive"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Activo">Activo</option>
                                    <option value="No Configurado">No Configurado</option>
                                    <option value="No Aplica">No Aplica</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">BackUp</label>
                                <select name="backup_status" id="edit_backup"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Si">Si</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">ScreenConnect</label>
                                <select name="screenconnect" id="edit_screenconnect"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="No Instalado">No Instalado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-2">
                        <button type="button" onclick="cerrarModalSpecs()"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md transition-colors">Guardar
                            Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal Programación Masiva -->
<div id="modalMasivo" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-indigo-900/50 backdrop-blur-sm transition-opacity" onclick="cerrarModalMasivo()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all w-full max-w-lg">
                <form method="POST" action="index.php">
                    <input type="hidden" name="accion" value="crear_solicitud_masiva">
                    <input type="hidden" name="view" value="mantenimiento_equipos">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-building-line"></i> Programar Visita de Mantenimiento
                        </h3>
                        <button type="button" onclick="cerrarModalMasivo()" class="text-white/80 hover:text-white">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 mb-4">
                            <p class="text-xs text-indigo-800">
                                <i class="ri-information-fill"></i> Se creará una <strong>Solicitud de Visita</strong>
                                para la sede. Los tickets individuales se generarán al reportar el trabajo.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                            <select name="empresa_id" id="masivo_empresa" onchange="cargarSucursalesMasivo(this.value)"
                                required
                                class="w-full px-3 py-2 border rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">Seleccionar Empresa...</option>
                                <?php foreach ($empresas as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                            <select name="sucursal_id" id="masivo_sucursal"
                                class="w-full px-3 py-2 border rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="">Todas las Sucursales (Opcional)</option>
                                <?php foreach ($sucursales as $suc): ?>
                                        <option value="<?= $suc['id'] ?>" data-empresa="<?= $suc['empresa_id'] ?>">
                                            <?= htmlspecialchars($suc['nombre']) ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo</label>
                                <select name="tipo_mantenimiento" class="w-full px-3 py-2 border rounded-lg text-sm">
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Upgrade">Upgrade</option>
                                    <option value="Correctivo">Correctivo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha
                                    Inicio</label>
                                <input type="date" name="fecha_inicio" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                    class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nota Global</label>
                            <input type="text" name="descripcion_masiva" placeholder="Ej: Mantenimiento Trimestral Q1"
                                required class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-2">
                        <button type="button" onclick="cerrarModalMasivo()"
                            class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-200 rounded-lg">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md">Programar
                            Tickets</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Filtro Equipos en Modal
    // Filtro Equipos en Modal
    function filtrarEquipos() {
        var input = document.getElementById("filtro_equipo_input");
        var filter = input.value.toUpperCase();
        var checkSinAsignar = document.getElementById("check_sin_asignar");
        var soloSinAsignar = checkSinAsignar ? checkSinAsignar.checked : false;

        var select = document.getElementById("equipo_id");
        var options = select.getElementsByTagName("option");

        for (var i = 1; i < options.length; i++) { // Skip "Seleccionar..."
            var txtValue = options[i].textContent || options[i].innerText;
            var isAsignado = options[i].getAttribute('data-asignado') === '1';

            var matchesText = txtValue.toUpperCase().indexOf(filter) > -1;
            var matchesFilter = !soloSinAsignar || !isAsignado;

            if (matchesText && matchesFilter) {
                options[i].style.display = "";
            } else {
                options[i].style.display = "none";
            }
        }
    }

    // Scripts Modal Mantenimiento (Existentes)
    function abrirModalMantenimiento() {
        // Resetear a modo buscar por defecto
        toggleModoEquipo('buscar');

        document.getElementById('form-accion').value = 'registrar_mantenimiento';
        document.getElementById('mantenimiento-id').value = '';
        document.getElementById('modal-titulo').innerHTML = '<i class="ri-tools-fill"></i> Registrar Mantenimiento';
        document.getElementById('equipo_id').value = '';
        document.getElementById('div-seleccion-equipo').classList.remove('hidden');
        document.getElementById('div-info-equipo').classList.add('hidden');
        document.getElementById('tipo_mantenimiento').value = 'Preventivo';
        // document.getElementById('prioridad').value = 'Media'; // Eliminado: No existe en el form
        document.getElementById('estado').value = 'Programado';
        document.getElementById('fecha_inicio').value = '<?= date('Y-m-d') ?>';
        document.getElementById('descripcion_problema').value = '';
        document.getElementById('modal-mantenimiento').classList.remove('hidden');
        document.getElementById('modal-mantenimiento').classList.add('flex');
    }
    function abrirModalMasivo() {
        document.getElementById('modalMasivo').classList.remove('hidden');
    }
    function cerrarModalMasivo() {
        document.getElementById('modalMasivo').classList.add('hidden');
    }
    function cargarSucursalesMasivo(empresaId) {
        var select = document.getElementById('masivo_sucursal');
        var options = select.querySelectorAll('option[data-empresa]');
        select.value = "";
        options.forEach(opt => {
            if (opt.dataset.empresa == empresaId || empresaId == "") {
                opt.style.display = "";
            } else {
                opt.style.display = "none";
            }
        });
    }

    function editarMantenimiento(data) {
        // Resetear a modo buscar (siempre editamos uno existente)
        toggleModoEquipo('buscar');

        document.getElementById('form-accion').value = 'actualizar_mantenimiento';
        document.getElementById('mantenimiento-id').value = data.id;
        document.getElementById('modal-titulo').innerHTML = '<i class="ri-edit-line"></i> Editar Mantenimiento';
        document.getElementById('div-seleccion-equipo').classList.add('hidden');
        document.getElementById('div-info-equipo').classList.remove('hidden');
        document.getElementById('info-equipo-texto').textContent = `Equipo: ${data.equipo_tipo} ${data.marca} (${data.serial})`;
        document.getElementById('tipo_mantenimiento').value = data.tipo_mantenimiento;
        document.getElementById('estado').value = data.estado;
        document.getElementById('fecha_inicio').value = data.fecha_inicio || '';
        document.getElementById('descripcion_problema').value = data.descripcion_problema;

        // Cargar Checklist
        // Resetear
        document.querySelectorAll('input[name^="checklist"]').forEach(cb => cb.checked = false);

        if (data.checklist) {
            try {
                // El campo checklist puede venir como objeto o string JSON dependiendo de PDO fetch
                var checks = typeof data.checklist === 'object' ? data.checklist : JSON.parse(data.checklist);
                for (var k in checks) {
                    var cb = document.querySelector(`input[name="checklist[${k}]"]`);
                    if (cb) cb.checked = true;
                }
            } catch (e) { console.log("Error checklist", e); }
        }

        document.getElementById('modal-mantenimiento').classList.remove('hidden');
        document.getElementById('modal-mantenimiento').classList.add('flex');
    }
    function cerrarModalMantenimiento() {
        document.getElementById('modal-mantenimiento').classList.add('hidden');
        document.getElementById('modal-mantenimiento').classList.remove('flex');
    }

    // Scripts Modal Specs (Nuevos)
    function editarSpecs(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_titulo').textContent = `${data.tipo} ${data.marca} ${data.modelo}`;
        document.getElementById('edit_subtitulo').textContent = `Serial: ${data.serial}`;

        document.getElementById('edit_procesador').value = data.procesador || '';
        document.getElementById('edit_ram').value = data.ram || '';
        document.getElementById('edit_disco_duro').value = data.disco_duro || '';
        document.getElementById('edit_antivirus').value = data.antivirus || '';
        document.getElementById('edit_onedrive').value = data.onedrive || '';
        document.getElementById('edit_backup').value = data.backup_status || '';
        document.getElementById('edit_screenconnect').value = data.screenconnect || '';

        document.getElementById('modalSpecs').classList.remove('hidden');
    }

    function cerrarModalSpecs() {
        document.getElementById('modalSpecs').classList.add('hidden');
    }

    // [NEW] Toggle Modo Equipo (Buscar vs Nuevo)
    function toggleModoEquipo(modo) {
        const btnBuscar = document.getElementById('btn-modo-buscar');
        const btnNuevo = document.getElementById('btn-modo-nuevo');
        const divBuscar = document.getElementById('modo-buscar-container');
        const divNuevo = document.getElementById('modo-nuevo-container');

        const inputEquipo = document.getElementById('equipo_id');
        const inputTipo = document.getElementById('nuevo_tipo');
        const inputMarca = document.getElementById('nueva_marca');
        const inputModelo = document.getElementById('nuevo_modelo');

        if (modo === 'buscar') {
            // Estilos Botones
            btnBuscar.className = "flex-1 px-3 py-1.5 rounded-md text-sm font-bold transition-all bg-white text-indigo-600 shadow-sm";
            btnNuevo.className = "flex-1 px-3 py-1.5 rounded-md text-sm font-bold text-slate-500 hover:text-indigo-600 transition-all";

            // Visibilidad
            divBuscar.classList.remove('hidden');
            divNuevo.classList.add('hidden');

            // Requireds
            inputEquipo.required = true;
            inputTipo.required = false;
            inputMarca.required = false;
            inputModelo.required = false;

            // Limpiar valores nuevos
            inputTipo.value = "";
            inputMarca.value = "";
            inputModelo.value = "";
            document.getElementById('nuevo_serial').value = "";

        } else {
            // Estilos Botones
            btnNuevo.className = "flex-1 px-3 py-1.5 rounded-md text-sm font-bold transition-all bg-white text-indigo-600 shadow-sm";
            btnBuscar.className = "flex-1 px-3 py-1.5 rounded-md text-sm font-bold text-slate-500 hover:text-indigo-600 transition-all";

            // Visibilidad
            divNuevo.classList.remove('hidden');
            divBuscar.classList.add('hidden');

            // Requireds
            inputEquipo.required = false;
            inputEquipo.value = ""; // Resetear selección
            inputTipo.required = true;
            inputMarca.required = true;
            inputModelo.required = true;

            // Ocultar info equipo si estaba mostrada
            document.getElementById('div-info-equipo').classList.add('hidden');
        }
    }

    function guardarSpecs(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('formSpecs'));

        // IMPORTANTE: Aseguramos que la URL apunte al view actual "mantenimiento_equipos" 
        // pero con un parámetro extra que index.php pueda interceptar, o usamos el view anterior si el handler es especifico.
        // En el paso anterior puse el handler para 'mantenimiento_reporte'. 
        // Ajustaré index.php en el siguiente paso para que acepte ambos views O cambiaré aquí a 'mantenimiento_reporte'
        // Para simplificar, lo enviaré a mantenimiento_reporte ya que el handler existe y no depende de la visualización.

        fetch('index.php?view=mantenimiento_reporte&ajax_action=guardar_specs', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Especificaciones actualizadas correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.msg || 'No se pudo guardar', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Error de conexión', 'error');
            });
    }

    function prepararMantenimiento(data) {
        abrirModalMantenimiento();

        // Pre-seleccionar en el dropdown
        var select = document.getElementById('equipo_id');
        select.value = data.id;

        // Mostrar ficha de información (Simulando "Tabla alimentada")
        document.getElementById('div-seleccion-equipo').classList.add('hidden');
        document.getElementById('div-info-equipo').classList.remove('hidden');

        var assigned = data.usuario_asignado ? data.usuario_asignado : 'Sin Asignar';
        var info = `
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-blue-800 uppercase">Equipo Seleccionado</p>
                    <p class="text-lg font-bold text-slate-800">${data.tipo} ${data.marca}</p>
                    <p class="text-sm text-slate-600">${data.modelo} <span class="text-slate-400">|</span> SN: ${data.serial}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-blue-800 uppercase">Asignado a</p>
                    <p class="text-base font-medium text-slate-800">${assigned}</p>
                </div>
            </div>
            <button type="button" onclick="resetearSeleccionEquipo()" class="text-xs text-blue-600 hover:underline mt-2">Cambiar equipo</button>
        `;
        document.getElementById('info-equipo-texto').innerHTML = info;
    }

    function resetearSeleccionEquipo() {
        document.getElementById('div-seleccion-equipo').classList.remove('hidden');
        document.getElementById('div-info-equipo').classList.add('hidden');
        document.getElementById('equipo_id').value = "";
    }

    function exportarReporte() {
        let table = document.getElementById("tabla-reporte");
        let html = table.outerHTML;
        let url = 'data:application/vnd.ms-excel,' + escape(html);
        let link = document.createElement("a");
        link.download = "Reporte_Tecnico_" + new Date().toISOString().slice(0, 10) + ".xls";
        link.href = url;
        link.click();
    }
</script>

<!-- Modal Ejecución Visita -->
<div id="modalEjecucion" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="cerrarModalEjecucion()">
    </div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-5xl max-h-[90vh] flex flex-col">

                <form id="formEjecucion" method="POST" onsubmit="enviarReporteVisita(event)"
                    class="flex flex-col w-full h-full overflow-hidden">
                    <input type="hidden" name="accion" value="guardar_reporte_masivo">
                    <input type="hidden" name="view" value="mantenimiento_equipos">
                    <input type="hidden" name="visita_id" id="exec_visita_id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <!-- Header -->
                    <div class="bg-indigo-700 px-6 py-4 flex justify-between items-center shrink-0 rounded-t-2xl">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <i class="ri-clipboard-line"></i> Ejecución de Visita
                            </h3>
                            <p class="text-indigo-200 text-sm" id="exec_titulo">...</p>
                        </div>
                        <button type="button" onclick="cerrarModalEjecucion()"
                            class="text-white/80 hover:text-white bg-white/10 p-2 rounded-full hover:bg-white/20 transition">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Toolbar -->
                    <div
                        class="bg-indigo-50 px-6 py-3 border-b border-indigo-100 flex justify-between items-center shrink-0">
                        <p class="text-xs font-bold text-indigo-800 uppercase">Lista de Activos</p>
                        <div class="text-xs text-indigo-600 italic">
                            Marca los equipos revisados y añade notas si existen incidentes.
                        </div>
                    </div>

                    <!-- Scrollable Content -->
                    <div id="lista-equipos-visita" class="flex-1 overflow-y-auto p-0 bg-slate-50 relative">
                        <!-- AJAX Content -->
                    </div>

                    <!-- Footer -->
                    <div
                        class="bg-white px-6 py-4 border-t border-slate-200 flex justify-between items-center shrink-0">
                        <div class="text-xs text-slate-500">
                            Solo se generarán historiales para los items marcados.
                        </div>
                        <div class="flex gap-3">
                            <button type="button" onclick="cerrarModalEjecucion()"
                                class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Cancelar</button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-lg shadow-indigo-500/30 flex items-center gap-2">
                                <i class="ri-save-3-line text-lg"></i> Finalizar y Generar Reporte
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script>
    function cerrarModalEjecucion() {
        document.getElementById('modalEjecucion').classList.add('hidden');
    }

    async function ejecutarVisita(id, emp, suc, titulo) {
        document.getElementById('exec_visita_id').value = id;
        document.getElementById('exec_titulo').textContent = titulo;
        document.getElementById('modalEjecucion').classList.remove('hidden');

        const container = document.getElementById('lista-equipos-visita');
        container.innerHTML = '<div class="flex h-full items-center justify-center gap-3 text-indigo-600"><i class="ri-loader-4-line text-3xl animate-spin"></i><span class="font-medium animate-pulse">Cargando inventario de la sede...</span></div>';

        try {
            const res = await fetch(`index.php?view=mantenimiento_equipos&ajax_action=obtener_equipos_visita&empresa_id=${emp}&sucursal_id=${suc}`);
            const data = await res.json();

            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="h-full flex flex-col items-center justify-center text-slate-400">
                        <i class="ri-ghost-line text-6xl mb-4 text-slate-300"></i>
                        <p class="font-medium">No se encontraron equipos asignados en esta sede.</p>
                        <p class="text-xs mt-2">Prueba asignando equipos a un usuario de esta sucursal.</p>
                    </div>`;
                return;
            }

            let html = `
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-white text-xs uppercase text-slate-500 font-bold sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="p-4 border-b w-10 text-center">
                                <input type="checkbox" onclick="toggleAllChecks(this)" class="rounded w-4 h-4 cursor-pointer">
                            </th>
                            <th class="p-4 border-b">Equipo</th>
                            <th class="p-4 border-b">Asignado</th>
                            <th class="p-4 border-b w-48">Técnico / Taller</th>
                            <th class="p-4 border-b w-32">Estado Final</th>
                            <th class="p-4 border-b">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
            `;

            data.forEach((eq, idx) => {
                html += `
                    <tr class="bg-white hover:bg-indigo-50/30 transition-colors group">
                        <td class="p-4 text-center align-top pt-5">
                            <input type="checkbox" name="equipos[${idx}][seleccionado]" value="${eq.id}" checked class="eq-check rounded w-5 h-5 text-indigo-600 focus:ring-indigo-500 cursor-pointer shadow-sm border-slate-300">
                            <input type="hidden" name="equipos[${idx}][id]" value="${eq.id}">
                        </td>
                        <td class="p-4 align-top">
                            <div class="font-bold text-slate-800 text-base">${eq.tipo} ${eq.marca}</div>
                            <div class="text-xs text-slate-500 font-mono mt-1">${eq.modelo}</div>
                            <div class="text-[10px] text-slate-400 font-mono">SN: ${eq.serial}</div>
                        </td>
                        <td class="p-4 align-top">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs ring-2 ring-white shadow-sm">
                                    ${eq.usuario_asignado.substring(0, 2).toUpperCase()}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-700">${eq.usuario_asignado}</p>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">Usuario Final</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 align-top">
                            <input type="text" name="equipos[${idx}][tecnico_externo]" placeholder="Nombre Técnico..." 
                                class="w-full px-2 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none placeholder-slate-300">
                        </td>
                        <td class="p-4 align-top">
                            <select name="equipos[${idx}][estado]" class="w-full px-2 py-1.5 text-xs font-bold rounded border border-slate-200 outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50">
                                <option value="Completado" class="text-green-600">Completado</option>
                                <option value="En Proceso" class="text-blue-600">En Proceso</option>
                                <option value="Reparacion" class="text-orange-600">Requiere Taller</option>
                                <option value="Omitido" class="text-slate-400">Omitir</option>
                            </select>
                        </td>
                        <td class="p-4 align-top">
                            <textarea name="equipos[${idx}][notas]" rows="2" placeholder="Notas..." 
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none transition-shadow placeholder-slate-300"></textarea>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;

        } catch (e) {
            console.log(e);
            container.innerHTML = '<div class="p-10 text-center text-red-500 font-bold">Error de conexión al cargar inventario.</div>';
        }
    }

    function toggleAllChecks(source) {
        document.querySelectorAll('.eq-check').forEach(cb => cb.checked = source.checked);
    }

    function enviarReporteVisita(e) {
        // El submit normal del form enviará los datos a index.php
        // No necesitamos preventDefault si queremos que index.php procese y redireccione.
        // Pero index.php espera POST.
        // El form tiene action="index.php" method="POST".
        // Así que dejamos que fluya.
        // Solo validación visual si queremos.
    }
</script>

<!-- Modal Detalles Mantenimiento -->
<div id="modal-detalles-mantenimiento"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[85vh] overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="ri-file-info-line"></i> Detalles del Servicio
            </h3>
            <button onclick="cerrarModalDetalles()" class="text-white/80 hover:text-white transition-colors">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <div class="overflow-y-auto p-6 flex-grow space-y-4" id="contenido-detalles">
            <!-- Contenido dinámico via JS -->
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button onclick="cerrarModalDetalles()"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors font-medium">Cerrar</button>
        </div>
    </div>
</div>

<script>
    function verDetallesMantenimiento(data) {
        const modal = document.getElementById('modal-detalles-mantenimiento');
        const contenido = document.getElementById('contenido-detalles');

        // Parse checklist if it's JSON/String
        let checklistHtml = '';
        try {
            let checklistObj = (typeof data.checklist === 'string' && data.checklist.trim() !== '') ? JSON.parse(data.checklist) : data.checklist;

            if (checklistObj && typeof checklistObj === 'object' && Object.keys(checklistObj).length > 0) {
                checklistHtml = '<div class="grid grid-cols-2 gap-2 mt-2 bg-slate-50 p-3 rounded-lg border border-slate-200">';
                for (const [key, value] of Object.entries(checklistObj)) {
                    if (value === 'on' || value === true || value === 'true') {
                        // Formatear key (reemplazar _ por espacio y capitalizar)
                        let label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        checklistHtml += `<div class="flex items-center gap-2 text-xs text-slate-700"><i class="ri-checkbox-circle-fill text-green-500"></i> <span class="font-medium">${label}</span></div>`;
                    }
                }
                checklistHtml += '</div>';
            } else {
                checklistHtml = '<p class="text-xs text-slate-400 italic mt-1">No se marcaron items en el checklist.</p>';
            }
        } catch (e) {
            checklistHtml = '<p class="text-xs text-slate-400 italic mt-1">Sin datos de checklist.</p>';
            console.error('Error parsing checklist', e);
        }

        contenido.innerHTML = `
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Equipo / Activo</p>
                <p class="font-bold text-slate-800 text-lg">${data.equipo_tipo} ${data.marca}</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-500 border border-slate-200">${data.serial}</span>
                    <span class="text-xs text-slate-500">${data.modelo}</span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Estado del Ticket</p>
                 <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-amber-100 text-amber-700 mt-1 border border-amber-200 shadow-sm">${data.estado}</span>
            </div>
        </div>

        <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="ri-tools-fill text-6xl text-indigo-600"></i>
            </div>
            <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-2">Tipo de Servicio</p>
            <p class="text-xl font-bold text-indigo-900">${data.tipo_mantenimiento}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <i class="ri-alert-line text-red-500"></i> Problema Reportado
                </p>
                <p class="text-sm text-slate-700 leading-relaxed">${data.descripcion_problema || '<span class="italic text-slate-400">Sin descripción</span>'}</p>
            </div>
             <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                     <i class="ri-checkbox-circle-line text-emerald-500"></i> Solución / Observaciones
                </p>
                <p class="text-sm text-slate-700 leading-relaxed">${data.descripcion_solucion || '<span class="italic text-slate-400">Sin solución registrada</span>'}</p>
            </div>
        </div>

        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2 mb-2">Protocolo de Servicio</p>
            ${checklistHtml}
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-3 gap-4 text-xs text-slate-500">
            <div>
                <span class="font-bold block text-slate-400 uppercase text-[10px]">Fecha Inicio</span> 
                ${data.fecha_inicio ? data.fecha_inicio : '---'}
            </div>
            <div>
                <span class="font-bold block text-slate-400 uppercase text-[10px]">Fecha Fin</span> 
                ${data.fecha_fin ? data.fecha_fin : '---'}
            </div>
            <div>
                <span class="font-bold block text-slate-400 uppercase text-[10px]">Técnico</span> 
                ${data.tecnico_nombre ? data.tecnico_nombre : '---'}
            </div>
        </div>
    `;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalDetalles() {
        const modal = document.getElementById('modal-detalles-mantenimiento');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: '<?= $_SESSION['tipo_mensaje'] ?? 'info' ?>',
                    title: '<?= $_SESSION['tipo_mensaje'] == 'error' || $_SESSION['tipo_mensaje'] == 'warning' ? 'Atención' : 'Éxito' ?>',
                    text: '<?= str_replace(["\r", "\n"], " ", addslashes($_SESSION['mensaje'])) ?>',
                    confirmButtonColor: '#4F46E5',
                    confirmButtonText: 'Entendido'
                });
            });
        </script>
        <?php
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
endif; ?>

<?php if (isset($_GET['print_report'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const reportUrl = 'imprimir_reporte_visita.php?id=<?= intval($_GET['print_report']) ?>';

                // Intentar abrir
                const newWindow = window.open(reportUrl, '_blank');

                // Mostrar alerta de éxito siempre (mejor experiencia) y link si falló popup
                Swal.fire({
                    title: '¡Reporte Generado!',
                    text: 'El mantenimiento se ha guardado correctamente. Haz clic para ver el PDF.',
                    icon: 'success',
                    confirmButtonText: '<i class="ri-printer-line"></i> Ver Reporte',
                    confirmButtonColor: '#4F46E5',
                    showCancelButton: true,
                    cancelButtonText: 'Cerrar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(reportUrl, '_blank');
                    }
                });
            });
        </script>
<?php endif; ?>