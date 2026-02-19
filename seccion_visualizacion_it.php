<?php
/**
 * seccion_visualizacion_it.php - Dashboard de Visualización IT
 * Muestra tarjetas con información completa de cuentas 365
 */

// Obtener lista de empresas para el filtro
$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$filtro_empresa = $_GET['empresa_it'] ?? '';
$filtro_sucursal = $_GET['sucursal_it'] ?? '';
$busqueda_it = $_GET['busqueda_it'] ?? '';

// Obtener sucursales (filtradas si hay empresa seleccionada)
$sql_suc = "SELECT id, nombre FROM sucursales WHERE activa=1";
$params_suc = [];
if ($filtro_empresa) {
    $sql_suc .= " AND empresa_id = ?";
    $params_suc[] = $filtro_empresa;
}
$sql_suc .= " ORDER BY nombre";
$stmt_suc = $pdo->prepare($sql_suc);
$stmt_suc->execute($params_suc);
$sucursales = $stmt_suc->fetchAll(PDO::FETCH_ASSOC);


// Obtener todas las cuentas con información completa
$sql = "
    SELECT r.*, 
           CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
           s.nombre as departamento,
           e.nombre as empresa_nombre
    FROM registros_365 r
    LEFT JOIN personal p ON r.usuario_id = p.id
    LEFT JOIN sucursales s ON p.sucursal_id = s.id
    LEFT JOIN empresas e ON s.empresa_id = e.id
    WHERE 1=1
";

$params = [];

if ($filtro_empresa) {
    $sql .= " AND e.id = ?";
    $params[] = $filtro_empresa;
}

if ($filtro_sucursal) {
    $sql .= " AND s.id = ?";
    $params[] = $filtro_sucursal;
}

