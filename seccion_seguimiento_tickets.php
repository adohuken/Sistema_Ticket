<?php
/**
 * seccion_seguimiento_tickets.php - Módulo de seguimiento completo de tickets
 * Diseño mejorado y optimizado para visualización de datos
 */

// Obtener tickets si no existen
if (!isset($tickets)) {
    try {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/conexion.php';
        }

        $sql = "SELECT t.*, c.nombre as categoria 
                FROM tickets t 
                LEFT JOIN categorias c ON t.categoria_id = c.id 
                ORDER BY t.id DESC";
        $stmt = $pdo->query($sql);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al cargar tickets: " . $e->getMessage());
        $tickets = [];
    }
}

// Obtener estadísticas generales
$total_tickets = count($tickets);
$tickets_abiertos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Pendiente'));
$tickets_en_proceso = count(array_filter($tickets, fn($t) => $t['estado'] === 'Asignado'));
$tickets_resueltos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Completo'));

// Filtros
$filtro_estado = $_GET['filtro_estado'] ?? 'todos';
$filtro_prioridad = $_GET['filtro_prioridad'] ?? 'todos';
$filtro_categoria = $_GET['filtro_categoria'] ?? 'todos';

// Aplicar filtros
$tickets_filtrados = $tickets;

if ($filtro_estado !== 'todos') {
    $tickets_filtrados = array_filter($tickets_filtrados, fn($t) => strcasecmp(trim($t['estado']), trim($filtro_estado)) === 0);
}

if ($filtro_prioridad !== 'todos') {
    $tickets_filtrados = array_filter($tickets_filtrados, fn($t) => strcasecmp(trim($t['prioridad']), trim($filtro_prioridad)) === 0);
}

if ($filtro_categoria !== 'todos') {
    $tickets_filtrados = array_filter($tickets_filtrados, fn($t) => strcasecmp(trim($t['categoria']), trim($filtro_categoria)) === 0);
}
?>

