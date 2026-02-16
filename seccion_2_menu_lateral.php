<?php
/**
 * seccion_2_menu_lateral.php - Menú lateral del sistema (DINÁMICO)
 */

// Mapeo de permisos a enlaces del menú
$mapa_enlaces = [
    // Enlaces principales (sin grupo)
    'dashboard' => ['href' => 'index.php?view=dashboard', 'label' => 'Dashboard', 'icon' => 'ri-dashboard-3-line', 'grupo' => ''],
    'crear_ticket' => ['href' => 'index.php?view=crear_ticket', 'label' => 'Nuevo Ticket', 'icon' => 'ri-add-circle-line', 'grupo' => ''],
    'solicitar_licencia' => ['href' => 'index.php?view=solicitud_licencia', 'label' => 'Solicitar Licencia', 'icon' => 'ri-shield-keyhole-line', 'grupo' => ''],
    'mis_tickets' => ['href' => 'index.php?view=mis_tickets', 'label' => 'Mis Tickets', 'icon' => 'ri-ticket-line', 'grupo' => ''],
    'mis_tareas' => ['href' => 'index.php?view=asignados', 'label' => 'Tickets Asignados', 'icon' => 'ri-checkbox-multiple-line', 'grupo' => ''],
    'colaboradores' => ['href' => 'index.php?view=colaboradores', 'label' => 'Directorio', 'icon' => 'ri-team-fill', 'grupo' => ''],

    // Administración
    'gestion_usuarios' => ['href' => 'index.php?view=usuarios', 'label' => 'Gestión Usuarios', 'icon' => 'ri-team-line', 'grupo' => 'Administración'],
    'gestion_personal' => ['href' => 'index.php?view=personal', 'label' => 'Gestión Personal', 'icon' => 'ri-contacts-book-line', 'grupo' => 'RRHH'],
    'gestion_sucursales' => ['href' => 'index.php?view=sucursales', 'label' => 'Gestión Sucursales', 'icon' => 'ri-building-line', 'grupo' => 'Administración'],
    'asignar_tickets' => ['href' => 'index.php?view=asignar', 'label' => 'Asignar Tickets', 'icon' => 'ri-user-received-line', 'grupo' => ''],
    'gestion_permisos' => ['href' => 'index.php?view=permisos', 'label' => 'Gestión de Permisos', 'icon' => 'ri-shield-keyhole-line', 'grupo' => 'Administración'],
    'categorias' => ['href' => 'index.php?view=categorias', 'label' => 'Categorías', 'icon' => 'ri-folder-settings-line', 'grupo' => 'Administración'],

    // Gestión IT
    'registros_365' => ['href' => 'index.php?view=registros_365', 'label' => 'Cuentas 365', 'icon' => 'ri-microsoft-line', 'grupo' => 'Gestión IT'],
    'mantenimiento_equipos' => ['href' => 'index.php?view=mantenimiento_equipos', 'label' => 'Mantenimiento', 'icon' => 'ri-tools-line', 'grupo' => 'Gestión IT'],
    'visualizacion_it' => ['href' => 'index.php?view=visualizacion_it', 'label' => 'Info IT', 'icon' => 'ri-information-line', 'grupo' => 'Gestión IT'],

    // RRHH
    'rrhh_altas' => ['href' => 'index.php?view=rrhh_menu', 'label' => 'Formulario Alta/Baja', 'icon' => 'ri-file-user-line', 'grupo' => 'RRHH'],
    'rrhh_historial' => ['href' => 'index.php?view=historial_rrhh', 'label' => 'Historial', 'icon' => 'ri-file-list-3-line', 'grupo' => 'RRHH'],
    'rrhh_inventario' => ['href' => 'index.php?view=inventario', 'label' => 'Inventario', 'icon' => 'ri-archive-line', 'grupo' => 'RRHH'],
    'rrhh_registro_equipo' => ['href' => 'index.php?view=registro_equipo', 'label' => 'Registro de Equipo', 'icon' => 'ri-add-box-line', 'grupo' => 'RRHH'],
    'rrhh_asignacion_equipos' => ['href' => 'index.php?view=asignacion_equipo', 'label' => 'Asignación de Equipos', 'icon' => 'ri-user-settings-line', 'grupo' => 'RRHH'],
    'cargos' => ['href' => 'index.php?view=cargos', 'label' => 'Gestión de Cargos', 'icon' => 'ri-briefcase-4-line', 'grupo' => 'RRHH'],

    // Reportes
    'reportes' => ['href' => 'index.php?view=reportes_nuevo', 'label' => 'Reportes', 'icon' => 'ri-pie-chart-2-line', 'grupo' => 'Reportes'],
    'seguimiento_tickets' => ['href' => 'index.php?view=seguimiento', 'label' => 'Seguimiento Tickets', 'icon' => 'ri-line-chart-line', 'grupo' => 'Reportes'],

    // Sistema
    'backup_bd' => ['href' => 'index.php?view=backup', 'label' => 'Backup BD', 'icon' => 'ri-database-2-line', 'grupo' => 'Sistema'],
    'restaurar_bd' => ['href' => 'index.php?view=restore', 'label' => 'Restaurar BD', 'icon' => 'ri-refresh-line', 'grupo' => 'Sistema'],
    'reiniciar_bd' => ['href' => 'index.php?view=restart', 'label' => 'Reiniciar BD', 'icon' => 'ri-restart-line', 'grupo' => 'Sistema'],
    'configuracion' => ['href' => 'index.php?view=config', 'label' => 'Configuración', 'icon' => 'ri-settings-3-line', 'grupo' => 'Sistema'],

    // Nuevo Módulo Estadísticas
    'estadisticas_globales' => ['href' => 'index.php?view=estadisticas_globales', 'label' => 'Estadísticas Globales', 'icon' => 'ri-bar-chart-grouped-line', 'grupo' => 'Reportes'],
];