if ($busqueda_it) {
    $sql .= " AND (r.email LIKE ? OR CONCAT(p.nombres, ' ', p.apellidos) LIKE ?)";
    $term = "%$busqueda_it%";
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY r.email ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [
    'total' => count($cuentas),
    'activas' => 0,
    'con_credenciales' => 0,
    'con_telefono' => 0
];

foreach ($cuentas as $c) {
    if ($c['estado'] === 'Activo')
        $stats['activas']++;
    if ($c['password_ag'] || $c['pin_windows'])
        $stats['con_credenciales']++;
    if ($c['telefono_principal'])
        $stats['con_telefono']++;
}
?>

<div class="p-6 flex-1">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-gradient-to-br from-cyan-600 to-blue-600 text-white p-3 rounded-xl shadow-lg">
                    <i class="ri-information-line"></i>
                </span>
                Info IT - Visualización Completa
            </h1>
            <p class="text-slate-500 mt-2">Vista detallada de todas las cuentas 365 con credenciales y contactos</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                <input type="hidden" name="view" value="visualizacion_it">

                <div class="w-full md:w-64">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                    <select name="empresa_it" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none bg-white text-sm">
                        <option value="">Todas las Empresas</option>
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $filtro_empresa == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="w-full md:w-64">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                    <select name="sucursal_it"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none bg-white text-sm">
                        <option value="">Todas las Sucursales</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?= $suc['id'] ?>" <?= $filtro_sucursal == $suc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($suc['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Búsqueda</label>
                    <div class="relative">
                        <input type="text" name="busqueda_it" value="<?= htmlspecialchars($busqueda_it) ?>"
                            class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm"
                            placeholder="Buscar por correo o nombre...">
                        <i class="ri-search-line absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors shadow-lg shadow-cyan-500/30 font-medium">
                        Filtrar
                    </button>
                    <?php if ($filtro_empresa || $busqueda_it): ?>
                        <a href="index.php?view=visualizacion_it"
                            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition-colors font-medium">
                            Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="ri-mail-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['total'] ?></h3>
                        <p class="text-xs text-slate-500">Total Cuentas</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                        <i class="ri-checkbox-circle-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['activas'] ?></h3>
                        <p class="text-xs text-slate-500">Activas</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="ri-key-2-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['con_credenciales'] ?></h3>
                        <p class="text-xs text-slate-500">Con Credenciales</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="ri-phone-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['con_telefono'] ?></h3>
                        <p class="text-xs text-slate-500">Con Teléfono</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Tarjetas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($cuentas)): ?>
                <div class="col-span-full bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
                    <i class="ri-inbox-line text-6xl text-slate-300 mb-4 block"></i>
                    <p class="text-slate-400 text-lg">No hay cuentas registradas</p>
                </div>
            <?php else: ?>
                <?php foreach ($cuentas as $c): ?>
                    <div
                        class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all">
                        <!-- Header -->
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
                                <?= $c['estado'] ?>
                                </span>
                                <button onclick='editarRegistro(<?= json_encode($c) ?>)'
                                    class="ml-2 bg-white/20 hover:bg-white/30 text-white p-1 rounded-lg transition-colors"
                                    title="Editar Cuenta">
                                    <i class="ri-edit-line"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div class="p-4 space-y-3">
                            <!-- Licencia -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                    <i class="ri-price-tag-3-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Licencia</p>
                                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['licencia']) ?></p>
                                </div>
                            </div>

                            <!-- Usuario -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="ri-user-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Asignado a</p>
                                    <?php if ($c['usuario_id']): ?>
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['usuario_nombre']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($c['departamento']) ?></p>
                                    <?php else: ?>
                                        <p class="text-slate-400 italic">Sin asignar</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Credenciales -->
                            <?php if ($c['password_ag'] || $c['pin_windows']): ?>
                                <div class="bg-slate-50 rounded-lg p-3 space-y-2">
                                    <p class="text-xs font-bold text-slate-600 uppercase flex items-center gap-1">
                                        <i class="ri-lock-password-line"></i> Credenciales
                                    </p>
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

                            <!-- Contacto -->
                            <?php if ($c['cuenta_gmail'] || $c['telefono_principal'] || $c['telefono_secundario']): ?>
                                <div class="bg-green-50 rounded-lg p-3 space-y-2">
                                    <p class="text-xs font-bold text-green-700 uppercase flex items-center gap-1">
                                        <i class="ri-smartphone-line"></i> Contacto
                                    </p>
                                    <?php if ($c['cuenta_gmail']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-google-fill text-slate-400 text-xs"></i>
                                            <span class="text-xs text-slate-700"><?= htmlspecialchars($c['cuenta_gmail']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($c['telefono_principal']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-phone-line text-slate-400 text-xs"></i>
                                            <span
                                                class="text-xs text-slate-700"><?= htmlspecialchars($c['telefono_principal']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($c['telefono_secundario']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-phone-line text-slate-400 text-xs"></i>
                                            <span
                                                class="text-xs text-slate-500"><?= htmlspecialchars($c['telefono_secundario']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Observaciones -->
                            <?php if ($c['observaciones']): ?>
                                <div class="text-xs text-slate-600 bg-amber-50 p-2 rounded border-l-2 border-amber-400">
                                    <i class="ri-information-line text-amber-600"></i>
                                    <?= htmlspecialchars($c['observaciones']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Fecha -->
                            <?php if ($c['fecha_asignacion']): ?>
                                <div class="text-xs text-slate-500 flex items-center gap-1">
                                    <i class="ri-calendar-check-line"></i>
                                    Asignado: <?= date('d/m/Y', strtotime($c['fecha_asignacion'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Edición Registro 365 -->
<div id="modal-editar-365" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="cerrarModalEditar()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">

                <!-- Header Modal -->
                <div class="bg-slate-50 px-4 py-3 sm:px-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold leading-6 text-slate-800" id="modal-title">
                        <i class="ri-edit-line text-blue-600 mr-2"></i> Editar Cuenta 365
                    </h3>
                    <button type="button" onclick="cerrarModalEditar()"
                        class="text-slate-400 hover:text-red-500 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>

                <!-- Formulario -->
                <form action="index.php" method="POST" id="form-editar-365">
                    <input type="hidden" name="accion" value="editar_registro_365">
                    <!-- Redireccionar de vuelta a esta vista -->
                    <input type="hidden" name="redirect_view" value="visualizacion_it">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">


                    <div class="px-4 py-5 sm:p-6 space-y-4">

                        <!-- Email y Licencia -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email <span
                                        class="text-red-500">*</span></label>
                                <input type="email" name="email" id="edit_email" required
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Licencia</label>
                                <select name="licencia" id="edit_licencia"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                                    <option value="Business Basic">Business Basic</option>
                                    <option value="Business Standard">Business Standard</option>
                                    <option value="Business Premium">Business Premium</option>
                                    <option value="E3">E3</option>
                                    <option value="E5">E5</option>
                                    <option value="Exchange Online">Exchange Online</option>
                                </select>
                            </div>
                        </div>

                        <!-- Empresa y Sucursal -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                                <select name="empresa_id" id="edit_empresa_id"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($empresas as $emp): ?>
                                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                                <select name="sucursal_id" id="edit_sucursal_id"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                                    <option value="">-- Seleccionar --</option>
                                    <!-- Se llenará via JS opcionaaalmente o mostrar todas -->
                                    <?php foreach ($sucursales as $suc): ?>
                                        <option value="<?= $suc['id'] ?>"><?= htmlspecialchars($suc['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Credenciales -->
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">
                                Credenciales</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Password
                                        AG</label>
                                    <input type="text" name="password_ag" id="edit_password_ag"
                                        class="w-full px-2 py-1.5 border border-slate-200 rounded text-sm font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Password
                                        Azure</label>
                                    <input type="text" name="password_azure" id="edit_password_azure"
                                        class="w-full px-2 py-1.5 border border-slate-200 rounded text-sm font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">PIN
                                        Windows</label>
                                    <input type="text" name="pin_windows" id="edit_pin_windows"
                                        class="w-full px-2 py-1.5 border border-slate-200 rounded text-sm font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-700 mb-2 border-b border-slate-200 pb-1">Contacto
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Teléfono
                                        Principal</label>
                                    <input type="text" name="telefono_principal" id="edit_telefono_principal"
                                        class="w-full px-2 py-1.5 border border-slate-200 rounded text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cuenta
                                        Gmail</label>
                                    <input type="text" name="cuenta_gmail" id="edit_cuenta_gmail"
                                        class="w-full px-2 py-1.5 border border-slate-200 rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Estado y Observaciones -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado</label>
                                <select name="estado" id="edit_estado"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="Suspendido">Suspendido</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase mb-1">Observaciones</label>
                                <input type="text" name="observaciones" id="edit_observaciones"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-100">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar Cambios
                        </button>
                        <button type="button" onclick="cerrarModalEditar()"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editarRegistro(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_licencia').value = data.licencia || 'Business Basic';
        document.getElementById('edit_empresa_id').value = data.empresa_id || '';
        document.getElementById('edit_sucursal_id').value = data.sucursal_id || ''; // Podría mejorarse la lógica de filtro

        document.getElementById('edit_password_ag').value = data.password_ag || '';
        document.getElementById('edit_password_azure').value = data.password_azure || '';
        document.getElementById('edit_pin_windows').value = data.pin_windows || '';

        document.getElementById('edit_telefono_principal').value = data.telefono_principal || '';
        document.getElementById('edit_cuenta_gmail').value = data.cuenta_gmail || '';

        document.getElementById('edit_estado').value = data.estado || 'Activo';
        document.getElementById('edit_observaciones').value = data.observaciones || '';

        document.getElementById('modal-editar-365').classList.remove('hidden');
    }

    function cerrarModalEditar() {
        document.getElementById('modal-editar-365').classList.add('hidden');
    }
</script>