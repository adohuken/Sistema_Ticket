<?php
/**
 * seccion_personal_lista.php - Lista de Personal Multi-Empresa
 * Módulo de Gestión de Personal
 */

// Obtener datos de empresas y sucursales
try {
    $stmt = $pdo->query("SELECT * FROM empresas WHERE activa = 1 ORDER BY nombre");
    $empresas = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM sucursales WHERE activa = 1 ORDER BY nombre");
    $sucursales = $stmt->fetchAll();
} catch (PDOException $e) {
    $empresas = [];
    $sucursales = [];
}

// Determinar contexto de usuario
$es_superadmin = in_array(($_SESSION['usuario_rol'] ?? ''), ['SuperAdmin', 'Admin']);
$usuario_empresa_id = $_SESSION['usuario_empresa_id'] ?? null;
$usuario_id = $_SESSION['usuario_id'] ?? null;

// REFRESCAR PERMISOS (Fix Hot-Reload)
// Consultamos directamente la DB para asegurar que los cambios de permisos se reflejen al instante
$usuario_sucursales_permitidas = [];
$usuario_empresa_asignada_str = ''; // Nueva variable para string 'mastertec', etc.

if (!$es_superadmin && $usuario_id) {
    try {
        // 1. Permisos Sucursales
        $stmt_perms = $pdo->prepare("SELECT sucursal_id FROM usuarios_accesos WHERE usuario_id = ?");
        $stmt_perms->execute([$usuario_id]);
        $usuario_sucursales_permitidas = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);

        // 2. Empresa Asignada — usar empresa_id (columna FK correcta)
        $stmt_emp = $pdo->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
        $stmt_emp->execute([$usuario_id]);
        $usuario_empresa_id_db = $stmt_emp->fetchColumn();

    } catch (Exception $e) {
        $usuario_sucursales_permitidas = $_SESSION['usuario_sucursales_permitidas'] ?? [];
        $usuario_empresa_id_db = null;
    }
} else {
    $usuario_sucursales_permitidas = $_SESSION['usuario_sucursales_permitidas'] ?? [];
    $usuario_empresa_id_db = null;
}
// FILTERING OPTIONS (Dropdowns)
if (!$es_superadmin && !empty($usuario_sucursales_permitidas)) {
    // 1. Filter Branches (Keep only allowed IDs)
    $sucursales = array_filter($sucursales, function($s) use ($usuario_sucursales_permitidas) {
        return in_array($s['id'], $usuario_sucursales_permitidas);
    });
    
    // 2. Filter Companies (Keep only those present in filtered branches)
    $allowed_company_ids = array_unique(array_column($sucursales, 'empresa_id'));
    $empresas = array_filter($empresas, function($e) use ($allowed_company_ids) {
        return in_array($e['id'], $allowed_company_ids);
    });
}


// Filtros
$filtro_empresa = $_GET['empresa'] ?? 'todos';
$filtro_sucursal = $_GET['sucursal'] ?? 'todos';
$filtro_estado = $_GET['estado'] ?? 'Activo';
$busqueda = $_GET['busqueda'] ?? '';

// LÓGICA DE AISLAMIENTO:
if (!$es_superadmin) {
    if (!empty($usuario_sucursales_permitidas)) {
        // Caso 1: Acceso Multi-Sucursal (Prioridad Absoluta)
        // No forzamos empresa única porque pueden venir de varias
        // El filtrado real ocurre en el WHERE
    } elseif ($usuario_empresa_id) {
        // Caso 2: Aislamiento por Empresa (Fallback Legacy)
        $filtro_empresa = $usuario_empresa_id;
    } else {
        // Caso 3: Usuario sin permisos
        // Forzamos un filtro imposible para devolver 0 resultados
        $filtro_empresa = -1;
    }
}

// Construir query con filtros
$sql = "SELECT * FROM vista_personal_completo WHERE 1=1";
$params = [];

if ($filtro_empresa !== 'todos' && $filtro_empresa != -1) {
    $sql .= " AND empresa_id = ?";
    $params[] = $filtro_empresa;
}

// Filtro de Seguridad por Sucursales (RRHH Multi-Sucursal)
if (!$es_superadmin && !empty($usuario_sucursales_permitidas)) {
    $placeholders = implode(',', array_fill(0, count($usuario_sucursales_permitidas), '?'));
    $sql .= " AND sucursal_id IN ($placeholders)";
    $params = array_merge($params, $usuario_sucursales_permitidas);
} elseif ($filtro_empresa == -1) {
    // Seguridad: Usuario sin permisos
    $sql .= " AND 1=0";
}

