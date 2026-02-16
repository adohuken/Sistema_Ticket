<?php
/**
 * seccion_reportes.php - Dashboard de Análisis Gerencial
 * Versión 3.0: Enfoque Corporativo (Empresas y Sucursales) (RECONSTRUIDO)
 */

// --- 1. Cargar Datos Estructurales (Empresas, Sucursales, Personal) ---
try {
    $stmt = $pdo->query("SELECT * FROM empresas WHERE activa = 1 ORDER BY nombre");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM sucursales WHERE activa = 1 ORDER BY nombre");
    $all_sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeo: Usuario ID -> Datos de Empresa/Sucursal
    $stmt = $pdo->query("
        SELECT u.id as usuario_id, 
               COALESCE(p.empresa_id, u.empresa_id) as empresa_id,
               COALESCE(p.sucursal_id, u.sucursal_id) as sucursal_id,
               u.nombre_completo as nombre_real
        FROM usuarios u
        LEFT JOIN personal p ON u.id = p.usuario_sistema_id
    ");
    $mapa_personal = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // Listar todos los usuarios con su rol (Global $usuarios required for later filters)
    $stmt = $pdo->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.nombre_completo");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Usuarios RRHH (Consulta corregida con JOIN)
    $stmt = $pdo->query("SELECT u.id, u.nombre_completo, p.empresa_id 
                         FROM usuarios u 
                         JOIN roles r ON u.rol_id = r.id
                         LEFT JOIN personal p ON u.id = p.usuario_sistema_id 
                         WHERE r.nombre = 'RRHH'");
    $usuarios_rrhh = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='p-4 text-red-600'>Error cargando datos: " . $e->getMessage() . "</div>";
    $empresas = [];
    $all_sucursales = [];
    $mapa_personal = [];
    $usuarios = [];
    $usuarios_rrhh = [];
}

// --- 1.1 Obtener Permisos de Visibilidad (RRHH) ---
$mis_permisos_sucursales = [];
if (isset($rol_usuario) && ($rol_usuario === 'RRHH' || strpos($rol_usuario, 'RRHH') !== false)) {
    try {
        $stmt_permisos = $pdo->prepare("SELECT sucursal_id FROM usuarios_accesos WHERE usuario_id = ?");
        $stmt_permisos->execute([$usuario_id]);
        $mis_permisos_sucursales = $stmt_permisos->fetchAll(PDO::FETCH_COLUMN);

        if (empty($mis_permisos_sucursales) && isset($mapa_personal[$usuario_id]['sucursal_id'])) {
            $base_suc = $mapa_personal[$usuario_id]['sucursal_id'];
            if ($base_suc)
                $mis_permisos_sucursales[] = $base_suc;
        }
    } catch (Exception $e) {
        $mis_permisos_sucursales = [];
    }
}

// --- 2. Inicializar Filtros ---
$filtro_empresa = $_GET['filtro_empresa'] ?? '';
$filtro_sucursal = $_GET['filtro_sucursal'] ?? '';
$filtro_tecnico = $_GET['filtro_tecnico'] ?? '';
$filtro_rrhh = $_GET['filtro_rrhh'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// --- 3. Filtrado de Tickets ---
$tickets_reporte = [];
$empresa_nombre_actual = "Todas las Empresas";

foreach ($tickets as $t) {
    // Filtro Fecha
    $fecha_ticket = isset($t['fecha_creacion']) ? substr($t['fecha_creacion'], 0, 10) : date('Y-m-d');
    if ($fecha_ticket < $fecha_inicio || $fecha_ticket > $fecha_fin)
        continue;

    // Datos Org
    $creador_id = $t['creador_id'];
    $info_org = $mapa_personal[$creador_id] ?? null;
    $empresa_id_ticket = $info_org['empresa_id'] ?? null;
    $sucursal_id_ticket = $info_org['sucursal_id'] ?? null;

    // Permisos
    if (!empty($mis_permisos_sucursales)) {
        if (!in_array($sucursal_id_ticket, $mis_permisos_sucursales))
            continue;
    }

    // Filtros
    if (!empty($filtro_empresa)) {
        if ($empresa_id_ticket != $filtro_empresa)
            continue;
        foreach ($empresas as $e)
            if ($e['id'] == $filtro_empresa)
                $empresa_nombre_actual = $e['nombre'];
    }
    if (!empty($filtro_sucursal) && $sucursal_id_ticket != $filtro_sucursal)
        continue;
    if (!empty($filtro_tecnico) && $t['tecnico_id'] != $filtro_tecnico)
        continue;
    if (!empty($filtro_rrhh) && $creador_id != $filtro_rrhh)
        continue;

    $t['info_org'] = $info_org;
    $tickets_reporte[] = $t;
}

// --- 3.1 Ordenamiento Personalizado (Resueltos al final) ---
usort($tickets_reporte, function($a, $b) {
    $a_resuelto = $a['estado'] === 'Completo';
    $b_resuelto = $b['estado'] === 'Completo';

    if ($a_resuelto && !$b_resuelto) return 1; // A va después
    if (!$a_resuelto && $b_resuelto) return -1; // A va antes
    
    // Si ambos son del mismo "grupo", mantener orden original (descendente por ID/Fecha)
    // Asumiendo que vienen ordenados por ID DESC desde la DB
    return 0; 
});

// --- 4. Estadísticas ---
$total_filtrados = count($tickets_reporte);
$resueltos = 0;
$por_sucursal = [];

foreach ($tickets_reporte as $t) {
    if ($t['estado'] == 'Completo')
        $resueltos++;
}

// --- 5. Preparar Textos para Resumen (Impresión) ---
$txt_empresa = $empresa_nombre_actual;
$txt_sucursal = 'Todas';
if (!empty($filtro_sucursal)) {
    foreach ($all_sucursales as $s)
        if ($s['id'] == $filtro_sucursal) {
            $txt_sucursal = $s['nombre'];
            break;
        }
}
$txt_tecnico = 'Todos';
if (!empty($filtro_tecnico) && isset($usuarios)) {
    foreach ($usuarios as $u)
        if ($u['id'] == $filtro_tecnico) {
            $txt_tecnico = $u['nombre_completo'];
            break;
        }
}
?>

<!-- ESTILOS DE IMPRESIÓN -->
<style>
    @media print {
        aside, nav, header, .sidebar, .navbar, .menu-lateral, footer, form, .btn-print, button, a[href] { display: none !important; }
        body, html, #root, .main-content { margin: 0 !important; padding: 0 !important; background: white !important; width: 100% !important; overflow: visible !important; }
        .p-8 { padding: 0 !important; background: white !important; }
        table { width: 100% !important; border: 1px solid #ddd !important; border-collapse: collapse !important; }
        th, td { border: 1px solid #eee !important; padding: 8px !important; }
        thead { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        tr { page-break-inside: avoid; }
        h1 { font-size: 20px !important; color: #000 !important; margin-bottom: 5px !important; }
        .resumen-filtros { display: block !important; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; font-size: 12px; }
        .kpi-container { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
        .kpi-card { border: 1px solid #ddd !important; padding: 10px !important; }
    }
</style>

<div class="p-8 bg-slate-50 min-h-full">
    <div class="max-w-7xl mx-auto">
        
        <!-- Encabezado y Acciones -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-6">
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                    <i class="ri-building-4-line text-blue-600"></i>
                    Reporte Corporativo
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    <?php echo htmlspecialchars($empresa_nombre_actual); ?> | Generado el: <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
            <div class="flex gap-2 print:hidden">
                <button onclick="exportarExcel()" class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <i class="ri-file-excel-2-line"></i> Exportar
                </button>
                <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg shadow-sm transition-colors btn-print">
                    <i class="ri-printer-line"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- RESUMEN DE FILTROS (Impresion) -->
        <div class="hidden print:flex resumen-filtros bg-slate-50 border border-slate-200 rounded-lg p-4 grid-cols-4 gap-4 text-xs mb-6">
            <div><strong class="text-slate-500 uppercase">Empresa:</strong><br><?php echo $txt_empresa; ?></div>
            <div><strong class="text-slate-500 uppercase">Sucursal:</strong><br><?php echo $txt_sucursal; ?></div>
            <div><strong class="text-slate-500 uppercase">Período:</strong><br><?php echo date('d/m/y', strtotime($fecha_inicio)) . ' - ' . date('d/m/y', strtotime($fecha_fin)); ?></div>
            <div><strong class="text-slate-500 uppercase">Técnico:</strong><br><?php echo $txt_tecnico; ?></div>
        </div>

        <!-- PANEL DE FILTROS (Compacto) -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-8 print:hidden">
            <form method="GET" action="index.php">
                <input type="hidden" name="view" value="reportes_nuevo">
                
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                        <select name="filtro_empresa" onchange="this.form.submit()" class="w-full bg-slate-50 border border-blue-200 text-slate-800 text-sm rounded-lg focus:ring-blue-500 block p-2">
                            <option value="">Todas</option>
                            <?php foreach ($empresas as $e): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo $filtro_empresa == $e['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e['nombre']); ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                        <select name="filtro_sucursal" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg p-2">
                            <option value="">Todas</option>
                            <?php foreach ($all_sucursales as $s): ?>
                                    <?php if (empty($filtro_empresa) || $s['empresa_id'] == $filtro_empresa): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $filtro_sucursal == $s['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s['nombre']); ?>
                                            </option>
                                    <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Desde</label>
                        <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg p-2">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hasta</label>
                        <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg p-2">
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm shadow-sm">
                            <i class="ri-refresh-line"></i> Filtrar
                        </button>
                    </div>

                    <!-- Fila 2 -->
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Técnico</label>
                        <select name="filtro_tecnico" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-xs rounded-lg p-2">
                            <option value="">Todos</option>
                            <?php foreach ($usuarios as $u) {
                                if ($u['rol_nombre'] === 'Tecnico') {
                                    $sel = $filtro_tecnico == $u['id'] ? 'selected' : '';
                                    echo "<option value='{$u['id']}' $sel>" . htmlspecialchars($u['nombre_completo']) . "</option>";
                                }
                            } ?>
                        </select>
                    </div>
                     <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Solicitante RRHH</label>
                        <select name="filtro_rrhh" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-xs rounded-lg p-2">
                            <option value="">Todos</option>
                            <?php foreach ($usuarios_rrhh as $urr): ?>
                                    <?php if (empty($filtro_empresa) || $urr['empresa_id'] == $filtro_empresa): ?>
                                            <option value="<?php echo $urr['id']; ?>" <?php echo $filtro_rrhh == $urr['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($urr['nombre_completo']); ?>
                                            </option>
                                    <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="md:col-span-6 flex justify-end">
                         <a href="index.php?view=reportes_nuevo" class="text-xs text-blue-500 hover:underline mt-2">Limpiar Filtros</a>
                     </div>
                </div>
            </form>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 kpi-container">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center kpi-card">
                <span class="text-xs text-slate-500 uppercase font-bold">Total</span>
                <span class="block text-3xl font-bold text-slate-800"><?php echo $total_filtrados; ?></span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center kpi-card">
                <span class="text-xs text-slate-500 uppercase font-bold">Resueltos</span>
                <span class="block text-3xl font-bold text-emerald-600"><?php echo $resueltos; ?></span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center kpi-card">
                <span class="text-xs text-slate-500 uppercase font-bold">Pendientes</span>
                <span class="block text-3xl font-bold text-orange-600"><?php echo $total_filtrados - $resueltos; ?></span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center kpi-card">
                <span class="text-xs text-slate-500 uppercase font-bold">Resuelto %</span>
                <span class="block text-3xl font-bold text-blue-600">
                    <?php echo $total_filtrados > 0 ? round(($resueltos / $total_filtrados) * 100) : 0; ?>%
                </span>
            </div>
        </div>

        <!-- TABLA -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase w-24">ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase w-32">Fecha</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Asunto / Descripción</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Solicitante</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Estado</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Técnico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($tickets_reporte)): ?>
                                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400">Sin resultados</td></tr>
                        <?php else: ?>
                                <?php foreach ($tickets_reporte as $t):
                                    $st_config = match ($t['estado']) {
                        'Pendiente' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'icon' => 'ri-alert-line'],
                        'Asignado' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'icon' => 'ri-loader-4-line'],
                        'Completo' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'icon' => 'ri-check-double-line'],
                        default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'icon' => 'ri-question-line']
                    };
                                    $is_urgent = ($t['prioridad'] == 'Alta' || $t['prioridad'] == 'Critica');
                                    $pr_class = $is_urgent ? 'text-red-500 bg-red-50' : 'text-slate-500 bg-slate-50';

                                    $nom_tecnico = 'Pendiente';
                                    $avatar_tecnico = '';
                                    if ($t['tecnico_id']) {
                                        foreach ($usuarios as $u)
                                            if ($u['id'] == $t['tecnico_id']) {
                                                $nom_tecnico = $u['nombre_completo'];
                                                $avatar_tecnico = substr($u['nombre_completo'], 0, 1);
                                                break;
                                            }
                                    }
                                    $nom_sol = isset($t['info_org']['nombre_real']) ? $t['info_org']['nombre_real'] : 'Usuario';
                                    ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500 align-top">
                                            #<?php echo str_pad($t['id'], 4, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-700 align-top">
                                            <?php echo isset($t['fecha_creacion']) ? date('d/m/Y', strtotime($t['fecha_creacion'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <div class="max-w-md">
                                                <div class="font-bold text-sm text-slate-800"><?php echo htmlspecialchars($t['titulo']); ?></div>
                                                <div class="text-xs text-slate-500 line-clamp-2">
                                                    <?php 
                                                    // Limpiar separadores visuales
                                                    $desc_limpia = preg_replace('/={3,}/', '', $t['descripcion']);
                                                    echo htmlspecialchars($desc_limpia); 
                                                    ?>
                                                </div>
                                                <?php if (empty($filtro_empresa) && isset($t['info_org']['empresa_id'])):
                                                    foreach ($empresas as $e)
                                                        if ($e['id'] == $t['info_org']['empresa_id']): ?>
                                                                <span class="mt-1 inline-block px-2 py-0.5 bg-slate-100 text-[10px] rounded text-slate-600 border border-slate-200"><?php echo $e['nombre']; ?></span>
                                                        <?php endif; endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                                    <?php echo strtoupper(substr($nom_sol, 0, 1)); ?>
                                                </div>
                                                <div class="text-xs">
                                                    <div class="font-bold text-slate-700"><?php echo htmlspecialchars($nom_sol); ?></div>
                                                    <div class="text-slate-400">Solicitante</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex flex-col gap-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold <?php echo $st_config['bg'] . ' ' . $st_config['text']; ?>">
                                                    <i class="<?php echo $st_config['icon']; ?>"></i> <?php echo $t['estado']; ?>
                                                </span>
                                                <span class="inline-block px-2 py-0.5 rounded text-[10px] border <?php echo $pr_class; ?>">
                                                    <?php echo $t['prioridad']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <?php if ($t['tecnico_id']): ?>
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold border">
                                                            <?php echo $avatar_tecnico; ?>
                                                        </div>
                                                        <div class="text-xs font-medium text-slate-700"><?php echo htmlspecialchars($nom_tecnico); ?></div>
                                                    </div>
                                            <?php else: ?>
                                                    <span class="text-xs text-slate-400 italic">Sin asignar</span>
                                            <?php endif; ?>
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

<script>
function exportarExcel() {
    // 1. Crear una tabla temporal para la exportación (más limpia)
    let tempTable = document.createElement("table");
    
    // 2. Cabecera limpia
    let thead = document.createElement("thead");
    let trHead = document.createElement("tr");
    let headers = document.querySelectorAll("table thead th");
    headers.forEach(th => {
        let newTh = document.createElement("th");
        newTh.innerText = th.innerText.toUpperCase().trim();
        newTh.style.background = "#f0f0f0";
        newTh.style.fontWeight = "bold";
        newTh.style.border = "1px solid #999";
        newTh.style.padding = "5px";
        trHead.appendChild(newTh);
    });
    thead.appendChild(trHead);
    tempTable.appendChild(thead);

    // 3. Cuerpo limpio (Extraer solo los datos necesarios)
    let tbody = document.createElement("tbody");
    let rows = document.querySelectorAll("table tbody tr");
    
    rows.forEach(row => {
        // Ignorar filas vacías o de carga
        if(row.innerText.includes("Sin resultados")) return;

        let newRow = document.createElement("tr");
        let cells = row.querySelectorAll("td");

        // Si la fila no tiene celdas estándar, la saltamos
        if(cells.length < 6) return; 

        // Columna 1: ID
        let tdId = document.createElement("td");
        tdId.innerText = cells[0].innerText.trim();
        tdId.style.border = "1px solid #ddd";
        newRow.appendChild(tdId);

        // Columna 2: Fecha
        let tdFecha = document.createElement("td");
        // Extraemos fecha y hora limpiando saltos de línea
        tdFecha.innerText = cells[1].innerText.replace(/[\n\r]+/g, ' ').trim();
        tdFecha.style.border = "1px solid #ddd";
        tdFecha.style.textAlign = "center";
        newRow.appendChild(tdFecha);

        // Columna 3: Asunto (Separar Título de Descripción)
        let tdAsunto = document.createElement("td");
        // Intentar buscar selectores específicos si existen, sino usar texto plano
        let titulo = cells[2].querySelector(".font-bold")?.innerText || "";
        let desc = cells[2].querySelector(".text-xs")?.innerText || "";
        // Si no encontramos selectores (caso fallback), usar el texto completo
        if(!titulo) { 
            tdAsunto.innerText = cells[2].innerText.trim(); 
        } else {
            tdAsunto.innerHTML = `<b>${titulo}</b><br><span style='color:#555'>${desc}</span>`;
        }
        tdAsunto.style.border = "1px solid #ddd";
        newRow.appendChild(tdAsunto);

        // Columna 4: Solicitante (Solo nombre, sin 'Solicitante' ni avatares)
        let tdSol = document.createElement("td");
        let divNombre = cells[3].querySelectorAll("div.flex-col span")[0]; // Primer span dentro del div flex-col
        tdSol.innerText = divNombre ? divNombre.innerText.trim() : cells[3].innerText.trim();
        tdSol.style.border = "1px solid #ddd";
        newRow.appendChild(tdSol);

        // Columna 5: Estado (Texto limpio)
        let tdEstado = document.createElement("td");
        tdEstado.innerText = cells[4].innerText.replace(/[\n\r]+/g, ' - ').trim();
        tdEstado.style.border = "1px solid #ddd";
        tdEstado.style.textAlign = "center";
        newRow.appendChild(tdEstado);

        // Columna 6: Técnico (Solo nombre)
        let tdTec = document.createElement("td");
        let divTec = cells[5].querySelectorAll("div.flex-col span")[0];
        let textoTec = divTec ? divTec.innerText.trim() : cells[5].innerText.trim();
        if(textoTec.includes("Sin asignar")) textoTec = "Sin asignar";
        tdTec.innerText = textoTec;
        tdTec.style.border = "1px solid #ddd";
        newRow.appendChild(tdTec);

        tbody.appendChild(newRow);
    });
    tempTable.appendChild(tbody);

    // 4. Exportar como XLS "Falso" (HTML) pero limpio
    let meta = '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    let style = '<style>body { font-family: Arial; } table { border-collapse: collapse; width: 100%; } td, th { vertical-align: top; mso-number-format:"\@"; }</style>'; 
    let html = meta + style + tempTable.outerHTML;
    
    let blob = new Blob([html], { type: "application/vnd.ms-excel" });
    let url = URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.href = url;
    a.download = "Reporte_Gerencial_" + new Date().toISOString().slice(0,10) + ".xls"; // Extensión .xls para abrir en Excel
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>
