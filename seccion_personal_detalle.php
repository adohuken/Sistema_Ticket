<?php
/**
 * seccion_personal_detalle.php - Vista Detallada de Personal
 * Módulo de Gestión de Personal
 */

// Verificar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='p-6'><div class='bg-red-100 text-red-700 p-4 rounded'>ID de personal no especificado.</div></div>";
    return;
}

$personal_id = $_GET['id'];

try {
    // Obtener datos del empleado usando la vista completa
    $stmt = $pdo->prepare("SELECT * FROM vista_personal_completo WHERE id = ?");
    $stmt->execute([$personal_id]);
    $empleado = $stmt->fetch();

    if (!$empleado) {
        echo "<div class='p-6'><div class='bg-red-100 text-red-700 p-4 rounded'>Personal no encontrado.</div></div>";
        return;
    }

    // Obtener historial de cambios
    $stmtHistory = $pdo->prepare("
        SELECT h.*, u.nombre_completo as usuario_nombre 
        FROM personal_historial h 
        LEFT JOIN usuarios u ON h.registrado_por = u.id 
        WHERE h.personal_id = ? 
        ORDER BY h.fecha_registro DESC
    ");
    $stmtHistory->execute([$personal_id]);
    $historial = $stmtHistory->fetchAll();

    // Obtener activos asignados
    // $nombre_completo ya no se usa para buscar activos, se usa el ID
    $stmtActivos = $pdo->prepare("
        SELECT * FROM inventario 
        WHERE condicion = 'Asignado' 
        AND asignado_a = ?
        ORDER BY tipo, marca
    ");
    $stmtActivos->execute([$personal_id]);
    $activos_asignados = $stmtActivos->fetchAll();

} catch (PDOException $e) {
    echo "<div class='p-6'><div class='bg-red-100 text-red-700 p-4 rounded'>Error: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    return;
}

// Configuración de colores para estados
$estado_colors = [
    'Activo' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'Inactivo' => 'bg-orange-100 text-orange-700 border-orange-200',
    'Suspendido' => 'bg-red-100 text-red-700 border-red-200',
    'Retirado' => 'bg-slate-100 text-slate-600 border-slate-200'
];
$estado_class = $estado_colors[$empleado['estado']] ?? $estado_colors['Activo'];
?>

<div class="p-6 ml-10"> <!-- Margen izquierdo compensatorio -->

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="index.php?view=personal" class="hover:text-blue-600 transition-colors">Personal</a>
                <i class="ri-arrow-right-s-line"></i>
                <span>Detalle</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-lg">
                    <?php echo strtoupper(substr($empleado['nombres'], 0, 1) . substr($empleado['apellidos'], 0, 1)); ?>
                </div>
                <?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?>
                <span class="text-sm px-2.5 py-0.5 rounded-full border <?php echo $estado_class; ?>">
                    <?php echo htmlspecialchars($empleado['estado']); ?>
                </span>
            </h2>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?view=personal_editar&id=<?php echo $empleado['id']; ?>"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-lg shadow-blue-600/20 flex items-center gap-2 text-sm">
                <i class="ri-edit-line"></i>
                <span>Editar Información</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Columna Izquierda: Información Principal -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Datos Laborales (Tarjeta Principal) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="ri-briefcase-line text-blue-500"></i> Información Laboral
                    </h3>
                    <span class="text-xs font-mono bg-slate-200 text-slate-600 px-2 py-1 rounded">
                        <?php echo htmlspecialchars($empleado['codigo_empleado'] ?? 'SIN CÓDIGO'); ?>
                    </span>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Empresa</p>
                        <p class="text-base font-medium text-slate-800 flex items-center gap-2">
                            <i class="ri-building-line text-slate-400"></i>
                            <?php echo htmlspecialchars($empleado['empresa_nombre']); ?>
                            <span
                                class="text-xs text-slate-400">(<?php echo htmlspecialchars($empleado['empresa_codigo']); ?>)</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Sucursal</p>
                        <p class="text-base font-medium text-slate-800 flex items-center gap-2">
                            <i class="ri-map-pin-line text-slate-400"></i>
                            <?php echo htmlspecialchars($empleado['sucursal_nombre']); ?>
                        </p>
                        <p class="text-xs text-slate-500 ml-6">
                            <?php echo htmlspecialchars($empleado['sucursal_ciudad'] . ', ' . $empleado['sucursal_pais']); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Cargo / Puesto</p>
                        <p class="text-base font-medium text-slate-800">
                            <?php echo htmlspecialchars($empleado['cargo'] ?? 'No definido'); ?>
                        </p>
                        <p class="text-sm text-slate-500">
                            <?php echo htmlspecialchars($empleado['departamento'] ?? ''); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Fecha de Ingreso
                        </p>
                        <p class="text-base font-medium text-slate-800">
                            <?php echo date('d/m/Y', strtotime($empleado['fecha_ingreso'])); ?>
                        </p>
                        <p class="text-xs text-slate-500 flex items-center gap-1">
                            <i class="ri-time-line"></i>
                            <?php echo $empleado['anos_servicio']; ?> años de servicio
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">Tipo de Contrato
                        </p>
                        <p class="text-sm font-medium text-slate-800 bg-slate-100 inline-block px-2 py-1 rounded">
                            <?php echo htmlspecialchars($empleado['tipo_contrato'] ?? 'N/A'); ?>
                        </p>
                    </div>

                </div>
            </div>

            <!-- Datos Personales y Contacto -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="ri-user-line text-blue-500"></i> Datos Personales y Contacto
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    <!-- Columna 1 -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Documento de Identidad</p>
                            <p class="text-sm font-medium text-slate-800">
                                <?php echo htmlspecialchars($empleado['cedula'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Fecha Nacimiento</p>
                            <p class="text-sm font-medium text-slate-800">
                                <?php echo $empleado['fecha_nacimiento'] ? date('d/m/Y', strtotime($empleado['fecha_nacimiento'])) : 'N/A'; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Estado Civil</p>
                            <p class="text-sm font-medium text-slate-800">
                                <?php echo htmlspecialchars($empleado['estado_civil'] ?? 'N/A'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Columna 2 -->
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Teléfono</p>
                            <div class="flex items-center gap-2">
                                <i class="ri-phone-line text-slate-400"></i>
                                <span
                                    class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($empleado['telefono'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Email</p>
                            <div class="flex items-center gap-2">
                                <i class="ri-mail-line text-slate-400"></i>
                                <a href="mailto:<?php echo $empleado['email']; ?>"
                                    class="text-sm font-medium text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($empleado['email'] ?? 'N/A'); ?>
                                </a>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Dirección</p>
                            <p class="text-sm font-medium text-slate-800 text-balance">
                                <?php echo htmlspecialchars($empleado['direccion'] . ', ' . $empleado['ciudad'] . ', ' . $empleado['pais']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activos Asignados -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="ri-macbook-line text-blue-600"></i> Activos Asignados
                    </h3>
                    <div class="flex items-center gap-3">
                        <a href="index.php?view=generar_acta_entrega&id=<?= $personal_id ?>" target="_blank"
                            class="text-xs bg-white text-blue-600 hover:bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg font-bold transition-all flex items-center gap-1 shadow-sm">
                            <i class="ri-printer-line"></i> Imprimir Acta
                        </a>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-bold">
                            <?= count($activos_asignados) ?> equipos
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($activos_asignados)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="ri-inbox-line text-slate-300 text-2xl"></i>
                            </div>
                            <p class="text-slate-500 text-sm">No hay activos asignados a este colaborador.</p>
                            <a href="index.php?view=inventario"
                                class="mt-3 inline-block text-blue-600 hover:text-blue-700 text-sm font-medium">
                                <i class="ri-add-line"></i> Asignar equipo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($activos_asignados as $activo):
                                $icon_bg = match ($activo['tipo']) {
                                    'Laptop' => 'bg-blue-100 text-blue-600',
                                    'PC' => 'bg-indigo-100 text-indigo-600',
                                    'Movil' => 'bg-purple-100 text-purple-600',
                                    'Monitor' => 'bg-cyan-100 text-cyan-600',
                                    'Silla', 'Escritorio' => 'bg-orange-100 text-orange-600',
                                    default => 'bg-slate-100 text-slate-600'
                                };
                                $icon = match ($activo['tipo']) {
                                    'Laptop' => 'ri-macbook-line',
                                    'PC' => 'ri-computer-line',
                                    'Movil' => 'ri-smartphone-line',
                                    'Monitor' => 'ri-tv-line',
                                    'Silla' => 'ri-armchair-line',
                                    'Teclado' => 'ri-keyboard-line',
                                    'Mouse' => 'ri-mouse-line',
                                    default => 'ri-box-3-line'
                                };
                                ?>
                                <div
                                    class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all">
                                    <div
                                        class="w-12 h-12 rounded-lg <?= $icon_bg ?> flex items-center justify-center text-xl flex-shrink-0">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-800 text-sm truncate">
                                            <?= htmlspecialchars($activo['marca'] . ' ' . $activo['modelo']) ?>
                                        </h4>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs text-slate-500"><?= $activo['tipo'] ?></span>
                                            <span class="text-xs text-slate-300">•</span>
                                            <span
                                                class="text-xs font-mono text-slate-400"><?= htmlspecialchars($activo['serial']) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span
                                            class="text-xs px-2 py-1 rounded-full <?= $activo['estado'] === 'Nuevo' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= $activo['estado'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <a href="index.php?view=inventario"
                                class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center gap-1">
                                <i class="ri-external-link-line"></i> Ver inventario completo
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Columna Derecha: Sistema e Historial -->
        <div class="space-y-6">

            <!-- Historial de Movimientos -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-full">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i class="ri-time-line text-blue-500"></i> Historial Laboral
                    </h3>
                </div>
                <div class="p-6 flex-1 overflow-y-auto max-h-[500px]">
                    <?php if (empty($historial)): ?>
                        <div class="text-center py-6">
                            <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="ri-history-line text-slate-300 text-xl"></i>
                            </div>
                            <p class="text-slate-500 text-sm">No hay movimientos registrados.</p>
                        </div>
                    <?php else: ?>
                        <div class="relative border-l-2 border-slate-100 ml-3 space-y-6 pb-2">
                            <?php foreach ($historial as $h): ?>
                                <div class="ml-6 relative">
                                    <!-- Punto en la línea -->
                                    <span class="absolute -left-[31px] top-0 w-4 h-4 rounded-full border-2 border-white 
                                        <?php
                                        echo match ($h['tipo_cambio']) {
                                            'Ingreso' => 'bg-emerald-500',
                                            'Promoción' => 'bg-blue-500',
                                            'Transferencia' => 'bg-purple-500',
                                            'Salida' => 'bg-red-500',
                                            default => 'bg-slate-400'
                                        };
                                        ?>">
                                    </span>

                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-sm font-bold text-slate-800"><?php echo $h['tipo_cambio']; ?></h4>
                                        <span
                                            class="text-xs text-slate-400 font-mono"><?php echo date('d/m/Y', strtotime($h['fecha_efectiva'])); ?></span>
                                    </div>

                                    <p class="text-xs text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 mb-1">
                                        <?php if ($h['tipo_cambio'] == 'Transferencia'): ?>
                                            Cambio de sucursal.
                                        <?php elseif ($h['tipo_cambio'] == 'Promoción'): ?>
                                            Nuevo cargo: <strong><?php echo htmlspecialchars($h['cargo_nuevo']); ?></strong>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($h['descripcion']); ?>
                                        <?php endif; ?>
                                    </p>

                                    <!-- Detalles del cambio (si aplica) -->
                                    <?php if ($h['cargo_anterior'] != $h['cargo_nuevo']): ?>
                                        <div class="mt-1 text-[10px] text-slate-500">
                                            <span class="line-through"><?php echo $h['cargo_anterior']; ?></span>
                                            <i class="ri-arrow-right-line mx-1"></i>
                                            <span class="text-blue-600 font-medium"><?php echo $h['cargo_nuevo']; ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-2 text-[10px] text-slate-400 flex items-center gap-1">
                                        <i class="ri-user-line"></i> Reg. por:
                                        <?php echo htmlspecialchars($h['usuario_nombre'] ?? 'Sistema'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notas -->
            <?php if (!empty($empleado['notas'])): ?>
                <div class="bg-amber-50 rounded-xl border border-amber-100 p-4">
                    <h4 class="text-amber-800 font-bold text-sm mb-2 flex items-center gap-2">
                        <i class="ri-sticky-note-line"></i> Notas Adicionales
                    </h4>
                    <p class="text-sm text-amber-700 italic">
                        "<?php echo nl2br(htmlspecialchars($empleado['notas'])); ?>"
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>