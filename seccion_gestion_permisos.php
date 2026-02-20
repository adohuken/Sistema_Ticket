<?php
/**
 * seccion_gestion_permisos.php - Gestión de permisos por rol y por usuario
 */

// Obtener datos necesarios
$roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$modulos = $pdo->query("SELECT * FROM modulos ORDER BY etiqueta")->fetchAll();
$todos_usuarios = $pdo->query("SELECT u.*, r.nombre as rol_nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.nombre_completo")->fetchAll();

// Obtener permisos actuales por rol
$permisos_por_rol = [];
foreach ($roles as $rol) {
    $stmt = $pdo->prepare("SELECT modulo_id FROM permisos_roles WHERE rol_id = ?");
    $stmt->execute([$rol['id']]);
    $permisos_por_rol[$rol['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Obtener asignaciones de usuario existentes para la tabla resumen
$asignaciones_usuarios = $pdo->query("
    SELECT u.id, u.nombre_completo, r.nombre as rol_nombre, COUNT(pu.modulo_id) as total_permisos
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    JOIN permisos_usuarios pu ON u.id = pu.usuario_id
    GROUP BY u.id
    ORDER BY u.nombre_completo
")->fetchAll();

// Configuración de Grupos (Centralizada)
$grupos_config = [
    'Gestión de Tickets' => ['crear_ticket', 'mis_tickets', 'asignar_tickets', 'mis_tareas'],
    'Administración' => ['gestion_usuarios', 'gestion_permisos', 'gestion_sucursales', 'categorias', 'dashboard'],
    'RRHH & Personal' => ['gestion_personal', 'personal_importar', 'rrhh_altas', 'rrhh_historial', 'cargos'],
    'Inventario & Activos' => ['rrhh_inventario', 'rrhh_registro_equipo', 'rrhh_asignacion_equipos'],
    'Gestión IT' => ['registros_365', 'mantenimiento_equipos', 'visualizacion_it'],
    'Reportes & Estadísticas' => ['reportes', 'estadisticas_globales', 'historial_tecnico', 'seguimiento_tickets'],
    'Sistema & Configuración' => ['configuracion', 'backup_bd', 'restaurar_bd', 'reiniciar_bd']
];

$icon_map = [
    'dashboard' => 'ri-dashboard-3-line',
    'crear_ticket' => 'ri-add-circle-line',
    'mis_tickets' => 'ri-ticket-line',
    'gestion_usuarios' => 'ri-team-line',
    'asignar_tickets' => 'ri-user-received-line',
    'mis_tareas' => 'ri-task-line',
    'reportes' => 'ri-pie-chart-2-line',
    'rrhh_altas' => 'ri-file-user-line',
    'rrhh_bajas' => 'ri-user-unfollow-line',
    'rrhh_historial' => 'ri-file-list-3-line',
    'rrhh_inventario' => 'ri-archive-line',
    'rrhh_registro_equipo' => 'ri-add-box-line',
    'rrhh_asignacion_equipos' => 'ri-user-settings-line',
    'backup_bd' => 'ri-database-2-line',
    'restaurar_bd' => 'ri-refresh-line',
    'reiniciar_bd' => 'ri-restart-line',
    'gestion_permisos' => 'ri-shield-keyhole-line',
    'seguimiento_tickets' => 'ri-line-chart-line',
    'configuracion' => 'ri-settings-3-line',
    'categorias' => 'ri-folder-settings-line',
    'gestion_personal' => 'ri-contacts-book-line',
    'gestion_sucursales' => 'ri-building-line',
    'personal_importar' => 'ri-file-upload-line',
    'estadisticas_globales' => 'ri-bar-chart-grouped-line',
    'historial_tecnico' => 'ri-history-line',
    'mantenimiento_equipos' => 'ri-tools-line',
    'registros_365' => 'ri-microsoft-line',
    'cargos' => 'ri-briefcase-4-line',
    'visualizacion_it' => 'ri-information-line'
];

$label_overrides = ['rrhh_altas' => 'Formulario Alta/Baja (Unificado)'];

// Preparar estructura de módulos (Respetando orden de $grupos_config)
$modulos_por_grupo = [];
foreach (array_keys($grupos_config) as $gName) {
    $modulos_por_grupo[$gName] = [];
}
$modulos_sin_grupo = [];

foreach ($modulos as $m) {
    if ($m['nombre'] === 'rrhh_bajas')
        continue;
    if (isset($label_overrides[$m['nombre']]))
        $m['etiqueta'] = $label_overrides[$m['nombre']];

    $asignado = false;
    foreach ($grupos_config as $nombre_grupo => $mods) {
        if (in_array($m['nombre'], $mods)) {
            $modulos_por_grupo[$nombre_grupo][] = $m;
            $asignado = true;
            break;
        }
    }
    if (!$asignado)
        $modulos_sin_grupo[] = $m;
}

// Filtrar grupos vacíos pero MANTENIENDO el orden
$all_groups = [];
foreach ($modulos_por_grupo as $gName => $gMods) {
    if (!empty($gMods)) {
        $all_groups[$gName] = $gMods;
    }
}

if (!empty($modulos_sin_grupo))
    $all_groups['Otros'] = $modulos_sin_grupo;
?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
            <i class="ri-shield-keyhole-line text-blue-600"></i> Gestión de Permisos
        </h2>
        <p class="text-slate-500 mt-1">Administra los accesos del sistema por Roles o Usuarios Específicos</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div
            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-r shadow-sm flex items-center gap-2">
            <i class="ri-checkbox-circle-line text-xl"></i>
            Permisos actualizados correctamente.
        </div>
    <?php endif; ?>

    <!-- Main Tabs -->
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden" x-data="{ currentTab: 'roles' }">
        <div class="flex border-b border-slate-200 bg-slate-50">
            <button @click="currentTab = 'roles'"
                :class="currentTab === 'roles' ? 'text-blue-600 border-blue-600 bg-white' : 'text-slate-500 border-transparent hover:text-blue-500'"
                class="px-6 py-4 font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="ri-shield-user-line"></i> Permisos por Rol
            </button>
            <button @click="currentTab = 'usuarios'"
                :class="currentTab === 'usuarios' ? 'text-blue-600 border-blue-600 bg-white' : 'text-slate-500 border-transparent hover:text-blue-500'"
                class="px-6 py-4 font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="ri-user-settings-line"></i> Por Usuario
            </button>
            <button @click="currentTab = 'resumen'"
                :class="currentTab === 'resumen' ? 'text-blue-600 border-blue-600 bg-white' : 'text-slate-500 border-transparent hover:text-blue-500'"
                class="px-6 py-4 font-bold border-b-2 transition-all flex items-center gap-2">
                <i class="ri-list-check"></i> Resumen Asignaciones
            </button>
        </div>

        <!-- TAB CONTENT: ROLES -->
        <div x-show="currentTab === 'roles'" class="p-6">
            <div x-data="{ currentRolTab: '<?php echo $roles[0]['id']; ?>' }">
                <!-- Sub-Tabs Roles -->
                <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
                    <?php foreach ($roles as $rol): ?>
                        <button @click="currentRolTab = '<?php echo $rol['id']; ?>'"
                            :class="currentRolTab === '<?php echo $rol['id']; ?>' ? 'bg-blue-600 text-white shadow-blue-200 shadow-lg' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all whitespace-nowrap">
                            <?php echo htmlspecialchars($rol['nombre']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($roles as $rol): ?>
                    <div x-show="currentRolTab === '<?php echo $rol['id']; ?>'">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                            <input type="hidden" name="rol_id" value="<?php echo $rol['id']; ?>">
                            <input type="hidden" name="actualizar_permisos" value="1">

                            <div class="mb-6">
                                <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($rol['nombre']); ?>
                                </h3>
                                <p class="text-slate-500 text-sm"><?php echo htmlspecialchars($rol['descripcion']); ?></p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <?php foreach ($all_groups as $nombre_grupo => $mods_grupo): ?>
                                    <div class="col-span-full mt-4 mb-2 border-b border-slate-100 pb-2">
                                        <h4
                                            class="font-bold text-slate-400 uppercase text-xs tracking-wider flex items-center gap-2">
                                            <i class="ri-apps-line"></i> <?= $nombre_grupo ?>
                                        </h4>
                                    </div>
                                    <?php foreach ($mods_grupo as $modulo):
                                        $checked = in_array($modulo['id'], $permisos_por_rol[$rol['id']]) ? 'checked' : '';
                                        $icon = $icon_map[$modulo['nombre']] ?? 'ri-checkbox-circle-line';
                                        ?>
                                        <label
                                            class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/50 transition-all cursor-pointer bg-white">
                                            <input type="checkbox" name="modulos[]" value="<?php echo $modulo['id']; ?>" <?php echo $checked; ?>
                                                class="mt-1 w-5 h-5 text-blue-600 rounded focus:ring-blue-500 border-slate-300">
                                            <div>
                                                <span
                                                    class="font-semibold text-slate-700 block text-sm"><?php echo htmlspecialchars($modulo['etiqueta']); ?></span>
                                                <p class="text-xs text-slate-400">
                                                    <?php echo htmlspecialchars($modulo['descripcion']); ?>
                                                </p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-slate-100">
                                <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                                    Guardar Permisos Roles
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB CONTENT: POR USUARIO -->
        <div x-show="currentTab === 'usuarios'" class="p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-6 flex items-start gap-3">
                    <i class="ri-information-line text-blue-600 text-xl mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-blue-800">Modo de Asignación Específica</h4>
                        <p class="text-sm text-blue-700">Selecciona un usuario para otorgarle permisos
                            <strong>adicionales</strong> a los de su rol. <br>Los permisos del rol aparecerán bloqueados
                            (gris) y los adicionales marcados en azul.
                        </p>
                    </div>
                </div>

                <!-- Selector de Usuario -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Seleccionar Usuario</label>
                    <div class="flex gap-4">
                        <select id="userSelector"
                            class="flex-1 p-3 rounded-xl border border-slate-300 focus:border-blue-500 outline-none shadow-sm">
                            <option value="">-- Elige un usuario --</option>
                            <?php foreach ($todos_usuarios as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>">
                                    <?php echo htmlspecialchars($usr['nombre_completo']); ?> (Rol:
                                    <?php echo $usr['rol_nombre']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="loadUserPermissions()"
                            class="bg-slate-800 text-white px-6 rounded-xl font-bold hover:bg-slate-700 transition-colors">
                            Cargar
                        </button>
                    </div>
                </div>

                <!-- Container Permisos Usuario (Cargado via AJAX/JS) -->
                <div id="userPermissionsContainer" class="hidden">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                        <input type="hidden" name="accion" value="actualizar_permisos_usuario">
                        <input type="hidden" name="usuario_id" id="form_usuario_id">

                        <div id="permissionsGrid">
                            <!-- JS will populate this -->
                        </div>

                        <div class="flex justify-end pt-6 border-t border-slate-100 mt-6">
                            <button type="submit"
                                class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 flex items-center gap-2">
                                <i class="ri-save-line"></i> Guardar Asignaciones
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: RESUMEN -->
        <div x-show="currentTab === 'resumen'" class="p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Usuarios con Permisos Personalizados</h3>
            <?php if (empty($asignaciones_usuarios)): ?>
                <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                    <i class="ri-user-star-line text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500">No hay usuarios con permisos adicionales asignados.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                <th class="p-4 font-bold border-b border-slate-200">Usuario</th>
                                <th class="p-4 font-bold border-b border-slate-200">Rol Base</th>
                                <th class="p-4 font-bold border-b border-slate-200 text-center">Permisos Extra</th>
                                <th class="p-4 font-bold border-b border-slate-200 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($asignaciones_usuarios as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-4 font-semibold text-slate-700">
                                        <?php echo htmlspecialchars($row['nombre_completo']); ?>
                                    </td>
                                    <td class="p-4">
                                        <span
                                            class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold border border-slate-200">
                                            <?php echo htmlspecialchars($row['rol_nombre']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">
                                            +<?php echo $row['total_permisos']; ?> Permisos
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button
                                            @click="currentTab = 'usuarios'; document.getElementById('userSelector').value = '<?php echo $row['id']; ?>'; loadUserPermissions();"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center justify-end gap-1">
                                            <i class="ri-edit-line"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alpine.js is assumed to be loaded in layout. If not, include it or use vanilla JS for tabs -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // Datos completos pasan a JS para renderizado dinámico
    const allModulesJSON = <?php echo json_encode($all_groups); ?>;
    const permissionsByRoleJSON = <?php echo json_encode($permisos_por_rol); ?>;
    const allUsersJSON = <?php echo json_encode($todos_usuarios); ?>;

    // Función para obtener asignaciones de usuario (necesitaría un endpoint, pero simularemos 
    // recargando la página con un parametro o usando un fetch si implementamos una API simple.
    // Para simplificar sin crear API nueva, incrustamos los datos al cargar si se selecciona uno,
    // o hacemos una llamada ajax a un nuevo endpoint.
    // 
    // Opción rápida: Usar fetch a un mini-script inline o parametro GET.
    // Vamos a usar un simple fetch a index.php con accion especial que devuelva JSON.

    async function loadUserPermissions() {
        const userId = document.getElementById('userSelector').value;
        if (!userId) return;

        const loadBtn = document.querySelector('button[onclick="loadUserPermissions()"]');
        loadBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Cargando...';

        try {
            // Buscamos el rol del usuario seleccionado
            const user = allUsersJSON.find(u => u.id == userId);
            const userRoleId = user.rol_id;
            const rolePerms = permissionsByRoleJSON[userRoleId] || [];

            // Fetch user specific permissions
            const response = await fetch('index.php?action=get_user_perms&user_id=' + userId);
            const userPerms = await response.json(); // Array of module IDs

            renderUserGrid(rolePerms, userPerms, userId);

            document.getElementById('userPermissionsContainer').classList.remove('hidden');
        } catch (e) {
            console.error(e);
            alert('Error al cargar permisos.');
        } finally {
            loadBtn.innerHTML = 'Cargar';
        }
    }

    function renderUserGrid(rolePerms, userPerms, userId) {
        document.getElementById('form_usuario_id').value = userId;
        const grid = document.getElementById('permissionsGrid');
        grid.innerHTML = '';

        // Icon map simple para JS
        const iconMap = <?php echo json_encode($icon_map); ?>;

        for (const [groupName, modules] of Object.entries(allModulesJSON)) {
            const groupDiv = document.createElement('div');
            groupDiv.className = 'mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100';

            groupDiv.innerHTML = `<h4 class="font-bold text-slate-500 mb-3 border-b border-slate-200 pb-2 uppercase text-xs tracking-wider">${groupName}</h4>`;

            const cardsContainer = document.createElement('div');
            cardsContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3';

            modules.forEach(mod => {
                const isRolePerm = rolePerms.includes(mod.id) || rolePerms.includes(String(mod.id));
                const isUserPerm = userPerms.includes(mod.id) || userPerms.includes(String(mod.id));
                const icon = iconMap[mod.nombre] || 'ri-checkbox-circle-line';

                const label = document.createElement('label');
                // Estilo basado en estado
                let opacityClass = isRolePerm ? 'opacity-60 bg-slate-100 cursor-not-allowed border-slate-200' : 'bg-white hover:border-blue-400 cursor-pointer border-slate-200 hover:shadow-md';

                label.className = `flex items-center gap-3 p-3 border rounded-xl transition-all ${opacityClass}`;

                const checkedAttr = (isRolePerm || isUserPerm) ? 'checked' : '';
                const disabledAttr = isRolePerm ? 'disabled' : '';

                // Si es por rol, mostramos badge
                const roleBadge = isRolePerm ? `<span class="ml-auto text-[10px] font-bold bg-slate-200 text-slate-500 px-2 py-0.5 rounded">ROL</span>` : '';

                label.innerHTML = `
                    <input type="checkbox" name="modulos_extra[]" value="${mod.id}" ${checkedAttr} ${disabledAttr}
                        class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500 border-slate-300">
                    <i class="${icon} text-lg text-slate-400"></i>
                    <div class="flex-1">
                        <span class="text-sm font-semibold text-slate-700">${mod.etiqueta}</span>
                    </div>
                    ${roleBadge}
                `;

                cardsContainer.appendChild(label);
            });

            groupDiv.appendChild(cardsContainer);
            grid.appendChild(groupDiv);
        }
    }

</script>