<div class="p-6">
    <!-- Header con Acciones -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                    <i class="ri-line-chart-line text-xl"></i>
                </div>
                Seguimiento de Tickets
            </h2>
            <p class="text-slate-500 mt-1">Vista general y control de estado de incidencias</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-medium rounded-lg transition-all shadow-sm flex items-center gap-2 text-sm">
                <i class="ri-printer-line"></i>
                <span>Imprimir</span>
            </button>
            <button onclick="exportarExcelSeguimiento()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-emerald-600/20 flex items-center gap-2 text-sm">
                <i class="ri-file-excel-line"></i>
                <span>Exportar Excel</span>
            </button>
        </div>
    </div>

    <!-- Estadísticas (Cards Compactas) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Total -->
        <!-- Total (Link) -->
        <a href="index.php?view=seguimiento"
            class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between group hover:border-blue-200 transition-colors cursor-pointer">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total</p>
                <h3 class="text-2xl font-bold text-slate-800"><?php echo $total_tickets; ?></h3>
            </div>
            <div
                class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="ri-ticket-2-line"></i>
            </div>
        </a>

        <!-- Abiertos -->
        <!-- Abiertos (Pendientes) -->
        <a href="index.php?view=seguimiento&filtro_estado=Pendiente"
            class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between group hover:border-cyan-200 transition-colors cursor-pointer">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pendientes</p>
                <h3 class="text-2xl font-bold text-slate-800"><?php echo $tickets_abiertos; ?></h3>
            </div>
            <div
                class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="ri-inbox-line"></i>
            </div>
        </a>

        <!-- En Proceso -->
        <!-- En Proceso (Asignados) -->
        <a href="index.php?view=seguimiento&filtro_estado=Asignado"
            class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between group hover:border-purple-200 transition-colors cursor-pointer">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Asignados</p>
                <h3 class="text-2xl font-bold text-slate-800"><?php echo $tickets_en_proceso; ?></h3>
            </div>
            <div
                class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="ri-loader-4-line"></i>
            </div>
        </a>

        <!-- Completos -->
        <!-- Completos -->
        <a href="index.php?view=seguimiento&filtro_estado=Completo"
            class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between group hover:border-emerald-200 transition-colors cursor-pointer">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Completos</p>
                <h3 class="text-2xl font-bold text-slate-800"><?php echo $tickets_resueltos; ?></h3>
            </div>
            <div
                class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="index.php" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="view" value="seguimiento">

            <div class="flex-1 min-w-[150px]">
                <div class="relative">
                    <i class="ri-filter-3-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="filtro_estado"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-700">
                        <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Estado: Todos
                        </option>
                        <option value="Pendiente" <?php echo $filtro_estado === 'Pendiente' ? 'selected' : ''; ?>>
                            Pendiente
                        </option>
                        <option value="Asignado" <?php echo $filtro_estado === 'Asignado' ? 'selected' : ''; ?>>Asignado
                        </option>
                        <option value="Completo" <?php echo $filtro_estado === 'Completo' ? 'selected' : ''; ?>>Completo
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex-1 min-w-[150px]">
                <div class="relative">
                    <i class="ri-flag-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="filtro_prioridad"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-700">
                        <option value="todos" <?php echo $filtro_prioridad === 'todos' ? 'selected' : ''; ?>>Prioridad:
                            Todas</option>
                        <option value="Baja" <?php echo $filtro_prioridad === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                        <option value="Media" <?php echo $filtro_prioridad === 'Media' ? 'selected' : ''; ?>>Media
                        </option>
                        <option value="Alta" <?php echo $filtro_prioridad === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="Critica" <?php echo $filtro_prioridad === 'Critica' ? 'selected' : ''; ?>>Crítica
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex-1 min-w-[150px]">
                <div class="relative">
                    <i class="ri-folder-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="filtro_categoria"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-700">
                        <option value="todos" <?php echo $filtro_categoria === 'todos' ? 'selected' : ''; ?>>Categoría:
                            Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>" <?php echo $filtro_categoria === $cat['nombre'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2 text-sm">
                <i class="ri-search-line"></i>
                Filtrar
            </button>
            <a href="index.php?view=seguimiento"
                class="p-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg transition-colors"
                title="Limpiar Filtros">
                <i class="ri-refresh-line"></i>
            </a>
        </form>
    </div>

    <!-- Tabla de Tickets -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Asunto</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Resolución</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Prioridad</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Solicitante</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Técnico</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Ver
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if (empty($tickets_filtrados)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="ri-search-2-line text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No se encontraron tickets</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets_filtrados as $t): ?>
                            <?php
                            // Configuración de colores
                            $prio_config = [
                                'Baja' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'icon' => 'ri-arrow-down-line'],
                                'Media' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'icon' => 'ri-subtract-line'],
                                'Alta' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'icon' => 'ri-arrow-up-line'],
                                'Critica' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'icon' => 'ri-alarm-warning-line']
                            ];
                            $pc = $prio_config[$t['prioridad']] ?? $prio_config['Media'];

                            $status_config = [
                                'Pendiente' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
                                'Asignado' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
                                'Completo' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500']
                            ];
                            $sc = $status_config[$t['estado']] ?? $status_config['Pendiente'];

                            // Datos usuarios
                            $nombre_creador = 'Desconocido';
                            $rol_creador = ''; // Inicializar rol
                            foreach ($usuarios as $u) {
                                if ($u['id'] == $t['creador_id']) {
                                    $nombre_creador = $u['nombre'];
                                    $rol_creador = $u['rol']; // Capturar rol
                                    break;
                                }
                            }
                            $nombre_tecnico = 'Sin Asignar';
                            $tecnico_asignado = false;
                            if ($t['tecnico_id']) {
                                foreach ($usuarios as $u) {
                                    if ($u['id'] == $t['tecnico_id']) {
                                        $nombre_tecnico = $u['nombre'];
                                        $tecnico_asignado = true;
                                        break;
                                    }
                                }
                            }

                            // Lógica de resaltado de filas para Altas y Bajas (Solo RRHH)
                            $row_class = 'hover:bg-slate-50/80'; // Default
                    
                            // Verificar si es RRHH
                            if ($rol_creador === 'RRHH') {
                                if (stripos($t['titulo'], 'Nuevo Ingreso') !== false) {
                                    $row_class = 'bg-emerald-50 hover:bg-emerald-100 border-l-4 border-l-emerald-400';
                                } elseif (stripos($t['titulo'], 'Baja de Personal') !== false) {
                                    $row_class = 'bg-rose-50 hover:bg-rose-100 border-l-4 border-l-rose-400';
                                }
                            }
                            ?>
                            <tr class="<?php echo $row_class; ?> transition-colors group">
                                <td class="px-4 py-3">
                                    <span
                                        class="font-mono text-xs font-bold text-slate-500">#<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition-colors truncate max-w-[300px]"
                                            title="<?php echo htmlspecialchars($t['titulo']); ?>">
                                            <?php echo htmlspecialchars($t['titulo']); ?>
                                        </span>
                                        <span class="text-xs text-slate-400 truncate max-w-[300px]">
                                            <?php echo htmlspecialchars(substr($t['descripcion'], 0, 60)); ?>...
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $sc['bg'] . ' ' . $sc['text']; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $sc['dot']; ?>"></span>
                                        <?php echo htmlspecialchars($t['estado']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php
                                    $res_raw = $t['resolucion'] ?? '';
                                    // Intentar parsear formato "📄 [FECHA] TEXTO" o "[FECHA] TEXTO"
                                    if ($res_raw && preg_match('/(?:📄\s*)?\[(.*?)\]\s*(.*)/s', $res_raw, $matches)) {
                                        $res_fecha = $matches[1];
                                        $res_msg = $matches[2];

                                        // Traducción español
                                        if (strtolower(trim($res_msg)) === 'ready') {
                                            $res_msg = 'Listo';
                                        }

                                        echo '<div class="flex flex-col">';
                                        echo '<span class="text-sm text-slate-700 font-medium leading-snug" title="' . htmlspecialchars($res_msg) . '">' . htmlspecialchars(substr($res_msg, 0, 80)) . (strlen($res_msg) > 80 ? '...' : '') . '</span>';
                                        echo '<span class="text-[10px] text-slate-400 mt-1 flex items-center gap-1"><i class="ri-calendar-check-line"></i> ' . htmlspecialchars($res_fecha) . '</span>';
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-xs text-slate-500 block max-w-[200px] truncate" title="' . htmlspecialchars($res_raw) . '">';
                                        echo htmlspecialchars($res_raw ? $res_raw : '-');
                                        echo '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-xs font-medium <?php echo $pc['text']; ?>">
                                        <i class="<?php echo $pc['icon']; ?>"></i>
                                        <?php echo htmlspecialchars($t['prioridad']); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-xs text-slate-600">
                                        <i class="ri-folder-2-line text-slate-400"></i>
                                        <span
                                            class="truncate max-w-[120px]"><?php echo htmlspecialchars($t['categoria']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 border border-slate-200">
                                            <?php echo strtoupper(substr($nombre_creador, 0, 1)); ?>
                                        </div>
                                        <span
                                            class="text-xs font-medium text-slate-700 truncate max-w-[120px]"><?php echo htmlspecialchars($nombre_creador); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($tecnico_asignado): ?>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-indigo-50 flex items-center justify-center text-[10px] font-bold text-indigo-600 border border-indigo-100">
                                                <?php echo strtoupper(substr($nombre_tecnico, 0, 1)); ?>
                                            </div>
                                            <span
                                                class="text-xs font-medium text-slate-700 truncate max-w-[120px]"><?php echo htmlspecialchars($nombre_tecnico); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="index.php?view=editar_ticket&id=<?php echo $t['id']; ?>"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 transition-all"
                                        title="Ver Detalles">
                                        <i class="ri-arrow-right-line"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function exportarExcelSeguimiento() {
        let table = document.querySelector("table");
        if (!table) return;

        let tempTable = document.createElement("table");
        let thead = document.createElement("thead");
        let trHead = document.createElement("tr");

        // Copiar headers menos el último (Ver)
        let headers = table.querySelectorAll("thead th");
        for (let i = 0; i < headers.length - 1; i++) {
            let newTh = document.createElement("th");
            newTh.innerText = headers[i].innerText.toUpperCase().trim();
            newTh.style.background = "#f0f0f0";
            newTh.style.border = "1px solid #999";
            trHead.appendChild(newTh);
        }
        thead.appendChild(trHead);
        tempTable.appendChild(thead);

        let tbody = document.createElement("tbody");
        let rows = table.querySelectorAll("tbody tr");

        rows.forEach(row => {
            if (row.innerText.includes("No se encontraron")) return;

            let newRow = document.createElement("tr");
            let cells = row.querySelectorAll("td");

            // Si la fila tiene datos
            if (cells.length > 5) {
                // Índices: 0:ID, 1:Asunto, 2:Estado, 3:Resolución, 4:Prioridad, 5:Categoría, 6:Solicitante, 7:Técnico
                // Copiamos hasta la antepenúltima (excluyendo 'Ver')
                // La tabla original tiene 9 columnas (0-8). Queremos copiar 0-7.
                for (let i = 0; i < cells.length - 1; i++) {
                    let td = document.createElement("td");
                    td.style.border = "1px solid #ddd";

                    let cell = cells[i];

                    if (i === 1) { // Asunto
                        let titulo = cell.querySelector("span.font-semibold")?.innerText || "";
                        let desc = cell.querySelector("span.text-xs")?.innerText || "";
                        td.innerHTML = `<b>${titulo}</b><br><span style='color:#555'>${desc}</span>`;
                    }
                    else if (i === 2) { // Estado
                        td.innerText = cell.innerText.trim();
                        td.style.textAlign = "center";
                    }
                    else if (i === 3) { // Resolución
                        let textoRes = "";
                        let msgSpan = cell.querySelector("span.font-medium");
                        let dateSpan = cell.querySelector("span.text-slate-400");
                        if (msgSpan && dateSpan) {
                            textoRes = msgSpan.innerText.trim() + " (" + dateSpan.innerText.trim() + ")";
                        } else {
                            textoRes = cell.innerText.trim();
                        }
                        td.innerText = textoRes;
                    }
                    else if (i === 6 || i === 7) { // Solicitante / Tecnico
                        // A veces es texto directo, a veces dentro de span
                        let nombre = cell.innerText.trim();
                        // Intentar limpiar si hay saltos de línea extra (e.g. inicial + nombre)
                        // En el HTML original: div > div(inicial) + span(nombre)
                        let spanNombre = cell.querySelector("span.font-medium, span.text-xs");
                        if (spanNombre) {
                            nombre = spanNombre.innerText.trim();
                        }
                        td.innerText = nombre;
                    }
                    else {
                        td.innerText = cell.innerText.replace(/[\n\r]+/g, " ").trim();
                    }
                    newRow.appendChild(td);
                }
                tbody.appendChild(newRow);
            }
        });
        tempTable.appendChild(tbody);

        // Meta para utf-8
        let meta = '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
        let style = `<style>
            body { font-family: Arial; }
            table { border-collapse: collapse; width: 100%; }
            td, th { vertical-align: top; mso-number-format: "\@"; }
        </style>`;

        let html = meta + style + tempTable.outerHTML;

        let blob = new Blob([html], { type: "application/vnd.ms-excel" });
        let url = URL.createObjectURL(blob);
        let a = document.createElement("a");
        a.href = url;
        a.download = "Seguimiento_Tickets_" + new Date().toISOString().slice(0, 10) + ".xls";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        // Limpieza
        setTimeout(() => URL.revokeObjectURL(url), 100);
    }
</script>