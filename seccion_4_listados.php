<?php
/**
 * seccion_4_listados.php - Módulo de listados y tablas del sistema
 * Muestra diferentes vistas de tickets y usuarios según el rol y la vista seleccionada.
 */
?>
<div class="p-6 flex-1 glass">
    <?php
    // Función auxiliar para imprimir tabla de tickets moderna
    function tabla_tickets($tickets, $filtrar_por_tecnico = false, $tecnico_id = null, $es_admin = false)
    {
        echo '<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">';
        echo '<div class="overflow-x-auto">';
        echo '<table class="w-full text-left border-collapse">';
        echo '<thead>';
        echo '<tr class="bg-slate-50 border-b border-slate-200/60">';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center font-mono">ID</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-left">Asunto</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Fecha</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Solicitante</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Prioridad</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Estado</th>';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Categoría</th>';
        if (!$filtrar_por_tecnico) {
            echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Técnico</th>';
        }
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Acciones</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="divide-y divide-slate-100">';

        if (empty($tickets)) {
            echo '<tr><td colspan="100%" class="px-6 py-12 text-center text-slate-500">';
            echo '<div class="flex flex-col items-center justify-center gap-3">';
            echo '<div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 mb-2"><i class="ri-ticket-2-line text-3xl"></i></div>';
            echo '<p class="text-lg font-medium text-slate-600">No hay tickets registrados</p>';
            echo '<p class="text-sm text-slate-400">Tus tickets aparecerán aquí cuando los crees.</p>';
            echo '</div>';
            echo '</td></tr>';
        }


        foreach ($tickets as $t) {
            if ($filtrar_por_tecnico && $t['tecnico_id'] != $tecnico_id)
                continue;

            // MOVEMOS LA BÚSQUEDA DEL CREADOR AQUÍ ARRIBA PARA USARLA EN EL COLOR DE LA FILA
            $nombre_creador = 'Desconocido';
            $rol_creador = '';
            foreach ($GLOBALS["usuarios"] as $u) {
                if ($u["id"] == $t["creador_id"]) {
                    $nombre_creador = $u["nombre"];
                    $rol_creador = $u["rol"] ?? 'Usuario';
                    break;
                }
            }

            // Lógica de Resaltado para RRHH (Nuevo Ingreso / Baja)
            $row_class = 'hover:bg-slate-50/80 transition-colors group border-l-4 border-transparent'; // Clase por defecto
    
            // Lógica de Resaltado (Global, no solo RRHH, para incluir pruebas de Admin)
            if (stripos($t['titulo'], 'Nuevo Ingreso') !== false) {
                // Verde para Ingresos
                $row_class = 'bg-emerald-50 hover:bg-emerald-100 transition-colors group border-l-4 border-emerald-400';
            } elseif (stripos($t['titulo'], 'Baja de Personal') !== false) {
                // Rojo para Bajas
                $row_class = 'bg-rose-50 hover:bg-rose-100 transition-colors group border-l-4 border-rose-400';
            } elseif (stripos($t['titulo'], 'Solicitud de Licencia') !== false) {
                // Azul para Licencias
                $row_class = 'bg-blue-50 hover:bg-blue-100 transition-colors group border-l-4 border-blue-400';
            }

            $prio_colors = [
                'Baja' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                'Media' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'Alta' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                'Critica' => 'bg-rose-50 text-rose-700 ring-rose-600/20'
            ];
            $p_clase_base = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset';
            $p_estilo = $prio_colors[$t['prioridad']] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';

            $status_colors = [
                'Pendiente' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                'Asignado' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                'Completo' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                'Cerrado' => 'bg-slate-50 text-slate-700 ring-slate-600/20'
            ];
            $s_estilo = $status_colors[$t['estado']] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';

            $empresa_row = $t['empresa_nombre'] ?? '';
            $sucursal_row = $t['sucursal_nombre'] ?? '';

            echo '<tr class="' . $row_class . ' cursor-pointer" onclick="window.location.href=\'index.php?view=editar_ticket&id=' . $t['id'] . '\'" data-empresa="' . htmlspecialchars($empresa_row) . '" data-sucursal="' . htmlspecialchars($sucursal_row) . '">';
            echo '<td class="px-6 py-4 text-sm font-medium text-slate-500 text-center font-mono">#' . str_pad($t['id'], 4, '0', STR_PAD_LEFT) . '</td>';
            echo '<td class="px-6 py-4 text-left">';
            echo '<div class="text-sm font-bold text-slate-800">' . htmlspecialchars($t['titulo']) . '</div>';
            echo '<div class="text-xs text-slate-400 mt-1 truncate max-w-xs">' . htmlspecialchars(substr($t['descripcion'], 0, 60)) . '...</div>';
            echo '</td>';

            // Fecha
            echo '<td class="px-6 py-4 text-sm text-slate-500 text-center">' . date('d/m/y', strtotime($t['fecha_creacion'])) . '<br><span class="text-xs text-slate-400">' . date('H:i', strtotime($t['fecha_creacion'])) . '</span></td>';

            // Colores de Rol (Dinámico)
            $rol_key = $GLOBALS['rol_colors_config'][$rol_creador] ?? 'slate';
            $r_class = $GLOBALS['colores_badges_map'][$rol_key] ?? $GLOBALS['colores_badges_map']['slate'];

            echo '<td class="px-6 py-4 text-center">';
            echo '<div class="flex flex-col items-center">';
            echo '<span class="text-sm font-medium text-slate-700">' . htmlspecialchars($nombre_creador) . '</span>';
            if ($rol_creador) {
                echo '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset ' . $r_class . ' mt-1">' . htmlspecialchars($rol_creador) . '</span>';
            }
            echo '</div>';
            echo '</td>';

            echo '<td class="px-6 py-4 text-center">';
            echo '<span class="' . $p_clase_base . ' ' . $p_estilo . '">';
            echo '<i class="ri-flag-fill text-[10px]"></i> ' . htmlspecialchars($t['prioridad']);
            echo '</span>';
            echo '</td>';

            echo '<td class="px-6 py-4 text-center">';
            echo '<span class="' . $p_clase_base . ' ' . $s_estilo . '">';
            echo '<span class="w-1.5 h-1.5 rounded-full bg-current"></span> ' . htmlspecialchars($t['estado']);
            echo '</span>';
            echo '</td>';

            echo '<td class="px-6 py-4 text-sm text-slate-600 text-center"><div class="flex items-center justify-center gap-1.5 bg-slate-50 py-1 px-3 rounded-lg border border-slate-100 w-fit mx-auto"><i class="ri-folder-2-line text-slate-400"></i> <span class="font-medium text-xs">' . htmlspecialchars($t['categoria']) . '</span></div></td>';

            $nombre_tecnico = 'Sin Asignar';
            $tecnico_avatar = 'U';
            foreach ($GLOBALS["usuarios"] as $u) {
                if ($u["id"] == $t["tecnico_id"]) {
                    $nombre_tecnico = $u["nombre"];
                    $tecnico_avatar = strtoupper(substr($u["nombre"], 0, 1));
                    break;
                }
            }

            if (!$filtrar_por_tecnico) {
                echo '<td class="px-6 py-4 text-center">';
                if ($t['tecnico_id']) {
                    echo '<div class="flex items-center justify-center gap-2 group/tec relative">';
                    echo '<div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm">' . $tecnico_avatar . '</div>';
                    echo '<span class="text-xs font-medium text-slate-600">' . explode(' ', $nombre_tecnico)[0] . '</span>';
                    echo '</div>';
                } else {
                    echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-50 text-slate-400 text-xs border border-slate-100 border-dashed"><i class="ri-user-unfollow-line"></i> Pendiente</span>';
                }
                echo '</td>';
            }

            echo '<td class="px-6 py-4">';
            echo '<div class="flex items-center justify-center gap-2">';
            // Botón VER (Siempre visible)
    

            // Lógica de botones de acción
            // 1. Si NO tiene técnico asignado (libre) -> Botón EDITAR simple
            if (empty($t['tecnico_id'])) {
                echo '<a href="index.php?view=editar_ticket&id=' . $t['id'] . '" onclick="event.stopPropagation()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Editar Ticket"><i class="ri-edit-line"></i></a>';
            }
            // 2. Si tiene técnico y es el usuario actual, o es admin, o es el creador -> Botón GESTIONAR
            elseif ($t['tecnico_id'] == $GLOBALS['usuario_id'] || $es_admin || $t['creador_id'] == $GLOBALS['usuario_id']) {
                echo '<a href="index.php?view=editar_ticket&id=' . $t['id'] . '" onclick="event.stopPropagation()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Gestionar Ticket"><i class="ri-edit-box-line"></i></a>';
            }

            if ($es_admin) {
                echo '<form method="POST" action="index.php?view=asignar" class="flex items-center gap-2" onclick="event.stopPropagation()">';
                echo '<input type="hidden" name="csrf_token" value="' . generar_csrf_token() . '">';
                echo '<input type="hidden" name="ticket_id" value="' . $t['id'] . '">';
                echo '<input type="hidden" name="asignar_tecnico" value="1">';
                echo '<details class="relative group/menu">';
                echo '<summary class="list-none cursor-pointer outline-none">';
                echo '<div class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm hover:border-indigo-300 hover:ring-2 hover:ring-indigo-100 transition-all group-open/menu:ring-2 group-open/menu:ring-indigo-100 text-xs font-medium text-slate-600">';
                echo '<i class="ri-user-add-line text-indigo-500"></i>';
                echo '<span>Asignar</span>';
                echo '</div>';
                echo '</summary>';

                echo '<div class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 p-2 z-50 transform origin-top-right transition-all">';
                echo '<div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 py-1 mb-1">Técnicos Disponibles</div>';
                echo '<div class="flex flex-col gap-1 max-h-48 overflow-y-auto custom-scrollbar">';

                foreach ($GLOBALS["usuarios"] as $u) {
                    if ($u['rol'] === 'Tecnico') {
                        $initials = strtoupper(substr($u['nombre'], 0, 2));
                        echo '<button type="submit" name="tecnico_id" value="' . $u['id'] . '" class="flex items-center gap-3 w-full p-2 hover:bg-slate-50 rounded-lg transition-colors text-left group/item">';
                        echo '<div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-[10px] font-bold shadow-sm group-hover/item:scale-110 transition-transform">' . $initials . '</div>';
                        echo '<div>';
                        echo '<div class="text-xs font-medium text-slate-700">' . htmlspecialchars($u['nombre']) . '</div>';
                        echo '<div class="text-[10px] text-slate-400">Técnico</div>';
                        echo '</div>';
                        echo '</button>';
                    }
                }
                echo '</div>';
                echo '</div>';
                echo '</details>';
                echo '</form>';
            }
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }

    // 1. Mis Tickets (Usuario/Admin/RRHH)
    if (!empty($mostrar_solo_mis_tickets) || (!empty($mostrar_listado_general) && $rol_usuario === 'Usuario')) {
        echo '<div class="flex justify-between items-center mb-8 border-b border-slate-200 pb-4">';
        echo '<h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3"><i class="ri-ticket-2-line text-blue-600"></i> Mis Tickets</h2>';
        echo '</div>';
        $mis_tickets = array_filter($tickets, function ($t) use ($usuario_id) {
            return $t['creador_id'] == $usuario_id;
        });

        // Ordenamiento personalizado: Activos primero (por ID desc), Resueltos al final
        usort($mis_tickets, function ($a, $b) {
            $estados_finales = ['Completo'];
            $a_es_final = $a['estado'] === 'Completo';
            $b_es_final = $b['estado'] === 'Completo';

            // 1. Grupo: Activos antes que Finalizados
            if ($a_es_final && !$b_es_final)
                return 1;
            if (!$a_es_final && $b_es_final)
                return -1;

            // 2. Orden secundario: Más nuevos primero (ID Descendente)
            return $b['id'] - $a['id'];
        });

        tabla_tickets($mis_tickets);
    }

    // 2. Mis Asignaciones (Técnico)
    elseif (!empty($mostrar_mis_asignaciones)) {
        echo '<h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="ri-checkbox-multiple-line text-blue-500"></i> Tickets Asignados</h2>';

        // Filtrar tickets ya Resueltos o Cerrados para limpiar la vista de trabajo
        $mis_tareas_activas = array_filter($tickets, function ($t) {
            return $t['estado'] !== 'Completo';
        });

        tabla_tickets($mis_tareas_activas, true, $usuario_id);
    }

    // 3. Gestión de Asignaciones (Admin) - CON FILTROS MEJORADOS
    elseif (!empty($mostrar_solo_tabla_tickets_admin) || (!empty($mostrar_listado_general) && in_array($rol_usuario, ['Admin', 'SuperAdmin', 'Gerencia']))) {
        // [NEW] Lógica de filtrado "Activos vs Historial"
        $modo_historial = isset($_GET['mode']) && $_GET['mode'] === 'history';

        if ($modo_historial) {
            echo '<h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="ri-history-line text-purple-500"></i> Historial de Tickets (Finalizados)</h2>';
            // Mostrar SOLO completados/resueltos/cerrados
            $tickets = array_filter($tickets, function ($t) {
                return in_array($t['estado'], ['Completo', 'Resuelto', 'Cerrado']);
            });
        } else {
            echo '<h2 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="ri-admin-line text-blue-500"></i> Gestión de Asignaciones (Activos)</h2>';
            // Filtrar y ocultar completados por defecto
            $tickets = array_filter($tickets, function ($t) {
                return !in_array($t['estado'], ['Completo', 'Resuelto', 'Cerrado']);
            });
        }

        // Barra de Búsqueda y Filtros
        echo '<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">';
        echo '<div class="grid grid-cols-1 md:grid-cols-6 gap-4">';

        // Búsqueda
        echo '<div class="md:col-span-2">';
        echo '<div class="relative">';
        echo '<i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>';
        echo '<input type="text" id="busquedaTickets" placeholder="Buscar por ID, asunto, prioridad, estado o técnico..." ';
        echo 'class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" ';
        echo 'onkeyup="filtrarTablaTickets()">';
        echo '</div>';
        echo '</div>';

        // Filtro por Estado
        echo '<div>';
        $url_filter = $_GET['filter'] ?? '';
        $default_status = '';
        if ($url_filter === 'abiertos')
            $default_status = 'Pendiente';
        elseif ($url_filter === 'asignado')
            $default_status = 'Asignado';
        elseif ($url_filter === 'completo')
            $default_status = 'Completo';

        echo '<select id="filtroEstado" onchange="filtrarTablaTickets()" ';
        echo 'class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">';
        echo '<option value="">Todos los estados</option>';
        echo '<option value="Pendiente" ' . ($default_status === 'Pendiente' ? 'selected' : '') . '>Pendiente</option>';
        echo '<option value="Asignado" ' . ($default_status === 'Asignado' ? 'selected' : '') . '>Asignado</option>';
        echo '<option value="Completo" ' . ($default_status === 'Completo' ? 'selected' : '') . '>Completo</option>';
        echo '</select>';
        echo '</div>';

        // Filtro por Prioridad
        echo '<div>';
        echo '<select id="filtroPrioridad" onchange="filtrarTablaTickets()" ';
        echo 'class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">';
        echo '<option value="">Todas las prioridades</option>';
        echo '<option value="Baja">Baja</option>';
        echo '<option value="Media">Media</option>';
        echo '<option value="Alta">Alta</option>';
        echo '<option value="Critica">Crítica</option>';
        echo '</select>';
        echo '</div>';

        // Filtro por Empresa
        echo '<div>';
        // [MOD] Added onchange call to updated filtering logic
        echo '<select id="filtroEmpresa" onchange="filtrarSucursales(); filtrarTablaTickets()" ';
        echo 'class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">';
        echo '<option value="" data-empresa-id="">Todas las empresas</option>';
        if (isset($GLOBALS['todas_las_empresas'])) {
            foreach ($GLOBALS['todas_las_empresas'] as $emp) {
                // [MOD] Added data-empresa-id
                echo '<option value="' . htmlspecialchars($emp['nombre']) . '" data-empresa-id="' . $emp['id'] . '">' . htmlspecialchars($emp['nombre']) . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';

        // Filtro por Sucursal
        echo '<div>';
        echo '<select id="filtroSucursal" onchange="filtrarTablaTickets()" ';
        echo 'class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">';
        echo '<option value="">Todas las sucursales</option>';
        if (isset($GLOBALS['todas_las_sucursales'])) {
            foreach ($GLOBALS['todas_las_sucursales'] as $suc) {
                // [MOD] Added data-empresa-id
                $emp_id = $suc['empresa_id'] ?? '';
                echo '<option value="' . htmlspecialchars($suc['nombre']) . '" data-empresa-id="' . $emp_id . '">' . htmlspecialchars($suc['nombre']) . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';

        // Botón Limpiar
        echo '<div>';
        echo '<button onclick="limpiarFiltrosTickets()" ';
        echo 'class="w-full px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-all flex items-center justify-center gap-2">';
        echo '<i class="ri-refresh-line"></i> Limpiar';
        echo '</button>';
        echo '</div>'; // Fin boton limpiar
    
        // Botón Exportar
        echo '<div>';
        echo '<button onclick="exportarExcelListado()" ';
        echo 'class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-all flex items-center justify-center gap-2">';
        echo '<i class="ri-file-excel-2-line"></i> Exportar';
        echo '</button>';
        echo '</div>';

        // Botón Modos (Activos / Historial)
        echo '<div>';
        if ($modo_historial) {
            echo '<a href="index.php?view=listados" ';
            echo 'class="block w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all text-center gap-2">';
            echo '<i class="ri-file-list-line"></i> Ver Activos';
            echo '</a>';
        } else {
            echo '<a href="index.php?view=listados&mode=history" ';
            echo 'class="block w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-all text-center gap-2">';
            echo '<i class="ri-history-line"></i> Ver Historial';
            echo '</a>';
        }
        echo '</div>';

        echo '</div>'; // grid
    
        // Contador de resultados
        echo '<div class="mt-4 flex items-center justify-between text-sm">';
        echo '<span id="contadorTickets" class="text-slate-600"></span>';
        echo '<span id="estadoFiltroTickets" class="text-slate-500"></span>';
        echo '</div>';

        echo '</div>'; // bg-white
    
        // MOSTRAR TODOS LOS TICKETS (no solo Abiertos)
    
        // [NEW] Filtro por Sede/País (Desde Dashboard)
        if (isset($_GET['filter'])) {
            $f = $_GET['filter'];
            if (strpos($f, 'pais_') === 0) {
                $pais_filtro = explode('_', $f)[1]; // 'nicaragua' o 'honduras'
                $tickets = array_filter($tickets, function ($t) use ($pais_filtro) {
                    $pais = strtolower($t['creador_pais'] ?? '');
                    return stripos($pais, $pais_filtro) !== false;
                });
            } elseif (strpos($f, 'empresa_') === 0) {
                $id_empresa = explode('_', $f)[1]; // '1', '2', etc.
                $tickets = array_filter($tickets, function ($t) use ($id_empresa) {
                    return isset($t['empresa_id']) && $t['empresa_id'] == $id_empresa;
                });
            }
        }

        // Ordenamiento personalizado: 1. Sin Asignar, 2. Asignados, 3. Completados
        $tickets_ordenados = $tickets;
        usort($tickets_ordenados, function ($a, $b) {
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
                $tier_a = 2; // Finalizados al fondo
            elseif ($a_assigned)
                $tier_a = 1; // Asignados en medio
            else
                $tier_a = 0; // Sin asignar al tope
    
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

        tabla_tickets($tickets_ordenados, false, null, true);

        // JavaScript para filtrado
        ?>
        <script>
            // [NEW] Filtrado dependiente Empresa -> Sucursal
            function filtrarSucursales() {
                const empresaSelect = document.getElementById('filtroEmpresa');
                const sucursalSelect = document.getElementById('filtroSucursal');

                // Obtener ID de la empresa seleccionada
                const selectedOption = empresaSelect.options[empresaSelect.selectedIndex];
                const empresaId = selectedOption.getAttribute('data-empresa-id');

                // Resetear selección de sucursal
                sucursalSelect.value = "";

                // Mostrar/Ocultar opciones
                let visibleCount = 0;
                for (let i = 0; i < sucursalSelect.options.length; i++) {
                    const option = sucursalSelect.options[i];
                    const optionEmpresaId = option.getAttribute('data-empresa-id');

                    // Siempre mostrar la opción por defecto ("Todas")
                    if (option.value === "") {
                        option.style.display = "";
                        continue;
                    }

                    // Si no hay empresa seleccionada, mostrar todo.
                    // Si hay empresa seleccionada, mostrar solo coincidencias.
                    if (!empresaId || optionEmpresaId === empresaId) {
                        option.style.display = "";
                        visibleCount++;
                    } else {
                        option.style.display = "none";
                    }
                }
            }

            function filtrarTablaTickets() {
                const busqueda = document.getElementById('busquedaTickets').value.toLowerCase();
                const filtroEstado = document.getElementById('filtroEstado').value.toLowerCase();
                const filtroPrioridad = document.getElementById('filtroPrioridad').value.toLowerCase();
                const tabla = document.querySelector('tbody');
                const filas = tabla.getElementsByTagName('tr');
                let visibles = 0;
                let total = 0;

                for (let i = 0; i < filas.length; i++) {
                    const fila = filas[i];
                    if (fila.querySelector('td[colspan]')) continue;
                    total++;

                    const id = fila.cells[0].textContent.toLowerCase();
                    const asunto = fila.cells[1].textContent.toLowerCase();
                    const prioridad = fila.cells[4].textContent.toLowerCase();
                    const estado = fila.cells[5].textContent.toLowerCase();
                    // [MOD] New technician column extraction.
                    // IMPORTANT: The technician column logic relies on the column order in tabla_tickets.
                    // Assuming columns are: ID, Asunto, Fecha, Solicitante, Prioridad, Estado, Categoria, (Technician or Pending), Acciones
                    // Technician is likely at index 7 if we are in admin view (filtrar_por_tecnico = false)
                    const tecnicoCell = fila.cells[7];
                    let tecnico = "";
                    if (tecnicoCell) {
                        tecnico = tecnicoCell.textContent.toLowerCase();
                    }

                    const empresaData = fila.getAttribute('data-empresa')?.toLowerCase() || "";
                    const sucursalData = fila.getAttribute('data-sucursal')?.toLowerCase() || "";

                    const filtroEmpresa = document.getElementById('filtroEmpresa').value.toLowerCase();
                    const filtroSucursal = document.getElementById('filtroSucursal').value.toLowerCase();

                    // [MOD] Extended search logic
                    const coincideBusqueda = id.includes(busqueda) ||
                        asunto.includes(busqueda) ||
                        prioridad.includes(busqueda) ||
                        estado.includes(busqueda) ||
                        tecnico.includes(busqueda);

                    const coincideEstado = filtroEstado === '' || estado.includes(filtroEstado);
                    const coincidePrioridad = filtroPrioridad === '' || prioridad.includes(filtroPrioridad);

                    const coincideEmpresa = filtroEmpresa === '' || empresaData === filtroEmpresa;
                    const coincideSucursal = filtroSucursal === '' || sucursalData === filtroSucursal;

                    if (coincideBusqueda && coincideEstado && coincidePrioridad && coincideEmpresa && coincideSucursal) {
                        fila.style.display = '';
                        visibles++;
                        fila.style.animation = 'fadeIn 0.3s ease-in';
                    } else {
                        fila.style.display = 'none';
                    }
                }
                actualizarContadorTickets(visibles, total);
                actualizarEstadoFiltroTickets(busqueda, filtroEstado, filtroPrioridad);
            }

            function actualizarContadorTickets(visibles, total) {
                const contador = document.getElementById('contadorTickets');
                if (visibles === total) {
                    contador.innerHTML = `<i class="ri-ticket-line mr-1"></i>Mostrando <strong>${total}</strong> ticket(s)`;
                } else {
                    contador.innerHTML = `<i class="ri-filter-line mr-1"></i>Mostrando <strong>${visibles}</strong> de <strong>${total}</strong> ticket(s)`;
                }
            }

            function actualizarEstadoFiltroTickets(busqueda, estado, prioridad) {
                const estadoElem = document.getElementById('estadoFiltroTickets');
                const filtros = [];
                if (busqueda) filtros.push(`Búsqueda: "${busqueda}"`);
                if (estado) filtros.push(`Estado: ${estado}`);
                if (prioridad) filtros.push(`Prioridad: ${prioridad}`);
                if (filtros.length > 0) {
                    estadoElem.innerHTML = `<i class="ri-filter-2-line mr-1"></i>${filtros.join(' | ')}`;
                    estadoElem.classList.add('text-blue-600', 'font-semibold');
                } else {
                    estadoElem.innerHTML = '';
                    estadoElem.classList.remove('text-blue-600', 'font-semibold');
                }
            }

            function limpiarFiltrosTickets() {
                document.getElementById('busquedaTickets').value = '';
                document.getElementById('filtroEstado').value = '';
                document.getElementById('filtroPrioridad').value = '';
                document.getElementById('filtroEmpresa').value = '';
                document.getElementById('filtroSucursal').value = '';
                filtrarTablaTickets();
            }

            document.addEventListener('DOMContentLoaded', function () {
                filtrarTablaTickets();
                const style = document.createElement('style');
                style.textContent = `@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }`;
                document.head.appendChild(style);
            });

            function exportarExcelListado() {
                let table = document.querySelector("table");
                if (!table) return;

                let tempTable = document.createElement("table");
                let thead = document.createElement("thead");
                let trHead = document.createElement("tr");

                // Copiar headers menos el último (Acciones)
                let headers = table.querySelectorAll("thead th");
                for (let i = 0; i < headers.length - 1; i++) {
                    let newTh = document.createElement("th");
                    newTh.innerText = headers[i].innerText.toUpperCase().trim();
                    newTh.style.border = "1px solid #999";
                    newTh.style.background = "#f0f0f0";
                    trHead.appendChild(newTh);
                }
                thead.appendChild(trHead);
                tempTable.appendChild(thead);

                let tbody = document.createElement("tbody");
                let rows = table.querySelectorAll("tbody tr");

                rows.forEach(row => {
                    if (row.style.display === 'none') return; // Solo exportar visibles

                    let newRow = document.createElement("tr");
                    let cells = row.querySelectorAll("td");

                    // Si la fila es de estructura normal (evitar colspan raros)
                    if (cells.length > 3) {
                        for (let i = 0; i < cells.length - 1; i++) { // Ignorar última columna (Acciones)
                            let td = document.createElement("td");
                            td.style.border = "1px solid #ddd";

                            let cell = cells[i];

                            // Lógica de limpieza según el índice columna (aproximado)
                            // 1: Asunto (Divs)
                            if (i === 1) {
                                let titulo = cell.querySelector(".text-sm")?.innerText || "";
                                let desc = cell.querySelector(".text-xs")?.innerText || "";
                                td.innerHTML = `<b>${titulo}</b><br><span style='color:#555'>${desc}</span>`;
                            }
                            // 5: Técnico (con avatar)
                            else if (cell.querySelector(".rounded-full") && cell.innerText.trim() !== "") {
                                let nombre = cell.querySelector("span.text-sm")?.innerText || cell.innerText;
                                td.innerText = nombre.trim();
                            }
                            // Estado/Prioridad (Spans)
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
                let style = '<style>body { font-family: Arial; } table { border-collapse: collapse; width: 100%; } td, th { vertical-align: top; mso-number-format:"\\@"; }</style>';
                let html = meta + style + tempTable.outerHTML;

                let blob = new Blob([html], {
                    type: "application/vnd.ms-excel"
                });
                let url = URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = url;
                a.download = "Listado_Tickets_" + new Date().toISOString().slice(0, 10) + ".xls";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                setTimeout(() => URL.revokeObjectURL(url), 100);
            }
        </script>
        <?php
    }

    // 4. Gestión de Usuarios (Admin)
    elseif (!empty($mostrar_solo_tabla_usuarios)) {
        echo '<h2 class="text-2xl font-bold text-slate-800 mt-8 mb-6 flex items-center gap-2"><i class="ri-team-line text-blue-500"></i> Usuarios del Sistema</h2>';
        echo '<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">';
        echo '<div class="overflow-x-auto">';
        echo '<table class="w-full text-left border-collapse">';
        echo '<thead><tr class="bg-slate-50/50 border-b border-slate-100">';
        echo '<th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">ID</th><th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Nombre</th><th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Email</th><th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Rol</th><th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Acciones</th>';
        echo '</tr></thead><tbody class="divide-y divide-slate-100">';
        foreach ($usuarios as $u) {
            echo '<tr class="hover:bg-slate-50/80 transition-colors">';
            echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . $u['id'] . '</td>';
            echo '<td class="px-6 py-4 font-medium text-slate-800">';
            echo '<div class="flex items-center gap-3">'; // Centrado vertical, alineación izquierda
            echo '<div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs">' . strtoupper(substr($u['nombre'], 0, 1)) . '</div>';
            echo htmlspecialchars($u['nombre']);
            echo '</div>';
            echo '</td>';
            echo '<td class="px-6 py-4 text-sm text-slate-500 text-center">' . htmlspecialchars($u['email']) . '</td>';
            echo '<td class="px-6 py-4 text-center"><span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100 inline-block">' . $u['rol'] . '</span></td>';
            echo '<td class="px-6 py-4 text-center space-x-2">';
            echo '<div class="flex items-center justify-center gap-2">';
            echo '<a href="index.php?view=editar_usuario&id=' . $u['id'] . '" class="text-slate-400 hover:text-blue-600 transition-colors" title="Editar Usuario"><i class="ri-edit-line text-lg"></i></a>';

            if ($u['id'] != $GLOBALS['usuario_id']) {
                echo '<form method="POST" action="index.php?view=usuarios" onsubmit="return confirm(\'¿Estás seguro de eliminar a este usuario? Esta acción no se puede deshacer.\');" class="inline">';
                echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
                echo '<input type="hidden" name="accion" value="eliminar_usuario">';
                echo '<input type="hidden" name="usuario_id" value="' . $u['id'] . '">';
                echo '<button type="submit" class="text-slate-400 hover:text-red-600 transition-colors" title="Eliminar Usuario"><i class="ri-delete-bin-line text-lg"></i></button>';
                echo '</form>';
            }
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
    }

    // 5. Listados de RRHH (Ingresos/Salidas)
    elseif (!empty($mostrar_listado_rrhh)) {
        $filtro_tipo = '';
        $titulo_seccion = 'Historial General de Movimientos';
        $icono_seccion = 'ri-file-list-3-line';

        // Detectar tipo de vista para filtrar
        if (isset($_GET['view'])) {
            if ($_GET['view'] === 'ingreso') {
                $filtro_tipo = 'Ingreso';
                $titulo_seccion = 'Historial de Altas de Personal';
                $icono_seccion = 'ri-user-add-line';
            } elseif ($_GET['view'] === 'salida') {
                $filtro_tipo = 'Salida';
                $titulo_seccion = 'Historial de Bajas de Personal';
                $icono_seccion = 'ri-user-unfollow-line';
            }
        }

        echo '<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">';
        echo '<h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2"><i class="' . $icono_seccion . ' text-pink-600"></i> ' . $titulo_seccion . '</h2>';

        echo '</div>';

        // Filtros visuales
        echo '<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">';
        echo '<div class="grid grid-cols-1 md:grid-cols-4 gap-4">';

        // Buscador
        echo '<div class="md:col-span-2 relative">';
        echo '<i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>';
        echo '<input type="text" id="busquedaRRHH" onkeyup="filtrarTablaRRHH()" placeholder="Buscar por nombre, cargo o cédula..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none transition-all">';
        echo '</div>';

        // Filtro Tipo
        echo '<div>';
        echo '<select id="filtroTipoRRHH" onchange="filtrarTablaRRHH()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none">';
        echo '<option value="">Todos los Tipos</option>';
        echo '<option value="Ingreso" ' . ($filtro_tipo === 'Ingreso' ? 'selected' : '') . '>Ingresos</option>';
        echo '<option value="Salida" ' . ($filtro_tipo === 'Salida' ? 'selected' : '') . '>Salidas</option>';
        echo '</select>';
        echo '</div>';

        // Botón Exportar
        echo '<div>';
        echo '<button onclick="exportarExcelListado()" class="w-full px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-colors flex items-center justify-center gap-2"><i class="ri-file-excel-2-line"></i> Exportar</button>';
        echo '</div>';

        echo '</div>'; // grid
        echo '</div>'; // filtros
    
        // Tabla
        echo '<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">';
        echo '<div class="overflow-x-auto">';
        echo '<table class="w-full text-left border-collapse" id="tablaRRHH">';
        echo '<thead class="bg-slate-50/50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-wider">';
        echo '<tr>';
        echo '<th class="px-6 py-4 text-center">ID</th>';
        echo '<th class="px-6 py-4 text-center">Tipo</th>';
        echo '<th class="px-6 py-4 text-center">Colaborador</th>';
        echo '<th class="px-6 py-4 text-center">Cargo / Zona</th>';
        echo '<th class="px-6 py-4 text-center">Fecha Efectiva</th>';
        echo '<th class="px-6 py-4 text-center">Estado</th>';
        echo '<th class="px-6 py-4 text-center">Acciones</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="divide-y divide-slate-100">';

        if (isset($GLOBALS['formularios']) && !empty($GLOBALS['formularios'])) {
            foreach ($GLOBALS['formularios'] as $f) {
                // Previsualización de datos
                $tipo_class = $f['tipo'] === 'Ingreso' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200';
                $icono_tipo = $f['tipo'] === 'Ingreso' ? 'ri-arrow-right-up-line' : 'ri-arrow-left-down-line';

                echo '<tr class="hover:bg-slate-50/80 transition-colors group fila-rrhh cursor-pointer" onclick=\'verDetallesRRHH(' . json_encode($f) . ')\' data-tipo="' . $f['tipo'] . '">';
                echo '<td class="px-6 py-4 text-sm font-medium text-slate-600 text-center">#' . str_pad($f['id'], 4, '0', STR_PAD_LEFT) . '</td>';

                echo '<td class="px-6 py-4 text-center">';
                echo '<span class="px-3 py-1 rounded-full text-xs font-bold border flex items-center justify-center gap-1 w-fit mx-auto ' . $tipo_class . '"><i class="' . $icono_tipo . '"></i> ' . $f['tipo'] . '</span>';
                echo '</td>';

                echo '<td class="px-6 py-4 text-left">';
                echo '<div class="font-semibold text-slate-800">' . htmlspecialchars($f['nombre_colaborador']) . '</div>';
                echo '<div class="text-xs text-slate-400">' . htmlspecialchars($f['cedula_telefono'] ?? '') . '</div>';
                echo '</td>';

                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center">' . htmlspecialchars($f['cargo_zona']) . '</td>';

                echo '<td class="px-6 py-4 text-sm text-slate-600 text-center"><span class="flex items-center justify-center gap-2"><i class="ri-calendar-line text-slate-400"></i> ' . date('d/m/Y', strtotime($f['fecha_efectiva'])) . '</span></td>';

                // Estado Dinámico desde Ticket
                $status_ticket = 'Procesado';
                $status_class = 'text-slate-500 bg-slate-100 ring-slate-500/10 ring-1';

                if (isset($GLOBALS['pdo'])) {
                    // Reconstruir título del ticket para búsqueda
                    $titulo_t = ($f['tipo'] == 'Ingreso') ? "Nuevo Ingreso: " . $f['nombre_colaborador'] : "Baja de Personal: " . $f['nombre_colaborador'];
                    $stmt_st = $GLOBALS['pdo']->prepare("SELECT estado FROM tickets WHERE titulo = ? ORDER BY id DESC LIMIT 1");
                    $stmt_st->execute([$titulo_t]);
                    $est = $stmt_st->fetchColumn();

                    if ($est) {
                        if ($est === 'Completo') {
                            $status_ticket = 'Terminado';
                            $status_class = 'text-emerald-700 bg-emerald-50 ring-emerald-600/20 ring-1';
                        } elseif ($est === 'Asignado') {
                            $status_ticket = 'En Proceso';
                            $status_class = 'text-blue-700 bg-blue-50 ring-blue-600/20 ring-1';
                        } elseif ($est === 'Pendiente') {
                            $status_ticket = 'Pendiente';
                            $status_class = 'text-amber-700 bg-amber-50 ring-amber-600/20 ring-1';
                        }
                    }
                }

                echo '<td class="px-6 py-4 text-center">';
                echo '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-inset ' . $status_class . '">' . $status_ticket . '</span>';
                echo '</td>';

                echo '<td class="px-6 py-4 text-center">';
                echo '<div class="flex items-center justify-center gap-2">';

                // Botones de Impresión (Agregados por solicitud)
                if ($f['tipo'] === 'Ingreso') {
                    echo '<a href="imprimir_acta_ingreso.php?id=' . $f['id'] . '" target="_blank" class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-600 hover:text-white transition-all shadow-sm flex items-center justify-center cursor-pointer" title="Ver Acta Informativa (Sin Firma)"><i class="ri-file-info-line"></i></a>';
                } else {
                    echo '<a href="imprimir_acta_salida.php?id=' . $f['id'] . '" target="_blank" class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-600 hover:text-white transition-all shadow-sm flex items-center justify-center cursor-pointer" title="Ver Acta Informativa (Sin Firma)"><i class="ri-file-info-line"></i></a>';
                }

                echo '<button class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center cursor-pointer" title="Ver Detalles"><i class="ri-eye-line"></i></button>';

                // Botón Editar (Condicional: Bloquear si ya está avanzado)
                // Ocultar completamente para Gerencia (Solo Visualización)
                if ($rol_usuario !== 'Gerencia') {
                    $bloquear_edicion = ($status_ticket === 'Terminado' || $status_ticket === 'En Proceso');

                    if ($bloquear_edicion) {
                        echo '<button onclick="event.stopPropagation(); mostrarAlerta(\'Acción no permitida\', \'El ticket asociado ya está en estado: <b>' . $status_ticket . '</b>.<br>Solo se pueden editar registros Pendientes.\')" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed flex items-center justify-center" title="Edición Bloqueada"><i class="ri-lock-line"></i></button>';
                    } else {
                        // Permitir editar (enlace)
                        // Nota: Se usa event.stopPropagation() para evitar disparar el onclick de la fila si fuera necesario, aunque el enlace navega.
                        echo '<a href="index.php?view=editar_rrhh&id=' . $f['id'] . '" onclick="event.stopPropagation()" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm flex items-center justify-center cursor-pointer" title="Editar"><i class="ri-edit-line"></i></a>';
                    }
                }
                echo '</div>';
                echo '</td>';

                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">No hay registros encontrados.</td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // overflow
        echo '</div>'; // card tabla
    
        // Modal Detalle RRHH --> (Se inyecta al final del body o aquí mismo)
        echo '
        <!-- Modal Alerta Personalizada -->
<div id="customAlertModal" class="fixed inset-0 z-[60] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="backdrop-filter: blur(4px);">
    <div class="fixed inset-0 bg-slate-900/40 transition-opacity" onclick="cerrarAlerta()"></div>
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-slate-100">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-50 sm:mx-0 sm:h-10 sm:w-10 ring-1 ring-amber-100">
                        <i class="ri-lock-2-line text-amber-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-semibold leading-6 text-slate-800" id="alertaTitulo">Atención</h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500" id="alertaMensaje">Mensaje de alerta.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                <button type="button" onclick="cerrarAlerta()" class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto transition-all">Entendido</button>
            </div>
        </div>
    </div>
</div>

<div id="modalRRHH" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4 md:items-start md:pt-32">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col transform transition-all scale-95 opacity-0 duration-300" id="modalRRHHContent">
                <!-- Header -->
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2" id="modalRRHHTitulo">
                        Detalles del Movimiento
                    </h3>
                    <button onclick="cerrarModalRRHH()" class="w-8 h-8 rounded-full hover:bg-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6 overflow-y-auto" id="modalRRHHBody">
                    <!-- Dynamic Content -->
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end">
                    <button onclick="cerrarModalRRHH()" class="px-5 py-2.5 bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition-all font-medium shadow-lg shadow-slate-800/20">Cerrar</button>
                </div>
            </div>
        </div>

        <script>
            function filtrarTablaRRHH() {
                const busqueda = document.getElementById("busquedaRRHH").value.toLowerCase();
                const tipo = document.getElementById("filtroTipoRRHH").value;
                const filas = document.querySelectorAll(".fila-rrhh");
                
                filas.forEach(fila => {
                    const texto = fila.innerText.toLowerCase();
                    const tipoFila = fila.getAttribute("data-tipo");
                    
                    const coincideTexto = texto.includes(busqueda);
                    const coincideTipo = tipo === "" || tipoFila === tipo;
                    
                    if (coincideTexto && coincideTipo) {
                        fila.style.display = "";
                    } else {
                        fila.style.display = "none";
                    }
                });
            }

            function mostrarAlerta(titulo, mensaje) {
                document.getElementById("alertaTitulo").innerText = titulo;
                document.getElementById("alertaMensaje").innerHTML = mensaje;
                document.getElementById("customAlertModal").classList.remove("hidden");
            }
            function cerrarAlerta() {
                document.getElementById("customAlertModal").classList.add("hidden");
            }

            function verDetallesRRHH(data) {
                const modal = document.getElementById("modalRRHH");
                const content = document.getElementById("modalRRHHContent");
                const body = document.getElementById("modalRRHHBody");
                const titulo = document.getElementById("modalRRHHTitulo");

                // Set Title and Color
                const isIngreso = data.tipo === "Ingreso";
                const iconClass = isIngreso ? "ri-arrow-right-up-line" : "ri-arrow-left-down-line";
                const colorClass = isIngreso ? "text-emerald-600" : "text-rose-600";
                
                titulo.innerHTML = `<i class="${iconClass} ${colorClass}"></i> <span>Detalle de ${data.tipo}</span> <span class="text-sm font-normal text-slate-400 ml-2">#${String(data.id).padStart(4,"0")}</span>`;

                // Helper to render fields safely
                const renderField = (label, value, detail = null) => {
                    const displayValue = value || "N/A";
                    const isNegative = ["NO", "NO REQUERIDO", "NO APLICA", "N/A", "0"].includes(displayValue);
                    const valClass = isNegative ? "text-slate-400 font-normal" : "text-slate-800 font-semibold";
                    
                    return `
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 transition-colors">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">${label}</p>
                            <p class="text-sm ${valClass}">${displayValue}</p>
                            ${detail ? `<div class="mt-2 pt-2 border-t border-slate-200/50 text-xs text-slate-600 pl-2 border-l-2 border-blue-400">${detail}</div>` : ""}
                        </div>
                    `;
                };

                let html = `<div class="flex flex-col gap-6">`;
                
                // Main Info Card
                html += `
                    <div class="bg-gradient-to-br ${isIngreso ? "from-emerald-50 to-teal-50 border-emerald-100" : "from-rose-50 to-pink-50 border-rose-100"} p-5 rounded-2xl border">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div>
                                <label class="text-[10px] font-bold ${isIngreso ? "text-emerald-600" : "text-rose-600"} uppercase tracking-wider block mb-1">Colaborador</label>
                                <p class="font-bold text-slate-800 text-xl">${data.nombre_colaborador}</p>
                                <div class="flex items-center gap-2 mt-1 opacity-75">
                                    <i class="ri-id-card-line text-xs"></i>
                                    <span class="text-xs font-medium">${data.cedula_telefono || "Sin ID"}</span>
                                </div>
                            </div>
                            <div class="md:text-right">
                                <label class="text-[10px] font-bold ${isIngreso ? "text-emerald-600" : "text-rose-600"} uppercase tracking-wider block mb-1">Cargo / Fecha</label>
                                <p class="font-bold text-slate-800">${data.cargo_zona}</p>
                                <div class="flex items-center gap-1 mt-1 font-mono text-xs text-slate-500 md:justify-end">
                                    <i class="ri-calendar-event-line"></i>
                                    <span>${data.fecha_efectiva ? new Date(data.fecha_efectiva).toLocaleDateString() : "N/A"}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Grids de detalles
                html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-3">`;
                html += renderField("Licencias", data.disponibilidad_licencias, data.detalle_licencias);
                html += renderField("Correo", data.correo_nuevo, data.direccion_correo);
                html += renderField("Remitente", data.remitente_mostrar, data.detalle_remitente);
                html += renderField("Respaldo", data.respaldo_nube, data.detalle_respaldo);
                html += renderField("Reenvíos", data.reenvios_correo, data.detalle_reenvios);
                html += renderField("Asignación HW", data.asignacion_equipo, data.detalle_asignacion);
                html += renderField("Nube Móvil", data.nube_movil, data.detalle_nube_movil);
                html += renderField("Equipo Usado", data.equipo_usado, data.especificacion_equipo_usado);
                html += `</div>`;

                // Otras indicaciones
                if (data.otras_indicaciones) {
                    html += `
                        <div class="mt-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Notas Adicionales</label>
                            <div class="p-4 bg-yellow-50 rounded-xl text-slate-700 italic border border-yellow-100 text-sm relative">
                                <i class="ri-sticky-note-line absolute top-2 right-2 text-yellow-300 text-xl"></i>
                                "${data.otras_indicaciones}"
                            </div>
                        </div>
                    `;
                }
                
                html += `</div>`;
                body.innerHTML = html;

                // Show
                modal.classList.remove("hidden");
                requestAnimationFrame(() => {
                    content.classList.remove("scale-95", "opacity-0");
                    content.classList.add("scale-100", "opacity-100");
                });
            }

            function cerrarModalRRHH() {
                const modal = document.getElementById("modalRRHH");
                const content = document.getElementById("modalRRHHContent");
                
                content.classList.remove("scale-100", "opacity-100");
                content.classList.add("scale-95", "opacity-0");
                
                setTimeout(() => {
                    modal.classList.add("hidden");
                }, 300);
            }

            document.addEventListener("DOMContentLoaded", filtrarTablaRRHH);
        </script>';
    }
    ?>
</div>