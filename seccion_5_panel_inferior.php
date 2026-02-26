<?php
/**
 * seccion_5_panel_inferior.php - Panel de control según rol de usuario
 */

// Este archivo se incluye desde index.php, donde ya están definidas las variables:
// $rol_usuario, $usuario_id, $pdo, $tickets

// $rol_usuario, $usuario_id, $pdo, $tickets

?>
<style>
    /* Custom Scrollbar for Kanban Columns */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }
</style>
<?php

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

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

        <!-- COLUMNA IZQUIERDA: Acciones + Stats (1/4) -->
        <div class="w-full lg:w-1/4 flex flex-col gap-5 overflow-y-auto custom-scrollbar pb-4">

            <!-- Acciones Rápidas (Premium Design) -->
            <div
                class="relative overflow-hidden group shrink-0 rounded-3xl bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 p-6 shadow-xl border border-indigo-500/20 text-white">
                <div
                    class="absolute -bottom-20 -right-20 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl group-hover:bg-indigo-500/40 transition-colors duration-500">
                </div>
                <div
                    class="absolute -top-20 -left-20 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl group-hover:bg-purple-500/40 transition-colors duration-500">
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-5">
                        <div
                            class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-md border border-white/10">
                            <i class="ri-dashboard-line text-indigo-300"></i>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-100">Panel de Admin</h3>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <a href="index.php?view=usuarios"
                            class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-indigo-500/30 transition-all group/item">
                            <div
                                class="w-8 h-8 rounded-xl bg-indigo-500/20 text-indigo-300 flex items-center justify-center group-hover/item:scale-110 group-hover/item:bg-indigo-500/40 transition-all shadow-inner shadow-white/5">
                                <i class="ri-user-settings-line"></i>
                            </div>
                            <span
                                class="font-medium text-sm text-indigo-50 group-hover/item:text-white transition-colors">Gestionar
                                Usuarios</span>
                        </a>
                        <a href="index.php?view=listados"
                            class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-violet-500/30 transition-all group/item">
                            <div
                                class="w-8 h-8 rounded-xl bg-violet-500/20 text-violet-300 flex items-center justify-center group-hover/item:scale-110 group-hover/item:bg-violet-500/40 transition-all shadow-inner shadow-white/5">
                                <i class="ri-list-check"></i>
                            </div>
                            <span
                                class="font-medium text-sm text-indigo-50 group-hover/item:text-white transition-colors">Todos
                                los Tickets</span>
                        </a>
                        <a href="index.php?view=listados&filter=abiertos"
                            class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-amber-500/30 transition-all group/item">
                            <div
                                class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-300 flex items-center justify-center group-hover/item:scale-110 group-hover/item:bg-amber-500/40 transition-all shadow-inner shadow-white/5">
                                <i class="ri-inbox-line"></i>
                            </div>
                            <span
                                class="font-medium text-sm text-indigo-50 group-hover/item:text-white transition-colors">Sin
                                Asignar</span>
                        </a>
                        <a href="index.php?view=listados&filter=prioridad_alta"
                            class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-rose-500/30 transition-all group/item">
                            <div
                                class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-300 flex items-center justify-center group-hover/item:scale-110 group-hover/item:bg-rose-500/40 transition-all shadow-inner shadow-white/5">
                                <i class="ri-alarm-warning-line"></i>
                            </div>
                            <span
                                class="font-medium text-sm text-indigo-50 group-hover/item:text-white transition-colors">Tickets
                                Críticos</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Principales (Premium) -->
            <div
                class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm shrink-0 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-lg"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Resumen General</h3>
                </div>
                <div class="space-y-3">
                    <a href="index.php?view=listados"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-blue-500 group-hover:text-white transition-all">
                                <i class="ri-ticket-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-blue-700 transition-colors">Total
                                Tickets</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-blue-600 transition-colors"><?= $total_tickets ?></span>
                    </a>
                    <a href="index.php?view=listados&filter=abiertos"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-amber-200 hover:bg-amber-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                <i class="ri-inbox-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-amber-700 transition-colors">Pendientes</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-amber-600 transition-colors"><?= $tickets_abiertos ?></span>
                    </a>
                    <a href="index.php?view=listados&filter=asignado"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-purple-200 hover:bg-purple-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-purple-500 group-hover:text-white transition-all">
                                <i class="ri-loader-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-purple-700 transition-colors">Asignados</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-purple-600 transition-colors"><?= $tickets_en_proceso ?></span>
                    </a>
                    <a href="index.php?view=listados&filter=completo"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                                <i class="ri-checkbox-circle-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-emerald-700 transition-colors">Completos</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-emerald-600 transition-colors"><?= $tickets_resueltos ?></span>
                    </a>
                </div>
            </div>


            <!-- Stats Adicionales (Premium) -->
            <div
                class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm shrink-0 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center">
                        <i class="ri-pulse-line text-lg"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Alertas y Actividad</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center">
                                <i class="ri-calendar-event-line text-sm"></i>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">Creados Hoy</span>
                        </div>
                        <span class="text-lg font-black text-slate-800"><?= $tickets_hoy ?></span>
                    </div>
                    <a href="index.php?view=listados&filter=abiertos"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-amber-200 hover:bg-amber-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all">
                                <i class="ri-user-unfollow-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-amber-700 transition-colors">Sin
                                Técnico</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-amber-600 transition-colors"><?= $tickets_sin_asignar_tecnico ?></span>
                    </a>
                    <a href="index.php?view=listados&filter=prioridad_alta"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-rose-200 hover:bg-rose-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-rose-100 text-rose-500 flex items-center justify-center group-hover:scale-110 group-hover:bg-rose-500 group-hover:text-white transition-all">
                                <i class="ri-alarm-warning-line text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-rose-600 transition-colors">Críticos</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-rose-600 transition-colors"><?= $tickets_criticos ?></span>
                    </a>
                </div>
            </div>

            <!-- Por País / Sede (Premium) -->
            <div
                class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm shrink-0 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                        <i class="ri-map-pin-2-line text-lg"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Por País / Sede</h3>
                </div>
                <div class="space-y-3">
                    <a href="index.php?view=listados&filter=pais_nicaragua"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-cyan-200 hover:bg-cyan-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-md shadow-cyan-500/20">
                                <i class="ri-map-pin-2-fill text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-cyan-700 transition-colors">Nicaragua</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-cyan-600 transition-colors"><?= $tickets_nicaragua ?></span>
                    </a>
                    <a href="index.php?view=listados&filter=pais_honduras"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-fuchsia-200 hover:bg-fuchsia-50/50 hover:shadow-sm transition-all group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl bg-gradient-to-br from-fuchsia-400 to-purple-500 text-white flex items-center justify-center group-hover:scale-110 transition-transform shadow-md shadow-fuchsia-500/20">
                                <i class="ri-global-fill text-sm"></i>
                            </div>
                            <span
                                class="text-sm font-semibold text-slate-700 group-hover:text-fuchsia-700 transition-colors">Honduras</span>
                        </div>
                        <span
                            class="text-lg font-black text-slate-800 group-hover:text-fuchsia-600 transition-colors"><?= $tickets_honduras ?></span>
                    </a>
                </div>
            </div>

            <!-- Por Empresa (Premium) -->
            <?php if (!empty($stats_empresas)): ?>
                <div
                    class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm shrink-0 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="ri-building-2-line text-lg"></i>
                        </div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Por Empresa</h3>
                    </div>
                    <div class="space-y-3">
                        <?php
                        $colores_empresas_left = [
                            ['bg' => 'bg-pink-100', 'text_color' => 'text-pink-600', 'hover_border' => 'hover:border-pink-200', 'hover_bg' => 'hover:bg-pink-50/50'],
                            ['bg' => 'bg-amber-100', 'text_color' => 'text-amber-600', 'hover_border' => 'hover:border-amber-200', 'hover_bg' => 'hover:bg-amber-50/50'],
                            ['bg' => 'bg-emerald-100', 'text_color' => 'text-emerald-600', 'hover_border' => 'hover:border-emerald-200', 'hover_bg' => 'hover:bg-emerald-50/50'],
                            ['bg' => 'bg-indigo-100', 'text_color' => 'text-indigo-600', 'hover_border' => 'hover:border-indigo-200', 'hover_bg' => 'hover:bg-indigo-50/50'],
                            ['bg' => 'bg-cyan-100', 'text_color' => 'text-cyan-600', 'hover_border' => 'hover:border-cyan-200', 'hover_bg' => 'hover:bg-cyan-50/50'],
                        ];
                        $ei = 0;
                        foreach ($stats_empresas as $id_emp => $data):
                            $ec = $colores_empresas_left[$ei % count($colores_empresas_left)];
                            $ei++;
                            ?>
                            <a href="index.php?view=listados&filter=empresa_<?= $id_emp ?>"
                                class="flex items-center justify-between p-3 bg-slate-50 rounded-2xl border border-slate-100 <?= $ec['hover_border'] ?> <?= $ec['hover_bg'] ?> hover:shadow-sm transition-all group">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div
                                        class="w-8 h-8 rounded-xl <?= $ec['bg'] ?> <?= $ec['text_color'] ?> flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                                        <i class="ri-building-4-line text-sm"></i>
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-slate-700 truncate group-hover:<?= $ec['text_color'] ?> transition-colors"
                                        title="<?= htmlspecialchars($data['nombre']) ?>"><?= htmlspecialchars($data['nombre']) ?></span>
                                </div>
                                <span
                                    class="text-lg font-black text-slate-800 shrink-0 ml-2 group-hover:<?= $ec['text_color'] ?> transition-colors"><?= $data['total'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- COLUMNA DERECHA: Perfil + Tabla (3/4) -->
        <div
            class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-5">

            <!-- Tarjeta de Perfil Admin (Premium Design) -->
            <div class="relative overflow-hidden group shrink-0 rounded-3xl bg-white border border-slate-200">
                <!-- Background Gradients -->
                <div
                    class="absolute -top-24 -right-24 w-64 h-64 bg-gradient-to-br from-indigo-500/20 to-purple-500/20 rounded-full blur-3xl opacity-50 group-hover:opacity-70 transition-opacity duration-500">
                </div>

                <div
                    class="relative z-10 p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white/40 backdrop-blur-xl">
                    <div class="flex items-center gap-5">
                        <div class="relative">
                            <div
                                class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-[2px] shadow-xl shadow-indigo-500/20 group-hover:shadow-indigo-500/40 transition-shadow">
                                <div class="w-full h-full rounded-2xl bg-white flex items-center justify-center">
                                    <span
                                        class="text-2xl font-black bg-gradient-to-br from-indigo-600 to-violet-700 bg-clip-text text-transparent">
                                        <?= strtoupper(substr($GLOBALS['nombre_usuario'] ?? 'A', 0, 1)) ?>
                                    </span>
                                </div>
                            </div>
                            <div
                                class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full border-2 border-white flex items-center justify-center shadow-sm">
                                <i class="ri-check-line text-white text-[10px]"></i>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800 tracking-tight mb-1">
                                Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'] ?? 'Admin')[0]) ?>
                            </h2>
                            <p class="text-slate-500 text-sm font-medium flex items-center gap-1.5">
                                <span
                                    class="flex items-center justify-center w-5 h-5 rounded-md bg-indigo-50 text-indigo-600">
                                    <i class="ri-shield-star-fill text-[11px]"></i>
                                </span>
                                Panel de Administración
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 flex-wrap">
                        <div
                            class="bg-white/60 backdrop-blur-md rounded-2xl p-4 border border-slate-100 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-md transition-all min-w-[90px] flex flex-col items-center justify-center">
                            <span
                                class="block text-3xl font-black text-blue-600 tracking-tight leading-none mb-1"><?= $total_tickets ?></span>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total</span>
                        </div>
                        <div
                            class="bg-white/60 backdrop-blur-md rounded-2xl p-4 border border-slate-100 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-md transition-all min-w-[90px] flex flex-col items-center justify-center">
                            <span
                                class="block text-3xl font-black text-amber-500 tracking-tight leading-none mb-1"><?= $tickets_abiertos ?></span>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Pendientes</span>
                        </div>
                        <div
                            class="bg-white/60 backdrop-blur-md rounded-2xl p-4 border border-slate-100 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-md transition-all min-w-[90px] flex flex-col items-center justify-center">
                            <span
                                class="block text-3xl font-black text-rose-500 tracking-tight leading-none mb-1"><?= $tickets_criticos ?></span>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Críticos</span>
                        </div>
                        <div
                            class="bg-gradient-to-br from-indigo-50 to-violet-50 rounded-2xl p-4 border border-indigo-100 shadow-sm shadow-indigo-100 hover:-translate-y-1 hover:shadow-md transition-all min-w-[90px] flex flex-col items-center justify-center relative overflow-hidden">
                            <i class="ri-calendar-event-line absolute -right-2 -bottom-2 text-4xl text-indigo-500/10"></i>
                            <span
                                class="relative block text-3xl font-black text-indigo-600 tracking-tight leading-none mb-1"><?= $tickets_hoy ?></span>
                            <span class="relative text-[10px] uppercase font-bold text-indigo-400 tracking-wider">Hoy</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mis Tickets Asignados (Premium) -->
            <?php
            $mis_tickets_admin = array_filter($tickets, function ($t) use ($usuario_id) {
                return $t['tecnico_id'] == $usuario_id && !in_array($t['estado'], ['Completo', 'Resuelto', 'Cerrado']);
            });
            if (!empty($mis_tickets_admin)):
                ?>
                <div
                    class="bg-indigo-50/50 backdrop-blur-sm border border-indigo-100 rounded-3xl overflow-hidden shrink-0 shadow-sm">
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-indigo-600 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md">
                            <i class="ri-checkbox-multiple-line text-white"></i>
                        </div>
                        <h3 class="text-sm font-bold text-white tracking-wide">Mis Tickets Asignados</h3>
                        <span
                            class="ml-auto bg-white/20 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1 rounded-full shadow-inner shadow-white/10"><?= count($mis_tickets_admin) ?></span>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <tbody class="divide-y-0">
                                <?php foreach ($mis_tickets_admin as $t):
                                    $prio_colors = ['Baja' => 'bg-emerald-100/50 text-emerald-700 border-emerald-200', 'Media' => 'bg-amber-100/50 text-amber-700 border-amber-200', 'Alta' => 'bg-orange-100/50 text-orange-700 border-orange-200', 'Critica' => 'bg-rose-100/50 text-rose-700 border-rose-200'];
                                    $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-100/50 text-slate-700 border-slate-200';
                                    $row_class = 'bg-white hover:bg-indigo-50/80 cursor-pointer transition-all duration-200 shadow-sm border border-transparent hover:border-indigo-100 rounded-2xl group';
                                    if (stripos($t['titulo'], 'Nuevo Ingreso') !== false)
                                        $row_class = 'bg-emerald-50/50 hover:bg-emerald-50 cursor-pointer transition-all duration-200 shadow-sm border border-emerald-100 rounded-2xl group';
                                    elseif (stripos($t['titulo'], 'Baja de Personal') !== false)
                                        $row_class = 'bg-rose-50/50 hover:bg-rose-50 cursor-pointer transition-all duration-200 shadow-sm border border-rose-100 rounded-2xl group';
                                    ?>
                                    <tr class="<?= $row_class ?> relative block md:table-row mb-2 md:mb-0"
                                        onclick="window.location.href='index.php?view=editar_ticket&id=<?= $t['id'] ?>'">
                                        <td class="px-5 py-4 whitespace-nowrap md:rounded-l-2xl">
                                            <span
                                                class="text-sm font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">#<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        <td class="px-5 py-4 w-full">
                                            <div
                                                class="text-sm font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">
                                                <?= htmlspecialchars($t['titulo']) ?>
                                            </div>
                                            <div class="text-xs text-slate-400 mt-1 line-clamp-1">
                                                <?= htmlspecialchars($t['categoria']) ?>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap hidden sm:table-cell">
                                            <span
                                                class="px-3 py-1 rounded-xl text-xs font-bold border <?= $p_class ?> flex items-center gap-1 w-max">
                                                <i class="ri-flag-fill text-[10px]"></i> <?= htmlspecialchars($t['prioridad']) ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right md:rounded-r-2xl hidden sm:table-cell">
                                            <div
                                                class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors ml-auto">
                                                <i class="ri-arrow-right-line"></i>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabla Tickets Recientes (Premium) -->
            <div class="flex-1 overflow-hidden flex flex-col bg-white rounded-3xl border border-slate-200 shadow-sm">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i class="ri-list-check text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 tracking-tight">Tickets Recientes</h2>
                            <p class="text-xs text-slate-500">Últimos 10 tickets del sistema</p>
                        </div>
                    </div>
                    <a href="index.php?view=listados"
                        class="flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 px-4 py-2 rounded-xl transition-all shadow-sm hover:shadow-indigo-500/30">
                        <span>Ver Todos</span>
                        <i class="ri-arrow-right-line text-xs"></i>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-white/80 backdrop-blur-md sticky top-0 z-10">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    Ticket</th>
                                <th
                                    class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest hidden sm:table-cell">
                                    Fecha</th>
                                <th
                                    class="px-6 py-4 text-left text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    Solicitante</th>
                                <th
                                    class="px-6 py-4 text-center text-[11px] font-black text-slate-400 uppercase tracking-widest hidden md:table-cell">
                                    Estado / Prioridad</th>
                                <th
                                    class="px-6 py-4 text-center text-[11px] font-black text-slate-400 uppercase tracking-widest">
                                    Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-50">
                            <?php
                            if (empty($tickets)) {
                                echo "<tr><td colspan='5' class='px-6 py-12 text-center text-slate-400'><i class='ri-inbox-line text-4xl mb-3 block opacity-20'></i><span class='font-medium'>No hay tickets registrados.</span></td></tr>";
                            } else {
                                $tickets_listar = $tickets;
                                usort($tickets_listar, function ($a, $b) {
                                    $estados_fin = ['Completo', 'Resuelto', 'Cerrado'];
                                    $a_fin = in_array($a['estado'], $estados_fin);
                                    $b_fin = in_array($b['estado'], $estados_fin);
                                    $a_assigned = !empty($a['tecnico_id']);
                                    $b_assigned = !empty($b['tecnico_id']);
                                    $tier_a = $a_fin ? 2 : ($a_assigned ? 1 : 0);
                                    $tier_b = $b_fin ? 2 : ($b_assigned ? 1 : 0);
                                    if ($tier_a !== $tier_b)
                                        return $tier_a - $tier_b;
                                    return $b['id'] - $a['id'];
                                });
                                $tickets_recientes = array_slice($tickets_listar, 0, 10);
                                foreach ($tickets_recientes as $t) {
                                    $prio_colors = ['Baja' => 'bg-emerald-50 text-emerald-600 border-emerald-100', 'Media' => 'bg-amber-50 text-amber-600 border-amber-100', 'Alta' => 'bg-orange-50 text-orange-600 border-orange-100', 'Critica' => 'bg-rose-50 text-rose-600 border-rose-100'];
                                    $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-600 border-slate-100';

                                    $status_colors = ['Pendiente' => 'bg-yellow-100 text-yellow-700', 'Asignado' => 'bg-blue-100 text-blue-700', 'Completo' => 'bg-emerald-100 text-emerald-700'];
                                    $s_class = $status_colors[$t['estado']] ?? 'bg-slate-100 text-slate-700';

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

                                    $row_class = 'hover:bg-slate-50/80 transition-all duration-200 cursor-pointer group/row';
                                    if (stripos($t['titulo'], 'Nuevo Ingreso') !== false)
                                        $row_class = 'bg-emerald-50/30 hover:bg-emerald-50/80 transition-colors border-l-2 border-transparent hover:border-emerald-400 cursor-pointer group/row';
                                    elseif (stripos($t['titulo'], 'Baja de Personal') !== false)
                                        $row_class = 'bg-rose-50/30 hover:bg-rose-50/80 transition-colors border-l-2 border-transparent hover:border-rose-400 cursor-pointer group/row';

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

                                    echo '<tr class="' . $row_class . '" onclick="verDetallesTicket(this)" data-ticket="' . $ticket_data_json . '">';

                                    // Columna 1: ID y Titulo
                                    echo '<td class="px-6 py-4">';
                                    echo '<div class="flex items-start gap-3">';
                                    echo '<span class="font-mono text-[10px] font-black text-slate-400 bg-white border border-slate-200 px-1.5 py-0.5 rounded-md mt-0.5 shadow-sm">#' . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . '</span>';
                                    echo '<div>';
                                    echo '<div class="text-sm font-bold text-slate-800 group-hover/row:text-indigo-600 transition-colors line-clamp-1">' . htmlspecialchars($t['titulo']) . '</div>';
                                    echo '<div class="text-[11px] font-medium text-slate-400 mt-1">' . htmlspecialchars($t['categoria']) . '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</td>';

                                    // Columna 2: Fecha
                                    echo '<td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">';
                                    echo '<div class="flex flex-col">';
                                    echo '<span class="text-sm font-semibold text-slate-700">' . date('d M', strtotime($t['fecha_creacion'])) . '</span>';
                                    echo '<span class="text-[10px] uppercase font-bold text-slate-400">' . date('Y', strtotime($t['fecha_creacion'])) . '</span>';
                                    echo '</div>';
                                    echo '</td>';

                                    // Columna 3: Solicitante
                                    echo '<td class="px-6 py-4">';
                                    echo '<div class="flex items-center gap-2">';
                                    echo '<div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-xs font-bold border border-slate-200 shrink-0">' . strtoupper(substr($nombre_creador, 0, 1)) . '</div>';
                                    echo '<div class="flex flex-col hidden sm:flex">';
                                    echo '<span class="text-sm font-bold text-slate-700 truncate max-w-[120px]">' . htmlspecialchars($nombre_creador) . '</span>';
                                    echo '<span class="text-[10px] font-bold ' . $r_class . ' truncate max-w-[120px]"> ' . htmlspecialchars($rol_creador) . '</span>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</td>';

                                    // Columna 4: Estado y Prioridad
                                    echo '<td class="px-6 py-4 hidden md:table-cell">';
                                    echo '<div class="flex flex-col items-center gap-2">';
                                    echo '<span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider ' . $p_class . ' border w-full text-center flex items-center justify-center gap-1"><i class="ri-flag-fill"></i> ' . $t['prioridad'] . '</span>';

                                    echo '<div class="flex items-center gap-1 w-full justify-center">';
                                    echo '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ' . $s_class . '">' . $t['estado'] . '</span>';
                                    if ($t['estado'] === 'Asignado' && !empty($t['tecnico_id'])) {
                                        echo '<div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[9px] font-bold border border-indigo-200" title="' . htmlspecialchars($nombre_tecnico) . '">' . $initials_tecnico . '</div>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</td>';

                                    // Columna 5: Acciones
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-center">';
                                    echo '<div class="flex items-center justify-center gap-2">';
                                    echo '<button type="button" class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 border border-slate-100 hover:bg-sky-50 hover:text-sky-600 hover:border-sky-200 transition-all flex items-center justify-center shadow-sm" title="Vista Rápida"><i class="ri-eye-line text-sm"></i></button>';
                                    echo '<button onclick="window.location.href=\'index.php?view=editar_ticket&id=' . $t['id'] . '\'; event.stopPropagation();" class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 border border-slate-100 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all flex items-center justify-center shadow-sm" title="Editar"><i class="ri-edit-box-line text-sm"></i></button>';
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

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

        <!-- COLUMNA IZQUIERDA: Sidebar de Herramientas (1/4) -->
        <div class="w-full lg:w-1/4 flex flex-col gap-6">



            <!-- 2. Acciones Rápidas (Premium Quick Actions) -->
            <div
                class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-6 shadow-xl text-white relative overflow-hidden shrink-0">
                <div
                    class="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-full blur-3xl -mr-12 -mb-12 pointer-events-none">
                </div>
                <div
                    class="absolute top-0 left-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl -ml-10 -mt-10 pointer-events-none">
                </div>

                <div class="flex items-center justify-between mb-5 relative z-10">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <i class="ri-flashlight-line text-amber-400"></i> Acciones Rápidas
                    </h3>
                </div>

                <div class="flex flex-col gap-3 relative z-10" id="lista-acciones">
                    <!-- Acciones Default -->
                    <a href="index.php?view=mantenimiento_equipos"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/0 to-blue-500/0 group-hover:from-blue-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform relative z-10">
                            <i class="ri-tools-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-slate-200 group-hover:text-white transition-colors relative z-10">Registrar
                            Mantenimiento</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-slate-500 group-hover:text-blue-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=inventario"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-emerald-500/0 via-emerald-500/0 to-emerald-500/0 group-hover:from-emerald-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform relative z-10">
                            <i class="ri-qr-code-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-slate-200 group-hover:text-white transition-colors relative z-10">Buscar
                            Equipo</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-slate-500 group-hover:text-emerald-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=historial_tecnico"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-amber-500/0 via-amber-500/0 to-amber-500/0 group-hover:from-amber-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30 group-hover:scale-110 group-hover:-rotate-3 transition-transform relative z-10">
                            <i class="ri-history-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-slate-200 group-hover:text-white transition-colors relative z-10">Historial
                            Reparaciones</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-slate-500 group-hover:text-amber-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=registros_365"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-sky-500/0 via-sky-500/0 to-sky-500/0 group-hover:from-sky-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shadow-lg shadow-sky-500/30 group-hover:scale-110 transition-transform relative z-10">
                            <i class="ri-microsoft-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-slate-200 group-hover:text-white transition-colors relative z-10">Cuentas
                            365</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-slate-500 group-hover:text-sky-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>
                </div>
            </div>

            <!-- 3. Herramientas / Utiles (Premium) -->
            <div
                class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex-1 flex flex-col hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-5 shrink-0">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2">
                        <i class="ri-pushpin-2-line text-indigo-400"></i> Accesos Directos
                    </h3>
                    <button type="button" onclick="openAddToolModal()"
                        class="w-8 h-8 flex items-center justify-center bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-100 rounded-xl transition-all text-slate-400 hover:text-indigo-600 hover:scale-105"
                        title="Agregar Herramienta">
                        <i class="ri-add-line text-sm font-bold"></i>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-3 overflow-y-auto custom-scrollbar pr-1 pb-1">
                    <!-- Herramientas Personalizadas -->
                    <?php
                    try {
                        $stmt_tools = $pdo->prepare("SELECT * FROM herramientas_tecnico WHERE usuario_id = ? ORDER BY created_at DESC");
                        $stmt_tools->execute([$usuario_id]);
                        $herramientas = $stmt_tools->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($herramientas as $h):
                            // Colores aleatorios premium
                            $colors = [
                                ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'border' => 'border-indigo-100', 'hover' => 'hover:border-indigo-300'],
                                ['bg' => 'bg-pink-50', 'text' => 'text-pink-600', 'border' => 'border-pink-100', 'hover' => 'hover:border-pink-300'],
                                ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-600', 'border' => 'border-cyan-100', 'hover' => 'hover:border-cyan-300'],
                                ['bg' => 'bg-violet-50', 'text' => 'text-violet-600', 'border' => 'border-violet-100', 'hover' => 'hover:border-violet-300']
                            ];
                            $c = $colors[$h['id'] % count($colors)];
                            ?>
                            <div class="group relative flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-slate-100 <?= $c['hover'] ?> hover:shadow-sm transition-all gap-2 text-slate-600 hover:text-slate-900 cursor-pointer overflow-hidden"
                                onclick="window.open('<?= htmlspecialchars($h['url']) ?>', '_blank')">

                                <div
                                    class="w-10 h-10 rounded-xl <?= $c['bg'] ?> border <?= $c['border'] ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="<?= htmlspecialchars($h['icono']) ?> text-xl <?= $c['text'] ?>"></i>
                                </div>

                                <span
                                    class="text-[10px] font-bold text-center truncate w-full group-hover:text-slate-900 transition-colors"><?= htmlspecialchars($h['nombre']) ?></span>

                                <!-- Botones Hover -->
                                <div class="absolute inset-0 bg-white/90 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                                    onclick="event.stopPropagation();">
                                    <button onclick="editarHerramienta(<?= htmlspecialchars(json_encode($h)) ?>)"
                                        class="w-8 h-8 rounded-full bg-white shadow-md text-slate-500 hover:text-indigo-600 hover:scale-110 hover:shadow-lg transition-all flex items-center justify-center"
                                        title="Editar">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <button onclick="eliminarHerramienta(<?= $h['id'] ?>, this)"
                                        class="w-8 h-8 rounded-full bg-white shadow-md text-slate-500 hover:text-rose-600 hover:scale-110 hover:shadow-lg transition-all flex items-center justify-center"
                                        title="Eliminar">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach;
                    } catch (PDOException $e) { /* Ignore */
                    }
                    ?>
                </div>
            </div>

        </div>

        <!-- COLUMNA DERECHA: Tablero Kanban (3/4) -->
        <div
            class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

            <!-- 1. Tarjeta de Perfil & Resumen (Premium) -->
            <div
                class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-3xl p-6 shadow-sm relative overflow-hidden group shrink-0">
                <div
                    class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-purple-500/10 blur-2xl group-hover:bg-purple-500/20 transition-all pointer-events-none">
                </div>
                <div
                    class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-blue-500/10 blur-2xl group-hover:bg-blue-500/20 transition-all pointer-events-none">
                </div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                            <i class="ri-user-settings-line text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-800 tracking-tight flex items-center gap-2 mb-0.5">
                                Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                            </h2>
                            <p class="text-slate-500 text-sm font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Técnico en Línea
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div
                            class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl p-3 border border-indigo-100/50 text-center min-w-[110px] hover:shadow-md hover:scale-105 transition-all">
                            <span
                                class="block text-3xl font-black text-indigo-600 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-600"><?= $total_asignados ?></span>
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mt-1 block">Asignados</span>
                        </div>
                        <div
                            class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-3 border border-orange-100/50 text-center min-w-[110px] hover:shadow-md hover:scale-105 transition-all">
                            <span
                                class="block text-3xl font-black text-orange-600 bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-amber-600"><?= $mis_abiertos ?></span>
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-orange-400 mt-1 block">Pendientes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tablero Kanban Header -->
            <div class="flex items-center justify-between mb-2 px-2">
                <h2 class="text-lg font-black text-slate-800 flex items-center gap-2 tracking-tight">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="ri-kanban-view-2 text-lg"></i>
                    </div>
                    Tablero de Trabajo
                </h2>
                <div class="flex gap-2">
                    <button onclick="location.reload()"
                        class="p-2.5 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all text-sm shadow-sm hover:shadow-md"
                        title="Refrescar Tablero">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>

            <!-- KANBAN COLUMNS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 flex-1 h-0 min-h-0 overflow-hidden px-1">

                <!-- 1. PENDIENTES -->
                <div class="flex flex-col h-full bg-slate-50/50 rounded-3xl border border-slate-200/60 p-3 overflow-hidden">
                    <div class="flex items-center justify-between mb-4 px-2 pt-1">
                        <span
                            class="text-[11px] font-black uppercase text-slate-500 tracking-widest flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-sm bg-orange-500 shadow-sm shadow-orange-500/50"></div>
                            Pendientes
                        </span>
                        <span
                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-600 border border-slate-200 shadow-sm"><?= $mis_abiertos ?></span>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-3 pr-2 pb-20 custom-scrollbar">
                        <?php
                        $pendientes = array_filter($mis_tickets, fn($t) => $t['estado'] === 'Pendiente');
                        if (empty($pendientes)): ?>
                            <div
                                class="text-center py-12 opacity-60 bg-white/50 border-2 border-dashed border-slate-200 rounded-2xl m-2 flex flex-col items-center justify-center">
                                <div
                                    class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                    <i class="ri-check-double-line text-2xl"></i>
                                </div>
                                <span class="text-sm font-bold text-slate-500">Todo al día</span>
                            </div>
                        <?php else:
                            foreach ($pendientes as $t):
                                $prio_colors = ['Baja' => 'bg-emerald-50 text-emerald-600 border-emerald-100 ring-emerald-500/10', 'Media' => 'bg-amber-50 text-amber-600 border-amber-100 ring-amber-500/10', 'Alta' => 'bg-orange-50 text-orange-600 border-orange-100 ring-orange-500/10', 'Critica' => 'bg-rose-50 text-rose-600 border-rose-100 ring-rose-500/10'];
                                $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-600 border-slate-100 ring-slate-500/10';
                                ?>
                                <div onclick="window.location.href='index.php?view=editar_ticket&id=<?= $t['id'] ?>'"
                                    class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-orange-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer group relative overflow-hidden ring-1 ring-transparent hover:ring-orange-500/20">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                    </div>

                                    <div class="flex justify-between items-start mb-3">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-md bg-slate-100 text-[10px] font-black text-slate-500 tracking-wider">
                                            #<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border <?= $p_class ?> ring-1 ring-inset">
                                            <?= $t['prioridad'] ?>
                                        </span>
                                    </div>

                                    <h4
                                        class="text-sm font-bold text-slate-800 leading-tight mb-2 line-clamp-2 group-hover:text-orange-600 transition-colors">
                                        <?= htmlspecialchars($t['titulo']) ?>
                                    </h4>

                                    <p class="text-xs text-slate-500 line-clamp-2 mb-4 leading-relaxed">
                                        <?= htmlspecialchars($t['descripcion']) ?>
                                    </p>

                                    <div class="flex items-center justify-between pt-3 border-t border-slate-100/80">
                                        <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-400">
                                            <i class="ri-time-line"></i> <?= date('d M Y', strtotime($t['fecha_creacion'])) ?>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[10px] font-bold text-slate-400"><?= strtoupper(substr($t['creador'] ?? 'U', 0, 15)) ?>...</span>
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-[10px] font-black text-slate-600 shadow-sm border border-slate-200"
                                                title="Solicitante">
                                                <?= strtoupper(substr($t['creador'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- 2. EN PROCESO -->
                <div class="flex flex-col h-full bg-slate-50/50 rounded-3xl border border-slate-200/60 p-3 overflow-hidden">
                    <div class="flex items-center justify-between mb-4 px-2 pt-1">
                        <span
                            class="text-[11px] font-black uppercase text-slate-500 tracking-widest flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-sm bg-blue-500 shadow-sm shadow-blue-500/50"></div> En Proceso
                        </span>
                        <span
                            class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white text-slate-600 border border-slate-200 shadow-sm"><?= $mis_en_proceso ?></span>
                    </div>

                    <div class="flex-1 overflow-y-auto space-y-3 pr-2 pb-20 custom-scrollbar">
                        <?php
                        $proceso = array_filter($mis_tickets, fn($t) => $t['estado'] === 'Asignado');
                        if (empty($proceso)): ?>
                            <div
                                class="text-center py-12 opacity-60 bg-white/50 border-2 border-dashed border-slate-200 rounded-2xl m-2 flex flex-col items-center justify-center">
                                <div
                                    class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                                    <i class="ri-tools-line text-2xl"></i>
                                </div>
                                <span class="text-sm font-bold text-slate-500">Sin tickets activos</span>
                            </div>
                        <?php else:
                            foreach ($proceso as $t):
                                $prio_colors = ['Baja' => 'bg-emerald-50 text-emerald-600 border-emerald-100 ring-emerald-500/10', 'Media' => 'bg-amber-50 text-amber-600 border-amber-100 ring-amber-500/10', 'Alta' => 'bg-orange-50 text-orange-600 border-orange-100 ring-orange-500/10', 'Critica' => 'bg-rose-50 text-rose-600 border-rose-100 ring-rose-500/10'];
                                $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-600 border-slate-100 ring-slate-500/10';
                                ?>
                                <div onclick="window.location.href='index.php?view=editar_ticket&id=<?= $t['id'] ?>'"
                                    class="bg-white p-4 rounded-2xl shadow-sm border border-blue-200 hover:shadow-lg hover:border-blue-300 hover:-translate-y-1 transition-all duration-300 cursor-pointer group relative overflow-hidden ring-1 ring-blue-500/10 hover:ring-blue-500/20">
                                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>

                                    <div class="flex justify-between items-start mb-3">
                                        <span
                                            class="inline-flex items-center justify-center px-2 py-1 rounded-md bg-slate-100 text-[10px] font-black text-slate-500 tracking-wider">
                                            #<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border <?= $p_class ?> ring-1 ring-inset">
                                            <?= $t['prioridad'] ?>
                                        </span>
                                    </div>

                                    <h4
                                        class="text-sm font-bold text-slate-800 leading-tight mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                                        <?= htmlspecialchars($t['titulo']) ?>
                                    </h4>

                                    <p class="text-xs text-slate-500 line-clamp-2 mb-4 leading-relaxed">
                                        <?= htmlspecialchars($t['descripcion']) ?>
                                    </p>

                                    <div class="flex items-center justify-between pt-3 border-t border-slate-100/80">
                                        <span
                                            class="text-[11px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-md flex items-center gap-1.5 border border-blue-100">
                                            <i class="ri-loader-4-line animate-spin"></i> Trabajando
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-[10px] font-black text-slate-600 shadow-sm border border-slate-200"
                                                title="Solicitante">
                                                <?= strtoupper(substr($t['creador'] ?? 'U', 0, 1)) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>



        </div>
    </div>
    </div>

    <?php
    // -------------------------------------------------------------------------
    // 3. Dashboard para RRHH
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'RRHH') {
    // Fetch RRHH forms
    $formularios_rrhh = [];
    $stats_rrhh = ['ingresos' => 0, 'salidas' => 0, 'licencias' => 0, 'mes_ingresos' => 0, 'mes_salidas' => 0, 'mes_licencias' => 0];
    try {
        $stmt_rrhh = $pdo->query("SELECT * FROM formularios_rrhh ORDER BY id DESC LIMIT 50");
        $formularios_rrhh = $stmt_rrhh->fetchAll(PDO::FETCH_ASSOC);

        // Stats totales
        $stats_rrhh['ingresos'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Ingreso'));
        $stats_rrhh['salidas'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Salida'));
        $stats_rrhh['licencias'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Licencia'));

        // Stats del mes actual
        $mes_actual = date('Y-m');
        foreach ($formularios_rrhh as $f) {
            $fecha = $f['fecha_solicitud'] ?? $f['fecha_efectiva'] ?? '';
            if (str_starts_with($fecha, $mes_actual)) {
                if ($f['tipo'] === 'Ingreso')
                    $stats_rrhh['mes_ingresos']++;
                elseif ($f['tipo'] === 'Salida')
                    $stats_rrhh['mes_salidas']++;
                elseif ($f['tipo'] === 'Licencia')
                    $stats_rrhh['mes_licencias']++;
            }
        }
    } catch (PDOException $e) { /* ignore */
    }
    ?>

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

        <!-- COLUMNA IZQUIERDA: Acciones + Stats (1/4) -->
        <div class="w-full lg:w-1/4 flex flex-col gap-6">

            <!-- Acciones Rápidas (Premium) -->
            <div
                class="bg-gradient-to-br from-pink-700 to-rose-900 rounded-3xl p-6 shadow-xl text-white relative overflow-hidden shrink-0">
                <div
                    class="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-full blur-3xl -mr-12 -mb-12 pointer-events-none">
                </div>
                <div
                    class="absolute top-0 left-0 w-32 h-32 bg-pink-500/10 rounded-full blur-2xl -ml-10 -mt-10 pointer-events-none">
                </div>

                <div class="flex items-center justify-between mb-5 relative z-10">
                    <h3 class="text-xs font-black uppercase tracking-widest text-pink-200 flex items-center gap-2">
                        <i class="ri-flashlight-line text-rose-300"></i> Acciones Rápidas
                    </h3>
                </div>

                <div class="flex flex-col gap-3 relative z-10">
                    <a href="index.php?view=nuevo_ingreso"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-emerald-500/0 via-emerald-500/0 to-emerald-500/0 group-hover:from-emerald-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform relative z-10">
                            <i class="ri-user-add-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-pink-50 group-hover:text-white transition-colors relative z-10">Nueva
                            Alta</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-pink-300 group-hover:text-emerald-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=nueva_salida"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-rose-500/0 via-rose-500/0 to-rose-500/0 group-hover:from-rose-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform relative z-10">
                            <i class="ri-user-unfollow-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-pink-50 group-hover:text-white transition-colors relative z-10">Nueva
                            Baja</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-pink-300 group-hover:text-rose-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=solicitud_licencia"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-indigo-500/0 via-indigo-500/0 to-indigo-500/0 group-hover:from-indigo-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 group-hover:scale-110 group-hover:-rotate-3 transition-transform relative z-10">
                            <i class="ri-shield-keyhole-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-pink-50 group-hover:text-white transition-colors relative z-10">Solicitar
                            Licencia</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-pink-300 group-hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=historial_rrhh"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-amber-500/0 via-amber-500/0 to-amber-500/0 group-hover:from-amber-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform relative z-10">
                            <i class="ri-file-list-3-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-pink-50 group-hover:text-white transition-colors relative z-10">Ver
                            Historial</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-pink-300 group-hover:text-amber-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>

                    <a href="index.php?view=personal"
                        class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-violet-500/0 via-violet-500/0 to-violet-500/0 group-hover:from-violet-500/10 group-hover:to-transparent transition-all duration-300">
                        </div>
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/30 group-hover:scale-110 transition-transform relative z-10">
                            <i class="ri-contacts-book-line text-lg text-white"></i>
                        </div>
                        <span
                            class="font-bold text-sm text-pink-50 group-hover:text-white transition-colors relative z-10">Gestión
                            Personal</span>
                        <i
                            class="ri-arrow-right-s-line ml-auto text-pink-300 group-hover:text-violet-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                    </a>
                </div>
            </div>

            <!-- Estadísticas del Mes (Premium) -->
            <div
                class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex-1 flex flex-col hover:shadow-md transition-shadow">
                <h3
                    class="text-xs font-black uppercase tracking-widest text-slate-400 mb-5 flex items-center gap-2 shrink-0">
                    <i class="ri-calendar-line text-pink-400"></i> Este Mes
                </h3>
                <div class="space-y-3 overflow-y-auto custom-scrollbar pr-1 flex-1">
                    <button onclick="filtrarRRHHDash('Ingreso')" data-card="Ingreso"
                        class="rrhh-stat-card w-full flex items-center justify-between p-3.5 bg-emerald-50 rounded-2xl border border-emerald-100 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-100 transition-all duration-300 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-emerald-500/30">
                                <i class="ri-user-add-line text-white text-lg"></i>
                            </div>
                            <span
                                class="text-sm font-bold text-slate-700 group-hover:text-emerald-700 transition-colors">Ingresos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-black text-emerald-600"><?= $stats_rrhh['mes_ingresos'] ?></span>
                            <i
                                class="ri-arrow-right-s-line text-emerald-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                        </div>
                    </button>

                    <button onclick="filtrarRRHHDash('Salida')" data-card="Salida"
                        class="rrhh-stat-card w-full flex items-center justify-between p-3.5 bg-rose-50 rounded-2xl border border-rose-100 hover:border-rose-300 hover:shadow-md hover:shadow-rose-100 transition-all duration-300 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-rose-500/30">
                                <i class="ri-user-unfollow-line text-white text-lg"></i>
                            </div>
                            <span
                                class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition-colors">Salidas</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-black text-rose-600"><?= $stats_rrhh['mes_salidas'] ?></span>
                            <i
                                class="ri-arrow-right-s-line text-rose-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                        </div>
                    </button>

                    <button onclick="filtrarRRHHDash('Licencia')" data-card="Licencia"
                        class="rrhh-stat-card w-full flex items-center justify-between p-3.5 bg-indigo-50 rounded-2xl border border-indigo-100 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-100 transition-all duration-300 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-indigo-500/30">
                                <i class="ri-shield-keyhole-line text-white text-lg"></i>
                            </div>
                            <span
                                class="text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Licencias</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-black text-indigo-600"><?= $stats_rrhh['mes_licencias'] ?></span>
                            <i
                                class="ri-arrow-right-s-line text-indigo-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                        </div>
                    </button>
                </div>

                <div class="mt-6 pt-5 border-t border-slate-100 shrink-0">
                    <h4
                        class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-2">
                        <i class="ri-bar-chart-2-line text-slate-400"></i> Totales Históricos
                    </h4>
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div
                            class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl p-3 border border-emerald-100 flex flex-col items-center justify-center group">
                            <span
                                class="block text-xl font-black text-emerald-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['ingresos'] ?></span>
                            <span
                                class="text-[9px] text-emerald-400 font-black uppercase tracking-widest mt-0.5">Altas</span>
                        </div>
                        <div
                            class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-3 border border-rose-100 flex flex-col items-center justify-center group">
                            <span
                                class="block text-xl font-black text-rose-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['salidas'] ?></span>
                            <span class="text-[9px] text-rose-400 font-black uppercase tracking-widest mt-0.5">Bajas</span>
                        </div>
                        <div
                            class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-3 border border-indigo-100 flex flex-col items-center justify-center group">
                            <span
                                class="block text-xl font-black text-indigo-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['licencias'] ?></span>
                            <span
                                class="text-[9px] text-indigo-400 font-black uppercase tracking-widest mt-0.5">Licenc.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Perfil + Formularios Recientes (3/4) -->
        <div
            class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

            <!-- Tarjeta de Perfil (Premium) -->
            <div
                class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-3xl p-6 shadow-sm relative overflow-hidden group shrink-0">
                <div
                    class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-pink-500/10 blur-2xl group-hover:bg-pink-500/20 transition-all pointer-events-none">
                </div>
                <div
                    class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-rose-500/10 blur-2xl group-hover:bg-rose-500/20 transition-all pointer-events-none">
                </div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-pink-500/30">
                            <?= strtoupper(substr($GLOBALS['nombre_usuario'], 0, 1)) ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-black text-slate-800 flex items-center gap-2 mb-0.5 tracking-tight">
                                Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                            </h2>
                            <p class="text-slate-500 text-sm font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
                                Panel de Recursos Humanos
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <!-- Tarjeta Ingresos -->
                        <button onclick="filtrarRRHHDash('Ingreso')" data-card="Ingreso"
                            class="rrhh-stat-card bg-gradient-to-br from-emerald-50 to-green-50 rounded-2xl p-3 border border-emerald-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                            <span
                                class="block text-3xl font-black text-emerald-600 bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-green-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['ingresos'] ?></span>
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-emerald-400 mt-1 block">Ingresos</span>
                        </button>
                        <!-- Tarjeta Salidas -->
                        <button onclick="filtrarRRHHDash('Salida')" data-card="Salida"
                            class="rrhh-stat-card bg-gradient-to-br from-rose-50 to-red-50 rounded-2xl p-3 border border-rose-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                            <span
                                class="block text-3xl font-black text-rose-600 bg-clip-text text-transparent bg-gradient-to-r from-rose-600 to-red-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['salidas'] ?></span>
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-rose-400 mt-1 block">Salidas</span>
                        </button>
                        <!-- Tarjeta Licencias -->
                        <button onclick="filtrarRRHHDash('Licencia')" data-card="Licencia"
                            class="rrhh-stat-card bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl p-3 border border-indigo-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                            <span
                                class="block text-3xl font-black text-indigo-600 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-600 group-hover:scale-110 transition-transform"><?= $stats_rrhh['licencias'] ?></span>
                            <span
                                class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mt-1 block">Licencias</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Formularios Recientes -->
            <div class="flex-1 overflow-hidden flex flex-col bg-slate-50/50 rounded-3xl border border-slate-200/60 p-1">
                <div class="flex items-center justify-between mb-4 shrink-0 px-4 pt-4">
                    <h2 class="text-lg font-black text-slate-800 flex items-center gap-2 tracking-tight">
                        <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                            <i class="ri-file-list-3-line text-lg"></i>
                        </div>
                        Formularios Recientes
                    </h2>
                    <!-- Filtros tipo tab -->
                    <div class="flex gap-1 p-1 rounded-xl bg-slate-200/50" id="rrhh-filter-tabs">
                        <button onclick="filtrarRRHHDash('todos')" data-filter="todos"
                            class="px-4 py-1.5 text-xs font-black rounded-lg bg-white text-slate-700 shadow-sm transition-all rrhh-tab-btn">Todos</button>
                        <button onclick="filtrarRRHHDash('Ingreso')" data-filter="Ingreso"
                            class="px-4 py-1.5 text-xs font-black rounded-lg text-slate-500 hover:text-slate-700 hover:bg-white/60 transition-all rrhh-tab-btn">Ingresos</button>
                        <button onclick="filtrarRRHHDash('Salida')" data-filter="Salida"
                            class="px-4 py-1.5 text-xs font-black rounded-lg text-slate-500 hover:text-slate-700 hover:bg-white/60 transition-all rrhh-tab-btn">Salidas</button>
                        <button onclick="filtrarRRHHDash('Licencia')" data-filter="Licencia"
                            class="px-4 py-1.5 text-xs font-black rounded-lg text-slate-500 hover:text-slate-700 hover:bg-white/60 transition-all rrhh-tab-btn">Licencias</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar px-3 pb-3">
                    <?php if (empty($formularios_rrhh)): ?>
                        <div
                            class="text-center py-16 opacity-60 bg-white/50 border-2 border-dashed border-slate-200 rounded-2xl m-2 flex flex-col items-center justify-center">
                            <div
                                class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mb-4">
                                <i class="ri-inbox-line text-3xl"></i>
                            </div>
                            <span class="text-sm font-bold text-slate-500">No hay formularios registrados</span>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3" id="rrhh-forms-list">
                            <?php foreach ($formularios_rrhh as $f):
                                $tipo = $f['tipo'];
                                $tipo_colors = [
                                    'Ingreso' => ['badge' => 'bg-emerald-50 text-emerald-600 border-emerald-100 ring-emerald-500/10', 'icon' => 'ri-user-add-line', 'border' => 'border-emerald-400 hover:border-emerald-500 bg-emerald-50/10', 'btn_color' => 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white', 'icon_color' => 'text-emerald-500 bg-emerald-50 text-emerald-600'],
                                    'Salida' => ['badge' => 'bg-rose-50 text-rose-600 border-rose-100 ring-rose-500/10', 'icon' => 'ri-user-unfollow-line', 'border' => 'border-rose-400 hover:border-rose-500 bg-rose-50/10', 'btn_color' => 'bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white', 'icon_color' => 'text-rose-500 bg-rose-50 text-rose-600'],
                                    'Licencia' => ['badge' => 'bg-indigo-50 text-indigo-600 border-indigo-100 ring-indigo-500/10', 'icon' => 'ri-shield-keyhole-line', 'border' => 'border-indigo-400 hover:border-indigo-500 bg-indigo-50/10', 'btn_color' => 'bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white', 'icon_color' => 'text-indigo-500 bg-indigo-50 text-indigo-600'],
                                ];
                                $tc = $tipo_colors[$tipo] ?? ['badge' => 'bg-slate-50 text-slate-600 border-slate-100 ring-slate-500/10', 'icon' => 'ri-file-line', 'border' => 'border-slate-400 hover:border-slate-500 bg-slate-50/10', 'btn_color' => 'bg-slate-50 text-slate-600 hover:bg-slate-600 hover:text-white', 'icon_color' => 'text-slate-500 bg-slate-50 text-slate-600'];
                                $fecha_mostrar = $f['fecha_solicitud'] ?? $f['fecha_efectiva'] ?? '';
                                ?>
                                <div class="rrhh-form-row bg-white rounded-2xl border border-slate-200 border-l-4 <?= $tc['border'] ?> p-4 flex items-center justify-between hover:shadow-md transition-all duration-300 group cursor-pointer hover:-translate-y-0.5"
                                    data-tipo="<?= $tipo ?>"
                                    onclick="window.location.href='index.php?view=editar_rrhh&id=<?= $f['id'] ?>'">

                                    <div class="flex items-center gap-4">
                                        <div
                                            class="hidden sm:flex w-10 h-10 rounded-xl <?= $tc['icon_color'] ?> items-center justify-center shrink-0">
                                            <i class="<?= $tc['icon'] ?> text-lg"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span
                                                    class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border ring-1 ring-inset <?= $tc['badge'] ?>">
                                                    <?= $tipo ?>
                                                </span>
                                            </div>
                                            <p
                                                class="font-bold text-slate-800 text-sm mb-0.5 group-hover:text-slate-900 transition-colors">
                                                <?= htmlspecialchars($f['nombre_colaborador']) ?>
                                            </p>
                                            <p class="text-xs text-slate-500 line-clamp-1">
                                                <?= htmlspecialchars($f['cargo_zona'] ?? 'Sin cargo asignado') ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-5 shrink-0">
                                        <div class="flex flex-col items-end hidden md:flex">
                                            <span
                                                class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Fecha</span>
                                            <span class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                                <i class="ri-calendar-line text-slate-400"></i>
                                                <?= $fecha_mostrar ? date('d M Y', strtotime($fecha_mostrar)) : '—' ?>
                                            </span>
                                        </div>

                                        <div
                                            class="h-8 w-px bg-slate-200 hidden md:block group-hover:bg-slate-300 transition-colors">
                                        </div>

                                        <div class="flex items-center gap-2" onclick="event.stopPropagation();">
                                            <?php
                                            $print_url = '';
                                            if ($tipo === 'Ingreso')
                                                $print_url = "imprimir_acta_ingreso.php?id={$f['id']}";
                                            elseif ($tipo === 'Licencia')
                                                $print_url = "imprimir_acta_licencia.php?id={$f['id']}";
                                            else
                                                $print_url = "imprimir_acta_salida.php?id={$f['id']}";
                                            ?>
                                            <a href="<?= $print_url ?>" target="_blank"
                                                class="w-8 h-8 rounded-xl <?= $tc['btn_color'] ?> flex items-center justify-center transition-colors shadow-sm"
                                                title="Imprimir Acta">
                                                <i class="ri-printer-line text-sm"></i>
                                            </a>
                                            <a href="index.php?view=editar_rrhh&id=<?= $f['id'] ?>"
                                                class="w-8 h-8 rounded-xl bg-slate-50 text-slate-500 border border-slate-100 hover:bg-slate-800 hover:text-white hover:border-slate-800 flex items-center justify-center transition-colors shadow-sm"
                                                title="Editar Registro">
                                                <i class="ri-pencil-line text-sm"></i>
                                            </a>
                                        </div>
                                        <span
                                            class="px-2 py-1 bg-slate-100 rounded-md text-[10px] font-black text-slate-500 tracking-wider w-12 text-center">
                                            #<?= str_pad($f['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rrhhFiltroActivo = 'todos';

            function filtrarRRHHDash(tipo) {
                const row        s = document.querySelectorAll('.rrhh-form-row');
                const tabs = document.querySelectorAll('.rrhh-tab-btn');
                const cards = document.querySelectorAll('.rrhh-stat-card');

                // Toggle: si ya está activo, volver a 'todos'
                if (rrhhFiltroActivo === tipo && tipo !== 'todos') {
                    tipo = 'todos';
                }
                rrhhFiltroActivo = tipo;

                // Sync tab buttons
                tabs.forEach(t => {
                    const isActive = t.dataset.filter === tipo;
                    t.classList.toggle('bg-white', isActive);
                    t.classList.toggle('text-slate-700', isActive);
                    t.classList.toggle('shadow-sm', isActive);
                    t.classList.toggle('text-slate-500', !isActive);
                });

                // Highlight active stat card con estilos inline (más confiable que Tailwind dinámico)
                const ringColors = {
                    'Ingreso': '0 0 0 3px #34d399',   // emerald-400
                    'Salida': '0 0 0 3px #fb7185',    // rose-400
                    'Licencia': '0 0 0 3px #818cf8',    // indigo-400
                };
                cards.forEach(c => {
                    const cardTipo = c.dataset.card;
                    if (cardTipo === tipo && tipo !== 'todos') {
                        c.style.boxShadow = ringColors[cardTipo] || '';
                        c.style.transform = 'scale(1.05)';
                    } else {
                        c.style.boxShadow = '';
                        c.style.transform = '';
                    }
                });

                // Filtrar filas con animación suave
                rows.forEach(r => {
                    const show = (tipo === 'todos' || r.dataset.tipo === tipo);
                    if (show) {
                        r.style.display = '';
                        r.style.opacity = '0';
                        r.style.transform = 'translateY(6px)';
                        requestAnimationFrame(() => {
                            r.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            r.style.opacity = '1';
                            r.style.transform = 'translateY(0)';
                        });
                    } else {
                        r.style.transition = '';
                        r.style.display = 'none';
                    }
                });
            }
        </script>


        <?php
    // -------------------------------------------------------------------------
    // 4. Dashboard para Usuarios
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'Usuario') {

    $mis_tickets_creados = array_filter($tickets, function ($t) use ($usuario_id) {
        return $t['creador_id'] == $usuario_id;
    });
    $total_creados = count($mis_tickets_creados);
    $tickets_abiertos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Pendiente'));
    $tickets_resueltos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Completo'));
    ?>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

            <!-- COLUMNA IZQUIERDA: Acciones + Stats (1/4) -->
            <div class="w-full lg:w-1/4 flex flex-col gap-6">

                <!-- Acciones Rápidas (Premium) -->
                <div
                    class="bg-gradient-to-br from-blue-700 to-indigo-900 rounded-3xl p-6 shadow-xl text-white relative overflow-hidden shrink-0">
                    <div
                        class="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-full blur-3xl -mr-12 -mb-12 pointer-events-none">
                    </div>
                    <div
                        class="absolute top-0 left-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl -ml-10 -mt-10 pointer-events-none">
                    </div>

                    <div class="flex items-center justify-between mb-5 relative z-10">
                        <h3 class="text-xs font-black uppercase tracking-widest text-blue-200 flex items-center gap-2">
                            <i class="ri-flashlight-line text-indigo-300"></i> Acciones Rápidas
                        </h3>
                    </div>

                    <div class="flex flex-col gap-3 relative z-10">
                        <a href="index.php?view=crear_ticket"
                            class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/0 to-blue-500/0 group-hover:from-blue-500/10 group-hover:to-transparent transition-all duration-300">
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform relative z-10">
                                <i class="ri-add-circle-line text-lg text-white"></i>
                            </div>
                            <span
                                class="font-bold text-sm text-blue-50 group-hover:text-white transition-colors relative z-10">Nuevo
                                Ticket</span>
                            <i
                                class="ri-arrow-right-s-line ml-auto text-blue-300 group-hover:text-blue-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                        </a>

                        <a href="index.php?view=mis_tickets"
                            class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-indigo-500/0 via-indigo-500/0 to-indigo-500/0 group-hover:from-indigo-500/10 group-hover:to-transparent transition-all duration-300">
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/30 group-hover:scale-110 group-hover:-rotate-3 transition-transform relative z-10">
                                <i class="ri-ticket-2-line text-lg text-white"></i>
                            </div>
                            <span
                                class="font-bold text-sm text-blue-50 group-hover:text-white transition-colors relative z-10">Mis
                                Tickets</span>
                            <i
                                class="ri-arrow-right-s-line ml-auto text-blue-300 group-hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                        </a>

                        <a href="index.php?view=listados"
                            class="flex items-center gap-4 p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-white/10 transition-all group overflow-hidden relative">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-violet-500/0 via-violet-500/0 to-violet-500/0 group-hover:from-violet-500/10 group-hover:to-transparent transition-all duration-300">
                            </div>
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-400 to-purple-500 flex items-center justify-center shadow-lg shadow-violet-500/30 group-hover:scale-110 transition-transform relative z-10">
                                <i class="ri-list-check-2 text-lg text-white"></i>
                            </div>
                            <span
                                class="font-bold text-sm text-blue-50 group-hover:text-white transition-colors relative z-10">Ver
                                Listados</span>
                            <i
                                class="ri-arrow-right-s-line ml-auto text-blue-300 group-hover:text-violet-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 relative z-10"></i>
                        </a>
                    </div>
                </div>

                <!-- Estadísticas (Premium) -->
                <div
                    class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex-1 flex flex-col hover:shadow-md transition-shadow">
                    <h3
                        class="text-xs font-black uppercase tracking-widest text-slate-400 mb-5 flex items-center gap-2 shrink-0">
                        <i class="ri-bar-chart-2-line text-blue-400"></i> Mis Tickets
                    </h3>
                    <div class="space-y-3 overflow-y-auto custom-scrollbar pr-1 flex-1">
                        <button onclick="filtrarTablaUsuarioPorEstado('todos')" data-card-u="todos"
                            class="usuario-stat-card w-full flex items-center justify-between p-3.5 bg-indigo-50 rounded-2xl border border-indigo-100 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-100 transition-all duration-300 cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-indigo-500/30">
                                    <i class="ri-ticket-2-line text-white text-lg"></i>
                                </div>
                                <span
                                    class="text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Total</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-black text-indigo-600"><?= $total_creados ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-indigo-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                            </div>
                        </button>

                        <button onclick="filtrarTablaUsuarioPorEstado('Pendiente')" data-card-u="Pendiente"
                            class="usuario-stat-card w-full flex items-center justify-between p-3.5 bg-amber-50 rounded-2xl border border-amber-100 hover:border-amber-300 hover:shadow-md hover:shadow-amber-100 transition-all duration-300 cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-amber-500/30">
                                    <i class="ri-time-line text-white text-lg"></i>
                                </div>
                                <span
                                    class="text-sm font-bold text-slate-700 group-hover:text-amber-700 transition-colors">Pendientes</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-black text-amber-600"><?= $tickets_abiertos ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-amber-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                            </div>
                        </button>

                        <button onclick="filtrarTablaUsuarioPorEstado('Completo')" data-card-u="Completo"
                            class="usuario-stat-card w-full flex items-center justify-between p-3.5 bg-emerald-50 rounded-2xl border border-emerald-100 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-100 transition-all duration-300 cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm shadow-emerald-500/30">
                                    <i class="ri-checkbox-circle-line text-white text-lg"></i>
                                </div>
                                <span
                                    class="text-sm font-bold text-slate-700 group-hover:text-emerald-700 transition-colors">Resueltos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-black text-emerald-600"><?= $tickets_resueltos ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-emerald-400 opacity-0 group-hover:opacity-100 transition-all group-hover:translate-x-1 text-lg"></i>
                            </div>
                        </button>
                    </div>

                    <!-- Top Categorías -->
                    <div class="mt-6 pt-5 border-t border-slate-100 shrink-0">
                        <h4
                            class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3 flex items-center gap-2">
                            <i class="ri-pie-chart-2-line text-slate-400"></i> Top Categorías
                        </h4>
                        <?php
                        $categorias_stats = [];
                        foreach ($mis_tickets_creados as $t) {
                            $cat = $t['categoria'] ?? 'Otros';
                            $categorias_stats[$cat] = ($categorias_stats[$cat] ?? 0) + 1;
                        }
                        arsort($categorias_stats);
                        $top_categorias = array_slice($categorias_stats, 0, 3);
                        $bar_colors_u = ['bg-gradient-to-r from-blue-400 to-indigo-500', 'bg-gradient-to-r from-indigo-400 to-violet-500', 'bg-gradient-to-r from-violet-400 to-purple-500'];
                        $ci = 0;
                        if (empty($top_categorias)) {
                            echo "<p class='text-xs font-medium text-slate-400 opacity-70 border border-dashed border-slate-200 p-3 rounded-xl text-center'>Sin datos aún.</p>";
                        } else {
                            foreach ($top_categorias as $cat => $count) {
                                $pct = ($total_creados > 0) ? round(($count / $total_creados) * 100) : 0;
                                $bc = $bar_colors_u[$ci % 3];
                                $ci++;
                                echo "<div class='mb-3 last:mb-0 group'>";
                                echo "<div class='flex justify-between items-end mb-1.5'><span class='text-[11px] font-bold text-slate-600 truncate max-w-[120px] group-hover:text-slate-800 transition-colors'>{$cat}</span><span class='text-[10px] font-black text-slate-400 group-hover:text-indigo-500 transition-colors'>{$pct}%</span></div>";
                                echo "<div class='w-full bg-slate-100/80 rounded-full h-1.5 overflow-hidden'><div class='h-1.5 rounded-full {$bc} rounded-l-none transform translate-x-[-100%] animate-[slideRight_1s_ease-out_forwards]' style='--final-width:{$pct}%'></div></div>";
                                echo "</div>";
                            }
                        }
                        ?>
                        <style>
                            @keyframes slideRight {
                                to {
                                    transform: translateX(0);
                                    width: var(--final-width);
                                }
                            }
                        </style>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Perfil + Tabla (3/4) -->
            <div
                class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

                <!-- Tarjeta de Perfil -->
                <div
                    class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-3xl p-6 shadow-sm relative overflow-hidden group shrink-0">
                    <div
                        class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-blue-500/10 blur-2xl group-hover:bg-blue-500/20 transition-all pointer-events-none">
                    </div>
                    <div
                        class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-indigo-500/10 blur-2xl group-hover:bg-indigo-500/20 transition-all pointer-events-none">
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-blue-500/30">
                                <?= strtoupper(substr($GLOBALS['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-slate-800 flex items-center gap-2 mb-0.5 tracking-tight">
                                    Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                                </h2>
                                <p class="text-slate-500 text-sm font-medium flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                    Panel de Usuario
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button onclick="filtrarTablaUsuarioPorEstado('todos')" data-card-u="todos"
                                class="usuario-stat-card bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl p-3 border border-indigo-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                                <span
                                    class="block text-3xl font-black text-indigo-600 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-600 group-hover:scale-110 transition-transform"><?= $total_creados ?></span>
                                <span
                                    class="text-[10px] uppercase font-black tracking-widest text-indigo-400 mt-1 block">Total</span>
                            </button>
                            <button onclick="filtrarTablaUsuarioPorEstado('Pendiente')" data-card-u="Pendiente"
                                class="usuario-stat-card bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-3 border border-amber-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                                <span
                                    class="block text-3xl font-black text-amber-600 bg-clip-text text-transparent bg-gradient-to-r from-amber-600 to-orange-600 group-hover:scale-110 transition-transform"><?= $tickets_abiertos ?></span>
                                <span
                                    class="text-[10px] uppercase font-black tracking-widest text-amber-400 mt-1 block">Pendientes</span>
                            </button>
                            <button onclick="filtrarTablaUsuarioPorEstado('Completo')" data-card-u="Completo"
                                class="usuario-stat-card bg-gradient-to-br from-emerald-50 to-green-50 rounded-2xl p-3 border border-emerald-100/50 text-center min-w-[100px] cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300 group">
                                <span
                                    class="block text-3xl font-black text-emerald-600 bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-green-600 group-hover:scale-110 transition-transform"><?= $tickets_resueltos ?></span>
                                <span
                                    class="text-[10px] uppercase font-black tracking-widest text-emerald-400 mt-1 block">Resueltos</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Tickets Recientes -->
                <div class="flex-1 overflow-hidden flex flex-col bg-slate-50/50 rounded-3xl border border-slate-200/60 p-1">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 shrink-0 px-4 pt-4">
                        <h2 class="text-lg font-black text-slate-800 flex items-center gap-2 tracking-tight">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <i class="ri-history-line text-lg"></i>
                            </div>
                            Actividad Reciente
                        </h2>
                        <div class="flex items-center gap-3">
                            <!-- Buscador compacto -->
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i
                                        class="ri-search-line text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                                </div>
                                <input type="text" id="buscadorTicketsUsuario" placeholder="Buscar tickets..."
                                    class="pl-9 pr-4 py-2 text-sm border-0 ring-1 ring-inset ring-slate-200 rounded-xl focus:ring-2 focus:ring-inset focus:ring-blue-500 bg-white/70 backdrop-blur-sm text-slate-800 w-48 transition-all shadow-sm shadow-slate-200/50 placeholder:text-slate-400"
                                    onkeyup="filtrarTablaUsuario()">
                            </div>
                            <a href="index.php?view=mis_tickets"
                                class="text-xs font-black uppercase tracking-wider text-blue-600 hover:text-white bg-blue-50 hover:bg-blue-600 px-4 py-2.5 rounded-xl border border-blue-100 hover:border-blue-600 transition-all duration-300 shadow-sm">
                                Ver Todo
                            </a>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar px-3 pb-3">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-100" id="tablaTicketsUsuario">
                                <thead>
                                    <tr class="bg-slate-50/80">
                                        <th
                                            class="px-5 py-3.5 text-left text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                            ID</th>
                                        <th
                                            class="px-5 py-3.5 text-left text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                            Asunto</th>
                                        <th
                                            class="px-5 py-3.5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                            Estado</th>
                                        <th
                                            class="px-5 py-3.5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                            Prioridad</th>
                                        <th
                                            class="px-5 py-3.5 text-left text-[10px] font-black tracking-widest text-slate-400 uppercase hidden md:table-cell">
                                            Categoría</th>
                                        <th
                                            class="px-5 py-3.5 text-center text-[10px] font-black tracking-widest text-slate-400 uppercase w-20">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <?php
                                    if (empty($mis_tickets_creados)) {
                                        echo "<tr><td colspan='6' class='px-6 py-16 text-center text-slate-400 bg-slate-50/30'>
                                            <div class='flex flex-col items-center justify-center'>
                                                <div class='w-16 h-16 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center mb-4'><i class='ri-ticket-line text-3xl'></i></div>
                                                <p class='font-bold text-slate-600 mb-1'>No has creado ningún ticket aún</p>
                                                <p class='text-sm text-slate-400 mb-4'>Los tickets que crees aparecerán aquí</p>
                                                <a href='index.php?view=crear_ticket' class='px-5 py-2 rounded-xl bg-blue-600 text-white font-bold text-sm shadow-md shadow-blue-500/20 hover:bg-blue-700 transition-colors'>Crear mi primer ticket</a>
                                            </div></td></tr>";
                                    } else {
                                        $ultimos_tickets = array_slice($mis_tickets_creados, 0, 20);
                                        foreach ($ultimos_tickets as $t) {
                                            $status_colors = [
                                                'Pendiente' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 border-yellow-200',
                                                'Asignado' => 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200',
                                                'En Atención' => 'bg-purple-50 text-purple-700 ring-purple-600/20 border-purple-200',
                                                'Completo' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
                                                'Resuelto' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
                                                'Cerrado' => 'bg-slate-50 text-slate-700 ring-slate-600/20 border-slate-200'
                                            ];
                                            $s_class = $status_colors[$t['estado']] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20 border-slate-200';

                                            $prio_colors = [
                                                'Baja' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'Media' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'Alta' => 'bg-orange-50 text-orange-700 border-orange-200',
                                                'Critica' => 'bg-rose-50 text-rose-700 border-rose-200'
                                            ];
                                            $p_class = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-700 border-slate-200';

                                            echo "<tr class='hover:bg-slate-50/80 transition-all duration-300 group cursor-pointer' data-estado='" . htmlspecialchars($t['estado']) . "' onclick=\"window.location.href='index.php?view=editar_ticket&id={$t['id']}'\">";
                                            echo "<td class='px-5 py-3'><span class='px-2 py-1 bg-slate-100 rounded-md text-[10px] font-black text-slate-500 tracking-wider whitespace-nowrap'>#" . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . "</span></td>";
                                            echo "<td class='px-5 py-3 w-max'><div class='text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors line-clamp-1 mb-0.5'>" . htmlspecialchars($t['titulo']) . "</div><div class='flex items-center gap-1.5 text-[11px] text-slate-400 font-medium'><i class='ri-calendar-line'></i> " . date('d M Y', strtotime($t['fecha_creacion'])) . "</div></td>";
                                            echo "<td class='px-5 py-3 text-center'><span class='inline-flex items-center justify-center rounded-lg px-2.5 py-1 text-[11px] font-bold border {$s_class} gap-1.5 whitespace-nowrap'><span class='w-1.5 h-1.5 rounded-full bg-current opacity-70'></span>" . htmlspecialchars($t['estado']) . "</span></td>";
                                            echo "<td class='px-5 py-3 text-center'><span class='inline-flex items-center justify-center rounded-lg px-2.5 py-1 text-[10px] font-black uppercase tracking-wider border {$p_class} gap-1 whitespace-nowrap'><i class='ri-flag-fill opacity-70'></i>" . htmlspecialchars($t['prioridad']) . "</span></td>";
                                            echo "<td class='px-5 py-3 text-sm font-medium text-slate-600 hidden md:table-cell'><div class='flex items-center gap-2'><div class='w-2 h-2 rounded-sm bg-slate-300'></div>" . htmlspecialchars($t['categoria'] ?? 'General') . "</div></td>";
                                            echo "<td class='px-5 py-3 text-center'><a href='index.php?view=editar_ticket&id={$t['id']}' class='inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 hover:shadow-sm transition-all' title='Ver Detalles' onclick='event.stopPropagation();'><i class='ri-eye-line text-[15px]'></i></a></td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

            <!-- COLUMNA IZQUIERDA: Navegación Estratégica + KPIs Alerta (1/4) -->
            <div class="w-full lg:w-1/4 flex flex-col gap-5 overflow-y-auto custom-scrollbar pb-4">

                <!-- Menú Gerencial -->
                <div
                    class="bg-gradient-to-br from-slate-800 to-black rounded-2xl p-5 shadow-xl text-white relative overflow-hidden shrink-0">
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-2xl -mr-10 -mb-10"></div>
                    <div class="flex items-center gap-2 mb-4 relative z-10">
                        <i class="ri-briefcase-4-line text-indigo-300"></i>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-200">Gestión Estratégica</h3>
                    </div>
                    <div class="flex flex-col gap-2 relative z-10">
                        <a href="index.php?view=reportes_nuevo"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-7 h-7 rounded-lg bg-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="ri-pie-chart-2-line text-sm"></i>
                            </div>
                            <span class="font-medium text-sm">Reportes Globales</span>
                        </a>
                        <a href="index.php?view=historial_rrhh"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-7 h-7 rounded-lg bg-pink-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="ri-group-line text-sm"></i>
                            </div>
                            <span class="font-medium text-sm">Talento Humano</span>
                        </a>
                        <a href="index.php?view=inventario"
                            class="flex items-center gap-3 p-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-7 h-7 rounded-lg bg-cyan-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="ri-computer-line text-sm"></i>
                            </div>
                            <span class="font-medium text-sm">Inventario Tecnológico</span>
                        </a>
                    </div>
                </div>

                <!-- KPIs Críticos / Alertas -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm shrink-0">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                        <i class="ri-dashboard-3-line text-rose-500"></i> Indicadores Clave
                    </h3>
                    <div class="space-y-3">
                        <!-- Resolución -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold text-slate-600">Efectividad Global</span>
                                <span class="text-xs font-bold text-indigo-600"><?= $porcentaje_resueltos ?>%</span>
                            </div>
                            <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: <?= $porcentaje_resueltos ?>%">
                                </div>
                            </div>
                        </div>

                        <!-- Críticos -->
                        <a href="index.php?view=seguimiento&prioridad=Critica"
                            class="flex items-center justify-between p-3 rounded-xl border transition-all group <?= $t_criticos > 0 ? 'bg-rose-50 border-rose-100 hover:border-rose-300' : 'bg-emerald-50 border-emerald-100' ?>">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center <?= $t_criticos > 0 ? 'bg-rose-500 text-white' : 'bg-emerald-500 text-white' ?>">
                                    <i class="<?= $t_criticos > 0 ? 'ri-alarm-warning-line' : 'ri-check-double-line' ?>"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold <?= $t_criticos > 0 ? 'text-rose-700' : 'text-emerald-700' ?>">
                                        Casos Críticos</p>
                                    <p class="text-[10px] <?= $t_criticos > 0 ? 'text-rose-500' : 'text-emerald-500' ?>">
                                        <?= $t_criticos > 0 ? 'Requiere Atención' : 'Todo bajo control' ?>
                                    </p>
                                </div>
                            </div>
                            <span
                                class="text-xl font-black <?= $t_criticos > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= $t_criticos ?></span>
                        </a>

                        <!-- RRHH Resumen -->
                        <div class="grid grid-cols-2 gap-2">
                            <div class="p-2 bg-emerald-50 rounded-lg border border-emerald-100 text-center">
                                <span class="block text-xl font-bold text-emerald-600">+<?= $ingresos_mes ?></span>
                                <span class="text-[10px] font-bold text-emerald-400 uppercase">Ingresos</span>
                            </div>
                            <div class="p-2 bg-rose-50 rounded-lg border border-rose-100 text-center">
                                <span class="block text-xl font-bold text-rose-600">-<?= $salidas_mes ?></span>
                                <span class="text-[10px] font-bold text-rose-400 uppercase">Bajas</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventario Mini -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm shrink-0 flex-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                        <i class="ri-hard-drive-2-line text-cyan-500"></i> Activos IT
                    </h3>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl font-black text-slate-800"><?= $total_activos ?></span>
                        <span class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded-lg">Total Equipos</span>
                    </div>
                    <div class="space-y-2 overflow-y-auto max-h-[150px] custom-scrollbar pr-1">
                        <?php foreach ($activos_por_tipo as $tipo => $cant): ?>
                                <div
                                    class="flex justify-between items-center text-xs p-1.5 hover:bg-slate-50 rounded-lg transition-colors">
                                    <span class="text-slate-600 font-medium truncate"><?= htmlspecialchars($tipo) ?></span>
                                    <span class="font-bold text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded"><?= $cant ?></span>
                                </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- COLUMNA DERECHA: Análisis Visión Global (3/4) -->
            <div
                class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

                <!-- Tarjeta Perfil Gerencia -->
                <div
                    class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-2xl p-5 shadow-sm relative overflow-hidden group shrink-0">
                    <div
                        class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-slate-800/5 blur-2xl group-hover:bg-slate-800/10 transition-all">
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-700 to-black flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-slate-500/30">
                                <?= strtoupper(substr($GLOBALS['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800 mb-0.5">
                                    Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                                </h2>
                                <p class="text-slate-500 text-sm flex items-center gap-1">
                                    <i class="ri-bar-chart-groupped-line text-indigo-500"></i> Visión Estratégica
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4 items-center">
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</p>
                                <p class="text-lg font-bold text-slate-700"><?php echo date('d M, Y'); ?></p>
                            </div>
                            <div class="h-8 w-px bg-slate-200"></div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Personal Activo</p>
                                <p class="text-lg font-bold text-emerald-600"><?= $personal_activo ?> <span
                                        class="text-xs text-emerald-400 font-normal">colaboradores</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distribución Visual Tickets -->
                <div
                    class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden shrink-0">
                    <div class="absolute inset-0 opacity-10 pointer-events-none">
                        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <path d="M0 100 Q 50 50 100 80 V 100 H 0 Z" fill="white" />
                        </svg>
                    </div>
                    <div class="flex justify-between items-center mb-6 relative z-10">
                        <h3 class="text-lg font-bold flex items-center gap-2">
                            <i class="ri-pie-chart-2-line text-indigo-400"></i> Distribución de Tickets
                        </h3>
                        <div class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/10"><?= $total_tickets ?>
                            Totales</div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 relative z-10">
                        <!-- Nuevos -->
                        <div class="bg-white/5 rounded-xl p-4 border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-blue-300 text-xs font-bold uppercase">Nuevos</span>
                                <i class="ri-inbox-line text-blue-400"></i>
                            </div>
                            <div class="text-2xl font-bold mb-2"><?= $t_abiertos ?></div>
                            <div class="w-full bg-white/10 h-1 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full"
                                    style="width: <?= ($total_tickets > 0 ? ($t_abiertos / $total_tickets) * 100 : 0) ?>%">
                                </div>
                            </div>
                        </div>
                        <!-- Proceso -->
                        <div class="bg-white/5 rounded-xl p-4 border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-purple-300 text-xs font-bold uppercase">En Proceso</span>
                                <i class="ri-loader-line text-purple-400"></i>
                            </div>
                            <div class="text-2xl font-bold mb-2"><?= $t_en_proceso ?></div>
                            <div class="w-full bg-white/10 h-1 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full"
                                    style="width: <?= ($total_tickets > 0 ? ($t_en_proceso / $total_tickets) * 100 : 0) ?>%">
                                </div>
                            </div>
                        </div>
                        <!-- Resueltos -->
                        <div class="bg-white/5 rounded-xl p-4 border border-white/5 hover:bg-white/10 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-emerald-300 text-xs font-bold uppercase">Resueltos</span>
                                <i class="ri-checkbox-circle-line text-emerald-400"></i>
                            </div>
                            <div class="text-2xl font-bold mb-2"><?= $t_resueltos ?></div>
                            <div class="w-full bg-white/10 h-1 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full"
                                    style="width: <?= ($total_tickets > 0 ? ($t_resueltos / $total_tickets) * 100 : 0) ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carga de Técnicos y Detalles -->
                <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                    <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <i class="ri-team-line text-violet-600"></i> Carga Operativa (Top 5)
                        </h3>
                    </div>
                    <div class="overflow-auto custom-scrollbar">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-400 font-semibold sticky top-0">
                                <tr>
                                    <th class="px-5 py-3 bg-slate-50">Técnico</th>
                                    <th class="px-5 py-3 text-right bg-slate-50">Tickets Activos</th>
                                    <th class="px-5 py-3 text-right bg-slate-50">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($carga_tecnicos as $nombre => $cantidad):
                                    $load_color = $cantidad > 5 ? 'bg-rose-50 text-rose-700' : ($cantidad > 2 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
                                    $load_text = $cantidad > 5 ? 'Alta' : ($cantidad > 2 ? 'Media' : 'Baja');
                                    ?>
                                        <tr onclick="window.location.href='index.php?view=historial_tecnico&tecnico=<?= urlencode($nombre) ?>'"
                                            class="hover:bg-slate-50 transition-colors cursor-pointer group">
                                            <td class="px-5 py-3 font-medium text-slate-700">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold group-hover:bg-violet-100 group-hover:text-violet-600 transition-colors">
                                                        <?= strtoupper(substr($nombre, 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($nombre) ?>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <span class="font-bold text-slate-700"><?= $cantidad ?></span>
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $load_color ?>"><?= $load_text ?></span>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                                <?php if (empty($carga_tecnicos)): ?>
                                        <tr>
                                            <td colspan="3" class="p-8 text-center text-slate-400 italic">
                                                <i class="ri-cup-line text-2xl mb-2 block"></i>
                                                Sin carga activa registrada
                                            </td>
                                        </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php
    // -------------------------------------------------------------------------
    // 3. Dashboard para RRHH
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'RRHH') {
    // Fetch RRHH forms
    $formularios_rrhh = [];
    $stats_rrhh = ['ingresos' => 0, 'salidas' => 0, 'licencias' => 0, 'mes_ingresos' => 0, 'mes_salidas' => 0, 'mes_licencias' => 0];
    try {
        $stmt_rrhh = $pdo->query("SELECT * FROM formularios_rrhh ORDER BY id DESC LIMIT 50");
        $formularios_rrhh = $stmt_rrhh->fetchAll(PDO::FETCH_ASSOC);

        // Stats totales
        $stats_rrhh['ingresos'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Ingreso'));
        $stats_rrhh['salidas'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Salida'));
        $stats_rrhh['licencias'] = count(array_filter($formularios_rrhh, fn($f) => $f['tipo'] === 'Licencia'));

        // Stats del mes actual
        $mes_actual = date('Y-m');
        foreach ($formularios_rrhh as $f) {
            $fecha = $f['fecha_solicitud'] ?? $f['fecha_efectiva'] ?? '';
            if (str_starts_with($fecha, $mes_actual)) {
                if ($f['tipo'] === 'Ingreso')
                    $stats_rrhh['mes_ingresos']++;
                elseif ($f['tipo'] === 'Salida')
                    $stats_rrhh['mes_salidas']++;
                elseif ($f['tipo'] === 'Licencia')
                    $stats_rrhh['mes_licencias']++;
            }
        }
    } catch (PDOException $e) { /* ignore */
    }
    ?>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

            <!-- COLUMNA IZQUIERDA: Acciones + Stats (1/4) -->
            <div class="w-full lg:w-1/4 flex flex-col gap-6">

                <!-- Acciones Rápidas -->
                <div
                    class="bg-gradient-to-br from-pink-700 to-rose-900 rounded-2xl p-6 shadow-xl text-white relative overflow-hidden">
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-2xl -mr-10 -mb-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-pink-200 flex items-center gap-2">
                            <i class="ri-flashlight-line"></i> Acciones Rápidas
                        </h3>
                    </div>
                    <div class="flex flex-col gap-3 relative z-10">
                        <a href="index.php?view=nuevo_ingreso"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/40 group-hover:scale-110 transition-transform">
                                <i class="ri-user-add-line"></i>
                            </div>
                            <span class="font-medium text-sm">Nueva Alta</span>
                        </a>
                        <a href="index.php?view=nueva_salida"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center shadow-lg shadow-rose-500/40 group-hover:scale-110 transition-transform">
                                <i class="ri-user-unfollow-line"></i>
                            </div>
                            <span class="font-medium text-sm">Nueva Baja</span>
                        </a>
                        <a href="index.php?view=solicitud_licencia"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/40 group-hover:scale-110 transition-transform">
                                <i class="ri-shield-keyhole-line"></i>
                            </div>
                            <span class="font-medium text-sm">Solicitar Licencia</span>
                        </a>
                        <a href="index.php?view=historial_rrhh"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/40 group-hover:scale-110 transition-transform">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                            <span class="font-medium text-sm">Ver Historial</span>
                        </a>
                        <a href="index.php?view=personal"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-violet-500 flex items-center justify-center shadow-lg shadow-violet-500/40 group-hover:scale-110 transition-transform">
                                <i class="ri-contacts-book-line"></i>
                            </div>
                            <span class="font-medium text-sm">Gestión Personal</span>
                        </a>
                    </div>
                </div>

                <!-- Estadísticas del Mes -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex-1 overflow-y-auto custom-scrollbar">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                        <i class="ri-calendar-line text-pink-500"></i> Este Mes
                    </h3>
                    <div class="space-y-3">
                        <button onclick="filtrarRRHHDash('Ingreso')" data-card="Ingreso"
                            class="rrhh-stat-card w-full flex items-center justify-between p-3 bg-emerald-50 rounded-xl border border-emerald-100 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-user-add-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Ingresos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-emerald-600"><?= $stats_rrhh['mes_ingresos'] ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                        <button onclick="filtrarRRHHDash('Salida')" data-card="Salida"
                            class="rrhh-stat-card w-full flex items-center justify-between p-3 bg-rose-50 rounded-xl border border-rose-100 hover:border-rose-300 hover:shadow-md hover:shadow-rose-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-user-unfollow-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Salidas</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-rose-600"><?= $stats_rrhh['mes_salidas'] ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-rose-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                        <button onclick="filtrarRRHHDash('Licencia')" data-card="Licencia"
                            class="rrhh-stat-card w-full flex items-center justify-between p-3 bg-indigo-50 rounded-xl border border-indigo-100 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-shield-keyhole-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Licencias</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-indigo-600"><?= $stats_rrhh['mes_licencias'] ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                            <i class="ri-bar-chart-2-line text-slate-400"></i> Totales Históricos
                        </h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-slate-50 rounded-xl p-2 border border-slate-100">
                                <span class="block text-lg font-bold text-emerald-600"><?= $stats_rrhh['ingresos'] ?></span>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase">Altas</span>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-2 border border-slate-100">
                                <span class="block text-lg font-bold text-rose-600"><?= $stats_rrhh['salidas'] ?></span>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase">Bajas</span>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-2 border border-slate-100">
                                <span class="block text-lg font-bold text-indigo-600"><?= $stats_rrhh['licencias'] ?></span>
                                <span class="text-[10px] text-slate-400 font-semibold uppercase">Licenc.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Perfil + Formularios Recientes (3/4) -->
            <div
                class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

                <!-- Tarjeta de Perfil -->
                <div
                    class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-2xl p-5 shadow-sm relative overflow-hidden group shrink-0">
                    <div
                        class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-pink-500/10 blur-2xl group-hover:bg-pink-500/20 transition-all">
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-pink-500/30">
                                <?= strtoupper(substr($GLOBALS['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800 mb-0.5">
                                    Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                                </h2>
                                <p class="text-slate-500 text-sm flex items-center gap-1">
                                    <i class="ri-user-star-line text-pink-500"></i> Panel de Recursos Humanos
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <!-- Tarjeta Ingresos -->
                            <button onclick="filtrarRRHHDash('Ingreso')" data-card="Ingreso"
                                class="rrhh-stat-card bg-emerald-50 rounded-xl p-3 border border-emerald-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-emerald-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-emerald-600 group-hover:text-emerald-700"><?= $stats_rrhh['ingresos'] ?></span>
                                <span class="text-[10px] uppercase font-bold text-emerald-400">Ingresos</span>
                                <div
                                    class="mt-1 text-[9px] text-emerald-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                            <!-- Tarjeta Salidas -->
                            <button onclick="filtrarRRHHDash('Salida')" data-card="Salida"
                                class="rrhh-stat-card bg-rose-50 rounded-xl p-3 border border-rose-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-rose-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-rose-600 group-hover:text-rose-700"><?= $stats_rrhh['salidas'] ?></span>
                                <span class="text-[10px] uppercase font-bold text-rose-400">Salidas</span>
                                <div class="mt-1 text-[9px] text-rose-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                            <!-- Tarjeta Licencias -->
                            <button onclick="filtrarRRHHDash('Licencia')" data-card="Licencia"
                                class="rrhh-stat-card bg-indigo-50 rounded-xl p-3 border border-indigo-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-indigo-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-indigo-600 group-hover:text-indigo-700"><?= $stats_rrhh['licencias'] ?></span>
                                <span class="text-[10px] uppercase font-bold text-indigo-400">Licencias</span>
                                <div
                                    class="mt-1 text-[9px] text-indigo-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formularios Recientes -->
                <div class="flex-1 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between mb-4 shrink-0">
                        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
                            <i class="ri-file-list-3-line text-pink-500"></i> Formularios Recientes
                        </h2>
                        <!-- Filtros tipo tab -->
                        <div class="flex gap-1 bg-slate-100 p-1 rounded-xl" id="rrhh-filter-tabs">
                            <button onclick="filtrarRRHHDash('todos')" data-filter="todos"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg bg-white text-slate-700 shadow-sm transition-all rrhh-tab-btn">Todos</button>
                            <button onclick="filtrarRRHHDash('Ingreso')" data-filter="Ingreso"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg text-slate-500 hover:bg-white/60 transition-all rrhh-tab-btn">Ingresos</button>
                            <button onclick="filtrarRRHHDash('Salida')" data-filter="Salida"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg text-slate-500 hover:bg-white/60 transition-all rrhh-tab-btn">Salidas</button>
                            <button onclick="filtrarRRHHDash('Licencia')" data-filter="Licencia"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg text-slate-500 hover:bg-white/60 transition-all rrhh-tab-btn">Licencias</button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <?php if (empty($formularios_rrhh)): ?>
                                <div class="text-center py-16 opacity-50 border-2 border-dashed border-slate-300 rounded-xl">
                                    <i class="ri-inbox-line text-4xl text-slate-400"></i>
                                    <p class="text-slate-500 mt-2">No hay formularios registrados</p>
                                </div>
                        <?php else: ?>
                                <div class="space-y-2" id="rrhh-forms-list">
                                    <?php foreach ($formularios_rrhh as $f):
                                        $tipo = $f['tipo'];
                                        $tipo_colors = [
                                            'Ingreso' => ['badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'icon' => 'ri-user-add-line', 'border' => 'border-l-emerald-400'],
                                            'Salida' => ['badge' => 'bg-rose-100 text-rose-700 border-rose-200', 'icon' => 'ri-user-unfollow-line', 'border' => 'border-l-rose-400'],
                                            'Licencia' => ['badge' => 'bg-indigo-100 text-indigo-700 border-indigo-200', 'icon' => 'ri-shield-keyhole-line', 'border' => 'border-l-indigo-400'],
                                        ];
                                        $tc = $tipo_colors[$tipo] ?? ['badge' => 'bg-slate-100 text-slate-700 border-slate-200', 'icon' => 'ri-file-line', 'border' => 'border-l-slate-400'];
                                        $fecha_mostrar = $f['fecha_solicitud'] ?? $f['fecha_efectiva'] ?? '';
                                        ?>
                                            <div class="rrhh-form-row bg-white rounded-xl border border-slate-200 border-l-4 <?= $tc['border'] ?> p-4 flex items-center justify-between hover:shadow-md transition-all group"
                                                data-tipo="<?= $tipo ?>">
                                                <div class="flex items-center gap-3">
                                                    <span
                                                        class="px-2.5 py-1 rounded-full text-xs font-bold border flex items-center gap-1 <?= $tc['badge'] ?>">
                                                        <i class="<?= $tc['icon'] ?>"></i> <?= $tipo ?>
                                                    </span>
                                                    <div>
                                                        <p class="font-bold text-slate-800 text-sm">
                                                            <?= htmlspecialchars($f['nombre_colaborador']) ?>
                                                        </p>
                                                        <p class="text-xs text-slate-400"><?= htmlspecialchars($f['cargo_zona'] ?? '') ?></p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="text-xs text-slate-400 hidden md:block">
                                                        <i class="ri-calendar-line"></i>
                                                        <?= $fecha_mostrar ? date('d M Y', strtotime($fecha_mostrar)) : '—' ?>
                                                    </span>
                                                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <?php if ($tipo === 'Ingreso'): ?>
                                                                <a href="imprimir_acta_ingreso.php?id=<?= $f['id'] ?>" target="_blank"
                                                                    class="p-1.5 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 transition-colors"
                                                                    title="Imprimir Acta">
                                                                    <i class="ri-printer-line text-sm"></i>
                                                                </a>
                                                        <?php elseif ($tipo === 'Licencia'): ?>
                                                                <a href="imprimir_acta_licencia.php?id=<?= $f['id'] ?>" target="_blank"
                                                                    class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                                                                    title="Imprimir Acta">
                                                                    <i class="ri-printer-line text-sm"></i>
                                                                </a>
                                                        <?php else: ?>
                                                                <a href="imprimir_acta_salida.php?id=<?= $f['id'] ?>" target="_blank"
                                                                    class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors"
                                                                    title="Imprimir Acta">
                                                                    <i class="ri-printer-line text-sm"></i>
                                                                </a>
                                                        <?php endif; ?>
                                                        <a href="index.php?view=editar_rrhh&id=<?= $f['id'] ?>"
                                                            class="p-1.5 rounded-lg bg-slate-50 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                                                            title="Editar">
                                                            <i class="ri-pencil-line text-sm"></i>
                                                        </a>
                                                    </div>
                                                    <span
                                                        class="text-xs font-bold text-slate-400">#<?= str_pad($f['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                                </div>
                                            </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let rrhhFiltroActivo = 'todos';

            function filtrarRRHHDash(tipo) {
                const row        s = document.querySelectorAll('.rrhh-form-row');
                const tabs = document.querySelectorAll('.rrhh-tab-btn');
                const cards = document.querySelectorAll('.rrhh-stat-card');

                // Toggle: si ya está activo, volver a 'todos'
                if (rrhhFiltroActivo === tipo && tipo !== 'todos') {
                    tipo = 'todos';
                }
                rrhhFiltroActivo = tipo;

                // Sync tab buttons
                tabs.forEach(t => {
                    const isActive = t.dataset.filter === tipo;
                    t.classList.toggle('bg-white', isActive);
                    t.classList.toggle('text-slate-700', isActive);
                    t.classList.toggle('shadow-sm', isActive);
                    t.classList.toggle('text-slate-500', !isActive);
                });

                // Highlight active stat card con estilos inline (más confiable que Tailwind dinámico)
                const ringColors = {
                    'Ingreso': '0 0 0 3px #34d399',   // emerald-400
                    'Salida': '0 0 0 3px #fb7185',    // rose-400
                    'Licencia': '0 0 0 3px #818cf8',    // indigo-400
                };
                cards.forEach(c => {
                    const cardTipo = c.dataset.card;
                    if (cardTipo === tipo && tipo !== 'todos') {
                        c.style.boxShadow = ringColors[cardTipo] || '';
                        c.style.transform = 'scale(1.05)';
                    } else {
                        c.style.boxShadow = '';
                        c.style.transform = '';
                    }
                });

                // Filtrar filas con animación suave
                rows.forEach(r => {
                    const show = (tipo === 'todos' || r.dataset.tipo === tipo);
                    if (show) {
                        r.style.display = '';
                        r.style.opacity = '0';
                        r.style.transform = 'translateY(6px)';
                        requestAnimationFrame(() => {
                            r.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                            r.style.opacity = '1';
                            r.style.transform = 'translateY(0)';
                        });
                    } else {
                        r.style.transition = '';
                        r.style.display = 'none';
                    }
                });
            }
        </script>


        <?php
    // -------------------------------------------------------------------------
    // 4. Dashboard para Usuarios
    // -------------------------------------------------------------------------
} elseif ($rol_usuario === 'Usuario') {

    $mis_tickets_creados = array_filter($tickets, function ($t) use ($usuario_id) {
        return $t['creador_id'] == $usuario_id;
    });
    $total_creados = count($mis_tickets_creados);
    $tickets_abiertos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Pendiente'));
    $tickets_resueltos = count(array_filter($mis_tickets_creados, fn($t) => $t['estado'] === 'Completo'));
    ?>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

            <!-- COLUMNA IZQUIERDA: Acciones + Stats (1/4) -->
            <div class="w-full lg:w-1/4 flex flex-col gap-6">

                <!-- Acciones Rápidas -->
                <div
                    class="bg-gradient-to-br from-blue-700 to-indigo-900 rounded-2xl p-6 shadow-xl text-white relative overflow-hidden">
                    <div class="absolute bottom-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-2xl -mr-10 -mb-10"></div>
                    <div class="flex items-center justify-between mb-4 relative z-10">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-blue-200 flex items-center gap-2">
                            <i class="ri-flashlight-line"></i> Acciones Rápidas
                        </h3>
                    </div>
                    <div class="flex flex-col gap-3 relative z-10">
                        <a href="index.php?view=crear_ticket"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-blue-400 flex items-center justify-center shadow-lg shadow-blue-400/40 group-hover:scale-110 transition-transform">
                                <i class="ri-add-circle-line"></i>
                            </div>
                            <span class="font-medium text-sm">Nuevo Ticket</span>
                        </a>
                        <a href="index.php?view=mis_tickets"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-400/40 group-hover:scale-110 transition-transform">
                                <i class="ri-ticket-2-line"></i>
                            </div>
                            <span class="font-medium text-sm">Mis Tickets</span>
                        </a>
                        <a href="index.php?view=listados"
                            class="flex items-center gap-3 p-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/5 transition-all group">
                            <div
                                class="w-8 h-8 rounded-lg bg-violet-400 flex items-center justify-center shadow-lg shadow-violet-400/40 group-hover:scale-110 transition-transform">
                                <i class="ri-list-check-2"></i>
                            </div>
                            <span class="font-medium text-sm">Ver Listados</span>
                        </a>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex-1 overflow-y-auto custom-scrollbar">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                        <i class="ri-bar-chart-2-line text-blue-500"></i> Mis Tickets
                    </h3>
                    <div class="space-y-3">
                        <button onclick="filtrarTablaUsuarioPorEstado('todos')" data-card-u="todos"
                            class="usuario-stat-card w-full flex items-center justify-between p-3 bg-indigo-50 rounded-xl border border-indigo-100 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-ticket-2-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Total</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-indigo-600"><?= $total_creados ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                        <button onclick="filtrarTablaUsuarioPorEstado('Pendiente')" data-card-u="Pendiente"
                            class="usuario-stat-card w-full flex items-center justify-between p-3 bg-amber-50 rounded-xl border border-amber-100 hover:border-amber-300 hover:shadow-md hover:shadow-amber-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-time-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Pendientes</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-amber-600"><?= $tickets_abiertos ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-amber-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                        <button onclick="filtrarTablaUsuarioPorEstado('Completo')" data-card-u="Completo"
                            class="usuario-stat-card w-full flex items-center justify-between p-3 bg-emerald-50 rounded-xl border border-emerald-100 hover:border-emerald-300 hover:shadow-md hover:shadow-emerald-100 transition-all duration-200 cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-checkbox-circle-line text-white text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Resueltos</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold text-emerald-600"><?= $tickets_resueltos ?></span>
                                <i
                                    class="ri-arrow-right-s-line text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </button>
                    </div>

                    <!-- Top Categorías -->
                    <div class="mt-5 pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                            <i class="ri-pie-chart-2-line text-slate-400"></i> Top Categorías
                        </h4>
                        <?php
                        $categorias_stats = [];
                        foreach ($mis_tickets_creados as $t) {
                            $cat = $t['categoria'] ?? 'Otros';
                            $categorias_stats[$cat] = ($categorias_stats[$cat] ?? 0) + 1;
                        }
                        arsort($categorias_stats);
                        $top_categorias = array_slice($categorias_stats, 0, 3);
                        $bar_colors_u = ['bg-blue-500', 'bg-indigo-500', 'bg-violet-500'];
                        $ci = 0;
                        if (empty($top_categorias)) {
                            echo "<p class='text-xs text-slate-400 italic'>Sin datos aún.</p>";
                        } else {
                            foreach ($top_categorias as $cat => $count) {
                                $pct = ($total_creados > 0) ? round(($count / $total_creados) * 100) : 0;
                                $bc = $bar_colors_u[$ci % 3];
                                $ci++;
                                echo "<div class='mb-2'>";
                                echo "<div class='flex justify-between text-xs mb-1'><span class='font-medium text-slate-600 truncate max-w-[120px]'>{$cat}</span><span class='text-slate-400'>{$pct}%</span></div>";
                                echo "<div class='w-full bg-slate-100 rounded-full h-1.5'><div class='h-1.5 rounded-full {$bc}' style='width:{$pct}%'></div></div>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Perfil + Tabla (3/4) -->
            <div
                class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

                <!-- Tarjeta de Perfil -->
                <div
                    class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-2xl p-5 shadow-sm relative overflow-hidden group shrink-0">
                    <div
                        class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-blue-500/10 blur-2xl group-hover:bg-blue-500/20 transition-all">
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-blue-500/30">
                                <?= strtoupper(substr($GLOBALS['nombre_usuario'], 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800 mb-0.5">
                                    Hola, <?= htmlspecialchars(explode(' ', $GLOBALS['nombre_usuario'])[0]) ?>
                                </h2>
                                <p class="text-slate-500 text-sm flex items-center gap-1">
                                    <i class="ri-user-smile-line text-blue-500"></i> Panel de Usuario
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="filtrarTablaUsuarioPorEstado('todos')" data-card-u="todos"
                                class="usuario-stat-card bg-indigo-50 rounded-xl p-3 border border-indigo-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-indigo-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-indigo-600 group-hover:text-indigo-700"><?= $total_creados ?></span>
                                <span class="text-[10px] uppercase font-bold text-indigo-400">Total</span>
                                <div
                                    class="mt-1 text-[9px] text-indigo-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                            <button onclick="filtrarTablaUsuarioPorEstado('Pendiente')" data-card-u="Pendiente"
                                class="usuario-stat-card bg-amber-50 rounded-xl p-3 border border-amber-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-amber-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-amber-600 group-hover:text-amber-700"><?= $tickets_abiertos ?></span>
                                <span class="text-[10px] uppercase font-bold text-amber-400">Pendientes</span>
                                <div
                                    class="mt-1 text-[9px] text-amber-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                            <button onclick="filtrarTablaUsuarioPorEstado('Completo')" data-card-u="Completo"
                                class="usuario-stat-card bg-emerald-50 rounded-xl p-3 border border-emerald-100 text-center min-w-[90px] cursor-pointer hover:scale-105 hover:shadow-lg hover:shadow-emerald-200 transition-all duration-200 group">
                                <span
                                    class="block text-2xl font-bold text-emerald-600 group-hover:text-emerald-700"><?= $tickets_resueltos ?></span>
                                <span class="text-[10px] uppercase font-bold text-emerald-400">Resueltos</span>
                                <div
                                    class="mt-1 text-[9px] text-emerald-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    Ver →</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Tickets Recientes -->
                <div class="flex-1 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between mb-4 shrink-0">
                        <h2 class="text-lg font-bold text-slate-700 flex items-center gap-2">
                            <i class="ri-history-line text-blue-500"></i> Actividad Reciente
                        </h2>
                        <div class="flex items-center gap-2">
                            <!-- Buscador compacto -->
                            <div class="relative">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input type="text" id="buscadorTicketsUsuario" placeholder="Buscar..."
                                    class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-transparent bg-white text-slate-700 w-40 transition-all"
                                    onkeyup="filtrarTablaUsuario()">
                            </div>
                            <a href="index.php?view=mis_tickets"
                                class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg border border-blue-100 transition-colors">
                                Ver Todo
                            </a>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-100" id="tablaTicketsUsuario">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            ID</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            Asunto</th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            Estado</th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            Prioridad</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            Categoría</th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <?php
                                    if (empty($mis_tickets_creados)) {
                                        echo "<tr><td colspan='6' class='px-6 py-12 text-center text-slate-400'>
                                        <div class='flex flex-col items-center'>
                                            <div class='bg-slate-50 p-4 rounded-full mb-3'><i class='ri-ticket-line text-3xl text-slate-300'></i></div>
                                            <p class='font-medium'>No has creado ningún ticket aún</p>
                                            <a href='index.php?view=crear_ticket' class='mt-2 text-sm text-blue-600 hover:underline'>Crear mi primer ticket</a>
                                        </div></td></tr>";
                                    } else {
                                        $ultimos_tickets = array_slice($mis_tickets_creados, 0, 20);
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

                                            echo "<tr class='hover:bg-slate-50/80 transition-all duration-200 group cursor-pointer' data-estado='" . htmlspecialchars($t['estado']) . "' onclick=\"window.location.href='index.php?view=editar_ticket&id={$t['id']}'\">";
                                            echo "<td class='px-4 py-3'><span class='font-mono text-xs font-medium text-slate-400'>#" . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . "</span></td>";
                                            echo "<td class='px-4 py-3'><div class='text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition-colors line-clamp-1'>" . htmlspecialchars($t['titulo']) . "</div><div class='text-xs text-slate-400 mt-0.5'>" . date('d M Y', strtotime($t['fecha_creacion'])) . "</div></td>";
                                            echo "<td class='px-4 py-3 text-center'><span class='inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$s_class} gap-1'><span class='w-1.5 h-1.5 rounded-full bg-current'></span>" . htmlspecialchars($t['estado']) . "</span></td>";
                                            echo "<td class='px-4 py-3 text-center'><span class='inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$p_class} gap-1'><i class='ri-flag-fill text-[10px]'></i>" . htmlspecialchars($t['prioridad']) . "</span></td>";
                                            echo "<td class='px-4 py-3 text-xs text-slate-600'>" . htmlspecialchars($t['categoria']) . "</td>";
                                            echo "<td class='px-4 py-3 text-center'><a href='index.php?view=editar_ticket&id={$t['id']}' class='inline-flex items-center justify-center w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all' title='Ver'><i class='ri-eye-line text-xs'></i></a></td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

            <!-- Panel Header Premium -->
            <div class="bg-white/80 backdrop-blur-xl border border-white/20 rounded-3xl p-6 shadow-sm relative overflow-hidden shrink-0 mb-6">
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-purple-500/10 blur-2xl pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
            
                <div class="relative z-10 flex items-center justify-between gap-5">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-500/30">
                            <i class="ri-shield-star-line text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2 mb-0.5">
                                Panel de SuperAdministrador
                            </h2>
                            <p class="text-slate-500 text-sm font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Control total del sistema
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Principales (Top 4) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                <a href="index.php?view=usuarios" class="group block">
                    <div class="bg-white/60 backdrop-blur-xl rounded-3xl p-6 border border-white/60 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-purple-500/10 transition-all duration-300 h-full relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-purple-500/5 rounded-full blur-xl group-hover:bg-purple-500/10 transition-colors"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Usuarios</p>
                                <h3 class="text-4xl font-black text-slate-800 tracking-tight flex items-baseline gap-1">
                                    <?= $total_usuarios ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center group-hover:scale-110 group-hover:-rotate-3 transition-transform shadow-inner shadow-white">
                                <i class="ri-user-line text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs font-bold text-purple-600 group-hover:gap-2 transition-all gap-1">
                            Gestionar <i class="ri-arrow-right-line"></i>
                        </div>
                    </div>
                </a>

                <a href="index.php?view=listados" class="group block">
                    <div class="bg-white/60 backdrop-blur-xl rounded-3xl p-6 border border-white/60 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10 transition-all duration-300 h-full relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/5 rounded-full blur-xl group-hover:bg-blue-500/10 transition-colors"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Tickets</p>
                                <h3 class="text-4xl font-black text-slate-800 tracking-tight flex items-baseline gap-1">
                                    <?= $total_tickets ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform shadow-inner shadow-white">
                                <i class="ri-ticket-line text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs font-bold text-blue-600 group-hover:gap-2 transition-all gap-1">
                            Ver todos <i class="ri-arrow-right-line"></i>
                        </div>
                    </div>
                </a>

                <a href="index.php?view=formularios_rrhh" class="group block">
                    <div class="bg-white/60 backdrop-blur-xl rounded-3xl p-6 border border-white/60 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-pink-500/10 transition-all duration-300 h-full relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-pink-500/5 rounded-full blur-xl group-hover:bg-pink-500/10 transition-colors"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Formularios RRHH</p>
                                <h3 class="text-4xl font-black text-slate-800 tracking-tight flex items-baseline gap-1">
                                    <?= $total_rrhh ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-pink-100 text-pink-600 flex items-center justify-center group-hover:scale-110 group-hover:-rotate-3 transition-transform shadow-inner shadow-white">
                                <i class="ri-user-star-line text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs font-bold text-pink-600 group-hover:gap-2 transition-all gap-1">
                            Ir a RRHH <i class="ri-arrow-right-line"></i>
                        </div>
                    </div>
                </a>

                <a href="index.php?view=categorias" class="group block">
                    <div class="bg-white/60 backdrop-blur-xl rounded-3xl p-6 border border-white/60 shadow-sm shadow-slate-200/50 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/10 transition-all duration-300 h-full relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition-colors"></div>
                        <div class="relative z-10 flex items-start justify-between">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Categorías</p>
                                <h3 class="text-4xl font-black text-slate-800 tracking-tight flex items-baseline gap-1">
                                    <?= $total_categorias ?>
                                </h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 group-hover:rotate-3 transition-transform shadow-inner shadow-white">
                                <i class="ri-folder-line text-2xl"></i>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-xs font-bold text-emerald-600 group-hover:gap-2 transition-all gap-1">
                            Gestionar <i class="ri-arrow-right-line"></i>
                        </div>
                    </div>
                </a>
            </div>

            <!-- [NEW] Tickets por Sede/País -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <a href="index.php?view=listados&filter=pais_nicaragua" class="group block">
                    <div class="relative overflow-hidden shrink-0 rounded-3xl bg-gradient-to-br from-cyan-900 via-slate-900 to-blue-950 p-6 shadow-xl border border-cyan-500/20 text-white h-full hover:shadow-cyan-500/20 hover:-translate-y-1 transition-all duration-300">
                        <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-cyan-500/20 rounded-full blur-3xl group-hover:bg-cyan-500/40 transition-colors duration-500"></div>
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/40 transition-colors duration-500"></div>
                    
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-cyan-200/80 text-[11px] font-black uppercase tracking-widest mb-1">Sedes</p>
                                <h3 class="text-white text-2xl font-bold tracking-tight mb-2">Nicaragua</h3>
                                <div class="text-5xl font-black bg-gradient-to-br from-white to-cyan-200 bg-clip-text text-transparent"><?= $tickets_nicaragua ?></div>
                            </div>
                            <div class="w-16 h-16 rounded-3xl bg-white/10 flex items-center justify-center backdrop-blur-md border border-white/10 shadow-inner group-hover:scale-110 transition-transform">
                                <i class="ri-map-pin-2-fill text-3xl text-cyan-300"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="index.php?view=listados&filter=pais_honduras" class="group block">
                    <div class="relative overflow-hidden shrink-0 rounded-3xl bg-gradient-to-br from-fuchsia-900 via-slate-900 to-purple-950 p-6 shadow-xl border border-fuchsia-500/20 text-white h-full hover:shadow-fuchsia-500/20 hover:-translate-y-1 transition-all duration-300">
                        <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-fuchsia-500/20 rounded-full blur-3xl group-hover:bg-fuchsia-500/40 transition-colors duration-500"></div>
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/40 transition-colors duration-500"></div>
                    
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-fuchsia-200/80 text-[11px] font-black uppercase tracking-widest mb-1">Sedes</p>
                                <h3 class="text-white text-2xl font-bold tracking-tight mb-2">Honduras</h3>
                                <div class="text-5xl font-black bg-gradient-to-br from-white to-fuchsia-200 bg-clip-text text-transparent"><?= $tickets_honduras ?></div>
                            </div>
                            <div class="w-16 h-16 rounded-3xl bg-white/10 flex items-center justify-center backdrop-blur-md border border-white/10 shadow-inner group-hover:scale-110 transition-transform">
                                <i class="ri-global-fill text-3xl text-fuchsia-300"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Herramientas de Administración -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden h-full flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-500 flex items-center justify-center">
                                <i class="ri-tools-line text-lg"></i>
                            </div>
                            Herramientas Admin
                        </h3>
                    </div>
                    <div class="p-5 flex-1 break-inside-avoid">
                        <div class="grid grid-cols-1 gap-3">
                            <a href="index.php?view=backup" class="flex items-center gap-4 p-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-all group hover:shadow-sm">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-database-2-line text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-emerald-700 transition-colors">Backup BD</h4>
                                    <p class="text-xs text-slate-500">Respaldar base de datos</p>
                                </div>
                            </a>

                            <a href="index.php?view=restore" class="flex items-center gap-4 p-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-100 hover:border-blue-200 transition-all group hover:shadow-sm">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-refresh-line text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-blue-700 transition-colors">Restaurar BD</h4>
                                    <p class="text-xs text-slate-500">Restaurar desde backup</p>
                                </div>
                            </a>

                            <a href="index.php?view=restart" class="flex items-center gap-4 p-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-100 hover:border-red-200 transition-all group hover:shadow-sm">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-restart-line text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-red-700 transition-colors">Reiniciar BD</h4>
                                    <p class="text-xs text-slate-500">Resetear sistema</p>
                                </div>
                            </a>

                            <a href="index.php?view=config" class="flex items-center gap-4 p-3 rounded-2xl bg-white hover:bg-slate-50 border border-slate-100 hover:border-purple-200 transition-all group hover:shadow-sm">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="ri-user-settings-line text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-purple-700 transition-colors">Configuración</h4>
                                    <p class="text-xs text-slate-500">Gestionar sistema</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>


                <!-- Usuarios por Rol -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden h-full flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <i class="ri-team-line text-lg"></i>
                            </div>
                            Usuarios por Rol
                        </h3>
                    </div>
                    <div class="p-5 flex-1">
                        <?php if (empty($usuarios_por_rol)): ?>
                                <div class="text-center py-8 opacity-60 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <i class="ri-team-line text-3xl mb-2 text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-500">No hay datos</span>
                                </div>
                        <?php else: ?>
                                <div class="space-y-3">
                                    <?php
                                    $rol_icons = ['Admin' => 'ri-shield-star-line', 'SuperAdmin' => 'ri-vip-crown-line', 'RRHH' => 'ri-user-settings-line', 'Tecnico' => 'ri-tools-line', 'Usuario' => 'ri-user-line', 'Gerencia' => 'ri-briefcase-line'];
                                    $rol_colors = ['Admin' => 'blue', 'SuperAdmin' => 'purple', 'RRHH' => 'emerald', 'Tecnico' => 'orange', 'Usuario' => 'slate', 'Gerencia' => 'indigo'];

                                    foreach ($usuarios_por_rol as $rol):
                                        $icon = $rol_icons[$rol['nombre']] ?? 'ri-user-line';
                                        $color = $rol_colors[$rol['nombre']] ?? 'slate';
                                        ?>
                                            <div class="group relative flex items-center justify-between p-3.5 bg-white rounded-2xl border border-slate-100 hover:border-<?= $color ?>-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 cursor-pointer"
                                                onclick="mostrarDetalleRol('<?= htmlspecialchars($rol['nombre']) ?>', <?= $rol['total'] ?>)">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-10 h-10 rounded-xl bg-<?= $color ?>-50 text-<?= $color ?>-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-<?= $color ?>-500 group-hover:text-white transition-all">
                                                        <i class="<?= $icon ?> text-xl"></i>
                                                    </div>
                                                    <span class="font-bold text-slate-700 text-sm group-hover:text-<?= $color ?>-700 transition-colors">
                                                        <?= htmlspecialchars($rol['nombre']) ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="px-3 py-1 bg-<?= $color ?>-50 outline outline-1 outline-<?= $color ?>-200 text-<?= $color ?>-700 rounded-full text-xs font-black shadow-sm group-hover:bg-<?= $color ?>-600 group-hover:text-white group-hover:outline-transparent transition-all">
                                                        <?= $rol['total'] ?>
                                                    </span>
                                                    <i class="ri-arrow-right-s-line text-slate-400 group-hover:text-<?= $color ?>-600 transition-colors"></i>
                                                </div>
                                            </div>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tickets por Estado -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden h-full flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                                <i class="ri-pie-chart-line text-lg"></i>
                            </div>
                            Tickets por Estado
                        </h3>
                    </div>
                    <div class="p-5 flex-1">
                        <?php if (empty($tickets_por_estado)): ?>
                                <div class="text-center py-8 opacity-60 flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-2xl">
                                    <i class="ri-pie-chart-line text-3xl mb-2 text-slate-400"></i>
                                    <span class="text-sm font-bold text-slate-500">No hay datos</span>
                                </div>
                        <?php else: ?>
                                <div class="space-y-4">
                                    <?php
                                    $estado_configs = [
                                        'Pendiente' => ['color' => 'amber', 'icon' => 'ri-error-warning-line'],
                                        'Asignado' => ['color' => 'blue', 'icon' => 'ri-user-settings-line'],
                                        'Completo' => ['color' => 'emerald', 'icon' => 'ri-checkbox-circle-line'],
                                        'En Proceso' => ['color' => 'blue', 'icon' => 'ri-loader-4-line'],
                                        'Resuelto' => ['color' => 'emerald', 'icon' => 'ri-checkbox-circle-line'],
                                        'Cerrado' => ['color' => 'slate', 'icon' => 'ri-lock-line']
                                    ];

                                    $total_tickets_estados = array_sum(array_column($tickets_por_estado, 'total'));

                                    foreach ($tickets_por_estado as $estado):
                                        $ec = $estado_configs[$estado['estado']] ?? ['color' => 'slate', 'icon' => 'ri-file-line'];
                                        $porcentaje = $total_tickets_estados > 0 ? round(($estado['total'] / $total_tickets_estados) * 100) : 0;
                                        $c = $ec['color'];
                                        ?>
                                            <div class="group relative cursor-pointer" onclick="mostrarDetalleEstado('<?= htmlspecialchars($estado['estado']) ?>', <?= $estado['total'] ?>, <?= $porcentaje ?>)">
                                                <div class="flex items-center justify-between p-3.5 bg-white rounded-2xl border border-slate-100 hover:border-<?= $c ?>-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-10 h-10 rounded-xl bg-<?= $c ?>-50 text-<?= $c ?>-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                                            <i class="<?= $ec['icon'] ?> text-xl"></i>
                                                        </div>
                                                        <div>
                                                            <span class="font-bold text-slate-800 text-sm group-hover:text-<?= $c ?>-700 transition-colors block leading-tight">
                                                                <?= htmlspecialchars($estado['estado']) ?>
                                                            </span>
                                                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mt-0.5">
                                                                <?= $porcentaje ?>% del total
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="px-3 py-1 bg-<?= $c ?>-50 text-<?= $c ?>-700 rounded-full text-xs font-black shadow-sm outline outline-1 outline-<?= $c ?>-200 group-hover:bg-<?= $c ?>-500 group-hover:text-white group-hover:outline-transparent transition-all">
                                                            <?= $estado['total'] ?>
                                                        </span>
                                                        <i class="ri-arrow-right-s-line text-slate-400 group-hover:text-<?= $c ?>-600 transition-colors"></i>
                                                    </div>
                                                </div>
                                                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden w-[95%] mx-auto">
                                                    <div class="h-full bg-<?= $c ?>-500 transition-all duration-1000 ease-out rounded-full shadow-sm group-hover:brightness-110" style="width: <?= $porcentaje ?>%"></div>
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
</div><?php if ($rol_usuario === 'Tecnico'): ?>
        <!-- MODAL AGREGAR /EDITAR HERRAMIENTA -->
        <div id="modal-add-tool" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"
                onclick="document.getElementById('modal-add-tool').classList.add('hidden')"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4" id="modal-tool-title">Agregar Acción
                                Rápida</h3>
                            <form id="form-add-tool" onsubmit="agregarHerramienta(event)">
                                <input type="hidden" name="id" id="tool_id">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Nombre</label>
                                        <input type="text" name="nombre" id="tool_nombre" required placeholder="Ej. Cámaras"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">URL</label>
                                        <input type="url" name="url" id="tool_url" required placeholder="https://..."
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Icono</label>
                                        <select name="icono" id="tool_icono"
                                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                                            <option value="ri-link">🔗 Link Generico</option>
                                            <option value="ri-camera-line">📷 Cámara</option>
                                            <option value="ri-server-line">🖧 Servidor/Switch</option>
                                            <option value="ri-microsoft-line">📝 Office 365</option>
                                            <option value="ri-global-line">🌐 Web</option>
                                            <option value="ri-printer-line">🖨️ Impresora</option>
                                            <option value="ri-file-cloud-line">☁️ Cloud</option>
                                            <option value="ri-shield-check-line">🛡️ Seguridad</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button type="submit"
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Guardar</button>
                                    <button type="button"
                                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0"
                                        onclick="document.getElementById('modal-add-tool').classList.add('hidden')">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openAddToolModal() {
                document.getElementById('form-add-tool').reset();
                document.getElementById('tool_id').value = '';
                document.getElementById('modal-tool-title').textContent = 'Agregar Herramienta';
                document.getElementById('modal-add-tool').classList.remove('hidden');
            }

            function editarHerramienta(tool) {
                document.getElementById('tool_id').value = tool.id;
                document.getElementById('tool_nombre').value = tool.nombre;
                document.getElementById('tool_url').value = tool.url;
                document.getElementById('tool_icono').value = tool.icono;
                document.getElementById('modal-tool-title').textContent = 'Editar Herramienta';
                document.getElementById('modal-add-tool').classList.remove('hidden');
            }

            function agregarHerramienta(e) {
                e.preventDefault();
                const formData = new FormData(e.target);
                const id = document.getElementById('tool_id').value;

                formData.append('ajax_action', 'manage_tools');
                formData.append('sub_action', id ? 'edit' : 'add');

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert('Error: ' + data.msg);
                        }
                    });
            }

            function eliminarHerramienta(id, btn) {
                if (!confirm('¿Eliminar este acceso directo?')) return;

                const formData = new FormData();
                formData.append('ajax_action', 'manage_tools');
                formData.append('sub_action', 'delete');
                formData.append('id', id);

                fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            btn.closest('.group\\/item').remove(); // remove row if exists
                            location.reload(); // safest reload to update layout
                        } else {
                            alert('Error');
                        }
                    });
            }
        </script>
<?php endif; ?>