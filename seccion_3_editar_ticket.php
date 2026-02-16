<?php
/**
 * seccion_3_editar_ticket.php - Vista de Gestión de Ticket
 * Diseño Optimizado: Información arriba, Gestión abajo (Sticky).
 */

$t = $ticket_editar;
$id = $t['id'];
$es_tecnico = ($_SESSION['usuario_rol'] === 'Tecnico');
$es_admin = ($_SESSION['usuario_rol'] === 'Admin' || $_SESSION['usuario_rol'] === 'SuperAdmin');
$es_creador_real = (isset($t['creador_id']) && isset($_SESSION['usuario_id']) && (string) $t['creador_id'] === (string) $_SESSION['usuario_id']);
$es_usuario_regular = ($_SESSION['usuario_rol'] === 'Usuario');
// Admins y Técnicos pueden editar siempre. Usuarios solo si es suyo y está Pendiente.
$es_pendiente = ($t['estado'] === 'Pendiente');
$puede_editar_contenido = ($es_admin || $es_tecnico || ($es_creador_real && $es_pendiente));

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Configuración de colores dinámica
$estado_colors = [
    'Pendiente' => 'bg-blue-100 text-blue-800 border-blue-200',
    'En Atención' => 'bg-purple-100 text-purple-800 border-purple-200',
    'Resuelto' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'Cerrado' => 'bg-slate-100 text-slate-600 border-slate-200'
];
$color_estado = $estado_colors[$t['estado']] ?? 'bg-gray-100 text-gray-800';

$prioridad_colors = [
    'Baja' => 'text-emerald-600 bg-emerald-50',
    'Media' => 'text-blue-600 bg-blue-50',
    'Alta' => 'text-orange-600 bg-orange-50',
    'Critica' => 'text-red-600 bg-red-50'
];
$color_prioridad = $prioridad_colors[$t['prioridad']] ?? 'text-slate-600 bg-slate-50';
?>