if ($filtro_sucursal !== 'todos') {
    $sql .= " AND sucursal_id = ?";
    $params[] = $filtro_sucursal;
}

if ($filtro_estado !== 'todos') {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($busqueda)) {
    $sql .= " AND (nombres LIKE ? OR apellidos LIKE ? OR codigo_empleado LIKE ? OR cedula LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

$sql .= " ORDER BY apellidos, nombres";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $personal = $stmt->fetchAll();
} catch (PDOException $e) {
    $personal = [];
    $error_msg = $e->getMessage();
}

// Estadísticas (Cálculo independiente de filtros visuales)
$sql_stats = "SELECT estado, COUNT(*) as total FROM vista_personal_completo WHERE 1=1";
$params_stats = [];

// APLICAR SOLO FILTROS DE SEGURIDAD (Ignorar filtros visuales de UI)
if (!$es_superadmin) {
    if (!empty($usuario_sucursales_permitidas)) {
        // SEGURIDAD NIVEL SUCURSAL (Acceso a específicas, ignora empresa)
        $placeholders = implode(',', array_fill(0, count($usuario_sucursales_permitidas), '?'));
        $sql_stats .= " AND sucursal_id IN ($placeholders)";
        $params_stats = array_merge($params_stats, $usuario_sucursales_permitidas);
    } elseif ($usuario_empresa_id) {
        // SEGURIDAD NIVEL EMPRESA (Solo su propia empresa)
        $sql_stats .= " AND empresa_id = ?";
        $params_stats[] = $usuario_empresa_id;
    } else {
        // SIN PERMISOS ESPECÍFICOS → 0 resultados (no aplica a Admin/SuperAdmin)
        $sql_stats .= " AND 1=0";
    }
}
// Si es Admin/SuperAdmin, ve TOTAL GLOBAL (no aplicamos ningún WHERE adicional)

// Agrupar y Ejecutar
$sql_stats .= " GROUP BY estado";

try {
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute($params_stats);
    $stats_data = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna ['Activo' => 5, 'Inactivo' => 2]
} catch (PDOException $e) {
    $stats_data = [];
}

$personal_activo = $stats_data['Activo'] ?? 0;
$personal_inactivo = $stats_data['Inactivo'] ?? 0;
$personal_retirado = $stats_data['Retirado'] ?? 0;
$personal_suspendido = $stats_data['Suspendido'] ?? 0;
$total_personal = array_sum($stats_data);
?>

