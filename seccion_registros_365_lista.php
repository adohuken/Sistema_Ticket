<?php
/**
 * seccion_registros_365_lista.php - Listado de Cuentas Microsoft 365
 */

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_licencia = $_GET['licencia'] ?? '';
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_sucursal = $_GET['sucursal'] ?? '';
$filtro_asignacion = $_GET['asignacion'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Obtener datos para filtros
$empresas = $pdo->query("SELECT id, nombre FROM empresas WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_KEY_PAIR);
$sucursales = $pdo->query("SELECT id, nombre FROM sucursales WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_KEY_PAIR);


// Construir query
$sql = "
    SELECT r.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as usuario_nombre,
           u.sucursal_nombre as departamento,
           e.nombre as empresa_nombre,
           s.nombre as sucursal_nombre
    FROM registros_365 r
    LEFT JOIN vista_personal_completo u ON r.usuario_id = u.id
    LEFT JOIN empresas e ON r.empresa_id = e.id
    LEFT JOIN sucursales s ON r.sucursal_id = s.id
    WHERE 1=1
";

$params = [];

if ($filtro_estado) {
    $sql .= " AND r.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_licencia) {
    $sql .= " AND r.licencia = ?";
    $params[] = $filtro_licencia;
}

if ($filtro_empresa) {
    $sql .= " AND r.empresa_id = ?";
    $params[] = $filtro_empresa;
}

if ($filtro_sucursal) {
    $sql .= " AND r.sucursal_id = ?";
    $params[] = $filtro_sucursal;
}

if ($filtro_asignacion === 'asignado') {
    $sql .= " AND r.usuario_id IS NOT NULL";
} elseif ($filtro_asignacion === 'no_asignado') {
    $sql .= " AND r.usuario_id IS NULL";
}

if ($busqueda) {
    $sql .= " AND (r.email LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

$sql .= " ORDER BY r.email ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas rápidas
$stats = [
    'total' => count($cuentas),
    'activas' => 0,
    'disponibles' => 0
];

foreach ($cuentas as $c) {
    if ($c['estado'] === 'Activo')
        $stats['activas']++;
    if (empty($c['usuario_id']))
        $stats['disponibles']++;
}
?>

<div class="p-6 flex-1">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                    <span class="bg-blue-600 text-white p-3 rounded-xl shadow-lg shadow-blue-500/30">
                        <i class="ri-microsoft-line"></i>
                    </span>
                    Registro de Cuentas 365
                </h1>
                <p class="text-slate-500 mt-2">Gestión de licencias y asignación de cuentas Microsoft 365</p>
            </div>
            <div class="flex gap-2">
                <a href="index.php?view=registros_365_importar"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-3 rounded-xl font-semibold transition-all flex items-center gap-2">
                    <i class="ri-upload-2-line text-xl"></i>
                    Importar
                </a>
                <a href="exportar_registros_365.php?<?= http_build_query($_GET) ?>" target="_blank"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-green-500/30 transition-all flex items-center gap-2">
                    <i class="ri-file-excel-2-line text-xl"></i>
                    Exportar Excel
                </a>
                <a href="index.php?view=registros_365_formulario"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                    <i class="ri-add-line text-xl"></i>
                    Nueva Cuenta
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
                    <i class="ri-mail-line"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800">
                        <?= $stats['total'] ?>
                    </h3>
                    <p class="text-sm text-slate-500">Total Cuentas</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-2xl">
                    <i class="ri-checkbox-circle-line"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800">
                        <?= $stats['activas'] ?>
                    </h3>
                    <p class="text-sm text-slate-500">Activas</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">
                    <i class="ri-user-unfollow-line"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-800">
                        <?= $stats['disponibles'] ?>
                    </h3>
                    <p class="text-sm text-slate-500">Sin Asignar</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                <input type="hidden" name="view" value="registros_365">

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Buscar</label>
                    <div class="relative">
                        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                            class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Email, nombre...">
                    </div>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Asignación</label>
                    <select name="asignacion"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="">Todas</option>
                        <option value="asignado" <?= $filtro_asignacion == 'asignado' ? 'selected' : '' ?>>Asignadas
                        </option>
                        <option value="no_asignado" <?= $filtro_asignacion == 'no_asignado' ? 'selected' : '' ?>>Sin
                            Asignar</option>
                    </select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                    <select name="empresa"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="">Todas</option>
                        <?php foreach ($empresas as $id => $nombre): ?>
                            <option value="<?= $id ?>" <?= $filtro_empresa == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                    <select name="sucursal"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $id => $nombre): ?>
                            <option value="<?= $id ?>" <?= $filtro_sucursal == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nombre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-40">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Licencia</label>
                    <select name="licencia"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="">Todas</option>
                        <option value="Business Basic" <?= $filtro_licencia == 'Business Basic' ? 'selected' : '' ?>>
                            Business Basic</option>
                        <option value="Business Standard" <?= $filtro_licencia == 'Business Standard' ? 'selected' : '' ?>>
                            Business Standard</option>
                        <option value="E3" <?= $filtro_licencia == 'E3' ? 'selected' : '' ?>>E3</option>
                    </select>
                </div>

                <div class="w-32">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado</label>
                    <select name="estado"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                        <option value="">Todos</option>
                        <option value="Activo" <?= $filtro_estado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= $filtro_estado == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="Suspendido" <?= $filtro_estado == 'Suspendido' ? 'selected' : '' ?>>Suspendido
                        </option>
                    </select>
                </div>

                <button type="submit"
                    class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition-colors text-sm font-semibold">
                    Filtrar
                </button>

                <?php if ($filtro_estado || $filtro_licencia || $busqueda || $filtro_empresa || $filtro_sucursal || $filtro_asignacion): ?>
                    <a href="index.php?view=registros_365" class="px-4 py-2 text-slate-600 hover:text-slate-800 text-sm">
                        Limpiar
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Vista de Tarjetas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
            <?php if (empty($cuentas)): ?>
                <div class="col-span-full bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
                    <i class="ri-inbox-line text-6xl text-slate-300 mb-4 block"></i>
                    <p class="text-slate-400 text-lg">No se encontraron cuentas registradas</p>
                </div>
            <?php else: ?>
                <?php foreach ($cuentas as $c): ?>
                    <div
                        class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all group">
                        <!-- Header de la tarjeta -->
                        <div class="bg-gradient-to-br from-blue-600 to-cyan-600 p-4 text-white">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="ri-microsoft-fill text-2xl"></i>
                                        <span class="text-xs font-bold uppercase tracking-wider opacity-90">Microsoft 365</span>
                                    </div>
                                    <h3 class="font-bold text-lg truncate" title="<?= htmlspecialchars($c['email']) ?>">
                                        <?= htmlspecialchars($c['email']) ?>
                                    </h3>
                                </div>
                                <?php
                                $estado_badges = [
                                    'Activo' => 'bg-green-500',
                                    'Inactivo' => 'bg-slate-400',
                                    'Suspendido' => 'bg-red-500'
                                ];
                                $estado_badge = $estado_badges[$c['estado']] ?? 'bg-slate-400';
                                ?>
                                <span class="<?= $estado_badge ?> px-2 py-1 rounded-full text-xs font-bold">
                                    <?= $c['estado'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Contenido de la tarjeta -->
                        <div class="p-4 space-y-3">
                            <!-- Usuario Asignado -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="ri-user-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Asignado a</p>
                                    <?php if ($c['usuario_id']): ?>
                                        <p class="font-semibold text-slate-800">
                                            <?= htmlspecialchars($c['usuario_nombre']) ?>
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            <?= htmlspecialchars($c['departamento']) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-slate-400 italic">Sin asignar</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Licencia -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                    <i class="ri-price-tag-3-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Licencia</p>
                                    <p class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($c['licencia']) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Detalles Colapsables -->
                            <div id="detalles-<?= $c['id'] ?>" class="hidden transition-all duration-300 ease-in-out">
                                <div class="pt-2 space-y-3">
                                    <!-- Credenciales (solo si existen) -->
                                    <?php if ($c['password_ag'] || $c['pin_windows']): ?>
                                        <div class="bg-slate-50 rounded-lg p-3 space-y-2">
                                            <p class="text-xs font-bold text-slate-600 uppercase flex items-center gap-1">
                                                <i class="ri-lock-password-line"></i> Credenciales
                                            </p>
                                            <?php if ($c['password_azure']): ?>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-slate-500">Pass 365:</span>
                                                    <code class="text-xs bg-white px-2 py-1 rounded border border-slate-200 font-mono">
                                                                                                                                        <?= htmlspecialchars($c['password_azure']) ?>
                                                                                                                                    </code>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($c['password_ag']): ?>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-slate-500">Password AG:</span>
                                                    <code class="text-xs bg-white px-2 py-1 rounded border border-slate-200 font-mono">
                                                                                                                                        <?= htmlspecialchars($c['password_ag']) ?>
                                                                                                                                    </code>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($c['pin_windows']): ?>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs text-slate-500">PIN Windows:</span>
                                                    <code class="text-xs bg-white px-2 py-1 rounded border border-slate-200 font-mono">
                                                                                                                                        <?= htmlspecialchars($c['pin_windows']) ?>
                                                                                                                                    </code>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Contacto (solo si existe) -->
                                    <?php if ($c['cuenta_gmail'] || $c['telefono_principal'] || $c['telefono_secundario']): ?>
                                        <div class="bg-green-50 rounded-lg p-3 space-y-2">
                                            <p class="text-xs font-bold text-green-700 uppercase flex items-center gap-1">
                                                <i class="ri-smartphone-line"></i> Contacto
                                            </p>
                                            <?php if ($c['cuenta_gmail']): ?>
                                                <div class="flex flex-col gap-1">
                                                    <div class="flex items-center gap-2">
                                                        <i class="ri-google-fill text-slate-400 text-xs"></i>
                                                        <span class="text-xs text-slate-700">
                                                            <?= htmlspecialchars($c['cuenta_gmail']) ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($c['password_gmail']): ?>
                                                        <div class="flex items-center gap-2 ml-5">
                                                            <i class="ri-key-2-line text-slate-400 text-[10px]"></i>
                                                            <code
                                                                class="text-[10px] bg-white px-1.5 py-0.5 rounded border border-green-200 font-mono text-slate-600">
                                                                                                                                                                    <?= htmlspecialchars($c['password_gmail']) ?>
                                                                                                                                                                </code>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($c['telefono_principal']): ?>
                                                <div class="flex items-center gap-2">
                                                    <i class="ri-phone-line text-slate-400 text-xs"></i>
                                                    <span class="text-xs text-slate-700">
                                                        <?= htmlspecialchars($c['telefono_principal']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($c['telefono_secundario']): ?>
                                                <div class="flex items-center gap-2">
                                                    <i class="ri-phone-line text-slate-400 text-xs"></i>
                                                    <span class="text-xs text-slate-500">
                                                        <?= htmlspecialchars($c['telefono_secundario']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Observaciones (si existen) -->
                                    <?php if ($c['observaciones']): ?>
                                        <div class="text-xs text-slate-600 bg-amber-50 p-2 rounded border-l-2 border-amber-400">
                                            <i class="ri-information-line text-amber-600"></i>
                                            <?= htmlspecialchars($c['observaciones']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Fecha de asignación -->
                                    <?php if ($c['fecha_asignacion']): ?>
                                        <div class="text-xs text-slate-500 flex items-center gap-1">
                                            <i class="ri-calendar-check-line"></i>
                                            Asignado:
                                            <?= date('d/m/Y', strtotime($c['fecha_asignacion'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Footer con acciones -->
                                    <div class="pt-2 border-t border-slate-100 flex justify-end gap-2">
                                        <a href="index.php?view=registros_365_formulario&id=<?= $c['id'] ?>"
                                            class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:text-blue-600 hover:border-blue-600 transition-all text-sm font-medium flex items-center gap-1">
                                            <i class="ri-edit-line"></i> Editar
                                        </a>
                                        <form method="POST" action="index.php"
                                            onsubmit="return confirm('¿Eliminar esta cuenta?');" class="inline">
                                            <input type="hidden" name="accion" value="eliminar_registro_365">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <button type="submit"
                                                class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 hover:text-red-600 hover:border-red-600 transition-all text-sm font-medium flex items-center gap-1">
                                                <i class="ri-delete-bin-line"></i> Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Expansor -->
                        <button onclick="toggleCard365(<?= $c['id'] ?>)"
                            class="w-full py-2 bg-slate-50 hover:bg-slate-100 border-t border-slate-100 text-slate-400 hover:text-blue-500 transition-colors flex items-center justify-center group/btn cursor-pointer">
                            <i id="icon-<?= $c['id'] ?>"
                                class="ri-arrow-down-s-line text-xl transform transition-transform duration-300 group-hover/btn:translate-y-0.5"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <script>
            function toggleCard365(id) {
                const detalles = document.getElementById('detalles-' + id);
                const icon = document.getElementById('icon-' + id);

                if (detalles.classList.contains('hidden')) {
                    // Mostrar
                    detalles.classList.remove('hidden');
                    icon.classList.add('rotate-180');
                } else {
                    // Ocultar
                    detalles.classList.add('hidden');
                    icon.classList.remove('rotate-180');
                }
            }
        </script>
    </div>
</div>