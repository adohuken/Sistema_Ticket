<?php
// seccion_historial_tecnico.php
// Módulo para visualizar el desempeño y resoluciones de cada técnico
// Versión Rediseñada: Visualización en Tabla (Data Table)

try {
    // 1. Obtener lista de técnicos
    $sql_tecnicos = "SELECT u.id, u.nombre_completo, u.email, r.nombre as rol 
                     FROM usuarios u 
                     JOIN roles r ON u.rol_id = r.id 
                     WHERE r.nombre = 'Tecnico' OR r.nombre = 'Admin' 
                     ORDER BY u.nombre_completo ASC";
    $stmt_tecnicos = $pdo->query($sql_tecnicos);
    $tecnicos = $stmt_tecnicos->fetchAll();

    // 2. Determinar técnico seleccionado
    $tecnico_seleccionado_id = $_GET['tecnico_id'] ??
        ($_SESSION['usuario_rol'] == 'Tecnico' ? $_SESSION['usuario_id'] : ($tecnicos[0]['id'] ?? 0));

    // Cargar Empresas y Sucursales para filtros
    $empresas_lista = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre ASC")->fetchAll();
    $sucursales_lista = $pdo->query("SELECT id, nombre, empresa_id FROM sucursales ORDER BY nombre ASC")->fetchAll();

    // 3. Construir Filtros
    // Usamos subconsulta para evitar duplicados por múltiples asignaciones
    $where_clauses = ["t.id IN (SELECT ticket_id FROM asignaciones WHERE tecnico_id = ?)"];
    $params = [$tecnico_seleccionado_id];

    // Filtro: Búsqueda
    if (!empty($_GET['q'])) {
        $where_clauses[] = "(t.titulo LIKE ? OR t.descripcion LIKE ? OR t.id LIKE ?)";
        $term = "%" . $_GET['q'] . "%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    // Filtro: Estado
    if (!empty($_GET['estado'])) {
        if ($_GET['estado'] === 'Todos') {
            // No aplicar filtro de estado
        } else {
            $where_clauses[] = "t.estado = ?";
            $params[] = $_GET['estado'];
        }
    } else {
        // Por defecto: Solo Resuelto o Cerrado (Historial de finalizados)
        $where_clauses[] = "t.estado = 'Completo'";
    }
    // Filtro: Empresa
    if (!empty($_GET['empresa_id'])) {
        $where_clauses[] = "u_creador.empresa_id = ?";
        $params[] = $_GET['empresa_id'];
    }
    // Filtro: Sucursal
    if (!empty($_GET['sucursal_id'])) {
        $where_clauses[] = "u_creador.sucursal_id = ?";
        $params[] = $_GET['sucursal_id'];
    }
    // Filtros Fecha
    if (!empty($_GET['f_ini'])) {
        $where_clauses[] = "DATE(t.fecha_actualizacion) >= ?";
        $params[] = $_GET['f_ini'];
    }
    if (!empty($_GET['f_fin'])) {
        $where_clauses[] = "DATE(t.fecha_actualizacion) <= ?";
        $params[] = $_GET['f_fin'];
    }

    $sql_where = implode(" AND ", $where_clauses);

    // Consulta Principal (Sin JOIN asignaciones para evitar duplicados)
    $sql_historial = "SELECT t.*, c.nombre as categoria_nombre, 
                             u_creador.empresa_id, u_creador.sucursal_id,
                             e.nombre as empresa_nombre, s.nombre as sucursal_nombre,
                             u_creador.nombre_completo as creador_nombre
                      FROM tickets t 
                      LEFT JOIN categorias c ON t.categoria_id = c.id
                      LEFT JOIN usuarios u_creador ON t.creador_id = u_creador.id
                      LEFT JOIN empresas e ON u_creador.empresa_id = e.id
                      LEFT JOIN sucursales s ON u_creador.sucursal_id = s.id
                      WHERE $sql_where
                      ORDER BY t.fecha_actualizacion DESC";

    $stmt_historial = $pdo->prepare($sql_historial);
    $stmt_historial->execute($params);
    $historial_tickets = $stmt_historial->fetchAll();

    // Datos del técnico seleccionado
    $nombre_tecnico_sel = "Desconocido";
    $email_tecnico_sel = "";
    $rol_tecnico_sel = "";
    foreach ($tecnicos as $t) {
        if ($t['id'] == $tecnico_seleccionado_id) {
            $nombre_tecnico_sel = $t['nombre_completo'];
            $email_tecnico_sel = $t['email'];
            $rol_tecnico_sel = $t['rol'];
            break;
        }
    }

    // Estadísticas Rápidas
    $count_total = count($historial_tickets);
    $count_resueltos = 0;
    foreach ($historial_tickets as $tic) {
        if ($tic['estado'] == 'Completo')
            $count_resueltos++;
    }

} catch (PDOException $e) {
    echo "<div class='p-4 sm:ml-64'><div class='bg-red-100 text-red-700 p-4 rounded'>Error DB: " . $e->getMessage() . "</div></div>";
    die();
}
?>

<div class="p-4 bg-slate-50 min-h-screen">
    <div class="w-full p-4 md:p-6">

        <!-- Header: Título y Selector -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Historial Técnico</h1>
                <p class="text-slate-500 text-sm">Registro completo de actividades y resoluciones.</p>
            </div>

            <!-- Selector de Técnico con mejor diseño -->
            <form action="index.php" method="GET" class="w-full lg:w-auto">
                <input type="hidden" name="view" value="historial_tecnico">
                <div class="relative group">
                    <div
                        class="flex items-center bg-white border border-slate-200 rounded-xl px-4 py-2 shadow-sm hover:border-blue-300 transition-colors w-full lg:w-80">
                        <div
                            class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs mr-3">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <div class="flex-1">
                            <label
                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Técnico</label>
                            <select name="tecnico_id" onchange="this.form.submit()"
                                class="w-full bg-transparent font-semibold text-slate-700 outline-none cursor-pointer appearance-none text-sm">
                                <?php foreach ($tecnicos as $tec): ?>
                                    <option value="<?php echo $tec['id']; ?>" <?php echo $tec['id'] == $tecnico_seleccionado_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tec['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <i class="ri-arrow-down-s-line text-slate-400 ml-2 pointer-events-none"></i>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tarjeta de Resumen del Técnico (Horizontal y Compacta) -->
        <div
            class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div
                    class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-blue-500/20">
                    <?php echo strtoupper(substr($nombre_tecnico_sel, 0, 1)); ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($nombre_tecnico_sel); ?>
                    </h2>
                    <div class="flex items-center gap-3 mt-1">
                        <span
                            class="px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200 uppercase tracking-wide">
                            <?php echo htmlspecialchars($rol_tecnico_sel); ?>
                        </span>
                        <span class="text-sm text-slate-400 flex items-center gap-1">
                            <i class="ri-mail-line"></i> <?php echo htmlspecialchars($email_tecnico_sel); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex gap-4 w-full md:w-auto">
                <div
                    class="flex-1 md:flex-none p-4 bg-slate-50 rounded-xl border border-slate-100 min-w-[140px] text-center">
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-wider mb-1">Total Tickets</p>
                    <p class="text-2xl font-bold text-slate-700"><?php echo $count_total; ?></p>
                </div>
                <div
                    class="flex-1 md:flex-none p-4 bg-emerald-50 rounded-xl border border-emerald-100 min-w-[140px] text-center">
                    <p class="text-xs text-emerald-600 uppercase font-bold tracking-wider mb-1">Resueltos</p>
                    <p class="text-2xl font-bold text-emerald-600"><?php echo $count_resueltos; ?></p>
                </div>
            </div>
        </div>

        <!-- Contenedor Principal: Filtros y Tabla -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">

            <!-- Barra de Filtros Integrada -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <input type="hidden" name="view" value="historial_tecnico">
                    <input type="hidden" name="tecnico_id"
                        value="<?php echo htmlspecialchars($tecnico_seleccionado_id); ?>">

                    <!-- Buscar -->
                    <div class="md:col-span-3">
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Buscar</label>
                        <div class="relative">
                            <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                placeholder="ID, Título..."
                                class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-medium text-slate-700">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="md:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Estado</label>
                        <select name="estado" onchange="this.form.submit()"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-700 cursor-pointer hover:border-blue-400 transition-colors">
                            <option value="" <?php echo empty($_GET['estado']) ? 'selected' : ''; ?>>Solo Terminados
                                (Por Defecto)</option>
                            <option value="Todos" <?php echo ($_GET['estado'] ?? '') === 'Todos' ? 'selected' : ''; ?>>Ver
                                Todos (Incluidos Activos)</option>
                            <optgroup label="Específico">
                                <?php
                                $estados = ['Pendiente', 'Asignado', 'Completo'];
                                foreach ($estados as $est) {
                                    $sel = ($_GET['estado'] ?? '') == $est ? 'selected' : '';
                                    echo "<option value='$est' $sel>$est</option>";
                                }
                                ?>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Empresa -->
                    <div class="md:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Empresa</label>
                        <select name="empresa_id" onchange="this.form.submit()"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-700">
                            <option value="">Todas</option>
                            <?php foreach ($empresas_lista as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= ($_GET['empresa_id'] ?? '') == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fecha Inicio -->
                    <div class="md:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Desde</label>
                        <input type="date" name="f_ini" value="<?= htmlspecialchars($_GET['f_ini'] ?? '') ?>"
                            class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm">
                    </div>

                    <!-- Botones -->
                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-sm transition-colors text-sm flex items-center gap-2 flex-1 justify-center">
                            <i class="ri-filter-3-line"></i> Filtrar
                        </button>
                        <?php if (!empty($_GET['q']) || !empty($_GET['estado']) || !empty($_GET['empresa_id'])): ?>
                            <a href="index.php?view=historial_tecnico&tecnico_id=<?= $tecnico_seleccionado_id ?>"
                                class="px-3 py-2 bg-slate-200 text-slate-600 rounded-lg hover:bg-slate-300 transition-colors flex items-center justify-center"
                                title="Limpiar">
                                <i class="ri-close-line text-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabla de Resultados -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-20">
                                ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-left">
                                Asunto / Descripción</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                Empresa / Sucursal</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                Actualizado</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                Prioridad</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">
                                Estado</th>
                            <th
                                class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-20">
                                Ver</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($historial_tickets)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-xl">
                                            <i class="ri-search-line"></i>
                                        </div>
                                        <p class="font-medium">No se encontraron tickets con los filtros actuales.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial_tickets as $t): ?>
                                <?php
                                // Colores y Badge Logica
                                $prio_bg = match ($t['prioridad']) { 'Alta', 'Critica' => 'bg-red-50 text-red-700 border-red-100', 'Media' => 'bg-orange-50 text-orange-700 border-orange-100', default => 'bg-emerald-50 text-emerald-700 border-emerald-100'};

                                $estado_bg = match ($t['estado']) {
                                    'Completo' => 'bg-emerald-100 text-emerald-800',
                                    'Asignado' => 'bg-blue-100 text-blue-800',
                                    default => 'bg-amber-100 text-amber-800'
                                };
                                ?>
                                <tr
                                    class="hover:bg-white hover:shadow-lg hover:shadow-blue-500/5 transition-all cursor-default border-l-4 border-transparent hover:border-blue-500 bg-white group">
                                    <td
                                        class="px-6 py-4 text-sm font-bold text-slate-400 group-hover:text-blue-600 transition-colors text-center">
                                        #<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="font-bold text-slate-800 text-sm mb-1 group-hover:text-blue-700 transition-colors">
                                            <?php echo htmlspecialchars($t['titulo']); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 line-clamp-1 mb-1.5 font-normal">
                                            <?php echo htmlspecialchars(trim(preg_replace('/={3,}/', '', $t['descripcion']))); ?>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[10px] uppercase font-bold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100 group-hover:bg-blue-50 group-hover:text-blue-400 group-hover:border-blue-100 transition-colors">
                                                <i
                                                    class="<?php echo $t['categoria_nombre'] == 'Hardware' ? 'ri-computer-line' : ($t['categoria_nombre'] == 'Software' ? 'ri-code-s-slash-line' : 'ri-question-line'); ?>"></i>
                                                <?php echo htmlspecialchars($t['categoria_nombre'] ?? 'General'); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="text-xs font-bold text-slate-700"><?= htmlspecialchars($t['empresa_nombre'] ?? 'N/A') ?></span>
                                            <span
                                                class="text-[10px] text-slate-400 uppercase"><?= htmlspecialchars($t['sucursal_nombre'] ?? '') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-slate-600">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="font-medium"><?= date('d M Y', strtotime($t['fecha_actualizacion'])) ?></span>
                                            <span
                                                class="text-xs text-slate-400"><?= date('H:i', strtotime($t['fecha_actualizacion'])) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold border <?= $prio_bg ?>">
                                            <?= htmlspecialchars($t['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $estado_bg ?>">
                                            <?= str_replace('_', ' ', strtoupper($t['estado'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="index.php?view=editar_ticket&id=<?= $t['id'] ?>"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                                            <i class="ri-eye-line text-lg"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer de Tabla -->
            <div
                class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-500 font-medium">
                <span>Mostrando <?= count($historial_tickets) ?> registros</span>
                <span>Última actualización: <?= date('H:i:s') ?></span>
            </div>
        </div>
    </div>
</div>