<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <?php
            // LÓGICA DE LOGO DINÁMICO
            $logo_url = '';

            // 1. Determinar qué empresa mostrar
            //    Prioridad: filtro activo → empresa_id de la DB (RRHH) → sesión legacy
            $empresa_id_logo = null;

            if (($es_superadmin || !empty($usuario_sucursales_permitidas)) && $filtro_empresa !== 'todos' && $filtro_empresa != -1) {
                // Filtro activo en la UI
                $empresa_id_logo = (int) $filtro_empresa;
            } elseif (!empty($usuario_empresa_id_db)) {
                // Empresa asignada al usuario RRHH (empresa_id FK)
                $empresa_id_logo = (int) $usuario_empresa_id_db;
            } elseif (!empty($usuario_empresa_id)) {
                // Fallback: sesión legacy
                $empresa_id_logo = (int) $usuario_empresa_id;
            }

            // 2. Obtener logo_key desde la tabla empresas y luego la URL desde configuracion_sistema
            if ($empresa_id_logo) {
                try {
                    $stmt_lk = $pdo->prepare("SELECT logo_key FROM empresas WHERE id = ?");
                    $stmt_lk->execute([$empresa_id_logo]);
                    $logo_key = $stmt_lk->fetchColumn();

                    if ($logo_key) {
                        $stmt_logo = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
                        $stmt_logo->execute([$logo_key]);
                        $logo_url = $stmt_logo->fetchColumn() ?: '';
                    }
                } catch (Exception $e) {
                    $logo_url = '';
                }
            }
            ?>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <?php if ($logo_url): ?>
                     <div class="h-14 w-auto min-w-[3.5rem] p-1.5 bg-white rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                        <img src="<?= htmlspecialchars($logo_url) ?>" class="h-full w-auto object-contain max-w-[10rem]" alt="Logo Empresa">
                    </div>
                <?php else: ?>
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="ri-team-line text-xl"></i>
                    </div>
                <?php endif; ?>
                Gestión de Personal
            </h2>
            <p class="text-slate-500 mt-1">Administración de empleados multi-empresa</p>
        </div>
        <!-- SweetAlert2 CDN -->

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <div class="flex items-center gap-3">
            <button onclick="exportarExcel()"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-emerald-600/20 flex items-center gap-2 text-sm">
                <i class="ri-file-excel-line"></i>
                <span>Exportar Excel</span>
            </button>
            <a href="index.php?view=personal_importar"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-2 text-sm">
                <i class="ri-file-upload-line"></i>
                <span>Importar CSV</span>
            </a>
            <a href="index.php?view=personal_nuevo"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-blue-600/20 flex items-center gap-2 text-sm">
                <i class="ri-user-add-line"></i>
                <span>Nuevo Empleado</span>
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <a href="index.php?view=personal&estado=todos" class="block group/card">
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm transition-all hover:shadow-md hover:border-blue-200 cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover/card:scale-110"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 group-hover/card:text-blue-500 transition-colors">Total</p>
                        <h3 class="text-2xl font-bold text-slate-800"><?php echo $total_personal; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover/card:scale-110 transition-transform">
                        <i class="ri-team-line"></i>
                    </div>
                </div>
            </div>
        </a>

        <a href="index.php?view=personal&estado=Activo" class="block group/card">
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm transition-all hover:shadow-md hover:border-emerald-200 cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover/card:scale-110"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 group-hover/card:text-emerald-500 transition-colors">Activos</p>
                        <h3 class="text-2xl font-bold text-emerald-600"><?php echo $personal_activo; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover/card:scale-110 transition-transform">
                        <i class="ri-user-follow-line"></i>
                    </div>
                </div>
            </div>
        </a>

        <a href="index.php?view=personal&estado=Inactivo" class="block group/card">
            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm transition-all hover:shadow-md hover:border-orange-200 cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-orange-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover/card:scale-110"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 group-hover/card:text-orange-500 transition-colors">Inactivos</p>
                        <h3 class="text-2xl font-bold text-orange-600"><?php echo $personal_inactivo; ?></h3>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-xl group-hover/card:scale-110 transition-transform">
                        <i class="ri-user-unfollow-line"></i>
                    </div>
                </div>
            </div>
        </a>


    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 mb-6">
        <form method="GET" action="index.php" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="view" value="personal">
            
            <!-- Búsqueda -->
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                <div class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>"
                        placeholder="Nombre, código, cédula..."
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>

            <!-- Empresa (Visible para SuperAdmin O Multi-Sucursal) -->
            <?php if ($es_superadmin || !empty($usuario_sucursales_permitidas)): ?>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Empresa</label>
                <div class="relative">
                    <i class="ri-building-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="empresa" id="filtro_empresa"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="todos">Todas las empresas</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php else: ?>
                <!-- Input Oculto para mantener el filtro forzado al enviar (si aplica) -->
                <?php if ($filtro_empresa !== 'todos'): ?>
                    <input type="hidden" name="empresa" value="<?php echo htmlspecialchars($filtro_empresa); ?>">
                <?php endif; ?>
                
                <!-- Badge Informativo -->
                <div class="flex-none flex flex-col justify-end pb-2">
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold border border-slate-200">
                        <?php if (!empty($usuario_sucursales_permitidas)): ?>
                            <i class="ri-git-merge-line mr-1 text-purple-500"></i>
                            Acceso: Multi-Sucursal (<?php echo count($usuario_sucursales_permitidas); ?>)
                        <?php else: ?>
                            <i class="ri-building-fill mr-1 text-slate-400"></i>
                            Empresa: <?php 
                                // Mostrar nombre de empresa actual
                                $nombre_empresa_actual = 'Asignada';
                                foreach($empresas as $e) { if($e['id'] == $filtro_empresa) $nombre_empresa_actual = $e['nombre']; }
                                echo htmlspecialchars($nombre_empresa_actual); 
                            ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Sucursal -->
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Sucursal</label>
                <div class="relative">
                    <i class="ri-map-pin-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="sucursal" id="filtro_sucursal"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="todos">Todas las sucursales</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" 
                                data-empresa="<?php echo $suc['empresa_id']; ?>"
                                <?php echo $filtro_sucursal == $suc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Estado -->
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                <div class="relative">
                    <i class="ri-user-star-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="estado"
                        class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="Activo" <?php echo $filtro_estado === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $filtro_estado === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="Suspendido" <?php echo $filtro_estado === 'Suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        <option value="Retirado" <?php echo $filtro_estado === 'Retirado' ? 'selected' : ''; ?>>Retirado</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <button type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2 text-sm">
                <i class="ri-search-line"></i>
                Filtrar
            </button>
            <a href="index.php?view=personal"
                class="p-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg transition-colors"
                title="Limpiar Filtros">
                <i class="ri-refresh-line"></i>
            </a>
        </form>
    </div>

    <!-- Tabla de Personal -->
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Empleado</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Empresa</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Cargo</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Teléfono</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if (empty($personal)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="ri-user-search-line text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-slate-500 font-medium">No se encontró personal</p>
                                    <p class="text-slate-400 text-sm mt-1">Intenta con otros filtros o agrega un nuevo empleado</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($personal as $p): ?>
                            <?php
                            $estado_config = [
                                'Activo' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
                                'Inactivo' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
                                'Suspendido' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
                                'Retirado' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'dot' => 'bg-slate-500']
                            ];
                            $ec = $estado_config[$p['estado']] ?? $estado_config['Activo'];
                            ?>
                            <tr onclick="window.location.href='index.php?view=personal_detalle&id=<?php echo $p['id']; ?>'" 
                                class="hover:bg-slate-50/80 transition-colors group cursor-pointer">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-bold text-slate-500">
                                        <?php echo htmlspecialchars($p['codigo_empleado'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                            <?php echo strtoupper(substr($p['nombres'], 0, 1) . substr($p['apellidos'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                <?php echo htmlspecialchars($p['nombres'] . ' ' . $p['apellidos']); ?>
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                <?php echo htmlspecialchars($p['cedula'] ?? 'Sin cédula'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-building-line text-slate-400"></i>
                                        <span class="text-sm text-slate-700"><?php echo htmlspecialchars($p['empresa_nombre']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-map-pin-line text-slate-400"></i>
                                        <span class="text-sm text-slate-700"><?php echo htmlspecialchars($p['sucursal_nombre']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-slate-700"><?php echo htmlspecialchars($p['cargo'] ?? 'Sin cargo'); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($p['telefono'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $ec['bg'] . ' ' . $ec['text']; ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?php echo $ec['dot']; ?>"></span>
                                        <?php echo htmlspecialchars($p['estado']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="index.php?view=generar_acta_entrega&id=<?php echo $p['id']; ?>" onclick="event.stopPropagation()"
                                            class="w-7 h-7 rounded bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50 transition-all flex items-center justify-center"
                                            title="Imprimir Acta de Entrega">
                                            <i class="ri-printer-line"></i>
                                        </a>
                                        <!-- Botón Ver Detalles eliminado por redundancia -->
                                        <a href="index.php?view=personal_editar&id=<?php echo $p['id']; ?>" onclick="event.stopPropagation()"
                                            class="w-7 h-7 rounded bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-300 hover:bg-emerald-50 transition-all flex items-center justify-center"
                                            title="Editar">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <button onclick="event.stopPropagation(); darDeBaja(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombres'] . ' ' . $p['apellidos'], ENT_QUOTES); ?>')"
                                            class="w-7 h-7 rounded bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-300 hover:bg-red-50 transition-all flex items-center justify-center"
                                            title="Dar de Baja">
                                            <i class="ri-user-unfollow-line"></i>
                                        </button>
                                    </div>
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
// Filtrar sucursales por empresa seleccionada
document.getElementById('filtro_empresa').addEventListener('change', function() {
    const empresaId = this.value;
    const sucursalSelect = document.getElementById('filtro_sucursal');
    const opciones = sucursalSelect.querySelectorAll('option');
    
    opciones.forEach(opcion => {
        if (opcion.value === 'todos') {
            opcion.style.display = 'block';
        } else {
            const empresaOpcion = opcion.getAttribute('data-empresa');
            if (empresaId === 'todos' || empresaOpcion === empresaId) {
                opcion.style.display = 'block';
            } else {
                opcion.style.display = 'none';
            }
        }
    });
    
    // Reset sucursal si no es válida
    if (sucursalSelect.value !== 'todos') {
        const selectedOption = sucursalSelect.querySelector(`option[value="${sucursalSelect.value}"]`);
        if (selectedOption && selectedOption.style.display === 'none') {
            sucursalSelect.value = 'todos';
        }
    }
});

// Exportar a Excel (XLS Bonito)
function exportarExcel() {
    let table = document.querySelector("table");
    if(!table) return;

    let tempTable = document.createElement("table");
    let thead = document.createElement("thead");
    let trHead = document.createElement("tr");
    
    // Copiar headers menos el último (Acciones)
    let headers = table.querySelectorAll("thead th");
    for(let i=0; i < headers.length - 1; i++) {
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
        if(row.innerText.includes("No se encontró personal")) return;

        let newRow = document.createElement("tr");
        let cells = row.querySelectorAll("td");
        
        if(cells.length > 5) {
            for(let i=0; i < cells.length - 1; i++) { // Ignorar última columna
                let td = document.createElement("td");
                td.style.border = "1px solid #ddd";
                let cell = cells[i];

                // Limpieza específica (0:Cod, 1:Empleado, 2:Emp, 3:Suc, 4:Cargo, 5:Tel, 6:Estado)
                if(i === 1) { // Empleado
                     let nombre = cell.querySelector("p.font-semibold")?.innerText || "";
                     let cedula = cell.querySelector("p.text-xs")?.innerText || "";
                     td.innerHTML = `<b>${nombre}</b><br><span style='color:#555'>${cedula}</span>`;
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

    let meta = '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    let style = '<style>body { font-family: Arial; } table { border-collapse: collapse; width: 100%; } td, th { vertical-align: top; mso-number-format:"\@"; }</style>'; 
    let html = meta + style + tempTable.outerHTML;
    
    let blob = new Blob([html], { type: "application/vnd.ms-excel" });
    let url = URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.href = url;
    a.download = "Personal_" + new Date().toISOString().slice(0,10) + ".xls";
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function darDeBaja(id, nombre) {
    // 1. Fetch Assets
    const formData = new FormData();
    formData.append('ajax_action', 'get_empleado_assets');
    formData.append('id', id);

    fetch('index.php?view=personal', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const assets = data.assets;
            let htmlContent = `<div class="text-left mb-4">
                <p class="text-slate-600 mb-2">El colaborador <strong>${nombre}</strong> pasará a estado <span class="text-orange-600 font-bold">Inactivo</span>.</p>`;
            
            if (assets.length > 0) {
                htmlContent += `<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">
                    <p class="text-sm text-orange-800 font-bold mb-2"><i class="ri-alert-line"></i> Se encontraron ${assets.length} activos asignados:</p>
                    <div class="max-h-40 overflow-y-auto border border-orange-100 rounded bg-white">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-orange-100 sticky top-0">
                                <tr>
                                    <th class="p-2">Tipo</th>
                                    <th class="p-2">Marca / Modelo</th>
                                    <th class="p-2">Serial</th>
                                </tr>
                            </thead>
                            <tbody>`;
                
                assets.forEach(a => {
                    htmlContent += `<tr class="border-b border-orange-50">
                        <td class="p-2 font-medium">${a.tipo}</td>
                        <td class="p-2">${a.marca} ${a.modelo}</td>
                        <td class="p-2 font-mono text-slate-500">${a.serial}</td>
                    </tr>`;
                });

                htmlContent += `</tbody></table></div>
                    <p class="text-xs text-orange-700 mt-2">⚠️ Estos activos pasarán a estado <strong>'En Revisión'</strong> automáticamente.</p>
                </div>`;
            } else {
                htmlContent += `<p class="text-sm text-green-600 bg-green-50 p-2 rounded border border-green-200"><i class="ri-check-line"></i> No tiene activos asignados.</p>`;
            }
            
            htmlContent += `</div>`;

            // 2. Show Confirmation Modal
            Swal.fire({
                title: '¿Confirmar Baja?',
                html: htmlContent,
                icon: assets.length > 0 ? 'warning' : 'info',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Sí, dar de baja',
                cancelButtonText: 'Cancelar',
                width: '600px'
            }).then((result) => {
                if (result.isConfirmed) {
                   ejecutarBaja(id);
                }
            });
        } else {
            Swal.fire('Error', 'No se pudo verificar la información del empleado.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

function ejecutarBaja(id) {
    const formData = new FormData();
    formData.append('ajax_action', 'dar_baja_personal');
    formData.append('id', id);

    fetch('index.php?view=personal', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                title: 'Procesado',
                text: data.msg,
                icon: 'success',
                confirmButtonText: 'Aceptar'
            })
            .then(() => location.reload()); 
        } else {
            Swal.fire('Error', data.msg, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}
</script>