<div class="max-w-7xl mx-auto p-4 md:p-8">
    <!-- Header de Navegación -->
    <div class="mb-8 flex items-center justify-between">
        <?php
        $link_volver = $es_usuario_regular ? 'index.php?view=mis_tickets' : 'index.php?view=seguimiento';
        ?>
        <a href="<?php echo $link_volver; ?>"
            class="group flex items-center gap-2 text-slate-500 hover:text-blue-600 transition-colors font-medium">
            <div
                class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                <i class="ri-arrow-left-line"></i>
            </div>
            Volver al listado
        </a>
        <div class="text-right">
            <span class="block text-xs uppercase tracking-wider text-slate-400 font-bold">Ticket ID</span>
            <span
                class="text-2xl font-mono font-bold text-slate-700">#<?php echo str_pad($id, 4, '0', STR_PAD_LEFT); ?></span>
        </div>
    </div>

    <form action="index.php?view=editar_ticket&id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data"
        class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Token CSRF de seguridad -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

        <!-- Columna Principal (Izquierda) - Ocupa 8/12 columnas -->
        <div class="lg:col-span-8 flex flex-col gap-6">

            <!-- Línea de Tiempo Interactiva -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-6">Progreso del Ticket</h3>
                
                <?php
                // Mapeo de estados a pasos logicos (1-4)
                $steps = [
                    1 => ['label' => 'Pendiente', 'icon' => 'ri-time-line', 'color' => 'bg-yellow-500 ring-yellow-200'],
                    2 => ['label' => 'Asignado', 'icon' => 'ri-user-settings-line', 'color' => 'bg-blue-500 ring-blue-200'],
                    3 => ['label' => 'En Atención', 'icon' => 'ri-tools-fill', 'color' => 'bg-indigo-500 ring-indigo-200'],
                    4 => ['label' => 'Completo', 'icon' => 'ri-checkbox-circle-line', 'color' => 'bg-emerald-500 ring-emerald-200']
                ];

                // Determinar paso actual
                $current_step = match($t['estado']) {
                    'Pendiente' => 1,
                    'Asignado' => 2,
                    'En Atención' => 3,
                    'Resuelto', 'Completo', 'Cerrado' => 4,
                    default => 1
                };
                ?>

                <div class="relative flex items-center justify-between w-full">
                    <!-- Línea de fondo -->
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-slate-100 -z-10 rounded-full"></div>
                    <!-- Línea de progreso -->
                    <div class="absolute top-1/2 left-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500 -z-10 rounded-full transition-all duration-1000 ease-out" 
                         style="width: <?php echo (($current_step - 1) / 3) * 100; ?>%"></div>

                    <?php foreach ($steps as $step_num => $step_data): 
                        $is_active = $step_num === $current_step;
                        $is_completed = $step_num < $current_step;
                        $is_future = $step_num > $current_step;
                        
                        $circle_class = '';
                        $icon_class = '';
                        $text_class = '';
                        
                        if ($is_completed) {
                            $circle_class = 'bg-slate-800 text-white shadow-md cursor-pointer hover:scale-110';
                            $icon_class = 'ri-check-line';
                            $text_class = 'text-slate-800 font-bold';
                        } elseif ($is_active) {
                            $circle_class = $step_data['color'] . ' text-white ring-4 shadow-lg scale-110 animate-pulse';
                            $icon_class = $step_data['icon'];
                            $text_class = 'text-indigo-600 font-extrabold';
                        } else { // Future
                            $circle_class = 'bg-white border-2 border-slate-200 text-slate-300';
                            $icon_class = $step_data['icon'];
                            $text_class = 'text-slate-300 font-medium';
                        }
                    ?>
                        <div class="flex flex-col items-center gap-3 relative group transition-all duration-300">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center transition-all duration-300 z-10 <?php echo $circle_class; ?>">
                                <i class="<?php echo $icon_class; ?> text-lg md:text-xl"></i>
                            </div>
                            <span class="absolute -bottom-8 text-[10px] md:text-xs uppercase tracking-wider text-center whitespace-nowrap transition-colors <?php echo $text_class; ?>">
                                <?php echo $step_data['label']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-4"></div> <!-- Spacer for text labels -->
            </div>

            <!-- Tarjeta del Ticket -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Encabezado del Ticket -->
                <div class="p-6 md:p-8 border-b border-slate-100 bg-slate-50/30">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <div class="flex gap-3">
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border <?php echo $color_estado; ?>">
                                <?php echo $t['estado']; ?>
                            </span>
                            <span
                                class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-transparent <?php echo $color_prioridad; ?>">
                                <i class="ri-flag-line mr-1"></i> <?php echo $t['prioridad']; ?>
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
                            <i class="ri-calendar-line"></i>
                            <?php echo date('d M, Y h:i A', strtotime($t['fecha_creacion'])); ?>
                        </span>
                    </div>

                    <?php if ($puede_editar_contenido): ?>
                        <input type="text" name="titulo_ticket" value="<?php echo htmlspecialchars($t['titulo']); ?>"
                            class="w-full text-2xl md:text-3xl font-bold text-slate-800 bg-transparent border-b-2 border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all placeholder-slate-300">
                    <?php else: ?>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 leading-tight">
                            <?php echo htmlspecialchars($t['titulo']); ?>
                        </h1>
                    <?php endif; ?>
                </div>

                <!-- Cuerpo del Ticket -->
                <div class="p-6 md:p-8">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="ri-file-text-line"></i> Descripción del Problema
                    </h3>
                    <?php
                    // Limpiar separadores visuales antiguos si existen
                    $descripcion_limpia = preg_replace('/={3,}/', '', $t['descripcion']);
                    ?>
                    <?php if ($puede_editar_contenido): ?>
                        <div class="relative">
                            <textarea name="descripcion_ticket" rows="12"
                                class="w-full p-5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-slate-700 font-mono text-sm leading-relaxed resize-y bg-white shadow-sm transition-all placeholder-slate-300 custom-scrollbar"><?php echo htmlspecialchars($descripcion_limpia); ?></textarea>
                            <div class="absolute bottom-3 right-3 pointer-events-none opacity-50">
                                <i class="ri-draggable text-slate-300"></i>
                            </div>
                        </div>
                    <?php else: ?>
                        <div
                            class="prose max-w-none text-slate-700 font-mono text-sm leading-relaxed whitespace-pre-wrap p-5 bg-slate-50/50 rounded-xl border border-slate-100">
                            <?php echo htmlspecialchars(trim($descripcion_limpia)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Zona de Resolución (Solo Técnicos/Admin) -->
            <?php if ($es_tecnico || $es_admin): ?>
                <div
                    class="bg-gradient-to-br from-white to-blue-50 rounded-2xl shadow-sm border border-blue-100 p-6 md:p-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="ri-customer-service-2-fill text-9xl text-blue-600"></i>
                    </div>

                    <div class="relative z-10">
                        <h3 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="ri-tools-fill"></i>
                            </span>
                            Zona de Resolución
                        </h3>

                        <?php if (!empty($t['resolucion'])): ?>
                            <div class="mb-4 bg-white/60 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                                <h4 class="text-xs font-bold text-blue-700 uppercase mb-2">Historial de Resolución</h4>
                                <div class="text-sm text-slate-700 whitespace-pre-wrap font-mono">
                                    <?php 
                                    $res_raw = $t['resolucion'];
                                    // Parsear formato: "📄 [FECHA] TEXTO" o "[FECHA] TEXTO"
                                    if ($res_raw && preg_match('/(?:📄\s*)?\[(.*?)\]\s*(.*)/s', $res_raw, $matches)) {
                                        $res_fecha = $matches[1];
                                        $res_msg = $matches[2];

                                        // Traducción español (Misma lógica que en seguimiento)
                                        if (strtolower(trim($res_msg)) === 'ready') {
                                            $res_msg = 'Listo';
                                        }

                                        echo '<div class="flex flex-col gap-1">';
                                        echo '<div class="text-sm text-slate-700 font-medium whitespace-pre-wrap">' . htmlspecialchars($res_msg) . '</div>';
                                        echo '<div class="text-xs text-slate-400 flex items-center gap-1"><i class="ri-calendar-check-line"></i> ' . htmlspecialchars($res_fecha) . '</div>';
                                        echo '</div>';
                                    } else {
                                        // Fallback si no coincide el formato
                                        echo htmlspecialchars($res_raw);
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div
                            class="bg-white/80 backdrop-blur-sm rounded-xl p-1 border border-blue-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                            <textarea name="comentarios_resolucion" rows="4"
                                placeholder="Escribe aquí los detalles técnicos de la solución o avances..."
                                class="w-full p-4 bg-transparent border-none outline-none text-slate-700 placeholder-slate-400 resize-none"></textarea>
                            <div
                                class="px-4 py-2 border-t border-blue-100 flex justify-between items-center bg-blue-50/50 rounded-b-lg">
                                <span class="text-xs text-blue-400 font-medium"> <i class="ri-information-line"></i> Visible
                                    solo para técnicos</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Nueva Sección: Comentarios y Adjuntos (Visible para Todos) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="ri-chat-history-line text-blue-600"></i> Conversación y Actualizaciones
                    </h3>
                    <span class="text-xs font-medium text-slate-500 bg-slate-200 px-2 py-1 rounded-md">
                        <?= isset($comentarios) ? count($comentarios) : 0 ?> mensajes
                    </span>
                </div>
                
                <div class="p-6 md:p-8 bg-slate-50/30">
                    <!-- Timeline de Comentarios -->
                    <div class="space-y-6 mb-8">
                        <?php if (empty($comentarios)): ?>
                            <div class="text-center py-8 text-slate-400">
                                <i class="ri-chat-1-line text-4xl mb-2 opacity-30"></i>
                                <p class="text-sm">No hay comentarios aún. ¡Inicia la conversación!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comentarios as $c): 
                                $es_mio = ($c['usuario_id'] == $_SESSION['usuario_id']);
                                $es_tecnico_comentario = ($c['rol_usuario'] == 'Tecnico' || $c['rol_usuario'] == 'Admin');
                                $bg_class = $es_mio ? 'bg-blue-50 border-blue-100' : 'bg-white border-slate-200';
                                $align_class = $es_mio ? 'ml-auto' : 'mr-auto';
                                $max_width = 'max-w-3xl';
                            ?>
                                <div class="flex gap-4 <?= $es_mio ? 'flex-row-reverse' : '' ?>">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shadow-sm <?= $es_tecnico_comentario ? 'bg-indigo-600' : 'bg-slate-500' ?>">
                                            <?= strtoupper(substr($c['nombre_usuario'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 <?= $max_width ?>">
                                        <div class="relative p-4 rounded-2xl border <?= $bg_class ?> shadow-sm">
                                            <div class="flex items-center justify-between gap-4 mb-2">
                                                <span class="text-sm font-bold text-slate-800">
                                                    <?= htmlspecialchars($c['nombre_usuario']) ?>
                                                    <?php if ($es_tecnico_comentario): ?>
                                                        <span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded uppercase tracking-wider">Técnico</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="text-xs text-slate-400" title="<?= $c['fecha_creacion'] ?>">
                                                    <?= date('d M, h:i A', strtotime($c['fecha_creacion'])) ?>
                                                </span>
                                            </div>
                                            <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($c['comentario']) ?></div>
                                            
                                            <!-- Adjuntos del Comentario -->
                                            <?php if (!empty($c['adjuntos'])): ?>
                                                <div class="mt-3 pt-3 border-t border-slate-100/50 flex flex-wrap gap-2">
                                                    <?php foreach ($c['adjuntos'] as $adj): ?>
                                                        <a href="<?= htmlspecialchars($adj['ruta_archivo']) ?>" target="_blank" class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-600 hover:text-blue-600 hover:border-blue-300 transition-colors">
                                                            <i class="ri-attachment-line"></i> <?= htmlspecialchars($adj['nombre_archivo']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Formulario de Nuevo Comentario -->
                    <?php if ($t['estado'] !== 'Completo' && $t['estado'] !== 'Cerrado'): ?>
                        <div class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm p-1 focus-within:ring-2 focus-within:ring-blue-500/50 transition-all">
                            <textarea name="nuevo_comentario" rows="3" class="w-full p-4 bg-transparent border-none outline-none text-slate-700 placeholder-slate-400 resize-none text-sm" placeholder="Escribe un comentario o actualización..."></textarea>
                            
                            <!-- Barra de Herramientas del Editor -->
                            <div class="px-3 py-2 border-t border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-b-lg">
                                <div class="flex items-center gap-2">
                                    <label class="cursor-pointer group flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-200/50 transition-colors" title="Adjuntar archivo">
                                        <i class="ri-attachment-2 text-slate-500 group-hover:text-blue-600"></i>
                                        <input type="file" name="archivo_adjunto" class="hidden">
                                    </label>
                                    <span id="archivo-nombre" class="text-xs text-slate-500 truncate max-w-[150px]"></span>
                                </div>
                                <button type="submit" name="accion_comentar" value="1" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center gap-2">
                                    <i class="ri-send-plane-fill"></i> Enviar
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-center p-4 bg-slate-100 rounded-xl text-slate-400 text-sm italic gap-2 border border-slate-200 border-dashed">
                            <i class="ri-lock-line"></i> Este ticket está cerrado, no se pueden agregar más comentarios.
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Botón de Actualizar (Solo para Admin y Técnicos) -->
            <!-- Botón movido al panel lateral -->
            <?php if (!$puede_editar_contenido): ?>
                <!-- Mensaje informativo para usuarios en modo lectura -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <i class="ri-information-line text-xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-blue-900 text-sm">Modo Solo Lectura</p>
                        <p class="text-blue-700 text-xs mt-1">Estás visualizando este ticket. Solo administradores y
                            técnicos pueden realizar cambios.</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Columna Lateral (Derecha) - Ocupa 4/12 columnas -->
        <div class="lg:col-span-4 flex flex-col gap-6">

            <!-- 1. Información del Solicitante (Ahora ARRIBA) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h4
                    class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">
                    Información del Solicitante</h4>

                <div class="flex items-center gap-4 mb-6">
                    <div class="relative">
                        <div
                            class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-2xl shadow-lg shadow-blue-600/20">
                            <?php echo strtoupper(substr($t['creador_nombre'] ?? 'Unknown', 0, 1)); ?>
                        </div>
                        <div
                            class="absolute -bottom-1 -right-1 w-5 h-5 bg-white rounded-full flex items-center justify-center">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full border border-white">
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-base leading-tight">
                            <?php echo htmlspecialchars($t['creador_nombre'] ?? 'Usuario Desconocido'); ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Colaborador</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Grid de Categoría y Prioridad (Info Solicitante) -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Categoría (Solo Lectura) -->
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Categoría</label>
                            <?php if ($puede_editar_contenido): ?>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-500">
                                        <i class="ri-folder-line text-lg"></i>
                                    </div>
                                    <select name="categoria_id" class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none appearance-none cursor-pointer hover:bg-white hover:shadow-sm">
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $t['categoria_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                        <i class="ri-folder-line text-lg"></i>
                                    </div>
                                    <span
                                        class="font-bold text-slate-700 truncate"><?php echo htmlspecialchars($t['categoria_nombre'] ?? 'General'); ?></span>
                                    <input type="hidden" name="categoria_id" value="<?php echo htmlspecialchars($t['categoria_id']); ?>">
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Prioridad (Ahora Arriba) -->
                        <!-- Prioridad (Solo Lectura) -->
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Prioridad</label>
                            <?php if ($puede_editar_contenido): ?>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                        <i class="ri-flag-fill text-lg"></i>
                                    </div>
                                    <select name="prioridad" class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all outline-none appearance-none cursor-pointer hover:bg-white hover:shadow-sm">
                                        <option value="Baja" <?php echo $t['prioridad'] == 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                        <option value="Media" <?php echo $t['prioridad'] == 'Media' ? 'selected' : ''; ?>>Media</option>
                                        <option value="Alta" <?php echo $t['prioridad'] == 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                        <option value="Critica" <?php echo $t['prioridad'] == 'Critica' ? 'selected' : ''; ?>>Crítica</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="ri-arrow-down-s-line"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php
                                $prio_color_class = match ($t['prioridad']) {
                                    'Baja' => 'bg-emerald-100 text-emerald-600',
                                    'Media' => 'bg-amber-100 text-amber-600',
                                    'Alta' => 'bg-orange-100 text-orange-600',
                                    'Critica' => 'bg-red-100 text-red-600',
                                    default => 'bg-slate-100 text-slate-600'
                                };
                                ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl">
                                    <div
                                        class="w-10 h-10 rounded-lg <?php echo $prio_color_class; ?> flex items-center justify-center shrink-0">
                                        <i class="ri-flag-fill text-lg"></i>
                                    </div>
                                    <span
                                        class="font-bold text-slate-700"><?php echo htmlspecialchars($t['prioridad']); ?></span>
                                    <input type="hidden" name="prioridad" value="<?php echo htmlspecialchars($t['prioridad']); ?>">
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <div
                        class="flex items-center p-3 rounded-xl bg-slate-50 border border-slate-100 transition-colors hover:bg-white hover:shadow-sm">
                        <div
                            class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-slate-500 uppercase font-bold">Técnico
                                Asignado</p>
                            <?php
                            $nombre_tecnico_asignado = 'Sin Asignar';
                            $avatar_tecnico = '?';

                            if ($t['tecnico_id']) {
                                if ($t['tecnico_id'] == $_SESSION['usuario_id']) {
                                    $nombre_tecnico_asignado = 'Tú (Asignado)';
                                    $avatar_tecnico = strtoupper(substr($_SESSION['usuario_nombre'] ?? 'T', 0, 1));
                                } else {
                                    // Buscar el nombre real del técnico
                                    foreach ($usuarios as $u) {
                                        if ($u['id'] == $t['tecnico_id']) {
                                            $nombre_tecnico_asignado = $u['nombre'];
                                            $avatar_tecnico = strtoupper(substr($u['nombre'], 0, 1));
                                            break;
                                        }
                                    }
                                }
                            }
                            ?>
                            <div class="flex items-center gap-2 mt-1">
                                <?php if ($t['tecnico_id']): ?>
                                    <div
                                        class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center text-xs font-bold">
                                        <?php echo $avatar_tecnico; ?>
                                    </div>
                                <?php endif; ?>
                                <p class="text-sm font-semibold text-slate-700">
                                    <?php echo htmlspecialchars($nombre_tecnico_asignado); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Panel de Gestión (Visible solo para Admin y Técnico) -->
            <?php if ($puede_editar_contenido): ?>
                <div
                    class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200 p-6 sticky top-6 z-20">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Gestionar Ticket</h3>
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    </div>

                    <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">

                    <div class="space-y-5">
                        <!-- Selector de Estado -->
                        <?php if ($es_admin || $es_tecnico): ?>
                                    <!-- Opción: Pendiente -->
                                    <label class="cursor-pointer relative block <?php echo $t['estado'] === 'Completo' ? 'opacity-50 pointer-events-none grayscale' : ''; ?>">
                                        <input type="radio" name="nuevo_estado" value="Pendiente" class="peer sr-only"
                                            <?= $t['estado'] == 'Pendiente' ? 'checked' : '' ?> <?php echo $t['estado'] === 'Completo' ? 'disabled' : ''; ?>>
                                        <div
                                            class="w-full flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all hover:shadow-md h-24 text-center bg-white border-slate-100 text-slate-500 hover:border-yellow-200 hover:text-yellow-600 peer-checked:bg-yellow-50 peer-checked:border-yellow-500 peer-checked:text-yellow-700">
                                            <i class="ri-time-line text-2xl mb-1"></i>
                                            <span class="font-bold text-xs">Pendiente</span>
                                        </div>
                                        <i
                                            class="ri-check-circle-fill absolute top-2 right-2 text-yellow-500 text-lg opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></i>
                                    </label>

                                    <!-- Opción: Asignado -->
                                    <label class="cursor-pointer relative block <?php echo $t['estado'] === 'Completo' ? 'opacity-50 pointer-events-none grayscale' : ''; ?>">
                                        <input type="radio" name="nuevo_estado" value="Asignado" class="peer sr-only"
                                            <?= $t['estado'] == 'Asignado' ? 'checked' : '' ?> <?php echo $t['estado'] === 'Completo' ? 'disabled' : ''; ?>>
                                        <div
                                            class="w-full flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all hover:shadow-md h-24 text-center bg-white border-slate-100 text-slate-500 hover:border-blue-200 hover:text-blue-600 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700">
                                            <i class="ri-user-settings-line text-2xl mb-1"></i>
                                            <span class="font-bold text-xs">Asignado</span>
                                        </div>
                                        <i
                                            class="ri-check-circle-fill absolute top-2 right-2 text-blue-500 text-lg opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></i>
                                    </label>

                                    <!-- Opción: Completo -->
                                    <label class="cursor-pointer relative block">
                                        <input type="radio" name="nuevo_estado" value="Completo" class="peer sr-only"
                                            <?= $t['estado'] == 'Completo' ? 'checked' : '' ?> <?php echo $t['estado'] === 'Completo' ? 'disabled' : ''; ?>>
                                        <div
                                            class="w-full flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all hover:shadow-md h-24 text-center bg-white border-slate-100 text-slate-500 hover:border-emerald-200 hover:text-emerald-600 peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700">
                                            <i class="ri-checkbox-circle-line text-2xl mb-1"></i>
                                            <span class="font-bold text-xs">Completo</span>
                                        </div>
                                        <i
                                            class="ri-check-circle-fill absolute top-2 right-2 text-emerald-500 text-lg opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></i>
                                    </label>
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="nuevo_estado" value="<?php echo htmlspecialchars($t['estado']); ?>">
                            <?php endif; ?>

                            <!-- Prioridad movida arriba -->

                            <!-- Botón de Guardar (Movido Aquí) -->
                            <div class="pt-4 border-t border-slate-100">
                                <button type="submit"
                                    class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-3 transform active:scale-95 group">
                                    <i class="ri-save-3-line text-xl group-hover:scale-110 transition-transform"></i>
                                    <?php echo ($t['estado'] == 'Resuelto' || $t['estado'] == 'Cerrado') ? 'Guardar Cambios / Notas' : 'Actualizar Ticket'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Hidden inputs for state and priority if the management panel is not visible -->
                    <input type="hidden" name="nuevo_estado" value="<?php echo htmlspecialchars($t['estado']); ?>">
                    <input type="hidden" name="prioridad" value="<?php echo htmlspecialchars($t['prioridad']); ?>">
                <?php endif; ?>

            </div>
            </div>
    </form>
</div>

<script>
document.querySelector('input[name="archivo_adjunto"]').addEventListener('change', function(e) {
    var fileName = e.target.files[0] ? e.target.files[0].name : '';
    document.getElementById('archivo-nombre').textContent = fileName;
});

// Prevent double submit
document.querySelector('form[action^="index.php?view=editar_ticket"]').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-xl"></i> Procesando...';
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }
});
</script>