// Obtener permisos del usuario actual (ROL + ESPECÍFICOS)
$permisos_usuario = [];

try {
    // 1. Permisos por ROL
    $stmt = $pdo->prepare("
        SELECT m.nombre 
        FROM permisos_roles pr 
        JOIN modulos m ON pr.modulo_id = m.id 
        JOIN roles r ON pr.rol_id = r.id 
        WHERE r.nombre = ?
    ");
    // $rol_usuario viene del index.php
    $stmt->execute([$rol_usuario ?? '']);
    $permisos_role = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Permisos ESPECÍFICOS de Usuario
    $permisos_extra = [];
    if (isset($_SESSION['usuario_id'])) {
        $stmt_u = $pdo->prepare("
            SELECT m.nombre
            FROM permisos_usuarios pu
            JOIN modulos m ON pu.modulo_id = m.id
            WHERE pu.usuario_id = ?
        ");
        $stmt_u->execute([$_SESSION['usuario_id']]);
        $permisos_extra = $stmt_u->fetchAll(PDO::FETCH_COLUMN);
    }

    // 3. Fusionar ambos
    $permisos_usuario = array_unique(array_merge($permisos_role, $permisos_extra));

} catch (PDOException $e) {
    $permisos_usuario = [];
}

// Construir lista de enlaces visibles
$enlaces_visibles = [];
foreach ($permisos_usuario as $permiso) {
    if (isset($mapa_enlaces[$permiso])) {
        $enlace = $mapa_enlaces[$permiso];

        // Si el permiso mapea a múltiples enlaces (como rrhh_historial)
        if (isset($enlace[0]) && is_array($enlace[0])) {
            foreach ($enlace as $sub_enlace) {
                $enlaces_visibles[] = $sub_enlace;
            }
        } else {
            $enlaces_visibles[] = $enlace;
        }
    }
}

// Organizar por grupos
$enlaces_por_grupo = ['' => []];
foreach ($enlaces_visibles as $enlace) {
    $grupo = $enlace['grupo'];
    if (!isset($enlaces_por_grupo[$grupo])) {
        $enlaces_por_grupo[$grupo] = [];
    }
    $enlaces_por_grupo[$grupo][] = $enlace;
}

// Inyección manual de enlaces obligatorios (sin requerir cambio en BD de permisos)
if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin' || $rol_usuario === 'Tecnico') {
    // 1. Historial Técnico
    if (!isset($enlaces_por_grupo['Reportes'])) {
        $enlaces_por_grupo['Reportes'] = [];
    }
    $existe_historial = false;
    foreach ($enlaces_por_grupo['Reportes'] as $e) {
        if ($e['href'] === 'index.php?view=historial_tecnico')
            $existe_historial = true;
    }
    if (!$existe_historial) {
        $enlaces_por_grupo['Reportes'][] = [
            'href' => 'index.php?view=historial_tecnico',
            'label' => 'Historial Técnico',
            'icon' => 'ri-history-line',
            'grupo' => 'Reportes'
        ];
    }

    // 2. Tickets Asignados (Solo Admin y Técnico)
    if ($rol_usuario != 'SuperAdmin') {
        $existe_tareas = false;
        if (isset($enlaces_por_grupo[''])) {
            foreach ($enlaces_por_grupo[''] as $e) {
                if ($e['href'] === 'index.php?view=asignados')
                    $existe_tareas = true;
            }
        }
        if (!$existe_tareas) {
            // Insertar después de Mis Tickets para orden lógico
            $enlaces_por_grupo[''][] = [
                'href' => 'index.php?view=asignados',
                'label' => 'Tickets Asignados',
                'icon' => 'ri-checkbox-multiple-line',
                'grupo' => ''
            ];
        }
    }
}
?>
<aside
    class="w-72 bg-slate-900 text-slate-300 h-screen fixed inset-y-0 left-0 overflow-y-auto border-r border-slate-800 shadow-2xl z-20 transition-all duration-300">
    <!-- Logo Area -->
    <div
        class="h-20 flex items-center px-8 border-b border-slate-800 bg-slate-900/50 backdrop-blur-sm sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                <i class="ri-ticket-2-fill text-white text-xl"></i>
            </div>
            <div>
                <h1 class="font-bold text-white text-lg leading-tight">TicketSys</h1>
                <p class="text-xs text-slate-500 font-medium">Gestión Inteligente</p>
            </div>
        </div>
    </div>

    <nav class="p-4 mt-4">
        <div class="mb-2 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Menu Principal</div>
        <ul class="space-y-1">
            <?php
            // Renderizar enlaces sin grupo (principales)
            if (!empty($enlaces_por_grupo[''])) {
                foreach ($enlaces_por_grupo[''] as $enlace) {
                    $view_param = '';
                    if (preg_match('/view=([^&]+)/', $enlace['href'], $matches)) {
                        $view_param = $matches[1];
                    }

                    $isActive = (isset($_GET['view']) && $_GET['view'] === $view_param) || (!isset($_GET['view']) && $view_param === 'dashboard');
                    $activeClass = $isActive ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-white text-slate-400';

                    echo "<li>";
                    echo "<a href='" . htmlspecialchars($enlace['href']) . "' class='flex items-center py-3 px-4 rounded-xl transition-all duration-200 group $activeClass'>";
                    echo "<i class='" . $enlace['icon'] . " text-xl mr-3 transition-transform group-hover:scale-110'></i>";
                    echo "<span class='font-medium'>" . htmlspecialchars($enlace['label']) . "</span>";
                    echo "</a>";
                    echo "</li>";
                }
            }


            // Renderizar grupos con título DESPLEGABLE
            foreach ($enlaces_por_grupo as $grupo => $enlaces) {
                if ($grupo === '' || empty($enlaces))
                    continue;

                $grupo_id = strtolower(str_replace(' ', '_', $grupo)); // ID único para cada grupo
            
                echo '<div class="mt-6">';
                // Botón del grupo (clickeable para expandir/contraer)
                echo '<button onclick="toggleGroup(\'' . $grupo_id . '\')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-400 hover:text-slate-300 uppercase tracking-wider transition-colors group">';
                echo '<span>' . htmlspecialchars($grupo) . '</span>';
                echo '<i id="icon_' . $grupo_id . '" class="ri-arrow-down-s-line text-lg transition-transform"></i>';
                echo '</button>';

                // Contenedor de enlaces (inicialmente visible)
                echo '<ul id="group_' . $grupo_id . '" class="space-y-1 mt-2 overflow-hidden transition-all duration-300">';

                foreach ($enlaces as $enlace) {
                    $view_param = '';
                    if (preg_match('/view=([^&]+)/', $enlace['href'], $matches)) {
                        $view_param = $matches[1];
                    }

                    $isActive = isset($_GET['view']) && $_GET['view'] === $view_param;
                    $activeClass = $isActive ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'hover:bg-slate-800 hover:text-white text-slate-400';

                    echo "<li>";
                    echo "<a href='" . htmlspecialchars($enlace['href']) . "' class='flex items-center py-3 px-4 rounded-xl transition-all duration-200 group $activeClass'>";
                    echo "<i class='" . $enlace['icon'] . " text-xl mr-3 transition-transform group-hover:scale-110'></i>";
                    echo "<span class='font-medium'>" . htmlspecialchars($enlace['label']) . "</span>";
                    echo "</a>";
                    echo "</li>";
                }

                echo '</ul>';
                echo '</div>';
            }
            ?>
        </ul>

        <script>
            // Estado de grupos (guardado en localStorage)
            const groupStates = JSON.parse(localStorage.getItem('menuGroupStates') || '{}');

            function toggleGroup(groupId) {
                const group = document.getElementById('group_' + groupId);
                const icon = document.getElementById('icon_' + groupId);

                if (group.style.maxHeight && group.style.maxHeight !== '0px') {
                    // Contraer
                    group.style.maxHeight = '0px';
                    group.style.opacity = '0';
                    icon.style.transform = 'rotate(-90deg)';
                    groupStates[groupId] = false;
                } else {
                    // Expandir
                    group.style.maxHeight = group.scrollHeight + 'px';
                    group.style.opacity = '1';
                    icon.style.transform = 'rotate(0deg)';
                    groupStates[groupId] = true;
                }

                localStorage.setItem('menuGroupStates', JSON.stringify(groupStates));
            }

            // Restaurar estados al cargar
            document.addEventListener('DOMContentLoaded', function () {
                <?php foreach ($enlaces_por_grupo as $grupo => $enlaces) {
                    if ($grupo === '' || empty($enlaces))
                        continue;
                    $grupo_id = strtolower(str_replace(' ', '_', $grupo));
                    echo "const group_$grupo_id = document.getElementById('group_$grupo_id');\n";
                    echo "const icon_$grupo_id = document.getElementById('icon_$grupo_id');\n";
                    echo "if (groupStates['$grupo_id'] === false) {\n";
                    echo "  group_$grupo_id.style.maxHeight = '0px';\n";
                    echo "  group_$grupo_id.style.opacity = '0';\n";
                    echo "  icon_$grupo_id.style.transform = 'rotate(-90deg)';\n";
                    echo "} else {\n";
                    echo "  group_$grupo_id.style.maxHeight = group_$grupo_id.scrollHeight + 'px';\n";
                    echo "}\n";
                } ?>
            });
        </script>

        <!-- User Profile Mini Card at Bottom -->
        <div class="mt-auto pt-8 pb-4">
            <div class="bg-slate-800/50 rounded-xl p-4 border border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-r from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-xs">
                        <?php echo substr($rol_usuario, 0, 2); ?>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($rol_usuario); ?>
                        </p>
                        <p class="text-xs text-slate-500 truncate">En línea</p>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</aside>

<?php
// Contenedor principal con margen izquierdo ajustado (w-72 = 18rem = ml-72)
?>
<div class="ml-72 bg-slate-50/50 transition-all duration-300 relative z-0">
    <!-- Espaciador para el header fijo -->
    <div class="pt-24 px-0 pb-0 w-full">
        <main class="w-full max-w-full mx-0 text-left block">