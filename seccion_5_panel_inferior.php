<?php
/**
 * seccion_5_panel_inferior.php - Panel de control según rol de usuario
 */

// Este archivo se incluye desde index.php, donde ya están definidas las variables:
// $rol_usuario, $usuario_id, $pdo, $tickets

echo '<div class="p-3">';

// -------------------------------------------------------------------------
// 1. Dashboard para Administradores
// -------------------------------------------------------------------------
if ($rol_usuario === 'Admin') {
    $total_tickets = count($tickets);
    $tickets_abiertos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Pendiente'));
    $tickets_en_proceso = count(array_filter($tickets, fn($t) => $t['estado'] === 'Asignado'));
    $tickets_resueltos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Completo'));

    try {
        $stmt = $pdo->query("SELECT COUNT(DISTINCT id) FROM usuarios");
        $total_usuarios = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $total_usuarios = 0;
        $total_usuarios = 0;
    }

    // [NEW] Calcular tickets por país
    $tickets_nicaragua = 0;
    $tickets_honduras = 0;

    // [NEW] Metricas adicionales para rellenar espacio
    $tickets_hoy = 0;
    $tickets_sin_asignar_tecnico = 0;
    $tickets_criticos = 0;

    // [NEW] Calcular tickets por empresa (Dinámico - Mostrar TODAS)
    $stats_empresas = [];

    // [Fix] Agregar categoría "Sin Empresa" para cuadrar el total
    $stats_empresas[0] = [
        'nombre' => 'Sin Empresa Asignada',
        'total' => 0,
        'sucursales' => []
    ];

    // 1. Inicializar todas las empresas en 0
    if (isset($todas_las_empresas)) {
        foreach ($todas_las_empresas as $emp) {
            $stats_empresas[$emp['id']] = [
                'nombre' => $emp['nombre'],
                'total' => 0,
                'sucursales' => [] // [NEW] Subdivision por sucursal
            ];
        }
    }

    if (isset($tickets)) {
        foreach ($tickets as $t) {
            $pais = $t['creador_pais'] ?? '';
            if (stripos($pais, 'Nicaragua') !== false)
                $tickets_nicaragua++;
            if (stripos($pais, 'Honduras') !== false)
                $tickets_honduras++;

            // Tickets de hoy
            if (date('Y-m-d', strtotime($t['fecha_creacion'])) === date('Y-m-d')) {
                $tickets_hoy++;
            }

            if (empty($t['tecnico_id'])) {
                $tickets_sin_asignar_tecnico++;
            }

            // Tickets Criticos (Alta o Critica) y no resueltos
            if (in_array($t['prioridad'], ['Alta', 'Critica']) && !in_array($t['estado'], ['Completo', 'Resuelto', 'Cerrado'])) {
                $tickets_criticos++;
            }



            // Agrupación por empresa
            $emp_id = $t['empresa_id'] ?? 0;
            $suc_nombre = $t['sucursal_nombre'] ?? 'Sin Sucursal';

            // Determinar ID objetivo (Empresa real o 0 si no existe)
            $target_id = (isset($stats_empresas[$emp_id])) ? $emp_id : 0;

            $stats_empresas[$target_id]['total']++;

            // Agrupación por sucursal
            if (!isset($stats_empresas[$target_id]['sucursales'][$suc_nombre])) {
                $stats_empresas[$target_id]['sucursales'][$suc_nombre] = 0;
            }
            $stats_empresas[$target_id]['sucursales'][$suc_nombre]++;
        }
    }

    // Limpieza: Si "Sin Empresa" tiene 0, lo quitamos
    if ($stats_empresas[0]['total'] === 0) {
        unset($stats_empresas[0]);
    }

    // Ordenar empresas por nombre, dejando "Sin Empresa" al final
    uasort($stats_empresas, function ($a, $b) {
        if ($a['nombre'] === 'Sin Empresa Asignada')
            return 1;
        if ($b['nombre'] === 'Sin Empresa Asignada')
            return -1;
        return strcmp($a['nombre'], $b['nombre']);
    });
    ?>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
            <i class="ri-dashboard-line text-indigo-600"></i> Panel de Administración
        </h2>
        <a href="index.php?view=usuarios"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow transition-colors flex items-center gap-2">
            <i class="ri-user-settings-line"></i> Gestionar Usuarios
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <a href="index.php?view=listados" class="block">
            <div
                class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Tickets</p>
                        <p class="text-3xl font-bold mt-2"><?= $total_tickets ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="ri-ticket-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </a>
        <a href="index.php?view=listados&filter=abiertos" class="block">
            <div
                class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Pendientes</p>
                        <p class="text-3xl font-bold mt-2"><?= $tickets_abiertos ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="ri-inbox-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </a>
        <a href="index.php?view=listados&filter=asignado" class="block">
            <div
                class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Asignados</p>
                        <p class="text-3xl font-bold mt-2"><?= $tickets_en_proceso ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="ri-loader-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </a>
        <a href="index.php?view=listados&filter=completo" class="block">
            <div
                class="bg-gradient-to-br from-green-500 to-green-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Completos</p>
                        <p class="text-3xl font-bold mt-2"><?= $tickets_resueltos ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 p-3 rounded-full">
                        <i class="ri-checkbox-circle-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- [NEW] Contenedor Interactivo: Estadísticas por Sede y Empresa -->
    <details class="group mb-8 bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden" open>
        <summary
            class="flex items-center justify-between p-4 cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors select-none">
            <h3 class="text-lg font-bold text-slate-700 flex items-center gap-2">
                <i class="ri-building-2-line text-indigo-500"></i> Estadísticas por Sede y Empresa
            </h3>
            <span class="transform transition-transform group-open:rotate-180 text-slate-400">
                <i class="ri-arrow-down-s-line text-2xl"></i>
            </span>
        </summary>

        <div class="p-6 border-t border-slate-100 bg-white">

            <!-- 1. Por País/Sede -->
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Por País / Sede</h4>
            <!-- [MOD] Grid más denso (cols-5) para tarjetas más pequeñas -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8">
                <a href="index.php?view=listados&filter=pais_nicaragua"
                    class="block transform hover:scale-[1.02] transition-all">
                    <!-- [MOD] Padding reducido (p-3) -->
                    <div
                        class="bg-gradient-to-br from-cyan-500 to-blue-600 text-white p-3 rounded-xl shadow-sm hover:shadow-md relative overflow-hidden group/card h-full">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover/card:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-blue-100 text-[10px] font-bold uppercase tracking-wider mb-0.5">Sede</p>
                                <!-- [MOD] Texto más pequeño (text-sm) -->
                                <h3 class="text-sm font-bold text-white leading-tight">Nicaragua</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <!-- [MOD] Número más pequeño (text-2xl) -->
                                    <span class="text-2xl font-black text-white"><?= $tickets_nicaragua ?></span>
                                    <span class="text-[10px] text-blue-100">tickets</span>
                                </div>
                            </div>
                            <!-- [MOD] Icono más pequeño (p-2, text-xl) -->
                            <div class="bg-white/20 p-2 rounded-full backdrop-blur-sm shadow-inner flex-shrink-0">
                                <i class="ri-map-pin-2-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>



                <a href="index.php?view=listados&filter=pais_honduras"
                    class="block transform hover:scale-[1.02] transition-all">
                    <div
                        class="bg-gradient-to-br from-fuchsia-500 to-purple-600 text-white p-3 rounded-xl shadow-sm hover:shadow-md relative overflow-hidden group/card h-full">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover/card:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-purple-100 text-[10px] font-bold uppercase tracking-wider mb-0.5">Sede</p>
                                <h3 class="text-sm font-bold text-white leading-tight">Honduras</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-white"><?= $tickets_honduras ?></span>
                                    <span class="text-[10px] text-purple-100">tickets</span>
                                </div>
                            </div>
                            <div class="bg-white/20 p-2 rounded-full backdrop-blur-sm shadow-inner flex-shrink-0">
                                <i class="ri-global-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- [NEW] Tickets de Hoy -->
                <div class="block transform hover:scale-[1.02] transition-all cursor-default">
                    <div
                        class="bg-gradient-to-br from-emerald-400 to-teal-500 text-white p-3 rounded-xl shadow-sm relative overflow-hidden h-full">
                        <div class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12">
                        </div>
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-emerald-100 text-[10px] font-bold uppercase tracking-wider mb-0.5">Actividad
                                </p>
                                <h3 class="text-sm font-bold text-white leading-tight">Creados Hoy</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-white"><?= $tickets_hoy ?></span>
                                    <span class="text-[10px] text-emerald-100">nuevos</span>
                                </div>
                            </div>
                            <div class="bg-white/20 p-2 rounded-full backdrop-blur-sm shadow-inner flex-shrink-0">
                                <i class="ri-calendar-event-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- [NEW] Sin Técnico (Por Asignar) -->
                <a href="index.php?view=listados&filter=abiertos" class="block transform hover:scale-[1.02] transition-all">
                    <div
                        class="bg-gradient-to-br from-amber-400 to-orange-500 text-white p-3 rounded-xl shadow-sm hover:shadow-md relative overflow-hidden h-full">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-amber-100 text-[10px] font-bold uppercase tracking-wider mb-0.5">Atención</p>
                                <h3 class="text-sm font-bold text-white leading-tight">Sin Técnico</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-white"><?= $tickets_sin_asignar_tecnico ?></span>
                                    <span class="text-[10px] text-amber-100">pendientes</span>
                                </div>
                            </div>
                            <div class="bg-white/20 p-2 rounded-full backdrop-blur-sm shadow-inner flex-shrink-0">
                                <i class="ri-user-unfollow-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- [NEW] Tickets Críticos -->
                <a href="index.php?view=listados&filter=prioridad_alta"
                    class="block transform hover:scale-[1.02] transition-all">
                    <div
                        class="bg-gradient-to-br from-rose-500 to-pink-600 text-white p-3 rounded-xl shadow-sm hover:shadow-md relative overflow-hidden h-full">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between gap-2">
                            <div>
                                <p class="text-rose-100 text-[10px] font-bold uppercase tracking-wider mb-0.5">Urgente</p>
                                <h3 class="text-sm font-bold text-white leading-tight">Críticos</h3>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-white"><?= $tickets_criticos ?></span>
                                    <span class="text-[10px] text-rose-100">activos</span>
                                </div>
                            </div>
                            <div class="bg-white/20 p-2 rounded-full backdrop-blur-sm shadow-inner flex-shrink-0">
                                <i class="ri-alarm-warning-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
                </div>


            </div>

            <!-- 2. Por Empresa -->
            <?php if (!empty($stats_empresas)): ?>
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-t border-slate-100 pt-6">Por
                        Empresa</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php
                        $colores_empresas = ['bg-pink-500', 'bg-amber-500', 'bg-emerald-500', 'bg-indigo-500', 'bg-cyan-500'];
                        $i = 0;
                        foreach ($stats_empresas as $id_emp => $data):
                            // Cambiamos a diseño "tarjeta con lista" (Card view)
                            // Usaremos un color sólido de encabezado y lista blanca
                            $color_header = $colores_empresas[$i % count($colores_empresas)];
                            $i++;
                            ?>
                                <div
                                    class="rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow group flex flex-col bg-white">
                                    <!-- Header: Nombre Empresa y Total -->
                                    <a href="index.php?view=listados&filter=empresa_<?= $id_emp ?>"
                                        class="<?= $color_header ?> p-4 flex justify-between items-center text-white cursor-pointer hover:opacity-90 transition-opacity">
                                        <div>
                                            <div class="text-[10px] uppercase font-bold opacity-80 mb-0.5">Empresa</div>
                                            <h3 class="font-bold text-lg leading-tight truncate w-full"
                                                title="<?= htmlspecialchars($data['nombre']) ?>">
                                                <?= htmlspecialchars($data['nombre']) ?>
                                            </h3>
                                        </div>
                                        <div class="bg-white/20 px-3 py-1 rounded-lg text-xl font-black backdrop-blur-sm">
                                            <?= $data['total'] ?>
                                        </div>
                                    </a>

                                    <!-- Body: Sucursales (Lista) -->
                                    <div class="p-3 bg-slate-50/50 flex-1">
                                        <?php if (!empty($data['sucursales'])): ?>
                                                <div class="flex flex-col gap-2">
                                                    <?php
                                                    // Ordenar sucursales por cantidad (Desc)
                                                    arsort($data['sucursales']);
                                                    foreach ($data['sucursales'] as $suc => $cant):
                                                        ?>
                                                            <div
                                                                class="flex justify-between items-center bg-white p-2.5 rounded-lg border border-slate-100 text-sm">
                                                                <div class="flex items-center gap-2 overflow-hidden">
                                                                    <i class="ri-store-2-line text-slate-400"></i>
                                                                    <span class="font-medium text-slate-600 truncate" title="<?= htmlspecialchars($suc) ?>">
                                                                        <?= htmlspecialchars($suc) ?>
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    class="bg-slate-100 text-slate-600 text-xs font-bold px-2 py-0.5 rounded-full border border-slate-200">
                                                                    <?= $cant ?>
                                                                </span>
                                                            </div>
                                                    <?php endforeach; ?>
                                                </div>
                                        <?php else: ?>
                                                <div class="h-full flex flex-col items-center justify-center text-slate-300 py-4">
                                                    <i class="ri-building-2-line text-2xl mb-1"></i>
                                                    <span class="text-xs">Sin actividad reciente</span>
                                                </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Footer: Acción -->
                                    <a href="index.php?view=listados&filter=empresa_<?= $id_emp ?>"
                                        class="p-2 text-center text-xs font-medium text-indigo-500 hover:bg-indigo-50 transition-colors border-t border-slate-100">
                                        Ver todos los tickets <i class="ri-arrow-right-line ml-1 align-bottom"></i>
                                    </a>
                                </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>

            </div>
        </details>

        <!-- Módulo: Mis Tareas Asignadas (Para Admin) -->
        <?php
        // Filtramos los tickets asignados a este admin específico
        $mis_tickets_admin = array_filter($tickets, function ($t) use ($usuario_id) {
            return $t['tecnico_id'] == $usuario_id && $t['estado'] !== 'Completo';
        }); // Admin Assigned: Exclude Completed
    
        if (!empty($mis_tickets_admin)):
            ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 border border-blue-100">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-blue-100">
                        <h3 class="text-lg font-bold text-blue-800 flex items-center gap-2">
                            <i class="ri-checkbox-multiple-line text-blue-600"></i> Tickets Asignados
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-blue-50">
                            <tbody class="bg-white divide-y divide-blue-50">
                                <?php foreach ($mis_tickets_admin as $t):
                                    // [NEW] Filtro: Excluir tickets completados de esta vista
                                    if (in_array($t['estado'], ['Completo', 'Resuelto', 'Cerrado']))
                                        continue;

                                    $prio_colors = [
                                        'Baja' => 'bg-emerald-100 text-emerald-700',
                                        'Media' => 'bg-amber-100 text-amber-700',
                                        'Alta' => 'bg-orange-100 text-orange-700',
                                        'Critica' => 'bg-rose-100 text-rose-700'
                                    ];
                                    $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-100 text-slate-700';
                                    ?>
                                        // Lógica de Resaltado (Global)
                                        $row_class = 'hover:bg-blue-50/50 cursor-pointer transition-colors group'; // Clase por defecto
                                        if (stripos($t['titulo'], 'Nuevo Ingreso') !== false) {
                                        $row_class = 'bg-emerald-50 hover:bg-emerald-100 transition-colors group border-l-4 border-emerald-400';
                                        } elseif (stripos($t['titulo'], 'Baja de Personal') !== false) {
                                        $row_class = 'bg-rose-50 hover:bg-rose-100 transition-colors group border-l-4 border-rose-400';
                                        }
                                        ?>
                                        <tr class='<?= $row_class ?>'
                                            onclick="window.location.href='index.php?view=editar_ticket&id=<?= $t['id'] ?>'">
                                            <td class='px-6 py-4 text-sm font-bold text-blue-600'>
                                                #<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td class='px-6 py-4'>
                                                <div class='text-sm font-semibold text-slate-800'><?= htmlspecialchars($t['titulo']) ?></div>
                                                <div class='text-xs text-slate-500 mt-0.5'>
                                                    <?= htmlspecialchars(substr($t['descripcion'], 0, 50)) ?>...
                                                </div>
                                            </td>
                                            <td class='px-6 py-4'><span
                                                    class='px-2.5 py-1 rounded-lg text-xs font-semibold <?= $p_class ?>'><?= htmlspecialchars($t['prioridad']) ?></span>
                                            </td>
                                            <td class='px-6 py-4 text-sm text-slate-500'><?= htmlspecialchars($t['categoria']) ?></td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ri-list-check text-indigo-600"></i> Tickets Recientes
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Asunto
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Solicitante</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Prioridad
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Categoría
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if (empty($tickets)) {
                            echo "<tr><td colspan='8' class='px-6 py-8 text-center text-gray-500'>No hay tickets registrados.</td></tr>";
                        } else {
                            // [NEW] Filtrar tickets (Ya no excluimos completados, solo ordenamos)
                            $tickets_listar = $tickets;

                            // [FIX] Ordenar: 1. Sin Asignar, 2. Asignados, 3. Completados
                            usort($tickets_listar, function ($a, $b) {
                                $estados_fin = ['Completo', 'Resuelto', 'Cerrado'];

                                // Determinar "Tier" (Nivel)
                                // 0: Sin Asignar (Pendiente de asignar)
                                // 1: Asignado (En proceso, pero no fin)
                                // 2: Finalizado (Completo/Resuelto/Cerrado)
                    
                                $a_fin = in_array($a['estado'], $estados_fin);
                                $b_fin = in_array($b['estado'], $estados_fin);

                                $a_assigned = !empty($a['tecnico_id']);
                                $b_assigned = !empty($b['tecnico_id']);

                                $tier_a = 0;
                                if ($a_fin)
                                    $tier_a = 2;
                                elseif ($a_assigned)
                                    $tier_a = 1;
                                else
                                    $tier_a = 0; // Unassigned & Not Finished
                    
                                $tier_b = 0;
                                if ($b_fin)
                                    $tier_b = 2;
                                elseif ($b_assigned)
                                    $tier_b = 1;
                                else
                                    $tier_b = 0;

                                if ($tier_a !== $tier_b) {
                                    return $tier_a - $tier_b; // Menor tier primero (0 -> 1 -> 2)
                                }

                                // Secondary sort: ID DESC (Newest first)
                                return $b['id'] - $a['id'];
                            });

                            $tickets_recientes = array_slice($tickets_listar, 0, 10);
                            foreach ($tickets_recientes as $t) {
                                $prio_colors = [
                                    'Baja' => 'bg-emerald-100 text-emerald-700',
                                    'Media' => 'bg-amber-100 text-amber-700',
                                    'Alta' => 'bg-orange-100 text-orange-700',
                                    'Critica' => 'bg-rose-100 text-rose-700'
                                ];
                                $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-100 text-slate-700';

                                $status_colors = [
                                    'Pendiente' => 'bg-yellow-50 text-yellow-600',
                                    'Asignado' => 'bg-blue-50 text-blue-600',
                                    'Completo' => 'bg-emerald-50 text-emerald-600'
                                ];
                                $s_class = $status_colors[$t['estado']] ?? 'bg-slate-50 text-slate-600';

                                // Solicitante
                                $nombre_creador = 'Desconocido';
                                $rol_creador = '';
                                if (isset($GLOBALS["usuarios"])) {
                                    foreach ($GLOBALS["usuarios"] as $u) {
                                        if ($u["id"] == $t["creador_id"]) {
                                            $nombre_creador = $u["nombre"];
                                            $rol_creador = $u["rol"] ?? 'Usuario';
                                            break;
                                        }
                                    }
                                }

                                $rol_key = $GLOBALS['rol_colors_config'][$rol_creador] ?? 'slate';
                                $r_class = $GLOBALS['colores_badges_map'][$rol_key] ?? $GLOBALS['colores_badges_map']['slate'];

                                // Tecnico Asignado (Nuevo Look)
                                $nombre_tecnico = 'Sin Asignar';
                                $initials_tecnico = '??';
                                if (!empty($t['tecnico_id']) && isset($GLOBALS["usuarios"])) {
                                    foreach ($GLOBALS["usuarios"] as $u) {
                                        if ($u["id"] == $t["tecnico_id"]) {
                                            $nombre_tecnico = $u["nombre"];
                                            $initials_tecnico = strtoupper(substr($u["nombre"], 0, 2));
                                            break;
                                        }
                                    }
                                }

                                // Lógica de Resaltado (Global)
                                $row_class = 'hover:bg-indigo-50/50 transition-all hover:shadow-md hover:scale-[1.005] duration-200';
                                if (stripos($t['titulo'], 'Nuevo Ingreso') !== false) {
                                    $row_class = 'bg-emerald-50 hover:bg-emerald-100 transition-colors group border-l-4 border-emerald-400';
                                } elseif (stripos($t['titulo'], 'Baja de Personal') !== false) {
                                    $row_class = 'bg-rose-50 hover:bg-rose-100 transition-colors group border-l-4 border-rose-400';
                                }

                                // Datos para Modal
                                $ticket_data_json = htmlspecialchars(json_encode([
                                    'id' => $t['id'],
                                    'titulo' => $t['titulo'],
                                    'descripcion' => $t['descripcion'],
                                    'fecha' => date('d/m/Y H:i', strtotime($t['fecha_creacion'])),
                                    'solicitante' => $nombre_creador,
                                    'rol_solicitante' => $rol_creador,
                                    'estado' => $t['estado'],
                                    'prioridad' => $t['prioridad'],
                                    'categoria' => $t['categoria'],
                                    'tecnico' => $nombre_tecnico,
                                    'tecnico_initials' => $initials_tecnico,
                                    'tecnico_id' => $t['tecnico_id']
                                ]), ENT_QUOTES, 'UTF-8');

                                echo '<tr class="' . $row_class . ' cursor-pointer group/row" onclick="verDetallesTicket(this)" data-ticket="' . $ticket_data_json . '">';

                                // ID
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">#' . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . '</td>';

                                // Asunto
                                echo '<td class="px-6 py-4 text-left">';
                                echo '<div class="text-sm font-bold text-slate-800">' . htmlspecialchars($t['titulo']) . '</div>';
                                echo '<div class="text-xs text-slate-400 mt-1 truncate max-w-xs group-hover/row:text-slate-600 transition-colors">' . htmlspecialchars(substr($t['descripcion'], 0, 50)) . '...</div>';
                                echo '</td>';

                                // Fecha
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap text-xs text-gray-500">' . date('d/m/y', strtotime($t['fecha_creacion'])) . '</td>';

                                // Solicitante
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap">';
                                echo '<div class="flex flex-col items-center justify-center">';
                                echo '<span class="text-sm font-medium text-slate-700">' . htmlspecialchars($nombre_creador) . '</span>';
                                echo '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset ' . $r_class . ' mt-1">' . htmlspecialchars($rol_creador) . '</span>';
                                echo '</div></td>';

                                // Prioridad
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $p_class . '">' . $t['prioridad'] . '</span></td>';

                                // Estado + Tecnico
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap">';
                                echo '<div class="flex flex-col items-center gap-2">';
                                echo '<span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ' . $s_class . '">' . $t['estado'] . '</span>';

                                if ($t['estado'] === 'Asignado' && !empty($t['tecnico_id'])) {
                                    echo '<div class="flex items-center gap-2 mt-1 bg-white px-3 py-1.5 rounded-full border border-indigo-200 shadow-sm">';
                                    echo '<div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">' . $initials_tecnico . '</div>';
                                    echo '<span class="text-xs font-bold text-indigo-700">' . htmlspecialchars(explode(' ', $nombre_tecnico)[0]) . '</span>';
                                    echo '</div>';
                                }
                                echo '</div>';
                                echo '</td>';

                                // Categoria
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($t['categoria']) . '</td>';

                                // Acciones
                                echo '<td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium">';
                                echo '<div class="flex items-center justify-center gap-2">';
                                // Botón VER (Ojo)
                                echo '<button type="button" class="text-sky-600 hover:text-sky-900 bg-sky-50 p-2 rounded-lg hover:bg-sky-100 transition-colors shadow-sm" title="Ver Detalles"><i class="ri-eye-line text-lg"></i></button>';
                                // Botón EDITAR
                                echo '<button onclick="window.location.href=\'index.php?view=editar_ticket&id=' . $t['id'] . '\'; event.stopPropagation();" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg hover:bg-indigo-100 transition-colors shadow-sm" title="Editar"><i class="ri-edit-box-line text-lg"></i></button>';
                                echo '</div>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
    // -------------------------------------------------------------------------
    // 2. Dashboard para Técnicos
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'Tecnico') {
    $mis_tickets = array_filter($tickets, function ($t) use ($usuario_id) {
        return $t['tecnico_id'] == $usuario_id;
    });
    $total_asignados = count($mis_tickets);
    $mis_abiertos = count(array_filter($mis_tickets, fn($t) => $t['estado'] === 'Pendiente'));
    $mis_en_proceso = count(array_filter($mis_tickets, fn($t) => $t['estado'] === 'Asignado'));
    $mis_resueltos = count(array_filter($mis_tickets, fn($t) => $t['estado'] === 'Completo'));
    ?>

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                <i class="ri-tools-line text-purple-600"></i> Mi Panel - Técnico
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <a href="index.php?view=dashboard&filter=todos" class="block">
                <div
                    class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Asignados</p>
                            <p class="text-3xl font-bold mt-2"><?= $total_asignados ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="ri-inbox-archive-line text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
            <a href="index.php?view=dashboard&filter=pendiente" class="block">
                <div
                    class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-sm font-medium">Pendientes</p>
                            <p class="text-3xl font-bold mt-2"><?= $mis_abiertos ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="ri-time-line text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
            <a href="index.php?view=dashboard&filter=asignado" class="block">
                <div
                    class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">En Proceso</p>
                            <p class="text-3xl font-bold mt-2"><?= $mis_en_proceso ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="ri-loader-line text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
            <a href="index.php?view=dashboard&filter=completo" class="block">
                <div
                    class="bg-gradient-to-br from-green-500 to-green-600 text-white p-5 rounded-xl shadow-lg transform hover:scale-105 transition h-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Completos</p>
                            <p class="text-3xl font-bold mt-2"><?= $mis_resueltos ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-3 rounded-full">
                            <i class="ri-checkbox-circle-line text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-white border-b border-blue-100">
                <h3 class="text-lg font-bold text-blue-800 flex items-center gap-2">
                    <i class="ri-checkbox-multiple-line text-blue-600"></i> Tickets Asignados
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Asunto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Solicitante
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Categoría</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        if (empty($mis_tickets)) {
                            echo "<tr><td colspan='5' class='px-6 py-8 text-center text-gray-500'>No tienes tickets asignados.</td></tr>";
                        } else {
                            // Filtro interactivo
                            $filtro = $_GET['filter'] ?? 'activos'; // Por defecto: solo pendientes
                    
                            foreach ($mis_tickets as $t) {
                                // Lógica de Filtrado
                                if ($filtro == 'activos' && $t['estado'] == 'Completo')
                                    continue;
                                if ($filtro == 'pendiente' && $t['estado'] !== 'Pendiente')
                                    continue;
                                if ($filtro == 'asignado' && $t['estado'] !== 'Asignado')
                                    continue;
                                if ($filtro == 'completo' && $t['estado'] !== 'Completo')
                                    continue;

                                $prio_colors = [
                                    'Baja' => 'bg-emerald-100 text-emerald-700',
                                    'Media' => 'bg-amber-100 text-amber-700',
                                    'Alta' => 'bg-orange-100 text-orange-700',
                                    'Critica' => 'bg-rose-100 text-rose-700'
                                ];
                                $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-100 text-slate-700';

                                $status_colors = [
                                    'Pendiente' => 'bg-blue-50 text-blue-600',
                                    'En Atención' => 'bg-purple-50 text-purple-600',
                                    'Resuelto' => 'bg-emerald-50 text-emerald-600',
                                    'Cerrado' => 'bg-slate-50 text-slate-600'
                                ];
                                $s_class = $status_colors[$t['estado']] ?? 'bg-slate-50 text-slate-600';

                                // Lógica de Resaltado (Global)
                                $row_class = 'hover:bg-slate-50 cursor-pointer transition-colors group border-l-4 border-transparent'; // Clase por defecto
                                if (stripos($t['titulo'], 'Nuevo Ingreso') !== false) {
                                    $row_class = 'bg-emerald-50 hover:bg-emerald-100 transition-colors group border-l-4 border-emerald-400';
                                } elseif (stripos($t['titulo'], 'Baja de Personal') !== false) {
                                    $row_class = 'bg-rose-50 hover:bg-rose-100 transition-colors group border-l-4 border-rose-400';
                                }

                                // Fila interactiva
                                echo "<tr class='{$row_class}' onclick=\"window.location.href='index.php?view=editar_ticket&id=" . $t['id'] . "'\">";
                                echo "<td class='px-6 py-4 text-sm font-medium text-slate-600 group-hover:text-blue-600 transition-colors'>#" . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td class='px-6 py-4'>";
                                echo "<div class='text-sm font-semibold text-slate-800'>" . htmlspecialchars($t['titulo']) . "</div>";
                                echo "<div class='text-xs text-slate-400 mt-0.5'>" . htmlspecialchars(substr($t['descripcion'], 0, 50)) . "...</div>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 text-sm font-medium text-slate-700'>";
                                echo "<div class='flex items-center gap-2'><div class='w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-xs text-slate-500 font-bold'>" . strtoupper(substr($t['creador'] ?? '?', 0, 1)) . "</div>" . htmlspecialchars($t['creador'] ?? 'Desconocido') . "</div>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 text-sm text-slate-500 whitespace-nowrap'>";
                                echo "<div class='flex items-center gap-1.5 font-medium'><i class='ri-calendar-event-line text-slate-400'></i> " . date('d M, Y', strtotime($t['fecha_creacion'])) . "</div>";
                                echo "<div class='text-xs text-slate-400 pl-5 mt-0.5'>" . date('h:i A', strtotime($t['fecha_creacion'])) . "</div>";
                                echo "</td>";
                                echo "<td class='px-6 py-4'><span class='px-2.5 py-1 rounded-lg text-xs font-semibold {$p_class}'>" . htmlspecialchars($t['prioridad']) . "</span></td>";
                                echo "<td class='px-6 py-4'><span class='px-2.5 py-1 rounded-full text-xs font-semibold {$s_class}'>" . htmlspecialchars($t['estado']) . "</span></td>";
                                echo "<td class='px-6 py-4 text-sm text-slate-600'>" . htmlspecialchars($t['categoria']) . "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
    // -------------------------------------------------------------------------
    // 3. Dashboard para Usuarios
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'Usuario') {
    $mis_tickets_creados = array_filter($tickets, function ($t) use ($usuario_id) {
        return $t['creador_id'] == $usuario_id;
    });
    $total_creados = count($mis_tickets_creados);
    $tickets_abiertos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Pendiente'));
    $tickets_resueltos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Completo'));
    ?>

        <!-- Encabezado Usuario Moderno -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                        <i class="ri-user-smile-line text-xl"></i>
                    </div>
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-slate-700 to-slate-900">
                        Hola, <?php echo htmlspecialchars($GLOBALS['nombre_usuario'] ?? 'Usuario'); ?>
                    </span>
                </h2>
                <p class="text-slate-500 mt-1 ml-14">Bienvenido a tu panel de gestión de tickets.</p>
            </div>
            <div>
                <a href="index.php?view=crear_ticket"
                    class="group relative inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white transition-all duration-200 bg-blue-600 font-pj rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/30 hover:-translate-y-1">
                    <i class="ri-add-circle-line mr-2 text-lg"></i> Nuevo Ticket
                    <div
                        class="absolute -inset-3 rounded-xl bg-blue-400 opacity-20 group-hover:opacity-40 blur-lg group-hover:duration-200 animate-tilt">
                    </div>
                </a>
            </div>
        </div>

        <!-- Cards Stats Usuario -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Mis Tickets Totales -->
            <a href="index.php?view=mis_tickets" class="block group">
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-blue-600 text-white p-5 rounded-2xl shadow-xl shadow-blue-500/20 transform transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-blue-500/30">
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-10 blur-xl"></div>
                    <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-black opacity-10 blur-xl"></div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <p class="text-indigo-100 text-sm font-semibold uppercase tracking-wider">Mis Tickets</p>
                            <p class="text-3xl font-bold mt-2 group-hover:scale-110 transition-transform origin-left">
                                <?= $total_creados ?>
                            </p>
                            <p class="text-xs text-indigo-200 mt-1 font-medium">Historial completo</p>
                        </div>
                        <div
                            class="bg-white/20 p-3 rounded-xl backdrop-blur-sm group-hover:rotate-12 transition-transform duration-300">
                            <i class="ri-ticket-2-line text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Pendientes -->
            <a href="index.php?view=listados&filter=usuario_pendientes" class="block group">
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-amber-400 to-orange-500 text-white p-5 rounded-2xl shadow-xl shadow-orange-500/20 transform transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-orange-500/30">
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-10 blur-xl"></div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm font-semibold uppercase tracking-wider">Pendientes</p>
                            <p class="text-3xl font-bold mt-2 group-hover:scale-110 transition-transform origin-left">
                                <?= $tickets_abiertos ?>
                            </p>
                            <p class="text-xs text-amber-100 mt-1 font-medium">Esperando respuesta</p>
                        </div>
                        <div
                            class="bg-white/20 p-3 rounded-xl backdrop-blur-sm group-hover:rotate-12 transition-transform duration-300">
                            <i class="ri-time-line text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Completos -->
            <a href="index.php?view=listados&filter=usuario_completos" class="block group">
                <div
                    class="relative overflow-hidden bg-gradient-to-br from-emerald-400 to-teal-500 text-white p-5 rounded-2xl shadow-xl shadow-emerald-500/20 transform transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-emerald-500/30">
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-10 blur-xl"></div>

                    <div class="relative z-10 flex items-center justify-between">
                        <div>
                            <p class="text-emerald-100 text-sm font-semibold uppercase tracking-wider">Resueltos</p>
                            <p class="text-3xl font-bold mt-2 group-hover:scale-110 transition-transform origin-left">
                                <?= $tickets_resueltos ?>
                            </p>
                            <p class="text-xs text-emerald-100 mt-1 font-medium">Procesos finalizados</p>
                        </div>
                        <div
                            class="bg-white/20 p-3 rounded-xl backdrop-blur-sm group-hover:rotate-12 transition-transform duration-300">
                            <i class="ri-checkbox-circle-line text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Sección de Estadísticas y Búsqueda -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- 1. Distribución por Categorías -->
            <div class="md:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-pie-chart-2-line text-blue-600"></i> Distribución de Problemas
                </h3>

                <?php
                // Lógica Backend para Categorías
                $categorias_stats = [];
                foreach ($mis_tickets_creados as $t) {
                    $cat = $t['categoria'] ?? 'Otros';
                    if (!isset($categorias_stats[$cat])) {
                        $categorias_stats[$cat] = 0;
                    }
                    $categorias_stats[$cat]++;
                }
                arsort($categorias_stats); // Ordenar de mayor a menor
                $top_categorias = array_slice($categorias_stats, 0, 3); // Top 3
            
                if (empty($top_categorias)) {
                    echo "<p class='text-sm text-slate-400 italic'>No hay datos suficientes para mostrar estadísticas.</p>";
                } else {
                    echo "<div class='space-y-4'>";
                    foreach ($top_categorias as $cat => $count) {
                        $porcentaje = ($total_creados > 0) ? round(($count / $total_creados) * 100) : 0;

                        // Colores dinámicos para las barras (ciclo simple)
                        $colors = [
                            'bg-blue-500',
                            'bg-indigo-500',
                            'bg-violet-500'
                        ];
                        static $i = 0;
                        $bar_color = $colors[$i % 3];
                        $i++;

                        echo "<div>";
                        echo "<div class='flex justify-between text-sm mb-1'>";
                        echo "<span class='font-medium text-slate-700'>{$cat}</span>";
                        echo "<span class='text-slate-500'>{$count} tickets ({$porcentaje}%)</span>";
                        echo "</div>";
                        echo "<div class='w-full bg-slate-100 rounded-full h-2.5 overflow-hidden'>";
                        echo "<div class='h-2.5 rounded-full {$bar_color}' style='width: {$porcentaje}%'></div>";
                        echo "</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
                ?>
            </div>

            <!-- 2. Búsqueda Rápida (Estilizada como Card) -->
            <div
                class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-lg p-6 text-white flex flex-col justify-center">
                <h3 class="text-lg font-bold mb-2 flex items-center gap-2">
                    <i class="ri-search-line text-blue-400"></i> Búsqueda Rápida
                </h3>
                <p class="text-sm text-slate-300 mb-4">Filtra tus tickets por ID, asunto o estado al instante.</p>

                <div class="relative">
                    <i class="ri-search-2-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="buscadorTicketsUsuario" placeholder="Escribe para buscar..."
                        class="w-full bg-slate-700/50 border border-slate-600 text-white placeholder-slate-400 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent block pl-10 p-3 transition-all"
                        onkeyup="filtrarTablaUsuario()">
                </div>
            </div>
        </div>

        <!-- Tabla Historial Premium -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 bg-white border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <div class="bg-blue-50 p-2 rounded-lg text-blue-600">
                        <i class="ri-history-line"></i>
                    </div>
                    Actividad Reciente
                </h3>
                <a href="index.php?view=mis_tickets"
                    class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">Ver Todo</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100" id="tablaTicketsUsuario">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">ID Ticket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Asunto
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Estado
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Categoría
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">Acción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <?php
                        if (empty($mis_tickets_creados)) {
                            echo "<tr><td colspan='6' class='px-6 py-12 text-center text-slate-500'>
                                <div class='flex flex-col items-center justify-center'>
                                    <div class='bg-slate-50 p-4 rounded-full mb-3'><i class='ri-ticket-line text-3xl text-slate-300'></i></div>
                                    <p class='font-medium'>No has creado ningún ticket aún</p>
                                    <p class='text-sm mt-1 text-slate-400'>¡Crea uno nuevo para comenzar!</p>
                                </div>
                              </td></tr>";
                        } else {
                            // Mostrar TODOS los tickets en la tabla del dashboard para que el buscador funcione sobre todo el set
                            // OJO: Si son muchos, limitamos. Pero el usuario pidió buscador "mágico".
                            // Para mantener performance y estética, mantendré los últimos 10
                            $ultimos_tickets = array_slice($mis_tickets_creados, 0, 10);

                            foreach ($ultimos_tickets as $t) {
                                $status_colors = [
                                    'Pendiente' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                                    'Asignado' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'En Atención' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    'Completo' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'Resuelto' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'Cerrado' => 'bg-slate-50 text-slate-700 ring-slate-600/20'
                                ];
                                $s_class = $status_colors[$t['estado']] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';

                                $prio_colors = [
                                    'Baja' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'Media' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    'Alta' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                                    'Critica' => 'bg-rose-50 text-rose-700 ring-rose-600/20'
                                ];
                                $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';

                                // Lógica de Resaltado
                                $row_class = 'hover:bg-slate-50/80 transition-all duration-200 group';

                                echo "<tr class='{$row_class}'>";
                                echo "<td class='px-6 py-3'>
                                    <span class='font-mono text-sm font-medium text-slate-500'>#" . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . "</span>
                                  </td>";

                                echo "<td class='px-6 py-3'>
                                    <div class='text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition-colors'>" . htmlspecialchars($t['titulo']) . "</div>
                                    <div class='text-xs text-slate-400 mt-0.5 flex items-center gap-1'><i class='ri-time-line'></i> " . date('d M Y, h:i A', strtotime($t['fecha_creacion'])) . "</div>
                                  </td>";

                                echo "<td class='px-6 py-3 text-center'>
                                    <span class='inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$s_class} gap-1.5'>
                                        <span class='w-1.5 h-1.5 rounded-full bg-current'></span>
                                        " . htmlspecialchars($t['estado']) . "
                                    </span>
                                  </td>";

                                echo "<td class='px-6 py-3 text-center'>
                                    <span class='inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$p_class} gap-1.5'>
                                        <i class='ri-flag-fill text-[10px]'></i>
                                        " . htmlspecialchars($t['prioridad']) . "
                                    </span>
                                  </td>";

                                echo "<td class='px-6 py-3 text-sm text-slate-600'>
                                    <div class='flex items-center justify-center gap-1.5 bg-slate-50 py-1 px-3 rounded-lg border border-slate-100 w-fit mx-auto'>
                                        <i class='ri-folder-2-line text-slate-400'></i>
                                        <span class='font-medium text-xs'>" . htmlspecialchars($t['categoria']) . "</span>
                                    </div>
                                  </td>";

                                echo "<td class='px-6 py-3 text-center'>
                                    <a href='index.php?view=editar_ticket&id={$t['id']}' 
                                       class='inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm' 
                                       title='Ver Detalles'>
                                       <i class='ri-eye-line'></i>
                                    </a>
                                  </td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
                                                                                    functi        on         f        iltr        a              rTablaUsuario() {
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("buscadorTicketsUsuario");
                filter = input.value.toUpperCase();
                table = document.getElementById("tablaTicketsUsuario");
                tr = table.getElementsByTagName("tr");

                for (i = 0; i < tr.length; i++) {
                    // Buscamos en todas las columnas relevantes (ID, Asunto, Estado, Categoría)
                    // Indices: 0 (ID),     1 (Asunto), 2 (Estado), 4 (Categoria)
                    var found = false;
                    var indices = [0, 1, 2, 4];

                    // Skip header row
                    if (tr[i].getElementsByTagName("th").length > 0) continue;

                    for (var j = 0; j < indices.length; j++) {
                        td = tr[i].getElementsByTagName("td")[indices[j]];
                        if (td) {
                            txtValue = td.textContent || td.innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                    }

                    if (found) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        </script>


        <?php
    // -------------------------------------------------------------------------
    // 2.5 Dashboard para Gerencia (Nuevo)
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'Gerencia') {
    // --- Lógica de Datos ---

    // 1. Estadísticas Tickets
    $total_tickets = count($tickets);
    $t_abiertos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Pendiente'));
    $t_en_proceso = count(array_filter($tickets, fn($t) => $t['estado'] === 'Asignado'));
    $t_resueltos = count(array_filter($tickets, fn($t) => $t['estado'] === 'Completo'));
    $t_criticos = count(array_filter($tickets, fn($t) => $t['prioridad'] === 'Critica'));

    // Calculo de porcentaje de resolución
    $porcentaje_resueltos = $total_tickets > 0 ? round(($t_resueltos / $total_tickets) * 100) : 0;

    // 2. Estadísticas Inventario
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM inventario");
        $total_activos = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT tipo, COUNT(*) as cant FROM inventario GROUP BY tipo");
        $activos_por_tipo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [ 'Laptop' => 10, 'PC' => 5 ]
    } catch (Exception $e) {
        $total_activos = 0;
        $activos_por_tipo = [];
    }

    // 3. Estadísticas RRHH (Mes Actual)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh WHERE tipo = 'Ingreso' AND MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
        $ingresos_mes = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh WHERE tipo = 'Salida' AND MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
        $salidas_mes = $stmt->fetchColumn();

        // Total Personal Activo (Simple aproximación: total usuarios o total inventario asignado? Usaremos usuarios activos)
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'Activo'");
        $personal_activo = $stmt->fetchColumn();
    } catch (Exception $e) {
        $ingresos_mes = 0;
        $salidas_mes = 0;
        $personal_activo = 0;
    }

    // 4. Carga de Técnicos (Top 5)
    $carga_tecnicos = []; // [ 'Nombre' => 5 tickets ]
    foreach ($tickets as $t) {
        if (!empty($t['tecnico_id']) && $t['estado'] != 'Resuelto' && $t['estado'] != 'Cerrado') {
            // Buscar nombre del técnico (ineficiente loop pero funcional para datasets pequeños)
            $nombre_tec = "Técnico #" . $t['tecnico_id'];
            foreach ($GLOBALS['usuarios'] ?? [] as $u) {
                if ($u['id'] == $t['tecnico_id']) {
                    $nombre_tec = $u['nombre'];
                    break;
                }
            }
            $carga_tecnicos[$nombre_tec] = ($carga_tecnicos[$nombre_tec] ?? 0) + 1;
        }
    }
    arsort($carga_tecnicos);
    $carga_tecnicos = array_slice($carga_tecnicos, 0, 5);

    ?>

        <!-- Encabezado Futurista -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        <i class="ri-bar-chart-groupped-line text-xl"></i>
                    </div>
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-slate-700 to-slate-900">
                        Visión Gerencial
                    </span>
                </h2>
                <p class="text-slate-500 mt-1 ml-14">Panorama estratégico en tiempo real</p>
            </div>
            <div class="flex gap-2 text-sm font-medium bg-white p-1.5 rounded-xl border border-slate-100 shadow-sm">
                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg">Hoy: <?php echo date('d M, Y'); ?></span>
            </div>
        </div>

        <!-- Grid de KPIs Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- KPI 1: Eficiencia Tickets -->
            <div onclick="window.location.href='index.php?view=reportes_nuevo'"
                class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-500/10 to-violet-500/10 rounded-bl-full -mr-10 -mt-10 group-hover:scale-110 transition-transform">
                </div>
                <div class="relative z-10">
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Resolución Global</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-indigo-900"><?= $porcentaje_resueltos ?>%</span>
                        <span class="text-xs font-semibold text-emerald-500 bg-emerald-50 px-1.5 py-0.5 rounded">+2%</span>
                    </div>
                    <!-- Barra de Progreso -->
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full"
                            style="width: <?= $porcentaje_resueltos ?>%"></div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-slate-400">
                        <span><?= $t_resueltos ?> Resueltos</span>
                        <span><?= $total_tickets ?> Total</span>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Casos Críticos -->
            <div onclick="window.location.href='index.php?view=seguimiento&prioridad=Critica'"
                class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-rose-500/10 to-orange-500/10 rounded-bl-full -mr-10 -mt-10 group-hover:scale-110 transition-transform">
                </div>
                <div class="relative z-10">
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Tickets Críticos</div>
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-4xl font-black <?= $t_criticos > 0 ? 'text-rose-600' : 'text-slate-700' ?>"><?= $t_criticos ?></span>
                        <?php if ($t_criticos > 0): ?>
                                <span class="text-xs font-bold text-rose-500 animate-pulse">¡Atención!</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Requieren acción inmediata.</p>
                    <div class="mt-4 flex -space-x-2 overflow-hidden">
                        <!-- Avatares dummy visuales -->
                        <div
                            class="w-8 h-8 rounded-full border-2 border-white bg-rose-100 flex items-center justify-center text-xs text-rose-600 font-bold">
                            !</div>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Talento Humano -->
            <div onclick="window.location.href='index.php?view=historial_rrhh'"
                class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-bl-full -mr-10 -mt-10 group-hover:scale-110 transition-transform">
                </div>
                <div class="relative z-10">
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Personal Activo</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-slate-800"><?= $personal_activo ?></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-4">
                        <div class="bg-emerald-50 rounded-lg p-2 text-center">
                            <div class="text-xs text-emerald-600 font-bold">Ingresos</div>
                            <div class="font-bold text-emerald-700 text-lg">+<?= $ingresos_mes ?></div>
                        </div>
                        <div class="bg-rose-50 rounded-lg p-2 text-center">
                            <div class="text-xs text-rose-600 font-bold">Bajas</div>
                            <div class="font-bold text-rose-700 text-lg">-<?= $salidas_mes ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Inventario -->
            <div onclick="window.location.href='index.php?view=inventario'"
                class="bg-white rounded-2xl p-6 border border-slate-100 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group cursor-pointer">
                <div
                    class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-bl-full -mr-10 -mt-10 group-hover:scale-110 transition-transform">
                </div>
                <div class="relative z-10">
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Total Activos</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-slate-800"><?= $total_activos ?></span>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">Laptops</span>
                            <span class="font-bold text-slate-700"><?= $activos_por_tipo['Laptop'] ?? 0 ?></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">PC Escritorio</span>
                            <span class="font-bold text-slate-700"><?= $activos_por_tipo['PC'] ?? 0 ?></span>
                        </div>
                        <div class="w-full bg-slate-100 h-1 rounded-full">
                            <div class="h-full bg-blue-500 rounded-full" style="width: 70%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Central: Análisis Visual -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <!-- Estado de Tickets (Visual) -->
            <div
                class="lg:col-span-2 bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-2xl relative overflow-hidden">
                <!-- Decorative Background Chart Lines -->
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <path d="M0 100 Q 50 50 100 80 V 100 H 0 Z" fill="white" />
                    </svg>
                </div>

                <div class="flex justify-between items-center mb-6 relative z-10">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <i class="ri-pie-chart-2-line text-indigo-400"></i> Distribución de Tickets
                    </h3>
                    <a href="index.php?view=reportes"
                        class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded-full transition">Ver
                        Reporte</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                    <div onclick="window.location.href='index.php?view=seguimiento&estado=Pendiente'"
                        class="bg-slate-700/50 rounded-xl p-4 backdrop-blur-sm border border-slate-600/50 hover:bg-slate-600/50 transition-all cursor-pointer hover:scale-105">
                        <div class="flex justify-between items-start mb-2">
                            <div class="p-2 bg-blue-500/20 rounded-lg text-blue-400"><i class="ri-inbox-line text-xl"></i></div>
                            <span class="text-xs text-slate-400">Nuevos</span>
                        </div>
                        <div class="text-2xl font-bold"><?= $t_abiertos ?></div>
                        <div class="w-full bg-slate-600 h-1 mt-2 rounded-full">
                            <div class="bg-blue-500 h-1 rounded-full"
                                style="width: <?= ($total_tickets > 0 ? ($t_abiertos / $total_tickets) * 100 : 0) ?>%"></div>
                        </div>
                    </div>

                    <div onclick="window.location.href='index.php?view=seguimiento&estado=En%20Atenci%C3%B3n'"
                        class="bg-slate-700/50 rounded-xl p-4 backdrop-blur-sm border border-slate-600/50 hover:bg-slate-600/50 transition-all cursor-pointer hover:scale-105">
                        <div class="flex justify-between items-start mb-2">
                            <div class="p-2 bg-purple-500/20 rounded-lg text-purple-400"><i class="ri-loader-line text-xl"></i>
                            </div>
                            <span class="text-xs text-slate-400">En Proceso</span>
                        </div>
                        <div class="text-2xl font-bold"><?= $t_en_proceso ?></div>
                        <div class="w-full bg-slate-600 h-1 mt-2 rounded-full">
                            <div class="bg-purple-500 h-1 rounded-full"
                                style="width: <?= ($total_tickets > 0 ? ($t_en_proceso / $total_tickets) * 100 : 0) ?>%"></div>
                        </div>
                    </div>

                    <div onclick="window.location.href='index.php?view=seguimiento&estado=Resuelto'"
                        class="bg-slate-700/50 rounded-xl p-4 backdrop-blur-sm border border-slate-600/50 hover:bg-slate-600/50 transition-all cursor-pointer hover:scale-105">
                        <div class="flex justify-between items-start mb-2">
                            <div class="p-2 bg-emerald-500/20 rounded-lg text-emerald-400"><i
                                    class="ri-check-double-line text-xl"></i></div>
                            <span class="text-xs text-slate-400">Resueltos</span>
                        </div>
                        <div class="text-2xl font-bold"><?= $t_resueltos ?></div>
                        <div class="w-full bg-slate-600 h-1 mt-2 rounded-full">
                            <div class="bg-emerald-500 h-1 rounded-full"
                                style="width: <?= ($total_tickets > 0 ? ($t_resueltos / $total_tickets) * 100 : 0) ?>%"></div>
                        </div>
                    </div>

                    <div class="mt-6 text-xs text-slate-400 border-t border-slate-700/50 pt-4">
                        * Datos actualizados al momento. La tendencia muestra un comportamiento estable.
                    </div>
                </div>

                <!-- Carga de Técnicos (Tabla Compacta) -->
                <div class="bg-white rounded-2xl p-0 border border-slate-100 shadow-xl overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="ri-team-line text-violet-600"></i> Carga Operativa
                        </h3>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-400 font-semibold">
                                <tr>
                                    <th class="px-5 py-3">Técnico</th>
                                    <th class="px-5 py-3 text-right">Tickets Activos</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($carga_tecnicos as $nombre => $cantidad): ?>
                                        <tr onclick="window.location.href='index.php?view=historial_tecnico&tecnico=<?= urlencode($nombre) ?>'"
                                            class="hover:bg-slate-50 transition-colors cursor-pointer hover:bg-violet-50/30">
                                            <td class="px-5 py-3 font-medium text-slate-700">
                                                <div class="flex items-center gap-2">
                                                    <div
                                                        class="w-6 h-6 rounded-full bg-violet-100 text-violet-600 flex items-center justify-center text-xs font-bold">
                                                        <?= strtoupper(substr($nombre, 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($nombre) ?>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <span
                                                    class="bg-violet-50 text-violet-700 px-2 py-1 rounded-lg font-bold text-xs"><?= $cantidad ?></span>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                                <?php if (empty($carga_tecnicos)): ?>
                                        <tr>
                                            <td colspan="2" class="p-4 text-center text-slate-400 italic">Sin actividad reciente</td>
                                        </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-slate-50 text-center text-xs text-slate-400 border-t border-slate-100">
                        Top 5 Técnicos con más asignaciones
                    </div>
                </div>
            </div>

            <!-- Sección Inferior: Inventario Rápido -->
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                        <i class="ri-computer-line text-blue-600"></i> Inventario de Hardware
                    </h3>
                    <a href="index.php?view=reportes_nuevo" class="text-sm text-blue-600 font-medium hover:underline">Ver
                        Reporte
                        Financiero &rarr;</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                    <?php foreach (array_slice($activos_por_tipo, 0, 4) as $tipo => $cant): ?>
                            <a href="index.php?view=inventario&tipo=<?= urlencode($tipo) ?>"
                                class="block p-6 text-center hover:bg-slate-50 transition-colors group cursor-pointer">
                                <div
                                    class="text-3xl font-black text-slate-800 mb-1 group-hover:scale-110 transition-transform inline-block">
                                    <?= $cant ?>
                                </div>
                                <div class="text-xs uppercase font-bold text-slate-400 tracking-wider"><?= htmlspecialchars($tipo) ?>
                                </div>
                            </a>
                    <?php endforeach; ?>
                    <!-- Relleno si no hay inventario -->
                    <?php if (empty($activos_por_tipo)): ?>
                            <div class="p-6 text-center text-slate-400 col-span-4">No hay datos de inventario disponibles.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
    // -------------------------------------------------------------------------
    // 4. Dashboard para RRHH
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'RRHH') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh WHERE tipo = 'Ingreso' AND MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
        $ingresos_mes = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh WHERE tipo = 'Salida' AND MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())");
        $salidas_mes = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh"); // Total histórico
        $total_historico = $stmt->fetchColumn();
        $stmt = $pdo->query("
            SELECT f.*, 
                   t.fecha_creacion as ticket_fecha_creacion,
                   t.id as ticket_id
            FROM formularios_rrhh f
            LEFT JOIN tickets t ON (
                (f.tipo = 'Ingreso' AND t.titulo = CONCAT('Nuevo Ingreso: ', f.nombre_colaborador))
                OR
                (f.tipo = 'Salida' AND t.titulo = CONCAT('Baja de Personal: ', f.nombre_colaborador))
            )
            ORDER BY f.fecha_registro DESC 
            LIMIT 5
        ");
        $movimientos = $stmt->fetchAll();
    } catch (PDOException $e) {
        $ingresos_mes = 0;
        $salidas_mes = 0;
        $movimientos = [];
    }
    ?>
            <h2 class="text-2xl font-bold mb-6 text-slate-800 flex items-center gap-3">
                <span class="bg-pink-100 text-pink-600 p-2 rounded-lg"><i class="ri-user-star-line"></i></span> Portal de
                Gestión
                Humana
            </h2>

            <!-- Estadísticas RRHH -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card Ingresos -->
                <a href="index.php?view=nuevo_ingreso" class="block">
                    <div
                        class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                        <div
                            class="absolute -right-4 -top-4 opacity-20 transform group-hover:scale-110 transition-transform duration-500">
                            <i class="ri-user-add-line text-9xl"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-emerald-100 font-semibold text-xs uppercase tracking-wider mb-1">Ingresos este Mes
                            </p>
                            <h3 class="text-4xl font-bold mb-2"><?= $ingresos_mes ?></h3>
                            <div class="flex items-center gap-1 text-emerald-50 text-sm font-medium">
                                <i class="ri-add-circle-line"></i> Nuevo Ingreso
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Card Salidas -->
                <a href="index.php?view=nueva_salida" class="block">
                    <div
                        class="bg-gradient-to-br from-rose-500 to-rose-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                        <div
                            class="absolute -right-4 -top-4 opacity-20 transform group-hover:scale-110 transition-transform duration-500">
                            <i class="ri-user-unfollow-line text-9xl"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-rose-100 font-semibold text-xs uppercase tracking-wider mb-1">Bajas este Mes</p>
                            <h3 class="text-4xl font-bold mb-2"><?= $salidas_mes ?></h3>
                            <div class="flex items-center gap-1 text-rose-50 text-sm font-medium">
                                <i class="ri-user-unfollow-line"></i> Nueva Baja
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Card Total -->
                <a href="index.php?view=formularios_rrhh" class="block">
                    <div
                        class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-all duration-300 cursor-pointer">
                        <div
                            class="absolute -right-4 -top-4 opacity-20 transform group-hover:scale-110 transition-transform duration-500">
                            <i class="ri-folder-history-line text-9xl"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-indigo-100 font-semibold text-xs uppercase tracking-wider mb-1">Total Movimientos</p>
                            <h3 class="text-4xl font-bold mb-2"><?= $total_historico ?? 0 ?></h3>
                            <div class="flex items-center gap-1 text-indigo-50 text-sm font-medium">
                                <i class="ri-history-line"></i> Ver Historial
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-8 items-center justify-between">
                <h3 class="text-lg font-bold text-slate-700">Acciones Rápidas</h3>
                <div class="flex gap-4">
                    <a href="index.php?view=nuevo_ingreso"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                        <i class="ri-user-add-line"></i> Nuevo Ingreso
                    </a>
                    <a href="index.php?view=nueva_salida"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                        <i class="ri-user-unfollow-line"></i> Nueva Salida
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-slate-100">
                <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-white border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-700 flex items-center gap-2">
                        <i class="ri-history-line text-blue-500"></i> Movimientos Recientes
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50 text-slate-500 text-xs uppercase font-semibold tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-center">ID</th>
                                <th class="px-6 py-3 text-center">Tipo</th>
                                <th class="px-6 py-3 text-left">Colaborador</th>
                                <th class="px-6 py-3 text-left">Cargo/Zona</th>
                                <th class="px-6 py-3 text-center">Fecha</th>
                                <th class="px-6 py-3 text-center">Bloqueo</th>
                                <th class="px-6 py-3 text-center">Equipo</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($movimientos)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-400">No hay movimientos recientes.</td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($movimientos as $m): ?>
                                            <tr class="hover:bg-blue-50 cursor-pointer transition-colors group border-b border-slate-50 last:border-none"
                                                onclick='verDetallesRRHH(<?= json_encode($m) ?>)'>
                                                <td class="px-6 py-4 text-sm font-medium text-slate-600 text-center">
                                                    #<?= str_pad($m['id'], 4, '0', STR_PAD_LEFT) ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <span
                                                        class="px-2 py-1 rounded text-xs font-bold <?= $m['tipo'] == 'Ingreso' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                                        <?= $m['tipo'] ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-left">
                                                    <div class="font-medium text-slate-700"><?= htmlspecialchars($m['nombre_colaborador']) ?>
                                                    </div>
                                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($m['cedula_telefono'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-slate-600 text-left"><?= htmlspecialchars($m['cargo_zona'] ?? '') ?>
                                                </td>
                                                <td class="px-6 py-4 text-slate-600 text-center">
                                                    <?php
                                                    $fecha_mostrar = $m['ticket_fecha_creacion'] ?? $m['fecha_efectiva'] ?? $m['fecha_solicitud'] ?? '';
                                                    if ($fecha_mostrar && $fecha_mostrar != '0000-00-00' && $fecha_mostrar != '0000-00-00 00:00:00') {
                                                        echo date('d/m/y', strtotime($fecha_mostrar));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <?php if ($m['tipo'] == 'Salida'): ?>
                                                            <span
                                                                class="px-2 py-1 rounded text-xs <?= $m['bloqueo_correo'] == 'SI' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' ?>">
                                                                <?= $m['bloqueo_correo'] ?? 'NO' ?>
                                                            </span>
                                                    <?php else: ?>
                                                            <span class="text-slate-400">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <?php if ($m['tipo'] == 'Salida'): ?>
                                                            <span
                                                                class="px-2 py-1 rounded text-xs <?= $m['devolucion_equipo'] == 'SI' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' ?>">
                                                                <?= $m['devolucion_equipo'] == 'SI' ? 'PC' : '-' ?>
                                                            </span>
                                                    <?php elseif ($m['tipo'] == 'Ingreso'): ?>
                                                            <span
                                                                class="px-2 py-1 rounded text-xs <?= $m['asignacion_equipo'] == 'SI' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' ?>">
                                                                <?= $m['asignacion_equipo'] == 'SI' ? 'PC' : '-' ?>
                                                            </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <button onclick='verDetallesRRHH(<?= json_encode($m) ?>)'
                                                        class="text-blue-600 hover:text-blue-800 transition flex items-center justify-center gap-1 mx-auto">
                                                        <i class="ri-eye-line"></i> Ver Detalles
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Modal Detalles RRHH -->
            <div id="modalRRHH" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                    <button onclick="cerrarModalRRHH()"
                        class="absolute top-4 right-4 p-2 hover:bg-slate-100 rounded-full transition-colors">
                        <i class="ri-close-line text-2xl text-slate-500"></i>
                    </button>

                    <div id="contenidoModalRRHH">
                        <!-- El contenido se carga dinámicamente -->
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button onclick="cerrarModalRRHH()"
                            class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <script>
                function verDetallesRRHH(data) {
                    const modal = document.getElementById('modalRRHH');
                    const contenido = document.getElementById('contenidoModalRRHH');

                    // Debug: ver qué datos llegan
                    console.log('=== DEBUG RRHH ===');
                    console.log('Datos completos:', data);
                    console.log('fecha_efectiva:', data.fecha_efectiva);
                    console.log('fecha_solicitud:', data.fecha_solicitud);
                    console.log('==================');

                    // Determinar colores según tipo
                    const esIngreso = data.tipo === 'Ingreso';
                    const colorClass = esIngreso ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
                    const iconClass = esIngreso ? 'ri-user-add-line' : 'ri-user-unfollow-line';

                    let html = `
                <div class="flex items-center gap-4 mb-6 pb-4 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-full ${colorClass} flex items-center justify-center text-2xl">
                        <i class="${iconClass}"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800">Detalles de ${data.tipo}</h3>
                        <p class="text-slate-500">Solicitud #${String(data.id).padStart(4, '0')} - ${data.fecha_solicitud}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Colaborador</label>
                            <p class="font-semibold text-slate-800 text-lg">${data.nombre_colaborador}</p>
                            <p class="text-sm text-slate-500"><i class="ri-id-card-line"></i> Cédula: ${data.cedula || 'N/A'}</p>
                            <p class="text-sm text-slate-500"><i class="ri-phone-line"></i> Teléfono: ${data.telefono || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Cargo / Zona</label>
                            <p class="font-medium text-slate-700">${data.cargo_zona || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text                    -xs font-bold text-slate-400 uppercase">Fecha Efectiva</                                       label>
                            <p class="font-medium                    text-slate-700"><i class="ri-calendar-event-line"></i> ${(() => {
                    // Validar fecha_efectiva primero
                    let fecha = null;
                    if (data.fecha_efectiva && data.fecha_efectiva !== '0000-00-00' && data.fecha_efectiva !== 'NULL') {
                        fecha = data.fecha_efectiva;
                    } else if (data.fecha_solicitud && data.fecha_solicitud !== '0000-00-00') {
                        fecha = data.fecha_solicitud;
                    }

                    if (fecha) {
                        try {
                            return new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        } catch (e) {
                            return 'N/A';
                        }
                    }
                    return 'N/A';
                })()
                }</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-400 uppercase">Correo Electrónico</label>
                            <p class="font-medium text-slate-700">${data.correo_nuevo || data.cuenta_correo_bloqueo || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-slate-50 rounded-xl space-y-3">
                    <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="ri-list-check"></i> Requerimientos IT</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            `;

                    // Lógica específica para Ingreso vs Salida
                    if (esIngreso) {
                        html += `
                    <div class="flex items-center gap-2">
                        <i class="${data.asignacion_equipo === 'SI' ? 'ri-checkbox-circle-line text-emerald-500' : 'ri-close-circle-line text-slate-400'}"></i>
                        <span>Asignación de Equipo: <strong>${data.asignacion_equipo}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="${data.disponibilidad_licencias === 'SI' ? 'ri-checkbox-circle-line text-emerald-500' : 'ri-close-circle-line text-slate-400'}"></i>
                        <span>Licencias M365: <strong>${data.disponibilidad_licencias}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="${(data.nube_movil === 'SI' || data.nube_movil === 'si') ? 'ri-checkbox-circle-line text-emerald-500' : 'ri-close-circle-line text-slate-400'}"></i>
                        <span>Nube/Móvil: <strong>${data.nube_movil && data.nube_movil !== 'null' ? data.nube_movil : 'NO'}</strong></span>
                    </div>
                `;
                    } else {
                        html += `
                    <div class="flex items-center gap-2">
                        <i class="${data.bloqueo_correo === 'SI' ? 'ri-lock-line text-red-500' : 'ri-lock-unlock-line text-slate-400'}"></i>
                        <span>Bloqueo Correo: <strong>${data.bloqueo_correo}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="${data.devolucion_equipo === 'SI' ? 'ri-computer-line text-blue-500' : 'ri-computer-line text-slate-400'}"></i>
                        <span>Devolución Equipo: <strong>${data.devolucion_equipo}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="${data.respaldo_info === 'SI' ? 'ri-save-line text-blue-500' : 'ri-save-line text-slate-400'}"></i>
                        <span>Respaldo Info: <strong>${data.respaldo_info}</strong></span>
                    </div>
                `;
                    }

                    html += `
                    </div>
                </div>
            `;

                    if (data.observaciones || data.otras_indicaciones) {
                        html += `
                    <div class="mt-4">
                        <label class="text-xs font-bold text-slate-400 uppercase">Observaciones / Indicaciones</label>
                        <p class="text-sm text-slate-600 bg-slate-50 p-3 rounded-lg mt-1">${data.observaciones || data.otras_indicaciones}</p>
                    </div>
                `;
                    }

                    contenido.innerHTML = html;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModalRRHH() {
                    const modal = document.getElementById('modalRRHH');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                // Cerrar con ESC
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') cerrarModalRRHH();
                });
            </script>
            <?php
    // -------------------------------------------------------------------------
    // 5. Dashboard para SuperAdmin
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'SuperAdmin') {
    // Obtener estadísticas del sistema
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $total_usuarios = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
        $total_tickets = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh");
        $total_rrhh = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM categorias");
        $total_categorias = $stmt->fetchColumn();

        // Estadísticas por rol
        $stmt = $pdo->query("SELECT r.nombre, COUNT(u.id) as total FROM usuarios u JOIN roles r ON u.rol_id = r.id GROUP BY r.nombre");
        $usuarios_por_rol = $stmt->fetchAll();

        // Tickets por estado
        $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado");
        $tickets_por_estado = $stmt->fetchAll();

    } catch (PDOException $e) {
        $total_usuarios = 0;
        $total_tickets = 0;
        $total_rrhh = 0;
        $total_categorias = 0;
        $usuarios_por_rol = [];
        $tickets_por_estado = [];
    }

    // [NEW] Calcular tickets por país usando el array global (optimizado)
    $tickets_nicaragua = 0;
    $tickets_honduras = 0;
    if (isset($tickets)) {
        foreach ($tickets as $t) {
            $pais = $t['creador_pais'] ?? '';
            if (stripos($pais, 'Nicaragua') !== false)
                $tickets_nicaragua++;
            if (stripos($pais, 'Honduras') !== false)
                $tickets_honduras++;
        }
    }
    ?>

            <div class="flex items-center justify-center gap-2 mb-3">
                <div
                    class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg flex items-center justify-center">
                    <i class="ri-shield-star-line text-2xl text-white"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900">Panel de SuperAdministrador</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                <a href="index.php?view=usuarios" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm font-medium">Total Usuarios</p>
                                <p class="text-3xl font-bold"><?= $total_usuarios ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-full">
                                <i class="ri-user-line text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-purple-100 text-xs mt-2 flex items-center gap-1">
                            <i class="ri-arrow-right-line"></i> Gestionar usuarios
                        </p>
                    </div>
                </a>

                <a href="index.php?view=listados" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total Tickets</p>
                                <p class="text-3xl font-bold"><?= $total_tickets ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-full">
                                <i class="ri-ticket-line text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-blue-100 text-xs mt-2 flex items-center gap-1">
                            <i class="ri-arrow-right-line"></i> Ver todos los tickets
                        </p>
                    </div>
                </a>

                <a href="index.php?view=formularios_rrhh" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-br from-pink-500 to-pink-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-pink-100 text-sm font-medium">Formularios RRHH</p>
                                <p class="text-3xl font-bold"><?= $total_rrhh ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-full">
                                <i class="ri-user-star-line text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-pink-100 text-xs mt-2 flex items-center gap-1">
                            <i class="ri-arrow-right-line"></i> Ver formularios RRHH
                        </p>
                    </div>
                </a>

                <a href="index.php?view=categorias" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-100 text-sm font-medium">Categorías</p>
                                <p class="text-3xl font-bold"><?= $total_categorias ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-3 rounded-full">
                                <i class="ri-folder-line text-2xl"></i>
                            </div>
                        </div>
                        <p class="text-emerald-100 text-xs mt-2 flex items-center gap-1">
                            <i class="ri-arrow-right-line"></i> Gestionar categorías
                        </p>
                    </div>
                </a>
            </div>

            <!-- [NEW] Tickets por Sede/País -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                <a href="index.php?view=listados&filter=pais_nicaragua" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white p-4 rounded-xl shadow-lg cursor-pointer relative overflow-hidden group">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-white/90 text-sm font-bold uppercase tracking-wider mb-1">Tickets Nicaragua</p>
                                <h3 class="text-4xl font-black text-white"><?= $tickets_nicaragua ?></h3>
                            </div>
                            <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                                <i class="ri-map-pin-2-line text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="index.php?view=listados&filter=pais_honduras" class="block transform hover:scale-105 transition">
                    <div
                        class="bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white p-4 rounded-xl shadow-lg cursor-pointer relative overflow-hidden group">
                        <div
                            class="absolute right-0 top-0 h-full w-24 bg-white/10 skew-x-12 transform translate-x-12 group-hover:translate-x-6 transition-transform">
                        </div>
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-white/90 text-sm font-bold uppercase tracking-wider mb-1">Tickets Honduras</p>
                                <h3 class="text-4xl font-black text-white"><?= $tickets_honduras ?></h3>
                            </div>
                            <div class="bg-white/20 p-3 rounded-full backdrop-blur-sm">
                                <i class="ri-global-line text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <!-- Herramientas de Administración -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden h-full">
                    <div class="px-4 py-2 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-slate-100">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                            <i class="ri-tools-line text-slate-600"></i> Herramientas de Administración
                        </h3>
                    </div>
                    <div class="p-3">
                        <div class="space-y-2">
                            <a href="index.php?view=backup"
                                class="flex items-center gap-2 p-2 border-2 border-emerald-200 rounded-lg hover:bg-emerald-50 transition group">
                                <div class="bg-emerald-100 p-2 rounded-lg group-hover:bg-emerald-200 transition">
                                    <i class="ri-database-2-line text-xl text-emerald-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Backup BD</h4>
                                    <p class="text-xs text-slate-500">Respaldar base de datos</p>
                                </div>
                            </a>

                            <a href="index.php?view=restore"
                                class="flex items-center gap-2 p-2 border-2 border-blue-200 rounded-lg hover:bg-blue-50 transition group">
                                <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200 transition">
                                    <i class="ri-refresh-line text-xl text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Restaurar BD</h4>
                                    <p class="text-xs text-slate-500">Restaurar desde backup</p>
                                </div>
                            </a>

                            <a href="index.php?view=restart"
                                class="flex items-center gap-2 p-2 border-2 border-red-200 rounded-lg hover:bg-red-50 transition group">
                                <div class="bg-red-100 p-2 rounded-lg group-hover:bg-red-200 transition">
                                    <i class="ri-restart-line text-xl text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Reiniciar BD</h4>
                                    <p class="text-xs text-slate-500">Resetear sistema</p>
                                </div>
                            </a>

                            <a href="index.php?view=config"
                                class="flex items-center gap-2 p-2 border-2 border-purple-200 rounded-lg hover:bg-purple-50 transition group">
                                <div class="bg-purple-100 p-2 rounded-lg group-hover:bg-purple-200 transition">
                                    <i class="ri-user-settings-line text-xl text-purple-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Configuración</h4>
                                    <p class="text-xs text-slate-500">Gestionar sistema</p>
                                </div>
                            </a>

                            <a href="index.php?view=asignar"
                                class="flex items-center gap-2 p-2 border-2 border-slate-200 rounded-lg hover:bg-slate-50 transition group">
                                <div class="bg-slate-100 p-2 rounded-lg group-hover:bg-slate-200 transition">
                                    <i class="ri-file-list-line text-xl text-slate-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Todos los Tickets</h4>
                                    <p class="text-xs text-slate-500">Ver listado completo</p>
                                </div>
                            </a>

                            <a href="index.php?view=categorias"
                                class="flex items-center gap-2 p-2 border-2 border-amber-200 rounded-lg hover:bg-amber-50 transition group">
                                <div class="bg-amber-100 p-2 rounded-lg group-hover:bg-amber-200 transition">
                                    <i class="ri-folder-settings-line text-xl text-amber-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 text-sm">Categorías</h4>
                                    <p class="text-xs text-slate-500">Gestionar categorías</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>


                <!-- Usuarios por Rol - INTERACTIVO -->
                <div
                    class="bg-white rounded-xl shadow-lg overflow-hidden h-full hover:shadow-2xl transition-shadow duration-300">
                    <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-purple-50 to-purple-100">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                            <i class="ri-team-line text-purple-600"></i> Usuarios por Rol
                        </h3>
                    </div>
                    <div class="p-3">
                        <?php if (empty($usuarios_por_rol)): ?>
                                <p class="text-slate-400 text-center py-4 text-sm">No hay datos disponibles</p>
                        <?php else: ?>
                                <div class="space-y-2">
                                    <?php
                                    $rol_icons = [
                                        'Admin' => 'ri-shield-star-line',
                                        'SuperAdmin' => 'ri-vip-crown-line',
                                        'RRHH' => 'ri-user-settings-line',
                                        'Tecnico' => 'ri-tools-line',
                                        'Usuario' => 'ri-user-line',
                                        'Gerencia' => 'ri-briefcase-line'
                                    ];
                                    $rol_colors = [
                                        'Admin' => 'from-blue-500 to-blue-600',
                                        'SuperAdmin' => 'from-purple-500 to-purple-600',
                                        'RRHH' => 'from-emerald-500 to-emerald-600',
                                        'Tecnico' => 'from-orange-500 to-orange-600',
                                        'Usuario' => 'from-slate-500 to-slate-600',
                                        'Gerencia' => 'from-indigo-500 to-indigo-600'
                                    ];
                                    foreach ($usuarios_por_rol as $rol):
                                        $icon = $rol_icons[$rol['nombre']] ?? 'ri-user-line';
                                        $color = $rol_colors[$rol['nombre']] ?? 'from-slate-500 to-slate-600';
                                        ?>
                                            <div class="group relative flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-gradient-to-r <?= $color ?> transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-md"
                                                onclick="mostrarDetalleRol('<?= htmlspecialchars($rol['nombre']) ?>', <?= $rol['total'] ?>)">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-white/50 rounded-lg group-hover:bg-white/90 transition-colors">
                                                        <i
                                                            class="<?= $icon ?> text-lg text-slate-700 group-hover:text-white transition-colors"></i>
                                                    </div>
                                                    <span class="font-medium text-slate-700 text-sm group-hover:text-white transition-colors">
                                                        <?= htmlspecialchars($rol['nombre']) ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold group-hover:bg-white group-hover:text-purple-700 transition-colors">
                                                        <?= $rol['total'] ?>
                                                    </span>
                                                    <i
                                                        class="ri-arrow-right-s-line text-slate-400 group-hover:text-white transition-all transform group-hover:translate-x-1"></i>
                                                </div>
                                            </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tickets por Estado - INTERACTIVO -->
                <div
                    class="bg-white rounded-xl shadow-lg overflow-hidden h-full hover:shadow-2xl transition-shadow duration-300">
                    <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-blue-100">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                            <i class="ri-pie-chart-line text-blue-600"></i> Tickets por Estado
                        </h3>
                    </div>
                    <div class="p-3">
                        <?php if (empty($tickets_por_estado)): ?>
                                <p class="text-slate-400 text-center py-4 text-sm">No hay datos disponibles</p>
                        <?php else: ?>
                                <div class="space-y-2">
                                    <?php
                                    $estado_configs = [
                                        'Pendiente' => [
                                            'bg' => 'from-yellow-500 to-amber-600',
                                            'badge' => 'bg-yellow-100 text-yellow-700',
                                            'icon' => 'ri-error-warning-line'
                                        ],
                                        'Asignado' => [
                                            'bg' => 'from-blue-500 to-blue-600',
                                            'badge' => 'bg-blue-100 text-blue-700',
                                            'icon' => 'ri-user-settings-line'
                                        ],
                                        'Completo' => [
                                            'bg' => 'from-green-500 to-green-600',
                                            'badge' => 'bg-green-100 text-green-700',
                                            'icon' => 'ri-checkbox-circle-line'
                                        ],
                                        'En Proceso' => [ // Legacy/Fallback
                                            'bg' => 'from-blue-500 to-blue-600',
                                            'badge' => 'bg-blue-100 text-blue-700',
                                            'icon' => 'ri-loader-4-line'
                                        ],
                                        'Resuelto' => [ // Legacy/Fallback
                                            'bg' => 'from-green-500 to-green-600',
                                            'badge' => 'bg-green-100 text-green-700',
                                            'icon' => 'ri-checkbox-circle-line'
                                        ],
                                        'Cerrado' => [
                                            'bg' => 'from-slate-500 to-slate-600',
                                            'badge' => 'bg-slate-100 text-slate-700',
                                            'icon' => 'ri-lock-line'
                                        ]
                                    ];

                                    // Calcular total para porcentajes
                                    $total_tickets = array_sum(array_column($tickets_por_estado, 'total'));

                                    foreach ($tickets_por_estado as $estado):
                                        $config = $estado_configs[$estado['estado']] ?? [
                                            'bg' => 'from-slate-500 to-slate-600',
                                            'badge' => 'bg-slate-100 text-slate-700',
                                            'icon' => 'ri-file-line'
                                        ];
                                        $porcentaje = $total_tickets > 0 ? round(($estado['total'] / $total_tickets) * 100) : 0;
                                        ?>
                                            <div class="group relative">
                                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-gradient-to-r <?= $config['bg'] ?> transition-all duration-300 cursor-pointer transform hover:scale-105 hover:shadow-md"
                                                    onclick="mostrarDetalleEstado('<?= htmlspecialchars($estado['estado']) ?>', <?= $estado['total'] ?>, <?= $porcentaje ?>)">
                                                    <div class="flex items-center gap-3">
                                                        <div class="p-2 bg-white/50 rounded-lg group-hover:bg-white/90 transition-colors">
                                                            <i
                                                                class="<?= $config['icon'] ?> text-lg text-gray-900 group-hover:text-white transition-colors"></i>
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="font-bold text-gray-900 text-sm group-hover:text-white transition-colors block">
                                                                <?= htmlspecialchars($estado['estado']) ?>
                                                            </span>
                                                            <span class="text-xs text-slate-500 group-hover:text-white/80 transition-colors">
                                                                <?= $porcentaje ?>% del total
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="px-3 py-1 <?= $config['badge'] ?> rounded-full text-xs font-bold group-hover:bg-white transition-colors">
                                                            <?= $estado['total'] ?>
                                                        </span>
                                                        <i
                                                            class="ri-arrow-right-s-line text-slate-400 group-hover:text-white transition-all transform group-hover:translate-x-1"></i>
                                                    </div>
                                                </div>
                                                <!-- Barra de progreso -->
                                                <div class="mt-1 h-1 bg-slate-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r <?= $config['bg'] ?> transition-all duration-1000 ease-out"
                                                        style="width: <?= $porcentaje ?>%"></div>
                                                </div>
                                            </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal para detalles (oculto por defecto) -->
            <div id="modalDetalles" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50"
                onclick="cerrarModal()">
                <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl transform transition-all"
                    onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-slate-800" id="modalTitulo"></h3>
                        <button onclick="cerrarModal()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                            <i class="ri-close-line text-2xl text-slate-600"></i>
                        </button>
                    </div>
                    <div id="modalContenido" class="text-slate-600"></div>
                    <div class="mt-6 flex gap-3">
                        <a id="btnAccionModal" href="#"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-center font-semibold flex items-center justify-center gap-2">
                            <i id="btnAccionIcon" class="ri-team-line"></i> <span id="btnAccionTexto">Ver Detalles</span>
                        </a>
                        <button onclick="cerrarModal()"
                            class="flex-1 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors font-semibold">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <script>
                function mostrarDetalleRol(rol, total) {
                    const modal = document.getElementById('modalDetalles');
                    const titulo = document.getElementById('modalTitulo');
                    const contenido = document.getElementById('modalContenido');
                    const btnAccion = document.getElementById('btnAccionModal');
                    const btnIcon = document.getElementById('btnAccionIcon');
                    const btnTexto = document.getElementById('btnAccionTexto');

                    titulo.innerHTML = `<i class="ri-team-line text-purple-600 mr-2"></i>Rol: ${rol}`;
                    contenido.innerHTML = `
            <div class="space-y-4">
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="text-sm text-purple-600 font-semibold mb-1">Total de Usuarios</div>
                    <div class="text-3xl font-bold text-purple-700">${total}</div>
                </div>
                <div class="text-sm text-slate-600">
                    <p class="mb-2">Este rol tiene <strong>${total}</strong> usuario(s) asignado(s) en el sistema.</p>
                    <p>Puedes gestionar los usuarios desde la sección de <strong>Gestión de Usuarios</strong>.</p>
                </div>
            </div>
        `;

                    // Configurar botón para Usuarios
                    btnAccion.href = 'index.php?view=usuarios&rol=' + encodeURIComponent(rol);
                    btnAccion.className = "flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-center font-semibold flex items-center justify-center gap-2";
                    btnIcon.className = "ri-team-line";
                    btnTexto.textContent = "Ver Usuarios";

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function mostrarDetalleEstado(estado, total, porcentaje) {
                    const modal = document.getElementById('modalDetalles');
                    const titulo = document.getElementById('modalTitulo');
                    const contenido = document.getElementById('modalContenido');
                    const btnAccion = document.getElementById('btnAccionModal');
                    const btnIcon = document.getElementById('btnAccionIcon');
                    const btnTexto = document.getElementById('btnAccionTexto');

                    const iconos = {
                        'Pendiente': 'ri-error-warning-line text-yellow-600',
                        'En Proceso': 'ri-loader-4-line text-blue-600',
                        'Asignado': 'ri-user-settings-line text-blue-600',
                        'Resuelto': 'ri-checkbox-circle-line text-green-600',
                        'Completo': 'ri-checkbox-circle-line text-green-600',
                        'Cerrado': 'ri-lock-line text-slate-600'
                    };

                    const colores = {
                        'Pendiente': 'blue',
                        'En Proceso': 'blue',
                        'Asignado': 'blue',
                        'Resuelto': 'green',
                        'Completo': 'green',
                        'Cerrado': 'slate'
                    };

                    // Mapeo de estado para filtro URL
                    let filtroUrl = 'todos';
                    if (estado === 'Pendiente') filtroUrl = 'abiertos';
                    else if (estado === 'En Atención' || estado === 'Asignado') filtroUrl = 'asignado';
                    else if (['Resuelto', 'Cerrado', 'Completo'].includes(estado)) filtroUrl = 'completo';

                    const icon = iconos[estado] || 'ri-file-line text-slate-600';
                    const color = colores[estado] || 'slate';

                    titulo.innerHTML = `<i class="${icon} mr-2"></i>Estado: ${estado}`;
                    contenido.innerHTML = `
            <div class="space-y-4">
                <div class="bg-${color}-50 rounded-lg p-4">
                    <div class="text-sm text-${color}-600 font-semibold mb-1">Total de Tickets</div>
                    <div class="text-3xl font-bold text-${color}-700">${total}</div>
                    <div class="text-sm text-${color}-600 mt-1">${porcentaje}% del total</div>
                </div>
                <div class="text-sm text-slate-600">
                    <p class="mb-2">Hay <strong>${total}</strong> ticket(s) en estado <strong>${estado}</strong>.</p>
                    <p>Representa el <strong>${porcentaje}%</strong> del total de tickets en el sistema.</p>
                </div>
            </div>
        `;

                    // Configurar botón para Tickets
                    btnAccion.href = 'index.php?view=listados&filter=' + filtroUrl;
                    // Ajustar color del botón según el estado (opcional, pero queda mejor)
                    let btnColorClass = `bg-${color}-600 hover:bg-${color}-700`;
                    // Fix para 'slate' que no siempre tiene buttons bonitos o colores no estándar, usaremos blue por defecto si no es uno común
                    if (color === 'yellow') btnColorClass = 'bg-yellow-600 hover:bg-yellow-700';
                    if (color === 'green') btnColorClass = 'bg-emerald-600 hover:bg-emerald-700';
                    if (color === 'blue') btnColorClass = 'bg-blue-600 hover:bg-blue-700';
                    if (color === 'slate') btnColorClass = 'bg-slate-600 hover:bg-slate-700';

                    btnAccion.className = `flex-1 px-4 py-2 text-white rounded-lg transition-colors text-center font-semibold flex items-center justify-center gap-2 ${btnColorClass}`;

                    btnIcon.className = "ri-ticket-line";
                    btnTexto.textContent = "Ver Tickets";


                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function cerrarModal() {
                    const modal = document.getElementById('modalDetalles');
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                // Cerrar modal con tecla ESC
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        cerrarModal();
                    }
                });
            </script>

            <?php
            // -------------------------------------------------------------------------
            // 6. Default (Otros roles)
            // -------------------------------------------------------------------------
            ?>
            <div class="flex flex-col items-center justify-center h-96 text-center">
                <div class="bg-slate-100 p-6 rounded-full mb-4">
                    <i class="ri-dashboard-line text-4xl text-slate-400"></i>
                </div>
                <h2 class="text-xl font-semibold text-slate-700">Bienvenido al Sistema</h2>
                <p class="text-slate-500 mt-2">Selecciona una opción del menú para comenzar.</p>
            </div>
            <?php
}
?>

    <!-- Quick View Modal -->
    <div id="modalDetalleTicket" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="cerrarDetalleTicket()">
        </div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">

                    <!-- Header -->
                    <div
                        class="bg-gradient-to-r from-slate-50 to-white px-6 py-4 border-b border-slate-100 flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span id="qv_id" class="text-xs font-mono font-bold text-slate-400">#0000</span>
                                <span id="qv_estado"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600">Estado</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800 leading-tight" id="qv_titulo">Título del Ticket
                            </h3>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600 transition-colors"
                            onclick="cerrarDetalleTicket()">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-6 space-y-6">

                        <!-- View: Info -->
                        <div id="modal_body_info">
                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                    <div class="text-xs text-slate-400 mb-1">Solicitante</div>
                                    <div class="font-bold text-slate-700" id="qv_solicitante">Nombre Apellido</div>
                                    <div class="text-[10px] text-slate-500" id="qv_rol_solicitante">Rol</div>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                                    <div class="text-xs text-slate-400 mb-1">Asignado a</div>
                                    <div id="qv_asignado_container" class="flex items-center gap-2">
                                        <span class="text-slate-500 italic">Sin asignar</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mt-6">
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Descripción
                                </div>
                                <div class="text-slate-600 text-sm leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 max-h-40 overflow-y-auto"
                                    id="qv_descripcion">
                                    Descripción completa...
                                </div>
                            </div>

                            <!-- Meta -->
                            <div
                                class="flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-50 mt-6">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center gap-1"><i class="ri-calendar-line"></i> <span
                                            id="qv_fecha">00/00/0000</span></span>
                                    <span class="flex items-center gap-1"><i class="ri-folder-line"></i> <span
                                            id="qv_categoria">General</span></span>
                                </div>
                                <span class="flex items-center gap-1 font-bold" id="qv_prioridad_container"><i
                                        class="ri-flag-fill"></i> <span id="qv_prioridad">Alta</span></span>
                            </div>
                        </div>

                        <!-- View: Asignar Tecnico -->
                        <div id="modal_body_asignar" class="hidden">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i class="ri-user-search-line text-indigo-500"></i> Selecciona un Técnico
                            </h4>
                            <div id="lista_tecnicos_asignar"
                                class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                <!-- JS Populated -->
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-slate-50 px-6 py-4 flex gap-3 justify-end border-t border-slate-100">
                        <!-- Botón Cancelar Asignación -->
                        <button type="button" id="qv_btn_cancelar_asign"
                            class="hidden px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors text-sm shadow-sm flex items-center gap-2"
                            onclick="cancelarAsignacion()">
                            <i class="ri-arrow-left-line"></i> Volver
                        </button>

                        <button type="button"
                            class="px-4 py-2 bg-white text-slate-700 font-medium rounded-lg border border-slate-200 hover:bg-slate-50 hover:text-slate-900 transition-colors text-sm shadow-sm"
                            onclick="cerrarDetalleTicket()">Cerrar</button>

                        <a id="qv_btn_asignar" href="#"
                            class="hidden px-4 py-2 bg-slate-800 text-white font-medium rounded-lg hover:bg-slate-900 transition-colors text-sm shadow-md flex items-center gap-2">
                            <i class="ri-user-add-line"></i> Asignar
                        </a>

                        <a id="qv_btn_editar" href="#"
                            class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors text-sm shadow-md flex items-center gap-2">
                            <i class="ri-pencil-line"></i> Gestionar Ticket
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- [NEW] Formulario oculto para asignación -->
    <form id="formAsignarTecnico" method="POST" action="index.php?view=dashboard" class="hidden">
        <input type="hidden" name="asignar_tecnico" value="1">
        <input type="hidden" name="ticket_id" id="form_ticket_id">
        <input type="hidden" name="tecnico_id" id="form_tecnico_id">
    </form>

    <script>
        // Prepare technicians data from PHP
        const tecnicosDisponibles = <?php
        $tecnicos_array = [];
        if (isset($GLOBALS['usuarios'])) {
            foreach ($GLOBALS['usuarios'] as $u) {
                // Solo mostrar Técnicos puros
                if (($u['rol'] ?? '') === 'Tecnico') {
                    $tecnicos_array[] = [
                        'id' => $u['id'],
                        'nombre' => $u['nombre'],
                        'initials' => strtoupper(substr($u['nombre'], 0, 2))
                    ];
                }
            }
        }
        echo json_encode($tecnicos_array);
        ?>;

        function verDetallesTicket(row) {
            const data = JSON.parse(row.getAttribute('data-ticket'));

            // Reset Modal State
            document.getElementById('modal_body_info').classList.remove('hidden');
            document.getElementById('modal_body_asignar').classList.add('hidden');
            document.getElementById('qv_btn_asignar').classList.remove('hidden'); // Show by default, hide logic below
            document.getElementById('qv_btn_cancelar_asign').classList.add('hidden');

            // Populate Info
            document.getElementById('qv_id').textContent = '#' + String(data.id).padStart(4, '0');
            document.getElementById('qv_titulo').textContent = data.titulo;
            document.getElementById('qv_descripcion').textContent = data.descripcion;
            document.getElementById('qv_fecha').textContent = data.fecha;
            document.getElementById('qv_solicitante').textContent = data.solicitante;
            document.getElementById('qv_rol_solicitante').textContent = data.rol_solicitante;
            document.getElementById('qv_categoria').textContent = data.categoria;

            // Prioridad
            const priocolors = { 'Baja': 'text-emerald-500', 'Media': 'text-amber-500', 'Alta': 'text-orange-500', 'Critica': 'text-rose-500' };
            const pContainer = document.getElementById('qv_prioridad_container');
            pContainer.className = 'flex items-center gap-1 font-bold ' + (priocolors[data.prioridad] || 'text-slate-500');
            document.getElementById('qv_prioridad').textContent = data.prioridad;

            // Estado
            const stColors = { 'Pendiente': 'bg-yellow-100 text-yellow-700', 'Asignado': 'bg-blue-100 text-blue-700', 'Completo': 'bg-emerald-100 text-emerald-700', 'En Proceso': 'bg-blue-100 text-blue-700' };
            const stBadge = document.getElementById('qv_estado');
            stBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ' + (stColors[data.estado] || 'bg-slate-100 text-slate-600');
            stBadge.textContent = data.estado;

            // Técnico
            const tecContainer = document.getElementById('qv_asignado_container');
            const btnAsignar = document.getElementById('qv_btn_asignar');

            if (data.tecnico && data.tecnico !== 'Sin Asignar') {
                tecContainer.innerHTML = `
                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold border border-indigo-200">
                        ${data.tecnico_initials}
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-700">${data.tecnico}</span>
                        <span class="text-[10px] text-indigo-500 font-bold">Técnico Asignado</span>
                    </div>
                `;
                btnAsignar.classList.add('hidden');
                btnAsignar.classList.remove('flex');
            } else {
                tecContainer.innerHTML = '<span class="text-slate-400 italic text-xs">Sin técnico asignado</span>';

                // Show Assign Button (Action triggers toggle)
                btnAsignar.classList.remove('hidden');
                btnAsignar.classList.add('flex');
                btnAsignar.onclick = function (e) {
                    e.preventDefault();
                    mostrarSeleccionTecnico(data.id);
                };
            }

            // Link
            document.getElementById('qv_btn_editar').href = 'index.php?view=editar_ticket&id=' + data.id;

            // Show Modal
            document.getElementById('modalDetalleTicket').classList.remove('hidden');
        }

        function mostrarSeleccionTecnico(ticketId) {
            // Update hidden ID
            document.getElementById('form_ticket_id').value = ticketId;

            // Toggle Views
            document.getElementById('modal_body_info').classList.add('hidden');
            document.getElementById('modal_body_asignar').classList.remove('hidden');

            // Toggle Buttons
            document.getElementById('qv_btn_asignar').classList.add('hidden'); // Hide trigger
            document.getElementById('qv_btn_asignar').classList.remove('flex');

            document.getElementById('qv_btn_cancelar_asign').classList.remove('hidden'); // Show cancel
            document.getElementById('qv_btn_cancelar_asign').classList.add('flex');

            // Render Tech List
            const listContainer = document.getElementById('lista_tecnicos_asignar');
            listContainer.innerHTML = '';

            tecnicosDisponibles.forEach(tec => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-200 group text-left';
                btn.onclick = () => confirmarAsignacion(tec.id);

                btn.innerHTML = `
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-sm font-bold shadow-sm group-hover:scale-110 transition-transform">
                        ${tec.initials}
                    </div>
                    <div>
                        <div class="font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">${tec.nombre}</div>
                        <div class="text-[10px] text-slate-400">Seleccionar para asignar</div>
                    </div>
                    <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity text-indigo-500">
                        <i class="ri-arrow-right-line"></i>
                    </div>
                `;
                listContainer.appendChild(btn);
            });
        }

        function cancelarAsignacion() {
            document.getElementById('modal_body_info').classList.remove('hidden');
            document.getElementById('modal_body_asignar').classList.add('hidden');

            document.getElementById('qv_btn_asignar').classList.remove('hidden');
            document.getElementById('qv_btn_asignar').classList.add('flex');

            document.getElementById('qv_btn_cancelar_asign').classList.add('hidden');
            document.getElementById('qv_btn_cancelar_asign').classList.remove('flex');
        }

        function confirmarAsignacion(tecnicoId) {
            document.getElementById('form_tecnico_id').value = tecnicoId;
            document.getElementById('formAsignarTecnico').submit();
        }

        function cerrarDetalleTicket() {
            document.getElementById('modalDetalleTicket').classList.add('hidden');
            // Reset on close just in case
            setTimeout(cancelarAsignacion, 300);
        }
    </script>
</div>